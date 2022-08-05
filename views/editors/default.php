<?php

namespace StringLocator;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

$editor_content = '';

// $file is validated in String_Locator::is_valid_location() before this page can be loaded through String_Locator::options_page().
$file = $_GET['string-locator-path'];

$details  = array();
$this_url = admin_url( ( is_multisite() ? 'network/admin.php' : 'tools.php' ) . '?page=string-locator' );

if ( 'core' === $_GET['file-type'] ) {
	$details = array(
		'name'        => 'WordPress',
		'version'     => get_bloginfo( 'version' ),
		'author'      => array(
			'uri'  => 'https://wordpress.org/',
			'name' => 'WordPress',
		),
		/* translators: The WordPress description, used when a core file is opened in the editor. */
		'description' => esc_html__( 'WordPress is web software you can use to create a beautiful website or blog. We like to say that WordPress is both free and priceless at the same time.', 'string-locator' ),
	);
} elseif ( 'theme' === $_GET['file-type'] ) {
	$themedata = wp_get_theme( $_GET['file-reference'] );

	$details = array(
		'name'        => $themedata->get( 'Name' ),
		'version'     => $themedata->get( 'Version' ),
		'author'      => array(
			'uri'  => $themedata->get( 'AuthorURI' ),
			'name' => $themedata->get( 'Author' ),
		),
		'description' => $themedata->get( 'Description' ),
		'parent'      => $themedata->get( 'parent' ),
	);
} elseif ( 'sql' === $_GET['file-type'] ) {
	$details = array(
		'name'        => 'Name',
		'version'     => 'Version',
		'author'      => array(
			'uri'  => 'author URI',
			'name' => 'author name',
		),
		'description' => 'description',
		'parent'      => 'parent',
	);
} else {
	$plugins = get_plugins();

	foreach ( $plugins as $pluginname => $plugindata ) {
		$pluginref = explode( '/', $pluginname );

		if ( $pluginref[0] === $_GET['file-reference'] ) {
			$details = array(
				'name'        => $plugindata['Name'],
				'version'     => $plugindata['Version'],
				'author'      => array(
					'uri'  => $plugindata['AuthorURI'],
					'name' => $plugindata['Author'],
				),
				'description' => $plugindata['Description'],
			);
		}
	}
}

