<?php
namespace NamelessCoder\CmsFluidPrecompilerModule\ViewHelpers;

use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\Expression\ExpressionNodeInterface;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\NodeInterface;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithContentArgumentAndRenderStatic;

/**
 * Class NodeMaxIndentViewHelper
 */
class NodeMaxIndentViewHelper extends AbstractViewHelper
{
    use CompileWithContentArgumentAndRenderStatic;

    public function initializeArguments()
    {
        $this->registerArgument('node', NodeInterface::class, 'Node to analyse for maximum depth');
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return mixed
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        return static::calculateDepth($renderChildrenClosure());
    }

    /**
     * @param NodeInterface $node
     * @param integer $depth
     * @return integer
     */
    protected static function calculateDepth(NodeInterface $node, $depth = 0)
    {
        $detected = $depth + 1;
        foreach ($node->getChildNodes() as $childNode) {
            $detected = max($detected, static::calculateDepth($childNode, $depth + 1));
        }
        return $detected;
    }
}
