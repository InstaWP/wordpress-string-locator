<?php
/**
 * Base class for handling Search requests.
 */

namespace StringLocator\Base;

/**
 * Search class.
 */
class Search {
	/**
	 * The server-configured max time a script can run.
	 *
	 * @var int
	 */
	protected $max_execution_time = null;

	/**
	 * The current time when our script started executing.
	 *
	 * @var float
	 */
	protected $start_execution_timer = 0;

	/**
	 * The server-configured max amount of memory a script can use.
	 *
	 * @var int
	 */
	protected $max_memory_consumption = 0;

	/**
	 * The path to the currently editable file.
	 *
	 * @var string
	 */
	protected $path_to_use = '';

	/**
	 * Class constructor.
	 */
	public function __construct() {
		/**
		 * Define class variables requiring expressions
		 */
		$this->path_to_use = ( is_multisite() ? 'network/admin.php' : 'tools.php' );

		$this->max_execution_time    = absint( ini_get( 'max_execution_time' ) );
		$this->start_execution_timer = microtime( true );

		if ( $this->max_execution_time > 30 ) {
			$this->max_execution_time = 30;
		}

		$this->set_memory_limit();

		add_action( 'string_locator_search_templates', array( $this, 'add_search_response_template' ) );
	}

	/**
	 * Load an underscores template file to be used in the search response.
	 *
	 * @return void
	 */
	public function add_search_response_template() {
		require_once STRING_LOCATOR_PLUGIN_DIR . '/views/templates/search-default.php';
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
}
