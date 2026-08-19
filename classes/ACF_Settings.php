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

		$this->register_locations_field_group();
	}

	/**
	 * Register the Locations field group (Layer 1 — global).
	 *
	 * One row per physical office. @id and the branchOf relationship to the
	 * global Organization are computed at render time from this data, not
	 * stored — see CLAUDE.md § Resolved Decisions.
	 *
	 * @return void
	 */
	private function register_locations_field_group() {
		acf_add_local_field_group(
			array(
				'key'      => 'group_' . $this->name_prefix . '_locations',
				'title'    => __( 'Nanato SEO — Locations', 'nanato-seo' ),
				'fields'   => array(
					array(
						'key'          => 'field_' . $this->name_prefix . '_contact_page',
						'label'        => __( 'Contact / Directions Page', 'nanato-seo' ),
						'name'         => $this->name_prefix . '_contact_page',
						'type'         => 'post_object',
						'instructions' => __( 'The page listing the firm\'s office(s). Anchors the @id of any location below that has no dedicated page of its own.', 'nanato-seo' ),
						'post_type'    => array( 'page' ),
						'allow_null'   => 1,
						'multiple'     => 0,
					),
					array(
						'key'          => 'field_' . $this->name_prefix . '_locations',
						'label'        => __( 'Locations', 'nanato-seo' ),
						'name'         => $this->name_prefix . '_locations',
						'type'         => 'repeater',
						'instructions' => __( 'One row per physical office.', 'nanato-seo' ),
						'layout'       => 'block',
						'button_label' => __( 'Add Location', 'nanato-seo' ),
						'sub_fields'   => array(
							array(
								'key'   => 'field_' . $this->name_prefix . '_location_name',
								'label' => __( 'Location Name', 'nanato-seo' ),
								'name'  => 'location_name',
								'type'  => 'text',
							),
							array(
								'key'   => 'field_' . $this->name_prefix . '_location_phone',
								'label' => __( 'Phone', 'nanato-seo' ),
								'name'  => 'phone',
								'type'  => 'text',
							),
							array(
								'key'   => 'field_' . $this->name_prefix . '_location_email',
								'label' => __( 'Email', 'nanato-seo' ),
								'name'  => 'email',
								'type'  => 'email',
							),
							array(
								'key'        => 'field_' . $this->name_prefix . '_location_address',
								'label'      => __( 'Address', 'nanato-seo' ),
								'name'       => 'address',
								'type'       => 'group',
								'sub_fields' => array(
									array(
										'key'   => 'field_' . $this->name_prefix . '_location_address_street',
										'label' => __( 'Street', 'nanato-seo' ),
										'name'  => 'street',
										'type'  => 'text',
									),
									array(
										'key'   => 'field_' . $this->name_prefix . '_location_address_city',
										'label' => __( 'City', 'nanato-seo' ),
										'name'  => 'city',
										'type'  => 'text',
									),
									array(
										'key'   => 'field_' . $this->name_prefix . '_location_address_state',
										'label' => __( 'State', 'nanato-seo' ),
										'name'  => 'state',
										'type'  => 'text',
									),
									array(
										'key'   => 'field_' . $this->name_prefix . '_location_address_postal_code',
										'label' => __( 'Postal Code', 'nanato-seo' ),
										'name'  => 'postal_code',
										'type'  => 'text',
									),
									array(
										'key'   => 'field_' . $this->name_prefix . '_location_address_country',
										'label' => __( 'Country', 'nanato-seo' ),
										'name'  => 'country',
										'type'  => 'text',
									),
								),
							),
							array(
								'key'        => 'field_' . $this->name_prefix . '_location_geo',
								'label'      => __( 'Coordinates', 'nanato-seo' ),
								'name'       => 'geo',
								'type'       => 'group',
								'sub_fields' => array(
									array(
										'key'   => 'field_' . $this->name_prefix . '_location_geo_latitude',
										'label' => __( 'Latitude', 'nanato-seo' ),
										'name'  => 'latitude',
										'type'  => 'text',
									),
									array(
										'key'   => 'field_' . $this->name_prefix . '_location_geo_longitude',
										'label' => __( 'Longitude', 'nanato-seo' ),
										'name'  => 'longitude',
										'type'  => 'text',
									),
								),
							),
							array(
								'key'   => 'field_' . $this->name_prefix . '_location_map_url',
								'label' => __( 'Map URL', 'nanato-seo' ),
								'name'  => 'map_url',
								'type'  => 'url',
							),
							array(
								'key'          => 'field_' . $this->name_prefix . '_location_areas_served',
								'label'        => __( 'Areas Served', 'nanato-seo' ),
								'name'         => 'areas_served',
								'type'         => 'repeater',
								'layout'       => 'table',
								'button_label' => __( 'Add Area', 'nanato-seo' ),
								'sub_fields'   => array(
									array(
										'key'   => 'field_' . $this->name_prefix . '_location_areas_served_area',
										'label' => __( 'Area', 'nanato-seo' ),
										'name'  => 'area',
										'type'  => 'text',
									),
								),
							),
							array(
								'key'          => 'field_' . $this->name_prefix . '_location_page',
								'label'        => __( 'Dedicated Location Page', 'nanato-seo' ),
								'name'         => 'location_page',
								'type'         => 'post_object',
								'instructions' => __( 'Optional. If this office has its own page, select it here — its permalink becomes this location\'s @id instead of the Contact/Directions page anchor.', 'nanato-seo' ),
								'post_type'    => array( 'page' ),
								'allow_null'   => 1,
								'multiple'     => 0,
							),
						),
					),
				),
				'location' => array(
					array(
						array(
							'param'    => 'options_page',
							'operator' => '==',
							'value'    => 'nanato-seo-global-settings',
						),
					),
				),
			)
		);
	}

	/**
	 * Register ACF options pages for the plugin
	 */
	public function register_acf_options_page() {
		// Check if ACF is active.
		if ( ! function_exists( 'acf_add_options_page' ) ) {
			return;
		}

		acf_add_options_page(
			array(
				'page_title'  => __( 'Nanato SEO — Global Settings', 'nanato-seo' ),
				'menu_title'  => __( 'Global Settings', 'nanato-seo' ),
				'menu_slug'   => 'nanato-seo-global-settings',
				'parent_slug' => 'nanato-seo',
				'capability'  => 'manage_options',
			)
		);

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
