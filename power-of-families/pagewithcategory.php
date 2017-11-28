<?php
/**
 * Template Name: Page with Categories
 *
 * @package      BE_Gallery
 * @since        1.0.0
 * @link         https://github.com/billerickson/BE-Gallery
 * @author       Bill Erickson <bill@billerickson.net>
 * @copyright    Copyright (c) 2011, Bill Erickson
 * @license      http://opensource.org/licenses/gpl-2.0.php GNU Public License
 *
 */
 
// Only use pagination on custom loop
remove_action( 'genesis_after_endwhile', 'genesis_posts_nav' );
 
add_action( 'genesis_after_entry_content', 'sk_do_loop' );
/**
 * Outputs a custom loop
 *
 * @global mixed $paged current page number if paginated
 * @return void
 */
function sk_do_loop() {
 	$cat = genesis_get_custom_field('category');
	$cat = get_cat_ID($cat);
	$category = get_category($cat); 
	$cat = $category->slug;
	//echo '<h1>' . $cat . '</h1>';
	echo '<div class="clearfix"></div>' . do_shortcode('[wpv-view name="Category post display" category="' . $cat . '"]');
}
 
genesis();