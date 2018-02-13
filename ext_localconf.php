<?php
defined('TYPO3_MODE') or die('Access denied');

if ('BE' === TYPO3_MODE) {
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms_fluid_precompiler_module']['setup'] = unserialize($_EXTCONF);

    if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms_fluid_precompiler_module']['setup']['cacheMenuItem'] ?? true) {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['additionalBackendItems']['cacheActions']['cms_fluid_precompiler_module'] =
            \NamelessCoder\CmsFluidPrecompilerModule\ClearCacheMenu\FluidCacheRegenerateCacheAction::class;

        \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class)->registerIcon(
            'fluid-precompile-cache-menu-action',
            \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
            [
                'source' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('cms_fluid_precompiler_module', 'Resources/Public/Icons/CacheMenuItem.png'),
                'size' => \TYPO3\CMS\Core\Imaging\Icon::SIZE_LARGE
            ]
        );
    }

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup(
        '
        plugin.tx_cmsfluidprecompilermodule.view {
            templateRootPaths {
                10 = EXT:fluid_precompiler_module/Resources/Private/Templates/
            }
            partialRootPaths {
                10 = EXT:fluid_precompiler_module/Resources/Private/Partials/
            }
            layoutRootPaths {
                10 = EXT:fluid_precompiler_module/Resources/Private/Layouts/
            }
        }
        module.tx_cmsfluidprecompilermodule.view <= plugin.tx_cmsfluidprecompilermodule.view 
        '
    );
}
