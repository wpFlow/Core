<?php
/**
 * Created by PhpStorm.
 * User: Dennis
 * Date: 12.06.15
 * Time: 10:24
 */

namespace wpFlow\Configuration;

use Symfony\Component\Yaml\Yaml;
use wpFlow\Core\Exception;
use wpFlow\Core\Utilities\ArrayCollection;
use wpFlow\Core\Utilities\Arrays;
use wpFlow\Core\Utilities\Debug;
use wpFlow\Core\Utilities\Files;

class ConfigLoader extends ArrayCollection {

    public function __construct($supportedFileTypesAndFilePaths){

        $this->resolveLoader($supportedFileTypesAndFilePaths);
    }

    protected function yamlLoader($filePath, $fileName){
        $rawYamlContent = Files::getFileContents($filePath);
        $this->offsetSet($fileName, Yaml::parse($rawYamlContent));
    }

    protected function newYamlLoader($filePath, $fileName, $allowSplitSource = TRUE){
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
                    $loadedConfiguration = \Symfony\Component\Yaml\Yaml::parse($pathAndFilename);

                    if (is_array($loadedConfiguration)) {
                        $this->offsetSet($fileName, $configuration = Arrays::arrayMergeRecursiveOverrule($configuration, $loadedConfiguration));
                    }
                } catch (Exception $exception) {
                    throw new Exception('A parse error occurred while parsing file "' . $pathAndFilename . '". Error message: ' . $exception->getMessage(), 1232014321);
                }
            }
        }
    }

    protected function xmlLoader(){

    }

    protected function jsonLoader($filePath){
        $this->append(Files::getFileContents(json_decode($filePath)));
    }

    protected function phpLoader(){

    }

    protected function resolveLoader($supportedFileTypesAndFilePaths){
        foreach($supportedFileTypesAndFilePaths as $fileType => $filePath){
            if($fileType == 'yaml' || 'yml'){
                $collection = new ArrayCollection($filePath);
                $iterator = $collection->getIterator();
                while($iterator->valid()){
                    $this->newYamlLoader($iterator->current(), $iterator->key());
                        $iterator->next();

                }
            }
            if($fileType == 'json'){
                $collection = new ArrayCollection($filePath);
                $iterator = $collection->getIterator();
                while($iterator->valid()){
                    $this->jsonLoader($iterator->current());
                    $iterator->next();
                }

            }
         }
    }
}