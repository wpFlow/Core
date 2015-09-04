<?php
/**
 * Created by PhpStorm.
 * User: Dennis
 * Date: 17.06.15
 * Time: 14:47
 */

namespace wpFlow\Core\Package;


use Core\Utilities\SingleConfiguration;
use PackageInterface;
use Symfony\Component\Config\ConfigCache;
use wpFlow\Configuration\Config\ConfigManager;
use wpFlow\Core\Bootstrap;
use wpFlow\Core\Exception;
use wpFlow\Core\Resource\ResourceManager;
use wpFlow\Core\Utilities\Files;


class PackageManager implements PackageManagerInterface
{

    /**
     * @var object Bootstrap
     */
    protected $bootstrap;

    /**
     * @var object PackageFactory
     */
    protected $packageFactory;
    /**
     * @var string
     */
    protected $packagesBasePath = WPFLOW_PATH_PACKAGES;

    /**
     * Array of available packages, indexed by package key (case sensitive)
     * @var array
     */
    protected $packages = array();

    /**
     * List of active packages as package key => package object
     * @var array
     */
    protected $activePackages = array();

    /**
     * The absolute path to the packages
     * @var string
     */
    protected $packagePaths = array();

    /**
     * A map between ComposerName and PackageKey, only available when scanAvailablePackages is run
     * @var array
     */
    protected $composerNameToPackageKeyMap = array();

    /**
     * Package states configuration as stored in the PackageStates.php file
     * @var array
     */
    protected $packageStatesConfiguration = array();

    protected $packageKeys = array();

    protected $classesPath;

    protected $manifestPath;

    /**
     * @var string
     */
    protected $packageStatesPathAndFilename;

    /**
     * @var  Object ConfigCache
     */
    protected $packageStatesCache;

    protected $configManagementEnabledPackages;

    protected $packageConfiguration;

    /**
     * Initializes the package manager
     *
     * @param
     * @return void
     */
    public function initialize(Bootstrap $bootstrap)
    {
        $this->bootstrap = $bootstrap;
        $this->packageStatesPathAndFilename = $this->packageStatesPathAndFilename ?: WPFLOW_PATH_DATA . 'PackageCache/PackageStates.php';
        $this->packageFactory = new PackageFactory($this);
        $this->packageStatesCache = new ConfigCache($this->packageStatesPathAndFilename, false);
        $context = $this->bootstrap->getContext();
        $yamlPathAndFileName = WPFLOW_PATH_CONFIGURATION . 'Package.yaml';
        $this->packageConfiguration = new SingleConfiguration($context,$yamlPathAndFileName);


        $this->loadPackageStates();

        //deliver an instance of this packageManager to the bootstrap
        $this->bootstrap->setPackageManager($this);

        $this->activePackages = array();
        foreach ($this->packages as $packageKey => $package) {
            if ($package->isProtected() || (isset($this->packageStatesConfiguration['packages'][$packageKey]['state']) && $this->packageStatesConfiguration['packages'][$packageKey]['state'] === 'active')) {
                $this->activePackages[$packageKey] = $package;
            }
        }
        //set up all active packages to the bootstrap class for further use
        $this->bootstrap->setPackages($this->activePackages);

        foreach ($this->activePackages as $activePackage){
            if($activePackage->isConfigManagementEnabled()){
                $this->configManagementEnabledPackages[$activePackage->getPackageKey()] = $activePackage;
            }
        }

        $this->bootPackages();
        $this->runPackages();
    }

    protected function bootPackages(){
        foreach ($this->activePackages as $package) {
            $package->boot($this->bootstrap);
        }
    }

    protected function runPackages(){
        foreach ($this->activePackages as $package) {
            $package->run();
        }
    }

