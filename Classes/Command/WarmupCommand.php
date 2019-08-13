<?php
namespace NamelessCoder\CmsFluidPrecompilerModule\Command;

use NamelessCoder\CmsFluidPrecompilerModule\Service\FluidPrecompilerService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\NodeInterface;

/**
 * Class WarmupCommand
 */
class WarmupCommand extends Command
{
    /**
     * Configure the command by defining the name, options and arguments
     */
    public function configure()
    {
        $this->setDescription('Warm up Fluid caches')
            ->addOption(
                'extension-key',
                'e',
                InputOption::VALUE_OPTIONAL,
                'Specific extension key to process, if empty, processes all'
            )->addOption(
                'fail',
                'f',
                InputOption::VALUE_OPTIONAL,
                'Fail if any templates cannot be compiled',
                false
            )->addOption(
                'profile',
                'p',
                InputOption::VALUE_OPTIONAL,
                'Profile templates',
                false
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $service = GeneralUtility::makeInstance(ObjectManager::class)->get(FluidPrecompilerService::class);

        $processExtensionKey = $input->hasOption('extension-key') ? $input->getOption('extension-key') : null;
        $failOnUncompilable = (bool)($input->hasOption('fail') ? ((string)$input->getOption('fail') === ''? true : $input->getOption('fail'))  : false);
        $profile = (bool)($input->hasOption('profile') ? ((string)$input->getOption('profile') === '' ? true : $input->getOption('profile'))  : false);

        $extensionKeys = $processExtensionKey ? [$processExtensionKey] : ExtensionManagementUtility::getLoadedExtensionListArray();
        $uncompilable = [];

        foreach ($extensionKeys as $extensionKey) {
            $output->write($extensionKey . ':');
            $results = $service->warmup($extensionKey)[$extensionKey];
            $numberOfTemplates = count($results['results'] ?? 0);

            if ($numberOfTemplates === 0) {
                $output->write(' ' . 0, true);
                continue;
            }

            if (!$profile) {
                $output->write(' ' . $numberOfTemplates, true);
                continue;
            }

            $output->write('  templates: ' . $numberOfTemplates, true);

            if ($results['uncompilable'] > 0) {
                $output->write('  uncompilable: ' . $results['uncompilable'], true);
                $uncompilable[$extensionKey] = $extensionKey . ': ' . $results['uncompilable'];
            }

            if ($profile) {
                $profiles = [];
                $output->write('  profile:', true);
                foreach ($results['results'] as $templatePathAndFilename => $data) {
                    $output->write('    ' . $templatePathAndFilename . ':', true);
                    $templateProfile = $profiles[] = $service->profileTemplateFile($templatePathAndFilename, $extensionKey);
                    $context = $templateProfile['context'];
                    $helpers = $templateProfile['helpers'];
                    unset($templateProfile['context'], $templateProfile['source'], $templateProfile['rootNode'], $templateProfile['helpers']);
                    foreach ($templateProfile as $key => $value) {
                        $output->write('      ' . $key . ': ');
                        if ($value instanceof NodeInterface) {
                            $value = $value->evaluate($context);
                        }

                        if (is_array($value)) {
                            $output->write(json_encode($value), true);
                        } else {
                            $output->write(var_export($value, true), true);
                        }
                    }
                    $output->write('      helpers:', true);
                    foreach ($helpers as $helper => $data) {
                        $output->write('        ' . $helper . ': ' . json_encode($data), true);
                    }
                }
                $output->write('  average-efficiency: ' . number_format(array_sum(array_column($profiles, 'efficiency')) / $numberOfTemplates, 3), true);

            }
            $output->write('', true);
        }

        if ($failOnUncompilable && !empty($uncompilable)) {
            $output->write('Failures:', true);
            foreach ($uncompilable as $line) {
                $output->write('  ' . $line, true);
            }
            return 1;
        }
        return 0;
    }
}
