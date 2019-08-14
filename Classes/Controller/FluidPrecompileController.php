<?php

namespace NamelessCoder\CmsFluidPrecompilerModule\Controller;

use NamelessCoder\CmsFluidPrecompilerModule\Service\FluidPrecompilerService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class FluidPrecompileController
 */
class FluidPrecompileController
{
    /**
     * @param ServerRequestInterface|null $request
     * @param ResponseInterface|null $response
     * @return ResponseInterface|null
     */
    public function precompileAction(ServerRequestInterface $request = null, ResponseInterface $response = null)
    {
        GeneralUtility::makeInstance(ObjectManager::class)->get(FluidPrecompilerService::class)->warmup();

        return $response;
    }
}