    protected function scanAvailablePackages()
    {
        if (isset($this->packageStatesConfiguration['packages'])) {
            foreach ($this->packageStatesConfiguration['packages'] as $packageKey => $configuration) {
                if (!file_exists($this->packagesBasePath . $configuration['packagePath'])) {
                    unset($this->packageStatesConfiguration['packages'][$packageKey]);
                }
            }
        } else {
            $this->packageStatesConfiguration['packages'] = array();
        }

        $packagePaths = array();
        foreach (new \DirectoryIterator($this->packagesBasePath) as $parentFileInfo) {
            $parentFilename = $parentFileInfo->getFilename();

            if ($parentFilename[0] !== '.' && $parentFileInfo->isDir()) {

                $packagePaths = $this->scanPackagesInPath($parentFileInfo->getPathname());
            }

            foreach ($packagePaths as $packagePath => $composerManifestPath) {
                try {
                    $composerManifest = self::getComposerManifest($composerManifestPath);
                    $packageKey = PackageFactory::getPackageKeyFromManifest($composerManifest, $packagePath, $this->packagesBasePath);

                    $this->composerNameToPackageKeyMap[strtolower($composerManifest->name)] = $packageKey;
                    $this->packageStatesConfiguration['packages'][$packageKey]['manifestPath'] = substr($composerManifestPath, strlen($packagePath)) ?: '';
                    $this->packageStatesConfiguration['packages'][$packageKey]['composerName'] = $composerManifest->name;

                    $composerManifestArray = (array) $composerManifest->require;
                    unset($composerManifestArray['php']);

                    $this->packageStatesConfiguration['packages'][$packageKey]['dependencies'] =  array_keys($composerManifestArray ,0);
                } catch (Exception $exception) {
                    $relativePackagePath = substr($packagePath, strlen($this->packagesBasePath));
                    $packageKey = substr($relativePackagePath, strpos($relativePackagePath, '/') + 1, -1);
                }

                if (!isset($this->packageStatesConfiguration['packages'][$packageKey]['state'])) {

                    $this->packageStatesConfiguration['packages'][$packageKey]['state'] = 'active';
                }

                $this->packageStatesConfiguration['packages'][$packageKey]['packagePath'] = str_replace($this->packagesBasePath, '', $packagePath);

                // Change this to read the target from Composer or any other source
                $this->packageStatesConfiguration['packages'][$packageKey]['classesPath'] = Package::DIRECTORY_CLASSES;

            }
        }

        $this->registerPackagesFromConfiguration(true);
    }

    /**
     * Scans all sub directories of the specified directory and collects the package keys of packages it finds.
     *
     * The return of the array is to make this method usable in array_merge.
     *
     * @param string $startPath
     * @param array $collectedPackagePaths
     * @return array
     */
    protected function scanPackagesInPath($startPath, array &$collectedPackagePaths = array()) {
        foreach (new \DirectoryIterator($startPath) as $fileInfo) {

            if (!$fileInfo->isDir()) {
                continue;
            }
            $filename = $fileInfo->getFilename();

            if ($filename[0] !== '.') {
                $currentPath = Files::getUnixStylePath($fileInfo->getPathName());
                $composerManifestPaths = $this->findComposerManifestPaths($currentPath);

                foreach ($composerManifestPaths as $composerManifestPath) {
                    $targetDirectory = rtrim(self::getComposerManifest($composerManifestPath, 'target-dir'), '/');
                    $packagePath = $targetDirectory ? substr(rtrim($composerManifestPath, '/'), 0, -strlen((string)$targetDirectory)) : $composerManifestPath;
                    $collectedPackagePaths[$packagePath] = $composerManifestPath;
                }
            }
        }
        return $collectedPackagePaths;
    }

    /**
     * Looks for composer.json in the given path and returns a path or NULL.
     *
     * @param string $packagePath
     * @return array
     */
    protected function findComposerManifestPaths($packagePath) {

        $manifestPaths = array();

        if (file_exists($packagePath . '/composer.json')) {
            $manifestPaths[] = $packagePath . '/';
        } else {
            $jsonPathsAndFilenames = Files::readDirectoryRecursively($packagePath, '.json');
            asort($jsonPathsAndFilenames);

            while (list($unusedKey, $jsonPathAndFilename) = each($jsonPathsAndFilenames)) {
                if (basename($jsonPathAndFilename) === 'composer.json') {
                    $manifestPath = dirname($jsonPathAndFilename) . '/';
                    $manifestPaths[] = $manifestPath;
                    $isNotSubPathOfManifestPath = function ($otherPath) use ($manifestPath) {
                        return strpos($otherPath, $manifestPath) !== 0;
                    };
                    $jsonPathsAndFilenames = array_filter($jsonPathsAndFilenames, $isNotSubPathOfManifestPath);
                }
            }
        }

        return $manifestPaths;
    }

