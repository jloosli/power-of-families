<?php

class Autoloader {
    public function __construct() {
        spl_autoload_register(array($this, 'loader'));
    }
    private function loader($className) {
        $className = ltrim($className, '\\');
        $className = str_replace('\\', '/', $className);
        if(file_exists(dirname(__FILE__) . '/' .$className.'.class.php')){
            //include_once APP_ROOT.'/lib/'.$className.'.class.php';
            include_once dirname(__FILE__) . '/' .$className.'.class.php';
        }
    }
}
