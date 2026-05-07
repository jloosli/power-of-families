<?php

/**
 * Test Fixtures
 * 
 * Provides pre-defined test data scenarios for consistent testing
 * 
 * @package Power_Of_Families
 */

class TestFixtures
{

    /**
     * Create a complete blog setup with posts, pages, and users
     *
     * @return array Created objects
     */
    public static function create_blog_setup()
    {
        $created = [];

        // Create admin user
        $admin_id = TestDataFactory::create_user('administrator', [
            'user_login' => 'admin',
            'user_email' => 'admin@example.com',
            'display_name' => 'Administrator',
        ]);
        if (!is_wp_error($admin_id)) {
            $created['admin_id'] = $admin_id;
        }

        // Create editor user
        $editor_id = TestDataFactory::create_user('editor', [
            'user_login' => 'editor',
            'user_email' => 'editor@example.com',
            'display_name' => 'Editor',
        ]);
        if (!is_wp_error($editor_id)) {
            $created['editor_id'] = $editor_id;
        }

        // Create categories
        $categories = [
            'News' => 'Latest news and updates',
            'Tutorials' => 'How-to guides and tutorials',
            'Reviews' => 'Product and service reviews',
        ];

        foreach ($categories as $name => $description) {
            $cat_id = TestDataFactory::create_category([
                'cat_name' => $name,
                'category_description' => $description,
            ]);
            if (!is_wp_error($cat_id)) {
                $created['categories'][$name] = $cat_id;
            }
        }

        // Create tags
        $tags = ['WordPress', 'PHP', 'JavaScript', 'CSS', 'HTML', 'Docker', 'Testing'];
        foreach ($tags as $tag) {
            $tag_result = TestDataFactory::create_tag([
                'name' => $tag,
                'description' => "Posts tagged with {$tag}",
            ]);
            if (!is_wp_error($tag_result)) {
                $created['tags'][$tag] = $tag_result['term_id'];
            }
        }

        // Create sample posts
        $posts_data = [
            [
                'post_title' => 'Welcome to Our Blog',
                'post_content' => 'This is our first blog post. Welcome to our site!',
                'post_author' => $admin_id,
                'post_status' => 'publish',
            ],
            [
                'post_title' => 'Getting Started with WordPress',
                'post_content' => 'Learn how to get started with WordPress development.',
                'post_author' => $editor_id,
                'post_status' => 'publish',
            ],
            [
                'post_title' => 'PHP Best Practices',
                'post_content' => 'A guide to writing clean and maintainable PHP code.',
                'post_author' => $editor_id,
                'post_status' => 'publish',
            ],
            [
                'post_title' => 'Draft Post',
                'post_content' => 'This is a draft post that should not be published.',
                'post_author' => $editor_id,
                'post_status' => 'draft',
            ],
        ];

        foreach ($posts_data as $post_data) {
            $post_id = TestDataFactory::create_post($post_data);
            if (!is_wp_error($post_id)) {
                $created['posts'][] = $post_id;
            }
        }

        // Create sample pages
        $pages_data = [
            [
                'post_title' => 'About Us',
                'post_content' => 'Learn more about our company and mission.',
                'post_author' => $admin_id,
            ],
            [
                'post_title' => 'Contact',
                'post_content' => 'Get in touch with us.',
                'post_author' => $admin_id,
            ],
            [
                'post_title' => 'Privacy Policy',
                'post_content' => 'Our privacy policy and data handling practices.',
                'post_author' => $admin_id,
            ],
        ];

        foreach ($pages_data as $page_data) {
            $page_id = TestDataFactory::create_page($page_data);
            if (!is_wp_error($page_id)) {
                $created['pages'][] = $page_id;
            }
        }

        return $created;
    }

    /**
     * Create a theme-specific test setup
     *
     * @return array Created objects
     */
    public static function create_theme_setup()
    {
        $created = [];

        // Create theme options
        $theme_options = [
            'power_of_families_logo' => 'test-logo.png',
            'power_of_families_footer_text' => '© 2024 Power of Families',
            'power_of_families_contact_email' => 'contact@poweroffamilies.com',
            'power_of_families_social_facebook' => 'https://facebook.com/poweroffamilies',
            'power_of_families_social_twitter' => 'https://twitter.com/poweroffamilies',
        ];

        TestDataFactory::create_theme_options($theme_options);
        $created['theme_options'] = $theme_options;

        // Create custom post types if they exist
        if (post_type_exists('program')) {
            $programs = [
                [
                    'post_title' => 'Family Wellness Program',
                    'post_content' => 'A comprehensive program for family health and wellness.',
                    'post_type' => 'program',
                    'post_status' => 'publish',
                ],
                [
                    'post_title' => 'Parenting Workshop',
                    'post_content' => 'Learn effective parenting strategies and techniques.',
                    'post_type' => 'program',
                    'post_status' => 'publish',
                ],
            ];

            foreach ($programs as $program_data) {
                $program_id = TestDataFactory::create_custom_post('program', $program_data);
                if (!is_wp_error($program_id)) {
                    $created['programs'][] = $program_id;
                }
            }
        }

        // Create test widgets
        $widgets = [
            'search' => [
                'title' => 'Search',
                'text' => 'Search our site',
            ],
            'recent-posts' => [
                'title' => 'Recent Posts',
                'number' => 5,
            ],
            'categories' => [
                'title' => 'Categories',
                'count' => 1,
            ],
        ];

        TestDataFactory::create_widgets($widgets);
        $created['widgets'] = $widgets;

        return $created;
    }

