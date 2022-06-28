<?php
/**
 * Class for testing serialized data on SQL saves.
 */

namespace StringLocator\Extensions\SQL\Tests;

/**
 * Serialized_Data class.
 */
class Serialized_Data {

	/**
	 * The content that will be scanned.
	 *
	 * @var string
	 */
	private $content = '';

	/**
	 * An array holding any errors returned during testing.
	 *
	 * @var array
	 */
	public $errors = array();

	/**
	 * SmartScan constructor.
	 */
	public function __construct() {
		add_action( 'string_locator_editor_checks', array( $this, 'print_checks_option' ) );

		add_filter( 'string_locator_pre_save', array( $this, 'maybe_perform_test' ), 10, 2 );
		add_filter( 'string_locator_pre_save_fail_notice', array( $this, 'return_failure_notices' ) );
	}

	/**
	 * Combine reported errors with any new notices.
	 *
	 * @param $notices
	 *
	 * @return mixed
	 */
	public function return_failure_notices( $notices ) {
		if ( empty( $this->errors ) ) {
			return $notices;
		}

		return array_merge(
			$notices,
			$this->errors
		);
	}

	/**
	 * Conditionally run the test tool.
	 *
	 * @param bool   $can_save A boolean value if the test has passed or failed.
	 * @param string $content  The content being modified.
	 *
	 * @return bool|mixed|null
	 */
	public function maybe_perform_test( $can_save, $content ) {
		// If another addon has determined the file can not be saved, bail early.
		if ( ! $can_save ) {
			return $can_save;
		}

		// Do not perform a smart scan if the option for it is disabled.
		if ( ! isset( $_POST['string-locator-validate-serialized-data'] ) ) {
			return $can_save;
		}

		return $this->run( $content );
	}

	/**
	 * Output a checkbox to enable or disable this feature from within the editor interface.
	 *
	 * @return void
	 */
	public function print_checks_option() {
		if ( ! isset( $_GET['file-type'] ) || 'sql' !== $_GET['file-type'] ) {
			return;
		}
		?>

		<div class="row">
			<label>
				<input type="checkbox" name="string-locator-validate-serialized-data" checked="checked">
				<?php esc_html_e( 'If the SQL data is serialized, validate it before saving.', 'string-locator' ); ?>
			</label>
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
	 * @param string $content The content to scan.
	 *
	 * @return bool
	 */
	public function run( $content ) {
		$this->content = $content;

		// Reset the stored errors for a fresh run.
		$this->errors = array();

		if ( \is_serialized( $this->content ) ) {
			$test_data = @unserialize( $this->content );

			if ( 'b:0;' !== $this->content && false === $test_data ) {
				$this->errors[] = array(
					'type'    => 'error',
					'message' => __( 'The data is meant to be serialized with PHP, but is no longer valid.', 'string-locator' ),
				);
			}
		}

		if ( ! empty( $this->errors ) ) {
			return false;
		}

		return true;
	}
}

new Serialized_Data();
