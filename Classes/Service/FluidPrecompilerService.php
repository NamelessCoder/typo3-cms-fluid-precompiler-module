<?php
namespace NamelessCoder\CmsFluidPrecompilerModule\Service;

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ArrayNode;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\BooleanNode;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\EscapingNode;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\Expression\ExpressionNodeInterface;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\NumericNode;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\RootNode;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\TextNode;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\Compiler\StopCompilingException;
use TYPO3Fluid\Fluid\Core\Parser\ParsingState;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\NodeInterface;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
use TYPO3Fluid\Fluid\View\Exception\InvalidTemplateResourceException;
use TYPO3Fluid\Fluid\ViewHelpers\SectionViewHelper;

/**
 * Class FluidPrecompilerService
 */
class FluidPrecompilerService implements SingletonInterface
{
    /**
     * @param null $extensionKey
     * @return array
     */
    public function warmup($extensionKey = null)
    {
        $results = [];
        $extensionKeys = $extensionKey ? [$extensionKey] : ExtensionManagementUtility::getLoadedExtensionListArray();
        $context = $this->getRenderingContext('core');
        $warmer = $context->getCache()->getCacheWarmer();
        foreach ($extensionKeys as $extensionKey) {
            $extensionContext = $this->getRenderingContext($extensionKey);
            $extensionResults = $warmer->warm($extensionContext)->getResults();
            $context->getTemplateCompiler()->reset();
            if (!empty($extensionResults)) {
                $uncompilableCount = $this->countUncompilableTemplates($extensionResults);
                if ($uncompilableCount) {
                    foreach ($extensionResults as $templatePathAndFilename => $fileResult) {
                        if (!$fileResult['compilable'] && !isset($fileResult['failure'])) {
                            $extensionResults[$templatePathAndFilename] += $this->analyzeTemplateFile($templatePathAndFilename, $extensionKey);
                        }
                    }
                }
                $results[$extensionKey] = [
                    'results' => $extensionResults,
                    'context' => $extensionContext,
                    'uncompilable' => $uncompilableCount
                ];
            }
        }
        return $results;
    }

    /**
     * @param string $templatePathAndFilename
     * @param string $extensionKey
     * @return array
     */
    public function profileTemplateFile($templatePathAndFilename, $extensionKey)
    {
        $context = $this->getRenderingContext($extensionKey);
        $context->getTemplatePaths()->fillDefaultsByPackageName($extensionKey);
        $source = file_get_contents($templatePathAndFilename);
        $parsedTemplate = $context->getTemplateParser()->parse($source);
        $layoutName = $parsedTemplate->hasLayout() ? $parsedTemplate->getLayoutName($context) : null;
        $helpers = $this->collectViewHelperUsages($parsedTemplate, $context);
        return [
            'source' => $source,
            'context' => $context,
            'identifier' => $parsedTemplate->getIdentifier(),
            'compilable' => $parsedTemplate->isCompilable(),
            'nodes' => $parsedTemplate->countNodeStack(),
            'layoutName' => $layoutName,
            'layoutFilename' => $layoutName ? $context->getTemplatePaths()->getLayoutPathAndFilename($layoutName) : null,
            'fanouts' => $this->collectRenderedPartialNames($parsedTemplate, $context),
            'sections' => $this->collectDefinedSectionNames($parsedTemplate, $context),
            'helpers' => $helpers,
            'efficiency' => $this->determineCompiledEfficiency($helpers)
        ];
    }

    protected function determineCompiledEfficiency(array $helpers)
    {
        if (count($helpers) === 0) {
            return 2;
        }
        return array_sum(array_column($helpers, 'efficiency')) / count($helpers);
    }

    /**
     * @param ParsingState $parsingState
     * @return array
     */
    protected function collectViewHelperUsages(ParsingState $parsingState, RenderingContextInterface $renderingContext)
    {
        return $this->collectViewHelpersAndUsageCountsFromNodeStack($parsingState->getRootNode(), $renderingContext);
    }

