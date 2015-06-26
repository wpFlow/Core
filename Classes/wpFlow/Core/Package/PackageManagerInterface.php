<?php

namespace wpFlow\Core\Package;


    /*                                                                        *
     * This script belongs to the wpFlow framework.                           *
     *                                                                        *
     * It is free software; you can redistribute it and/or modify it under    *
     * the terms of the GNU Lesser General Public License, either version 3   *
     * of the License, or (at your option) any later version.                 *
     *                                                                        *
     *                                                                        */
use PackageInterface;
use wpFlow\Core\Bootstrap;

/**
 * Interface for the wpFlow Package Manager
 *
 */
interface PackageManagerInterface {

    const PATTERN_MATCH_PACKAGEKEY = '/^[a-z0-9]+\.(?:[a-z0-9][\.a-z0-9]*)+$/i';

    const DIRECTORY_CLASSES = 'Classes/';
    const DIRECTORY_CONFIGURATION = 'Configuration/';
    const DIRECTORY_TESTS_FUNCTIONAL = 'Tests/Functional/';
    const DIRECTORY_TESTS_UNIT = 'Tests/Unit/';
    const DIRECTORY_RESOURCES = 'Resources/';

    /**
     * Initializes the package manager
     *
     * @param
     * @return void
     */
    public function initialize(Bootstrap $bootstrap);

    /**
     * Returns TRUE if a package is available (the package's files exist in the packages directory)
     * or FALSE if it's not. If a package is available it doesn't mean necessarily that it's active!
     *
     * @param string $packageKey The key of the package to check
     * @return boolean TRUE if the package is available, otherwise FALSE
     */
    public function isPackageAvailable($packageKey);

    /**
     * Returns TRUE if a package is activated or FALSE if it's not.
     *
     * @param string $packageKey The key of the package to check
     * @return boolean TRUE if package is active, otherwise FALSE
     */
    public function isPackageActive($packageKey);

    /**
     * Returns a PackageInterface object for the specified package.
     * A package is available, if the package directory contains valid meta information.
     *
     * @param string $packageKey
     * @return
     */
    public function getPackage($packageKey);

    /**
     * Returns a PackageInterface object for the specified package.
     * A package is available, if the package directory contains valid meta information.
     *
     * @param string $packageKey
     * @return string
     */
    public function getPackagePath($packageKey);

    /**
     * Finds a package by a given object of that package; if no such package
     * could be found, NULL is returned.
     *
     * @param object $object The object to find the possessing package of
     * @return  The package the given object belongs to or NULL if it could not be found
     */
    public function getPackageOfObject($object);

    /**
     * Finds a package by a given class name of that package
     *
     * @param string $className The class name to find the possessing package of
     * @return  The package the given object belongs to or NULL if it could not be found
     * @see getPackageOfObject()
     */
    public function getPackageByClassName($className);

    /**
     * Returns an array of PackageInterface objects of all available packages.
     * A package is available, if the package directory contains valid meta information.
     *
     * @return array Array of PackageInterface
     */
    public function getAvailablePackages();

    /**
     * Returns an array of PackageInterface objects of all active packages.
     * A package is active, if it is available and has been activated in the package
     * manager settings.
     *
     * @return array
     */
    public function getActivePackages();

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
    public function getFilteredPackages($packageState = 'available', $packagePath = NULL, $packageType = NULL);

    /**
     * Returns the upper camel cased version of the given package key or FALSE
     * if no such package is available.
     *
     * @param string $unknownCasedPackageKey The package key to convert
     * @return mixed The upper camel cased package key or FALSE if no such package exists
     */
    public function getCaseSensitivePackageKey($unknownCasedPackageKey);

    /**
     * Check the conformance of the given package key
     *
     * @param string $packageKey The package key to validate
     */
    public function isPackageKeyValid($packageKey);

    /**
     * Create a new package, given the package key
     *
     * @param string $packageKey The package key to use for the new package
     * @param MetaData $packageMetaData Package metadata
     * @param string $packagesPath If specified, the package will be created in this path
     * @param string $packageType If specified, the package type will be set
     * @return Package The newly created package
     */
    public function createPackage();

    /**
     * Deactivates a package if it is in the list of active packages
     *
     * @param string $packageKey The package to deactivate
     * @return void
     * @api
     */
    public function deactivatePackage($packageKey);

    /**
     * Activates a package
     *
     * @param string $packageKey The package to activate
     * @return void
     * @api
     */
    public function activatePackage($packageKey);


    /**
     * Register a native Flow package
     *
     * @param PackageInterface $package The Package to be registered
     * @param boolean $sortAndSave allows for not saving packagestates when used in loops etc.
     * @return PackageInterface
     * @throws Exception
     */
    public function registerPackage(PackageInterface $package);

    /**
     * Unregisters a package from the list of available packages
     *
     * @param PackageInterface $package The package to be unregistered
     * @throws Exception
     */
    public function unregisterPackage(PackageInterface $package);

    /**
     * Removes a package from registry and deletes it from filesystem
     *
     * @param string $packageKey package to delete
     * @return void
     * @api
     */
    public function deletePackage($packageKey);


}
