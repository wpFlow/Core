<?php
/**
 * Created by PhpStorm.
 * User: Dennis
 * Date: 20.06.15
 * Time: 23:52
 */

namespace wpFlow\Core;

use wpFlow\Core\Package\Package as BasePackage;
use wpFlow\Core\Utilities\Debug;

class Package extends BasePackage {

    /**
     * @var boolean
     */
    protected $protected = TRUE;
    protected $configManagementEnabled = true;


    protected $bootstrap;


    public function boot(Bootstrap $bootstrap){
       $this->bootstrap = $bootstrap;
    }


}