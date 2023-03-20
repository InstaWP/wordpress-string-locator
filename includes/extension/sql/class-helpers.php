<?php

namespace StringLocator\Extension\SQL;

/**
 * Helpers class.
 */
class Helpers {

	public static function validate_sql_fields( $field ) {
		return preg_match( '/^[0-9a-zA-Z_]+$/s', $field );
	}

}

