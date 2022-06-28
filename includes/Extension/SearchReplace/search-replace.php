<?php
/**
 * Plugin Name: String Locator: Replacement module
 */

namespace StringLocator\Extension\SearchReplace;

// Primary extension class.
require_once __DIR__ . '/class-replace.php';

// Replacement handlers.
require_once __DIR__ . '/Replace/class-file.php';
require_once __DIR__ . '/Replace/class-sql.php';

// REST Endpoints.
require_once __DIR__ . '/REST/class-replace.php';
