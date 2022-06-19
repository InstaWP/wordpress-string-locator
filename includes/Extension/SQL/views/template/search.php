<?php
if ( ! defined( 'ABSPATH' ) ) {
	die();
}
?>
<script id="tmpl-string-locator-search-result-sql" type="text/template">
	<tr>
		<td>
			{{{ data.stringresult }}}

			<div class="row-actions">
				<# if ( data.editurl ) { #>
				<span class="edit">
						<a href="{{ data.editurl }}" aria-label="<?php esc_attr_e( 'Edit', 'string-locator' ); ?>">
							<?php esc_html_e( 'Edit', 'string-locator' ); ?>
						</a>
					</span>
				<# } #>
			</div>
		</td>
		<td>
			<# if ( data.editurl ) { #>
			<a href="{{ data.editurl }}">
				{{ data.filename }}
			</a>
			<# } #>
			<# if ( ! data.editurl ) { #>
			{{ data.filename }}
			<# } #>
		</td>
		<td>
			{{ data.primary_key }}
		</td>
		<td>
			{{ data.linepos }}
		</td>
	</tr>
</script>
