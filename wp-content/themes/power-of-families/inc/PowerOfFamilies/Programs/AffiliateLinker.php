<?php

namespace PowerOfFamilies\Programs;

class AffiliateLinker implements ProgramModule
{
    private ?AffiliateLinkerSettings $settings = null;

    public string $affiliate_id = '';

    /**
     * The token namespaces script handles and hook names. This module
     * registers neither under a namespaced name -- its ajax action and cron
     * hook are fixed strings -- so it takes the argument and drops it, for
     * the sake of a uniform construction contract.
     */
    public function __construct(string $token = '')
    {
    }

    public function register(): void
    {
        $this->affiliate_id = (string) get_option('pof_amazon_affiliate_id');

        add_action('wp', [$this, 'activation']);
        add_action('wp_ajax_pof_affiliates_run', [$this, 'add_amazon_ajax']);
        // Cron callback: wp_schedule_event() schedules the action hook,
        // do_action() fires it on the daily tick, and this binding is what
        // actually runs add_amazon(). Without it the scheduled cron is a
        // no-op (no global function exists at the cron-hook name).
        add_action('POF_Affiliate_Linker_CRON', [$this, 'add_amazon']);
    }

    /**
     * Built once per instance rather than once per request: the settings
     * screen was a static singleton, which is process-wide state that two
     * tests -- or two Settings instances -- would have had to share.
     */
    public function settings() : ?ProgramSettings
    {
        return $this->settings ??= new AffiliateLinkerSettings();
    }

    public function activation() : void
    {
        $cron = 'POF_Affiliate_Linker_CRON';
        if (!wp_next_scheduled($cron)) {
            wp_schedule_event(time(), 'daily', $cron);
        }
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
        check_ajax_referer( 'pof_affiliates_run', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Insufficient permissions.' ], 403 );
        }
        wp_send_json( $this->add_amazon() );
    }

    /**
     * Walk published posts, append the affiliate tag to Amazon URLs.
     * Returns a summary; does not terminate execution so the daily cron
     * (POF_Affiliate_Linker_CRON) can invoke this without wp_die().
     */
    public function add_amazon() : array
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
        return [
            'success' => true,
            'message' => sprintf(
                'Replaced %d urls in %d posts (%0.2f%%)',
                $changeCount,
                count($has_amazon),
                count($has_amazon) > 0 ? $changeCount / count($has_amazon) : 0
            ),
        ];

    }

}