    /**
     * @param NodeInterface $node
     * @param RenderingContextInterface $renderingContext
     * @param array $usages
     * @return array
     */
    protected function collectViewHelpersAndUsageCountsFromNodeStack(NodeInterface $node, RenderingContextInterface $renderingContext, array $usages = [])
    {
        $escaped = false;
        if ($node instanceof EscapingNode) {
            $node = $node->getNode();
            $escaped = true;
        }
        if ($node instanceof ViewHelperNode) {
            $identifier = $node->getViewHelperClassName();
            if (!isset($usages[$identifier])) {
                $usages[$identifier]['usages'] = 0;
                $usages[$identifier]['escaped'] = $escaped;
                try {
                    $code = '';
                    $compiled = $node->getUninitializedViewHelper()->compile('void', 'void', $code, $node, $renderingContext->getTemplateCompiler());
                    // Guesstimate the efficiency once compiled. Neutral is default compiling, semi-good is renderstatic, best is custom compile output.
                    if (strpos($compiled, '->getViewHelperInvoker()->invoke') !== false) {
                        $efficiency = 0;
                    } elseif (strpos($compiled, sprintf('%s::renderStatic', $identifier)) !== false) {
                        $efficiency = 1;
                    } else {
                        $efficiency = 2;
                    }
                    $usages[$identifier]['compilable'] = true;
                    $usages[$identifier]['efficiency'] = $efficiency;
                } catch (StopCompilingException $exception) {
                    $usages[$identifier]['compilable'] = false;
                    // Uncompilable ViewHelpers automatically detract so much from performance that the result is always below zero.
                    $usages[$identifier]['efficiency'] = -100000000;
                }
            }
            ++ $usages[$identifier]['usages'];
        }
        $nodes = $node->getChildNodes();
        foreach ($nodes as $childNode) {
            $usages = $this->collectViewHelpersAndUsageCountsFromNodeStack($childNode, $renderingContext, $usages);
        }
        return $usages;
    }

    /**
     * @param ParsingState $parsingState
     * @param RenderingContextInterface $renderingContext
     * @return array
     */
    protected function collectDefinedSectionNames(ParsingState $parsingState, RenderingContextInterface $renderingContext)
    {
        return $this->collectSectionNamesFromNodeStack($parsingState->getRootNode(), $renderingContext);
    }

    /**
     * @param NodeInterface $node
     * @param RenderingContextInterface $renderingContext
     * @return array
     */
    protected function collectSectionNamesFromNodeStack(NodeInterface $node, RenderingContextInterface $renderingContext)
    {
        $names = [];
        if ($node instanceof ViewHelperNode && is_a($node->getViewHelperClassName(), SectionViewHelper::class, true)) {
            $names[] = $node->getArguments()['name']->evaluate($renderingContext);
        }
        foreach ($node->getChildNodes() as $childNode) {
            $names = array_merge($names, $this->collectSectionNamesFromNodeStack($childNode, $renderingContext));
        }
        return $names;
    }

    /**
     * @param ParsingState $parsingState
     * @param RenderingContextInterface $renderingContext
     * @return array
     */
    protected function collectRenderedPartialNames(ParsingState $parsingState, RenderingContextInterface $renderingContext)
    {
        return $this->collectPartialNamesFromNodeStack($parsingState->getRootNode(), $renderingContext);
    }

    /**
     * @param NodeInterface $node
     * @param RenderingContextInterface $renderingContext
     * @return array
     */
    protected function collectPartialNamesFromNodeStack(NodeInterface $node, RenderingContextInterface $renderingContext)
    {
        $partials = [];
        if (($partial = $this->isRenderCallToPartial($node, $renderingContext))) {
            $partials[] = $partial;
        }
        foreach ($node->getChildNodes() as $childNode) {
            $partials = array_merge($partials, $this->collectPartialNamesFromNodeStack($childNode, $renderingContext));
        }
        return array_unique($partials);
    }

    /**
     * @param NodeInterface $node
     * @param RenderingContextInterface $renderingContext
     * @return bool|array
     */
    protected function isRenderCallToPartial(NodeInterface $node, RenderingContextInterface $renderingContext)
    {
        if ($node instanceof ViewHelperNode) {
            $arguments = $node->getArguments();
            if (isset($arguments['partial'])) {
                $partialName = $arguments['partial']->evaluate($renderingContext);
                try {
                    $partialFilename = $renderingContext->getTemplatePaths()->getPartialPathAndFilename($partialName);
                } catch (InvalidTemplateResourceException $error) {
                    $partialFilename = null;
                }
                return [
                    'partial' => $partialName,
                    'filename' => $partialFilename,
                    'section' => isset($arguments['section']) ? $arguments['section']->evaluate($renderingContext) : null,
                    'arguments' => $this->extractArgumentDescriptionsFromViewHelperNode($node)
                ];
            }
        }
        return false;
    }

