<?php
/**
 * The template for displaying Home Page.
 *
 * @package WordPress
 * @subpackage Fruitful theme
 * @since Fruitful theme 2.0
 */

get_header(); ?>

	<?php fruitful_get_content_with_custom_sidebar('homepage'); ?>
		
<?php get_footer(); ?>
