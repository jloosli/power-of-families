<?php

/**
 * WooCommerce function stubs for unit testing.
 *
 * Lets the theme code run under PHPUnit when WooCommerce isn't installed.
 * Each stub is guarded with function_exists() so this file is safe to load
 * even if WooCommerce is present.
 *
 * @package Power_Of_Families
 */

if ( ! function_exists( 'is_product' ) ) {
    /**
     * Whether the current request is a WooCommerce product page.
     *
     * @return bool
     */
    function is_product() {
        return false;
    }
}
