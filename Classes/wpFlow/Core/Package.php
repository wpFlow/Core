<?php
/**
 * Created by PhpStorm.
 * User: Dennis
 * Date: 20.06.15
 * Time: 23:52
 */

namespace wpFlow\Core;

use wpFlow\Configuration\Config\ConfigManager;
use wpFlow\Core\Package\Package as BasePackage;
use wpFlow\Core\Resource\ResourceManager;
use wpFlow\Core\Utilities\Debug;

class Package extends BasePackage {

    /**
     * @var boolean
     */
    protected $protected = TRUE;
    protected $configManagementEnabled = true;
    protected $bootstrap;


    public function boot(Bootstrap $bootstrap){
        $this->bootstrap = $bootstrap;
        $activePackages = $this->packageManager->getActivePackages();

        //initialize the config Manager
        $this->bootConfigManager($activePackages);

        //initialize the Resource Manager
        $this->bootResourceManager($activePackages);

    }

    protected function bootConfigManager($activePackages){
        $configManager = new ConfigManager();
        $configManager->initialize($activePackages, $this->bootstrap);
    }

    protected function bootResourceManager($activePackages){
        $resources = $this->packageManager->getPackagesConfigValues('Resources.yaml');
        $resourceManager = new ResourceManager();
        $resourceManager->initialize($resources, $activePackages, $this->bootstrap);
    }


}