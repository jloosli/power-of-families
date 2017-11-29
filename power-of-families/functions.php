<?php

include_once('php-scripts/Autoloader.class.php');
$Autoloader = new \POF\Autoloader();
$ThemeSetup = new \Avanti\ThemeSetup();

new \Avanti\WooCommerce();
