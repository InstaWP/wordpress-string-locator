<?php

namespace StringLocator;

use StringLocator\Tests\Loopback;
use StringLocator\Tests\Smart_Scan;

class Save {

	/**
	 * An array of notices to send back to the user.
	 *
	 * @var array
	 */
	public $notice = array();

	/**
	 * Save constructor.
	 */
	public function __construct() {}

	/**
	 * Handler for storing the content of the code editor.
	 *
	 * Also runs over the Smart-Scan if enabled.
	 *
	 * @return void|array
	 */
	public function save( $save_params ) {
		$_POST = $save_params;

		if ( String_Locator::is_valid_location( $_POST['string-locator-path'] ) ) {
			$path    = urldecode( $_POST['string-locator-path'] );
			$content = $_POST['string-locator-editor-content'];

			/**
			 * Send an error notice if the file isn't writable
			 */
			if ( ! is_writeable( $path ) ) {
				$this->notice[] = array(
					'type'    => 'error',
					'message' => __( 'The file could not be written to, please check file permissions or edit it manually.', 'string-locator' ),
				);

				return array(
					'notices' => $this->notice,
				);
			}

			/**
			 * Filter if the save process should be performed or not.
			 *
			 * @attr bool   $can_save Can the save be carried out.
			 * @attr string $content  The content to save.
			 * @attr string $path     Path to the file being edited.
			 */
			$can_save = apply_filters( 'string_locator_pre_save', true, $content, $path );

			if ( ! $can_save ) {
				return array(
					'notices' => apply_filters( 'string_locator_pre_save_fail_notice', array() ),
				);
			}

			$original = file_get_contents( $path );

			$this->write_file( $path, $content );

			/**
			 * Filter if the save process completed as it should or if warnings should be returned.
			 *
			 * @attr bool   $save_successful Boolean indicating if the save was successful.
			 * @attr string $content         The edited content.
			 * @attr string $original        The original content.
			 * @attr string $path            The path to the file being edited.
			 */
			$save_successful = apply_filters( 'string_locator_post_save', true, $content, $original, $path );

			/**
			 * Check the status of the site after making our edits.
			 * If the site fails, revert the changes to return the sites to its original state
			 */
			if ( ! $save_successful ) {
				$this->write_file( $path, $original );

				return array(
					'notices' => apply_filters( 'string_locator_post_save_fail_notice', array() ),
				);
			}

			return array(
				'notices' => array(
					array(
						'type'    => 'success',
						'message' => __( 'The file has been saved', 'string-locator' ),
					),
				),
			);
		} else {
			return array(
				'notices' => array(
					array(
						'type'    => 'error',
						'message' => sprintf(
						// translators: %s: The file location that was sent.
							__( 'The file location provided, <strong>%s</strong>, is not valid.', 'string-locator' ),
							$_POST['string-locator-path']
						),
					),
				),
			);
		}
	}

	/**
	 * When editing a file, this is where we write all the new content.
	 * We will break early if the user isn't allowed to edit files.
	 *
	 * @param string $path The path to the file.
	 * @param string $content The content to write to the file.
	 *
	 * @return void
	 */
	private function write_file( $path, $content ) {
		if ( ! current_user_can( String_Locator::$default_capability ) ) {
			return;
		}

		// Verify the location is valid before we try using it.
		if ( ! String_Locator::is_valid_location( $path ) ) {
			return;
		}

		$back_compat_filter = apply_filters( 'string-locator-filter-closing-php-tags', true ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

		if ( apply_filters( 'string_locator_filter_closing_php_tags', $back_compat_filter ) ) {
			$content = preg_replace( '/\?>$/si', '', trim( $content ), - 1, $replaced_strings );

			if ( $replaced_strings >= 1 ) {
				$this->notice[] = array(
					'type'    => 'error',
					'message' => __( 'We detected a PHP code tag ending, this has been automatically stripped out to help prevent errors in your code.', 'string-locator' ),
				);
			}
		}

		$file        = fopen( $path, 'w' );
		$lines       = explode( "\n", str_replace( array( "\r\n", "\r" ), "\n", $content ) );
		$total_lines = count( $lines );

		for ( $i = 0; $i < $total_lines; $i ++ ) {
			$write_line = $lines[ $i ];

			if ( ( $i + 1 ) < $total_lines ) {
				$write_line .= PHP_EOL;
			}

			fwrite( $file, $write_line );
		}

		fclose( $file );
	}
}
