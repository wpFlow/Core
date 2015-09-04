<?php
/**
 * Created by PhpStorm.
 * User: Dennis
 * Date: 01.07.15
 * Time: 13:28
 */

namespace wpFlow\Core\Utilities;
use Symfony\Component\Yaml\Yaml as SymfonyYaml;

class Yaml {
    public static function parse($yamlPathAndFileName){

        $rawYamlContent = Files::getFileContents($yamlPathAndFileName);
        return  SymfonyYaml::parse($rawYamlContent);

    }
}