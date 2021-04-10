<?php

namespace JITS\StringLocator\Tests;

class Loopback {
	/**
	 * An array of HTTP status codes that will trigger the rollback feature.
	 *
	 * @var string[]
	 */
	private $bad_http_codes = array( '500' );

	/**
	 * An array holding any errors returned during testing.
	 *
	 * @var array
	 */
	public $errors = array();

	/**
	 * Loopback constructor.
	 */
	public function __construct() {}

	/**
	 * A helper function to return any errors.
	 *
	 * @return array
	 */
	public function get_errors() {
		return $this->errors;
	}

	/**
	 * Main test runner.
	 *
	 * @return bool
	 */
	public function run() {
		$this->bad_http_codes = apply_filters( 'string_locator_bad_http_codes', $this->bad_http_codes );

		// Clear the error log before doing a new request.
		$this->errors = array();

		$frontend = $this->test_frontend();

		// If the frontend has a loopback error, return it immediately.
		if ( ! $frontend ) {
			return false;
		}

		// If frontend tests are fine, we return the result of the backend scan.
		return $this->test_backend();
	}

	private function test_frontend() {
		$header = wp_remote_head( site_url() );

		// If we get redirected, follow the redirect.
		if ( ! is_wp_error( $header ) && 301 === (int) $header['response']['code'] ) {
			$header = wp_remote_head( $header['headers']['location'] );
		}

		if ( is_wp_error( $header ) ) {
			if ( 'http_request_failed' === $header->get_error_code() ) {
				$this->errors[] = array(
					'type'    => 'error',
					'message' => __( 'Your changes were not saved, as a check of your site could not be completed afterwards. This may be due to a <a href="https://wordpress.org/support/article/loopbacks/">loopback</a> error.', 'string-locator' ),
				);

				return false;
			}

			// Fallback error message here.
			$this->errors[] = array(
				'type'    => 'error',
				'message' => $header->get_error_message(),
			);

			return false;
		}

		if ( in_array( $header['response']['code'], $this->bad_http_codes, true ) ) {
			$this->errors[] = array(
				'type'    => 'error',
				'message' => __( 'A 500 server error was detected on your site after updating your file. We have restored the previous version of the file for you.', 'string-locator' ),
			);

			return false;
		}

		return true;
	}

	private function test_backend() {
		$header = wp_remote_head( admin_url() );

		// If we get redirected, follow the redirect.
		if ( ! is_wp_error( $header ) && 301 === (int) $header['response']['code'] ) {
			$header = wp_remote_head( $header['headers']['location'] );
		}

		if ( is_wp_error( $header ) ) {
			if ( 'http_request_failed' === $header->get_error_code() ) {
				$this->errors[] = array(
					'type'    => 'error',
					'message' => __( 'Your changes were not saved, as a check of your sites backend could not be completed afterwards. This may be due to a <a href="https://wordpress.org/support/article/loopbacks/">loopback</a> error.', 'string-locator' ),
				);

				return false;
			}

			// Fallback error message here.
			$this->errors[] = array(
				'type'    => 'error',
				'message' => $header->get_error_message(),
			);

			return false;
		}

		if ( in_array( $header['response']['code'], $this->bad_http_codes, true ) ) {
			$this->errors[] = array(
				'type'    => 'error',
				'message' => __( 'A 500 server error was detected on your sites backend after updating your file. We have restored the previous version of the file for you.', 'string-locator' ),
			);

			return false;
		}

		return true;
	}
}
