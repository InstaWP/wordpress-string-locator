<?php

namespace StringLocator\REST;

use StringLocator\Base\REST;
use StringLocator\Directory_Iterator;
use StringLocator\String_Locator;

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

	public function permission_callback() {
		return current_user_can( String_Locator::$search_capability );
	}

	public function get_structure( \WP_REST_Request $request ) {
		$short_circuit = apply_filters( 'string_locator_directory_iterator_short_circuit', array(), $request );

		if ( ! empty( $short_circuit ) ) {
			return $short_circuit;
		}

		$data = json_decode( $request->get_param( 'data' ) );

		$directory = $data->directory;
		$search    = $data->search;
		$regex     = $data->regex;

		// Validate the search path to avoid unintended directory traversal.
		if ( 0 !== validate_file( $directory ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'data'    => __( 'Invalid search source provided.', 'string-locator' ),
				),
				400
			);
		}

		$iterator = new Directory_Iterator(
			$directory,
			$search,
			$regex
		);

		return array(
			'success' => true,
			'data'    => $iterator->get_structure(),
		);
	}

}

new Directory_Structure();
