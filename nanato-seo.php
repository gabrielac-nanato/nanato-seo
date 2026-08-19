<?php
/**
 * Plugin Name: Nanato SEO
 * Description: A plugin for managing SEO tasks, including structured data schemas.
 * Version: 1.0.0
 * Author: gabrielac-nanato
 * Author URI: https://github.com/gabrielac-nanato
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 *
 * @package Nanato_SEO
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin version.
define( 'NANATO_SEO_VERSION', '1.0.0' );

// Load plugin textdomain for translations.
add_action(
	'plugins_loaded',
	function () {
		load_plugin_textdomain( 'nanato-seo', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
);

// Include Composer's autoload file.
if ( file_exists( plugin_dir_path( __FILE__ ) . 'vendor/autoload.php' ) ) {
	require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
} else {
	// Fallback: manually include the classes.
	require_once plugin_dir_path( __FILE__ ) . 'classes/Plugin_Definitions.php';
	require_once plugin_dir_path( __FILE__ ) . 'classes/Plugin_Paths.php';
	require_once plugin_dir_path( __FILE__ ) . 'classes/ACF_Settings.php';
	require_once plugin_dir_path( __FILE__ ) . 'classes/Hooks.php';
	require_once plugin_dir_path( __FILE__ ) . 'classes/Admin.php';
	require_once plugin_dir_path( __FILE__ ) . 'classes/Noindex_Archive.php';
}

// Include helper functions.
require_once plugin_dir_path( __FILE__ ) . 'helpers/helpers.php';

// Instantiate the classes.
$nanato_seo_classes = array(
	\Nanato_SEO\Plugin_Definitions::class,
	\Nanato_SEO\Plugin_Paths::class,
	\Nanato_SEO\ACF_Settings::class,
	\Nanato_SEO\Hooks::class,
	\Nanato_SEO\Admin::class,
	\Nanato_SEO\Noindex_Archive::class,
);

// Instantiate each class.
foreach ( $nanato_seo_classes as $nanato_seo_class ) {
	new $nanato_seo_class();
}
