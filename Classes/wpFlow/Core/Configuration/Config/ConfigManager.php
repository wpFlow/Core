<?php
/**
 * Created by PhpStorm.
 * User: Dennis
 * Date: 20.06.15
 * Time: 23:16
 */

namespace wpFlow\Configuration\Config;


use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Definition\ConfigurationInterface;
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

    protected $configCache;

    protected $cacheLog;

    protected $configDirectories = array();

    protected $configFileContent;

    protected $configValidation = array();

    /**
     * Array with all the ConfigManagementEnabled PackageKeys
     * @var array
     */
    protected $configManagementEnabledPackages;


    public function initialize($configManagementEnabledPackages, Bootstrap $bootstrap){
        $this->configManagementEnabledPackages = $configManagementEnabledPackages;
        $this->bootstrap = $bootstrap;

        //run the configfile processing
        $this->run();


    }

    public function run(){
        if($this->bootstrap->getContext()->isDevelopment() || $this->bootstrap->getContext()->isTesting() || !is_dir(WPFLOW_PATH_DATA . 'ConfigManagementCache/Production') ){

            // get all the Files for each configManagementEnabled Package
            foreach($this->configManagementEnabledPackages as $packageKey => $package) {
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

    public function addConfigValidation($fileName, ConfigurationInterface $configuration ){
        $this->configValidation[$fileName] = $configuration;
        $this->run();
    }


    protected function processConfigFiles(array $configFilesContent, $packageKey){

        $configValidations = $this->configValidation;

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

    /**
     * @return array
     */
    public function getConfigValidation()
    {
        return $this->configValidation;
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