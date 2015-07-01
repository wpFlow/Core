<?php
/**
 * Created by PhpStorm.
 * User: Dennis
 * Date: 15.06.15
 * Time: 11:22
 */

namespace wpFlow\Core\Package;



use wpFlow\Configuration\Config\ConfigInterface;
use wpFlow\Core\Bootstrap;
use wpFlow\Core\Exception;
use wpFlow\Core\Utilities\Arrays;
use wpFlow\Core\Utilities\Debug;
use wpFlow\Core\Utilities\Files;

class Package implements \PackageInterface {

    /**
     * The Instance of this Class
     */

    private static $instance = NULL;


    /**
     * @var string
     */
    const AUTOLOADER_TYPE_PSR0 = 'psr-0';

    /**
     * @var string
     */
    const AUTOLOADER_TYPE_PSR4 = 'psr-4';

    /**
     * @var string
     */
    const AUTOLOADER_TYPE_CLASSMAP = 'classmap';

    /**
     * @var string
     */
    const AUTOLOADER_TYPE_FILES = 'files';

    /**
     * Unique key of this package. Example for the wpFlow package: "wpFlow.Core"
     * @var string
     */
    protected $packageKey;

    /**
     * @var string
     */
    protected $manifestPath;

    /**
     * Full path to this package's main directory
     * @var string
     */
    protected $packagePath;

    /**
     * Full path to this package's PSR-0 class loader entry point
     * @var string
     */
    protected $classesPath;

    /**
     * If this package is protected and therefore cannot be deactivated or deleted
     * @var boolean
     */
    protected $protected = FALSE;

    /**
     * @var \stdClass
     */
    protected $composerManifest;

    /**
     * Names and relative paths (to this package directory) of files containing classes
     * @var array
     */
    protected $classFiles;

    /**
     * The namespace of the classes contained in this package
     * @var string
     */
    protected $namespace;

    /**
     * If enabled, the files in the Classes directory are registered and Reflection, Dependency Injection, AOP etc. are supported.
     * Disable this flag if you don't need object management for your package and want to save some memory.
     * @var boolean
     * @api
     */
    protected $objectManagementEnabled = FALSE;

    /**
     * @var PackageManager
     */
    protected $packageManager;

    /**
     * Declare if the PackagesConfig Files should be Managed
     * Default False therefore to be activated in local Package.php file.
     * @var bool
     */
    protected $configManagementEnabled = False;

    /**
     * All files from the ConfigDir of this Package filtered by
     * the ConfigFileConstraints
     * @var array strings
     */
    protected $filteredConfigDirFiles = array();

    /**
     * Only these filetypes are accepted i.e. yaml, yml, xml, php
     * You can add more filetypes using the setter method
     * @var array of strings
     */
    protected $configFileConstraints = array('yaml', 'yml');


