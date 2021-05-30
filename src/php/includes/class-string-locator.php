<?php

namespace JITS\StringLocator;

/**
 * Class String_Locator
 */
class String_Locator {
	/**
	 * The code language used for the editing page.
	 *
	 * @var string
	 */
	public $string_locator_language = '';

	/**
	 * String Locator version number.
	 *
	 * @var string
	 */
	public $version = '2.4.2';

	/**
	 * An array containing all notices to display.
	 *
	 * @var array
	 */
	public $notice = array();

	/**
	 * Construct the plugin
	 */
	function __construct() {
		$this->init();
	}

	/**
	 * The plugin initialization, ready as a stand alone function so it can be instantiated in other
	 * scenarios as well.
	 *
	 * @since 2.2.0
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'admin_body_class', array( $this, 'admin_body_class' ) );

		add_action( 'admin_menu', array( $this, 'populate_menu' ) );
		add_action( 'network_admin_menu', array( $this, 'populate_network_menu' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 11 );

		add_action( 'plugins_loaded', array( $this, 'load_i18n' ) );

		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );

		add_filter( 'string_locator_search_sources_markup', array( $this, 'add_search_options' ), 10, 2 );
	}

	public function add_search_options( $searchers, $search_location ) {
		ob_start();
		?>
		<optgroup label="<?php esc_attr_e( 'Core', 'string-locator' ); ?>">
				<option value="core"><?php esc_html_e( 'The whole WordPress directory', 'string-locator' ); ?></option>
		<option value="wp-content"><?php esc_html_e( 'Everything under wp-content', 'string-locator' ); ?></option>
		</optgroup>
		<optgroup label="<?php esc_attr_e( 'Themes', 'string-locator' ); ?>">
			<?php echo String_Locator::get_themes_options( $search_location ); ?>
		</optgroup>
		<?php if ( String_Locator::has_mu_plugins() ) : ?>
			<optgroup label="<?php esc_attr_e( 'Must Use Plugins', 'string-locator' ); ?>">
				<?php echo String_Locator::get_mu_plugins_options( $search_location ); ?>
			</optgroup>
		<?php endif; ?>
		<optgroup label="<?php esc_attr_e( 'Plugins', 'string-locator' ); ?>">
			<?php echo String_Locator::get_plugins_options( $search_location ); ?>
		</optgroup>
		<?php

		$searchers .= ob_get_clean();

		return $searchers;
	}

	/**
	 * Add a donation link to the plugins page.
	 *
	 * @param array $meta An array of meta links for this plugin.
	 * @param string $plugin_file The main plugin file name, used to identify our own plugin.
	 *
	 * @return array
	 */
	function plugin_row_meta( $meta, $plugin_file ) {
		if ( 'string-locator/string-locator.php' === $plugin_file ) {
			$meta[] = sprintf(
				'<a href="https://www.paypal.me/clorith">%s</a>',
				esc_html__( 'Donate to this plugin', 'string-locator' )
			);
		}

		return $meta;
	}

