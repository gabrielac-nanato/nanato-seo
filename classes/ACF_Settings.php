<?php
// phpcs:ignoreFile WordPress.Files.FileName.NotHyphenatedLowercase,WordPress.Files.FileName.InvalidClassFileName
/**
 * ACF Settings & Options
 *
 * This class handles the ACF settings and options for the plugin.
 *
 * @package Nanato_SEO
 */

// Define the namespace.
namespace Nanato_SEO;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Import necessary classes.
use Nanato_SEO\Plugin_Definitions;
use Nanato_SEO\Plugin_Paths;

/**
 * Options class for managing plugin settings and ACF field groups
 */
class ACF_Settings {

	/**
	 * Prefix for use in slugs.
	 *
	 * @var string
	 */
	private $slug_prefix;

	/**
	 * Prefix for use in field names.
	 *
	 * @var string
	 */
	private $name_prefix;

	/**
	 * Constructor
	 *
	 * Registers all hooks when the class is instantiated.
	 */
	public function __construct() {
		$this->slug_prefix = Plugin_Definitions::plugin_prefix();
		$this->name_prefix = str_replace( '-', '_', $this->slug_prefix );

		// Hook into ACF actions and filters.
		add_action( 'acf/include_fields', array( $this, 'register_acf_field_groups' ) );

		// Register options page on ACF initialization.
		add_action( 'acf/init', array( $this, 'register_acf_options_page' ) );

		// Set ACF directories.
		/* 
		add_filter( 'acf/settings/save_json', array( $this, 'acf_json_save_point' ) );
		add_filter( 'acf/settings/load_json', array( $this, 'acf_json_load_point' ) );
		*/
	}

	/**
	 * Register ACF field groups for the plugin
	 */
	public function register_acf_field_groups() {
		// Check if ACF is active.
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		// TODO: Define and register ACF field groups here.
	}

	/**
	 * Register ACF options pages for the plugin
	 */
	public function register_acf_options_page() {
		// Check if ACF is active.
		if ( ! function_exists( 'acf_add_options_page' ) ) {
			return;
		}

		// TODO: Define and register ACF options pages here.

		// TODO: Define and register ACF options sub pages if needed.
		/*
		if ( function_exists( 'acf_add_options_sub_page' ) ) {
			
		}
		*/
	}

	// TODO: Implement additional ACF-related functionality if needed.

	/**
	 * Set ACF save point to theme directory
	 *
	 * @param string $path Default save path.
	 * @return string Theme's acf-json directory path.
	 */
	/*
	public function acf_json_save_point( $path ) {
		return Plugin_Paths::plugin_path() . '/acf-json';
	}
	*/

	/**
	 * Set ACF load point from theme directory
	 *
	 * @param array $paths Default load paths.
	 * @return array Modified paths with theme's acf-json directory.
	 */
	/*
	function acf_json_load_point( $paths ) {
		$paths[] = Plugin_Paths::plugin_path() . '/acf-json';
		return $paths;
	}
	*/
}
