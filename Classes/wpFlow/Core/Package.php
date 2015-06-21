<?php
/**
 * Created by PhpStorm.
 * User: Dennis
 * Date: 20.06.15
 * Time: 23:52
 */

namespace wpFlow\Core;

use wpFlow\Core\Package\Package as BasePackage;

class Package extends BasePackage {

    /**
     * @var boolean
     */
    protected $protected = TRUE;

    /**
     * @var boolean
     */
    protected $configManagementEnabled = true;


    public function boot(Bootstrap $bootstrap){

    }


}