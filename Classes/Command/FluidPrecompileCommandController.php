<?php
namespace NamelessCoder\CmsFluidPrecompilerModule\Command;

use NamelessCoder\CmsFluidPrecompilerModule\Service\FluidPrecompilerService;
use Symfony\Component\Console\Helper\TableSeparator;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3Fluid\Fluid\Core\Cache\FluidCacheWarmupResult;

/**
 * Class FluidPrecompileCommandController
 */
class FluidPrecompileCommandController extends CommandController
{
    /**
     * Precompile fluid templates
     *
     * (Pre-)compiles all fluid templates to static files of the given or all extension(s).
     * 
     * @param string $extension extension key of which the fluid template should be compiled, leave empty to compile all
     * @param bool $fail fail with a non-zero return code if any template could not be compiled
     * @param bool $onlyFailed show only failed fluid templates from compiling
     * @param int $limit limit of template files of each extension shown
     * @throws \Exception
     */
    public function compileCommand($extension = null, $fail = true, $onlyFailed = true, $limit = null)
    {
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

            $this->output->outputTable(
                $templateRows,
                ['Compile Results', vsprintf('Extension %s (%d uncompilable). Showing %d.', [$extensionKey, $extensionResult['uncompilable'], count($templates)])]
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
            $this->outputLine();
            $this->outputFormatted(
                vsprintf(
                    "Got an error (because --no-fail is set, the return code equals zero)\nError: %s\nReason(%d): %s\n\n%s",
                    [get_class($lastError), $lastError->getCode(), $lastError->getMessage(), $lastError->getTraceAsString()]
                ),
                [],
                2
            );
            $this->outputLine();
        }
    }

    /**
     * Receives an instance of the FluidPrecompilerService
     *
     * @return FluidPrecompilerService
     */
    protected function getPrecompilerService()
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        return $objectManager->get(FluidPrecompilerService::class);
    }
}