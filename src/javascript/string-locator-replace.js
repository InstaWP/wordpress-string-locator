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
		progressText = document.getElementById( 'string-locator-feedback-text' ),
		searchString = document.getElementById( 'string-locator-string' ),
		searchRegex = document.getElementById( 'string-locator-regex' );

	let searchResultsTableRow,
		searchResultText;

	function replaceSingleInstance( instance ) {
		const formData = new FormData(),
			dataSets = { ...searchResultsTableRow[ instance ].dataset };

		formData.append( '_wpnonce', stringLocatorReplace.rest_nonce );
		formData.append( 'replace_nonce', stringLocatorReplace.replace_nonce );
		formData.append( 'replace_string', replaceStringField.value );
		formData.append( 'search_string', searchString.value );
		formData.append( 'search_regex', searchRegex.checked );

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
			noticeWrapper.style.display = 'block';
			noticeWrapper.innerHTML = error.message;
		} );
	}

	replaceForm.addEventListener( 'submit', function( e ) {
		e.preventDefault();

		searchResultsTableRow = searchResultsTable.getElementsByTagName( 'tbody' )[ 0 ].getElementsByTagName( 'tr' );

		progressWrapper.style.display = 'block';
		progressText.innerText = stringLocatorReplace.string.replace_started;
		noticeWrapper.innerHTML = '';

		progressIndicator.value = 0;
		progressIndicator.setAttribute( 'max', searchResultsTableRow.length );

		replaceSingleInstance( 0 );
	} );

	toggleButton.addEventListener( 'click', function() {
		replaceControls.classList.toggle( 'visible' );

		if ( replaceControls.classList.contains( 'visible' ) ) {
			toggleButton.setAttribute( 'aria-expanded', 'true' );
			toggleButton.innerText = stringLocatorReplace.string.button_hide;
		} else {
			toggleButton.setAttribute( 'aria-expanded', 'false' );
			toggleButton.innerText = stringLocatorReplace.string.button_show;
		}
	} );
} );

import '../sass/replace/replace.scss';
