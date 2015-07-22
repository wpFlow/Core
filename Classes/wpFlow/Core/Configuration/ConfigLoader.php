<?php
/**
 * Created by PhpStorm.
 * User: Dennis
 * Date: 12.06.15
 * Time: 10:24
 */

namespace wpFlow\Configuration;

use Symfony\Component\Yaml\Yaml;
use wpFlow\Core\Utilities\ArrayCollection;
use wpFlow\Core\Utilities\Files;

class ConfigLoader extends ArrayCollection {

    public function __construct($supportedFileTypesAndFilePaths){

        $this->resolveLoader($supportedFileTypesAndFilePaths);
    }

    protected function yamlLoader($filePath, $fileName){
        $rawYamlContent = Files::getFileContents($filePath);
        $this->offsetSet($fileName, Yaml::parse($rawYamlContent));
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
                    $this->yamlLoader($iterator->current(), $iterator->key());
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