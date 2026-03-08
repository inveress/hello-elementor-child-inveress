<?php
/**
 * Theme functions and definitions
 *
 * @package HelloElementorChild
 */

/**
 * Load child theme css and optional scripts
 *
 * @return void
 */
function hello_elementor_child_enqueue_scripts() {
	wp_enqueue_style(
		'hello-elementor-child-style',
		get_stylesheet_directory_uri() . '/style.css',
		[
			'hello-elementor-theme-style',
		],
		'1.0.2'
	);
}
add_action( 'wp_enqueue_scripts', 'hello_elementor_child_enqueue_scripts', 20 );



/*** STANDARD MODIFICATIONS ***/


/* Disable XML-RPC */

add_filter( 'xmlrpc_enabled', '__return_false' );


/* Remove dashicons for non-logged users */

function inv_remove_dashicons() {
	if ( ! is_user_logged_in() ) {
		wp_deregister_style( 'dashicons' );
	}
}
add_action( 'wp_enqueue_scripts', 'inv_remove_dashicons' );


/* Remove jQuery Migrate */

function inv_remove_jquery_migrate( $scripts ) {
	if ( ! is_admin() && isset( $scripts->registered['jquery'] ) ) {
		$script = $scripts->registered['jquery'];
		if ( $script->deps ) {
			$script->deps = array_diff( $script->deps, [ 'jquery-migrate' ] );
		}
	}
}
add_action( 'wp_default_scripts', 'inv_remove_jquery_migrate' );


/* Add excerpt support to pages */

add_post_type_support( 'page', 'excerpt' );


/* Disable scaled image size > 2560px */
// add_filter( 'big_image_size_threshold', '__return_false' );


/* Image size changes */

// Add custom size
add_action( 'after_setup_theme', function() {
	add_image_size( 'grid', 600, 400, [ 'center', 'center' ] );
});

// Remove unnecessary sizes
add_filter( 'intermediate_image_sizes_advanced', function( $sizes ) {
	//if ( ! class_exists( 'WooCommerce' ) ) {
		unset( $sizes['1536x1536'] );
		unset( $sizes['2048x2048'] );
	//}
	return $sizes;
});

// Add custom sizes to media library menus
add_filter( 'image_size_names_choose', function( $sizes ) {
	return array_merge( $sizes, [
		'grid' => __( 'Grid', 'hello-elementor-child' ),
	]);
});


/* Disable Gutenburg editor */

// OLD: add_filter( 'use_block_editor_for_post', '__return_false', 10 );

add_filter( 'use_block_editor_for_post_type', function( $use, $post_type ) {
	if ( $post_type === 'page' ) {
		return false;
	}
	return $use;
}, 10, 2 );


/* Remove block editor code files */

function inv_remove_block_editor_scripts() {
	wp_dequeue_style( 'wp-block-library' ); // Core
	wp_dequeue_style( 'wp-block-library-theme' ); // Core
	wp_dequeue_style( 'wc-blocks-style' ); // WooCommerce
}
add_action( 'wp_enqueue_scripts', 'inv_remove_block_editor_scripts', 200 );


/* Disable block widget editor */

function inv_remove_block_widget_editor() {
	remove_theme_support( 'widgets-block-editor' );
}
add_action( 'after_setup_theme', 'inv_remove_block_widget_editor' );


/* Disable Windows Live Writer */

remove_action( 'wp_head', 'wlwmanifest_link' );


/* Disable emojis */

remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
remove_action( 'admin_print_styles', 'print_emoji_styles' );


/* Removing emojis s.w.org DNS prefetch */

add_filter( 'wp_resource_hints', function( array $urls, string $relation ): array {
	if( $relation !== 'dns-prefetch' ) {
		return $urls;
	}
	$urls = array_filter( $urls, function( string $url ): bool {
		return strpos( $url, 's.w.org' ) === false;
	});
	return $urls;
}, 10, 2);


/* Disable RSD endpoint */

remove_action( 'wp_head', 'rsd_link' );


/* Remove shortlink */

remove_action( 'wp_head', 'wp_shortlink_wp_head', 10, 0 );


/* Disable WP version output */

