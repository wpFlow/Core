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
     * The Resourcename.
     * @var string
     */
    protected $handle;

    /**
     * The path to the public reachable folder where the new resource file will be created.
     * @vars string
     */
    protected $publicPath;

    /**
     * The Filename of the Resource.
     * @var string
     */
    protected $fileName;

    /**
     * The name of the public folder where the new resource file is resides.
     * @var string
     */
    protected $fileTypeDirectory;

    protected $src;


    public function __construct($handle, $fileName){
        $this->handle = $handle;
        $this->fileName = $fileName;
    }

    public function dumpReadable($content, $publicPath = NULL, $fileTypeDirectory = NULL){

        (isset($publicPath)) ? $publicPath : $publicPath = get_template_directory() . $fileTypeDirectory;

        $copy = new Filesystem();
        $publicPathAndFilename = $publicPath . '/' .$this->fileName;

            try {
                $copy->dumpFile($publicPathAndFilename, $content);

            } catch (IOExceptionInterface $e){
                echo "An error occurred while writing your file!" . $e->getLine();
            }
    }

    public function registerResource($resourceType, $src, $inFooter){

        switch($resourceType){

            case 'css':
                wp_register_style($this->handle, $src, array(), WPFLOW_VERSION_BRANCH);
                break;

            case 'js':
                wp_register_script($this->handle, $src, array(), WPFLOW_VERSION_BRANCH, $inFooter);
                break;
        }
    }

}