<?php
/**
 * Base class for the Replace feature.
 */

namespace StringLocator\Extension\SearchReplace;

/**
 * Replace class.
 */
class Replace {

	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_action( 'string_locator_search_results_tablenav_controls', array( $this, 'add_replace_button' ) );
		add_action( 'string_locator_search_results_tablenav_controls', array( $this, 'output_replace_form' ) );
		add_action( 'string_locator_instawp_tablenav_controls', array( $this, 'add_instawp_stage_button' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ) );

		add_action( 'string_locator_search_templates', array( $this, 'add_replace_response_template' ) );

		add_action( 'wp_ajax_install_activate_plugin', array( $this, 'install_activate_plugin_callback' ) );
	}

	/**
	 * Add error notice template.
	 *
	 * @return void
	 */
	public function add_replace_response_template() {
		require_once STRING_LOCATOR_PLUGIN_DIR . '/includes/Extension/SearchReplace/template/error-notice.php';
	}

	/**
	 * Conditionally register assets on the appropriate pages within wp-admin.
	 *
	 * @param string $hook The hook name for the page being loaded.
	 *
	 * @return void
	 */
	public function maybe_enqueue_assets( $hook ) {
		// Break out early if we are not on a String Locator page
		if ( 'tools_page_string-locator' !== $hook && 'toplevel_page_string-locator' !== $hook ) {
			return;
		}

		$replace = STRING_LOCATOR_PLUGIN_DIR . 'build/string-locator-replace.asset.php';

		$replace = file_exists( $replace ) ? require $replace : array( 'version' => false );

		/**
		 * String Locator Styles and Scripts.
		 */
		wp_enqueue_style( 'string-locator-replace', trailingslashit( STRING_LOCATOR_PLUGIN_URL ) . 'build/string-locator-replace.css', array(), $replace['version'] );
		wp_enqueue_script( 'string-locator-replace', trailingslashit( STRING_LOCATOR_PLUGIN_URL ) . 'build/string-locator-replace.js', array(), $replace['version'], true );

		wp_localize_script(
			'string-locator-replace',
			'stringLocatorReplace',
			array(
				'rest_nonce'    => wp_create_nonce( 'wp_rest' ),
				'replace_nonce' => wp_create_nonce( 'string-locator-replace' ),
				'url'           => array(
					'replace' => get_rest_url( null, 'string-locator/v1/replace' ),
				),
				'string'        => array(
					'replace_started' => __( 'Running replacemenets...', 'string-locator' ),
					'button_show'     => __( 'Show replacement controls', 'string-locator' ),
					'button_hide'     => __( 'Hide replacement controls', 'string-locator' ),
					'confirm_all'     => __( 'Are you sure you want to replace all strings?', 'string-locator' ),
				),
			)
		);

		/**
		 * Instawp installation event handle script
		 * */
		wp_enqueue_script( 'string-locator-instawp', trailingslashit( STRING_LOCATOR_PLUGIN_URL ) . 'build/string-locator-instawp.js', array( 'jquery', 'updates' ), $replace['version'], false );
		wp_localize_script(
			'string-locator-instawp',
			'instawp_activate',
			array( 'nonce' => wp_create_nonce( 'string-locator-activate-instawp' ) )
		);

	}

	/**
	 * Output a toggle button to display the replacement form.
	 *
	 * @return void
	 */
	public function add_replace_button() {
		printf(
			'<button type="button" class="button button-link" id="string-locator-toggle-replace-controls" aria-expanded="false" aria-controls="string-locator-replace-form">%s</button>',
			esc_html__( 'Show replacement controls', 'string-locator' )
		);
	}

	/**
	 * Output a button to display the create staging site.
	 *
	 * @return void
	 */
	public function add_instawp_stage_button() {
		self::install_plugin_button( 'instawp-connect', 'instawp-connect.php', 'InstaWP Connect', array(), __( 'Go to InstaWP &rarr;', 'string-locator' ), __( 'Activate InstaWP', 'string-locator' ), __( 'Create a Staging Site (powered by InstaWP)', 'string-locator' ) );
	}

	public static function install_plugin_button( $plugin_slug, $plugin_file, $plugin_name, $classes = array(), $activated = '', $activate = '', $install = '' ) {
		if ( current_user_can( 'install_plugins' ) && current_user_can( 'activate_plugins' ) ) {
			if ( is_plugin_active( $plugin_slug . '/' . $plugin_file ) ) {
				// The plugin is already active.
				// $instawp_connect = menu_page_url( 'instawp-connect', false );
				$instawp_connect = menu_page_url( 'instawp', false );
				$button          = array(
					'message' => esc_attr__( 'Create a Staging Site', 'string-locator' ),
					'url'     => $instawp_connect,
					'classes' => array( 'string-locator-instawp-button', 'disabled' ),
				);

				if ( '' !== $activated ) {
					$button['message'] = esc_attr( $activated );

					$button['target'] = "onclick=\"window.open('{$instawp_connect}', '_blank');\"";
				}
				$button['logo-img'] = '<span class="btn-logo-inline"><img src="' . esc_url( plugins_url( 'views/assets/instawp-logo.svg', STRING_LOCATOR_PLUGIN_FILE ) ) . '" alt="InstaWP logo"></span>';

			} elseif ( self::is_plugin_installed( $plugin_slug ) ) {
				$url = self::is_plugin_installed( $plugin_slug );

				// The plugin exists but isn't activated yet.
				$button = array(
					'message' => esc_attr__( 'Create a Staging Site', 'string-locator' ),
					// 'url'     => $url,
					'url'     => 'javascript:void 0;',
					'classes' => array( 'instawp-activate-now' ),
				);

				$button['logo-img'] = '<span class="btn-logo-inline"><img src="' . esc_url( plugins_url( 'views/assets/instawp-logo.svg', STRING_LOCATOR_PLUGIN_FILE ) ) . '" alt="InstaWP logo"></span>';
				if ( '' !== $activate ) {
					$button['message'] = esc_attr( $activate );
				}
			} else {
				// The plugin doesn't exist.
				$url    = wp_nonce_url(
					add_query_arg(
						array(
							'action' => 'install-plugin',
							'plugin' => $plugin_slug,
						),
						self_admin_url( 'update.php' )
					),
					'install-plugin_' . $plugin_slug
				);
				$button = array(
					'message' => esc_attr__( 'Create a Staging Site', 'string-locator' ),
					'url'     => $url,
					'classes' => array( 'sl-instawp-install-now', 'install-now', 'install-' . $plugin_slug ),
				);

				if ( '' !== $install ) {
					$button['message'] = esc_attr( $install );
				}

				$button['logo-img'] = '<span class="btn-logo-inline"><img src="' . esc_url( plugins_url( 'views/assets/instawp-logo.svg', STRING_LOCATOR_PLUGIN_FILE ) ) . '" alt="InstaWP logo"></span>';
			}

			if ( ! empty( $classes ) ) {
				$button['classes'] = array_merge( $button['classes'], $classes );
			}

			$button['classes'] = implode( ' ', $button['classes'] );

			?>
			<span class="plugin-card-<?php echo esc_attr( $plugin_slug ); ?>" style="float: right; margin-top: 7px;">
				<?php echo ! empty( $button['logo-img'] ) ? $button['logo-img'] : ''; ?>
				<button href="<?php echo esc_url( $button['url'] ); ?>" class="<?php echo esc_attr( $button['classes'] ); ?>" data-originaltext="<?php echo esc_attr( $button['message'] ); ?>" data-name="<?php echo esc_attr( $plugin_name ); ?>" data-slug="<?php echo esc_attr( $plugin_slug ); ?>" aria-label="<?php echo esc_attr( $button['message'] ); ?>" <?php echo ! empty( $button['target'] ) ? $button['target'] : ''; ?>> 
					<?php echo esc_html( $button['message'] ); ?> 
				</button>
			</span>
			<?php
		}

	}

	/**
	 * Handle Ajax call to activate the function
	 *
	 */
	public function install_activate_plugin_callback() {
		// for instawp-connect plugin only
		$plugin_slug = 'instawp-connect';

		$plugin_slug_file = $plugin_slug . '/' . $plugin_slug . '.php';

		// Verify the nonce
		if ( ! check_ajax_referer( 'string-locator-activate-instawp', 'nonce', false ) ) {
			wp_send_json_error( __( 'Invalid nonce.', 'string-locator' ) );
		}

		// Check if the current user has the required capability
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( __( 'You do not have permission to perform this action.', 'string-locator' ) );
		}

		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		$result = activate_plugin( $plugin_slug_file );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		$instawp_link = menu_page_url( 'instawp', false );
		if ( empty( $instawp_link ) ) {
			$instawp_link = admin_url( 'tools.php?page=instawp' );
		}

		$response = array(
			'message'     => __( 'Plugin installed and activated successfully.', 'string-locator' ),
			'href'        => $instawp_link,
			'anchor_text' => __( 'Go to InstaWP →', 'string-locator' ),
		);

		wp_send_json_success( $response );
		wp_die();
	}

	/**
	 * Check if a plugin is installed and return the url to activate it if so.
	 *
	 * @param string $plugin_slug The plugin slug.
	 */
	private static function is_plugin_installed( $plugin_slug ) {
		if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin_slug ) ) {
			$plugins = get_plugins( '/' . $plugin_slug );
			if ( ! empty( $plugins ) ) {
				$keys        = array_keys( $plugins );
				$plugin_file = $plugin_slug . '/' . $keys[0];
				$url         = wp_nonce_url(
					add_query_arg(
						array(
							'action' => 'activate',
							'plugin' => $plugin_file,
						),
						admin_url( 'plugins.php' )
					),
					'activate-plugin_' . $plugin_file
				);
				return $url;
			}
		}
		return false;
	}

	/**
	 * Output the replacement form.
	 *
	 * @return void
	 */
	public function output_replace_form() {
		$instawp_plugin = 'instawp-connect';
		include_once __DIR__ . '/views/replace-form.php';
	}

}

new Replace();
