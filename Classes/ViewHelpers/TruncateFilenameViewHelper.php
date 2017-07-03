<?php
namespace NamelessCoder\CmsFluidPrecompilerModule\ViewHelpers;

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithContentArgumentAndRenderStatic;
use TYPO3Fluid\Fluid\View\TemplatePaths;

/**
 * Class TruncateFilenameViewHelper
 */
class TruncateFilenameViewHelper extends AbstractViewHelper
{
    use CompileWithContentArgumentAndRenderStatic;

    /**
     * @return void
     */
    public function initializeArguments()
    {
        $this->registerArgument('filename', 'string', 'Filename to truncate - strips off PATH_site');
        $this->registerArgument('paths', TemplatePaths::class, 'Paths in which the template file may be found');
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return mixed
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $trim = strlen(PATH_site);
        $file = $renderChildrenClosure();
        if (isset($arguments['paths'])) {
            /** @var TemplatePaths $paths */
            $paths = $arguments['paths'];
            foreach (array_merge($paths->getTemplateRootPaths(), $paths->getPartialRootPaths(), $paths->getLayoutRootPaths()) as $path) {
                if (strpos($file, $path) === 0) {
                    $trim = strlen($path);
                    break;
                }
            }


        }
        return substr($file, $trim);
    }
}
