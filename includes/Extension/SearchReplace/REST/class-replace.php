<?php
/**
 * REST API endpoints for the replacement module.
 */

namespace StringLocator\Extension\SearchReplace\REST;

use StringLocator\Base\REST;
use StringLocator\Extension\SearchReplace\Replace\File;
use StringLocator\Extension\SearchReplace\Replace\SQL;

/**
 * Replace class.
 */
class Replace extends REST {

	protected $rest_base = 'replace';

	/**
	 * Class constructor.
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Register custom REST API endpoints.
	 *
	 * @return void
	 */
	public function register_rest_route() {
		register_rest_route(
			$this->namespace,
			$this->rest_base,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'replace' ),
				'permission_callback' => array( $this, 'permission_callback' ),
			)
		);
	}

	/**
	 * REST API handler for the replacement endpoint.
	 *
	 * @param \WP_REST_Request $request A dynamic request object depending on the data type being replaced.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function replace( \WP_REST_Request $request ) {
		$replace_nonce = $request->get_param( 'replace_nonce' );

		if ( ! $replace_nonce || ! wp_verify_nonce( $replace_nonce, 'string-locator-replace' ) ) {
			return new \WP_Error( 'invalid_nonce', __( 'Invalid nonce.', 'string-locator' ), array( 'status' => 400 ) );
		}

		// Ensure the regex flag is a boolean value.
		$is_regex = $request->get_param( 'search_regex' );
		if ( ! is_bool( $is_regex ) ) {
			if ( 'false' === $is_regex ) {
				$is_regex = false;
			} else {
				$is_regex = true;
			}
		}

		$check_loopback = $request->get_param( 'replace_loopback' );
		if ( ! is_bool( $check_loopback ) ) {
			if ( 'false' === $check_loopback ) {
				$check_loopback = false;
			} else {
				$check_loopback = true;
			}
		}

		switch ( $request->get_param( 'type' ) ) {
			case 'sql':
				$handler = new SQL(
					$request->get_param( 'primaryColumn' ),
					$request->get_param( 'primaryKey' ),
					$request->get_param( 'primaryType' ),
					$request->get_param( 'tableName' ),
					$request->get_param( 'columnName' ),
					$request->get_param( 'search_string' ),
					$request->get_param( 'replace_string' ),
					$is_regex
				);
				break;
			case 'file':
			default:
				$handler = new File(
					$request->get_param( 'filename' ),
					$request->get_param( 'linenum' ),
					$request->get_param( 'search_string' ),
					$request->get_param( 'replace_string' ),
					$is_regex
				);
		}

		if ( ! $handler->validate() ) {
			return new \WP_Error( 'invalid_request', __( 'Invalid request', 'string-locator' ), array( 'status' => 400 ) );
		}

		$replace = $handler->replace();

		if ( is_wp_error( $replace ) ) {
			return $replace;
		}

		// Basic check to ensure the site is not broken after the modifications.
		if ( $check_loopback ) {
			$urls = array(
				get_site_url( null, '/' ),
				get_admin_url( null, '/' ),
			);

			foreach ( $urls as $url ) {
				$loopback = wp_remote_head( $url );

				if ( is_wp_error( $loopback ) ) {
					$handler->restore();

					return new \WP_Error(
						'loopback_failed',
						sprintf(
							// translators: 1: The URL being requested.
							__( 'The address `%s` could not be loaded after the edits were made, and the changes were therefore reverted.', 'string-locator' ),
							esc_html( $url )
						),
						array(
							'status' => 400,
						)
					);
				}

				$response_code = wp_remote_retrieve_response_code( $loopback );
				if ( (int) substr( $response_code, 0, 1 ) > 3 ) {
					$handler->restore();

					return new \WP_Error(
						'loopback_failed',
						sprintf(
							// translators: 1: The URL being requested. 2: The HTTP status code returned.
							__( 'The address `%1$s` returned an error code (%2$s), and the changes were therefore reverted.', 'string-locator' ),
							esc_html( $url ),
							esc_html( $response_code )
						),
						array(
							'status' => 400,
						)
					);
				}
			}
		}

		/*
		 * A `true` response means no errors occurred, but also no replacements/updated were made.
		 */
		if ( true !== $replace ) {
			$string_preview = sprintf(
				'%s<div class="row-actions"><span class="edit"><a href="%s">%s</a></span></div>',
				$replace,
				$handler->get_edit_url(),
				esc_html__( 'Edit', 'string-locator' )
			);
		} else {
			$string_preview = true;
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => array(
					'replace_string' => $string_preview,
				),
			),
			200
		);
	}
}

new Replace();
