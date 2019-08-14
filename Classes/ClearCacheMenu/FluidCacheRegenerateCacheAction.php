<?php

namespace NamelessCoder\CmsFluidPrecompilerModule\ClearCacheMenu;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Toolbar\ClearCacheActionsHookInterface;

/**
 * Class FluidCacheRegenerateCacheAction
 */
class FluidCacheRegenerateCacheAction implements ClearCacheActionsHookInterface
{
    /**
     * @param array $cacheActions
     * @param array $optionValues
     * @return void
     */
    public function manipulateCacheActions(&$cacheActions, &$optionValues)
    {
        $cacheActions[] = [
            'id' => 'fluid-cache-regenerate',
            'title' => 'LLL:EXT:cms_fluid_precompiler_module/Resources/Private/Language/locallang.xlf:cachemenuitem.title',
            'description' => 'LLL:EXT:cms_fluid_precompiler_module/Resources/Private/Language/locallang.xlf:cachemenuitem.description',
            'href' => (new UriBuilder())->buildUriFromRoute('fluid_precompile'),
            'iconIdentifier' => 'fluid-precompile-cache-menu-action'
        ];
    }

}
