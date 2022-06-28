<?php
/**
 * Class for handling replacements in files.
 */

namespace StringLocator\Extension\SearchReplace\Replace;

use StringLocator\Search;
use StringLocator\String_Locator;

/**
 * File class.
 */
class File {

	private $file;
	private $line;
	private $regex;
	private $new_string;
	private $old_string;
	private $original_string;

	private $search;

	/**
	 * Class constructor.
	 *
	 * @param string $file       The relative path from the WordPress root to the file being edited.
	 * @param int    $line       The line in the file containing the string to be replaced.
	 * @param string $old_string The string to be replaced.
	 * @param string $new_string The string to be added.
	 * @param bool   $regex      Is the search string a regex string.
	 */
	public function __construct( $file, $line, $old_string, $new_string, $regex = false ) {
		$this->file       = trailingslashit( ABSPATH ) . $file;
		$this->line       = ( absint( $line ) - 1 );
		$this->regex      = $regex;
		$this->old_string = $old_string;
		$this->new_string = $new_string;

		$this->search = new Search();
	}

	/**
	 * Validate that the file is in a location that does not allow directory traversals.
	 *
	 * @return bool
	 */
	public function validate() {
		return String_Locator::is_valid_location( $this->file );
	}

	/**
	 * Run the replacement function.
	 *
	 * @return bool|string|\WP_Error
	 */
	public function replace() {
		// A value of 0 or lower indicates a filename or similar matched, and these should NOT be replaced.
		if ( $this->line < 0 ) {
			return true;
		}

		$file_contents = file( $this->file );

		if ( ! $file_contents ) {
			return new \WP_Error( 'file_inaccessible', __( 'The file could not be read.', 'string-locator' ), array( 'status' => 400 ) );
		}

		$this->original_string = $file_contents[ $this->line ];

		if ( $this->regex ) {
			$file_contents[ $this->line ] = preg_replace( $this->old_string, $this->new_string, $file_contents[ $this->line ] );
		} else {
			$file_contents[ $this->line ] = str_ireplace( $this->old_string, $this->new_string, $file_contents[ $this->line ] );
		}

		$file = fopen( $this->file, 'w' );

		if ( ! $file ) {
			return new \WP_Error( 'file_inaccessible', __( 'The file could not be written.', 'string-locator' ), array( 'status' => 400 ) );
		}

		foreach ( $file_contents as $file_line ) {
			fwrite( $file, $file_line );
		}

		fclose( $file );

		return String_Locator::create_preview( $file_contents[ $this->line ], $this->new_string, $this->regex );
	}

	/**
	 * Restore the last ran modification.
	 *
	 * @return bool
	 */
	public function restore() {
		$file_contents = file( $this->file );

		$file_contents[ $this->line ] = $this->original_string;

		$file = fopen( $this->file, 'w' );

		foreach ( $file_contents as $file_line ) {
			fwrite( $file, $file_line );
		}

		fclose( $file );

		return true;
	}

	/**
	 * Return the URL for the editor interface for this file.
	 *
	 * @return string
	 */
	public function get_edit_url() {
		return $this->search->create_edit_link( $this->file, $this->line, 0 );
	}
}
