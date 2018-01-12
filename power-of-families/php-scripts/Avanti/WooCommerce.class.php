<?php
/**
 * Created by PhpStorm.
 * User: jloosli
 * Date: 11/28/17
 * Time: 5:40 PM
 */

namespace Avanti;


class WooCommerce
{

    function __construct()
    {
        add_action('woocommerce_after_shop_loop_item_title',
            [$this, 'woocommerce_after_shop_loop_item_title_short_description'], 5);

        // Replace add to card button with learn more in the store page
        add_action('init', [$this, 'remove_loop_button']);
        add_action('woocommerce_after_shop_loop_item', [$this, 'replace_add_to_cart']);

        add_filter('gettext', [$this, 'change_billing_details_to_your_details']);
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

    function change_billing_details_to_your_details($translated_text, $text, $domain)
    {
        switch (strtolower('Billing Details')) {
            case 'billing details':
                $translated_text = __('Your details', 'woocommerce');
                break;
        }
        return $translated_text;
    }
}