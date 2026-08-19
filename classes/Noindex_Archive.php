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

		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'wp_head', array( $this, 'add_noindex_meta' ) );
	}

	/**
	 * Get the plugin options, migrating from the legacy option name once if
	 * the new option has never been set.
	 *
	 * @return array Noindex archive settings.
	 */
	private function get_options() {
		$options = get_option( self::OPTION_NAME, null );

		if ( null === $options ) {
			$options = get_option( self::LEGACY_OPTION_NAME, array() );
			update_option( self::OPTION_NAME, $options );
		}

		return $options;
	}

	/**
	 * Add the settings page under Settings.
	 *
	 * @return void
	 */
	public function add_admin_menu() {
		add_options_page(
			__( 'Noindex Archive Settings', 'nanato-seo' ),
			__( 'Noindex Tag', 'nanato-seo' ),
			'manage_options',
			'nanato-seo-noindex-archive',
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
			<h1><?php esc_html_e( 'Noindex Archive Settings', 'nanato-seo' ); ?></h1>
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
