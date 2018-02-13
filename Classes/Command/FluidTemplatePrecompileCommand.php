<?php
namespace NamelessCoder\CmsFluidPrecompilerModule\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3Fluid\Fluid\Core\Cache\FluidCacheWarmupResult;

/**
 * A console command for precompiling Fluid templates
 */
class FluidTemplatePrecompileCommand extends Command
{
    use FluidPrecompilerServiceTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Precompile Fluid templates')
            ->addArgument(
                'extension',
                InputArgument::OPTIONAL,
                'Extension key of which the fluid template should be compiled, leave empty to compile all',
                null
            )
            ->addOption(
                'fail',
                null,
                InputOption::VALUE_NONE,
                'Fail with a non-zero return code if any template could not be compiled'
            )
            ->addOption(
                'show-only-failed',
                null,
                InputOption::VALUE_NONE,
                'Show only failed fluid templates from compiling'
            )
            ->addOption(
                'limit',
                'l',
                InputOption::VALUE_REQUIRED,
                'Limit of template files of each extension shown',
                5
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $outputHelper = new SymfonyStyle($input, $output);

        // Test exception handlers
        if (set_error_handler(function() {})) {
            $outputHelper->warning(
                "\nIt seems like there is an error handler registered."
                . "\nIn some cases this can prevent the precompiling to run properly."
                . "\nAn ErrorHandler might raise an exception for a PHP Warning."
            );
        }
        restore_error_handler();

        // Get arguments and options
        $extension = $input->getArgument('extension');
        $fail = $input->getOption('fail');
        $onlyFailed = $input->getOption('show-only-failed');
        $limit = $input->getOption('limit');

        $lastError = null;

        try {
            $result = $this->getPrecompilerService()->warmup($extension);
        } catch (\Exception $e) {
            $result = [];
            $lastError = $e;
        }

        foreach ($result as $extensionKey => $extensionResult) {
            $templates = $extensionResult['results'];

            if ($onlyFailed) {
                $templates = array_filter($templates, function ($template) {
                    return false == $template[FluidCacheWarmupResult::RESULT_COMPILABLE];
                });
            }

            $templates = array_slice($templates, 0, $limit);

            $templateRows = [];
            foreach ($templates as $templateName => $templateAttributes) {
                $templateRows[] = ['Template', $templateName];

                foreach ($templateAttributes as $attributeName => $attributeValue) {
                    $templateRows[] = [
                        $attributeName,
                        is_array($attributeValue) ? implode(PHP_EOL, $attributeValue) : $attributeValue
                    ];
                }

                $templateRows[] = new TableSeparator();
            }

            array_pop($templateRows);

            $outputHelper->table(
                ['Compile Results', vsprintf('Extension %s (%d uncompilable). Showing %d.', [$extensionKey, $extensionResult['uncompilable'], count($templates)])],
                $templateRows
            );
        }

        if ($result) {
            $uncompilable = array_column($result, 'uncompilable');
            $uncompilable = array_sum($uncompilable);

            if ($uncompilable > 0) {
                $lastError = new \Exception(sprintf('Could not compile %d templates.', $uncompilable), 1518205183);
            }
        }

        if ($fail && null !== $lastError) {
            throw $lastError;
        } elseif (!$fail) {
            $outputHelper->error(
                vsprintf(
                    "Got an error (because --no-fail is set, the return code equals zero)\nError: %s\nReason(%d): %s",
                    [get_class($lastError), $lastError->getCode(), $lastError->getMessage()]
                )
            );
        }
    }
}