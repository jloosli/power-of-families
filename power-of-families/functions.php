<?php
require_once 'vendor/autoload.php';
$theme_setup = new \PowerOfFamilies\Avanti\ThemeSetup();
$theme_setup->init();
new \PowerOfFamilies\Avanti\WooCommerce();
new \PowerOfFamilies\Avanti\Readme();

$PowerOfFamiliesPrograms = new \PowerOfFamilies\POF\PowerOfFamiliesPrograms();