    /**
     * Returns contents of Composer manifest - or part there of.
     *
     * @param string $manifestPath
     * @param string $key Optional. Only return the part of the manifest indexed by 'key'
     * @param object $composerManifest Optional. Manifest to use instead of reading it from file
     * @return mixed
     * @throws Exception
     * @see json_decode for return values
     */
    static public function getComposerManifest($manifestPath, $key = NULL, $composerManifest = NULL) {
        if ($composerManifest === NULL) {
            if (!file_exists($manifestPath . 'composer.json')) {
                throw new Exception('No composer manifest file found at "' . $manifestPath . '/composer.json".', 1349868540);
            }
            $json = file_get_contents($manifestPath . 'composer.json');
            $composerManifest = json_decode($json);
        }

        if ($key !== NULL) {
            if (isset($composerManifest->{$key})) {
                $value = $composerManifest->{$key};
            } else {
                $value = NULL;
            }
        } else {
            $value = $composerManifest;
        }
        return $value;
    }
    /**
     * Activates a package
     *
     * @param string $packageKey The package to activate
     * @return void
     * @api
     */
    public function activatePackage($packageKey) {
        if ($this->isPackageActive($packageKey)) {
            return;
        }

        $package = $this->getPackage($packageKey);
        $this->activePackages[$packageKey] = $package;
        $this->packageStatesConfiguration['packages'][$packageKey]['state'] = 'active';
        if (!isset($this->packageStatesConfiguration['packages'][$packageKey]['packagePath'])) {
            $this->packageStatesConfiguration['packages'][$packageKey]['packagePath'] = str_replace($this->packagesBasePath, '', $package->getPackagePath());
        }
        if (!isset($this->packageStatesConfiguration['packages'][$packageKey]['classesPath'])) {
            $this->packageStatesConfiguration['packages'][$packageKey]['classesPath'] = Package::DIRECTORY_CLASSES;
        }
    }

    /**
     * Unregisters a package from the list of available packages
     *
     * @param PackageInterface $package The package to be unregistered
     * @return void
     * @throws Exception
     */
    public function unregisterPackage(PackageInterface $package) {
        $packageKey = $package->getPackageKey();
        if (!$this->isPackageAvailable($packageKey)) {
            throw new Exception('Package "' . $packageKey . '" is not registered.', 1338996142);
        }
        $this->unregisterPackageByPackageKey($packageKey);
    }

    /**
     * Unregisters a package from the list of available packages
     *
     * @param string $packageKey Package Key of the package to be unregistered
     * @return void
     */
    protected function unregisterPackageByPackageKey($packageKey) {
        unset($this->packages[$packageKey]);
        unset($this->packageKeys[strtolower($packageKey)]);
        unset($this->packageStatesConfiguration['packages'][$packageKey]);
        $this->sortAndSavePackageStates();
    }

    /**
     * Returns TRUE if a package is available (the package's files exist in the packages directory)
     * or FALSE if it's not. If a package is available it doesn't mean necessarily that it's active!
     *
     * @param string $packageKey The key of the package to check
     * @return boolean TRUE if the package is available, otherwise FALSE
     */
    public function isPackageAvailable($packageKey)
    {
        $packageKey = $this->getCaseSensitivePackageKey($packageKey);
        return (isset($this->packages[$packageKey]));
    }

    /**
     * Returns TRUE if a package is activated or FALSE if it's not.
     *
     * @param string $packageKey The key of the package to check
     * @return boolean TRUE if package is active, otherwise FALSE
     */
    public function isPackageActive($packageKey)
    {
        return (isset($this->activePackages[$packageKey]));
    }

    /**
     * Returns a PackageInterface object for the specified package.
     * A package is available, if the package directory contains valid meta information.
     *
     * @param string $packageKey
     * @return
     * @throws Exception
     */

    public function getPackage($packageKey)
    {
        if (!$this->isPackageAvailable($packageKey)) {
            throw new Exception('Package "' . $packageKey . '" is not available. Please check if the package exists and that the package key is correct (package keys are case sensitive).', 1166546734);
        }
        return $this->packages[$packageKey];
    }

    /**
     * Finds a package by a given object of that package; if no such package
     * could be found, NULL is returned.
     *
     * @param object $object The object to find the possessing package of
     * @return  The package the given object belongs to or NULL if it could not be found
     */
    public function getPackageOfObject($object)
    {
        // TODO: Implement getPackageOfObject() method.
    }

    /**
     * Finds a package by a given class name of that package
     *
     * @param string $className The class name to find the possessing package of
     * @return  The package the given object belongs to or NULL if it could not be found
     * @see getPackageOfObject()
     */
    public function getPackageByClassName($className)
    {
        // TODO: Implement getPackageByClassName() method.
    }

