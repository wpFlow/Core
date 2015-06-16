<?php
/**
 * Created by PhpStorm.
 * User: Dennis
 * Date: 15.06.15
 * Time: 11:22
 */

namespace wpFlow\Core\Package;


use wpFlow\Core\Bootstrap;

class Package implements PackageInterface {



    /**
     * Invokes custom PHP code directly after the package manager has been initialized.
     *
     * @param wpFlow\Core\Bootstrap
     * @return void
     */
    public function boot()
    {
        // TODO: Implement boot() method.
    }

    /**
     * Returns the array of filenames of the class files
     *
     * @return array An array of class names (key) and their filename, including the relative path to the package's directory
     */
    public function getClassFiles()
    {
        // TODO: Implement getClassFiles() method.
    }

    /**
     * Returns the package key of this package.
     *
     * @return string
     */
    public function getPackageKey()
    {
        // TODO: Implement getPackageKey() method.
    }

    /**
     * Returns the PHP namespace of classes in this package.
     *
     * @return string
     */
    public function getNamespace()
    {
        // TODO: Implement getNamespace() method.
    }

    /**
     * Tells if this package is protected and therefore cannot be deactivated or deleted
     *
     * @return boolean
     */
    public function isProtected()
    {
        // TODO: Implement isProtected() method.
    }

    /**
     * Sets the protection flag of the package
     *
     * @param boolean $protected TRUE if the package should be protected, otherwise FALSE
     * @return void
     */
    public function setProtected($protected)
    {
        // TODO: Implement setProtected() method.
    }

    /**
     * Returns the full path to this package's main directory
     *
     * @return string Path to this package's main directory
     */
    public function getPackagePath()
    {
        // TODO: Implement getPackagePath() method.
    }

    /**
     * Returns the full path to this package's Classes directory
     *
     * @return string Path to this package's Classes directory
     */
    public function getClassesPath()
    {
        // TODO: Implement getClassesPath() method.
    }

    /**
     * Returns the full path to the package's classes namespace entry path,
     * e.g. "My.Package/ClassesPath/My/Package/"
     *
     * @return string Path to this package's Classes directory
     * @api
     */
    public function getClassesNamespaceEntryPath()
    {
        // TODO: Implement getClassesNamespaceEntryPath() method.
    }

    /**
     * Returns the full path to this package's Resources directory
     *
     * @return string Path to this package's Resources directory
     * @api
     */
    public function getResourcesPath()
    {
        // TODO: Implement getResourcesPath() method.
    }

    /**
     * Returns the full path to this package's Configuration directory
     *
     * @return string Path to this package's Configuration directory
     * @api
     */
    public function getConfigurationPath()
    {
        // TODO: Implement getConfigurationPath() method.
    }

    /**
     * Returns the full path to this package's Configuration directory
     *
     * @return string Path to this package's Configuration directory
     * @api
     */
    public function getConfigurationFileMap()
    {
        // TODO: Implement getConfigurationFileMap() method.
    }
}