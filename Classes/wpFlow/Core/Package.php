<?php
/**
 * Created by PhpStorm.
 * User: Dennis
 * Date: 20.06.15
 * Time: 23:52
 */

namespace wpFlow\Core;

<<<<<<< HEAD
use wpFlow\Configuration\Config\ConfigManager;
use wpFlow\Core\Package\Package as BasePackage;
use wpFlow\Core\Utilities\Debug;
=======
use wpFlow\Core\Package\Package as BasePackage;
>>>>>>> f85394a0c3c9a99459129fcd3320e99423b4cad3

class Package extends BasePackage {

    /**
     * @var boolean
     */
    protected $protected = TRUE;

    /**
     * @var boolean
     */
    protected $configManagementEnabled = true;

<<<<<<< HEAD
    protected $bootstrap;


    public function boot(Bootstrap $bootstrap){
       $this->bootstrap = $bootstrap;
=======

    public function boot(Bootstrap $bootstrap){

>>>>>>> f85394a0c3c9a99459129fcd3320e99423b4cad3
    }


}