    /**
     * Returns an array of PackageInterface objects of all available packages.
     * A package is available, if the package directory contains valid meta information.
     *
     * @return array Array of PackageInterface
     */
    public function getAvailablePackages()
    {
        // TODO: Implement getAvailablePackages() method.
    }

    /**
     * Returns an array of PackageInterface objects of all active packages.
     * A package is active, if it is available and has been activated in the package
     * manager settings.
     *
     * @return array
     */
    public function getActivePackages()
    {
        return $this->activePackages;
    }

    /**
     * Returns an array of PackageInterface objects of all packages that match
     * the given package state, path, and type filters. All three filters must match, if given.
     *
     * @param string $packageState defaults to available
     * @param string $packagePath
     * @param string $packageType
     *
     * @return array
     */
    public function getFilteredPackages($packageState = 'available', $packagePath = NULL, $packageType = NULL)
    {
        // TODO: Implement getFilteredPackages() method.
    }

    /**
     * Returns the upper camel cased version of the given package key or FALSE
     * if no such package is available.
     *
     * @param string $unknownCasedPackageKey The package key to convert
     * @return mixed The upper camel cased package key or FALSE if no such package exists
     */
    public function getCaseSensitivePackageKey($unknownCasedPackageKey)
    {
        $lowerCasedPackageKey = strtolower($unknownCasedPackageKey);
        return (isset($this->packageKeys[$lowerCasedPackageKey])) ? $this->packageKeys[$lowerCasedPackageKey] : FALSE;
    }

    /**
     * Check the conformance of the given package key
     *
     * @param string $packageKey The package key to validate
     */
    public function isPackageKeyValid($packageKey)
    {
        // TODO: Implement isPackageKeyValid() method.
    }

    /**
     * Deactivates a package
     *
     * @param string $packageKey The package to deactivate
     * @throws Exception
     */
    public function deactivatePackage($packageKey) {
        if (!$this->isPackageActive($packageKey)) {
            return;
        }

        $package = $this->getPackage($packageKey);
        if ($package->isProtected()) {
            throw new Exception('The package "' . $packageKey . '" is protected and cannot be deactivated.', 1308662891);
        }

        unset($this->activePackages[$packageKey]);
        $this->packageStatesConfiguration['packages'][$packageKey]['state'] = 'inactive';
        $this->sortAndSavePackageStates();
    }


    /**
     * Requires and registers all packages which were defined in packageStatesConfiguration
     *
     * @return void
     * @throws Exception
     */
    protected function registerPackagesFromConfiguration($sortAndSafe = TRUE) {

        if ($sortAndSafe === TRUE) {
            $this->sortAndSavePackageStates();
        }

        foreach ($this->packageStatesConfiguration['packages'] as $packageKey => $stateConfiguration) {

            $packagePath = isset($stateConfiguration['packagePath']) ? $stateConfiguration['packagePath'] : NULL;
            $classesPath = isset($stateConfiguration['classesPath']) ? $stateConfiguration['classesPath'] : NULL;
            $manifestPath = isset($stateConfiguration['manifestPath']) ? $stateConfiguration['manifestPath'] : NULL;

            try {
                $package = $this->packageFactory->create($this->packagesBasePath, $packagePath, $packageKey, $classesPath, $manifestPath);
            } catch (Exception $exception) {
               //$this->unregisterPackageByPackageKey($packageKey);
                continue;
            }

            //add the created packages to $this->packages[$packageKey]
            $this->registerPackage($package);


            if (!$this->packages[$packageKey] instanceof PackageInterface) {
                throw new Exception(sprintf('The package class in package "%s" does not implement PackageInterface.', $packageKey), 1300782487);
            }

            $this->packageKeys[strtolower($packageKey)] = $packageKey;
            if ($stateConfiguration['state'] === 'active') {
                $this->activePackages[$packageKey] = $this->packages[$packageKey];
            }
        }

    }

