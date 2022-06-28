<?php

namespace StringLocator\Tests;

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
	public function __construct() {
		add_action( 'string_locator_editor_checks', array( $this, 'print_checks_option' ) );

		add_filter( 'string_locator_post_save', array( $this, 'maybe_perform_test' ) );
		add_filter( 'string_locator_post_save_fail_notice', array( $this, 'return_failure_notices' ) );
	}

	public function return_failure_notices( $notices ) {
		if ( empty( $this->errors ) ) {
			return $notices;
		}

		return array_merge(
			$notices,
			$this->errors
		);
	}

	public function maybe_perform_test( $save_successful ) {
		// If another addon has determined the save is a failure, don't perform the test.
		if ( ! $save_successful ) {
			return $save_successful;
		}

		// Do not run this check if it has been disabled.
		if ( ! isset( $_POST['string-locator-loopback-check'] ) ) {
			return $save_successful;
		}

		return $this->run();
	}

	public function print_checks_option() {
		?>

		<div class="row">
			<label>
				<input type="checkbox" name="string-locator-loopback-check" checked="checked">
				<?php esc_html_e( 'Enable loopback tests after making a save.', 'string-locator' ); ?>
			</label>
			<br>
			<em>
				<?php esc_html_e( 'This feature is highly recommended, and is what WordPress does when using the plugin- or theme-editor.', 'string-locator' ); ?>
			</em>
		</div>

		<?php
	}

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

new Loopback();
