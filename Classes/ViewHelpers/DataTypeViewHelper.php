<?php
namespace NamelessCoder\CmsFluidPrecompilerModule\ViewHelpers;

use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\Expression\ExpressionNodeInterface;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\NodeInterface;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithContentArgumentAndRenderStatic;

/**
 * Class DataTypeViewHelper
 */
class DataTypeViewHelper extends AbstractViewHelper
{
    use CompileWithContentArgumentAndRenderStatic;

    public function initializeArguments()
    {
        $this->registerArgument('value', 'mixed', 'Value to return type of');
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return mixed
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        return gettype($renderChildrenClosure());
    }
}
