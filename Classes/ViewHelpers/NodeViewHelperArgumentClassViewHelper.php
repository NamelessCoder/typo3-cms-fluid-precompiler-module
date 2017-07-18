<?php
namespace NamelessCoder\CmsFluidPrecompilerModule\ViewHelpers;

use TYPO3Fluid\Fluid\Core\Parser\Exception;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ArrayNode;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\NodeInterface;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\NumericNode;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\TextNode;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Class NodeViewHelperArgumentClassViewHelper
 */
class NodeViewHelperArgumentClassViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * @return void
     */
    public function initializeArguments()
    {
        $this->registerArgument('parent', ViewHelperNode::class, 'Node which contains arguments (ViewHelperNode)');
        $this->registerArgument('argument', NodeInterface::class, 'Name of argument', true);
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return mixed
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $parent = $arguments['parent'];
        if (!$parent instanceof ViewHelperNode) {
            return '';
        }
        $passedArguments = $parent->getArguments();
        $argument = $arguments['argument'];
        $passedValue = static::evaluateSimpleNode(($parent->getArguments()[$argument] ?? null));
        $argumentDefinition = $parent->getArgumentDefinition($argument);
        $additionalArguments = array_diff_key($passedArguments, $parent->getArgumentDefinitions());

        if (!$argumentDefinition) {
            try {
                $parent->getUninitializedViewHelper()->validateAdditionalArguments($additionalArguments);
                return 'warning';
            } catch (Exception $error) {
                return 'danger';
            } catch (\TYPO3Fluid\Fluid\Core\ViewHelper\Exception $error) {
                return 'danger';
            }
        }
        $defaultValue = $argumentDefinition->getDefaultValue();
        if ($defaultValue == $passedValue) {
            return 'info';
        }
        if (!$argumentDefinition->isRequired()) {
            return 'success';
        }

        return '';
    }

    /**
     * @param NodeInterface|null $node
     * @return mixed
     */
    protected static function evaluateSimpleNode(NodeInterface $node = null)
    {
        if ($node instanceof TextNode) {
            return $node->getText();
        }
        if ($node instanceof NumericNode) {
            return $node->getValue();
        }
        if ($node instanceof ArrayNode) {
            return $node->getInternalArray();
        }

        return 'Unknown';
    }
}
