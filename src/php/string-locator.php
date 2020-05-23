<?php
/**
 * Plugin Name: String Locator
 * Plugin URI: http://www.clorith.net/wordpress-string-locator/
 * Description: Scan through theme and plugin files looking for text strings
 * Version: 2.3.1
 * Author: Clorith
 * Author URI: http://www.clorith.net
 * Text Domain: string-locator
 * License: GPL2
 *
 * Copyright 2013 Marius Jensen (email : marius@clorith.net)
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

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

define( 'STRING_LOCATOR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'STRING_LOCATOR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require_once( __DIR__ . '/includes/class-string-locator.php' );

/**
 * Instantiate the plugin
 */
$string_locator = new string_locator();
