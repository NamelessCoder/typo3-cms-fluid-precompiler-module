<?php
defined('TYPO3_MODE') or die('Access denied');

if ('BE' === TYPO3_MODE) {
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms_fluid_precompiler_module']['setup'] = unserialize($_EXTCONF);

    if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms_fluid_precompiler_module']['setup']['backendModule'] ?? true) {
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
            'NamelessCoder.CmsFluidPrecompilerModule',
            'tools',
            'txfluidprecompilermoduleM1',
            '',
            array(
                'Module' => 'index,compile',
            ),
            array(
                'access' => 'user,group',
                'icon' => 'EXT:cms_fluid_precompiler_module/ext_icon.svg',
                'labels' => 'LLL:EXT:cms_fluid_precompiler_module/Resources/Private/Language/locallang.xlf'
            )
        );

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
            'cms_fluid_precompiler_module',
            'EXT:cms_fluid_precompiler_module/Resources/Private/Language/locallang.xlf'
        );
    }

}
