<?php

/**
 * Enhanced Test Data Seeder
 * 
 * Advanced test database seeding with configuration and logging
 * 
 * @package Power_Of_Families
 */

class EnhancedTestDataSeeder
{

    /**
     * Seed the database with test data using configuration
     *
     * @param string $scenario Test scenario to seed
     * @param array $options Additional seeding options
     * @return array Created objects
     */
    public static function seed($scenario = 'minimal', $options = [])
    {
        // Load configuration
        $config = TestSeederConfig::get_scenario($scenario);
        if (!$config) {
            throw new InvalidArgumentException("Invalid scenario: $scenario");
        }

        // Get seeding options
        $seeding_options = TestSeederConfig::get_seeding_options($scenario);
        $seeding_options = array_merge($seeding_options, $options);

        // Get environment configuration
        $env_config = TestSeederConfig::get_environment_config();
        $hooks_config = TestSeederConfig::get_hooks_config();
        $performance_config = TestSeederConfig::get_performance_config();

        // Set performance settings
        self::apply_performance_settings($performance_config);

        // Initialize logging
        $logger = self::initialize_logging($scenario);

        $logger->info("Starting database seeding for scenario: $scenario");

        // Execute before-seed hooks
        if ($hooks_config['before_seed']['cleanup_existing_data']) {
            $logger->info("Cleaning up existing test data");
            self::cleanup_existing_data();
        }

        if ($hooks_config['before_seed']['setup_wordpress_core']) {
            $logger->info("Setting up WordPress core");
            self::setup_wordpress_core();
        }

        // Seed data based on scenario
        $created = [];
        $start_time = microtime(true);

        try {
            // Seed users
            if ($seeding_options['users']['count'] > 0) {
                $logger->info("Seeding users: {$seeding_options['users']['count']}");
                $created['users'] = self::seed_users($seeding_options['users'], $scenario);
            }

            // Seed categories
            if ($seeding_options['categories']['count'] > 0) {
                $logger->info("Seeding categories: {$seeding_options['categories']['count']}");
                $created['categories'] = self::seed_categories($seeding_options['categories'], $scenario);
            }

            // Seed tags
            if ($seeding_options['tags']['count'] > 0) {
                $logger->info("Seeding tags: {$seeding_options['tags']['count']}");
                $created['tags'] = self::seed_tags($seeding_options['tags'], $scenario);
            }

            // Seed posts
            if ($seeding_options['posts']['count'] > 0) {
                $logger->info("Seeding posts: {$seeding_options['posts']['count']}");
                $created['posts'] = self::seed_posts($seeding_options['posts'], $scenario, $created);
            }

            // Seed pages
            if ($seeding_options['pages']['count'] > 0) {
                $logger->info("Seeding pages: {$seeding_options['pages']['count']}");
                $created['pages'] = self::seed_pages($seeding_options['pages'], $scenario, $created);
            }

            // Seed custom post types
            if ($seeding_options['custom_posts']['count'] > 0) {
                $logger->info("Seeding custom posts: {$seeding_options['custom_posts']['count']}");
                $created['custom_posts'] = self::seed_custom_posts($seeding_options['custom_posts'], $scenario, $created);
            }

            // Seed theme options
            if ($seeding_options['theme_options']['include']) {
                $logger->info("Seeding theme options");
                $created['theme_options'] = self::seed_theme_options($scenario);
            }

            // Seed widgets
            if ($seeding_options['widgets']['include']) {
                $logger->info("Seeding widgets");
                $created['widgets'] = self::seed_widgets($scenario);
            }

            $end_time = microtime(true);
            $duration = round($end_time - $start_time, 2);

            $logger->info("Database seeding completed in {$duration} seconds");

            // Execute after-seed hooks
            if ($hooks_config['after_seed']['verify_data_integrity']) {
                $logger->info("Verifying data integrity");
                self::verify_data_integrity($created);
            }

            if ($hooks_config['after_seed']['setup_test_environment']) {
                $logger->info("Setting up test environment");
                self::setup_test_environment($created);
            }

            return $created;
        } catch (Exception $e) {
            $logger->error("Seeding failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Seed users based on configuration
     *
     * @param array $options User seeding options
     * @param string $scenario Test scenario
     * @return array Created user IDs
     */
    private static function seed_users($options, $scenario)
    {
        $created = [];
        $templates = TestSeederConfig::get_data_templates($scenario);
        $roles = $options['roles'];
        $count = $options['count'];

        for ($i = 0; $i < $count; $i++) {
            $role = $roles[$i % count($roles)];
            $user_name = isset($templates['user_names'][$i]) ? $templates['user_names'][$i] : "Test User $i";

            $user_data = [
                'user_login' => sanitize_title($user_name) . '_' . $i,
                'user_email' => sanitize_title($user_name) . '_' . $i . '@example.com',
                'display_name' => $user_name,
                'role' => $role,
            ];

            $user_id = TestDataFactory::create_user($role, $user_data);
            if (!is_wp_error($user_id)) {
                $created[] = $user_id;

                // Add user meta if requested
                if ($options['include_meta']) {
                    TestDataFactory::create_meta($user_id, 'test_user', 'true', 'user');
                    TestDataFactory::create_meta($user_id, 'test_scenario', $scenario, 'user');
                }
            }
        }

        return $created;
    }

    /**
     * Seed categories based on configuration
     *
     * @param array $options Category seeding options
     * @param string $scenario Test scenario
     * @return array Created category IDs
     */
    private static function seed_categories($options, $scenario)
    {
        $created = [];
        $count = $options['count'];
        $include_hierarchy = $options['include_hierarchy'];

        for ($i = 0; $i < $count; $i++) {
            $category_data = [
                'cat_name' => "Test Category $i",
                'category_description' => "A test category for scenario: $scenario",
            ];

            // Add parent category for hierarchy
            if ($include_hierarchy && $i > 0 && !empty($created)) {
                $category_data['category_parent'] = $created[0]; // First category as parent
            }

            $cat_id = TestDataFactory::create_category($category_data);
            if (!is_wp_error($cat_id)) {
                $created[] = $cat_id;
            }
        }

        return $created;
    }

    /**
     * Seed tags based on configuration
     *
     * @param array $options Tag seeding options
     * @param string $scenario Test scenario
     * @return array Created tag IDs
     */
    private static function seed_tags($options, $scenario)
    {
        $created = [];
        $count = $options['count'];

        $tag_names = ['WordPress', 'PHP', 'JavaScript', 'CSS', 'HTML', 'Docker', 'Testing', 'Development'];

        for ($i = 0; $i < $count; $i++) {
            $tag_name = isset($tag_names[$i]) ? $tag_names[$i] : "Test Tag $i";

            $tag_data = [
                'name' => $tag_name,
                'description' => "A test tag for scenario: $scenario",
            ];

            $tag_result = TestDataFactory::create_tag($tag_data);
            if (!is_wp_error($tag_result)) {
                $created[] = $tag_result['term_id'];
            }
        }

        return $created;
    }

    /**
     * Seed posts based on configuration
     *
     * @param array $options Post seeding options
     * @param string $scenario Test scenario
     * @param array $existing_data Existing created data
     * @return array Created post IDs
     */
    private static function seed_posts($options, $scenario, $existing_data)
    {
        $created = [];
        $templates = TestSeederConfig::get_data_templates($scenario);
        $count = $options['count'];
        $statuses = $options['statuses'];
        $include_meta = $options['include_meta'];
        $include_categories = $options['include_categories'];
        $include_tags = $options['include_tags'];

        for ($i = 0; $i < $count; $i++) {
            $title = isset($templates['post_titles'][$i]) ? $templates['post_titles'][$i] : "Test Post $i";
            $status = $statuses[$i % count($statuses)];
            $author = !empty($existing_data['users']) ? $existing_data['users'][$i % count($existing_data['users'])] : 1;

            $post_data = [
                'post_title' => $title,
                'post_content' => "This is test post content for: $title. Scenario: $scenario",
                'post_status' => $status,
                'post_author' => $author,
            ];

            $post_id = TestDataFactory::create_post($post_data);
            if (!is_wp_error($post_id)) {
                $created[] = $post_id;

                // Add post meta if requested
                if ($include_meta) {
                    TestDataFactory::create_meta($post_id, 'test_post', 'true');
                    TestDataFactory::create_meta($post_id, 'test_scenario', $scenario);
                }

                // Assign categories if requested and available
                if ($include_categories && !empty($existing_data['categories'])) {
                    $category_ids = array_slice($existing_data['categories'], 0, rand(1, min(3, count($existing_data['categories']))));
                    wp_set_post_categories($post_id, $category_ids);
                }

                // Assign tags if requested and available
                if ($include_tags && !empty($existing_data['tags'])) {
                    $tag_ids = array_slice($existing_data['tags'], 0, rand(1, min(3, count($existing_data['tags']))));
                    wp_set_post_tags($post_id, $tag_ids);
                }
            }
        }

        return $created;
    }

    /**
     * Seed pages based on configuration
     *
     * @param array $options Page seeding options
     * @param string $scenario Test scenario
     * @param array $existing_data Existing created data
     * @return array Created page IDs
     */
    private static function seed_pages($options, $scenario, $existing_data)
    {
        $created = [];
        $templates = TestSeederConfig::get_data_templates($scenario);
        $count = $options['count'];
        $statuses = $options['statuses'];
        $include_meta = $options['include_meta'];

        for ($i = 0; $i < $count; $i++) {
            $title = isset($templates['page_titles'][$i]) ? $templates['page_titles'][$i] : "Test Page $i";
            $status = $statuses[$i % count($statuses)];
            $author = !empty($existing_data['users']) ? $existing_data['users'][$i % count($existing_data['users'])] : 1;

            $page_data = [
                'post_title' => $title,
                'post_content' => "This is test page content for: $title. Scenario: $scenario",
                'post_type' => 'page',
                'post_status' => $status,
                'post_author' => $author,
            ];

            $page_id = TestDataFactory::create_page($page_data);
            if (!is_wp_error($page_id)) {
                $created[] = $page_id;

                // Add page meta if requested
                if ($include_meta) {
                    TestDataFactory::create_meta($page_id, 'test_page', 'true');
                    TestDataFactory::create_meta($page_id, 'test_scenario', $scenario);
                }
            }
        }

        return $created;
    }

    /**
     * Seed custom post types based on configuration
     *
     * @param array $options Custom post seeding options
     * @param string $scenario Test scenario
     * @param array $existing_data Existing created data
     * @return array Created custom post IDs
     */
    private static function seed_custom_posts($options, $scenario, $existing_data)
    {
        $created = [];
        $count = $options['count'];
        $post_types = $options['post_types'];

        for ($i = 0; $i < $count; $i++) {
            $post_type = $post_types[$i % count($post_types)];
            $author = !empty($existing_data['users']) ? $existing_data['users'][$i % count($existing_data['users'])] : 1;

            $post_data = [
                'post_title' => "Test $post_type $i",
                'post_content' => "This is test content for $post_type. Scenario: $scenario",
                'post_type' => $post_type,
                'post_status' => 'publish',
                'post_author' => $author,
            ];

            $post_id = TestDataFactory::create_custom_post($post_type, $post_data);
            if (!is_wp_error($post_id)) {
                $created[] = $post_id;
            }
        }

        return $created;
    }

    /**
     * Seed theme options based on scenario
     *
     * @param string $scenario Test scenario
     * @return array Theme options
     */
    private static function seed_theme_options($scenario)
    {
        $options = [
            'power_of_families_site_title' => 'Power of Families',
            'power_of_families_tagline' => 'Strengthening families through community',
            'power_of_families_test_scenario' => $scenario,
        ];

        TestDataFactory::create_theme_options($options);
        return $options;
    }

    /**
     * Seed widgets based on scenario
     *
     * @param string $scenario Test scenario
     * @return array Widget data
     */
    private static function seed_widgets($scenario)
    {
        $widgets = [
            'search' => [
                'title' => 'Test Search',
                'text' => 'Search the test site',
            ],
            'recent-posts' => [
                'title' => 'Recent Test Posts',
                'number' => 5,
            ],
        ];

        TestDataFactory::create_widgets($widgets);
        return $widgets;
    }

    /**
     * Apply performance settings
     *
     * @param array $config Performance configuration
     * @return void
     */
    private static function apply_performance_settings($config)
    {
        if (isset($config['memory_limit'])) {
            ini_set('memory_limit', $config['memory_limit']);
        }

        if (isset($config['time_limit'])) {
            set_time_limit($config['time_limit']);
        }

        if ($config['disable_plugins']) {
            add_filter('option_active_plugins', '__return_empty_array');
        }

        if ($config['disable_cron']) {
            wp_clear_scheduled_hook('wp_scheduled_delete');
        }
    }

    /**
     * Initialize logging
     *
     * @param string $scenario Test scenario
     * @return object Logger instance
     */
    private static function initialize_logging($scenario)
    {
        $log_config = TestSeederConfig::get_logging_config();

        if (!$log_config['enabled']) {
            return new class {
                public function info($message) {}
                public function error($message) {}
            };
        }

        // Simple file logger
        return new class($log_config, $scenario) {
            private $log_file;
            private $level;

            public function __construct($config, $scenario)
            {
                $this->log_file = $config['file'];
                $this->level = $config['level'];
            }

            public function info($message)
            {
                $this->log('INFO', $message);
            }

            public function error($message)
            {
                $this->log('ERROR', $message);
            }

            private function log($level, $message)
            {
                $timestamp = date('Y-m-d H:i:s');
                $memory = memory_get_usage(true);
                $log_entry = "[$timestamp] [$level] [Memory: {$memory} bytes] $message" . PHP_EOL;
                file_put_contents($this->log_file, $log_entry, FILE_APPEND | LOCK_EX);
            }
        };
    }

    /**
     * Clean up existing test data
     *
     * @return void
     */
    private static function cleanup_existing_data()
    {
        // Clean up posts
        $posts = get_posts(['numberposts' => -1, 'post_type' => 'any']);
        foreach ($posts as $post) {
            wp_delete_post($post->ID, true);
        }

        // Clean up users (except admin)
        $users = get_users(['exclude' => [1]]);
        foreach ($users as $user) {
            wp_delete_user($user->ID);
        }

        // Clean up terms
        $terms = get_terms(['taxonomy' => ['category', 'post_tag'], 'hide_empty' => false]);
        foreach ($terms as $term) {
            wp_delete_term($term->term_id, $term->taxonomy);
        }
    }

    /**
     * Set up WordPress core
     *
     * @return void
     */
    private static function setup_wordpress_core()
    {
        // Ensure WordPress is properly loaded
        if (!function_exists('wp_insert_user')) {
            return;
        }

        // Create default admin user if it doesn't exist
        if (!get_user_by('login', 'admin')) {
            wp_insert_user([
                'user_login' => 'admin',
                'user_email' => 'admin@example.com',
                'user_pass' => 'password',
                'role' => 'administrator',
            ]);
        }
    }

    /**
     * Verify data integrity
     *
     * @param array $created Created objects
     * @return void
     */
    private static function verify_data_integrity($created)
    {
        // Verify users exist
        if (isset($created['users'])) {
            foreach ($created['users'] as $user_id) {
                if (!get_user_by('id', $user_id)) {
                    throw new Exception("User $user_id was not created properly");
                }
            }
        }

        // Verify posts exist
        if (isset($created['posts'])) {
            foreach ($created['posts'] as $post_id) {
                if (!get_post($post_id)) {
                    throw new Exception("Post $post_id was not created properly");
                }
            }
        }
    }

    /**
     * Set up test environment
     *
     * @param array $created Created objects
     * @return void
     */
    private static function setup_test_environment($created)
    {
        // Set up test-specific options
        update_option('test_environment', true);
        update_option('test_scenario_data', $created);
        update_option('test_seed_timestamp', time());
    }
}
