<?php

namespace PowerOfFamilies\Avanti;

class NavCustomizations
{
    public function __construct()
    {
        // Move primary menu into header
        // https://wpbeaches.com/switching-primary-menu-genesis-theme-header-right/
        remove_action('genesis_after_header', 'genesis_do_nav');
        add_action('genesis_header_right', 'genesis_do_nav');
        unregister_sidebar('header-right');

        // Filter primary menu — append login link or My Account submenu
        add_filter('genesis_nav_items', [$this, 'be_follow_icons'], 10, 2);
        add_filter('wp_nav_menu_items', [$this, 'be_follow_icons'], 10, 2);
        add_filter('wp_nav_menu_header_items', [$this, 'be_follow_icons'], 10, 2);

        add_action('genesis_footer', [$this, 'footer_menu'], 0);
    }

    public function be_follow_icons($menu, $args): string
    {
        $args = (array)$args;
        if ('primary' !== $args['theme_location']) {
            return $menu;
        }
        if (is_user_logged_in()) {
            $follow = '<li class="menu-item menu-item-has-children" ><a href="/my-account/" title="My Account">My Account</a>';
            $follow .= '<ul class="sub-menu">';
            $follow .= '<li class="menu-item menu-login-link"><a href="/my-programs/" title="Go to My Programs">My Programs</a></li>';
            $follow .= '<li class="menu-item"><a href="/wp-admin/profile.php" title="Update My Profile">Update My Profile</a></li>';
            $follow .= '<li class="menu-item"><a href="' . wp_logout_url(get_permalink()) . '" title="Logout">Logout</a></li>';
            $follow .= '</ul></li>';
        } else {
            $follow = '<li class="menu-item menu-login-link" data-toggle="collapse" data-target="#login-bar"><a href="#">Log In</a></li>';
        }

        return $menu . $follow;
    }

    public function footer_menu(): void
    {
        $args = [
            'theme_location' => 'tertiary',
            'container' => 'nav',
            'container_class' => 'wrap',
            'menu_class' => 'menu genesis-nav-menu menu-tertiary',
            'depth' => 1,
        ];
        echo '<div class="footer-menu-container">';
        wp_nav_menu($args);
        echo '</div>';
    }
}
