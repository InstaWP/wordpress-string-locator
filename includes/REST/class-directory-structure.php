<?php

namespace JITS\StringLocator\REST;

use JITS\StringLocator\Base\REST;
use JITS\StringLocator\Directory_Iterator;

class Directory_Structure extends REST {

	protected $rest_base = 'get-directory-structure';

	public function __construct() {
		parent::__construct();
	}

	public function register_rest_route() {
		register_rest_route(
			$this->namespace,
			$this->rest_base,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'get_structure' ),
				'permission_callback' => array( $this, 'permission_callback' ),
			)
		);
	}

	public function get_structure( \WP_REST_Request $request ) {
		// Validate the search path to avoid unintended directory traversal.
		if ( 0 !== validate_file( $request->get_param( 'directory' ) ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'data'    => __( 'Invalid search source provided.', 'string-locator' ),
				),
				400
			);
		}

		$iterator = new Directory_Iterator(
			$request->get_param( 'directory' ),
			$request->get_param( 'search' ),
			$request->get_param( 'regex' )
		);

		return array(
			'success' => true,
			'data'    => $iterator->get_structure(),
		);
	}

}

new Directory_Structure();
