<?php
/**
 * Template for notices returned form the REST API when performing a replacement.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}
?>

<script id="tmpl-string-locator-replace-error" type="text/template">
	<div class="notice notice-error">
		<p>
			{{ data.message }}
		</p>
	</div>
</script>
