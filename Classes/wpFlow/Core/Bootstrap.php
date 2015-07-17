<?php
/**
 * Created by PhpStorm.
 * User: Dennis
 * Date: 12.06.15
 * Time: 18:06
 */

namespace wpFlow\Core;

use wpFlow\Core\Booting\Scripts;

include_once('ApplicationContext.php');


class Bootstrap {

    /**
     * Required PHP version
     */
    const MINIMUM_PHP_VERSION = '5.3.2';

    const MAXIMUM_PHP_VERSION = '5.99.9';

    static protected $instance = null;

    protected $context;

    protected $fileLocaterPath;

    protected $Packages;

    protected $configValidation;

    protected $packageManager;


    static public function boot($context)
      {
         if (null === self::$instance) {
             self::$instance = new self($context);
         }
         return self::$instance;
      }

    /**
     * Constructor
     *
     * @param string $context The application context, for example "Testing" or "Development"
     */
    public function __construct($context) {
        $this->context = new ApplicationContext($context);

    }


    /**
     * Returns the context this bootstrap was started in.
     *
     * @return \wpFlow\Core\ApplicationContext The context encapsulated in an object, for example "Development" or "Development/MyDeployment"
     * @api
     */
    public function getContext() {
        return $this->context;
    }

    /**
     * @return mixed
     */
    public function getConfigValidation()
    {
        return $this->configValidation;
    }

    /**
     * @param $className
     * @param $fileName
     */
    public function addConfigValidation($className, $fileName)
    {
        $this->configValidation[$fileName] = $className;
    }

    /**
     * @param mixed $packageManager
     */
    public function setPackageManager($packageManager)
    {
        $this->packageManager = $packageManager;
    }

    /**
     * Checks PHP version and other parameters of the environment
     *
     * @return void
     */
    protected function ensureRequiredEnvironment() {
        if (version_compare(phpversion(), self::MINIMUM_PHP_VERSION, '<')) {
            echo('wpFlow requires PHP version ' . self::MINIMUM_PHP_VERSION . ' or higher but your installed version is currently ' . phpversion() . '. (Error #1172215790)' . PHP_EOL);
            exit(1);
        }
        if (version_compare(PHP_VERSION, self::MAXIMUM_PHP_VERSION, '>')) {
            echo('wpFlow requires PHP version ' . self::MAXIMUM_PHP_VERSION . ' or lower but your installed version is currently ' . PHP_VERSION . '. (Error #1172215790)' . PHP_EOL);
            exit(1);
        }
        if (version_compare(PHP_VERSION, '6.0.0', '<') && !extension_loaded('mbstring')) {
            echo('wpFlow requires the PHP extension "mbstring" for PHP versions below 6.0.0 (Error #1207148809)' . PHP_EOL);
            exit(1);
        }
        if (DIRECTORY_SEPARATOR !== '/' && PHP_WINDOWS_VERSION_MAJOR < 6) {
            echo('wpFlow does not support Windows versions older than Windows Vista or Windows Server 2008 (Error #1312463704)' . PHP_EOL);
            exit(1);
        }

        set_time_limit(0);
        ini_set('unicode.output_encoding', 'utf-8');
        ini_set('unicode.stream_encoding', 'utf-8');
        ini_set('unicode.runtime_encoding', 'utf-8');

        if (ini_get('date.timezone') === '') {
            echo('wpFlow requires the PHP setting "date.timezone" to be set. (Error #1342087777)');
            exit(1);
        }

        if (version_compare(PHP_VERSION, '5.4', '<') && get_magic_quotes_gpc() === 1) {
            echo('wpFlow requires the PHP setting "magic_quotes_gpc" set to Off. (Error #1224003190)');
            exit(1);
        }

        if (!is_dir(WPFLOW_PATH_DATA) && !is_link(WPFLOW_PATH_DATA)) {
            if (!@mkdir(WPFLOW_PATH_DATA)) {
                echo('wpFlow could not create the directory "' . WPFLOW_PATH_DATA . '". Please check the file permissions manually or run "sudo ./flow flow:core:setfilepermissions" to fix the problem. (Error #1347526552)');
                exit(1);
            }
        }
    }

    /**
     * Run the System - Nice and Smooth!
     */
    public function run(){
        Scripts::initializePackageManagement($this);
    }

    /**
     * @return mixed
     */
    public function getPackages()
    {
        return $this->Packages;
    }

    /**
     * @param mixed $Packages
     */
    public function setPackages($Packages)
    {
        $this->Packages = $Packages;
    }
}