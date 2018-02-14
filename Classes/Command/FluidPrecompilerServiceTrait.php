<?php
namespace NamelessCoder\CmsFluidPrecompilerModule\Command;

use NamelessCoder\CmsFluidPrecompilerModule\Service\FluidPrecompilerService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Trait used by commands to get the FluidPrecompilerService
 */
trait FluidPrecompilerServiceTrait
{
    /**
     * Receives an instance of the FluidPrecompilerService
     *
     * @return FluidPrecompilerService
     */
    protected function getPrecompilerService()
    {
        return GeneralUtility::makeInstance(FluidPrecompilerService::class);
    }
}