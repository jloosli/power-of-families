<?php

/**
 * Test Seeder Configuration
 * 
 * Configuration class for test database seeding scenarios
 * 
 * @package Power_Of_Families
 */

class TestSeederConfig
{

    /**
     * Get available seeding scenarios
     *
     * @return array Available scenarios
     */
    public static function get_scenarios()
    {
        return [
            'minimal' => [
                'name' => 'Minimal Test Data',
                'description' => 'Basic test data for simple tests',
                'users' => 1,
                'posts' => 1,
                'pages' => 1,
                'categories' => 0,
                'tags' => 0,
                'custom_posts' => 0,
            ],
            'blog' => [
                'name' => 'Blog Setup',
                'description' => 'Complete blog with posts, pages, and users',
                'users' => 3,
                'posts' => 5,
                'pages' => 3,
                'categories' => 3,
                'tags' => 5,
                'custom_posts' => 0,
            ],
            'theme' => [
                'name' => 'Theme Setup',
                'description' => 'Theme-specific data and custom post types',
                'users' => 2,
                'posts' => 3,
                'pages' => 2,
                'categories' => 2,
                'tags' => 3,
                'custom_posts' => 2,
            ],
            'complex' => [
                'name' => 'Complex Scenario',
                'description' => 'Complex relationships and hierarchies',
                'users' => 4,
                'posts' => 8,
                'pages' => 4,
                'categories' => 5,
                'tags' => 8,
                'custom_posts' => 3,
            ],
            'performance' => [
                'name' => 'Performance Test',
                'description' => 'Large dataset for performance testing',
                'users' => 50,
                'posts' => 100,
                'pages' => 10,
                'categories' => 10,
                'tags' => 20,
                'custom_posts' => 0,
            ],
            'security' => [
                'name' => 'Security Test',
                'description' => 'Data with potential security issues',
                'users' => 2,
                'posts' => 5,
                'pages' => 2,
                'categories' => 1,
                'tags' => 2,
                'custom_posts' => 0,
            ],
            'accessibility' => [
                'name' => 'Accessibility Test',
                'description' => 'Content focused on accessibility testing',
                'users' => 2,
                'posts' => 3,
                'pages' => 2,
                'categories' => 1,
                'tags' => 2,
                'custom_posts' => 0,
            ],
        ];
    }

    /**
     * Get scenario configuration
     *
     * @param string $scenario Scenario name
     * @return array|null Scenario configuration
     */
    public static function get_scenario($scenario)
    {
        $scenarios = self::get_scenarios();
        return isset($scenarios[$scenario]) ? $scenarios[$scenario] : null;
    }

    /**
     * Get default scenario
     *
     * @return string Default scenario name
     */
    public static function get_default_scenario()
    {
        return 'minimal';
    }

    /**
     * Validate scenario
     *
     * @param string $scenario Scenario name
     * @return bool True if valid
     */
    public static function is_valid_scenario($scenario)
    {
        return self::get_scenario($scenario) !== null;
    }

    /**
     * Get seeding options for a scenario
     *
     * @param string $scenario Scenario name
     * @return array Seeding options
     */
    public static function get_seeding_options($scenario)
    {
        $config = self::get_scenario($scenario);
        if (!$config) {
            return [];
        }

        return [
            'users' => [
                'count' => $config['users'],
                'roles' => ['administrator', 'editor', 'author', 'subscriber'],
                'include_meta' => true,
            ],
            'posts' => [
                'count' => $config['posts'],
                'statuses' => ['publish', 'draft', 'private'],
                'include_meta' => true,
                'include_categories' => $config['categories'] > 0,
                'include_tags' => $config['tags'] > 0,
            ],
            'pages' => [
                'count' => $config['pages'],
                'statuses' => ['publish', 'draft', 'private'],
                'include_meta' => true,
            ],
            'categories' => [
                'count' => $config['categories'],
                'include_hierarchy' => $scenario === 'complex',
            ],
            'tags' => [
                'count' => $config['tags'],
            ],
            'custom_posts' => [
                'count' => $config['custom_posts'],
                'post_types' => ['program', 'event', 'resource'],
            ],
            'theme_options' => [
                'include' => in_array($scenario, ['theme', 'complex']),
            ],
            'widgets' => [
                'include' => in_array($scenario, ['theme', 'complex']),
            ],
        ];
    }

    /**
     * Get environment-specific seeding configuration
     *
     * @return array Environment configuration
     */
    public static function get_environment_config()
    {
        return [
            'test_db_name' => getenv('TEST_DB_NAME') ?: 'wordpress_tests',
            'test_db_user' => getenv('TEST_DB_USER') ?: 'root',
            'test_db_password' => getenv('TEST_DB_PASSWORD') ?: 'password',
            'test_db_host' => getenv('TEST_DB_HOST') ?: 'db',
            'cleanup_after_tests' => getenv('CLEANUP_AFTER_TESTS') !== 'false',
            'seed_before_tests' => getenv('SEED_BEFORE_TESTS') !== 'false',
            'default_scenario' => getenv('TEST_SEED_SCENARIO') ?: self::get_default_scenario(),
        ];
    }

    /**
     * Get seeding hooks configuration
     *
     * @return array Hooks configuration
     */
    public static function get_hooks_config()
    {
        return [
            'before_seed' => [
                'cleanup_existing_data' => true,
                'setup_wordpress_core' => true,
                'create_test_users' => true,
            ],
            'after_seed' => [
                'verify_data_integrity' => true,
                'setup_test_environment' => true,
                'cache_warmup' => false,
            ],
            'before_cleanup' => [
                'backup_test_data' => false,
                'export_test_data' => false,
            ],
            'after_cleanup' => [
                'verify_cleanup' => true,
                'reset_environment' => true,
            ],
        ];
    }

