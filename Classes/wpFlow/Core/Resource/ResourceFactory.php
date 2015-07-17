<?php
/**
 * Created by PhpStorm.
 * User: Dennis
 * Date: 02.07.15
 * Time: 22:00
 */

namespace wpFlow\Core\Resource;


class ResourceFactory {



    public function create($handle,$type, $fileName, $ranking,$position, $minify, $resourcePath, $content, $compile, $expression){

        $resource = new Resource($handle,$type, $fileName, $ranking,$position, $minify, $resourcePath, $content, $compile, $expression);

        return $resource;

    }
}