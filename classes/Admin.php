<?php
/**
 * Admin Customizations
 *
 * @package Nanato_SEO
 */

// Define the namespace
namespace Nanato_SEO;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Import necessary classes
use Nanato_SEO\Plugin_Definitions;
use Nanato_SEO\Plugin_Paths;

/**
 * Admin Class
 *
 * Handles admin dashboard customizations.
 */
class Admin {

	/**
	 * Constructor
	 *
	 * Registers all admin hooks when the class is instantiated.
	 */
	public function __construct() {
		$this->register_hooks();
	}

	/**
	 * Register all admin hooks
	 *
	 * @return void
	 */
	private function register_hooks() {

	}

}
