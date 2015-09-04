<?php
/**
 * Created by PhpStorm.
 * User: Dennis
 * Date: 27.07.15
 * Time: 16:29
 */

namespace Core\Utilities;

use Symfony\Component\Config\ConfigCache;
use wpFlow\Core\Exception;
use wpFlow\Core\Utilities\Files;
use wpFlow\Core\Utilities\Yaml;

class SingleConfiguration
{
    protected $context;
    protected $yamlFilePathAndName;
    protected $fileName;
    protected $path;
    protected $baseName;


    public function __construct($context = NULL, $yamlFilePathAndName){
        $this->context = $context;
        $this->yamlFilePathAndName = $yamlFilePathAndName;

        $this->fileName = pathinfo($this->yamlFilePathAndName, PATHINFO_FILENAME);
        $this->path = pathinfo($this->yamlFilePathAndName, PATHINFO_DIRNAME);
        $this->baseName = pathinfo($this->yamlFilePathAndName, PATHINFO_BASENAME);

    }


    public function load(){
        $context = (isset($this->context)? $this->context . '/'  : '' );

        $configCacheDir = WPFLOW_PATH_DATA . $this->fileName .'Configuration/' . $context .'Config.php';

        $configCacheFile = $this->path . '/' . $context . $this->baseName ;

        $cache = new ConfigCache($configCacheDir, false);

        if($this->context->isDevelopment() || $this->context->isTesting() || !file_exists($configCacheDir)){

            //load the yaml file
            if(file_exists($configCacheFile)){
                $contentYaml = Yaml::parse($configCacheFile);

            } else throw new \wpFlow\Core\Exception("There was no $this->baseName File found in the $context configuration directory");

            //write configcontent to the cache
            $cache->write(serialize($contentYaml));

            //load configcontent from the cache
            $configContent = $this->getContentfromCache($configCacheDir);
            return $configContent;
        } else {

            //load configcontent from the cache
            $configContent = $this->getContentfromCache($configCacheDir);
            return $configContent;
        }

    }

    protected function getContentfromCache($configCacheDir){

        if(file_exists($configCacheDir)) {
            $configContent = Files::getFileContents($configCacheDir);

            return unserialize($configContent);
        } else throw new Exception("The Cachefile: $configCacheDir does not exist!");


    }
}