<?php
/**
 * Created by PhpStorm.
 * User: Dennis
 * Date: 03.07.15
 * Time: 02:04
 */

namespace wpFlow\Configuration;

use wpFlow\Configuration\Validation\ResourceConfiguration;
use wpFlow\Core\Bootstrap;
use wpFlow\Core\Package\Package as BasePackage;


class Package extends BasePackage {
    protected $protected = true;
    protected $configManagementEnabled = true;
    protected $bootstrap;

    public function boot(Bootstrap $bootstrap){
        $this->bootstrap = $bootstrap;
        dump($this->getPackageKey());
    }
}