	/**
	 * Create a set of drop-down options for picking one of the available themes.
	 *
	 * @param string $current The current selection option to match against.
	 *
	 * @return string
	 */
	public static function get_themes_options( $current = null ) {
		$options = sprintf(
			'<option value="%s" %s>&mdash; %s &mdash;</option>',
			't--',
			( 't--' === $current ? 'selected="selected"' : '' ),
			esc_html( __( 'All themes', 'string-locator' ) )
		);

		$string_locate_themes = wp_get_themes();

		foreach ( $string_locate_themes as $string_locate_theme_slug => $string_locate_theme ) {
			$string_locate_theme_data = wp_get_theme( $string_locate_theme_slug );
			$string_locate_value      = 't-' . $string_locate_theme_slug;

			$options .= sprintf(
				'<option value="%s" %s>%s</option>',
				$string_locate_value,
				( $current === $string_locate_value ? 'selected="selected"' : '' ),
				$string_locate_theme_data->Name // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			);
		}

		return $options;
	}

	public static function get_edit_form_url() {
		$url_query = String_Locator::edit_form_fields();

		return admin_url(
			sprintf(
				'tools.php?%s',
				build_query( $url_query )
			)
		);
	}

	public static function edit_form_fields( $echo = false ) {
		$fields = array(
			'page'                => ( isset( $_GET['page'] ) ? $_GET['page'] : '' ),
			'edit-file'           => ( isset( $_GET['edit-file'] ) ? $_GET['edit-file'] : '' ),
			'file-reference'      => ( isset( $_GET['file-reference'] ) ? $_GET['file-reference'] : '' ),
			'file-type'           => ( isset( $_GET['file-type'] ) ? $_GET['file-type'] : '' ),
			'string-locator-line' => ( isset( $_GET['string-locator-line'] ) ? $_GET['string-locator-line'] : '' ),
			'string-locator-path' => ( isset( $_GET['string-locator-path'] ) ? $_GET['string-locator-path'] : '' ),
		);

		$fields = apply_filters( 'string_locator_editor_fields', $fields );

		$field_output = array();

		foreach ( $fields as $label => $value ) {
			$field_output[] = sprintf(
				'<input type="hidden" name="%s" value="%s">',
				esc_attr( $label ),
				esc_attr( $value )
			);
		}

		if ( $echo ) {
			echo implode( "\n", $field_output );
		}

		return $field_output;
	}

	/**
	 * Create a set of drop-down options for picking one of the available plugins.
	 *
	 * @param string $current The current selection option to match against.
	 *
	 * @return string
	 */
	public static function get_plugins_options( $current = null ) {
		$options = sprintf(
			'<option value="%s" %s>&mdash; %s &mdash;</option>',
			'p--',
			( 'p--' === $current ? 'selected="selected"' : '' ),
			esc_html( __( 'All plugins', 'string-locator' ) )
		);

		$string_locate_plugins = get_plugins();

		foreach ( $string_locate_plugins as $string_locate_plugin_path => $string_locate_plugin ) {
			$string_locate_value = 'p-' . $string_locate_plugin_path;

			$options .= sprintf(
				'<option value="%s" %s>%s</option>',
				$string_locate_value,
				( $current === $string_locate_value ? 'selected="selected"' : '' ),
				$string_locate_plugin['Name']
			);
		}

		return $options;
	}

	/**
	 * Create a set of drop-down options for picking one of the available must-use plugins.
	 *
	 * @param string $current The current selection option to match against.
	 *
	 * @return string
	 */
	public static function get_mu_plugins_options( $current = null ) {
		$options = sprintf(
			'<option value="%s" %s>&mdash; %s &mdash;</option>',
			'mup--',
			( 'mup--' === $current ? 'selected="selected"' : '' ),
			esc_html__( 'All must-use plugins', 'string-locator' )
		);

		$string_locate_plugins = get_mu_plugins();

		foreach ( $string_locate_plugins as $string_locate_plugin_path => $string_locate_plugin ) {
			$string_locate_value = 'mup-' . $string_locate_plugin_path;

			$options .= sprintf(
				'<option value="%s" %s>%s</option>',
				$string_locate_value,
				( $current === $string_locate_value ? 'selected="selected"' : '' ),
				$string_locate_plugin['Name']
			);
		}

		return $options;
	}

	/**
	 * Check if there are Must-Use plugins available on this WordPress install.
	 *
	 * @since 2.2.0
	 *
	 * @return bool
	 */
	public static function has_mu_plugins() {
		$mu_plugin_count = get_mu_plugins();

		if ( count( $mu_plugin_count ) >= 1 ) {
			return true;
		}

		return false;
	}

	/**
	 * Create a table row for insertion into the search results list.
	 *
	 * @param array|object $item The table row item.
	 *
	 * @return string
	 */
	public static function prepare_table_row( $item ) {
		if ( ! is_object( $item ) ) {
			$item = (object) $item;
		}

		return sprintf(
			'<tr>
                <td>
                	%s
                	<div class="row-actions">
                		%s
                    </div>
                </td>
                <td>
                	%s
                </td>
                <td>
                	%d
                </td>
                <td>
                	%d
                </td>
            </tr>',
			$item->stringresult,
			( ! current_user_can( 'edit_themes' ) ? '' : sprintf(
				'<span class="edit"><a href="%1$s" aria-label="%2$s">%2$s</a></span>',
				esc_url( $item->editurl ),
				// translators: The row-action edit link label.
				esc_html__( 'Edit', 'string-locator' )
			) ),
			( ! current_user_can( 'edit_themes' ) ? $item->filename_raw : sprintf(
				'<a href="%s">%s</a>',
				esc_url( $item->editurl ),
				esc_html( $item->filename_raw )
			) ),
			esc_html( $item->linenum ),
			esc_html( $item->linepos )
		);
	}

	/**
	 * Create a full table populated with the supplied items.
	 *
	 * @param array $items An array of table rows.
	 * @param array $table_class An array of items to append to the table class along with the defaults.
	 *
	 * @return string
	 */
	public static function prepare_full_table( $items, $table_class = array() ) {
		$table_class = array_merge(
			$table_class,
			array(
				'wp-list-table',
				'widefat',
				'fixed',
				'striped',
				'tools_page_string-locator',
			)
		);

		$table_columns = sprintf(
			'<tr>
				<th scope="col" class="manage-column column-stringresult column-primary string">%s</th>
				<th scope="col" class="manage-column column-filename filename">%s</th>
				<th scope="col" class="manage-column column-linenum line">%s</th>
				<th scope="col" class="manage-column column-linepos position">%s</th>
			</tr>',
			esc_html( __( 'String', 'string-locator' ) ),
			esc_html( __( 'File', 'string-locator' ) ),
			esc_html( __( 'Line number', 'string-locator' ) ),
			esc_html( __( 'Line position', 'string-locator' ) )
		);

		$table_rows = array();
		foreach ( $items as $item ) {
			$table_rows[] = self::prepare_table_row( $item );
		}

		$table = sprintf(
			'<div class="tablenav top"><br class="clear"></div><table class="%s"><thead>%s</thead><tbody>%s</tbody><tfoot>%s</tfoot></table>',
			implode( ' ', $table_class ),
			$table_columns,
			implode( "\n", $table_rows ),
			$table_columns
		);

		return $table;
	}

	/**
	 * Set the text domain for translated plugin content.
	 *
	 * @return void
	 */
	function load_i18n() {
		$i18n_dir = 'string-locator/languages/';
		load_plugin_textdomain( 'string-locator', false, $i18n_dir );
	}

	/**
	 * Convert a value to its absolute boolean interpretation.
	 *
	 * @param $value
	 *
	 * @return bool
	 */
	public static function absbool( $value ) {
		if ( is_bool( $value ) ) {
			$bool = $value;
		} else {
			if ( 'false' === $value ) {
				$bool = false;
			} else {
				$bool = true;
			}
		}

		return $bool;
	}

	/**
	 * Load up JavaScript and CSS for our plugin on the appropriate admin pages.
	 *
	 * @return void
	 */
	function admin_enqueue_scripts( $hook ) {
		// Break out early if we are not on a String Locator page
		if ( 'tools_page_string-locator' !== $hook && 'toplevel_page_string-locator' !== $hook ) {
			return;
		}

		if ( ! wp_script_is( 'react', 'registered' ) ) {
			wp_register_script( 'react', trailingslashit( STRING_LOCATOR_PLUGIN_URL ) . 'resources/js/react.js', array() );
		}

		if ( ! wp_script_is( 'react-dom', 'registered' ) ) {
			wp_register_script( 'react-dom', trailingslashit( STRING_LOCATOR_PLUGIN_URL ) . 'resources/js/react-dom.js', array() );
		}

		/**
		 * String Locator Styles
		 */
		wp_enqueue_style( 'string-locator', trailingslashit( STRING_LOCATOR_PLUGIN_URL ) . 'resources/css/string-locator.css', array(), $this->version );

		if ( ! isset( $_GET['edit-file'] ) || ! current_user_can( 'edit_themes' ) ) {
			/**
			 * String Locator Scripts
			 */
			wp_enqueue_script( 'string-locator-search', trailingslashit( STRING_LOCATOR_PLUGIN_URL ) . 'resources/js/string-locator-search.js', array( 'jquery', 'wp-util' ), $this->version, true );

			wp_localize_script(
				'string-locator-search',
				'string_locator',
				array(
					'rest_nonce'            => wp_create_nonce( 'wp_rest' ),
					'search_nonce'          => wp_create_nonce( 'string-locator-search' ),
					'search_current_prefix' => __( 'Next file: ', 'string-locator' ),
					'saving_results_string' => __( 'Saving search results&hellip;', 'string-locator' ),
					'search_preparing'      => __( 'Preparing search&hellip;', 'string-locator' ),
					'search_started'        => __( 'Preparations completed, search started&hellip;', 'string-locator' ),
					'search_error'          => __( 'The above error was returned by your server, for more details please consult your servers error logs.', 'string-locator' ),
					'search_no_results'     => __( 'Your search was completed, but no results were found.', 'string-locator' ),
					'warning_title'         => __( 'Warning', 'string-locator' ),
					'url'                   => array(
						'search'              => get_rest_url( null, 'string-locator/v1/search' ),
						'clean'               => get_rest_url( null, 'string-locator/v1/clean' ),
						'directory_structure' => get_rest_url( null, 'string-locator/v1/get-directory-structure' ),
					),
				)
			);

		} else {
			$code_mirror = wp_enqueue_code_editor(
				array(
					'file' => $_GET['edit-file'],
				)
			);

			/**
			 * String Locator Scripts
			 */
			wp_enqueue_script( 'string-locator-editor', trailingslashit( STRING_LOCATOR_PLUGIN_URL ) . 'resources/js/string-locator.js', array( 'jquery', 'code-editor', 'wp-util' ), $this->version, true );

			wp_localize_script(
				'string-locator-editor',
				'string_locator',
				array(
					'CodeMirror'   => $code_mirror,
					'goto_line'    => absint( $_GET['string-locator-line'] ),
					'goto_linepos' => absint( $_GET['string-locator-linepos'] ),
					'url'          => array(
						'save' => get_rest_url( null, 'string-locator/v1/save' ),
					),
				)
			);
		}
	}

	/**
	 * Add our plugin to the 'Tools' menu.
	 *
	 * @return void
	 */
	function populate_menu() {
		if ( is_multisite() ) {
			return;
		}
		$page_title  = __( 'String Locator', 'string-locator' );
		$menu_title  = __( 'String Locator', 'string-locator' );
		$capability  = 'install_plugins';
		$parent_slug = 'tools.php';
		$menu_slug   = 'string-locator';
		$function    = array( $this, 'options_page' );

		add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
	}

	/**
	 * Add our plugin to the main menu in the Network Admin.
	 *
	 * @return void
	 */
	function populate_network_menu() {
		$page_title = __( 'String Locator', 'string-locator' );
		$menu_title = __( 'String Locator', 'string-locator' );
		$capability = 'install_plugins';
		$menu_slug  = 'string-locator';
		$function   = array( $this, 'options_page' );

		add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, 'dashicons-edit' );
	}

