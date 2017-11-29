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
        add_action('woocommerce_after_shop_loop_item_title', [$this, 'woocommerce_after_shop_loop_item_title_short_description'], 5);
    }

    function woocommerce_after_shop_loop_item_title_short_description()
    {
        global $product;
        if (!$product->post->post_excerpt) return;
        ?>
        <div itemprop="description">
            <?php echo apply_filters('woocommerce_short_description', $product->post->post_excerpt) ?>
        </div>
        <?php
    }


}