<?php
/**
 * Created by PhpStorm.
 * User: Dennis
 * Date: 15.06.15
 * Time: 14:07
 */

namespace wpFlow\Core;


use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use wpFlow\Core\DependencyInjenction\ServiceContainerInterface;

class ServiceContainer implements ServiceContainerInterface {

    protected $fileLocaterPath;
    protected $serviceContainer;

    /**
     * The Constructor
     */

    public function __construct($fileLocaterPath){
        $this->setFileLocaterPath($fileLocaterPath);
        $this->buildServiceContainer();
        $this->loadServiceYaml();

    }

    public function buildServiceContainer()
    {
        $this->serviceContainer = new ContainerBuilder();
    }

    public function setFileLocaterPath($fileLocaterPath)
    {
        $this->fileLocaterPath = $fileLocaterPath;
    }

    public function loadServiceYaml()
    {
        dump($this->fileLocaterPath);
        $loader = new YamlFileLoader($this->serviceContainer, new FileLocator($this->fileLocaterPath));
        $loader->load('Services.yaml');
    }
}