    /**
     * Create a minimal test setup for fast tests
     *
     * @return array Created objects
     */
    public static function create_minimal_setup()
    {
        $created = [];

        // Create one admin user
        $admin_id = TestDataFactory::create_user('administrator', [
            'user_login' => 'testadmin',
            'user_email' => 'testadmin@example.com',
        ]);
        if (!is_wp_error($admin_id)) {
            $created['admin_id'] = $admin_id;
        }

        // Create one test post
        $post_id = TestDataFactory::create_post([
            'post_title' => 'Test Post',
            'post_content' => 'Test content',
            'post_author' => $admin_id,
        ]);
        if (!is_wp_error($post_id)) {
            $created['post_id'] = $post_id;
        }

        // Create one test page
        $page_id = TestDataFactory::create_page([
            'post_title' => 'Test Page',
            'post_content' => 'Test page content',
            'post_author' => $admin_id,
        ]);
        if (!is_wp_error($page_id)) {
            $created['page_id'] = $page_id;
        }

        return $created;
    }

    /**
     * Create a complex test scenario with relationships
     *
     * @return array Created objects
     */
    public static function create_complex_scenario()
    {
        $created = [];

        // Create users with different roles
        $users = [
            'administrator' => TestDataFactory::create_user('administrator', [
                'user_login' => 'admin',
                'user_email' => 'admin@example.com',
            ]),
            'editor' => TestDataFactory::create_user('editor', [
                'user_login' => 'editor',
                'user_email' => 'editor@example.com',
            ]),
            'author' => TestDataFactory::create_user('author', [
                'user_login' => 'author',
                'user_email' => 'author@example.com',
            ]),
        ];

        foreach ($users as $role => $user_id) {
            if (!is_wp_error($user_id)) {
                $created['users'][$role] = $user_id;
            }
        }

        // Create categories with hierarchy
        $parent_cat = TestDataFactory::create_category([
            'cat_name' => 'Parent Category',
            'category_description' => 'A parent category',
        ]);
        if (!is_wp_error($parent_cat)) {
            $created['categories']['parent'] = $parent_cat;
        }

        $child_cat = TestDataFactory::create_category([
            'cat_name' => 'Child Category',
            'category_description' => 'A child category',
            'category_parent' => $parent_cat,
        ]);
        if (!is_wp_error($child_cat)) {
            $created['categories']['child'] = $child_cat;
        }

        // Create posts with complex relationships
        $posts = [
            [
                'post_title' => 'Featured Post',
                'post_content' => 'This is a featured post with custom meta.',
                'post_author' => $users['administrator'],
                'post_status' => 'publish',
            ],
            [
                'post_title' => 'Editor Post',
                'post_content' => 'This post was created by an editor.',
                'post_author' => $users['editor'],
                'post_status' => 'publish',
            ],
            [
                'post_title' => 'Author Post',
                'post_content' => 'This post was created by an author.',
                'post_author' => $users['author'],
                'post_status' => 'publish',
            ],
        ];

        foreach ($posts as $post_data) {
            $post_id = TestDataFactory::create_post($post_data);
            if (!is_wp_error($post_id)) {
                $created['posts'][] = $post_id;

                // Add custom meta
                TestDataFactory::create_meta($post_id, 'featured', 'yes');
                TestDataFactory::create_meta($post_id, 'custom_field', 'test_value');

                // Assign to categories
                if (isset($created['categories']['parent'])) {
                    wp_set_post_categories($post_id, [$created['categories']['parent']]);
                }
            }
        }

        return $created;
    }

    /**
     * Clean up all test fixtures
     *
     * @param array $created Created objects from fixtures
     * @return void
     */
    public static function cleanup_fixtures($created)
    {
        // Clean up posts
        if (isset($created['posts'])) {
            foreach ($created['posts'] as $post_id) {
                wp_delete_post($post_id, true);
            }
        }

        // Clean up pages
        if (isset($created['pages'])) {
            foreach ($created['pages'] as $page_id) {
                wp_delete_post($page_id, true);
            }
        }

        // Clean up users
        if (isset($created['users'])) {
            foreach ($created['users'] as $user_id) {
                wp_delete_user($user_id);
            }
        }

        // Clean up categories
        if (isset($created['categories'])) {
            foreach ($created['categories'] as $cat_id) {
                wp_delete_category($cat_id);
            }
        }

        // Clean up tags
        if (isset($created['tags'])) {
            foreach ($created['tags'] as $tag_id) {
                wp_delete_term($tag_id, 'post_tag');
            }
        }

        // Clean up custom post types
        if (isset($created['programs'])) {
            foreach ($created['programs'] as $program_id) {
                wp_delete_post($program_id, true);
            }
        }
    }
}
