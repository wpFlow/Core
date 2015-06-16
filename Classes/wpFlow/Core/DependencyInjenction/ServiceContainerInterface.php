<?php
/**
 * Created by PhpStorm.
 * User: Dennis
 * Date: 15.06.15
 * Time: 13:17
 */

namespace wpFlow\Core\DependencyInjenction;


interface ServiceContainerInterface
{
    public function __construct($fileLocaterPath);

    public function buildServiceContainer();

    public function setFileLocaterPath($fileLocaterPath);

    public function loadServiceYaml();
}