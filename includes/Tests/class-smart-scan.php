<?php

namespace StringLocator\Tests;

class Smart_Scan {

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

	public function return_failure_notices( $notices ) {
		if ( empty( $this->errors ) ) {
			return $notices;
		}

		return array_merge(
			$notices,
			$this->errors
		);
	}

	public function maybe_perform_test( $can_save, $content ) {
		// If another addon has determined the file can not be saved, bail early.
		if ( ! $can_save ) {
			return $can_save;
		}

		// Do not perform a smart scan if the option for it is disabled.
		if ( ! isset( $_POST['string-locator-smart-edit'] ) ) {
			return $can_save;
		}

		return $this->run( $content );
	}

	public function print_checks_option() {
		?>

		<div class="row">
			<label>
				<input type="checkbox" name="string-locator-smart-edit" checked="checked">
				<?php esc_html_e( 'Enable a smart-scan of your code to help detect bracket mismatches before saving.', 'string-locator' ); ?>
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

		$this->check_braces();
		$this->check_brackets();
		$this->check_parenthesis();

		if ( ! empty( $this->errors ) ) {
			return false;
		}

		return true;
	}

	private function check_braces() {
		$open_brace  = substr_count( $this->content, '{' );
		$close_brace = substr_count( $this->content, '}' );

		if ( $open_brace !== $close_brace ) {
			$opened = $this->compare( '{', '}' );

			foreach ( $opened as $line ) {
				$this->errors[] = array(
					'type'    => 'error',
					'message' => sprintf(
						// translators: 1: Line number with an error.
						__( 'There is an inconsistency in the opening and closing braces, { and }, of your file on line %s', 'string-locator' ),
						'<a href="#" class="string-locator-edit-goto" data-goto-line="' . ( $line + 1 ) . '">' . ( $line + 1 ) . '</a>'
					),
				);
			}

			return false;
		}

		return true;
	}

	private function check_brackets() {
		$open_bracket  = substr_count( $this->content, '[' );
		$close_bracket = substr_count( $this->content, ']' );

		if ( $open_bracket !== $close_bracket ) {
			$opened = $this->compare( '[', ']' );

			foreach ( $opened as $line ) {
				$this->errors[] = array(
					'type'    => 'error',
					'message' => sprintf(
						// translators: 1: Line number with an error.
						__( 'There is an inconsistency in the opening and closing braces, [ and ], of your file on line %s', 'string-locator' ),
						'<a href="#" class="string-locator-edit-goto" data-goto-line="' . ( $line + 1 ) . '">' . ( $line + 1 ) . '</a>'
					),
				);
			}

			return false;
		}

		return true;
	}

	private function check_parenthesis() {
		$open_parenthesis  = substr_count( $this->content, '(' );
		$close_parenthesis = substr_count( $this->content, ')' );

		if ( $open_parenthesis !== $close_parenthesis ) {
			$this->failed_edit = true;

			$opened = $this->compare( '(', ')' );

			foreach ( $opened as $line ) {
				$this->errors[] = array(
					'type'    => 'error',
					'message' => sprintf(
					// translators: 1: Line number with an error.
						__( 'There is an inconsistency in the opening and closing braces, ( and ), of your file on line %s', 'string-locator' ),
						'<a href="#" class="string-locator-edit-goto" data-goto-line="' . ( $line + 1 ) . '">' . ( $line + 1 ) . '</a>'
					),
				);
			}

			return false;
		}

		return true;
	}

	/**
	 * Check for inconsistencies in brackets and similar.
	 *
	 * @param string $start Start delimited.
	 * @param string $end   End delimiter.
	 *
	 * @return array
	 */
	function compare( $start, $end ) {
		$opened = array();

		$lines = explode( "\n", $this->content );
		for ( $i = 0; $i < count( $lines ); $i ++ ) {
			if ( stristr( $lines[ $i ], $start ) ) {
				$opened[] = $i;
			}
			if ( stristr( $lines[ $i ], $end ) ) {
				array_pop( $opened );
			}
		}

		return $opened;
	}

}

new Smart_Scan();
