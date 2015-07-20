<?php
/**
 * Created by PhpStorm.
 * User: Dennis
 * Date: 02.07.15
 * Time: 22:00
 */

namespace wpFlow\Core\Resource;


class ResourceFactory {



    public function create($handle = NULL ,$type = NULL, $fileName = NULL, $ranking = NULL,$position = NULL, $minify = NULL, $resourcePath = NULL, $content = NULL, $compile = NULL, $expression = NULL,$arguments = NULL, $scss = NULL){

        $resource = new Resource($handle,$type, $fileName, $ranking,$position, $minify, $resourcePath, $content, $compile, $expression,$arguments, $scss);

        return $resource;

    }
}