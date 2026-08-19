<?php
// phpcs:ignoreFile WordPress.Files.FileName.NotHyphenatedLowercase,WordPress.Files.FileName.InvalidClassFileName
/**
 * Plugin Paths Class
 *
 * Provides methods to retrieve plugin URL and path.
 *
 * @package Nanato_SEO
 */

// Define the namespace.
namespace Nanato_SEO;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Plugin_Paths handles retrieval of plugin paths and URLs.
 */
class Plugin_Paths {

	/**
	 * Get the plugin URL.
	 *
	 * @return string Plugin URL.
	 */
	public static function plugin_url() {
		return plugin_dir_url( __DIR__ );
	}

	/**
	 * Get the plugin path.
	 *
	 * @return string Plugin path.
	 */
	public static function plugin_path() {
		return plugin_dir_path( __DIR__ );
	}
}
