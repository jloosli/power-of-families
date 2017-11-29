<?php

include_once('php-scripts/Autoloader.class.php');
$Autoloader = new \POF\Autoloader();
$ThemeSetup = new \Avanti\ThemeSetup();

new \Avanti\WooCommerce();


/*========================================================================
Enqueue JS/CSS
========================================================================*/
add_action('genesis_after_footer', 'after_body_js');
function after_body_js()
{
    wp_enqueue_script('bootstrap', get_stylesheet_directory_uri() . '/bootstrap/js/bootstrap.min.js', array('jquery'), '', true);
    wp_enqueue_script('z-responsive-menu', get_stylesheet_directory_uri() . '/lib/js/responsive-menu.js', array('jquery'), '', true);
    wp_enqueue_script('scripts', get_stylesheet_directory_uri() . '/js/scripts.js', array('jquery'), '', true);

}

add_action('wp_enqueue_scripts', 'custom_load_custom_style_sheet', 0);
function custom_load_custom_style_sheet()
{
    wp_enqueue_style('bootstrap', get_stylesheet_directory_uri() . '/bootstrap/css/bootstrap.min.css', array(), PARENT_THEME_VERSION);
    wp_enqueue_style('fontello', get_stylesheet_directory_uri() . '/css/fontello.css', array(), PARENT_THEME_VERSION);

    wp_enqueue_style('custom-google-fonts', 'https://fonts.googleapis.com/css?family=Montserrat:300,300i,400,400i,500,600,700|Playfair+Display:400,700', false);
}

if (!is_admin()) {
    add_action("wp_enqueue_scripts", "my_jquery_enqueue", 11);
}
function my_jquery_enqueue()
{
    wp_deregister_script('jquery');
    wp_register_script('jquery', "//ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js", array(), '', false);
    wp_enqueue_script('jquery');

    wp_deregister_script('essb-counter-script');
}

// function theme_typekit() {
//     wp_enqueue_script( 'theme_typekit', '//use.typekit.net/vyj5nea.js', array(), false, true );
// }
// add_action( 'wp_enqueue_scripts', 'theme_typekit' );

function theme_typekit_inline()
{
    if (wp_script_is('theme_typekit', 'done')) {
        ?>
        <script type="text/javascript">try {
            Typekit.load();
          } catch (e) {
          }</script>
        <?php
    }
}

//add_action( 'wp_head', 'theme_typekit_inline' );


/*========================================================================
header login bar
========================================================================*/
if (!is_user_logged_in()) {
    add_action('genesis_before_header', 'login_bar', 10);
}
function login_bar()
{

    echo '<div class="login-bar collapse" id="login-bar"><div class="wrap">';
    $request = isset($_GET['wlfrom']) ? $_GET['wlfrom'] : $_SERVER['REQUEST_URI'];
    $args = array(
        'echo' => true,
        //'redirect'       => $request,
        'redirect' => '\/my-programs\/',
        'form_id' => 'loginform',
        'label_username' => __('Username'),
        'label_password' => __('Password'),
        'label_remember' => __('Remember Me'),
        'label_log_in' => __('Log In'),
        'id_username' => 'user_login',
        'id_password' => 'user_pass',
        'id_remember' => 'rememberme',
        'id_submit' => 'wp-submit',
        'remember' => true,
        'value_username' => null,
        'value_remember' => true
    );
    echo '<h2>Member Login</h2>';
    echo wp_login_form($args);
    echo '<div class="register"><a href="/register" class="button">Register</a></div>';
    printf('<div class="lost_password"><a href="%s" title="Lost Password">Lost Password?</a></div>',
        wp_lostpassword_url(get_permalink())
    );


    echo '<button type="button" class="close" data-toggle="collapse" data-target="#login-bar"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button></div></div>';

}

/*========================================================================
Add search button to nav
========================================================================*/
//add_filter( 'wp_nav_menu_items', 'genesis_search_secondary_nav_menu', 10, 2 );

function genesis_search_secondary_nav_menu($menu, stdClass $args)
{


    if ('secondary' != $args->theme_location) {
        return $menu;
    }

    if (genesis_get_option('nav_extras')) {
        return $menu;
    }

    $menu .= sprintf('<div class="secondary-search">%s</div>', __(genesis_search_form()));


    return $menu;

}

/*========================================================================
Display titles on category archives
========================================================================*/
function themeprefix_category_header()
{
    if (is_category()) {
        echo '<h1 class="archive-title">Posts in the "';
        echo single_cat_title();
        echo '" category:</h1>';
    }
}

add_action('genesis_before_loop', 'themeprefix_category_header');

function add_alternate_social_urls($content)
{
    if (!is_feed() && (is_single() || is_page())) {
        global $post;
        $url = get_permalink($post->ID);
        $url_variations = array();
        // https/http
        if (strpos($url, 'https://') === 0) {
            $url_variations[] = str_replace('https://', 'http://', $url);
        } else {
            $url_variations[] = str_replace('http://', 'https://', $url);
        }
        $alternates = get_post_custom_values('essb_alternate_url', $post->ID);
        if ($alternates) {
            foreach ($alternates as $val) {
                $url_variations[] = $val;
            }
        }
        $toAdd = '';
        foreach ($url_variations as $variation) {
            $toAdd .= sprintf('<input type="hidden" class="essb_alternate_url" value="%s" />', $variation);
        }
        $content = $toAdd . $content;
    }
    return $content;
}

add_filter('the_content', 'add_alternate_social_urls', 10, 1);

function get_protected_pages()
{
    if (false === ($ids = get_transient('pom_protected_pages'))) {
        $ids = [];
        if (function_exists('wlmapi_get_protected_pages')) {
            $pages = wlmapi_get_protected_pages();
            $ids = array_merge($ids, array_map(function ($item) {
                return $item->ID;
            }, $pages['pages']['page']));
        }
        if (function_exists('wlmapi_get_protected_posts')) {
            $pages = wlmapi_get_protected_posts();
            $ids = array_merge($ids, array_map(function ($item) {
                return $item->ID;
            }, $pages['posts']['post']));
        }
        $ids = array_map('intval', $ids);
        set_transient('pom_protected_pages', $ids, HOUR_IN_SECONDS);
    }
    return $ids;
}

function hide_on_protected_pages($metadata, $object_id, $meta_key, $single)
{
    if ($meta_key === 'essb_off' && in_array($object_id, get_protected_pages())) {
        return 'true';
    }
}

add_filter('get_post_metadata', 'hide_on_protected_pages', 10, 4);
add_filter('get_page_metadata', 'hide_on_protected_pages', 10, 4);




