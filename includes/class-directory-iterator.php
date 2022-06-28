<?php

namespace StringLocator;

class Directory_Iterator {

	/**
	 * Directory that will be searched.
	 *
	 * @var
	 */
	private $directory;

	/**
	 * The search term being used.
	 *
	 * @var
	 */
	private $search;

	/**
	 * A check if regex searches are enabled or not.
	 *
	 * @var
	 */
	private $regex;

	/**
	 * DirectoryIterator constructor.
	 *
	 * @param $directory
	 * @param $search
	 * @param $regex
	 *
	 * @return array
	 */
	public function __construct( $directory, $search, $regex ) {
		$this->directory = $directory;
		$this->search    = $search;
		$this->regex     = $regex;
	}

	/**
	 * Build the folder structure.
	 *
	 * @return array
	 */
	public function get_structure() {
		$scan_path = $this->prepare_scan_path( $this->directory );

		if ( is_file( $scan_path->path ) ) {
			$files = array( $scan_path->path );
		} else {
			$files = $this->ajax_scan_path( $scan_path->path );
		}

		/*
		 * Make sure each chunk of file arrays never exceeds 500 files
		 * This is to prevent the SQL string from being too large and crashing everything
		 */
		$back_compat_filter = apply_filters( 'string-locator-files-per-array', 500 ); //phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

		$file_chunks = array_chunk( $files, apply_filters( 'string_locator_files_per_array', $back_compat_filter ), true );

		$store = (object) array(
			'scan_path' => $scan_path,
			'search'    => $this->search,
			'directory' => $this->directory,
			'chunks'    => count( $file_chunks ),
			'regex'     => $this->regex,
		);

		$response = array(
			'total'     => count( $files ),
			'current'   => 0,
			'directory' => $scan_path,
			'chunks'    => count( $file_chunks ),
			'regex'     => $this->regex,
		);

		set_transient( 'string-locator-search-overview', $store );
		update_option( 'string-locator-search-history', array(), false );

		foreach ( $file_chunks as $count => $file_chunk ) {
			set_transient( 'string-locator-search-files-' . $count, $file_chunk );
		}

		return $response;
	}

	/**
	 * Parse the search option to determine what kind of search we are performing and what directory to start in.
	 *
	 * @param string $option The search-type identifier.
	 *
	 * @return bool|object
	 */
	private function prepare_scan_path( $option ) {
		$data = array(
			'path' => '',
			'type' => '',
			'slug' => '',
		);

		switch ( true ) {
			case ( 't--' === $option ):
				$data['path'] = WP_CONTENT_DIR . '/themes/';
				$data['type'] = 'theme';
				break;
			case ( strlen( $option ) > 3 && 't-' === substr( $option, 0, 2 ) ):
				$data['path'] = WP_CONTENT_DIR . '/themes/' . substr( $option, 2 );
				$data['type'] = 'theme';
				$data['slug'] = substr( $option, 2 );
				break;
			case ( 'p--' === $option ):
				$data['path'] = WP_CONTENT_DIR . '/plugins/';
				$data['type'] = 'plugin';
				break;
			case ( 'mup--' === $option ):
				$data['path'] = WP_CONTENT_DIR . '/mu-plugins/';
				$data['type'] = 'mu-plugin';
				break;
			case ( strlen( $option ) > 3 && 'p-' === substr( $option, 0, 2 ) ):
				$slug = explode( '/', substr( $option, 2 ) );

				$data['path'] = WP_CONTENT_DIR . '/plugins/' . $slug[0];
				$data['type'] = 'plugin';
				$data['slug'] = $slug[0];
				break;
			case ( 'core' === $option ):
				$data['path'] = ABSPATH;
				$data['type'] = 'core';
				break;
			case ( 'wp-content' === $option ):
				$data['path'] = WP_CONTENT_DIR;
				$data['type'] = 'core';
				break;
		}

		if ( empty( $data['path'] ) ) {
			return false;
		}

		return (object) $data;
	}

	private function ajax_scan_path( $path ) {
		$files = array();

		$paths = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $path ),
			\RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ( $paths as $name => $location ) {
			if ( is_dir( $location->getPathname() ) ) {
				continue;
			}

			$files[] = $location->getPathname();
		}

		return $files;
	}

}
