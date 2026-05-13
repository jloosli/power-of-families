<?php

namespace PowerOfFamilies;

class HomepageWidgets implements HookRegistrar
{
    public function register(): void
    {
        add_action('widgets_init', [$this, 'createWidgets']);
    }

    public function createWidgets(): void
    {
        genesis_register_sidebar([
            'id' => 'home_large_featured',
            'name' => __('Home Large Feature', 'powerofmoms'),
            'description' => __('This is the large home section.', 'news'),
        ]);
        genesis_register_sidebar([
            'id' => 'home_left',
            'name' => __('Home Left Feature', 'powerofmoms'),
            'description' => __('This is the left home featured content section.', 'news'),
        ]);
        genesis_register_sidebar([
            'id' => 'home_middle',
            'name' => __('Home Middle Feature', 'powerofmoms'),
            'description' => __('This is the middle home featured content section.', 'news'),
        ]);
        genesis_register_sidebar([
            'id' => 'home_right',
            'name' => __('Home Right Feature', 'powerofmoms'),
            'description' => __('This is the right home featured content section.', 'news'),
        ]);

        add_action('genesis_before_content', [$this, 'home_large_featured']);
        add_action('genesis_before_content', [$this, 'home_featured_widgets']);
    }

    public function home_large_featured(): void
    {
        if (is_home()) {
            genesis_widget_area('home_large_featured', [
                'before' => '<div class="home-large-featured"><div class="wrap">',
                'after' => '</div></div>',
            ]);
        }
    }

    public function home_featured_widgets(): void
    {
        if (is_home()) {
            echo '<div class="home-featured-widgets"><div class="wrap"><h2 class="panel-title">What\'s New</h2>';
            genesis_widget_area('home_left', [
                'before' => '<div class="home-featured-widget-1 widget-area home-featured-widget"><div class="home-featured-widget-inner">',
                'after' => '</div></div>',
            ]);
            genesis_widget_area('home_middle', [
                'before' => '<div class="home-featured-widget-2 widget-area home-featured-widget"><div class="home-featured-widget-inner">',
                'after' => '</div></div>',
            ]);
            genesis_widget_area('home_right', [
                'before' => '<div class="home-featured-widget-3 widget-area home-featured-widget"><div class="home-featured-widget-inner">',
                'after' => '</div></div>',
            ]);
            echo '</div></div>';
        }
    }
}
