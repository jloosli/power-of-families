<?php

namespace PowerOfFamilies;

class SiteChrome implements HookRegistrar
{
    public function register(): void
    {
        add_action('wp_head', [$this, 'pre_load_favicon']);
        add_action('admin_head', [$this, 'pre_load_favicon']);

        remove_action('genesis_footer', 'genesis_do_footer');
        add_action('genesis_footer', [$this, 'footer_copyright']);

        if (!is_user_logged_in()) {
            add_action('genesis_before_header', [$this, 'login_bar'], 10);
        }
    }

    public function footer_copyright(): void
    {
        printf(
            '<p>&copy; Copyright 2017 - %s <a href="%s">Power of Families</a></p>',
            esc_html( date( 'Y' ) ),
            esc_url( 'https://poweroffamilies.com' )
        );
    }

    public function pre_load_favicon(): void
    {
        $favicon_directory = get_stylesheet_directory_uri() . '/assets/images/favicon/';

        echo '<link rel="shortcut icon" href="' . $favicon_directory . 'favicon.ico">' . PHP_EOL
            . '<link rel="apple-touch-icon-precomposed" sizes="57x57"   href="' . $favicon_directory . 'apple-touch-icon-57x57.png" />' . PHP_EOL
            . '<link rel="apple-touch-icon-precomposed" sizes="114x114" href="' . $favicon_directory . 'apple-touch-icon-114x114.png" />' . PHP_EOL
            . '<link rel="apple-touch-icon-precomposed" sizes="72x72"   href="' . $favicon_directory . 'apple-touch-icon-72x72.png" />' . PHP_EOL
            . '<link rel="apple-touch-icon-precomposed" sizes="144x144" href="' . $favicon_directory . 'apple-touch-icon-144x144.png" />' . PHP_EOL
            . '<link rel="apple-touch-icon-precomposed" sizes="60x60"   href="' . $favicon_directory . 'apple-touch-icon-60x60.png" />' . PHP_EOL
            . '<link rel="apple-touch-icon-precomposed" sizes="120x120" href="' . $favicon_directory . 'apple-touch-icon-120x120.png" />' . PHP_EOL
            . '<link rel="apple-touch-icon-precomposed" sizes="76x76"   href="' . $favicon_directory . 'apple-touch-icon-76x76.png" />' . PHP_EOL
            . '<link rel="apple-touch-icon-precomposed" sizes="152x152" href="' . $favicon_directory . 'apple-touch-icon-152x152.png" />' . PHP_EOL
            . '<link rel="icon" type="image/png" href="' . $favicon_directory . 'favicon-196x196.png" sizes="196x196" />' . PHP_EOL
            . '<link rel="icon" type="image/png" href="' . $favicon_directory . 'favicon-96x96.png" sizes="96x96" />' . PHP_EOL
            . '<link rel="icon" type="image/png" href="' . $favicon_directory . 'favicon-32x32.png" sizes="32x32" />' . PHP_EOL
            . '<link rel="icon" type="image/png" href="' . $favicon_directory . 'favicon-16x16.png" sizes="16x16" />' . PHP_EOL
            . '<link rel="icon" type="image/png" href="' . $favicon_directory . 'favicon-128.png" sizes="128x128" />';
    }

    public function login_bar(): void
    {
        echo '<div class="login-bar collapse" id="login-bar"><div class="wrap">';
        $request = isset( $_GET['wlfrom'] )
            ? esc_url_raw( wp_validate_redirect( sanitize_url( wp_unslash( $_GET['wlfrom'] ) ), home_url( '/my-programs/' ) ) )
            : home_url( '/my-programs/' );
        $args = [
            'echo' => true,
            'redirect' => $request,
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
            'value_remember' => true,
        ];
        echo '<h2>Member Login</h2>';
        echo wp_login_form($args);
        echo '<div class="register"><a href="/register" class="button">Register</a></div>';
        printf(
            '<div class="lost_password"><a href="%s" title="Lost Password">Lost Password?</a></div>',
            wp_lostpassword_url(get_permalink())
        );

        echo '<button type="button" class="close" data-toggle="collapse" data-target="#login-bar">
<span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button></div></div>';
    }
}