    /**
     * Constructor
     *
     * @param PackageManager $packageManager the package manager which knows this package
     * @param string $packageKey Key of this package
     * @param string $packagePath Absolute path to the location of the package's composer manifest
     * @param string $classesPath Path the classes of the package are in, relative to $packagePath. Optional, PSR-0/PSR-4 mappings of the composer manifest overrule this argument, if present
     * @param string $manifestPath Path the composer manifest of the package, relative to $packagePath. Optional, defaults to ''
     * @throws Exception
     * @throws Exception
     * @throws Exception
     */
    public function __construct(PackageManager $packageManager, $packageKey, $packagePath, $classesPath = NULL, $manifestPath = '') {
        if (preg_match(self::PATTERN_MATCH_PACKAGEKEY, $packageKey) !== 1) {
            throw new Exception('"' . $packageKey . '" is not a valid package key.', 1217959510);
        }
        if (!(is_dir($packagePath) || (Files::is_link($packagePath) && is_dir(Files::getNormalizedPath($packagePath))))) {
            throw new Exception(sprintf('Tried to instantiate a package object for package "%s" with a non-existing package path "%s". Either the package does not exist anymore, or the code creating this object contains an error.', $packageKey, $packagePath), 1166631889);
        }
        if (substr($packagePath, -1, 1) !== '/') {
            throw new Exception(sprintf('The package path "%s" provided for package "%s" has no trailing forward slash.', $packagePath, $packageKey), 1166633720);
        }
        if (substr($classesPath, 0, 1) === '/') {
            throw new Exception(sprintf('The package classes path provided for package "%s" has a leading forward slash.', $packageKey), 1334841320);
        }
        if (!file_exists($packagePath . $manifestPath . 'composer.json')) {
            throw new Exception(sprintf('No composer manifest file found for package "%s". Please create one at "%scomposer.json".', $packageKey, $manifestPath), 1349776393);
        }

        $this->packageManager = $packageManager;
        $this->manifestPath = $manifestPath;
        $this->packageKey = $packageKey;
        $this->packagePath = Files::getNormalizedPath($packagePath);
        $autoloadType = $this->getAutoloadType();

        if ($autoloadType === self::AUTOLOADER_TYPE_PSR0 || $autoloadType === self::AUTOLOADER_TYPE_PSR4) {
            $autoloadPath = $this->getComposerManifest()->autoload->{$autoloadType}->{$this->getNamespace()};
            if (is_array($autoloadPath)) {
                $autoloadPath = $autoloadPath[0];
            }
            $this->classesPath = Files::getNormalizedPath($this->packagePath . $autoloadPath);
        } else {
            $this->classesPath = Files::getNormalizedPath($this->packagePath . $classesPath);
        }

        if($this->isConfigManagementEnabled()){
            $this->ensurePackageConfigEnvironment();
            $this->buildArrayOfConfigFiles();
        }
    }

    /**
     * Invokes custom PHP code directly after the package manager has been initialized.
     *
     * @param Bootstrap $bootstrap The current bootstrap
     * @return void
     */
    public function boot(Bootstrap $bootstrap) {
    }

    /**
     * @param array $configFileConstraints
     */
    public function setConfigFileConstraints($configFileConstraints)
    {
        $this->configFileConstraints = (array)$configFileConstraints;
    }

    /**
     * @return array
     */
    public function getFilteredConfigDirFiles()
    {
        return $this->filteredConfigDirFiles;
    }

    /**
     * @return PackageManager
     */
    public function getPackageManager()
    {
        return $this->packageManager;
    }

    /**
     * Check whether the given package requirement (like "wpFlow/core" or "php") is a composer package or not
     *
     * @param string $requirement the composer requirement string
     * @return boolean TRUE if $requirement is a composer package (contains a slash), FALSE otherwise
     */
    protected function packageRequirementIsComposerPackage($requirement) {
        return (strpos($requirement, '/') !== FALSE);
    }

    /**
     * Returns the array of filenames of the class files
     *
     * @return array An array of class names (key) and their filename, including the relative path to the package's directory
     */
    public function getClassFiles() {
        if (!is_array($this->classFiles)) {
            $this->classFiles = $this->buildArrayOfClassFiles($this->classesPath);
        }
        return $this->classFiles;
    }

    /**
     * Returns the package key of this package.
     *
     * @return string
     * @api
     */
    public function getPackageKey() {
        return $this->packageKey;
    }

    /**
     * Returns the PHP namespace of classes in this package.
     *
     * @return string
     * @throws Exception
     * @api
     */
    public function getNamespace() {
        if (!$this->namespace) {
            $manifest = $this->getComposerManifest();
            $autoloadType = $this->getAutoloadType();
            if ($autoloadType === self::AUTOLOADER_TYPE_PSR0 || $autoloadType === self::AUTOLOADER_TYPE_PSR4) {
                $namespaces = (array)$manifest->autoload->{$autoloadType};
                $namespace = key($namespaces);
            } else {
                $namespace = str_replace('.', '\\', $this->getPackageKey());
            }
            $this->namespace = $namespace;
        }
        return $this->namespace;
    }

