<?php
/**
 * Class to handle the edit page.
 */

namespace StringLocator\Extension\SQL;

use StringLocator\String_Locator;

/**
 * Edit class.
 */
class Edit {

	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_filter( 'string_locator_view', array( $this, 'sql_edit_page' ) );

		add_filter( 'admin_body_class', array( $this, 'admin_body_class' ) );
		add_filter( 'string_locator_editor_fields', array( $this, 'editor_form_fields' ) );
	}

	/**
	 * Generate form fields that need ot be a part of the editor interface for this data type.
	 *
	 * @param array $fields An array of form fields to output in hidden elements.
	 *
	 * @return array
	 */
	public function editor_form_fields( $fields ) {
		if ( isset( $_GET['file-type'] ) && 'sql' === $_GET['file-type'] ) {
			$fields = array_merge(
				array(
					'sql-column'         => $_GET['sql-column'],
					'sql-table'          => $_GET['sql-table'],
					'sql-primary-column' => $_GET['sql-primary-column'],
					'sql-primary-type'   => $_GET['sql-primary-type'],
					'sql-primary-key'    => $_GET['sql-primary-key'],
				),
				$fields
			);
		}

		return $fields;
	}

	/**
	 * Append a helper class ot the wp-admin body class.
	 *
	 * @param string $class The classes for the admin body class.
	 *
	 * @return string
	 */
	public function admin_body_class( $class ) {
		if ( isset( $_GET['file-type'] ) && 'sql' === $_GET['file-type'] && current_user_can( String_Locator::$default_capability ) ) {
			$class .= ' file-edit-screen';
		}

		return $class;
	}

	/**
	 * Conditionally filter the editor interface for SQL files.
	 *
	 * @param string $include_path The path to the editor interface.
	 *
	 * @return string
	 */
	public function sql_edit_page( $include_path ) {
		if ( ! isset( $_GET['file-type'] ) || 'sql' !== $_GET['file-type'] || ! current_user_can( String_Locator::$default_capability ) ) {
			return $include_path;
		}

		// Validate the table name.
		if ( ! isset( $_GET['sql-table'] ) || ! validate_sql_fields( $_GET['sql-table'] ) ) {
			return $include_path;
		}

		// Validate the primary column
		if ( ! isset( $_GET['sql-primary-column'] ) || ! validate_sql_fields( $_GET['sql-primary-column'] ) ) {
			return $include_path;
		}

		// A primary key needs to be provided, this could be anything so we just make sure it is set and not empty.
		if ( ! isset( $_GET['sql-primary-key'] ) || empty( $_GET['sql-primary-key'] ) ) {
			return $include_path;
		}

		return STRING_LOCATOR_PLUGIN_DIR . '/includes/Extension/SQL/views/editor/sql.php';
	}
}

new Edit();
