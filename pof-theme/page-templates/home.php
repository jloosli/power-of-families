<?php

/**
 * Home page Template
 * 
 * Template Name: Home
 *
 */

/**
 * Home Page Filters and Actions
 */

add_filter('genesis_attr_site-inner', 'pof_attr_site_inner');
// Use either 'genesis_after_header' or 'genesis_before_content_sidebar_wrap'
add_action('genesis_after_header', 'pof_home_page_slider');
add_action('genesis_before_content_sidebar_wrap', 'pof_home_page_showcase');
add_action('genesis_before_content_sidebar_wrap', 'pof_home_page_cta');

function pof_home_page_slider()
{
?>
	<div id="home-page-slider">
		<div class="slider-slide">
			<div class="slider-message">Ready to set your family up for more peace, order, and joy?</div>
			<img class="slider-image" src="https://poweroffamilies.com/wp-content/uploads/2017/11/parents-helping-children-with-homework-at-kitchen-table-picture-id638922898.jpg" alt="Parents helping children with homework at kitchen table">
		</div>
		<div class="slider-slide fade-out">
			<img class="slider-image" src="https://poweroffamilies.com/wp-content/uploads/2017/11/family.jpg" alt="Landscape picture of large extended family" class="slider-image">
			<div class="slider-message">Great families don't just happen. They are built.</div>
		</div>
	</div>
	<script>
		const slider = document.getElementById('home-page-slider');
		const slides = slider.querySelectorAll('.slider-slide');
		let currentSlideIndex = 0;
		const slideTransitionms = 20_000;
		const transitionDurationms = parseInt(getComputedStyle(slider).getPropertyValue('--transition-duration')) || 500;

		function fadeOutCurrentSlide() {
			slides[currentSlideIndex].classList.remove('fade-in');
			slides[currentSlideIndex].classList.add('fade-out');
		}

		function fadeInNextSlide() {
			currentSlideIndex = (currentSlideIndex + 1) % slides.length;
			slides[currentSlideIndex].classList.remove('fade-out');
			slides[currentSlideIndex].classList.add('fade-in');
		}

		setInterval(() => {
			fadeOutCurrentSlide();
			setTimeout(fadeInNextSlide, transitionDurationms);
		}, slideTransitionms);
	</script>
<?php
}
function pof_home_page_showcase()
{
	echo '<div class="home-page-content-above-the-fold"><h1>Hello World (Showcase)</h1></div>';
}
function pof_home_page_cta()
{
	echo '<div class="home-page-content-above-the-fold"><h1>Hello World (CTA)</h1></div>';
}

/**
 * Adds the attributes from 'entry', since this replaces the main entry.
 *
 * @param array $attributes Existing attributes.
 *
 * @return array Amended attributes.
 */
function pof_attr_site_inner($attributes)
{

	// Adds a class of 'full' for styling this .site-inner differently.
	$attributes['class'] .= ' full';

	// Adds an id of 'genesis-content' for accessible skip links.
	$attributes['id'] = 'genesis-content';

	// Adds the attributes from .entry, since this replaces the main entry.
	$attributes = wp_parse_args($attributes, genesis_attributes_entry(array()));

	return $attributes;
}

add_filter('body_class', 'pof_landing_body_class');
/**
 * Adds landing page body class.
 *
 * @since 1.0.0
 *
 * @param array $classes Original body classes.
 * @return array Modified body classes.
 */
function pof_landing_body_class($classes)
{

	$classes[] = 'home-page';
	return $classes;
}

// Removes Skip Links.
// remove_action( 'genesis_before_header', 'genesis_skip_links', 5 );

// add_action( 'wp_enqueue_scripts', 'pof_dequeue_skip_links' );
/**
 * Dequeues Skip Links Script.
 *
 * @since 1.0.0
 */
function pof_dequeue_skip_links()
{
	wp_dequeue_script('skip-links');
}

// Removes site header elements.
// remove_action( 'genesis_header', 'genesis_header_markup_open', 5 );
// remove_action( 'genesis_header', 'genesis_do_header' );
// remove_action( 'genesis_header', 'genesis_header_markup_close', 15 );
// remove_action('genesis_site_title', 'genesis_seo_site_title');
// remove_action('genesis_site_title','');
// remove_action('genesis_entry_header','genesis_entry_header_markup_open',5);
// remove_action('genesis_entry_header','genesis_entry_header_markup_close',15);

// Removes navigation.
// remove_theme_support( 'genesis-menus' );

// Removes site footer elements.
// remove_action( 'genesis_footer', 'genesis_footer_markup_open', 5 );
// remove_action( 'genesis_footer', 'genesis_do_footer' );
// remove_action( 'genesis_footer', 'genesis_footer_markup_close', 15 );

// remove_action( 'genesis_entry_header', 'genesis_do_post_title' );

// Runs the Genesis loop.
genesis();
