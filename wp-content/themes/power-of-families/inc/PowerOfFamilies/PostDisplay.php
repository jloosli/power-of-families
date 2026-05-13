<?php

namespace PowerOfFamilies;

class PostDisplay implements HookRegistrar
{
    public function register(): void
    {
        add_filter('genesis_post_info', [$this, 'sp_post_info_filter']);

        remove_action('genesis_entry_footer', 'genesis_post_meta');

        add_action('genesis_entry_header', [$this, 'wpsites_post_author_avatars']);

        // Move featured image in archives
        remove_action('genesis_entry_content', 'genesis_do_post_image', 8);
        add_action('genesis_entry_header', 'genesis_do_post_image', 1);

        add_filter('avatar_defaults', [$this, 'newgravatar']);

        $this->display_author_box_on_single_posts();
    }

    public function sp_post_info_filter(string $post_info): string
    {
        if (is_single()) {
            return 'by [post_author_posts_link] on [post_date format="M j, Y"] [post_comments] [post_edit] [post_categories sep=", " before="Posted in: "]';
        }
        return 'by [post_author_posts_link] on [post_date format="M j, Y"]';
    }

    public function wpsites_post_author_avatars(): void
    {
        if (is_single()) {
            echo get_avatar(get_the_author_meta('email'), 60);
        }
    }

    public function newgravatar(array $avatar_defaults): array
    {
        $myavatar = get_stylesheet_directory_uri() . '/assets/images/default_avatar.jpg';
        $avatar_defaults[$myavatar] = "Power of Families Avatar";

        return $avatar_defaults;
    }

    public function display_author_box_on_single_posts(): void
    {
        add_filter('get_the_author_genesis_author_box_single', '__return_true');
        remove_action('genesis_after_entry', 'genesis_do_author_box_single', 8);
        add_action('genesis_entry_content', 'genesis_do_author_box_single', 10);
    }
}