    /**
     * PSR autoloading type
     *
     * @return string see self::AUTOLOADER_TYPE_* - NULL in case it is not defined or unknown
     * @api
     */
    public function getAutoloadType() {
        $manifest = $this->getComposerManifest();
        if (isset($manifest->autoload->{self::AUTOLOADER_TYPE_PSR0})) {
            return self::AUTOLOADER_TYPE_PSR0;
        }
        if (isset($manifest->autoload->{self::AUTOLOADER_TYPE_PSR4})) {
            return self::AUTOLOADER_TYPE_PSR4;
        }
        if (isset($manifest->autoload->{self::AUTOLOADER_TYPE_CLASSMAP})) {
            return self::AUTOLOADER_TYPE_CLASSMAP;
        }
        if (isset($manifest->autoload->{self::AUTOLOADER_TYPE_FILES})) {
            return self::AUTOLOADER_TYPE_FILES;
        }

        return NULL;
    }

    /**
     * Tells if this package is protected and therefore cannot be deactivated or deleted
     *
     * @return boolean
     * @api
     */
    public function isProtected() {
        return $this->protected;
    }

    /**
     * Tells if files in the Classes directory should be registered and object management enabled for this package.
     *
     * @return boolean
     */
    public function isObjectManagementEnabled() {
        return $this->objectManagementEnabled;
    }

    /**
     * Sets the protection flag of the package
     *
     * @param boolean $protected TRUE if the package should be protected, otherwise FALSE
     * @return void
     * @api
     */
    public function setProtected($protected) {
        $this->protected = (boolean)$protected;
    }

    /**
     * Sets the ConfigManagementEnabled flag of the package
     *
     * @param $configManagementEnabled
     * @internal param bool $ConfigManagementEnabled TRUE if the packages Config files should be managed by the system, otherwise FALSE.
     */
    public function setConfigManagement($configManagementEnabled) {
        $this->configManagementEnabled = (boolean)$configManagementEnabled;
    }

    /**
     * Tells if this packages Config files Management is enabled
     *
     * @return boolean
     */
    public function isConfigManagementEnabled(){
        return $this->configManagementEnabled;
    }

    /**
     * Returns the full path to this package's main directory
     *
     * @return string Path to this package's main directory
     */
    public function getPackagePath() {
        return $this->packagePath;
    }

    /**
     * Returns the full path to the packages Composer manifest
     *
     * @return string
     */
    public function getManifestPath() {
        return $this->packagePath . $this->manifestPath;
    }

    /**
     * Returns the full path to this package's Classes directory
     *
     * @return string Path to this package's Classes directory
     */
    public function getClassesPath() {
        return $this->classesPath;
    }

    /**
     * Returns the full path to the package's classes namespace entry path,
     * e.g. "My.Package/ClassesPath/My/Package/"
     *
     * @return string Path to this package's Classes directory
     * @api
     */
    public function getClassesNamespaceEntryPath() {
        $pathifiedNamespace = str_replace('\\', '/', $this->getNamespace());
        return Files::getNormalizedPath($this->classesPath . trim($pathifiedNamespace, '/'));
    }


    /**
     * Returns the full path to this package's Resources directory
     *
     * @return string Path to this package's Resources directory
     * @api
     */
    public function getResourcesPath() {
        return $this->packagePath . self::DIRECTORY_RESOURCES;
    }

    /**
     * Returns the full path to this package's Configuration directory
     *
     * @return string Path to this package's Configuration directory
     */
    public function getConfigurationPath() {

        return $this->packagePath . self::DIRECTORY_CONFIGURATION;
    }

    /**
     * Returns the full path to this package's Configuration directory
     *
     * @return string Path to this package's Configuration directory
     */
    public function getConfigurationPathByContext() {
        if($this->packageManager->getBootstrap()->getContext()->getContextString() == 'Development'){
            $applicationContext = 'Development';

        } elseif($this->packageManager->getBootstrap()->getContext()->getContextString() == 'Testing') {
            $applicationContext = 'Testing';

        } else {
            $applicationContext = '';
        }

        return $this->packagePath . self::DIRECTORY_CONFIGURATION . $applicationContext;
    }

