<?php
/**
 * Created by PhpStorm.
 * User: Dennis
 * Date: 23.06.15
 * Time: 23:46
 */

namespace wpFlow\Core\Utilities;


class Debug {
    public static function vdump($expression){
        echo '<pre>';
            var_dump($expression);
        echo '</pre>';
    }

    public static function vdumpdie($expression){
        echo '<pre>';
        var_dump($expression);
        echo '</pre>';
        die();
    }
}