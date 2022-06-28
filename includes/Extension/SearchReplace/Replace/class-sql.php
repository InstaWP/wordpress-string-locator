<?php
/**
 * Handle replacements in SQL strings.
 *
 * This file borrows and adapts functions and concepts from the
 * interconnect.it Search-Replace script (https://github.com/interconnectit/Search-Replace-DB).
 */

namespace StringLocator\Extension\SearchReplace\Replace;

use StringLocator\Extension\SQL\Search;
use StringLocator\String_Locator;
use function StringLocator\Extension\SQL\validate_sql_fields;

/**
 * SQL class.
 */
class SQL {

	private $table_name;
	private $primary_column;
	private $primary_key;
	private $primary_type;
	private $column_name;
	private $regex;
	private $new_string;
	private $old_string;
	private $original_string;

	private $search;

	/**
	 * Class constructor.
	 *
	 * @param string $primary_column The name of the primary column of the entry being edited.
	 * @param string $primary_key    The key identified of the primary column.
	 * @param string $primary_type   The type of the primary column (`int` or `string`).
	 * @param string $table_name     The name of the table to perform an edit within.
	 * @param string $column_name    The column being edited.
	 * @param string $old_string     The string to be replaced.
	 * @param string $new_string     The string to be added.
	 * @param bool   $regex          Is the search string a regex string.
	 */
	public function __construct( $primary_column, $primary_key, $primary_type, $table_name, $column_name, $old_string, $new_string, $regex = false ) {
		$this->primary_column = $primary_column;
		$this->primary_key    = $primary_key;
		$this->primary_type   = $primary_type;
		$this->table_name     = $table_name;
		$this->column_name    = $column_name;
		$this->regex          = $regex;
		$this->old_string     = $old_string;
		$this->new_string     = $new_string;

		$this->search = new Search();
	}

	/**
	 * Validate that the only non-escaped strings are alpha-numeric to avoid SQL injections.
	 *
	 * @return bool
	 */
	public function validate() {
		if ( ! validate_sql_fields( $this->primary_column ) ) {
			return false;
		}
		if ( ! validate_sql_fields( $this->table_name ) ) {
			return false;
		}
		if ( ! validate_sql_fields( $this->column_name ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Run the replacement function.
	 *
	 * @return bool|string|\WP_Error
	 */
	public function replace() {
		global $wpdb;

		if ( 'int' === $this->primary_type ) {
			$this->original_string = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT ' . $this->column_name . ' FROM ' . $this->table_name . ' WHERE ' . $this->primary_column . ' = %d LIMIT 1', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- It is not possible to prepare a table or column name, but these are instead validated in `/includes/Search/class-sql.php` before reaching this point.
					$this->primary_key
				)
			);
		} else {
			$this->original_string = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT ' . $this->column_name . ' FROM ' . $this->table_name . ' WHERE ' . $this->primary_column . ' = %s LIMIT 1', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- It is not possible to prepare a table or column name, but these are instead validated in `/includes/Search/class-sql.php` before reaching this point.
					$this->primary_key
				)
			);
		}

		$replaced_line = $this->recursive_unserialize_replace( $this->old_string, $this->new_string, $this->original_string );

		$updated = $wpdb->update(
			$this->table_name,
			array(
				$this->column_name => $replaced_line,
			),
			array(
				$this->primary_column => $this->primary_key,
			)
		);

		if ( ! $updated ) {
			/*
			 * Cause an error to be thrown if updates fail due ot the query.
			 *
			 * This also checks that `$wpdb->last_error` is empty before treating
			 * the result as an error, this is because `$wpdb->update` will return
			 * a `false` value if it did not perform an update, for example when
			 * a string is identical. This may be the case where a class name or
			 * object is encountered, which can not be replaced.
			 */
			if ( empty( $wpdb->last_error ) ) {
				return true;
			}

			return new \WP_Error( 'search_replace_sql_error', __( 'Error updating the database.', 'search-replace' ) );
		}