    /**
     * Returns contents of Composer manifest - or part there of.
     *
     * @param string $key Optional. Only return the part of the manifest indexed by 'key'
     * @return mixed|NULL
     * @see json_decode for return values
     */
    public function getComposerManifest($key = NULL) {
        if (!isset($this->composerManifest)) {
            $this->composerManifest = PackageManager::getComposerManifest($this->getManifestPath());
        }

        return PackageManager::getComposerManifest($this->getManifestPath(), $key, $this->composerManifest);
    }

    /**
     * Builds and returns an array of class names => file names of all
     * *.php files in the package's Classes directory and its sub-
     * directories.
     *
     * @param string $classesPath Base path acting as the parent directory for potential class files
     * @param string $extraNamespaceSegment A PHP class namespace segment which should be inserted like so: \TYPO3\PackageKey\{namespacePrefix\}PathSegment\PathSegment\Filename
     * @param string $subDirectory Used internally
     * @param integer $recursionLevel Used internally
     * @return array
     * @throws Exception if recursion into directories was too deep or another error occurred
     */
    protected function buildArrayOfClassFiles($classesPath, $extraNamespaceSegment = '', $subDirectory = '', $recursionLevel = 0) {
        $classFiles = array();
        $currentPath = $classesPath . $subDirectory;
        $currentRelativePath = substr($currentPath, strlen($this->packagePath));
        $namespacePrefix = '';

        if ($this->getAutoloadType() === self::AUTOLOADER_TYPE_PSR4) {
            $namespacePrefix = $this->getNamespace();
        }

        if (!is_dir($currentPath)) {
            return array();
        }
        if ($recursionLevel > 100) {
            throw new Exception('Recursion too deep while collecting class files.', 1166635495);
        }

        try {
            $classesDirectoryIterator = new \DirectoryIterator($currentPath);
            while ($classesDirectoryIterator->valid()) {
                $filename = $classesDirectoryIterator->getFilename();
                if ($filename[0] != '.') {
                    if (is_dir($currentPath . $filename)) {
                        $classFiles = array_merge($classFiles, $this->buildArrayOfClassFiles($classesPath, $extraNamespaceSegment, $subDirectory . $filename . '/', ($recursionLevel + 1)));
                    } else {
                        if (substr($filename, -4, 4) === '.php') {
                            $className = Files::concatenatePaths(array($namespacePrefix, $extraNamespaceSegment, substr($currentPath, strlen($classesPath)), substr($filename, 0, -4)));
                            $className = str_replace('/', '\\', $className);
                            $classFiles[$className] = $currentRelativePath . $filename;
                        }
                    }
                }
                $classesDirectoryIterator->next();
            }

        } catch (\Exception $exception) {
            throw new Exception($exception->getMessage(), 1166633720);
        }
        return $classFiles;
    }

    protected function buildArrayOfConfigFiles(){

        foreach ($this->configFileConstraints as $constraint) {

            $this->filteredConfigDirFiles[$constraint] = Files::readDirectory($this->getConfigurationPathByContext(), '.' . $constraint);
        }
    }

    protected function getConfigValues($fileName){
        $context = $this->packageManager->getBootstrap()->getContext()->getContextString();
        $cacheFile = WPFLOW_PATH_DATA . 'ConfigManagementCache/' . $context .'/'. $this->getPackageKey() .'Config.php';

        if(file_exists($cacheFile)) {
            $fileContent =  unserialize(Files::getFileContents($cacheFile));
        } else throw new Exception("No File ($cacheFile) available");

        foreach($fileContent as $key => $values) {

            if(isset($fileContent[$fileName]))
            return $fileContent[$fileName];
        }
    }

    protected function ensurePackageConfigEnvironment(){
        if (!is_dir($this->getConfigurationPathByContext()) && !is_link($this->getConfigurationPathByContext())) {
            if (!@mkdir($this->getConfigurationPathByContext())) {
                echo('wpFlow could not create the directory "' . $this->getConfigurationPathByContext() . '". Please check the file permissions manually. (Error #1347526552)');
                exit(1);
            }
        }
    }
}