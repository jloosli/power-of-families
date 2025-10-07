<?php

/**
 * Test Data Seeder
 * 
 * Seeds the test database with consistent test data
 * 
 * @package Power_Of_Families
 */

class TestDataSeeder
{

    /**
     * Seed the database with test data
     *
     * @param string $scenario Test scenario to seed
     * @return array Created objects
     */
    public static function seed($scenario = 'minimal')
    {
        switch ($scenario) {
            case 'blog':
                return TestFixtures::create_blog_setup();
            case 'theme':
                return TestFixtures::create_theme_setup();
            case 'complex':
                return TestFixtures::create_complex_scenario();
            case 'minimal':
            default:
                return TestFixtures::create_minimal_setup();
        }
    }

    /**
     * Seed with WordPress core data
     *
     * @return array Created objects
     */
    public static function seed_wordpress_core()
    {
        $created = [];

        // Create default admin user
        $admin_id = TestDataFactory::create_user('administrator', [
            'user_login' => 'admin',
            'user_email' => 'admin@example.com',
            'display_name' => 'Administrator',
        ]);
        if (!is_wp_error($admin_id)) {
            $created['admin_id'] = $admin_id;
        }

        // Create sample categories
        $categories = [
            'Uncategorized' => 'Default category for posts',
            'News' => 'Latest news and updates',
            'Tutorials' => 'How-to guides and tutorials',
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

        // Create sample posts
        $posts = [
            [
                'post_title' => 'Hello World',
                'post_content' => 'Welcome to WordPress. This is your first post.',
                'post_author' => $admin_id,
                'post_status' => 'publish',
            ],
            [
                'post_title' => 'Sample Page',
                'post_content' => 'This is an example page.',
                'post_type' => 'page',
                'post_author' => $admin_id,
                'post_status' => 'publish',
            ],
        ];

        foreach ($posts as $post_data) {
            $post_id = TestDataFactory::create_post($post_data);
            if (!is_wp_error($post_id)) {
                $created['posts'][] = $post_id;
            }
        }

        return $created;
    }

    /**
     * Seed with theme-specific data
     *
     * @return array Created objects
     */
    public static function seed_theme_data()
    {
        $created = [];

        // Create theme options
        $theme_options = [
            'power_of_families_site_title' => 'Power of Families',
            'power_of_families_tagline' => 'Strengthening families through community',
            'power_of_families_logo' => 'logo.png',
            'power_of_families_footer_text' => '© 2024 Power of Families. All rights reserved.',
            'power_of_families_contact_email' => 'info@poweroffamilies.com',
            'power_of_families_contact_phone' => '(555) 123-4567',
            'power_of_families_address' => '123 Family Street, Community City, ST 12345',
            'power_of_families_social_facebook' => 'https://facebook.com/poweroffamilies',
            'power_of_families_social_twitter' => 'https://twitter.com/poweroffamilies',
            'power_of_families_social_instagram' => 'https://instagram.com/poweroffamilies',
            'power_of_families_social_linkedin' => 'https://linkedin.com/company/poweroffamilies',
        ];

        TestDataFactory::create_theme_options($theme_options);
        $created['theme_options'] = $theme_options;

        // Create custom post types if they exist
        if (post_type_exists('program')) {
            $programs = [
                [
                    'post_title' => 'Family Wellness Program',
                    'post_content' => 'A comprehensive program focused on family health and wellness.',
                    'post_type' => 'program',
                    'post_status' => 'publish',
                ],
                [
                    'post_title' => 'Parenting Workshop Series',
                    'post_content' => 'Learn effective parenting strategies and techniques.',
                    'post_type' => 'program',
                    'post_status' => 'publish',
                ],
                [
                    'post_title' => 'Community Support Group',
                    'post_content' => 'Join our community support group for families.',
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

        // Create test users with different roles
        $users = [
            'admin' => TestDataFactory::create_user('administrator', [
                'user_login' => 'admin',
                'user_email' => 'admin@poweroffamilies.com',
                'display_name' => 'Site Administrator',
            ]),
            'editor' => TestDataFactory::create_user('editor', [
                'user_login' => 'editor',
                'user_email' => 'editor@poweroffamilies.com',
                'display_name' => 'Content Editor',
            ]),
            'member' => TestDataFactory::create_user('subscriber', [
                'user_login' => 'member',
                'user_email' => 'member@poweroffamilies.com',
                'display_name' => 'Family Member',
            ]),
        ];

        foreach ($users as $role => $user_id) {
            if (!is_wp_error($user_id)) {
                $created['users'][$role] = $user_id;
            }
        }

        return $created;
    }

    /**
     * Seed with test content for specific test scenarios
     *
     * @param string $test_type Type of test content needed
     * @return array Created objects
     */
    public static function seed_test_content($test_type)
    {
        switch ($test_type) {
            case 'performance':
                return self::seed_performance_test_data();
            case 'security':
                return self::seed_security_test_data();
            case 'accessibility':
                return self::seed_accessibility_test_data();
            default:
                return self::seed_minimal_test_data();
        }
    }

    /**
     * Seed performance test data
     *
     * @return array Created objects
     */
    private static function seed_performance_test_data()
    {
        $created = [];

        // Create many posts for performance testing
        for ($i = 1; $i <= 100; $i++) {
            $post_id = TestDataFactory::create_post([
                'post_title' => "Performance Test Post {$i}",
                'post_content' => "This is performance test post number {$i} with some content to test database performance.",
                'post_status' => 'publish',
            ]);
            if (!is_wp_error($post_id)) {
                $created['posts'][] = $post_id;
            }
        }

        // Create many users
        for ($i = 1; $i <= 50; $i++) {
            $user_id = TestDataFactory::create_user('subscriber', [
                'user_login' => "user{$i}",
                'user_email' => "user{$i}@example.com",
            ]);
            if (!is_wp_error($user_id)) {
                $created['users'][] = $user_id;
            }
        }

        return $created;
    }

    /**
     * Seed security test data
     *
     * @return array Created objects
     */
    private static function seed_security_test_data()
    {
        $created = [];

        // Create posts with potentially problematic content
        $security_posts = [
            [
                'post_title' => 'Post with <script>alert("XSS")</script>',
                'post_content' => 'This post contains potentially malicious content.',
            ],
            [
                'post_title' => "Post with SQL'; DROP TABLE posts; --",
                'post_content' => 'This post contains SQL injection attempt.',
            ],
            [
                'post_title' => 'Post with Special Characters: <>&"\'',
                'post_content' => 'This post contains special characters that might cause issues.',
            ],
        ];

        foreach ($security_posts as $post_data) {
            $post_id = TestDataFactory::create_post($post_data);
            if (!is_wp_error($post_id)) {
                $created['security_posts'][] = $post_id;
            }
        }

        return $created;
    }

    /**
     * Seed accessibility test data
     *
     * @return array Created objects
     */
    private static function seed_accessibility_test_data()
    {
        $created = [];

        // Create posts with accessibility-focused content
        $accessibility_posts = [
            [
                'post_title' => 'Post with Alt Text Images',
                'post_content' => 'This post contains images with proper alt text for accessibility.',
            ],
            [
                'post_title' => 'Post with Headings Structure',
                'post_content' => '<h1>Main Heading</h1><h2>Sub Heading</h2><h3>Sub Sub Heading</h3>',
            ],
            [
                'post_title' => 'Post with Form Elements',
                'post_content' => 'This post contains form elements for accessibility testing.',
            ],
        ];

        foreach ($accessibility_posts as $post_data) {
            $post_id = TestDataFactory::create_post($post_data);
            if (!is_wp_error($post_id)) {
                $created['accessibility_posts'][] = $post_id;
            }
        }

        return $created;
    }

    /**
     * Seed minimal test data
     *
     * @return array Created objects
     */
    private static function seed_minimal_test_data()
    {
        return TestFixtures::create_minimal_setup();
    }

    /**
     * Clear all seeded data
     *
     * @param array $created Created objects
     * @return void
     */
    public static function clear_seeded_data($created)
    {
        TestFixtures::cleanup_fixtures($created);
    }
}