		return String_Locator::create_preview( $replaced_line, $this->new_string, $this->regex );
	}

	/**
	 * Restore the last ran modification.
	 *
	 * @return bool
	 */
	public function restore() {
		global $wpdb;

		$wpdb->update(
			$this->table_name,
			array(
				$this->column_name => $this->original_string,
			),
			array(
				$this->primary_column => $this->primary_key,
			)
		);
		return true;
	}

	public function get_edit_url() {
		return $this->search->create_edit_link( $this->table_name, $this->column_name, $this->primary_column, $this->primary_type, (object) array( 'primary_column' => $this->primary_key ) );
	}

	/**
	 * Take a serialised array and unserialise it replacing elements as needed and
	 * unserialising any subordinate arrays and performing the replacement on those too.
	 *
	 * @param string $from       String we're looking to replace.
	 * @param string $to         What we want it to be replaced with
	 * @param array  $data       Used to pass any subordinate arrays back to in.
	 * @param bool   $serialised Does the array passed via $data need serialising.
	 *
	 * @return array|string    The original array with all elements replaced as needed.
	 */
	public function recursive_unserialize_replace( $from = '', $to = '', $data = '', $serialised = false ) {
		// Some unserialised data cannot be re-serialised eg. SimpleXMLElements.
		try {
			$unserialized = @unserialize( $data );
			if ( is_string( $data ) && false !== $unserialized ) {
				$data = $this->recursive_unserialize_replace( $from, $to, $unserialized, true );
			} elseif ( is_array( $data ) ) {
				$_tmp = array();
				foreach ( $data as $key => $value ) {
					$_tmp[ $key ] = $this->recursive_unserialize_replace( $from, $to, $value, false );
				}

				$data = $_tmp;
				unset( $_tmp );
			} elseif ( is_object( $data ) && ! is_a( $data, '__PHP_Incomplete_Class' ) ) {
				$_tmp  = $data;
				$props = get_object_vars( $data );
				foreach ( $props as $key => $value ) {
					$_tmp->$key = $this->recursive_unserialize_replace( $from, $to, $value, false );
				}

				$data = $_tmp;
				unset( $_tmp );
			} else {
				if ( is_string( $data ) ) {
					$data = $this->str_replace( $from, $to, $data );
				}
			}

			if ( $serialised ) {
				return serialize( $data );
			}
		} catch ( \Exception $error ) {
		}

		return $data;
	}

	/**
	 * Wrapper for regex/non regex search & replace
	 *
	 * @param string $search
	 * @param string $replace
	 * @param string $string
	 * @param int $count
	 *
	 * @return string
	 */
	public function str_replace( $search, $replace, $string, &$count = 0 ) {
		if ( $this->regex ) {
			return preg_replace( $search, $replace, $string, - 1, $count );
		} elseif ( function_exists( 'mb_split' ) ) {
			return $this->mb_str_replace( $search, $replace, $string, $count );
		} else {
			return str_ireplace( $search, $replace, $string, $count );
		}
	}

	/**
	 * Replace all occurrences of the search string with the replacement string.
	 *
	 * @param mixed $search
	 * @param mixed $replace
	 * @param mixed $subject
	 * @param int $count
	 *
	 * @return mixed
	 * @copyright Copyright 2012 Sean Murphy. All rights reserved.
	 * @license http://creativecommons.org/publicdomain/zero/1.0/
	 * @link http://php.net/manual/function.str-replace.php
	 *
	 * @author Sean Murphy <sean@iamseanmurphy.com>
	 */
	public function mb_str_replace( $search, $replace, $subject, &$count = 0 ) {
		if ( ! is_array( $subject ) ) {
			// Normalize $search and $replace so they are both arrays of the same length
			$searches     = is_array( $search ) ? array_values( $search ) : array( $search );
			$replacements = is_array( $replace ) ? array_values( $replace ) : array( $replace );
			$replacements = array_pad( $replacements, count( $searches ), '' );

			foreach ( $searches as $key => $search ) {
				$parts = mb_split( preg_quote( $search ), $subject );
				if ( ! is_array( $parts ) ) {
					continue;
				}
				$count  += count( $parts ) - 1;
				$subject = implode( $replacements[ $key ], $parts );
			}
		} else {
			// Call mb_str_replace for each subject in array, recursively
			foreach ( $subject as $key => $value ) {
				$subject[ $key ] = $this->mb_str_replace( $search, $replace, $value, $count );
			}
		}

		return $subject;
	}
}
