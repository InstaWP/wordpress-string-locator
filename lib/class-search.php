<?php

namespace JITS\StringLocator;

class Search {

	/**
	 * An array of file extensions that will be ignored by the scanner.
	 *
	 * @var string[]
	 */
	private $bad_file_types = array(
		'rar',
		'7z',
		'zip',
		'tar',
		'gz',
		'jpg',
		'jpeg',
		'png',
		'gif',
		'mp3',
		'mp4',
		'avi',
		'wmv',
	);

	/**
	 * The path to the currently editable file.
	 *
	 * @var string
	 */
	private $path_to_use = '';

	/**
	 * The length of the excerpt from the line containing a match.
	 *
	 * @var int
	 */
	private $excerpt_length = 25;

	/**
	 * The server-configured max time a script can run.
	 *
	 * @var int
	 */
	private $max_execution_time = null;

	/**
	 * The current time when our script started executing.
	 *
	 * @var float
	 */
	private $start_execution_timer = 0;

	/**
	 * The server-configured max amount of memory a script can use.
	 *
	 * @var int
	 */
	private $max_memory_consumption = 0;

	public function __construct() {
		/**
		 * Define class variables requiring expressions
		 */
		$this->path_to_use    = ( is_multisite() ? 'network/admin.php' : 'tools.php' );
		$this->excerpt_length = apply_filters( 'string_locator_excerpt_length', 25 );

		$this->max_execution_time    = absint( ini_get( 'max_execution_time' ) );
		$this->start_execution_timer = microtime( true );

		if ( $this->max_execution_time > 30 ) {
			$this->max_execution_time = 30;
		}

		$this->set_memory_limit();
	}

	/**
	 * Sets up the memory limit variables.
	 *
	 * @return void
	 * @since 2.0.0
	 *
	 */
	function set_memory_limit() {
		$memory_limit = ini_get( 'memory_limit' );

		$this->max_memory_consumption = absint( $memory_limit );

		if ( strstr( $memory_limit, 'k' ) ) {
			$this->max_memory_consumption = ( str_replace( 'k', '', $memory_limit ) * 1000 );
		}
		if ( strstr( $memory_limit, 'M' ) ) {
			$this->max_memory_consumption = ( str_replace( 'M', '', $memory_limit ) * 1000000 );
		}
		if ( strstr( $memory_limit, 'G' ) ) {
			$this->max_memory_consumption = ( str_replace( 'G', '', $memory_limit ) * 1000000000 );
		}
	}