remove_action( 'wp_head', 'wp_generator' );
add_filter( 'the_generator', '__return_null' );


/* Disable embeds */

function speed_stop_loading_wp_embed() {
	if( !is_admin() ) {
		wp_deregister_script('wp-embed');
	}
}
add_action( 'init', 'speed_stop_loading_wp_embed' );


/* Disable comments */

function comments_clean_header_hook() {
	wp_deregister_script( 'comment-reply' );
}
add_action( 'init', 'comments_clean_header_hook' );


/* Disable RSS feed links */

function itsme_disable_feed() {
	wp_die( __( 'Nothing here! Please go back to the <a href="'. esc_url( home_url( '/' ) ) .'">homepage</a>!' ) );
}
if ( ! class_exists( 'WooCommerce' ) ) {
	// If not a WooCommerce site -- change for alternate eCommerce solutions
	add_action( 'do_feed', 'itsme_disable_feed', 1 );
	add_action( 'do_feed_rdf', 'itsme_disable_feed', 1 );
	add_action( 'do_feed_rss', 'itsme_disable_feed', 1 );
	add_action( 'do_feed_rss2', 'itsme_disable_feed', 1 );
	add_action( 'do_feed_atom', 'itsme_disable_feed', 1 );
}
add_action( 'do_feed_rss2_comments', 'itsme_disable_feed', 1 );
add_action( 'do_feed_atom_comments', 'itsme_disable_feed', 1 );
remove_action( 'wp_head', 'feed_links_extra', 3 );
remove_action( 'wp_head', 'feed_links', 2 );


/* Optimise cart fragments for WooCommerce sites */

add_filter( 'woocommerce_cart_fragments_refresh_on_load', '__return_false' );

add_action( 'wp_footer', function() {

	if ( is_admin() || ! class_exists( 'WooCommerce' ) ) {
		return;
	}
	?>

	<script>
	(function($){
		// Refresh fragments after AJAX add/remove
		$(document.body).on('added_to_cart removed_from_cart', function(){
			if (typeof wc_cart_fragments_params !== 'undefined') {
				$(document.body).trigger('wc_fragment_refresh');
			}
		});
		// If cart cookie exists, refresh once on load
		$(function(){
			if (document.cookie.indexOf('woocommerce_items_in_cart') !== -1) {
				if (typeof wc_cart_fragments_params !== 'undefined') {
					$(document.body).trigger('wc_fragment_refresh');
				}
			}
		});
	})(jQuery);
	</script>

	<?php
}, 100 );


/* Add basic security headers */

function inv_security_headers() {
	header( 'X-Content-Type-Options: nosniff' );
	header( 'X-Frame-Options: SAMEORIGIN' );
	header( 'Referrer-Policy: strict-origin-when-cross-origin' );
}
add_action( 'send_headers', 'inv_security_headers' );



/*** OPTIONAL MODIFICATIONS - GENERAL ***/


/* Remove duplicated robots.txt meta tag if needed */
// remove_filter( 'wp_robots', 'wp_robots_max_image_preview_large' );


/* Add custom image size, centre crop, and make selectable */
/*
add_image_size( 'news-post-size', '1200', '800', [ "center", "center"] );
add_image_size( 'halfwide-content-size', '800', '800', [ "center", "center"] );
function inv_custom_sizes( $sizes ) {
	return array_merge( $sizes, array(
		'news-post-size' => __( 'News Post Image Size' ),
		'halfwide-content-size' => __( 'Half-Wide Content Image Size' ),
    ) );
}
add_filter( 'image_size_names_choose', 'inv_custom_sizes' );
*/



/*** OPTIONAL MODIFICATIONS - ELEMENTOR ***/


/* Remove fontawesome */

// add_filter( 'elementor/icons_manager/additional_tabs', '__return_empty_array' );


/* Native lazy-loading override */

add_filter( 'wp_lazy_loading_enabled', function( $default, $tag_name, $context ) {
	if ( 'img' === $tag_name && 'the_content' === $context ) {
		return false;
	}
	return $default;
}, 10, 3 );



/*** ADDITIONAL CUSTOM CODE ***/
