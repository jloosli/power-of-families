<?php

include_once('inc/Autoloader.class.php');
$Autoloader = new \POF\Autoloader();
$ThemeSetup = new \Avanti\ThemeSetup();

new \Avanti\WooCommerce();
new \Avanti\Readme();

function example_project_enqueue_editor_assets() {
    $asset_file = include( get_stylesheet_directory() . 'build/index.asset.php');

    wp_enqueue_script(
        'example-editor-scripts',
        get_stylesheet_directory_uri(). '/build/index.js',
        $asset_file['dependencies'],
        $asset_file['version']
    );
}
add_action( 'wp_enqueue_scripts', 'example_project_enqueue_editor_assets' );