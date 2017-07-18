<?php
namespace NamelessCoder\CmsFluidPrecompilerModule\ViewHelpers;

use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\Expression\ExpressionNodeInterface;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\NodeInterface;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractConditionViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithContentArgumentAndRenderStatic;

/**
 * Class NodeFullNameViewHelper
 */
class IsNodeViewHelper extends AbstractConditionViewHelper
{
    /**
     * @return void
     */
    public function initializeArguments()
    {
        $this->registerArgument('node', 'mixed', 'Potential node', true);
    }

    /**
     * @return mixed|string
     */
    public function render()
    {
        return static::evaluateCondition($this->arguments) ? $this->renderThenChild() : $this->renderElseChild();
    }

    /**
     * @param null $arguments
     * @return boolean
     */
    protected static function evaluateCondition($arguments = null)
    {
        return $arguments['node'] instanceof NodeInterface;
    }
}
