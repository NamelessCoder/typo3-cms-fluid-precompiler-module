<?php declare(strict_types=1);

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
     * @return void
     */
    public function precompileAction(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        GeneralUtility::makeInstance(ObjectManager::class)->get(FluidPrecompilerService::class)->warmup();

        return $response;
    }
}
