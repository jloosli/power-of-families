<?php

include_once('inc/Autoloader.class.php');
$Autoloader = new \POF\Autoloader();
$ThemeSetup = new \Avanti\ThemeSetup();

new \Avanti\WooCommerce();
new \Avanti\Readme();

$PowerOfFamiliesPrograms = new \POF\Power_of_Families_Programs();
