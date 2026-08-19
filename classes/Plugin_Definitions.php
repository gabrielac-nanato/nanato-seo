<?php
// phpcs:ignoreFile WordPress.Files.FileName.NotHyphenatedLowercase,WordPress.Files.FileName.InvalidClassFileName
/**
 * Plugin Definitions
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
 * Class Plugin_Definitions
 */
class Plugin_Definitions {

	/**
	 * Get the plugin prefix.
	 *
	 * @return string Plugin prefix (kebab-case).
	 */
	public static function plugin_prefix() {
		return 'nanato-seo';
	}

	// TODO: Implement additional plugin definitions if needed.
}
