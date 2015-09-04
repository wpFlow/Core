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
use wpFlow\Core\Exception;
use wpFlow\Core\Utilities\Arrays;
use wpFlow\Core\Utilities\Debug;
use wpFlow\Core\Utilities\Yaml;


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

    protected $usePhpYamlExtension;

    /**
     * Array with all the ConfigManagementEnabled PackageKeys
     * @var array
     */
    protected $configManagementEnabledPackages;


    public function initialize($configManagementEnabledPackages, Bootstrap $bootstrap){
        $this->configManagementEnabledPackages = $configManagementEnabledPackages;
        $this->bootstrap = $bootstrap;

        if (extension_loaded('yaml')) {
            $this->usePhpYamlExtension = TRUE;
        }

        //run the configfile processing
        $this->run();

    }

    public function run(){
        if($this->bootstrap->getContext()->isDevelopment() || $this->bootstrap->getContext()->isTesting() || !is_dir(WPFLOW_PATH_DATA . 'ConfigManagementCache/Production') ){

            // get all the Files for each configManagementEnabled Package
            foreach($this->configManagementEnabledPackages as $packageKey => $package) {
                $configuration[$packageKey] = $package->getFilteredConfigDirFiles();

                foreach($configuration[$packageKey] as $fileName => $filePath){
                    $configContent[$fileName] = $this->YamlLoader($filePath);

                    //this feels like a workaround but it works
                    $targetFileName = explode('.', $fileName);

                    if(count($targetFileName) == 3){
                        unset($configContent[$fileName]);
                    }
                }
            }

            $processedData = $this->processConfigFiles($configContent);

            if($processedData !== NULL){
                $mergedValues = array_replace($configContent, $processedData);

                $this->writeConfigFilesToCache($mergedValues);

            } else {
                $this->writeConfigFilesToCache($configContent);
            }
        }
    }

    protected function processConfigFiles(array $configFilesContent){

        $configValidations = $this->configValidation;

        foreach($configValidations as $fileName => $configuration){

            $processor = new ProcessConfigurations($configuration);

            $processedData[$fileName] = $processor->process($configFilesContent[$fileName]);
        }

        return $processedData;
    }

    protected function YamlLoader($filePath, $allowSplitSource = TRUE){

        $pathAndFilename = pathinfo($filePath)['dirname'] . '/' .pathinfo($filePath)['filename'];

        $pathsAndFileNames = array($pathAndFilename . '.yaml');
        if ($allowSplitSource === TRUE) {
            $splitSourcePathsAndFileNames = glob($pathAndFilename . '.*.yaml');
            if ($splitSourcePathsAndFileNames !== FALSE) {
                sort($splitSourcePathsAndFileNames);
                $pathsAndFileNames = array_merge($pathsAndFileNames, $splitSourcePathsAndFileNames);
            }
        }
        $configuration = array();
        foreach ($pathsAndFileNames as $pathAndFilename) {
            if (file_exists($pathAndFilename)) {
                try {
                    if ($this->usePhpYamlExtension) {
                        $loadedConfiguration = @yaml_parse_file($pathAndFilename);
                        if ($loadedConfiguration === FALSE) {
                            throw new Exception('A parse error occurred while parsing file "' . $pathAndFilename . '".', 1391894094);
                        }
                    } else {
                        $loadedConfiguration = Yaml::parse($pathAndFilename);
                    }
                    if (is_array($loadedConfiguration)) {
                        $configuration = Arrays::arrayMergeRecursiveOverrule($configuration, $loadedConfiguration);
                    }
                } catch (Exception $exception) {
                    throw new Exception('A parse error occurred while parsing file "' . $pathAndFilename . '". Error message: ' . $exception->getMessage(), 1232014321);
                }
            }
        }

        return $configuration;
    }

    public function addConfigValidation($fileName, ConfigurationInterface $configuration){
        $this->configValidation[$fileName] = $configuration;
    }


    protected function writeConfigFilesToCache($content) {
        $configCache = new ConfigCache(WPFLOW_PATH_DATA . 'ConfigManagementCache/Config.php', true);
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