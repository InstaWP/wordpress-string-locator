<?php

namespace JITS\StringLocator\REST;

class Save extends Base {

	protected $rest_base = 'save';

	public function __construct() {
		parent::__construct();
	}

	public function register_rest_route() {
		register_rest_route(
			$this->namespace,
			$this->rest_base,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'save' ),
				'permission_callback' => array( $this, 'permission_callback' ),
			)
		);
	}

	public function save( \WP_REST_Request $request ) {
		$handler = new \JITS\StringLocator\Save();

		/**
		 * Filter the save handler used to perform edits.
		 *
		 * @attr object $handler The handler performing the save.
		 */
		$handler = apply_filters( 'string_locator_save_handler', $handler );

		return $handler->save( $request->get_params() );
	}

}

new Save();
