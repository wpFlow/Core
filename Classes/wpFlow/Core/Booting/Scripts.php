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

    static public function initializeConfigManagement($package){
        $configManager = new ConfigManager();
        $configManager->initialize($package);

    }




}
