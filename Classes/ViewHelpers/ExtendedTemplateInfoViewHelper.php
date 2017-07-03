<?php
namespace NamelessCoder\CmsFluidPrecompilerModule\ViewHelpers;

use NamelessCoder\CmsFluidPrecompilerModule\Service\FluidPrecompilerService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Class ExtendedTemplateInfoViewHelper
 */
class ExtendedTemplateInfoViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * @var boolean
     */
    protected $escapeOutput = false;

    /**
     * @return void
     */
    public function initializeArguments()
    {
        $this->registerArgument('template', 'string', 'Absolute path to the template file', true);
        $this->registerArgument('extensionKey', 'string', 'Extension key in which the template file belongs', true);
        $this->registerArgument('as', 'string', 'Name of template variable to assign', false, 'info');
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return mixed
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $service = GeneralUtility::makeInstance(ObjectManager::class)->get(FluidPrecompilerService::class);
        $renderingContext->getVariableProvider()->add($arguments['as'], $service->profileTemplateFile($arguments['template'], $arguments['extensionKey']));
        $content = $renderChildrenClosure();
        $renderingContext->getVariableProvider()->remove($arguments['as']);
        return $content;
    }
}
