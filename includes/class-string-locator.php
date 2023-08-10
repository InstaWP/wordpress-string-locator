<?php

namespace StringLocator;

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
	 * The default capability to check for when seeing if a user can access the plugin.
	 *
	 * @var string
	 */
	public static $default_capability = 'edit_themes';

	/**
	 * The capability required to perform searches, but not necessarily edit files.
	 *
	 * We use the `edit_users` capability here, although this is not technically the most ideal,
	 * all other relevant capabilities are disabled in one way or another when certain features
	 * are used to disable things like the plugin or theme editing.
	 *
	 * The use of `edit_users` may also cause other problems, but we do not want to allow any user
	 * access to search the entire filesystem, or database, without some sort of restriction.
	 *
	 * @var string
	 */
	public static $search_capability = 'edit_users';

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

		add_action( 'string_locator_search_templates', array( $this, 'add_search_restults_templates' ) );
		add_action( 'string_locator_editor_sidebar_before_checks', array( $this, 'add_instawp_reference' ) );
	}

	public function add_search_restults_templates() {
		require_once STRING_LOCATOR_PLUGIN_DIR . '/views/templates/search-default.php';
	}

	public function add_instawp_reference() {
		include_once STRING_LOCATOR_PLUGIN_DIR . '/views/templates/instawp.php';
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
				'<a href="https://instawp.com/?utm_source=stringlocator/" target="_blank">%s</a>',
				esc_html__( 'Create Disposable WordPress Sites in Seconds', 'string-locator' )
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

		$row = sprintf(
			'<tr data-type="file" data-linenum="%6$d" data-filename="%4$s">
				<th scope="row" class="check-column">
					<input type="checkbox" name="string-locator-replace-checked[]" class="check-column-box">
				</th>
                <td>
                	%1$s
                	<div class="row-actions">
                		%2$s
                    </div>
                </td>
                <td>
                	%3$s
                </td>
                <td>
                	%5$d
                </td>
                <td>
                	%7$d
                </td>
            </tr>',
			$item->stringresult,
			( ! current_user_can( String_Locator::$default_capability ) ? '' : sprintf(
				'<span class="edit"><a href="%1$s" aria-label="%2$s">%2$s</a></span>',
				esc_url( $item->editurl ),
				// translators: The row-action edit link label.
				esc_html__( 'Edit', 'string-locator' )
			) ),
			( ! current_user_can( String_Locator::$default_capability ) ? $item->filename_raw : sprintf(
				'<a href="%s">%s</a>',
				esc_url( $item->editurl ),
				esc_html( $item->filename_raw )
			) ),
			esc_attr( $item->filename_raw ),
			esc_html( $item->linenum ),
			esc_attr( $item->linenum ),
			esc_html( $item->linepos )
		);

		/**
		 * Enable extensions to override the table row when restoring a previous search.
		 *
		 * @attr string $row  The HTML markup for the table row.
		 * @attr object $item The search result item data.
		 */
		$row = apply_filters( 'string_locator_restore_search_row', $row, $item );

		return $row;
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
				'tools-page-string-locator',
			)
		);

		$table_columns = sprintf(
			'<tr>
				<th scope="col" class="manage-column column-cb check-column">
					<label class="screen-reader-text" for="cb-select-all-1">Select All</label>
					<input id="cb-select-all-1" type="checkbox">
				</th>
				<th scope="col" class="manage-column column-stringresult column-primary string">%s</th>
				<th scope="col" class="manage-column column-filename filename">%s</th>
				<th scope="col" class="manage-column column-linenum line">%s</th>
				<th scope="col" class="manage-column column-linepos position">%s</th>
			</tr>',
			esc_html( __( 'String', 'string-locator' ) ),
			esc_html( __( 'File / Table', 'string-locator' ) ),
			esc_html( __( 'ID / Line number', 'string-locator' ) ),
			esc_html( __( 'Line position', 'string-locator' ) )
		);

		$table_rows = array();
		foreach ( $items as $item ) {
			$table_rows[] = self::prepare_table_row( $item );
		}

		$table = sprintf(
			'<table class="%s" id="string-locator-search-results-table"><thead>%s</thead><tbody id="string-locator-search-results-tbody">%s</tbody><tfoot>%s</tfoot></table>',
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

		$search = STRING_LOCATOR_PLUGIN_DIR . 'build/string-locator-search.asset.php';
		$editor = STRING_LOCATOR_PLUGIN_DIR . 'build/string-locator.asset.php';

		$search = file_exists( $search ) ? require $search : array();
		$editor = file_exists( $editor ) ? require $editor : array();

		/**
		 * String Locator Styles
		 */
		wp_enqueue_style( 'string-locator', trailingslashit( STRING_LOCATOR_PLUGIN_URL ) . 'build/string-locator.css', array(), $search['version'] );

		if ( ! isset( $_GET['edit-file'] ) || ! current_user_can( String_Locator::$default_capability ) ) {
			/**
			 * String Locator Scripts
			 */
			wp_enqueue_script(
				'string-locator-search',
				trailingslashit( STRING_LOCATOR_PLUGIN_URL ) . 'build/string-locator-search.js',
				array( 'wp-util' ),
				$search['version'],
				true
			);

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
			wp_enqueue_script(
				'string-locator-editor',
				trailingslashit( STRING_LOCATOR_PLUGIN_URL ) . 'build/string-locator.js',
				array( 'code-editor', 'wp-util' ),
				$editor['version'],
				true
			);

			wp_localize_script(
				'string-locator-editor',
				'string_locator',
				array(
					'CodeMirror'   => $code_mirror,
					'goto_line'    => ( isset( $_GET['string-locator-line'] ) ? absint( $_GET['string-locator-line'] ) : 0 ),
					'goto_linepos' => ( isset( $_GET['string-locator-linepos'] ) ? absint( $_GET['string-locator-linepos'] ) : 0 ),
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
		// if ( is_multisite() ) {
		// 	return;
		// }
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
		if ( ! current_user_can( 'edit_users' ) ) {
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
		if ( isset( $_GET['string-locator-path'] ) && self::is_valid_location( $_GET['string-locator-path'] ) && current_user_can( String_Locator::$default_capability ) ) {
			$include_path = trailingslashit( STRING_LOCATOR_PLUGIN_DIR ) . 'views/editors/default.php';
		} else {
			$include_path = trailingslashit( STRING_LOCATOR_PLUGIN_DIR ) . 'views/search.php';
		}

		$include_path = apply_filters( 'string_locator_view', $include_path );

		if ( ! empty( $include_path ) ) {
			include_once $include_path;
		}
	}

	function admin_body_class( $class ) {
		if ( isset( $_GET['string-locator-path'] ) && self::is_valid_location( $_GET['string-locator-path'] ) && current_user_can( String_Locator::$default_capability ) ) {
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
		$path    = str_replace( array( '/' ), array( DIRECTORY_SEPARATOR ), stripslashes( $path ) );
		$abspath = str_replace( array( '/' ), array( DIRECTORY_SEPARATOR ), ABSPATH );

		/*
		 * Check that the ABSPath is the start of the path.
		 * This helps ensure that no protocol triggers can be used as part of the file path.
		 */
		if ( substr( $path, 0, strlen( $abspath ) ) !== $abspath ) {
			return false;
		}

		// Check that it is a valid file we are trying to access as well.
		if ( ! file_exists( $path ) ) {
			return false;
		}

		if ( empty( $path ) ) {
			return false;
		}
		if ( stristr( $path, '..' ) ) {
			return false;
		}

		return true;
	}

	public static function create_preview( $string_preview, $string, $regex = false ) {
		/**
		 * Define class variables requiring expressions
		 */
		$excerpt_length = apply_filters( 'string_locator_excerpt_length', 25 );

		$string_preview_is_cut = false;

		if ( strlen( $string_preview ) > ( strlen( $string ) + $excerpt_length ) ) {
			$string_location = strpos( $string_preview, $string );

			$string_location_start = $string_location - $excerpt_length;
			if ( $string_location_start < 0 ) {
				$string_location_start = 0;
			}

			$string_location_end = ( strlen( $string ) + ( $excerpt_length * 2 ) );
			if ( $string_location_end > strlen( $string_preview ) ) {
				$string_location_end = strlen( $string_preview );
			}

			$string_preview        = substr( $string_preview, $string_location_start, $string_location_end );
			$string_preview_is_cut = true;
		}

		if ( $regex ) {
			$string_preview = preg_replace( preg_replace( '/\/(.+)\//', '/($1)/', $string ), '<strong>$1</strong>', esc_html( $string_preview ) );
		} else {
			$string_preview = preg_replace( '/(' . preg_quote( $string, '/' ) . ')/i', '<strong>$1</strong>', esc_html( $string_preview ) );
		}
		if ( $string_preview_is_cut ) {
			$string_preview = sprintf(
				'&hellip;%s&hellip;',
				$string_preview
			);
		}

		return $string_preview;
	}
}
