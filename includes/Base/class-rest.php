<?php
/**
 * Base class for all REST API controllers.
 */

namespace StringLocator\Base;

use StringLocator\String_Locator;

/**
 * Base REST class.
 */
class REST extends \WP_REST_Controller {

	protected $namespace = 'string-locator/v1';

	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_rest_route' ) );
	}

	/**
	 * Generic helper function to check if the current user
	 * meets the minimum access requirements of the plugin.
	 *
	 * @return bool
	 */
	public function permission_callback() {
		return current_user_can( String_Locator::$search_capability );
	}

}