    /**
     * @param ViewHelperNode $node
     * @return array
     */
    protected function extractArgumentDescriptionsFromViewHelperNode(ViewHelperNode $node)
    {
        $arguments = [];
        /** @var RootNode $argumentsNode */
        $argumentsNode = $node->getArguments()['arguments'] ?? null;
        if (!$argumentsNode || !count($argumentsNode->getChildNodes())) {
            return [];
        }
        $argumentsNode = $argumentsNode->getChildNodes()[0];
        if ($argumentsNode instanceof ObjectAccessorNode) {
            if ($argumentsNode->getObjectPath() === '_all') {
                return ['_all' => ['type' => 'Passthrough arguments', 'source' => 'all']];
            } else {
                return [$argumentsNode->getObjectPath() => ['type' => 'Invalid', 'source' => 'Likely template error!']];
            }
        }
        foreach ($argumentsNode->getInternalArray() as $name => $argumentNode) {
            if ($argumentNode instanceof TextNode) {
                $type = 'TextNode';
                $source = $argumentNode->getText();
            } elseif ($argumentNode instanceof ViewHelperNode) {
                $type = 'ViewHelperNode';
                $source = $argumentNode->getViewHelperClassName();
            } elseif ($argumentNode instanceof ObjectAccessorNode) {
                $type = 'ObjectAccessorNode';
                $source = '{' . $argumentNode->getObjectPath() . '}';
            } elseif ($argumentNode instanceof ExpressionNodeInterface) {
                $type = 'ExpressionNode (' . get_class($argumentNode) . ')';
                $source = $argumentNode->getExpression();
            } elseif ($argumentNode instanceof NumericNode) {
                $type = 'Numeric';
                $source = $argumentNode->getValue();
            } elseif ($argumentNode instanceof BooleanNode) {
                $type = 'Boolean';
                $source = json_encode($argumentNode->getStack());
            } elseif ($argumentNode instanceof ArrayNode) {
                $type = 'Array';
                $source = 'Members: ' . array_keys($argumentNode->getInternalArray());
            } elseif ($argumentNode instanceof RootNode) {
                $type = 'RootNode';
                $source = 'Root with ' . count($argumentNode->getChildNodes()) . ' child node(s)';
            } else {
                $type = 'Unknown (' . (is_object($argumentNode) ? get_class($argumentNode) : gettype($argumentNode)) . ')';
                $source = 'Unknown';
            }
            $arguments[$name] = [
                'type' => $type,
                'source' => $source
            ];
        }
        return $arguments;
    }

    /**
     * Returns an array of ["failure" => "Description of problem", "mitigation" => ["More detailed description of reason"]]
     * for why a template file cannot be compiled. Implies that the template file gets converted to compiled code
     * without doing so through the normal approach which catches StopCompilingExceptions (which we want to know about
     * in this particular case).
     *
     * The return format is compatible with return formats from the normal warmup procedure and allows the array to be
     * merged directly to fill info about uncompilable templates.
     *
     * @param string $templatePathAndFilename
     * @param string $extensionKey
     * @return array
     */
    public function analyzeTemplateFile($templatePathAndFilename, $extensionKey)
    {
        $context = $this->getRenderingContext($extensionKey);
        $parsedTemplate = $context->getTemplateParser()->parse(file_get_contents($templatePathAndFilename));
        try {
            $context->getTemplateCompiler()->store('void_identifier', $parsedTemplate);
        } catch (StopCompilingException $error) {
            $reason = 'Unknown';
            foreach ($error->getTrace() as $line) {
                if (isset($line['args'][0]) && is_a($line['args'][0], ViewHelperNode::class, true)) {
                    // We have to resort to reflection here due to protected property.
                    $reflectedProperty = new \ReflectionProperty(ViewHelperNode::class, 'pointerTemplateCode');
                    $reflectedProperty->setAccessible(true);
                    $reason = $reflectedProperty->getValue($line['args'][0]);
                    break;
                }
            }
            return [
                'failure' => 'Compiling was stopped by StopCompilingException',
                'mitigations' => [
                    'Reconsider use of the following suspect ViewHelper(s) which caused compiling to stop: <code>' . htmlspecialchars($reason) . '</code> '
                ]
            ];
        }
        return [
            'failure' => 'Unknown failure - template seems to compile without error but no compiled file was yielded',
            'mitigations' => ['None, please inspect file manually']
        ];
    }

    /**
     * @param string $extensionKey
     * @return RenderingContextInterface
     */
    protected function getRenderingContext($extensionKey): RenderingContextInterface
    {
        $context = GeneralUtility::makeInstance(ObjectManager::class)->get(RenderingContext::class);
        $context->getTemplatePaths()->fillDefaultsByPackageName($extensionKey);
        return $context;
    }

    /**
     * @param array $results
     * @return integer
     */
    protected function countUncompilableTemplates(array $results)
    {
        return count($results) - array_sum(array_column($results, 'compilable'));
    }
}