if ( 'sql' !== $_GET['file-type'] ) {
	$readfile = fopen( $file, 'r' );
	if ( $readfile ) {
		while ( ( $readline = fgets( $readfile ) ) !== false ) { // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
			$editor_content .= $readline;
		}
	}
}
?>
<form id="string-locator-edit-form" class="string-locator-editor-wrapper">
	<?php wp_nonce_field( 'wp_rest' ); ?>

	<h1 class="screen-reader-text">
		<?php
			/* translators: Title on the editor page. */
			esc_html_e( 'String Locator - Editor', 'string-locator' );
		?>
	</h1>

	<?php String_Locator::edit_form_fields( true ); ?>

	<div class="string-locator-header">
		<div>
			<span class="title">
				<?php
				printf(
					// translators: %s: The name of the file being edited.
					__( 'You are currently editing <em>%s</em>', 'string-locator' ),
					esc_html( $_GET['file-reference'] )
				);
				?>
			</span>
		</div>

		<div>
			<a href="<?php echo esc_url( $this_url . '&restore=true' ); ?>" class="button button-default"><?php esc_html_e( 'Return to search results', 'string-locator' ); ?></a>
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save changes', 'string-locator' ); ?></button>
		</div>
	</div>

	<div class="string-locator-editor">
		<div id="string-locator-notices">
			<div class="row notice notice-error inline below-h2 hide-if-js">
				<p>
					<?php esc_html_e( 'The editor requires JavaScript to be enabled before it can be used.', 'string-locator' ); ?>
				</p>
			</div>

			<?php if ( isset( $details['parent'] ) && ! $details['parent'] ) { ?>
				<div class="row notice notice-warning inline below-h2 is-dismissible">
					<p>
						<?php esc_html_e( 'It seems you are making direct edits to a theme.', 'string-locator' ); ?>
					</p>

					<p>
						<?php _e( 'When making changes to a theme, it is recommended you make a <a href="https://developer.wordpress.org/themes/advanced-topics/child-themes/">Child Theme</a>.', 'string-locator' ); ?>
					</p>
				</div>
			<?php } ?>

			<?php if ( ! stristr( $file, 'wp-content' ) ) { ?>
				<div class="row notice notice-warning inline below-h2 is-dismissible">
					<p>
						<strong><?php esc_html_e( 'Warning:', 'string-locator' ); ?></strong> <?php esc_html_e( 'You appear to be editing a Core file.', 'string-locator' ); ?>
					</p>
					<p>
						<?php _e( 'Keep in mind that edits to core files will be lost when WordPress is updated. Please consider <a href="https://make.wordpress.org/core/handbook/">contributing to WordPress core</a> instead.', 'string-locator' ); ?>
					</p>
				</div>
			<?php } ?>
		</div>

		<?php
		$editor = '<textarea
			name="string-locator-editor-content"
			class="string-locator-editor hide-if-no-js"
			id="code-editor"
			data-editor-goto-line="' . esc_attr( $_GET['string-locator-line'] ) . ':' . esc_attr( $_GET['string-locator-linepos'] ) . '"
			data-editor-language=""
			autofocus="autofocus"
		>' . esc_html( $editor_content ) . '</textarea>';

		echo apply_filters( 'string_locator_editor_markup', $editor );

		?>
	</div>

	<div class="string-locator-sidebar">

		<?php do_action( 'string_locator_editor_sidebar_start' ); ?>

		<div class="string-locator-panel">
			<h2 class="title"><?php esc_html_e( 'Details', 'string-locator' ); ?></h2>
			<div class="string-locator-panel-body">
				<div class="row">
					<?php echo esc_html( $details['name'] ); ?> <small>v. <?php echo esc_html( $details['version'] ); ?></small>
				</div>
				<div class="row">
					<?php esc_html_e( 'By', 'string-locator' ); ?> <a href="<?php echo esc_url( $details['author']['uri'] ); ?>" target="_blank"><?php echo esc_html( $details['author']['name'] ); ?></a>
				</div>
				<div class="row">
					<?php echo esc_html( $details['description'] ); ?>
				</div>
			</div>
		</div>

		<?php do_action( 'string_locator_editor_sidebar_before_checks' ); ?>

		<div class="string-locator-panel">
			<h2 class="title"><?php esc_html_e( 'Save checks', 'string-locator' ); ?></h2>
			<div class="string-locator-panel-body">
				<?php do_action( 'string_locator_editor_checks' ); ?>
			</div>
		</div>

		<?php do_action( 'string_locator_editor_sidebar_after_checks' ); ?>

		<div class="string-locator-panel">
			<h2 class="title"><?php esc_html_e( 'File information', 'string-locator' ); ?></h2>
			<div class="string-locator-panel-body">
				<div class="row">
					<?php esc_html_e( 'File location:', 'string-locator' ); ?>
					<br />
					<span class="string-locator-italics">
						<?php echo esc_html( str_replace( ABSPATH, '', $file ) ); ?>
						<span title="<?php echo esc_attr( sprintf( /* translators: File path. */ esc_html__( 'Full file path: %s', 'string-locator' ), $file ) ); ?>" class="dashicons dashicons-editor-help"></span>
					</span>
				</div>

				<div class="row">
					<?php esc_html_e( 'File size:', 'string-locator' ); ?>
					<br />
					<span class="string-locator-italics">
						<?php echo esc_html( size_format( filesize( $file ), 1 ) ); ?>
					</span>
				</div>

				<div class="row">
					<?php esc_html_e( 'Last modified:', 'string-locator' ); ?>
					<br />
					<span class="string-locator-italics">
						<?php echo gmdate( 'Y-m-d H:i:s', filemtime( $file ) ); ?>
					</span>
				</div>
			</div>
		</div>

		<?php
		$function_info = get_defined_functions();
		$function_help = '';

		foreach ( $function_info['user'] as $user_func ) {
			if ( strstr( $editor_content, $user_func . '(' ) ) {
				$function_object = new \ReflectionFunction( $user_func );
				$attrs           = $function_object->getParameters();

				$attr_strings = array();

				foreach ( $attrs as $attr ) {
					$arg = '';

					if ( $attr->isPassedByReference() ) {
						$arg .= '&';
					}

					if ( $attr->isOptional() ) {
						$arg = sprintf(
							'[ %s$%s ]',
							$arg,
							$attr->getName()
						);
					} else {
						$arg = sprintf(
							'%s$%s',
							$arg,
							$attr->getName()
						);
					}

					$attr_strings[] = $arg;
				}

				$function_help .= sprintf(
					'<div class="row"><a href="%s" target="_blank">%s</a></div>',
					esc_url( sprintf( 'https://developer.wordpress.org/reference/functions/%s/', $user_func ) ),
					$user_func . '( ' . implode( ', ', $attr_strings ) . ' )'
				);
			}
		}
		?>

		<?php if ( ! empty( $function_help ) ) : ?>

		<div class="string-locator-panel">
			<h2 class="title"><?php esc_html_e( 'WordPress functions', 'string-locator' ); ?></h2>
			<div class="string-locator-panel-body">
				<?php echo $function_help; ?>
		</div>
		<?php endif; ?>

		<?php do_action( 'string_locator_editor_sidebar_end' ); ?>

	</div>

</div>

<script id="tmpl-string-locator-alert" type="text/template">
	<div class="row notice notice-{{ data.type }} inline below-h2 is-dismissible">
		{{{ data.message }}}

		<button type="button" class="notice-dismiss" onclick="this.closest( '.notice' ).remove()"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'string-locator' ); ?></span></button>
	</div>
</script>
