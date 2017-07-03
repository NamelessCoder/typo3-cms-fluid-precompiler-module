<?php
namespace NamelessCoder\CmsFluidPrecompilerModule\Controller;

use NamelessCoder\CmsFluidPrecompilerModule\Service\FluidPrecompilerService;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * Class ModuleController
 */
class ModuleController extends ActionController
{
    /**
     * @var FluidPrecompilerService
     */
    protected $precompilerService;

    /**
     * @param FluidPrecompilerService $precompilerService
     * @return void
     */
    public function injectPrecompilerService(FluidPrecompilerService $precompilerService)
    {
        $this->precompilerService = $precompilerService;
    }

    /**
     * @return void
     */
    public function indexAction()
    {
        $cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('fluid_template')->getBackend();
        $lifetimeProperty = new \ReflectionProperty($cache, 'defaultLifetime');
        $lifetimeProperty->setAccessible(true);
        $defaultLifetime = $lifetimeProperty->getValue($cache);
        if ($defaultLifetime < 86400) {
            $this->view->assign(
                'cacheAdvise',
                $defaultLifetime
            );
        }
        $this->view->assign('extensionKeys', ExtensionManagementUtility::getLoadedExtensionListArray());
    }

    /**
     * @param string $extensionKey
     * @param string $templateFile
     * @param boolean $verbose
     * @return void
     */
    public function compileAction($extensionKey = null, $templateFile = null, $verbose = false)
    {
        $extensionKey = $extensionKey === 'All' ? null : $extensionKey;
        $results = $this->precompilerService->warmup($extensionKey);
        $this->view->assign('extensionKey', $extensionKey);
        $this->view->assign('templateFile', $templateFile);
        $this->view->assign('verbose', $verbose);
        $this->view->assign('results', $results);
        if ($extensionKey && $templateFile) {
            $this->view->assign('result', $results[$extensionKey]['results'][$templateFile]);
        }
    }
}