    /**
     * Register a native wpFlow package
     *
     * @param PackageInterface $package The Package to be registered
     * @param boolean $sortAndSave allows for not saving packagestates when used in loops etc.
     * @return PackageInterface
     * @throws Exception
     */
    public function registerPackage(PackageInterface $package) {
        $packageKey = $package->getPackageKey();
        $caseSensitivePackageKey = $this->getCaseSensitivePackageKey($packageKey);
        if ($this->isPackageAvailable($caseSensitivePackageKey)) {
            throw new Exception('Package "' . $packageKey . '" is already registered as "' . $caseSensitivePackageKey .  '".', 1338996122);
        }

        $this->packages[$packageKey] = $package;
        $this->packageStatesConfiguration['packages'][$packageKey]['packagePath'] = str_replace($this->packagesBasePath, '', $package->getPackagePath());
        $this->packageStatesConfiguration['packages'][$packageKey]['classesPath'] = str_replace($package->getPackagePath(), '', $package->getClassesPath());


        return $package;
       }


    /**
     * Saves the current content of $this->packageStatesConfiguration to the
     * PackageStates.php file.
     *
     * @return void
     * @throws Exception
     */
    protected function sortAndSavePackageStates() {
        //$this->sortAvailablePackagesByDependencies();
        $this->sortAvalailablePackagesByConfiguration();

        $this->packageStatesConfiguration['version'] = 4;

        $fileDescription = "# PackageStates.php\n\n";
        $fileDescription .= "# This file is maintained by wpFlow's package management. Although you can edit it\n";
        $fileDescription .= "# manually, you just should not.\n";
        $fileDescription .= "# This file will be regenerated automatically if it doesn't exist. Deleting this file\n";
        $fileDescription .= "# should, however, never become necessary.\n";
        $fileDescription .= "# This file was generated on:"  . date('m-d-Y_hia') . ".\n";

        $packageStatesCode = "<?php\n$fileDescription\nreturn " . var_export($this->packageStatesConfiguration, TRUE) . ';';

        $this->packageStatesCache->write($packageStatesCode, array());

    }

    protected function sortAvalailablePackagesByConfiguration(){
        $packageYamlContent = $this->packageConfiguration->load();

        $newOrder = $packageYamlContent['Packages']['Global']['PackageOrder'];
        $sortedPackages = array_replace(array_flip($newOrder), $this->packageStatesConfiguration['packages']);

        $this->packageStatesConfiguration['packages'] = $sortedPackages;

    }

    /**
     * Loads the states of available packages from the PackageStates.php file.
     * The result is stored in $this->packageStatesConfiguration.
     *
     * @return void
     */
    protected function loadPackageStates() {
        $this->packageStatesConfiguration = file_exists($this->packageStatesPathAndFilename) ? include($this->packageStatesPathAndFilename) : array();
        if (!isset($this->packageStatesConfiguration['version']) || $this->packageStatesConfiguration['version'] < 4) {
            $this->packageStatesConfiguration = array();
        }
        if ($this->packageStatesConfiguration === array()) {
            $this->scanAvailablePackages();

        } else {
            $this->registerPackagesFromConfiguration($sortAndSafe = false);
        }
    }

    /**
     * @return object
     */
    public function getBootstrap()
    {
        return $this->bootstrap;
    }

    /**
     * Orders all packages by comparing their dependencies. By this, the packages
     * and package configurations arrays holds all packages in the correct
     * initialization order.
     *
     * @return void
     */
    protected function sortAvailablePackagesByDependencies() {

        uasort($this->packageStatesConfiguration['packages'], array($this, 'sortDependencies'));

    }

    protected function sortDependencies($a, $b){
        if ( is_array($b['dependencies']) && in_array($a['composerName'], $b['dependencies'])) return -1;

        if ( $a['composerName'] == $b['dependencies'] ) return -1;

        return 1;
    }

    /**
     * @return mixed
     */
    public function getConfigManagementEnabledPackages()
    {
        return $this->configManagementEnabledPackages;
    }



    /**
     * Create a new package, given the package key
     *
     * @param string $packageKey The package key to use for the new package
     * @param MetaData $packageMetaData Package metadata
     * @param string $packagesPath If specified, the package will be created in this path
     * @param string $packageType If specified, the package type will be set
     * @return Package The newly created package
     */
    public function createPackage()
    {
        // TODO: Implement createPackage() method.
    }

    /**
     * Removes a package from registry and deletes it from filesystem
     *
     * @param string $packageKey package to delete
     * @return void
     * @api
     */
    public function deletePackage($packageKey)
    {
        // TODO: Implement deletePackage() method.
    }

    /**
     * Returns a PackageInterface object for the specified package.
     * A package is available, if the package directory contains valid meta information.
     *
     * @param string $packageKey
     * @return string
     */
    public function getPackagePath($packageKey)
    {
        // TODO: Implement getPackagePath() method.
    }
}