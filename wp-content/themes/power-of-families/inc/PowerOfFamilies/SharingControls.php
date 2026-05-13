<?php

namespace PowerOfFamilies;

class SharingControls
{
    public function __construct()
    {
        // Hide sharing buttons on protected pages
        // @todo: Need to update this for Groups instead of wishlist member
        add_filter('get_post_metadata', [$this, 'hide_on_protected_pages'], 10, 4);
        add_filter('get_page_metadata', [$this, 'hide_on_protected_pages'], 10, 4);

        // Remove sidebar on single WooCommerce product pages
        add_action('wp', function () {
            if (function_exists('is_product') && is_product()) {
                add_filter('genesis_pre_get_option_site_layout', '__genesis_return_full_width_content');
            }
        });
    }

    public function hide_on_protected_pages($metadata, $object_id, $meta_key, $single): mixed
    {
        if ($meta_key === 'essb_off' && in_array($object_id, $this->get_protected_pages())) {
            return 'true';
        }
        return $metadata;
    }

    private function get_protected_pages(): array
    {
        if (false === ($ids = get_transient('pof_protected_pages'))) {
            $ids = [];
            if (function_exists('wlmapi_get_protected_pages')) {
                $pages = wlmapi_get_protected_pages();
                $ids = array_merge($ids, array_map(fn($item) => $item->ID, $pages['pages']['page']));
            }
            if (function_exists('wlmapi_get_protected_posts')) {
                $pages = wlmapi_get_protected_posts();
                $ids = array_merge($ids, array_map(fn($item) => $item->ID, $pages['posts']['post']));
            }
            $ids = array_map('intval', $ids);
            set_transient('pof_protected_pages', $ids, HOUR_IN_SECONDS);
        }
        return $ids;
    }
}
