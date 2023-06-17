<?php

namespace StringLocator\REST;

use StringLocator\Base\REST;

class Save extends REST {

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
		$handler = new \StringLocator\Save();

		/**
		 * Filters the REST Request parameter values that will be used for the save call.
		 *
		 * @param array $params REST Request parameters.
		 */
		$params = apply_filters( 'string_locator_save_params', $request->get_params() );

		/**
		 * Filter the save handler used to perform edits.
		 *
		 * @attr object $handler The handler performing the save.
		 */
		$handler = apply_filters( 'string_locator_save_handler', $handler );

		/**
		 * Trigger an action before the save has been performed.
		 *
		 * @attr array $params The parameters used to perform the save.
		 */
		do_action( 'string_locator_pre_save_action', $params );

		$save_result = $handler->save( $params );

		/**
		 * Trigger an action after the save has been performed.
		 *
		 * @attr array $save_result The result of the save.
		 * @attr array $params The parameters used to perform the save.
		 */
		do_action( 'string_locator_post_save_action', $save_result, $params );

		return $save_result;
	}

}

new Save();
