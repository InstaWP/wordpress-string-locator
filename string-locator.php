<?php
/**
 * Plugin Name: String Locator
 * Plugin URI: https://wordpress.org/plugins/string-locator/
 * Description: Scan through theme and plugin files looking for text strings
 * Version: 2.6.3
 * Author: InstaWP
 * Author URI: https://instawp.com/
 * Text Domain: string-locator
 * License: GPL2
 *
 * Copyright 2013 Marius Jensen (email : marius@clorith.net)
 *           2022 InstaWP (https://instawp.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

namespace StringLocator;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

define( 'STRING_LOCATOR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'STRING_LOCATOR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'STRING_LOCATOR_PLUGIN_FILE', __FILE__ );

/**
 * Base classes that other classes may extend.
 */
require_once __DIR__ . '/includes/Base/class-search.php';
require_once __DIR__ . '/includes/Base/class-rest.php';

/**
 * Search handlers
 */
require_once __DIR__ . '/includes/Extension/SQL/sql.php';
require_once __DIR__ . '/includes/Extension/SearchReplace/search-replace.php';

/**
 * Plugin test runners
 */
require_once __DIR__ . '/includes/Tests/class-loopback.php';
require_once __DIR__ . '/includes/Tests/class-smart-scan.php';

/**
 * Plugin action classes.
 */
require_once __DIR__ . '/includes/class-save.php';
require_once __DIR__ . '/includes/class-search.php';
require_once __DIR__ . '/includes/class-directory-iterator.php';

/**
 * Prepare REST endpoints.
 */
require_once __DIR__ . '/includes/REST/class-save.php';
require_once __DIR__ . '/includes/REST/class-clean.php';
require_once __DIR__ . '/includes/REST/class-search.php';
require_once __DIR__ . '/includes/REST/class-directory-structure.php';

/**
 * Instantiate the plugin
 */
require_once __DIR__ . '/includes/class-string-locator.php';
new String_Locator();
