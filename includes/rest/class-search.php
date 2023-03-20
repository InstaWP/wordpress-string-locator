<?php

namespace StringLocator\REST;

use StringLocator\Base\REST;
use StringLocator\String_Locator;

class Search extends REST {

	protected $rest_base = 'search';

	public function __construct() {
		parent::__construct();
	}

	public function register_rest_route() {
		register_rest_route(
			$this->namespace,
			$this->rest_base,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'perform_search' ),
				'permission_callback' => array( $this, 'permission_callback' ),
			)
		);
	}

	public function permission_callback() {
		return current_user_can( String_Locator::$search_capability );
	}

	public function perform_search( \WP_REST_Request $request ) {
		$handler = new \StringLocator\Search();

		/**
		 * Filter the search handler used to find strings.
		 *
		 * @attr object           $handler The handler performing searches.
		 * @attr \WP_REST_Request $request The request received by the REST API handler.
		 */
		$handler = apply_filters( 'string_locator_search_handler', $handler, $request );

		return array(
			'success' => true,
			'data'    => $handler->run( $request->get_param( 'filenum' ) ),
		);
	}

}

new Search();
