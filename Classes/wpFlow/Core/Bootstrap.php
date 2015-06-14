<?php
/**
 * Created by PhpStorm.
 * User: Dennis
 * Date: 12.06.15
 * Time: 18:06
 */

namespace wpFlow\Core;

include_once('ApplicationContext.php');


class Bootstrap {

    /**
     * Required PHP version
     */
    const MINIMUM_PHP_VERSION = '5.3.2';
    const MAXIMUM_PHP_VERSION = '5.99.9';

    static protected $instance = null;

    protected $context;

    static public function getInstance($context)
      {
         if (null === self::$instance) {
             self::$instance = new self($context);
         }
         return self::$instance;
      }

    /**
     * Constructor
     *
     * @param string $context The application context, for example "Production" or "Development"
     */
    public function __construct($context) {
        $this->defineConstants();
        $this->ensureRequiredEnvironment();

        $this->context = new ApplicationContext($context);
    }

    /**
     * Defines various path constants used by Flow and if no root path or web root was
     * specified by an environment variable, exits with a respective error message.
     *
     * @return void
     */
    protected function defineConstants() {
        /**
         * Defining Path Constants
         */

        define('WPFLOW_PATH_ROOT', ROOT_DIR .'/');
        define('WPFLOW_WEBPATH_ROOT', WEBROOT_DIR . '/' );

        define('WPFLOW_PATH_CONFIGURATION', WPFLOW_PATH_ROOT . 'Configuration/');
        define('WPFLOW_PATH_DATA', WPFLOW_PATH_ROOT . 'Data/');
        define('WPFLOW_PATH_PACKAGES', WPFLOW_PATH_ROOT . 'Packages/');

        /**
         * Defining Version Branch
         */
        define('WPFLOW_VERSION_BRANCH', '1.0');

        /**
         * Defining WORDPRESS CONSTANTS
         */

        define('WP_HOME', "http://localhost:8080/");
        define('WP_SITEURL', 'http://localhost:8080/wp-core/');

        /** Custom Content Directory */
        define('WP_CONTENT_DIR', WPFLOW_WEBPATH_ROOT .  'app-content');
        define('WP_CONTENT_URL', WP_HOME . 'app-content');

        /** DB settings */
        define('DB_NAME', "localhost");
        define('DB_USER', "root");
        define('DB_PASSWORD', "root");
        define('DB_HOST', "localhost");
        define('DB_CHARSET', 'utf8');
        define('DB_COLLATE', '');

        /** Authentication Unique Keys and Salts */
        define('AUTH_KEY',         'xdZsX~SwyiBipwC+Sp,+Mu}zg++7F#%fYf`?*0iPQ7 |j0@F,PXF}g:-Ep07XC&?');
        define('SECURE_AUTH_KEY',  'kTuGuo?y.?h2RPu*bV(*1m%2JM<RFtf2%>Y|A%wG_IiSu4!r5w==vi-)B_,!I2IA');
        define('LOGGED_IN_KEY',    'q6DU)~UP-N2PGliW;4rS9x7Ww8~7y _1%E.ez7hDZ&f&N+)%4N |Or7+iwVsol^M');
        define('NONCE_KEY',        'y}n-eWx/T6?[mO`]d?VfN&7G:eu>i3 YQ|r-+6}~y=j/<lSf0rr@ASGX[GxwbCRd');
        define('AUTH_SALT',        'm3)= CK#NtgLB*x[ceT93tA^&6D2H.]ITU7tuINS|m`IdFYawKda)Zk69SxOo-aA');
        define('SECURE_AUTH_SALT', 'Ozkf@)5+Z [-BP3}WTqa6+#Q/_@cR0</_e:=s83y;`ZW,`9eiFO[G^>f;zTE5-)}');
        define('LOGGED_IN_SALT',   'syf^Kpo3qJ8Da_2*+~.MylvH~6EkA+7B_*{pW@[A?0!1s|LSvCF+?u3J~te_j6e-');
        define('NONCE_SALT',       '!yz2E^Oi/{T`I#C`t lLSpUeE(2e3@g<P+B=Osu]Ei{0rQP4yQH*U#34b~pdfz>+');

        /** Custom Settings */
        define('AUTOMATIC_UPDATER_DISABLED', true);
        define('DISABLE_WP_CRON', true);
        define('DISALLOW_FILE_EDIT', true);

        /** Bootstrap WordPress */
        if (!defined('ABSPATH')) {
            define('ABSPATH', WPFLOW_WEBPATH_ROOT . '/wp-core/');
        }

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

        if (!extension_loaded('Reflection')) {
            echo('The PHP extension "Reflection" is required by wpFlow.' . PHP_EOL);
            exit(1);
        }
        $method = new \ReflectionMethod(__CLASS__, __FUNCTION__);
        if ($method->getDocComment() === FALSE || $method->getDocComment() === '') {
            echo('Reflection of doc comments is not supported by your PHP setup. Please check if you have installed an accelerator which removes doc comments.' . PHP_EOL);
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
        if (!is_dir(WPFLOW_PATH_DATA . 'Persistent') && !is_link(WPFLOW_PATH_DATA . 'Persistent')) {
            if (!@mkdir(WPFLOW_PATH_DATA . 'Persistent')) {
                echo('wpFlow could not create the directory "' . WPFLOW_PATH_DATA . 'Persistent". Please check the file permissions manually or run "sudo ./flow flow:core:setfilepermissions" to fix the problem. (Error #1347526553)');
                exit(1);
            }
        }
    }


    /**
     * Tries to find an environment setting with the following fallback chain:
     *
     * - getenv with $variableName
     * - getenv with REDIRECT_ . $variableName (this is for php cgi where environment variables from the http server get prefixed)
     * - $_SERVER[$variableName] (this is an alternative to set WPFLOW_* environment variables if passing environment variables is not possible)
     * - $_SERVER[REDIRECT_ . $variableName] (again for php cgi environments)
     *
     * @param string $variableName
     * @return string or NULL if this variable was not set at all.
     */
    static public function getEnvironmentConfigurationSetting($variableName) {
        $variableValue = getenv($variableName);

        if ($variableValue !== FALSE) {
            return $variableValue;
        }

        $variableValue = getenv('REDIRECT_' . $variableName);
        if ($variableValue !== FALSE) {
            return $variableValue;
        }

        if (isset($_SERVER[$variableName])) {
            return $_SERVER[$variableName];
        }

        if (isset($_SERVER['REDIRECT_' . $variableName])) {
            return $_SERVER['REDIRECT_' . $variableName];
        }

        return NULL;
    }

    public function run(){

    }
}