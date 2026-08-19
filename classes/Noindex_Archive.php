<?php
// phpcs:ignoreFile WordPress.Files.FileName.NotHyphenatedLowercase,WordPress.Files.FileName.InvalidClassFileName
/**
 * Noindex Archive Pages
 *
 * Adds a noindex meta tag to archive pages (category, tag, author, date)
 * per site settings. Folded in from the standalone Noindex Archive Pages
 * plugin.
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
 * Noindex_Archive class
 *
 * Manages settings and frontend output for noindexing archive pages.
 */
class Noindex_Archive {

	/**
	 * Option name storing the noindex settings.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'nanato_seo_noindex_archive_options';

	/**
	 * Legacy option name used by the standalone Noindex Archive Pages plugin.
	 * Read once to migrate existing settings; never written to again.
	 *
	 * @var string
	 */
	const LEGACY_OPTION_NAME = 'archive_noindex_options';

	/**
	 * Action name for the reset-to-defaults form submission.
	 *
	 * @var string
	 */
	const RESET_ACTION = 'nanato_seo_reset_noindex_archive';

	/**
	 * Plugin options.
	 *
	 * @var array
	 */
	private $options;

	/**
	 * Constructor
	 *
	 * Loads (and migrates, if needed) options, then registers hooks.
	 */
	public function __construct() {
		$this->options = $this->get_options();

		// Late priority so this always registers after Global Settings (ACF's
		// options page, added via the default admin_menu priority) — keeps
		// this secondary, low-configuration feature last in the submenu.
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 20 );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_post_' . self::RESET_ACTION, array( $this, 'handle_reset' ) );
		add_action( 'wp_head', array( $this, 'add_noindex_meta' ) );
	}

	/**
	 * Get the plugin options, migrating from the legacy option name once if
	 * the new option has never been set. On a fresh install with no legacy
	 * option either, archive noindexing defaults to on for every archive type.
	 *
	 * @return array Noindex archive settings.
	 */
	private function get_options() {
		$options = get_option( self::OPTION_NAME, null );

		if ( null === $options ) {
			$legacy_options = get_option( self::LEGACY_OPTION_NAME, false );
			$options        = false !== $legacy_options ? $legacy_options : $this->default_options();
			update_option( self::OPTION_NAME, $options );
		}

		return $options;
	}

	/**
	 * Default settings for a fresh install (no prior plugin ever configured).
	 *
	 * @return array Default noindex archive settings.
	 */
	private function default_options() {
		return array(
			'category' => 1,
			'tag'      => 1,
			'author'   => 1,
			'date'     => 1,
		);
	}

	/**
	 * Add the Nanato SEO top-level admin menu page.
	 *
	 * @return void
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'Nanato SEO', 'nanato-seo' ),
			__( 'Nanato SEO', 'nanato-seo' ),
			'manage_options',
			'nanato-seo',
			array( $this, 'options_page' ),
			'dashicons-search',
			3 // Between Dashboard (2) and the separator above Posts (4).
		);

		// Relabel the auto-duplicated first submenu entry; without this it
		// would just repeat "Nanato SEO" now that a second page (Global
		// Settings) exists under the same top-level menu.
		add_submenu_page(
			'nanato-seo',
			__( 'Noindex Archive Settings', 'nanato-seo' ),
			__( 'Noindex Archive', 'nanato-seo' ),
			'manage_options',
			'nanato-seo',
			array( $this, 'options_page' )
		);
	}

	/**
	 * Register the plugin setting.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting( 'nanato_seo_noindex_archive', self::OPTION_NAME );
	}

	/**
	 * Reset this plugin's noindex archive options back to defaults.
	 * The legacy plugin's option is left untouched.
	 *
	 * @return void
	 */
	public function handle_reset() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'nanato-seo' ) );
		}

		check_admin_referer( self::RESET_ACTION );

		update_option( self::OPTION_NAME, $this->default_options() );

		wp_safe_redirect( add_query_arg( 'nanato-seo-reset', '1', admin_url( 'admin.php?page=nanato-seo' ) ) );
		exit;
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function options_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Nanato SEO', 'nanato-seo' ); ?></h1>
			<h2><?php esc_html_e( 'Noindex Archive Settings', 'nanato-seo' ); ?></h2>
			<?php if ( isset( $_GET['nanato-seo-reset'] ) ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Noindex archive settings have been reset to defaults.', 'nanato-seo' ); ?></p>
				</div>
			<?php endif; ?>
			<form method="post" action="options.php">
				<?php settings_fields( 'nanato_seo_noindex_archive' ); ?>
				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Apply noindex to:', 'nanato-seo' ); ?></th>
						<td>
							<fieldset>
								<label>
									<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[category]" value="1" <?php checked( ! empty( $this->options['category'] ) ); ?>>
									<?php esc_html_e( 'Category Archives', 'nanato-seo' ); ?>
								</label><br>
								<label>
									<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[tag]" value="1" <?php checked( ! empty( $this->options['tag'] ) ); ?>>
									<?php esc_html_e( 'Tag Archives', 'nanato-seo' ); ?>
								</label><br>
								<label>
									<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[author]" value="1" <?php checked( ! empty( $this->options['author'] ) ); ?>>
									<?php esc_html_e( 'Author Archives', 'nanato-seo' ); ?>
								</label><br>
								<label>
									<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[date]" value="1" <?php checked( ! empty( $this->options['date'] ) ); ?>>
									<?php esc_html_e( 'Date Archives', 'nanato-seo' ); ?>
								</label>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Pagination Settings:', 'nanato-seo' ); ?></th>
						<td>
							<fieldset>
								<label>
									<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[paginated_only]" value="1" <?php checked( ! empty( $this->options['paginated_only'] ) ); ?>>
									<?php esc_html_e( 'Only noindex paginated pages (page 2 and beyond)', 'nanato-seo' ); ?>
								</label>
							</fieldset>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>

			<hr />

			<h2><?php esc_html_e( 'Reset', 'nanato-seo' ); ?></h2>
			<p><?php esc_html_e( 'Clear the settings created by this plugin and restore the defaults above.', 'nanato-seo' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( self::RESET_ACTION ); ?>
				<input type="hidden" name="action" value="<?php echo esc_attr( self::RESET_ACTION ); ?>">
				<?php submit_button( __( 'Reset to Defaults', 'nanato-seo' ), 'delete' ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Output a noindex meta tag on archive pages per the configured settings.
	 *
	 * @return void
	 */
	public function add_noindex_meta() {
		$targets_archive = ( is_category() && ! empty( $this->options['category'] ) )
			|| ( is_tag() && ! empty( $this->options['tag'] ) )
			|| ( is_author() && ! empty( $this->options['author'] ) )
			|| ( is_date() && ! empty( $this->options['date'] ) );

		if ( ! $targets_archive ) {
			return;
		}

		$paged = get_query_var( 'paged' ) ? (int) get_query_var( 'paged' ) : 1;

		if ( ! empty( $this->options['paginated_only'] ) && $paged <= 1 ) {
			return;
		}

		echo '<meta name="robots" content="noindex, follow">' . "\n";
	}
}
