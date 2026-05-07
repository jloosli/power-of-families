<?php

/**
 * Created by PhpStorm.
 * User: jloosli
 * Date: 12/10/14
 * Time: 9:51 AM
 */

namespace PowerOfFamilies\POF\Programs;
function POF_Affiliate_Linker_CRON()
{
    $linker = new Affiliate_Linker();
    return $linker->add_amazon();
}


class Affiliate_Linker
{
    public static ?Affiliate_Linker_Settings $settingsInstance = null;

    public function __construct()
    {
        $this->affiliate_id = get_option('pof_amazon_affiliate_id');

        //Actions
        add_action('wp', $this->activation(...));
        add_action('wp_ajax_pof_affiliates_run', $this->add_amazon_ajax(...));

        //Filters

        //Short codes

        //Scripts

    }

    public static function getSettingsInstance() : Affiliate_Linker_Settings
    {
        if (is_null(self::$settingsInstance)) {
            self::$settingsInstance = new Affiliate_Linker_Settings();
        }

        return self::$settingsInstance;

    }

    public function activation() : void
    {
        $cron = 'POF_Affiliate_Linker_CRON';
        if (!wp_next_scheduled($cron)) {
            wp_schedule_event(time(), 'daily', $cron);
        }
//        $this->clear_all_crons($cron);
//        wp_clear_scheduled_hook($cron);
    }

    public function add_amazon_tag( array $matches ) : string
    {

        $url = parse_url($matches[2]); // split url into parts
        if (strpos(strtolower($url['host']), 'amazon') === false || (isset($url['query']) && strstr($url['query'], "tag"))) {
            return $matches[0];
        } // ignore if already has tag query
        $prefix = isset($url['query']) ? "&" : "?"; // add & if already query, ? if no query
        return str_replace($matches[2], $matches[2] . $prefix . "tag={$this->affiliate_id}", $matches[0]); // add tag

    }

    public function add_amazon_ajax() : void
    {
        $this->add_amazon();
    }

    public function add_amazon() : void
    {

        global $wpdb;

        $has_amazon = $wpdb->get_results(<<<sql
        SELECT ID, post_content
        FROM $wpdb->posts
        WHERE post_status = 'publish'
		AND post_content LIKE '%amazon.com%';
sql
        );
        $url_find = "<a\s[^>]*href=(\"|'??)([^\" >]*?)\1[^>]*>";
        $changeCount = 0;
        foreach ($has_amazon as $am) {
            $new_content = preg_replace_callback("/$url_find/siU", array(
                $this,
                "add_amazon_tag"
            ), $am->post_content);
            if ($new_content !== $am->post_content) {
                $changeCount++;

                $wpdb->update(
                    $wpdb->posts,
                    array(
                        'post_content' => $new_content,
                    ),
                    array('ID' => $am->ID), // where
                    array('%s'), // replacement: string
                    array('%d') // where: string
                );
            }
        }
        wp_send_json([
            'success' => true,
            'message' => sprintf(
                'Replaced %d urls in %d posts (%0.2f%%)',
                $changeCount,
                count($has_amazon),
                count($has_amazon) > 0 ? $changeCount / count($has_amazon) : 0
            ),
        ]);

    }

}
