<?php

namespace StringLocator\REST;

use StringLocator\Base\REST;

class Clean extends REST {

	protected $rest_base = 'clean';

	public function __construct() {
		parent::__construct();
	}

	public function register_rest_route() {
		register_rest_route(
			$this->namespace,
			$this->rest_base,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'clean' ),
				'permission_callback' => array( $this, 'permission_callback' ),
			)
		);
	}

	public function clean() {
		$scan_data = get_transient( 'string-locator-search-overview' );
		for ( $i = 0; $i < $scan_data->chunks; $i ++ ) {
			delete_transient( 'string-locator-search-files-' . $i );
		}

		wp_send_json_success( true );
	}

}

new Clean();