    /**
     * Get performance settings for seeding
     *
     * @return array Performance settings
     */
    public static function get_performance_config()
    {
        return [
            'batch_size' => 50,
            'memory_limit' => '256M',
            'time_limit' => 300,
            'disable_plugins' => true,
            'disable_themes' => false,
            'disable_cron' => true,
            'disable_autosave' => true,
            'disable_revisions' => true,
        ];
    }

    /**
     * Get logging configuration
     *
     * @return array Logging configuration
     */
    public static function get_logging_config()
    {
        return [
            'enabled' => getenv('TEST_SEED_LOGGING') !== 'false',
            'level' => getenv('TEST_SEED_LOG_LEVEL') ?: 'info',
            'file' => getenv('TEST_SEED_LOG_FILE') ?: '/tmp/test-seed.log',
            'include_timing' => true,
            'include_memory' => true,
            'include_queries' => false,
        ];
    }

    /**
     * Get scenario-specific data templates
     *
     * @param string $scenario Scenario name
     * @return array Data templates
     */
    public static function get_data_templates($scenario)
    {
        $templates = [
            'minimal' => [
                'post_titles' => ['Test Post'],
                'page_titles' => ['Test Page'],
                'user_names' => ['Test User'],
            ],
            'blog' => [
                'post_titles' => [
                    'Welcome to Our Blog',
                    'Getting Started with WordPress',
                    'PHP Best Practices',
                    'JavaScript Tips and Tricks',
                    'CSS Grid Layout Guide',
                ],
                'page_titles' => [
                    'About Us',
                    'Contact',
                    'Privacy Policy',
                ],
                'user_names' => [
                    'Administrator',
                    'Content Editor',
                    'Contributor',
                ],
            ],
            'theme' => [
                'post_titles' => [
                    'Family Wellness Program',
                    'Parenting Workshop',
                    'Community Support',
                ],
                'page_titles' => [
                    'Our Programs',
                    'Get Involved',
                ],
                'user_names' => [
                    'Program Director',
                    'Community Manager',
                ],
            ],
            'complex' => [
                'post_titles' => [
                    'Featured Article',
                    'Editor\'s Choice',
                    'Author Spotlight',
                    'Community News',
                    'Event Announcement',
                    'Resource Guide',
                    'Tutorial Series',
                    'Case Study',
                ],
                'page_titles' => [
                    'Home',
                    'About',
                    'Services',
                    'Contact',
                ],
                'user_names' => [
                    'Site Administrator',
                    'Content Editor',
                    'Staff Writer',
                    'Community Moderator',
                ],
            ],
        ];

        return isset($templates[$scenario]) ? $templates[$scenario] : $templates['minimal'];
    }

    /**
     * Get scenario description
     *
     * @param string $scenario Scenario name
     * @return string Scenario description
     */
    public static function get_scenario_description($scenario)
    {
        $config = self::get_scenario($scenario);
        return $config ? $config['description'] : 'Unknown scenario';
    }

    /**
     * Get scenario statistics
     *
     * @param string $scenario Scenario name
     * @return array Scenario statistics
     */
    public static function get_scenario_stats($scenario)
    {
        $config = self::get_scenario($scenario);
        if (!$config) {
            return [];
        }

        $total_objects = array_sum([
            $config['users'],
            $config['posts'],
            $config['pages'],
            $config['categories'],
            $config['tags'],
            $config['custom_posts'],
        ]);

        return [
            'total_objects' => $total_objects,
            'estimated_time' => self::estimate_seeding_time($config),
            'memory_usage' => self::estimate_memory_usage($config),
            'complexity' => self::calculate_complexity($config),
        ];
    }

    /**
     * Estimate seeding time for a scenario
     *
     * @param array $config Scenario configuration
     * @return int Estimated time in seconds
     */
    private static function estimate_seeding_time($config)
    {
        $base_time = 5; // Base time for setup
        $time_per_object = 0.1; // Time per object in seconds

        $total_objects = array_sum([
            $config['users'],
            $config['posts'],
            $config['pages'],
            $config['categories'],
            $config['tags'],
            $config['custom_posts'],
        ]);

        return $base_time + ($total_objects * $time_per_object);
    }

    /**
     * Estimate memory usage for a scenario
     *
     * @param array $config Scenario configuration
     * @return string Estimated memory usage
     */
    private static function estimate_memory_usage($config)
    {
        $base_memory = 32; // Base memory in MB
        $memory_per_object = 0.5; // Memory per object in MB

        $total_objects = array_sum([
            $config['users'],
            $config['posts'],
            $config['pages'],
            $config['categories'],
            $config['tags'],
            $config['custom_posts'],
        ]);

        $total_memory = $base_memory + ($total_objects * $memory_per_object);
        return $total_memory . 'MB';
    }

    /**
     * Calculate complexity score for a scenario
     *
     * @param array $config Scenario configuration
     * @return int Complexity score (1-10)
     */
    private static function calculate_complexity($config)
    {
        $score = 1;

        // Add points for each type of object
        $score += min($config['users'] / 10, 2);
        $score += min($config['posts'] / 20, 2);
        $score += min($config['pages'] / 10, 1);
        $score += min($config['categories'] / 5, 1);
        $score += min($config['tags'] / 10, 1);
        $score += min($config['custom_posts'] / 5, 2);

        return min(round($score), 10);
    }
}
