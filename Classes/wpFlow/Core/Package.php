<?php
/**
 * Created by PhpStorm.
 * User: Dennis
 * Date: 20.06.15
 * Time: 23:52
 */

namespace wpFlow\Core;

use wpFlow\Configuration\Config\ConfigManager;
use wpFlow\Configuration\Validation\ResourceConfiguration;
use wpFlow\Core\Resource\ResourceManager;
use wpFlow\Core\Utilities\Debug;
use wpFlow\Core\Package\Package as BasePackage;

class Package extends BasePackage {

    /**
     * @var boolean
     */
    protected $protected = TRUE;
    protected $configManagementEnabled = TRUE;
    protected $bootstrap;
    protected $configManager;
    protected $resourceManager;


    public function boot(Bootstrap $bootstrap){
        $this->bootstrap = $bootstrap;
        $activePackages = $this->packageManager->getActivePackages();
        $configManagementEnabledPackages = $this->packageManager->getConfigManagementEnabledPackages();

        $this->configManager = $bootstrap->registerDependency('configManager',new ConfigManager() );
        $this->resourceManager = $bootstrap->registerDependency('resourceManager', new ResourceManager());

        //initialize the config Manager
        $this->bootConfigManager($configManagementEnabledPackages);

        //initialize the Resource Manager
        $this->bootResourceManager($activePackages);

    }

    protected function bootConfigManager($configManagementEnabledPackages){
        $this->configManager->initialize($configManagementEnabledPackages, $this->bootstrap);
        $this->configManager->addConfigValidation('Resources.yaml', new ResourceConfiguration());
    }

    protected function bootResourceManager($activePackages){
        $resources = $this->packageManager->getPackagesConfigValues('Resources.yaml');
        $this->resourceManager->initialize($resources, $activePackages, $this->bootstrap);
    }

}