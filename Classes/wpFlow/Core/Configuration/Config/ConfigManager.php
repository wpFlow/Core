<?php
/**
 * Created by PhpStorm.
 * User: Dennis
 * Date: 20.06.15
 * Time: 23:16
 */

namespace wpFlow\Configuration\Config;


use Symfony\Component\Config\ConfigCache;
use wpFlow\Configuration\ConfigLoader;
use wpFlow\Configuration\Validation\ResourceConfiguration;
use wpFlow\Core\Bootstrap;
use wpFlow\Core\Utilities\Arrays;


class ConfigManager implements ConfigManagerInterface {

    const CACHE_DIR = WPFLOW_PATH_DATA;
    protected $bootstrap;
    /**
     * @var string
     */
    protected $packageBasePath = WPFLOW_PATH_PACKAGES;

    /**
     * @var array of active Package objects
     */
    protected $activePackages;

    protected $configCache;

    protected $cacheLog;

    protected $configDirectories = array();

    protected $configFileContent;

    /**
     * Array with all the ConfigManagementEnabled PackageKeys
     * @var array
     */
    protected $configManagementEnabledPackages;


    public function initialize($activePackages, Bootstrap $bootstrap){
        $this->activePackages = $activePackages;
        $this->bootstrap = $bootstrap;

        // filter active packages by ConfigManagement flag
        foreach ($this->activePackages as $activePackage){
            if($activePackage->isConfigManagementEnabled()){

                $configManagementEnabledPackages[$activePackage->getPackageKey()] = $activePackage;
            }
        }
        $this->configManagementEnabledPackages = $configManagementEnabledPackages;

        $packages = $this->configManagementEnabledPackages;


        if($this->bootstrap->getContext()->isDevelopment() || $this->bootstrap->getContext()->isTesting() || !is_dir(WPFLOW_PATH_DATA . 'ConfigManagementCache/Production') ){
            // get all the Files for each configManagementEnabled Packages
            foreach($packages as $packageKey => $package) {
                if($package->getFilteredConfigDirFiles()) {
                    $configFileInfos[$packageKey] = Arrays::removeEmptyElementsRecursively($package->getFilteredConfigDirFiles());
                }
                $configFileInfo = Arrays::removeEmptyElementsRecursively($configFileInfos);
            }

            foreach($configFileInfo as $packageKey => $configFileType){
                $content[$packageKey] = (array) new ConfigLoader($configFileType);
            }

            foreach($content as $packageKey => $values) {

                $processedData = $this->processConfigFiles($values, $packageKey);

                if(!$processedData == NULL){
                    $mergedValues = array_replace($values, $processedData);
                    $this->writeConfigFilesToCache($packageKey, $mergedValues);
                } else {
                    $this->writeConfigFilesToCache($packageKey, $values);
                }
            }
        }
    }


    protected function processConfigFiles(array $configFilesContent, $packageKey){

        //$configValidations = $this->bootstrap->getConfigValidation();
        $configValidations = array("Resources.yaml" => new ResourceConfiguration());

        foreach($configValidations as $fileName => $configuration){
            $processor = new ProcessConfigurations($configuration);

            if(!$configFilesContent[$fileName] == NULL) {

                $processedData[$fileName][$packageKey] = $processor->process($configFilesContent[$fileName][$packageKey]);
                return $processedData;
            }
        }
    }

    protected function writeConfigFilesToCache($packageKey, $content) {
        $configCache = new ConfigCache(WPFLOW_PATH_DATA . 'ConfigManagementCache/' . $packageKey .'Config.php', true);
        $configCache->write(serialize($content));
    }


    protected function getContextString(){
        return $this->bootstrap->getContext()->getContextString();
    }

    /**
     * @return mixed
     */
    public function getConfigManagementEnabledPackages()
    {
        return $this->configManagementEnabledPackages;
    }

    /**
     * @param mixed $configManagementEnabledPackages
     */
    public function setConfigManagementEnabledPackages($configManagementEnabledPackages)
    {
        $this->configManagementEnabledPackages = $configManagementEnabledPackages;
    }

    /**
     * @return mixed
     */
    public function getConfigFileContent()
    {
        return $this->configFileContent;
    }
}