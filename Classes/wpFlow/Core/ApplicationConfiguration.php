<?php
/**
 * Created by PhpStorm.
 * User: Dennis
 * Date: 30.06.15
 * Time: 14:36
 */

namespace wpFlow\Core\ApplicationConfiguration;


use Symfony\Component\Config\ConfigCache;
use wpFlow\Core\Utilities\Yaml;
use TYPO3\Fluid\Exception;
use wpFlow\Core\ApplicationContext;
use wpFlow\Core\Utilities\Debug;
use wpFlow\Core\Utilities\Files;

class ApplicationConfiguration {

    protected static $instance = null;

    protected $context;

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
     * Defines various path constants used by wpFlow and if no root path or web root was
     * specified by an environment variable, exits with a respective error message.
     *
     * @return void
     */
    public function defineConstants() {
        /**
         * Defining Path Constants
         **/

        define('WPFLOW_PATH_ROOT', ROOT_DIR .'/');
        define('WPFLOW_WEBPATH_ROOT', WEBROOT_DIR . '/' );

        define('WPFLOW_PATH_CONFIGURATION', WPFLOW_PATH_ROOT . 'Configuration/');
        define('WPFLOW_PATH_DATA', WPFLOW_PATH_ROOT . 'Data/');
        define('WPFLOW_PATH_PACKAGES', WPFLOW_PATH_ROOT . 'Packages/');
        define('WPFLOW_PATH_SITES',WPFLOW_PATH_PACKAGES .'Sites/');

        try {
            //load configurations
            $configs = $this->loadApplicationConfiguration();
            $configs = $configs['wpFlow'];

        } catch (\wpFlow\Core\Exception $e) {
            echo '<pre>An Error occured: ' . $e->getMessage(). '</pre>';
        }

        /** Database settings */
        if(!isset($configs['Database'])) {
            throw new \wpFlow\Core\Exception('Please define the Databaseconfiguration für your Application');
        } else {

            $database = $configs['Database'];

            define('DB_NAME', $database['dbname']);
            define('DB_USER', $database['dbuser']);
            define('DB_PASSWORD', $database['dbpassword']);
            define('DB_HOST', $database['dbhost']);
            define('DB_CHARSET', $database['dbcharset']);
            define('DB_COLLATE', $database['dbcollate']);
        }

        /**
         * Defining Version Branch
         */
        define('WPFLOW_VERSION_BRANCH', '1.0');

        /**
         * Defining WORDPRESS CONSTANTS
         */

        if(!isset($configs['Application'])) {
            throw new \wpFlow\Core\Exception('Please define the Applicationconfiguration für your Application');
        } else {

            $application = $configs['Application'];

            define('WP_HOME', $application['siteurl']);
            define('WP_SITEURL', $application['homeurl']);

            /** Language Settings */
            define ('WPLANG', $application['language']);

            /** Custom Settings */
            define('AUTOMATIC_UPDATER_DISABLED', $application['automaticUpdaterDisabled']);
            define('DISABLE_WP_CRON', $application['disableWpCron']);

            /** Debug Settings */
            $debug = $configs['Application']['Debug'];

            // Enable WP_DEBUG mode
            define('WP_DEBUG', $debug['debugMode']);

            // Enable Debug logging to the /app-content/debug.log file
            define('WP_DEBUG_LOG', $debug['debugLog']);

            // Disable display of errors and warnings
            define('WP_DEBUG_DISPLAY',$debug['displayDebug']);
            @ini_set('display_errors',$debug['displayErrors']);

            // Use dev versions of core JS and CSS files (only needed if you are modifying these core files)
            define('SCRIPT_DEBUG',$debug['scriptDebug']);
        }

        if(!isset($configs['Security'])) {
            throw new \wpFlow\Core\Exception('Please define the Applicationconfiguration für your Application');
        } else {
            $security = $configs['Security'];

            /** Authentication Unique Keys and Salts */
            define('AUTH_KEY',         $security['authKey']);
            define('SECURE_AUTH_KEY',  $security['secureAuthKey']);
            define('LOGGED_IN_KEY',    $security['loggedInKey']);
            define('NONCE_KEY',        $security['noneKey']);
            define('AUTH_SALT',        $security['authSalt']);
            define('SECURE_AUTH_SALT', $security['secureAuthSalt']);
            define('LOGGED_IN_SALT',   $security['loggedInSalt']);
            define('NONCE_SALT',       $security['nonceSalt']);

            define('DISALLOW_FILE_EDIT', $security['disallowFileEdit']);

        }

        /** Custom Content Directory */
        define('WP_CONTENT_DIR', WPFLOW_WEBPATH_ROOT .  'app-content');
        define('WP_CONTENT_URL', WP_HOME . 'app-content');

        /** Bootstrap WordPress */
        if (!defined('ABSPATH')) {
            define('ABSPATH', WPFLOW_WEBPATH_ROOT . '/wp-core/');
        }

    }

    protected function loadApplicationConfiguration(){

        if($this->context->getContextString() == 'Development'){
            $applicationContext = 'Development';

        } elseif($this->context->getContextString() == 'Testing') {
            $applicationContext = 'Testing';

        } else {
            $applicationContext = '';
        }


        $configCacheDir = WPFLOW_PATH_DATA . 'ApplicationConfiguration/' . $applicationContext .'AppConfig.php';
        dump($applicationContext);
        $configCacheFile = ROOT_DIR . "/Configuration/$applicationContext/Config.yaml";
        $cache = new ConfigCache($configCacheDir, false);

        if($this->context->isDevelopment() || $this->context->isTesting() || !file_exists($configCacheDir)){

            //load the Config.yaml file
            if(file_exists($configCacheFile)){
                $rawYamlContent = Files::getFileContents($configCacheFile);
                $configYaml = Yaml::parse($rawYamlContent);

            } else throw new \wpFlow\Core\Exception("There was no Config.yaml File found in the $applicationContext configuration directory");

            //write configcontent to the cache
            $cache->write(serialize($configYaml));

            //load configcontent from the cache
            $configContent = $this->getConfigfromCache($configCacheDir);
            return $configContent;
        } else {

            //load configcontent from the cache
            $configContent = $this->getConfigfromCache($configCacheDir);
            return $configContent;
        }

    }

    protected function getConfigfromCache($configCacheDir){
        $configContent = Files::getFileContents($configCacheDir);

        return unserialize($configContent);
    }

    /**
     * @return ApplicationContext
     */
    public function getContext()
    {
        return $this->context;
    }

}