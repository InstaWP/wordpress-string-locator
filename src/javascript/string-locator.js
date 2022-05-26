/* global string_locator */
jQuery( document ).ready( function( $ ) {
	let StringLocator;
	if ( false !== string_locator.CodeMirror && '' !== string_locator.CodeMirror ) {
		StringLocator = wp.codeEditor.initialize( 'code-editor', string_locator.CodeMirror );
		const template = wp.template( 'string-locator-alert' );

		function resizeEditor() {
			const setEditorSize = ( Math.max( document.documentElement.clientHeight, window.innerHeight || 0 ) - 89 );
			StringLocator.codemirror.setSize( null, parseInt( setEditorSize ) );
		}

		$( '.string-locator-editor' ).on( 'click', '.string-locator-edit-goto', function( e ) {
			e.preventDefault();
			StringLocator.codemirror.scrollIntoView( parseInt( $( this ).data( 'goto-line' ) ) );
			StringLocator.codemirror.setCursor( parseInt( $( this ).data( 'goto-line' ) - 1 ), $( this ).data( 'goto-linepos' ) );
		} );

		$( 'body' ).on( 'submit', '#string-locator-edit-form', function( e ) {
			const $notices = $( '#string-locator-notices' );

			$.post(
				string_locator.url.save,
				$( this ).serialize()
			).always( function( response ) {
				if ( 'undefined' === typeof ( response.notices ) ) {
					$notices.append( template( {
						type: 'error',
						message: response.responseText,
					} ) );
				} else {
					$.each( response.notices, function() {
						$notices.append( template( this ) );
					} );
				}
			} );

			e.preventDefault();
			return false;
		} );

		resizeEditor();
		StringLocator.codemirror.scrollIntoView( parseInt( string_locator.goto_line ) );
		StringLocator.codemirror.setCursor( parseInt( string_locator.goto_line - 1 ), parseInt( string_locator.goto_linepos ) );

		window.onresize = resizeEditor;
	} else {
		StringLocator = $( '#code-editor' );

		StringLocator.css( 'width', $( '.string-locator-edit-wrap' ).width() );
		StringLocator.css( 'height', parseInt( ( Math.max( document.documentElement.clientHeight, window.innerHeight || 0 ) - 89 ) ) );
	}

	$( '#string-locator-notices' ).on( 'click', '.notice-dismiss', function( e ) {
		$( this ).closest( '.notice' ).slideUp( 400, 'swing', function() {
			$( this ).remove();
		} );

		e.preventDefault();
		return false;
	} );
} );

import '../sass/string-locator.scss';
