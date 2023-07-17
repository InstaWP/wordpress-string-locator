<?php

namespace StringLocator;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

$this_url = admin_url( ( is_multisite() ? 'network/admin.php' : 'tools.php' ) . '?page=string-locator' );

$search_string   = '';
$search_location = '';
$search_regex    = false;

if ( isset( $_POST['string-locator-string'] ) ) {
	$search_string = $_POST['string-locator-string'];
}
if ( isset( $_POST['string-locator-search'] ) ) {
	$search_location = $_POST['string-locator-search'];
}

if ( isset( $_GET['restore'] ) ) {
	$restore = get_transient( 'string-locator-search-overview' );

	if ( false !== $restore ) {
		$search_string   = $restore->search;
		$search_location = $restore->directory;
		$search_regex    = String_Locator::absbool( $restore->regex );
	} else {
		?>
		<div class="notice notice-large notice-warning"><?php esc_html_e( 'No previous searches could be restored.', 'string-locator' ); ?></div>
		<?php
	}
}
?>
<div class="wrap">
	<h1>
		<?php esc_html_e( 'String Locator', 'string-locator' ); ?>
	</h1>

	<?php do_action( 'string_locator_view_search_pre_form' ); ?>

	<?php if ( ! current_user_can( String_Locator::$default_capability ) ) : ?>
		<div class="notice notice-warning inline">
			<p>
				<strong>
					<?php esc_html_e( 'String Locator is limited to search mode only.', 'string-locator' ); ?>
				</strong>
			</p>
			<p>
				<?php esc_html_e( 'Because this site is configured to not allow direct file editing, the String Locator plugin has limited functionality and may noy allow you to directly edit files with your string in them.', 'string-locator' ); ?>
			</p>
			<p>
				<?php
				echo sprintf(
						// translators: 1: The capability needed for this feature.
					esc_html__( 'To edit files, you need to have the `%s` capability.', 'string-locator' ),
					String_Locator::$default_capability
				);
				?>
			</p>
		</div>
	<?php endif; ?>

	<?php if ( ! current_user_can( String_Locator::$search_capability ) ) : ?>
		<div class="notice notice-warning inline">
			<p>
				<strong>
					<?php esc_html_e( 'String Locator is restricted.', 'string-locator' ); ?>
				</strong>
			</p>
			<p>
				<?php esc_html_e( 'Your user does not have the needed capabilities to edit, or search through files on this site.', 'string-locator' ); ?>
			</p>

			<p>
				<?php
				echo sprintf(
						// translators: 1: The capability needed for this feature.
					esc_html__( 'To use the search feature, you need to have the `%s` capability.', 'string-locator' ),
					String_Locator::$search_capability
				);
				?>
			</p>
		</div>
	<?php endif; ?>

	<form action="<?php echo esc_url( $this_url ); ?>" method="post" id="string-locator-search-form">
		<label for="string-locator-search"><?php esc_html_e( 'Search through', 'string-locator' ); ?></label>
		<select name="string-locator-search" id="string-locator-search">
			<?php
			$searchers = apply_filters( 'string_locator_search_sources_markup', '', $search_location );

			echo $searchers;
			?>
		</select>

		<label for="string-locator-string"><?php esc_html_e( 'Search string', 'string-locator' ); ?></label>
		<input type="text" name="string-locator-string" id="string-locator-string" value="<?php echo esc_attr( $search_string ); ?>" />

		<label><input type="checkbox" name="string-locator-regex" id="string-locator-regex"<?php echo ( $search_regex ? ' checked="checked"' : '' ); ?>> <?php esc_html_e( 'RegEx search', 'string-locator' ); ?></label>

		<p>
			<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_html_e( 'Search', 'string-locator' ); ?>">
			<a href="<?php echo esc_url( $this_url . '&restore=true' ); ?>" class="button button-primary"><?php esc_html_e( 'Restore last search', 'string-locator' ); ?></a>

			<?php
			/**
			 * Provides an area for outputting additional markup or controls
			 * immediately following the button controllers for the String Locator
			 * search form, but within the same paragraph for easier styling where needed.
			 */
			do_action( 'string_locator_after_search_buttons' );
			?>
		</p>
	</form>

	<div class="notices" id="string-locator-search-notices"></div>

	<div class="string-locator-feedback hide" id="string-locator-progress-wrapper">
		<progress id="string-locator-search-progress" max="100"></progress>
		<span id="string-locator-feedback-text"><?php esc_html_e( 'Preparing search&hellip;', 'string-locator' ); ?></span>
	</div>

	<?php
	/**
	 * Provides an action for outputting additional markup or controls
	 * immediately preceding the table displaying search results.
	 */
	do_action( 'string_locator_before_search_results_table' );

	$wrapper_classes = array(
		'table-wrapper',
	);

	if ( isset( $_GET['restore'] ) ) {
		$wrapper_classes[] = 'restore';
	}
	?>

	<div id="string-locator-search-results-table-wrapper" class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>">
		<div class="tablenav top">
			<div class="replacement-control-main-wrapper">		
				<div class="replacement-control-left-details">
					<?php
					/**
					 * An action to output controls in the tablenav region, which only
					 * become visible when there are search results available.
					 */
					do_action( 'string_locator_search_results_tablenav_controls' );
					?>
				</div>
				<br class="clear" />
				<div class="replacement-control-instawp-btn">
					<?php
					/**
					 * An action to output controls in the tablenav region, which only
					 * become visible when there are search results available.
					 */
					do_action( 'string_locator_instawp_tablenav_controls' );
					?>
				</div>
			</div>
		</div>
		<?php
		if ( isset( $_GET['restore'] ) ) {
			$items = get_option( 'string-locator-search-history', array() );
			$items = maybe_unserialize( $items );

			echo String_Locator::prepare_full_table( $items, array( 'restore' ) );
		} else {
			echo String_Locator::prepare_full_table( array() );
		}
		?>
	</div>

	<?php
	/**
	 * Provides an action for outputting additional markup or controls
	 * immediately following the table displaying search results.
	 */
	do_action( 'string_locator_after_search_results_table' );
	?>
</div>

<?php do_action( 'string_locator_search_templates' ); ?>
