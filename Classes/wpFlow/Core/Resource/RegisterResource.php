<?php
/**
 * Created by PhpStorm.
 * User: Dennis
 * Date: 08.07.15
 * Time: 21:19
 */

namespace wpFlow\Core\Resource;


use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

class RegisterResource {

    /**
     * The Resource Entity - All you need here!
     * @var object
     */
    protected $resource;


    public function __construct($resource){
        $this->resource = $resource;
    }

    public function dumpReadable(){

        $copy = new Filesystem();

            try {
                $copy->dumpFile($this->resource->getPublicPath(), $this->resource->getContent());

            } catch (IOExceptionInterface $e){
                echo "An error occurred while writing your file!" . $e->getLine();
            }
    }

    public function registerResource($src){

        switch($this->resource->getFileType()){

            case 'css':
                wp_register_style($this->resource->getHandle(), $src, array(), WPFLOW_VERSION_BRANCH);
                break;

            case 'js':
                wp_register_script($this->resource->getHandle(), $src, array(), WPFLOW_VERSION_BRANCH, $this->resource->embedInFooter());
                break;
        }
    }
}