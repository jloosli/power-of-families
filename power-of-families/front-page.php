<?php
/* Template Name: Front Page */

add_action('genesis_after_header', function () {
    power_of_families_hero_block();
    power_of_families_main_category_widgets();
    power_of_families_home_cta();
});

function power_of_families_hero_block()
{
    $hero_items = array(
        array(
            'title' => 'Great families don&rsquo;t just happen. They are built.',
            'content' => "https://poweroffamilies.com/wp-content/uploads/2017/11/family.jpg"
        ),
        array(
            'title' => 'Ready to set your family up for more peace, order, and joy?',
            'content' => 'https://poweroffamilies.com/wp-content/uploads/2017/11/parents-helping-children-with-homework-at-kitchen-table-picture-id638922898.jpg',
        )
    );
?>
    <div class="hero-block">
        <?php foreach ($hero_items as $item) : ?>
            <div class="hero-item" style="background-image: url('<?php echo esc_url($item['content']); ?>');">
                <h2><?php echo esc_html($item['title']); ?></h2>
            </div>
        <?php endforeach; ?>
    </div>
<?php
}

function power_of_families_main_category_widgets()
{
    $categories_and_links = array(
        array(
            'title' => 'Build Relationships',
            'link' => '/building-relationships/',
            'img' => '/wp-content/uploads/2017/11/build-relationships-290x290.jpg'
        ),
        array(
            'title' => 'Teach Values',
            'link' => '/teaching-values/',
            'img' => '/wp-content/uploads/2017/11/teach-values-image-290x290.jpg'
        ),
        array(
            'title' => 'Establish Systems',
            'link' => '/systems/',
            'img' => '/wp-content/uploads/2017/11/establish-systems-image-290x290.jpg'
        ),
        array(
            'title' => 'Get Training',
            'link' => '/upcoming-retreats-and-workshops/',
            'img' => '/wp-content/uploads/2017/11/get-training-290x290.jpg'
        ),
        array(
            'title' => 'Find Answers',
            'link' => '/find-answers/',
            'img' => '/wp-content/uploads/2017/11/Get-Answers-Image-290x290.jpg'
        )
    );
?>
    <div class="main-category-widgets">
        <h2>We're here to help you</h2>
        <ul>
            <?php foreach ($categories_and_links as $category) : ?>
                <li>
                    <div class="category-widget">
                        <img src="<?php echo esc_url($category['img']); ?>" alt="<?php echo esc_attr($category['title']); ?>" />
                        <a href="<?php echo esc_url($category['link']); ?>">
                            <?php echo esc_html($category['title']); ?>
                        </a>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php
}

function power_of_families_home_cta()
{
?>
    <div class="home-cta">
        <div class="cta-main">
            <h4>Would you like to see how you&rsquo;re setting your kids up for good behavior and where you can improve?</h4>
            <p>Sign up for our free 5-minute assessment: <em>Your Home Environment</em></p>
            <a href="https://sibforms.com/serve/MUIFADzIwAKC7lCxiA0_8Y38donGmB6G1QYpA9hTduVI5bBD_YxK2Rkgntjv2CU8LTtQbxWvJg783OGy6Ft0Ss2urtacm2y29SN4lVLWw9E-boeHkVk8JB8BzhXPfRlUyTN_og243mGKm0J8m4MgO8EZE5ECpjv_VN-ja8VvdJ1JTI1I-B4vmEi2RlAvChSFq6nESKq1_M72655I/" class="button">I'm ready to set my children up for success</a>
        </div>
        <div class="cta-img"><img src="https://poweroffamilies.com/wp-content/uploads/2017/11/home-environment-assessment-212x300.jpg" alt="5 minute assessment asking if you are setting your kids up for positive behavior" /></div>
    </div>
<?php
}

genesis();
