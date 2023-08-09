// global string_locator, fetch, FormData
document.addEventListener( 'DOMContentLoaded', function() {
	let StringLocator,
		formData;

	if ( false !== string_locator.CodeMirror && '' !== string_locator.CodeMirror ) {
		StringLocator = wp.codeEditor.initialize( 'code-editor', string_locator.CodeMirror );
		const template = wp.template( 'string-locator-alert' );

		function resizeEditor() {
			const setEditorSize = ( Math.max( document.documentElement.clientHeight, window.innerHeight || 0 ) - 89 );
			StringLocator.codemirror.setSize( null, parseInt( setEditorSize ) );
		}

		document.addEventListener( 'click', function( e ) {
			const element = e.target;

			if ( ! element.classList.contains( 'string-locator-edit-goto' ) ) {
				return;
			}

			e.preventDefault();
			StringLocator.codemirror.scrollIntoView( parseInt( element.dataset.gotoLine ) );
			StringLocator.codemirror.setCursor( parseInt( element.dataset.gotoLine - 1 ), element.dataset.gotoLinepos );
		} );

		document.getElementById( 'string-locator-edit-form' ).addEventListener( 'submit', function( e ) {
			const noticeWrapper = document.getElementById( 'string-locator-notices' );

			formData = new FormData( this );

			fetch(
				string_locator.url.save,
				{
					method: 'POST',
					body: formData,
				}
			).then(
				( response ) => response.json()
			).then( function( response ) {
				if ( 'undefined' === typeof ( response.notices ) ) {
					noticeWrapper.innerHTML += template( {
						type: 'error',
						message: response.responseText,
					} );
				} else {
					response.notices.forEach( function( entry ) {
						noticeWrapper.innerHTML += template( entry );
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
		StringLocator = document.getElementById( 'code-editor' );

		StringLocator.style.width = document.getElementsByClassName( 'string-locator-edit-wrap' )[ 0 ].offsetWidth;
		StringLocator.style.height = parseInt( ( Math.max( document.documentElement.clientHeight, window.innerHeight || 0 ) - 89 ) );
	}
} );

import '../sass/string-locator.scss';
