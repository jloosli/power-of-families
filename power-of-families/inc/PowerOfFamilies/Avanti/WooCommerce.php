<?php

/**
 * Created by PhpStorm.
 * User: jloosli
 * Date: 11/28/17
 * Time: 5:40 PM
 */

namespace PowerOfFamilies\Avanti;

global $product;

class WooCommerce
{

    function __construct()
    {

        add_action('woocommerce_after_shop_loop_item_title', [$this, 'woocommerce_after_shop_loop_item_title_short_description'], 5);

        // Add "Add to Cart" button to the bottom of the page summary
        add_action('woocommerce_after_single_product_summary', function () {
            if (is_product()) {
                woocommerce_simple_add_to_cart();
            }
        });

        // Replace add to card button with learn more in the store page
        add_action('init', [$this, 'remove_loop_button']);
        add_action('woocommerce_after_shop_loop_item', [$this, 'replace_add_to_cart']);

        add_filter('gettext', [$this, 'change_billing_details_to_your_details'], 20, 3);
        add_filter('woocommerce_checkout_login_message', [$this, 'returning_customer_to_returning_member']);
        add_filter('woocommerce_billing_fields', [$this, 'remove_unnecessary_billing_fields']);
        // remove Order Notes from checkout field in Woocommerce
        add_filter('woocommerce_checkout_fields', [$this, 'alter_woocommerce_checkout_fields']);
        // removes Order Notes Title - Additional Information
        add_filter('woocommerce_enable_order_notes_field', '__return_false');
        //remove Order Notes Field
        add_filter('woocommerce_checkout_fields', [$this, 'remove_order_notes']);

        add_action('woocommerce_checkout_after_customer_details', [$this, 'add_text_to_checkout']);

        add_action('woocommerce_order_details_before_order_table', [$this, 'add_my_programs_message']);

        /**
         * Auto Complete all WooCommerce orders.
         */
        add_action('woocommerce_thankyou', [$this, 'custom_woocommerce_auto_complete_order']);
        add_filter('gettext', [$this, 'change_billing_field_strings'], 20, 3);
        add_filter('woocommerce_checkout_login_message', [$this, 'change_return_customer_message']);

        // Remove elements from single product page for the Family Coaching Group
        add_action('wp', [$this, 'remove_elements_from_header_of_family_coaching_group_product_page']);
    }

    function remove_elements_from_header_of_family_coaching_group_product_page()
    {
        $ids = array(62545);
        if (!function_exists('is_product') || !function_exists('wc_get_product')) return;
        $product = wc_get_product();
        if (is_product() && $product && in_array($product->get_id(), $ids)) {
            remove_action('woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20);
            add_action('woocommerce_before_single_product_summary', 'woocommerce_template_single_title', 20);
            remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_title', 5);
            remove_action('woocommerce_product_thumbnails', 'woocommerce_show_product_thumbnails', 20);
            remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 10);
            remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20);
            remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
            add_filter('woocommerce_product_description_heading', '__return_false', 90, 1);
            remove_action('woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10);
            add_action('woocommerce_before_single_product_summary', function() {
                // Hide the whole summary div (throws off spacing)
                ?><style>
                    .woocommerce div.product div.summary {
                        display: none;
                    }
                </style><?php

            }, 25);
        }
    }

    function woocommerce_after_shop_loop_item_title_short_description()
    {
        global $product;
        $excerpt = $product->get_short_description();
        if (!$excerpt) return;
?>
        <div itemprop="description">
            <?php echo apply_filters('woocommerce_short_description', $excerpt); ?>
        </div>
    <?php
    }

    function remove_loop_button()
    {
        remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);
    }

    function replace_add_to_cart()
    {
        global $product;
        $link = $product->get_permalink();
        echo do_shortcode('<br><a class="button" href="' . esc_attr($link) . '">Learn More</a>');
    }

    function change_billing_details_to_your_details($translated_text, $text = '', $domain = '')
    {
        remove_filter(current_filter(), __FUNCTION__);
        switch (strtolower($translated_text)) {
            case 'billing details':
                $translated_text = is_user_logged_in() ? '' : __('Your details', 'woocommerce');
                break;
        }
        return $translated_text;
    }

    function returning_customer_to_returning_member()
    {
        return 'Returning Member?';
    }

    function remove_unnecessary_billing_fields($fields = [])
    {
        if (is_user_logged_in()) {
            unset($fields['billing_first_name']);
            unset($fields['billing_last_name']);
            unset($fields['billing_email']);
        }
        unset($fields['billing_company']);
        unset($fields['billing_address_1']);
        unset($fields['billing_address_2']);
        unset($fields['billing_state']);
        unset($fields['billing_city']);
        unset($fields['billing_phone']);
        unset($fields['billing_postcode']);
        unset($fields['billing_country']);
        return $fields;
    }


    function alter_woocommerce_checkout_fields($fields)
    {
        unset($fields['order']['order_comments']);
        return $fields;
    }

    function remove_order_notes($fields)
    {
        unset($fields['order']['order_comments']);
        return $fields;
    }

    function add_text_to_checkout()
    {
        if (is_user_logged_in()) return;
        echo "If you already have an account on Power of Families, use the link at the top of this page to log in before continuing. Otherwise, we'll 
need to quickly create an account for you. Your email will be your username and you choose your password. You will use your username/email 
and password to log in and access your materials whenever you wish.";
    }

    function custom_woocommerce_auto_complete_order($order_id)
    {
        if (!$order_id) {
            return;
        }

        $order = wc_get_order($order_id);
        $order->update_status('completed');
    }

    function change_billing_field_strings($translated_text, $text, $domain)
    {
        switch ($translated_text) {
            case 'Billing details':
                $translated_text = is_user_logged_in() ? '' : __('Your Details', 'woocommerce');
                break;
        }
        return $translated_text;
    }

    function change_return_customer_message()
    {
        return 'Returning Member?';
    }

    function add_my_programs_message()
    {
    ?>
        <div class="my-programs-after-order-message">
            To access your new program now and in the future, be sure you are logged (in upper right corner
            of the website) then use the dropdown menu for "My Account" to click on "My Programs". You should
            then see an icon for your new program. Click on that icon to access your materials.
        </div>
<?php
    }
}
