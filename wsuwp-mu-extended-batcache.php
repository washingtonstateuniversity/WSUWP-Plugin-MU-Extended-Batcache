<?php
/*
Plugin Name: WSU MU Extended Batcache
Plugin URI: https://github.com/washingtonstateuniversity/WSUWP-Plugin-MU-Extended-Batcache
Description: A fork and extension of the batcache.php file included with Batcache.
Author: washingtonstateuniversity, jeremyfelt
Author URI: https://web.wsu.edu
Version: 2.0.0
*/

// Do not load if our advanced-cache.php isn't loaded
if ( ! isset( $batcache ) || ! is_object( $batcache ) || ! method_exists( $wp_object_cache, 'incr' ) ) {
	return;
}

$batcache->configure_groups();

add_action( 'clean_post_cache', 'batcache_post', 10, 2 );
/**
 * Regenerates a set of page caches when post cache is cleared.
 *
 * @since Batcache
 * @since 2.0.0 Updated to clear a global rest-api URL key.
 *
 * @param $post_id
 * @param $post
 */
function batcache_post( $post_id, $post ) {
	if ( 'revision' === $post->post_type || ! in_array( get_post_status( $post_id ), array( 'publish', 'trash' ), true ) ) {
		return;
	}

	$home = trailingslashit( get_option( 'home' ) );

	// Clear home page and feed URLS.
	batcache_clear_url( $home );
	batcache_clear_url( $home . 'feed/' );

	// Clear base URL for the post.
	batcache_clear_url( get_permalink( $post_id ) );

	// Clear the entire REST API page cache.
	batcache_clear_url_group( 'rest-api' );
}

/**
 * Clears a group of URLs page cached with Batcache by incrementing
 * the version of the URL group in object cache.
 *
 * @since 2.0.0
 *
 * @global batcache $batcache
 *
 * @param $group
 * @return bool|false|int
 */
function batcache_clear_url_group( $group ) {
	global $batcache;

	if ( empty( $group ) ) {
		return false;
	}

	$group_key = md5( $group );
	wp_cache_add( "{$group_key}_version", 0, $batcache->group );

	return wp_cache_incr( "{$group_key}_version", 1, $batcache->group );
}

/**
 * Clears a specific URL page cached with Batcache by incrementing
 * the version of the URL in object cache.
 *
 * @since Batcache
 *
 * @param $url
 * @return bool|false|int
 */
function batcache_clear_url( $url ) {
	global $batcache;

	if ( empty( $url ) ) {
		return false;
	}

	if ( 0 === strpos( $url, 'https://' ) ) {
		$url = str_replace( 'https://', 'http://', $url );
	}
	if ( 0 !== strpos( $url, 'http://' ) ) {
		$url = 'http://' . $url;
	}

	$url_key = md5( $url );
	wp_cache_add( "{$url_key}_version", 0, $batcache->group );

	return wp_cache_incr( "{$url_key}_version", 1, $batcache->group );
}
