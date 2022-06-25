<?php

namespace JITS\StringLocator\Extension\SearchReplace\REST;

use JITS\StringLocator\Base\REST;
use JITS\StringLocator\Extension\SearchReplace\Replace\File;
use JITS\StringLocator\Extension\SearchReplace\Replace\SQL;

class Replace extends REST {

	protected $rest_base = 'replace';

	public function __construct() {
		parent::__construct();
	}

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
