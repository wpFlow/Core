<?php
/**
 * Created by PhpStorm.
 * User: Dennis
 * Date: 02.07.15
 * Time: 21:59
 */

namespace wpFlow\Core\Resource;
use wpFlow\Core\Utilities\Debug;


/**
 * Class Resource ## Entity ##
 * @package wpFlow\Core\Resource
 */
class Resource {

    protected static $strips = array(
        '#\s*(?<!:)//.*+#',
        "!/\*[^*]*\*+([^/][^*]*\*+)*/!",
        '/(\s)+/s',
        '/[\t\r\n]/',
    );

    /**
     * The name of the Resource
     * @var string
      */
    protected $handle;

    /** What type of resource is this file?
     * You can choose between a "local" resource and an "cdn".
     * @var string
     */
    protected $type;

    /** The filename!
     * @var string
     */
    protected $fileName;

    /** The ranking is used to determine at which position the content should
     * get compiled by the ResourceMerger!
     * @var integer
     */
    protected $ranking;

    /** At the end there will be only four public resource files which are integrated.
     * Two for javascript (header and footer) and two for css (header and footer).
     * @var string
     */
    protected $position;

    /** If the content should be compressed by removing white space and comments.
     * @var boolean
     */
    protected $minify;

    /** The absolute path to the resource or if it´s an cdn resource the url.
     * @var string
     */
    protected $resourcePath;

    /** The file Content.
     * @var string
     */
    protected $content;

    /**
     * If the resource should be compiled or not.
     * @var boolean
     */
    protected $compile;

    /**
     * You can specify an expression which can be anything call_user_function can call.
     * Only works when resource is not enabled for compiling.
     * @var string or array
     */
    protected $expression;

    /**
     * Optional Arguments für the Expressionfunction
     * @var array
     */
    protected $arguments;

    /**
     * The Type of the ResourceFile. For example css for a stylesheet or js for a javascriptfile.
     * @var 'string
     */
    protected $fileType;

    /**
     * The path to where the public accessible Version of the resource resides.
     * Only set when resource type is local or localCDN.
     * @var string
     */
    protected $publicPath;

    /**
     * The URI to where the public accessible Version of the resource resides.
     * Only set when resource type is local or localCDN.
     * @var string
     */
    protected $publicURI;

    /**
     * The Sass Compiler Object. Called once in the ResourceManager and injected trough
     * the factory.
     * @var Object
     */
    protected $scss;


    public function __construct($handle,$type, $fileName, $ranking,$position, $minify, $resourcePath, $content, $compile, $expression, $arguments, $scss ){
        $this->handle = $handle;
        $this->type = $type;
        $this->fileName = $fileName;
        $this->ranking = $ranking;
        $this->position = $position;
        $this->minify = $minify;
        $this->resourcePath = $resourcePath;
        $this->content = ($this->minify) ? preg_replace(self::$strips, '', $content) : $content;
        $this->compile = $compile;
        $this->expression = $expression;
        $this->arguments = $arguments;

        $this->scss = $scss;

        $this->fileType = pathinfo($this->fileName, PATHINFO_EXTENSION);

        if($this->fileType == 'scss'){
            $this->fileName = pathinfo($this->fileName, PATHINFO_FILENAME) . '.css';
            $this->fileType = "css";

            //compile the sass content to css
            if($this->isMinify()){
                $this->scss->setFormatter('scss_formatter_compressed');
            }
            $this->scss->addImportPath($this->resourcePath);
            $this->content = $this->scss->compile($this->content);

        }

        if ($this->type === 'local' || 'localCDN') {
            $this->publicPath = get_template_directory() . '/' . $this->fileType . '/' . $this->fileName;
            $this->publicURI = get_template_directory_uri() . '/' . $this->fileType . '/' . $this->fileName;
        }

    }

    /**
     * @return string
     */
    public function getHandle()
    {
        return $this->handle;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /** Returns TRUE if the resource is a local resource and not an cdn.
     * @return bool
     */
    public function isTypeLocal(){

        return ($this->type === 'local');
    }

    /** Returns TRUE if the resource is a cdn.
     * @return bool
     */
    public function isTypeCDN(){
        return ($this->type === 'cdn');
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * @return int
     */
    public function getRanking()
    {
        return $this->ranking;
    }

    /**
     * @return string
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @return boolean
     */
    public function isMinify()
    {
        return $this->minify;
    }

    /**
     * @return string
     */
    public function getResourcePath()
    {
        return $this->resourcePath;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Returns true is the resource should be compiled with others
     * from the same fileType.
     * @return boolean
     */
    public function isCompileEnabled()
    {
        return ($this->compile === true);
    }

    /**
     * Returns true is the resource should not be compiled with others
     * from the same fileType.
     * @return boolean
     */
    public function isNotCompileEnabled()
    {
        return ($this->compile === false);
    }

    /**
     * @return mixed
     */
    public function getFileType()
    {
        return $this->fileType;
    }


    /** Returns TRUE if the Resource is of type .css.
     * @return bool
     */
    public function isStylesheet(){
        return ($this->fileType === 'css');
    }

    /** Returns TRUE if the resource is of type .js.
     * @return bool
     */
    public function isJavascript(){
        return ($this->fileType === 'js');
    }

    /** Returns TRUE if the resource is of type .scss.
     * @return bool
     */
    public function isSass(){
        return ($this->fileType === 'scss');
    }

    /** Returns TRUE if the resource is supposed to be embeded in header.
     * @return bool
     */
    public function embedInHeader(){
        return ($this->position === 'header');
    }

    /** Returns TRUE if the resource is supposed to be embeded in footer.
     * @return bool
     */
    public function embedInFooter(){
        return ($this->position === 'footer');
    }

    /**
     * @return string
     */
    public function getExpression()
    {
        return $this->expression;
    }

    /**
     * @return string
     */
    public function getPublicPath()
    {
        return $this->publicPath;
    }

    /**
     * @return string
     */
    public function getPublicURI()
    {
        return $this->publicURI;
    }

    /**
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

}