<?php
/**
 * Plugin Name: String Locator
 * Plugin URI: http://www.mrstk.net/wordpress-string-locator/
 * Description: Scan through theme and plugin files looking for text strings
 * Version: 1.0.0
 * Author: Clorith
 * Author URI: http://www.mrstk.net
 * License: GPL2
 *
 * Copyright 2013 Marius Jensen (email : marius@jits.no)
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

class string_locator
{
    /**
     * Construct the plugin
     */
    function __construct()
    {
        add_action( 'admin_menu', array( $this, 'populate_menu' ) );
    }

    /**
     * Add our plugin to the 'Tools' menu
     */
    function populate_menu()
    {
        $page_title  = __( 'String Locator', 'string-locator-plugin' );
        $menu_title  = __( 'String Locator', 'string-locator-plugin' );
        $capability  = 'edit_files';
        $parent_slug = 'tools.php';
        $menu_slug   = 'string-locator';
        $function    = array( $this, 'options_page' );

        add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
    }

    function options_page()
    {
        include_once( dirname( __FILE__ ) . '/options.php' );
    }
}

//  Initiate the plugin code
$string_locator = new string_locator();
