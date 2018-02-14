<?php
namespace NamelessCoder\CmsFluidPrecompilerModule\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\Output;
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
            ->setDescription('Precompile a Fluid resource')
            ->addArgument(
                'extension',
                InputArgument::OPTIONAL,
                'Extension key of which the Fluid resources should be compiled. Leave empty to compile all',
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
                'Show only failed Fluid templates which failed to compile (in conjunction with -v)'
            )
            ->addOption(
                'limit',
                'l',
                InputOption::VALUE_REQUIRED,
                'Limit template information shown of each extension (in conjunction with -v)',
                5
            )
            ->addOption(
                'disable-error-handler',
                null,
                InputOption::VALUE_NONE,
                'Temporarily disable any registered error handler for compiling'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $outputHelper = new SymfonyStyle($input, $output);

        // Get arguments and options
        $extension = $input->getArgument('extension');
        $fail = $input->getOption('fail');
        $onlyFailed = $input->getOption('show-only-failed');
        $limit = $input->getOption('limit');
        $disableErrorHandlers = $input->getOption('disable-error-handler');

        // Test exception handlers
        if (set_error_handler(function() {}) && !$disableErrorHandlers) {
            $outputHelper->warning(
                "\nIt seems like there is an error handler registered."
                . "\nIn some cases this can prevent the precompiling to run properly."
                . "\nAn ErrorHandler might raise an exception for a PHP Warning."
            );

            restore_error_handler();
        }

        $result = $this->getPrecompilerService()->warmup($extension);

        // General information
        $templatesCount = 0;
        foreach ($result as $extensionResult) {
            $templatesCount += count($extensionResult);
        }

        $uncompilable = array_column($result, 'uncompilable');
        $uncompilable = array_sum($uncompilable);

        $outputHelper->section('General');
        $outputHelper->text(vsprintf('Compiled %d extension(s) with %d template(s) in total.', [count($result), $templatesCount]));
        $outputHelper->text(vsprintf('%d template(s) could not be compiled.', [$uncompilable]));

        // Additional information
        if ($output->isVerbose()) {
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
                            is_array($attributeValue) ? implode(PHP_EOL, $attributeValue) : static::formatReadable($attributeValue)
                        ];
                    }

                    $templateRows[] = new TableSeparator();
                }

                array_pop($templateRows);

                $outputHelper->section(sprintf('Extension: %s (%d uncompilable of %d)', $extensionKey, $extensionResult['uncompilable'], count($templates)));

                $outputHelper->table(
                    [],
                    $templateRows
                );
            }
        }

        if ($disableErrorHandlers) {
            restore_error_handler();
        }

        if ($fail && $uncompilable > 0) {
            throw new \Exception(sprintf('Could not compile %d templates.', $uncompilable), 1518205183);
        }
    }

    /**
     * Formats the given input in a readable format
     *
     * @param mixed $data
     * @return string
     */
    protected static function formatReadable($data)
    {
        if (is_bool($data)) {
            $data = $data ? 'yes' : 'no';
        }

        return $data;
    }
}