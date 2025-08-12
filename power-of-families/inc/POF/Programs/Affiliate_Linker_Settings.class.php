<?php

namespace POF\Programs;
class Affiliate_Linker_Settings {

    public function __construct() {
//        add_action( 'pof_programs_settings_admin_end', array( $this, 'add_script' ) );
        global $wp_scripts;

        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'), 10, 1);
    }

    public function add_script() {
        $javascript = <<<JS
function pof_runAffiliateScript(me) {
    var message = document.createElement('span');
    me.parentNode.replaceChild(message,me);
    message.innerHTML = 'Working...';
    message.onclick = '';
    jQuery.post(ajaxurl, {action: 'pof_affiliates_run'}, function (response) {
        console.log(response);
        message.innerHTML = response.message;
    });
    return false;
}

JS;
        $javascript = preg_replace( "/[\n\t]/", "", $javascript );
        printf( "<script>%s</script>", $javascript );
    }

    public function enqueue_scripts(){
//        wp_enqueue_script('requirejs');
    }

    /**
     * Build settings fields
     * @return array Fields to be displayed on settings page
     */
    public function getSettings() {


        $settings = array(
            'title'       => __( 'Amazon Affiliate Linker', 'power-of-families-programs' ),
            'description' => __( 'Settings to add amazon affiliate linking', 'power-of-families-programs' ),
            'fields'      => array(
                array(
                    'id'          => 'amazon_affiliate_id',
                    'label'       => __( 'Amazon Affiliate ID', 'power-of-families-programs' ),
                    'description' => __( 'Enter the Amazon Affiliate ID (e.g. thpoofmo0fb-20)', 'power-of-families-programs' ),
                    'type'        => 'text',
                    'placeholder' => __( 'Amazon Affiliate ID', 'wordpress-plugin-template' )
                ),
                array(
                    'id'    => 'pof_amazon_affiliate_run_now',
                    'label' => sprintf( '<a id="pof_amazon_affiliate_run_now" class="button">%s</a>',
                         __( 'Run amazon affiliate script now', 'power-of-families-programs' ) ),
                )
            )
        );

        return $settings;
    }
}