<?php

namespace JITS\StringLocator\Extension\SQL;

use JITS\StringLocator\Base\Search as SearchBase;
use JITS\StringLocator\String_Locator;

class Search extends SearchBase {

	public function __construct() {

		add_filter( 'string_locator_search_handler', array( $this, 'maybe_perform_sql_search' ), 10, 2 );
		add_filter( 'string_locator_directory_iterator_short_circuit', array( $this, 'maybe_short_circuit_directory_iterator' ), 10, 2 );

		parent::__construct();
	}

	public function add_search_response_template() {
		require_once STRING_LOCATOR_PLUGIN_DIR . '/includes/Extension/SQL/views/template/search.php';
	}

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
				)
			);
		}

		return $short_circuit;
	}

	public function maybe_perform_sql_search( $handler, $request ) {
		$search_data = get_transient( 'string-locator-search-overview' );

		if ( empty( $search_data ) || ! isset( $search_data->type ) || 'sql' !== $search_data->type ) {
			return $handler;
		}

		return $this;
	}

	public function run( $filenum ) {
		global $wpdb;

		$response        = array(
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

		$match_count = 0;

		foreach ( $tables as $table ) {
			$table_name = $table->{ $identifier_name };

			$columns = $wpdb->get_results( 'DESCRIBE ' . $table_name );

			$primary_column = null;
			$primary_type   = null;

			// Initial loop only gets primary data.
			foreach ( $columns as $column ) {
				if ( 'PRI' === $column->Key ) {
					$primary_column = $column->Field;
					$primary_type   = ( stristr( $column->Type, 'int' ) ? 'int' : 'str' );
				}
			}

			foreach ( $columns as $column ) {
				$column_name = $column->Field;

				if ( $is_regex ) {
					$matches = $wpdb->get_results(
						$wpdb->prepare(
							'SELECT ' . $column_name . ' AS column_name, ' . $primary_column . ' as primary_column FROM ' . $table_name . ' WHERE ' . $column_name . ' REGEXP %s',
							$scan_data->search
						)
					);
				} else {
					$matches = $wpdb->get_results(
						$wpdb->prepare(
							'SELECT ' . $column_name . ' AS column_name, ' . $primary_column . ' as primary_column FROM ' . $table_name . ' WHERE ' . $column_name . ' LIKE %s',
							'%' . $wpdb->esc_like( $scan_data->search ) . '%'
						)
					);
				}

				if ( is_wp_error( $matches ) ) {
					wp_send_json_error(
						array(
							'continue' => false,
							'message'  => sprintf(
							/* translators: %s: The search string used. */
								__( 'Your search for <strong>%s</strong> led to an SQL error, and the search has been aborted. The error encountered was: %s', 'string-locator' ),
								esc_html( $matches->get_error_message() )
							),
						)
					);
				}

				foreach ( $matches as $match ) {
					$match_count++;
					$string_preview_is_cut = false;

					$string         = $scan_data->search;
					$string_preview = $match->column_name;

					$string_location = 0;

					if ( strlen( $string_preview ) > ( strlen( $string ) + $this->excerpt_length ) ) {
						$string_location = strpos( $string_preview, $string );

						$string_location_start = $string_location - $this->excerpt_length;
						if ( $string_location_start < 0 ) {
							$string_location_start = 0;
						}

						$string_location_end = ( strlen( $string ) + ( $this->excerpt_length * 2 ) );
						if ( $string_location_end > strlen( $string_preview ) ) {
							$string_location_end = strlen( $string_preview );
						}

						$string_preview        = substr( $string_preview, $string_location_start, $string_location_end );
						$string_preview_is_cut = true;
					}

					if ( $is_regex ) {
						$string_preview = preg_replace( preg_replace( '/\/(.+)\//', '/($1)/', $string ), '<strong>$1</strong>', esc_html( $string_preview ) );
					} else {
						$string_preview = preg_replace( '/(' . preg_quote( $string ) . ')/i', '<strong>$1</strong>', esc_html( $string_preview ) );
					}
					if ( $string_preview_is_cut ) {
						$string_preview = sprintf(
							'&hellip;%s&hellip;',
							$string_preview
						);
					}

					$editurl = add_query_arg(
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

					$response['search'][] = array(
						array(
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
							'editurl'        => ( current_user_can( 'edit_themes' ) ? $editurl : false ),
							'stringresult'   => $string_preview,
							'linepos'        => $string_location,
						)
					);
				}
			}
		}

		return $response;
	}

}

new Search();
