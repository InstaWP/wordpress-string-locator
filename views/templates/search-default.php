<?php
if ( ! defined( 'ABSPATH' ) ) {
	die();
}
?>
<script id="tmpl-string-locator-search-result" type="text/template">
	<tr data-type="file" data-linenum="{{ data.linenum }}" data-filename="{{ data.filename_raw }}">
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
				{{ data.filename_raw }}
			</a>
			<# } #>
			<# if ( ! data.editurl ) { #>
			{{ data.filename_raw }}
			<# } #>
		</td>
		<td>
			{{ data.linenum }}
		</td>
		<td>
			{{ data.linepos }}
		</td>
	</tr>
</script>
