<?php
namespace NamelessCoder\CmsFluidPrecompilerModule\Controller;

use NamelessCoder\CmsFluidPrecompilerModule\Service\FluidPrecompilerService;
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
    public function precompileAction()
    {
        GeneralUtility::makeInstance(ObjectManager::class)->get(FluidPrecompilerService::class)->warmup();
    }
}
