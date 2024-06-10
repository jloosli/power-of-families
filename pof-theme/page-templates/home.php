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
add_action('genesis_after_header', 'pof_home_page_showcase');
add_action('genesis_after_header', 'pof_home_page_cta');

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
?>
	<div id="showcase">
		<h2>We're here to Help You</h2>
		<div class="showcase-list">
			<div class="item">
				<a href="/building-relationships/">
					<img loading="lazy" decoding="async" alt="Father and daughter looking lovingly at each other" src="/wp-content/uploads/2017/11/build-relationships-290x290.jpg" itemprop="image" height="200" width="200">
					<h3 class="item-caption">Build Relationships</h3>
				</a>
			</div>
			<div class="item">
				<a href="/teaching-values/">
					<img loading="lazy" decoding="async" alt="Father and daughter looking lovingly at each other" src="/wp-content/uploads/2017/11/teach-values-image-300x300.jpg" itemprop="image" height="200" width="200">
					<h3 class="item-caption">Teach Values</h3>
				</a>
			</div>
			<div class="item">
				<a href="/systems/">
					<img loading="lazy" decoding="async" alt="Father and son doing dishes together" src="/wp-content/uploads/2017/11/establish-systems-image-300x300.jpg" itemprop="image" height="200" width="200">
					<h3 class="item-caption">Establish Systems</h3>
				</a>
			</div>
			<div class="item">
				<a href="/upcoming-retreats-and-workshops/">
					<img loading="lazy" decoding="async" alt="Group of people sitting in a circle in a discussion" src="/wp-content/uploads/2017/11/get-training-300x300.jpg" itemprop="image" height="200" width="200">
					<h3 class="item-caption">Get Training</h3>
				</a>
			</div>
			<div class="item">
				<a href="/find-answers/">
					<img loading="lazy" decoding="async" alt="Rear view of husband sitting on couch with arm around wife" src="/wp-content/uploads/2017/11/Get-Answers-Image-300x300.jpg" itemprop="image" height="200" width="200">
					<h3 class="item-caption">Find Answers</h3>
				</a>
			</div>
		</div>
	</div>
<?php
}
function pof_home_page_cta()
{
?>
<div id="home-cta">
	<div class="cta-wrapper">
		<h3 class="cta-title">Would you like to see how you're setting your kids up for good behavior and where you can improve?</h3>
		<div class="cta-content">Sign up for our free 5 minute assessment: Your Home Environment</div>
		<div class="cta-button"><a class="button" target="_self" href="https://hm156.infusionsoft.com/app/form/home-environment-submitted?_ga=2.167372087.846642928.1717655143-291391111.1716594697">I'm ready to set my children up for success</a></div>
		<div class="cta-image">
			<img loading="lazy" decoding="async" src="/wp-content/uploads/2017/11/home-environment-assessment.jpg" alt="Father and daughter looking lovingly at each other. Cover for 5 minute home environment assessment">
		</div>
	</div>
</div>

<?php
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
remove_action( 'genesis_before_header', 'genesis_skip_links', 5 );

add_action( 'wp_enqueue_scripts', 'pof_dequeue_skip_links' );
/**
 * Dequeues Skip Links Script.
 *
 * @since 1.0.0
 */
function pof_dequeue_skip_links()
{
	wp_dequeue_script('skip-links');
}


// Runs the Genesis loop.
genesis();
