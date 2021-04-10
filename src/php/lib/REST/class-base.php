<?php

namespace JITS\StringLocator\REST;

class Base extends \WP_REST_Controller {

	protected $namespace = 'string-locator/v1';

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_rest_route' ) );
	}

	public function permission_callback() {
		return current_user_can( 'edit_themes' );
	}

}
