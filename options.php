<?php
    /**
     * Get theme and plugin lists
     */
    $string_locate_themes = wp_get_themes();
    $string_locate_plugins = get_plugins();
?>
<div class="wrap">
    <h2>
        <?php _e( 'String Locator', 'string-locator-plugin' ); ?>
    </h2>

    <form action="" method="post">
        <label for="string-locator-search"><?php _e( 'Search through', 'string-locator-plugin' ); ?></label>
        <select name="string-locator-search" id="string-locator-search">
            <optgroup label="<?php _e( 'Themes', 'string-locator-plugin' ); ?>">
                <?php
                    /**
                     * Loop through themes for our dropdown list
                     */
                    foreach( $string_locate_themes AS $string_locate_theme_slug => $string_locate_theme )
                    {
                        $string_locate_theme_data = wp_get_theme( $string_locate_theme_slug );
                        $string_locate_value = 't-' . $string_locate_theme_slug;
                        echo '
                            <option value="' . $string_locate_value . '"' . ( isset( $_POST['string-locator-search'] ) && $_POST['string-locator-search'] == $string_locate_value ? ' selected="selected"' : '' ) . '>' . $string_locate_theme_data->Name . '</option>
                        ';
                    }
                ?>
            </optgroup>
            <optgroup label="<?php _e( 'Plugins', 'string-locator-plugin' ); ?>">
                <?php
                    /**
                     * Loop through plugins for our dropdown list
                     */
                    foreach( $string_locate_plugins AS $string_locate_plugin_path => $string_locate_plugin )
                    {
                        $string_locate_value = 'p-' . $string_locate_plugin_path;
                        echo '
                            <option value="' . $string_locate_value . '"' . ( isset( $_POST['string-locator-search'] ) && $_POST['string-locator-search'] == $string_locate_value ? ' selected="selected"' : '' ) . '>' . $string_locate_plugin['Name'] . '</option>
                        ';
                    }
                ?>
            </optgroup>
        </select>

        <label for="string-locator-string"><?php _e( 'Search string', 'string-locator-plugin' ); ?></label>
        <input type="text" name="string-locator-string" id="string-locator-string" value="<?php echo ( isset( $_POST['string-locator-string'] ) ? $_POST['string-locator-string'] : '' ); ?>" />

        <?php submit_button( __( 'Search', 'string-locator-plugin' ) ); ?>
    </form>

    <?php
        if ( isset( $_POST['string-locator-search'] ) )
        {
    ?>

    <form action="" method="post">
        <table class="wp-lsit-table widefat fixed">
            <thead>
                <tr>
                    <th scope="col" style=""><?php _e( 'Line', 'string-locator-plugin' ); ?></th>
                    <th scope="col" style=""><?php _e( 'File', 'string-locator-plugin' ); ?></th>
                    <th scope="col" style=""><?php _e( 'String', 'string-locator-plugin' ); ?></th>
                </tr>
            </thead>

            <tfoot>
                <tr>
                    <th scope="col" style=""><?php _e( 'Line', 'string-locator-plugin' ); ?></th>
                    <th scope="col" style=""><?php _e( 'File', 'string-locator-plugin' ); ?></th>
                    <th scope="col" style=""><?php _e( 'String', 'string-locator-plugin' ); ?></th>
                </tr>
            </tfoot>

            <tbody>
            <?php
                $found = false;
                $path = ABSPATH . 'wp-content/';

                /**
                 * Check what we are search through, a theme or a plugin
                 */
                if ( substr( $_POST['string-locator-search'], 0, 2 ) == 't-' )
                {
                    $theme = substr( $_POST['string-locator-search'], 2 );
                    $path .= 'themes/' . $theme;
                }
                else {
                    $plugin = explode( '/', substr( $_POST['string-locator-search'], 2 ) );
                    $path .= 'plugins/' . $plugin[0];
                }

                $relativepath = str_replace( ABSPATH, '', $path );

                /**
                 * We use the PHP Iterator class to recursively check for files
                 */
                $paths = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator( $path ),
                    RecursiveIteratorIterator::SELF_FIRST
                );

                foreach ( $paths AS $name => $location )
                {
                    $linenum = 0;

                    /**
                     * If it's a directory, skip this runthrough, we can't read a directory line by line
                     */
                    if ( is_dir( $location->getPathname() ) )
                        continue;

                    /**
                     * Start reading the file
                     */
                    $readfile = fopen( $location->getPathname(), "r" );
                    if ( $readfile )
                    {
                        while ( ( $readline = fgets( $readfile ) ) !== false )
                        {
                            $linenum++;
                            /**
                             * If our string is found in this line, output the line number and other data
                             */
                            if ( stristr( $readline, $_POST['string-locator-string'] ) )
                            {
                                $found = true;
                                echo '
                                    <tr>
                                        <td>' . $linenum . '</td>
                                        <td>' . $relativepath . '/' . $location->getFilename() . '</td>
                                        <td>' . str_ireplace( $_POST['string-locator-string'], '<strong>' . $_POST['string-locator-string'] . '</strong>', htmlentities( $readline ) ) . '</td>
                                    </tr>
                                ';
                            }
                        }
                    }
                    else {
                        /**
                         * The file was unreadable, give the user a friendly notification
                         */
                        echo '
                            <tr>
                                <td colspan="3">
                                    <strong>
                                        ' . __( 'Could not read file: ', 'string-locator-plugin' ) . $location->getFilename() . '
                                    </strong>
                                </td>
                            </tr>
                        ';
                    }
                }

                /**
                 * Give the user feedback if the string wasn't found anywhere
                 */
                if ( ! $found )
                    echo '
                        <tr>
                            <td colspan="3">
                                ' . __( 'Your string was not present in any of the available files.', 'string-locator-plugin' ) . '
                            </td>
                        </tr>
                    ';
            ?>
            </tbody>
        </table>
    </form>

    <?php
        }
    ?>
</div>
