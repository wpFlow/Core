<?php

namespace wpFlow\Core\Booting;

/*                                                                        *
 * This script belongs to the wpFlow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 *                                                                        */

use wpFlow\Configuration\Config\ConfigManager;
use wpFlow\Core\Package\PackageManager;
use wpFlow\Core\Bootstrap;


/**
 * Initialization scripts for modules of the wpFlow.Core package
 *
 */
class Scripts {


    /**
     * Initializes the package system and loads the package configuration and settings
     * provided by the packages.
     *
     * @param Bootstrap $bootstrap
     * @return void
     */
    static public function initializePackageManagement(Bootstrap $bootstrap) {


        $packageManager = new PackageManager();
        $packageManager->initialize($bootstrap);
    }

    /**
     * Initializes the config system and loads the package configuration
     * provided by the packages.
     *
     * @param \PackageInterface $package
     */

    static public function initializeConfigManagement($package, Bootstrap $bootstrap){
        $configManager = new ConfigManager();
        $configManager->initialize($package, $bootstrap);

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

        dump('The ' . __METHOD__ . 'in ' . __CLASS__ .' produces null when in use!');

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




}
