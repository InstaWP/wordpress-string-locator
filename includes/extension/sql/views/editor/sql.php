<?php

namespace StringLocator;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

global $wpdb;

$editor_content = '';

$this_url = add_query_arg(
	array(
		'page' => 'string-locator',
	),
	admin_url( ( is_multisite() ? 'network/admin.php' : 'tools.php' ) )
);

if ( 'int' === $_GET['sql-primary-type'] ) {
	$row = $wpdb->get_row(
		$wpdb->prepare(
			'SELECT * FROM ' . $_GET['sql-table'] . ' WHERE ' . $_GET['sql-primary-column'] . ' = %d LIMIT 1', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- It is not possible to prepare a table or column name, but these are instead validated in `/includes/Search/class-sql.php` before reaching this point.
			$_GET['sql-primary-key']
		)
	);
} else {
	$row = $wpdb->get_row(
		$wpdb->prepare(
			'SELECT * FROM ' . $_GET['sql-table'] . ' WHERE ' . $_GET['sql-primary-column'] . ' = %s LIMIT 1', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- It is not possible to prepare a table or column name, but these are instead validated in `/includes/Search/class-sql.php` before reaching this point.
			$_GET['sql-primary-key']
		)
	);
}

$format = 'string';

if ( is_serialized( $row->{ $_GET['sql-column'] }, true ) ) {
	$format = 'serialized';
}

$editor_content = $row->{ $_GET['sql-column'] };
?>
<form id="string-locator-edit-form" class="string-locator-editor-wrapper">
	<?php wp_nonce_field( 'wp_rest' ); ?>

	<h1 class="screen-reader-text">
		<?php
			/* translators: Title on the editor page. */
			esc_html_e( 'String Locator - SQL Editor', 'string-locator' );
		?>
	</h1>

	<?php String_Locator::edit_form_fields( true ); ?>

	<div class="string-locator-header">
		<div>
			<span class="title">
				<?php
				printf(
					// translators: %s: The name of the database column being edited.
					__( 'You are currently editing a database entry from <em>%s</em>', 'string-locator' ),
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

			<div class="row notice notice-warning inline below-h2 is-dismissible <?php echo ( 'serialized' === $format ? 'notice-error' : '' ); ?>">
				<p>
					<strong><?php esc_html_e( 'Warning:', 'string-locator' ); ?></strong> <?php esc_html_e( 'You are directly editing a database entry.', 'string-locator' ); ?>
				</p>
				<p>
					<?php _e( 'You are about to make modifications directly to the database. Even if you are familiar with such changes, it is strongly recommended you keep a backup, and to perform such changes on a <a href="https://wordpress.org/support/article/running-a-development-copy-of-wordpress/">staging site</a> first.', 'string-locator' ); ?>
				</p>

				<?php if ( 'serialized' === $format ) : ?>
				<p>
					<strong>
						<?php _e( 'This data is serialized, that means extra care must be taken as string lengths, and special characters must be accurately representative of the various field descriptors also found in the database entry.', 'string-locator' ); ?>
					</strong>
				</p>
				<?php endif; ?>
			</div>
		</div>

		<textarea
			name="string-locator-editor-content"
			class="string-locator-editor hide-if-no-js"
			id="code-editor"
			autofocus="autofocus"
		><?php echo esc_html( $editor_content ); ?></textarea>
	</div>

	<div class="string-locator-sidebar">

		<?php do_action( 'string_locator_editor_sidebar_start' ); ?>

		<div class="string-locator-panel">
			<h2 class="title"><?php esc_html_e( 'Details', 'string-locator' ); ?></h2>
			<div class="string-locator-panel-body">
				<div class="row">
					<?php
					printf(
						// translators: 1: Table name being edited.
						esc_html__( 'Database table: %s', 'string-locator' ),
						esc_html( $_GET['sql-table'] )
					);
					?>
				</div>

				<div class="row">
					<?php
					printf(
						// translators: 1: Column name being edited.
						esc_html__( 'Database Column: %s', 'string-locator' ),
						esc_html( $_GET['sql-column'] )
					);
					?>
				</div>

				<div class="row">
					<?php
					printf(
						// translators: 1: Primary database column name. 2: Primary database column key.
						esc_html__( 'Primary column and key: %1$s:%2$s', 'string-locator' ),
						esc_html( $_GET['sql-primary-column'] ),
						esc_html( $_GET['sql-primary-key'] )
					);
					?>
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
			<h2 class="title"><?php esc_html_e( 'Database entry context', 'string-locator' ); ?></h2>
			<div class="string-locator-panel-body">

				<?php
				foreach ( $row as $key => $value ) {
					// Do not output the currently edited column as a context relationship.
					if ( $_GET['sql-column'] === $key ) {
						continue;
					}
					?>

					<div class="row">
						<?php echo esc_html( $key ); ?>:
						<br />
						<span class="string-locator-italics">
							<?php echo esc_html( $value ); ?>
						</span>
					</div>

					<?php
				}
				?>
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
