<?php
if ( ! defined( 'ABSPATH' ) ) {
	die();
}
?>

<div class="string-locator-panel">
	<h2 class="title">
		<img src="<?php echo plugins_url( 'views/assets/instawp-logo.svg', STRING_LOCATOR_PLUGIN_FILE ); ?>" alt="<?php esc_attr_e( 'InstaWP logo', 'string-locator' ); ?>">
		<span>
			<?php esc_html_e( 'Create a disposable site', 'string-locator' ); ?>
		</span>
	</h2>
	<div class="string-locator-panel-body">
		<div class="row">
			<a href="https://instawp.com/?utm_source=stringlocator" target="_blank">
				<?php esc_html_e( 'Create Disposable WordPress Sites in Seconds.', 'string-locator' ); ?>
			</a>
		</div>
	</div>
</div>
