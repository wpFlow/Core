<?php
/**
 * Created by PhpStorm.
 * User: Dennis
 * Date: 20.06.15
 * Time: 23:17
 */

namespace wpFlow\Configuration\Config;


use wpFlow\Core\Bootstrap;

interface ConfigManagerInterface {

    public function initialize($activePackages, Bootstrap $bootstrap);

    /**
     * @return mixed
     */
    public function getConfigManagementEnabledPackages();
    /**
     * @param mixed $configManagementEnabledPackages
     */
    public function setConfigManagementEnabledPackages($configManagementEnabledPackages);

    public function getConfigFileContent();

}