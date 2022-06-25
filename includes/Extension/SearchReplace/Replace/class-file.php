<?php

namespace JITS\StringLocator\Extension\SearchReplace\Replace;

use JITS\StringLocator\Search;
use JITS\StringLocator\String_Locator;

class File {

	private $file;
	private $line;
	private $regex;
	private $new_string;
	private $old_string;
	private $original_string;

	private $search;

	public function __construct( $file, $line, $old_string, $new_string, $regex = false ) {
		$this->file       = trailingslashit( ABSPATH ) . $file;
		$this->line       = ( absint( $line ) - 1 );
		$this->regex      = $regex;
		$this->old_string = $old_string;
		$this->new_string = $new_string;

		$this->search = new Search();
	}

	public function validate() {
		return String_Locator::is_valid_location( $this->file );
	}

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

		return String_Locator::create_preview( $file_contents[ $this->line ], $this->new_string, $this->regex );
	}

	public function restore() {
		$file_contents[ $this->line ] = $this->original_string;

		$file = fopen( $this->file, 'w' );

		foreach ( $file_contents as $file_line ) {
			fwrite( $file, $file_line );
		}

		return true;
	}

	public function get_edit_url( $path, $line, $linepos ) {
		return $this->search->create_edit_link( $path, $line, $linepos );
	}
}
