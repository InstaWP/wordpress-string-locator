<?php
if ( ! defined( 'ABSPATH' ) ) {
	die();
}
?>
<script id="tmpl-string-locator-search-result-sql" type="text/template">
	<tr data-type="sql" data-primary-key="{{ data.primary_key }}" data-primary-column="{{ data.primary_column }}" data-primary-type="{{ data.primary_type }}" data-table-name="{{ data.table }}" data-column-name="{{ data.column }}">
		<th scope="row" class="check-column">
			<input type="checkbox" name="string-locator-replace-checked[]" class="check-column-box">
		</th>
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