	/**
	 * Check if the script is about to exceed the max execution time.
	 *
	 * @return bool
	 * @since 1.9.0
	 *
	 */
	function nearing_execution_limit() {
		// Max execution time is 0 or -1 (infinite) in server config
		if ( 0 === $this->max_execution_time || - 1 === $this->max_execution_time ) {
			return false;
		}

		$back_compat_filter = apply_filters( 'string-locator-extra-search-delay', 2 ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

		$built_in_delay = apply_filters( 'string_locator_extra_search_delay', $back_compat_filter );
		$execution_time = ( microtime( true ) - $this->start_execution_timer + $built_in_delay );

		if ( $execution_time >= $this->max_execution_time ) {
			return $execution_time;
		}

		return false;
	}

	/**
	 * Check if the script is about to exceed the server memory limit.
	 *
	 * @return bool
	 * @since 2.0.0
	 *
	 */
	function nearing_memory_limit() {
		// Check if the memory limit is set t o0 or -1 (infinite) in server config
		if ( 0 === $this->max_memory_consumption || - 1 === $this->max_memory_consumption ) {
			return false;
		}

		// We give our selves a 256k memory buffer, as we need to close off the script properly as well
		$back_compat_filter = apply_filters( 'string-locator-extra-memory-buffer', 256000 ); //phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
		$built_in_buffer    = apply_filters( 'string_locator_extra_memory_buffer', $back_compat_filter );
		$memory_use         = ( memory_get_usage( true ) + $built_in_buffer );

		if ( $memory_use >= $this->max_memory_consumption ) {
			return $memory_use;
		}

		return false;
	}

	public function run( $filenum ) {
		$_POST['filenum'] = $filenum;

		$files_per_chunk = apply_filters( 'string_locator_files_per_array', 500 );
		$response        = array(
			'search'  => array(),
			'filenum' => absint( $_POST['filenum'] ),
		);

		$filenum = absint( $_POST['filenum'] );

		$chunk = ( ceil( $filenum / $files_per_chunk ) - 1 );
		if ( $chunk < 0 ) {
			$chunk = 0;
		}

		$scan_data = get_transient( 'string-locator-search-overview' );
		$file_data = get_transient( 'string-locator-search-files-' . $chunk );

		if ( ! isset( $file_data[ $filenum ] ) ) {
			wp_send_json_error(
				array(
					'continue' => false,
					'message'  => sprintf(
						/* translators: %d: The numbered reference to a file being searched. */
						esc_html__( 'The file-number, %d, that was sent could not be found.', 'string-locator' ),
						$filenum
					),
				)
			);
		}

		if ( $this->nearing_execution_limit() ) {
			wp_send_json_error(
				array(
					'continue' => false,
					'message'  => sprintf(
						/* translators: %1$d: The time a PHP file can run, as defined by the server configuration. %2$d: The amount of time used by the PHP file so far. */
						esc_html__( 'The maximum time your server allows a script to run (%1$d) is too low for the plugin to run as intended, at startup %2$d seconds have passed', 'string-locator' ),
						$this->max_execution_time,
						$this->nearing_execution_limit()
					),
				)
			);
		}
		if ( $this->nearing_memory_limit() ) {
			wp_send_json_error(
				array(
					'continue' => false,
					'message'  => sprintf(
						/* translators: %1$d: Current amount of used system memory resources. %2$d: The maximum available system memory. */
						esc_html__( 'The memory limit is about to be exceeded before the search has started, this could be an early indicator that your site may soon struggle as well, unfortunately this means the plugin is unable to perform any searches. Current memory consumption: %1$d of %2$d bytes', 'string-locator' ),
						$this->nearing_memory_limit(),
						$this->max_memory_consumption
					),
				)
			);
		}

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

		while ( ! $this->nearing_execution_limit() && ! $this->nearing_memory_limit() && isset( $file_data[ $filenum ] ) ) {
			$filenum        = absint( $_POST['filenum'] );
			$search_results = null;
			$next_file      = $filenum + 1;

			$next_chunk = ( ceil( ( $next_file ) / $files_per_chunk ) - 1 );
			$chunk      = ( ceil( $filenum / $files_per_chunk ) - 1 );
			if ( $chunk < 0 ) {
				$chunk = 0;
			}
			if ( $next_chunk < 0 ) {
				$next_chunk = 0;
			}

			if ( ! isset( $file_data[ $filenum ] ) ) {
				$chunk ++;
				$file_data = get_transient( 'string-locator-search-files-' . $chunk );
				continue;
			}

			$file_name = explode( '/', $file_data[ $filenum ] );
			$file_name = end( $file_name );

			/*
			 * Check the file type, if it's an unsupported type, we skip it
			 */
			$file_type = explode( '.', $file_name );
			$file_type = strtolower( end( $file_type ) );

			/*
			 * Scan the file and look for our string, but only if it's an approved file extension
			 */
			$bad_file_types = apply_filters( 'string_locator_bad_file_types', $this->bad_file_types );
			if ( ! in_array( $file_type, $bad_file_types, true ) ) {
				$search_results = $this->scan_file( $file_data[ $filenum ], $scan_data->search, $file_data[ $filenum ], $scan_data->scan_path->type, '', $is_regex );
			}

			$response['last_file'] = $file_data[ $filenum ];
			$response['filenum']   = $filenum;
			$response['filename']  = $file_name;
			if ( $search_results ) {
				$response['search'][] = $search_results;
			}

			if ( $next_chunk !== $chunk ) {
				$file_data = get_transient( 'string-locator-search-files-' . $next_chunk );
			}

			$response['next_file'] = ( isset( $file_data[ $next_file ] ) ? $file_data[ $next_file ] : '' );

			if ( ! empty( $search_results ) ) {
				$history = get_option( 'string-locator-search-history', array() );
				$history = array_merge( $history, $search_results );
				update_option( 'string-locator-search-history', $history, false );
			}

			$_POST['filenum'] ++;
		}

		return $response;
	}

	/**
	 * Scan through an individual file to look for occurrences of £string.
	 *
	 * @param string $filename The path to the file.
	 * @param string $string The search string.
	 * @param mixed $location The file location object/string.
	 * @param string $type File type.
	 * @param string $slug The plugin/theme slug of the file.
	 * @param boolean $regex Should a regex search be performed.
	 *
	 * @return array
	 */
	function scan_file( $filename, $string, $location, $type, $slug, $regex = false ) {
		if ( empty( $string ) || ! is_file( $filename ) ) {
			return array();
		}
		$output      = array();
		$linenum     = 0;
		$match_count = 0;

		if ( ! is_object( $location ) ) {
			$path     = $location;
			$location = explode( DIRECTORY_SEPARATOR, $location );
			$file     = end( $location );
		} else {
			$path = $location->getPathname();
			$file = $location->getFilename();
		}

		/*
		 * Check if the filename matches our search pattern
		 */
		if ( stristr( $file, $string ) || ( $regex && preg_match( $string, $file ) ) ) {
			$relativepath = str_replace(
				array(
					ABSPATH,
					'\\',
					'/',
				),
				array(
					'',
					DIRECTORY_SEPARATOR,
					DIRECTORY_SEPARATOR,
				),
				$path
			);
			$match_count ++;

			$editurl = $this->create_edit_link( $path, $linenum );

			$path_string = sprintf(
				'<a href="%s">%s</a>',
				esc_url( $editurl ),
				esc_html( $relativepath )
			);

			$output[] = array(
				'ID'           => $match_count,
				'linenum'      => sprintf(
					'[%s]',
					esc_html__( 'Filename matches search', 'string-locator' )
				),
				'linepos'      => '',
				'path'         => $path,
				'filename'     => $path_string,
				'filename_raw' => $relativepath,
				'editurl'      => ( current_user_can( 'edit_themes' ) ? $editurl : false ),
				'stringresult' => $file,
			);
		}

		$readfile = @fopen( $filename, 'r' );
		if ( $readfile ) {
			while ( ( $readline = fgets( $readfile ) ) !== false ) { // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
				$string_preview_is_cut = false;
				$linenum ++;
				/**
				 * If our string is found in this line, output the line number and other data
				 */
				if ( ( ! $regex && stristr( $readline, $string ) ) || ( $regex && preg_match( $string, $readline, $match, PREG_OFFSET_CAPTURE ) ) ) {
					/**
					 * Prepare the visual path for the end user
					 * Removes path leading up to WordPress root and ensures consistent directory separators
					 */
					$relativepath = str_replace(
						array(
							ABSPATH,
							'\\',
							'/',
						),
						array(
							'',
							DIRECTORY_SEPARATOR,
							DIRECTORY_SEPARATOR,
						),
						$path
					);
					$match_count ++;

					if ( $regex ) {
						$str_pos = $match[0][1];
					} else {
						$str_pos = stripos( $readline, $string );
					}

					/**
					 * Create the URL to take the user to the editor
					 */
					$editurl = $this->create_edit_link( $path, $linenum, $str_pos );

					$string_preview = $readline;
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

					if ( $regex ) {
						$string_preview = preg_replace( preg_replace( '/\/(.+)\//', '/($1)/', $string ), '<strong>$1</strong>', esc_html( $string_preview ) );
					} else {
						$string_preview = preg_replace( '/(' . $string . ')/i', '<strong>$1</strong>', esc_html( $string_preview ) );
					}
					if ( $string_preview_is_cut ) {
						$string_preview = sprintf(
							'&hellip;%s&hellip;',
							$string_preview
						);
					}

					$path_string = sprintf(
						'<a href="%s">%s</a>',
						esc_url( $editurl ),
						esc_html( $relativepath )
					);

					$output[] = array(
						'ID'           => $match_count,
						'linenum'      => $linenum,
						'linepos'      => $str_pos,
						'path'         => $path,
						'filename'     => $path_string,
						'filename_raw' => $relativepath,
						'editurl'      => ( current_user_can( 'edit_themes' ) ? $editurl : false ),
						'stringresult' => $string_preview,
					);
				}
			}

			fclose( $readfile );
		} else {
			/**
			 * The file was unreadable, give the user a friendly notification
			 */
			$output[] = array(
				'linenum'      => '#',
				// translators: 1: Filename.
				'filename'     => esc_html( sprintf( __( 'Could not read file: %s', 'string-locator' ), $filename ) ),
				'stringresult' => '',
			);
		}

		return $output;
	}

	/**
	 * Create an admin edit link for the supplied path.
	 *
	 * @param string $path Path to the file we'er adding a link for.
	 * @param int $line The line in the file where our search result was found.
	 * @param int $linepos The positin in the line where the search result was found.
	 *
	 * @return string
	 */
	function create_edit_link( $path, $line = 0, $linepos = 0 ) {
		$file_type    = 'core';
		$file_slug    = '';
		$content_path = str_replace( '\\', '/', WP_CONTENT_DIR );

		$path  = str_replace( '\\', '/', $path );
		$paths = explode( '/', $path );

		$url_args = array(
			'page=string-locator',
			'edit-file=' . end( $paths ),
		);

		switch ( true ) {
			case ( in_array( 'wp-content', $paths, true ) && in_array( 'plugins', $paths, true ) ):
				$file_type     = 'plugin';
				$content_path .= '/plugins/';
				break;
			case ( in_array( 'wp-content', $paths, true ) && in_array( 'themes', $paths, true ) ):
				$file_type     = 'theme';
				$content_path .= '/themes/';
				break;
		}

		$rel_path  = str_replace( $content_path, '', $path );
		$rel_paths = explode( '/', $rel_path );

		if ( 'core' !== $file_type ) {
			$file_slug = $rel_paths[0];
		}

		$url_args[] = 'file-reference=' . $file_slug;
		$url_args[] = 'file-type=' . $file_type;
		$url_args[] = 'string-locator-line=' . absint( $line );
		$url_args[] = 'string-locator-linepos=' . absint( $linepos );
		$url_args[] = 'string-locator-path=' . urlencode( str_replace( '/', DIRECTORY_SEPARATOR, $path ) );

		$url = admin_url( $this->path_to_use . '?' . implode( '&', $url_args ) );

		return $url;
	}

}
