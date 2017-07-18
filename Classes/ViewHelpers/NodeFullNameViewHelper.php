<?php
namespace NamelessCoder\CmsFluidPrecompilerModule\ViewHelpers;

use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\Expression\ExpressionNodeInterface;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\NodeInterface;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithContentArgumentAndRenderStatic;

/**
 * Class NodeFullNameViewHelper
 */
class NodeFullNameViewHelper extends AbstractViewHelper
{
    use CompileWithContentArgumentAndRenderStatic;

    public function initializeArguments()
    {
        $this->registerArgument('node', NodeInterface::class, 'Node to return short name (prefix-less)');
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return mixed
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        return array_pop(explode('\\', get_class($renderChildrenClosure())));
    }
}
