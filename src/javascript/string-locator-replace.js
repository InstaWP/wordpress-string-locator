/* global stringLocatorReplace, fetch, FormData */
document.addEventListener( 'DOMContentLoaded', function() {
	const replaceStringField = document.getElementById( 'string-locator-replace-new-string' ),
		toggleButton = document.getElementById( 'string-locator-toggle-replace-controls' ),
		replaceControls = document.getElementById( 'string-locator-replace-form' ),
		replaceForm = document.getElementById( 'string-locator-replace-form' ),
		searchResultsTable = document.getElementById( 'string-locator-search-results-table' ),
		noticeWrapper = document.getElementById( 'string-locator-search-notices' ),
		progressWrapper = document.getElementById( 'string-locator-progress-wrapper' ),
		progressIndicator = document.getElementById( 'string-locator-search-progress' ),
		progressText = document.getElementById( 'string-locator-feedback-text' );

	let searchResultsTableRow,
		searchResultText;

	function replaceSingleInstance( instance ) {
		let formData = new FormData(),
			dataSets = { ...searchResultsTableRow[ instance ].dataset };

		formData.append( '_wpnonce', stringLocatorReplace.rest_nonce );
		formData.append( 'replace_nonce', stringLocatorReplace.replace_nonce );
		formData.append( 'replace_string', replaceStringField.value );

		for ( const key in dataSets ) {
			formData.append( key, dataSets[ key ] );
		}

		progressIndicator.value = instance;

		fetch(
			stringLocatorReplace.url.replace,
			{
				method: 'POST',
				body: formData,
			}
		).then(
			( response ) => response.json()
		).then( function( response ) {
			searchResultText = searchResultsTableRow[ instance ].getElementsByTagName( 'td' )[ 0 ];

			if ( true !== response.data.replace_string ) {
				searchResultText.innerHTML = response.data.replace_string;
			}

			if ( instance < ( searchResultsTableRow.length - 1 ) ) {
				replaceSingleInstance( instance + 1 );
			} else {
				progressWrapper.style.display = 'none';
			}
		} ).catch( function( error ) {
			console.error( error );
		} );
	}

	replaceForm.addEventListener( 'submit', function( e ) {
		e.preventDefault();

		searchResultsTableRow = searchResultsTable.getElementsByTagName( 'tbody' )[0].getElementsByTagName( 'tr' );

		progressWrapper.style.display = 'block';
		noticeWrapper.innerHTML = '';

		progressIndicator.value = 0;
		progressIndicator.setAttribute( 'max', searchResultsTableRow.length );

		replaceSingleInstance( 0 );
	} );

	toggleButton.addEventListener( 'click', function() {
		replaceControls.classList.toggle( 'visible' );
	} );
} );

import '../sass/replace/replace.scss';
