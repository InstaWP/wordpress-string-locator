<?php

namespace JITS\StringLocator;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

$this_url = admin_url( ( is_multisite() ? 'network/admin.php' : 'tools.php' ) . '?page=string-locator' );

?>

<div id="string-locator-replace-form">
	<h2><?php esc_html_e( 'Replace in results', 'string-locator' ); ?></h2>

	<form action="<?php echo esc_url( $this_url ); ?>" method="post">
		<p>
			<label for="string-locator-replace-new-string"><?php esc_html_e( 'New string', 'string-locator' ); ?></label>
			<input type="text" id="string-locator-replace-new-string" name="string-locator-replace-new-string">
		</p>

		<p>
			<button type="button" class="button button-primary" id="string-locator-replace-button-all">
				<?php esc_html_e( 'Replace all strings', 'string-locator' ); ?>
			</button>
			<button type="button" class="button button-primary" id="string-locator-replace-button-selected">
				<?php esc_html_e( 'Replace selected strings', 'string-locator' ); ?>
			</button>
		</p>
	</form>
</div>
