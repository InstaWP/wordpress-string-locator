<?php

namespace StringLocator\Extension\SQL;

use StringLocator\Base\Search as SearchBase;
use StringLocator\String_Locator;

/**
 * Search class.
 */
class Search extends SearchBase {

	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_filter( 'string_locator_search_sources_markup', array( $this, 'add_search_options' ), 11, 2 );

		add_filter( 'string_locator_search_handler', array( $this, 'maybe_perform_sql_search' ), 10, 2 );
		add_filter( 'string_locator_directory_iterator_short_circuit', array( $this, 'maybe_short_circuit_directory_iterator' ), 10, 2 );

		add_filter( 'string_locator_restore_search_row', array( $this, 'restore_sql_search' ), 10, 2 );

		parent::__construct();
	}

	/**
	 * Handle markup output when restoring a search result.
	 *
	 * @param string $row  The result row.
	 * @param object $item The search result item for this row.
	 *
	 * @return string
	 */
	public function restore_sql_search( $row, $item ) {
		if ( ! isset( $item->primary_key ) ) {
			return $row;
		}

		$row = sprintf(
			'<tr data-type="sql" data-primary-key="%d" data-primary-column="%s" data-primary-type="%s" data-table-name="%s" data-column-name="%s">
				<th scope="row" class="check-column">
					<input type="checkbox" name="string-locator-replace-checked[]" class="check-column-box">
				</th>
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
			esc_attr( $item->primary_key ),
			esc_attr( $item->primary_column ),
			esc_attr( $item->primary_type ),
			esc_attr( $item->table ),
			esc_attr( $item->column ),
			$item->stringresult,
			( ! current_user_can( String_Locator::$default_capability ) ? '' : sprintf(
				'<span class="edit"><a href="%1$s" aria-label="%2$s">%2$s</a></span>',
				esc_url( $item->editurl ),
				// translators: The row-action edit link label.
				esc_html__( 'Edit', 'string-locator' )
			) ),
			( ! current_user_can( String_Locator::$default_capability ) ? $item->filename : sprintf(
				'<a href="%s">%s</a>',
				esc_url( $item->editurl ),
				esc_html( $item->filename )
			) ),
			esc_html( $item->primary_key ),
			esc_html( $item->linepos )
		);

		return $row;
	}

	/**
	 * Add SQL items as search options.
	 *
	 * @param string $searchers       The markup for the existing search options.
	 * @param string $search_location The currently selected search option, when restoring a search.
	 *
	 * @return string
	 */
	public function add_search_options( $searchers, $search_location ) {
		ob_start();
		?>

		<optgroup label="<?php esc_attr_e( 'Database', 'string-locator' ); ?>">
			<option value="sql"<?php echo ( 'sql' === $search_location ? ' selected="selected"' : '' ); ?>><?php esc_html_e( 'All database tables', 'string-locator' ); ?></option>
		</optgroup>

		<?php

		$searchers .= ob_get_clean();

		return $searchers;
	}

	/**
	 * Output the underscores template used for SQL search results.
	 *
	 * @return void
	 */
	public function add_search_response_template() {
		require_once STRING_LOCATOR_PLUGIN_DIR . '/includes/Extension/SQL/views/template/search.php';
	}

	/**
	 * Allow the SQL search to ignore the DirectoryIterator request used to build search bases for files.
	 *
	 * @param bool              $short_circuit Whether to short-circuit the DirectoryIterator request.
	 * @param \WP_REST_Response $request       The WP_REST_Request for this API call.
	 *
	 * @return array
	 */
	public function maybe_short_circuit_directory_iterator( $short_circuit, $request ) {
		$data = json_decode( $request->get_param( 'data' ) );

		if ( 'sql' === $data->directory ) {
			$store = (object) array(
				'type'      => 'sql',
				'search'    => $data->search,
				'directory' => 'sql',
				'chunks'    => 1,
				'regex'     => $data->regex,
			);

			set_transient( 'string-locator-search-overview', $store );
			update_option( 'string-locator-search-history', array(), false );

			return array(
				'success' => true,
				'data'    => array(
					'chunks'  => 1,
					'current' => 0,
					'regex'   => $data->regex,
					'total'   => 1,
				),
			);
		}

		return $short_circuit;
	}

	/**
	 * Conditionally override the search handler.
	 *
	 * @param mixed            $handler The currently active class for handling the search request.
	 * @param \WP_REST_Request $request The request received by the REST API handler.
	 *
	 * @return $this
	 */
	public function maybe_perform_sql_search( $handler, $request ) {
		$search_data = get_transient( 'string-locator-search-overview' );

		if ( empty( $search_data ) || ! isset( $search_data->type ) || 'sql' !== $search_data->type ) {
			return $handler;
		}

		return $this;
	}

	/**
	 * Run the search.
	 *
	 * @param int $filenum An integer representing where in the line you are when doing batch searches.
	 *
	 * @return array
	 */
	public function run( $filenum ) {
		global $wpdb;

		$response = array(
			'search'  => array(),
			'filenum' => absint( $filenum ),
			'current' => 0,
			'total'   => 0,
			'type'    => 'sql',
		);

		$scan_data = get_transient( 'string-locator-search-overview' );

		$is_regex = false;
		if ( isset( $scan_data->regex ) ) {
			$is_regex = String_Locator::absbool( $scan_data->regex );
		}

		if ( $is_regex ) {
			if ( false === @preg_match( $scan_data->search, '' ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				wp_send_json_error(
					array(
						'continue' => false,
						'message'  => sprintf(
							/* translators: %s: The search string used. */
							__( 'Your search string, <strong>%s</strong>, is not a valid pattern, and the search has been aborted.', 'string-locator' ),
							esc_html( $scan_data->search )
						),
					)
				);
			}
		}

		// Get al ist of all available tables to search through.
		$tables = $wpdb->get_results( 'SHOW TABLES' );

		$identifier_name = 'Tables_in_' . DB_NAME;

		if ( ! validate_sql_fields( $identifier_name ) ) {
			wp_send_json_error(
				array(
					'continue' => false,
					'message'  => sprintf(
					/* translators: %s: The search string used. */
						__( 'The table identifier, combined with your database name, <strong>%s</strong>, is not a valid SQL pattern, and the search has been aborted.', 'string-locator' ),
						esc_html( $identifier_name )
					),
				)
			);
		}

		$match_count = 0;

		$search_results = array();

		foreach ( $tables as $table ) {
			$table_name = $table->{ $identifier_name };

			$columns = $wpdb->get_results( 'DESCRIBE ' . $table_name ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- The table name is validated earlier for SQLi, and needs to be dynamic due to relying on `DB_NAME`.

			$primary_column = null;
			$primary_type   = null;

			// Initial loop only gets primary data.
			foreach ( $columns as $column ) {
				if ( 'PRI' === $column->Key ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Object property name is returned by the MySQL database.
					$primary_column = $column->Field; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Object property name is returned by the MySQL database.
					$primary_type   = ( stristr( $column->Type, 'int' ) ? 'int' : 'str' ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Object property name is returned by the MySQL database.
				}
			}

			foreach ( $columns as $column ) {
				$column_name = $column->Field; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Object property name is returned by the MySQL database.

				if ( $is_regex ) {
					$matches = $wpdb->get_results(
						$wpdb->prepare(
							'SELECT ' . $column_name . ' AS column_name, ' . $primary_column . ' as primary_column FROM ' . $table_name . ' WHERE ' . $column_name . ' REGEXP %s', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- It is not possible to prepare a table or column name, but these are instead validated in `/includes/Search/class-sql.php` before reaching this point.
							$scan_data->search
						)
					);
				} else {
					$matches = $wpdb->get_results(
						$wpdb->prepare(
							'SELECT ' . $column_name . ' AS column_name, ' . $primary_column . ' as primary_column FROM ' . $table_name . ' WHERE ' . $column_name . ' LIKE %s', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- It is not possible to prepare a table or column name, but these are instead validated in `/includes/Search/class-sql.php` before reaching this point.
							'%' . $wpdb->esc_like( $scan_data->search ) . '%'
						)
					);
				}

				if ( is_wp_error( $matches ) ) {
					wp_send_json_error(
						array(
							'continue' => false,
							'message'  => sprintf(
								/* translators: 1: The search string used. 2: The error received */
								__( 'Your search for <strong>%1$s</strong> led to an SQL error, and the search has been aborted. The error encountered was: %2$s', 'string-locator' ),
								esc_html( $scan_data->search ),
								esc_html( $matches->get_error_message() )
							),
						)
					);
				}

				foreach ( $matches as $match ) {
					$match_count++;

					$string         = $scan_data->search;
					$string_preview = $match->column_name;

					$string_location = 0;

					$string_preview = String_Locator::create_preview( $string_preview, $string, $is_regex );

					$editurl = $this->create_edit_link( $table_name, $column_name, $primary_column, $primary_type, $match );

					$search_results[] = array(
						'ID'             => $match_count,
						'table'          => $table_name,
						'column'         => $column_name,
						'primary_key'    => $match->primary_column,
						'primary_type'   => $primary_type,
						'primary_column' => $primary_column,
						'filename'       => sprintf(
							'`%s`.`%s`',
							$table_name,
							$column_name
						),
						'filename_raw'   => sprintf(
							'`%s`.`%s`',
							$table_name,
							$column_name
						),
						'editurl'        => ( current_user_can( String_Locator::$default_capability ) ? $editurl : false ),
						'stringresult'   => $string_preview,
						'linepos'        => $string_location,
						'linenum'        => 0,
					);
				}
			}
		}

		if ( ! empty( $search_results ) ) {
			$history = get_option( 'string-locator-search-history', array() );
			$history = array_merge( $history, $search_results );
			update_option( 'string-locator-search-history', $history, false );
		}

		$response['search'] = array(
			$search_results,
		);

		return $response;
	}

	/**
	 * Generate a link to the editor interface for a search result.
	 *
	 * @param string $table_name     The table name where a match was found.
	 * @param string $column_name    The column name where a match was found.
	 * @param string $primary_column The primary column from the table having a match.
	 * @param string $primary_type   The type of the primary column.
	 * @param object $match          An object containing details of the match.
	 *
	 * @return string
	 */
	public function create_edit_link( $table_name, $column_name, $primary_column, $primary_type, $match ) {
		return add_query_arg(
			array(
				'page'               => 'string-locator',
				'edit-file'          => true,
				'file-type'          => 'sql',
				'file-reference'     => sprintf(
					'`%s`.`%s`',
					$table_name,
					$column_name
				),
				'sql-column'         => $column_name,
				'sql-table'          => $table_name,
				'sql-primary-column' => $primary_column,
				'sql-primary-type'   => $primary_type,
				'sql-primary-key'    => $match->primary_column,
			),
			admin_url( $this->path_to_use )
		);
	}
}

new Search();
