<?php

/**
 * Test Data Factory
 * 
 * Provides factory methods for creating test data consistently across tests
 * 
 * @package Power_Of_Families
 */

class TestDataFactory
{

    /**
     * Create a test user with specified role
     *
     * @param string $role User role (default: 'subscriber')
     * @param array $user_data Additional user data
     * @return WP_User|WP_Error
     */
    public static function create_user($role = 'subscriber', $user_data = [])
    {
        $default_data = [
            'user_login' => 'testuser_' . wp_generate_password(8, false),
            'user_email' => 'testuser_' . wp_generate_password(8, false) . '@example.com',
            'user_pass' => wp_generate_password(),
            'role' => $role,
            'first_name' => 'Test',
            'last_name' => 'User',
            'display_name' => 'Test User',
        ];

        $user_data = array_merge($default_data, $user_data);

        return wp_insert_user($user_data);
    }

    /**
     * Create a test post
     *
     * @param array $post_data Post data
     * @return int|WP_Error Post ID
     */
    public static function create_post($post_data = [])
    {
        $default_data = [
            'post_title' => 'Test Post ' . wp_generate_password(6, false),
            'post_content' => 'This is a test post content.',
            'post_status' => 'publish',
            'post_type' => 'post',
            'post_author' => 1,
        ];

        $post_data = array_merge($default_data, $post_data);

        return wp_insert_post($post_data);
    }

    /**
     * Create a test page
     *
     * @param array $page_data Page data
     * @return int|WP_Error Page ID
     */
    public static function create_page($page_data = [])
    {
        $default_data = [
            'post_title' => 'Test Page ' . wp_generate_password(6, false),
            'post_content' => 'This is a test page content.',
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_author' => 1,
        ];

        $page_data = array_merge($default_data, $page_data);

        return wp_insert_post($page_data);
    }

    /**
     * Create a test category
     *
     * @param array $category_data Category data
     * @return int|WP_Error Category ID
     */
    public static function create_category($category_data = [])
    {
        $default_data = [
            'cat_name' => 'Test Category ' . wp_generate_password(6, false),
            'category_description' => 'A test category',
            'category_nicename' => 'test-category-' . wp_generate_password(6, false),
        ];

        $category_data = array_merge($default_data, $category_data);

        return wp_create_category($category_data['cat_name']);
    }

    /**
     * Create a test tag
     *
     * @param array $tag_data Tag data
     * @return array|WP_Error Term array with 'term_id' and 'term_taxonomy_id', or WP_Error
     */
    public static function create_tag($tag_data = [])
    {
        $default_data = [
            'name' => 'Test Tag ' . wp_generate_password(6, false),
            'description' => 'A test tag',
            'slug' => 'test-tag-' . wp_generate_password(6, false),
        ];

        $tag_data = array_merge($default_data, $tag_data);

        return wp_insert_term($tag_data['name'], 'post_tag', $tag_data);
    }

    /**
     * Create a test custom post type
     *
     * @param string $post_type Post type name
     * @param array $post_data Post data
     * @return int|WP_Error Post ID
     */
    public static function create_custom_post($post_type, $post_data = [])
    {
        $default_data = [
            'post_title' => 'Test ' . ucfirst($post_type) . ' ' . wp_generate_password(6, false),
            'post_content' => 'This is a test ' . $post_type . ' content.',
            'post_status' => 'publish',
            'post_type' => $post_type,
            'post_author' => 1,
        ];

        $post_data = array_merge($default_data, $post_data);

        return wp_insert_post($post_data);
    }

    /**
     * Create test meta data
     *
     * @param int $object_id Object ID (post, user, etc.)
     * @param string $meta_key Meta key
     * @param mixed $meta_value Meta value
     * @param string $meta_type Meta type (post, user, comment, term)
     * @return int|false Meta ID
     */
    public static function create_meta($object_id, $meta_key, $meta_value, $meta_type = 'post')
    {
        return add_metadata($meta_type, $object_id, $meta_key, $meta_value);
    }

    /**
     * Create a test menu
     *
     * @param array $menu_data Menu data
     * @return int|WP_Error Menu ID
     */
    public static function create_menu($menu_data = [])
    {
        $default_data = [
            'menu-name' => 'Test Menu ' . wp_generate_password(6, false),
            'description' => 'A test menu',
        ];

        $menu_data = array_merge($default_data, $menu_data);

        return wp_create_nav_menu($menu_data['menu-name']);
    }

    /**
     * Create test theme options
     *
     * @param array $options Options array
     * @return void
     */
    public static function create_theme_options($options = [])
    {
        $default_options = [
            'site_title' => 'Test Site',
            'site_description' => 'A test site description',
            'header_text_color' => '#000000',
            'background_color' => '#ffffff',
        ];

        $options = array_merge($default_options, $options);

        foreach ($options as $key => $value) {
            update_option($key, $value);
        }
    }

    /**
     * Create test widgets
     *
     * @param array $widgets Widget data
     * @return void
     */
    public static function create_widgets($widgets = [])
    {
        $default_widgets = [
            'search' => [
                'title' => 'Test Search',
                'text' => 'Search the site',
            ],
            'recent-posts' => [
                'title' => 'Recent Posts',
                'number' => 5,
            ],
        ];

        $widgets = array_merge($default_widgets, $widgets);

        foreach ($widgets as $widget_id => $widget_data) {
            update_option('widget_' . $widget_id, $widget_data);
        }
    }

    /**
     * Clean up test data
     *
     * @param array $ids Array of IDs to clean up
     * @return void
     */
    public static function cleanup($ids = [])
    {
        foreach ($ids as $id) {
            if (is_numeric($id)) {
                wp_delete_post($id, true);
            }
        }
    }

    /**
     * Create a complete test site setup
     *
     * @param array $config Configuration array
     * @return array Created objects
     */
    public static function create_test_site($config = [])
    {
        $default_config = [
            'users' => ['admin', 'editor', 'subscriber'],
            'posts' => 5,
            'pages' => 3,
            'categories' => 3,
            'tags' => 5,
        ];

        $config = array_merge($default_config, $config);
        $created = [];

        // Create users
        foreach ($config['users'] as $role) {
            $user_id = self::create_user($role);
            if (!is_wp_error($user_id)) {
                $created['users'][] = $user_id;
            }
        }

        // Create categories
        for ($i = 0; $i < $config['categories']; $i++) {
            $cat_id = self::create_category();
            if (!is_wp_error($cat_id)) {
                $created['categories'][] = $cat_id;
            }
        }

        // Create tags
        for ($i = 0; $i < $config['tags']; $i++) {
            $tag_result = self::create_tag();
            if (!is_wp_error($tag_result)) {
                $created['tags'][] = $tag_result['term_id'];
            }
        }

        // Create posts
        for ($i = 0; $i < $config['posts']; $i++) {
            $post_id = self::create_post();
            if (!is_wp_error($post_id)) {
                $created['posts'][] = $post_id;

                // Assign random category and tags
                if (!empty($created['categories'])) {
                    wp_set_post_categories($post_id, [array_rand($created['categories'])]);
                }
                if (!empty($created['tags'])) {
                    wp_set_post_tags($post_id, array_rand($created['tags'], min(3, count($created['tags']))));
                }
            }
        }

        // Create pages
        for ($i = 0; $i < $config['pages']; $i++) {
            $page_id = self::create_page();
            if (!is_wp_error($page_id)) {
                $created['pages'][] = $page_id;
            }
        }

        return $created;
    }
}