	/**
	 * Function for including the actual plugin Admin UI page.
	 *
	 * @return mixed
	 */
	function options_page() {
		/**
		 * Don't load anything if the user can't edit themes any way
		 */
		if ( ! current_user_can( 'update_core' ) ) {
			return false;
		}

		$include_path = '';

		/**
		 * Show the edit page if;
		 * - The edit file path query var is set
		 * - The edit file path query var isn't empty
		 * - The edit file path query var does not contains double dots (used to traverse directories)
		 * - The user is capable of editing files.
		 */
		if ( isset( $_GET['string-locator-path'] ) && self::is_valid_location( $_GET['string-locator-path'] ) && current_user_can( 'edit_themes' ) ) {
			$include_path = trailingslashit( STRING_LOCATOR_PLUGIN_DIR ) . 'views/editor.php';
		} else {
			$include_path = trailingslashit( STRING_LOCATOR_PLUGIN_DIR ) . 'views/search.php';
		}

		$include_path = apply_filters( 'string_locator_view', $include_path );

		if ( ! empty( $include_path ) ) {
			include_once $include_path;
		}
	}

	function admin_body_class( $class ) {
		if ( isset( $_GET['string-locator-path'] ) && self::is_valid_location( $_GET['string-locator-path'] ) && current_user_can( 'edit_themes' ) ) {
			$class .= ' file-edit-screen';
		}

		return $class;
	}

	/**
	 * Hook the admin notices and loop over any notices we've registered in the plugin.
	 *
	 * @return void
	 */
	function admin_notice() {
		if ( ! empty( $this->notice ) ) {
			foreach ( $this->notice as $note ) {
				printf(
					'<div class="%s"><p>%s</p></div>',
					esc_attr( $note['type'] ),
					$note['message']
				);
			}
		}
	}

	/**
	 * Check if a file path is valid for editing.
	 *
	 * @param string $path Path to file.
	 *
	 * @return bool
	 */
	public static function is_valid_location( $path ) {
		$valid   = true;
		$path    = str_replace( array( '/' ), array( DIRECTORY_SEPARATOR ), stripslashes( $path ) );
		$abspath = str_replace( array( '/' ), array( DIRECTORY_SEPARATOR ), ABSPATH );

		// Check that it is a valid file we are trying to access as well.
		if ( ! file_exists( $path ) ) {
			$valid = false;
		}

		if ( empty( $path ) ) {
			$valid = false;
		}
		if ( stristr( $path, '..' ) ) {
			$valid = false;
		}
		if ( ! stristr( $path, $abspath ) ) {
			$valid = false;
		}

		return $valid;
	}
}
