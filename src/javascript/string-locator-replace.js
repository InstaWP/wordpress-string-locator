/* global stringLocatorReplace, fetch, confirm, FormData, Event */
document.addEventListener( 'DOMContentLoaded', function() {
	const replaceStringField = document.getElementById( 'string-locator-replace-new-string' ),
		replaceLoopbackCheckbox = document.getElementById( 'string-locator-replace-loopback-check' ),
		toggleButton = document.getElementById( 'string-locator-toggle-replace-controls' ),
		replaceControls = document.getElementById( 'string-locator-replace-form' ),
		replaceForm = document.getElementById( 'string-locator-replace-form' ),
		searchResultsTable = document.getElementById( 'string-locator-search-results-table' ),
		noticeWrapper = document.getElementById( 'string-locator-search-notices' ),
		progressWrapper = document.getElementById( 'string-locator-progress-wrapper' ),
		progressIndicator = document.getElementById( 'string-locator-search-progress' ),
		progressText = document.getElementById( 'string-locator-feedback-text' ),
		searchString = document.getElementById( 'string-locator-string' ),
		searchRegex = document.getElementById( 'string-locator-regex' ),
		replaceFormButtonAll = document.getElementById( 'string-locator-replace-button-all' ),
		replaceFormButtonSelect = document.getElementById( 'string-locator-replace-button-selected' );

	let searchResultsTableRow,
		searchResultText,
		replaceFormConfirmed = false,
		replaceFormDoAll = false;

	function replaceSingleInstance( instance ) {
		const formData = new FormData(),
			dataSets = { ...searchResultsTableRow[ instance ].dataset };

		progressIndicator.value = instance;

		// Check if this line has been ticked off if not replacing all entries.
		if ( ! replaceFormDoAll ) {
			if ( ! searchResultsTableRow[ instance ].getElementsByClassName( 'check-column-box' )[ 0 ].checked ) {
				if ( instance < ( searchResultsTableRow.length - 1 ) ) {
					replaceSingleInstance( instance + 1 );
				} else {
					progressWrapper.style.display = 'none';
					replaceFormDoAll = false;
				}

				return;
			}
		}

		formData.append( '_wpnonce', stringLocatorReplace.rest_nonce );
		formData.append( 'replace_nonce', stringLocatorReplace.replace_nonce );
		formData.append( 'replace_string', replaceStringField.value );
		formData.append( 'search_string', searchString.value );
		formData.append( 'search_regex', searchRegex.checked );
		formData.append( 'replace_loopback', replaceLoopbackCheckbox.checked );

		for ( const key in dataSets ) {
			formData.append( key, dataSets[ key ] );
		}

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
				replaceFormDoAll = false;
			}
		} ).catch( function( error ) {
			noticeWrapper.style.display = 'block';
			noticeWrapper.innerHTML = error.message;
		} );
	}

	function handleFormSubmission() {
		if ( ! replaceFormConfirmed ) {
			replaceFormButtonAll.dispatchEvent( new Event( 'click' ) );

			return false;
		}

		searchResultsTableRow = searchResultsTable.getElementsByTagName( 'tbody' )[ 0 ].getElementsByTagName( 'tr' );

		progressWrapper.style.display = 'block';
		progressText.innerText = stringLocatorReplace.string.replace_started;
		noticeWrapper.innerHTML = '';

		progressIndicator.value = 0;
		progressIndicator.setAttribute( 'max', searchResultsTableRow.length );

		replaceFormConfirmed = false;

		replaceSingleInstance( 0 );
	}

	replaceForm.addEventListener( 'submit', function( e ) {
		e.preventDefault();

		handleFormSubmission();

		return false;
	} );

	replaceFormButtonAll.addEventListener( 'click', function() {
		if ( ! confirm( stringLocatorReplace.string.confirm_all ) ) { // eslint-disable-line no-alert -- We need the users confirmation before doing an action on all results.
			return false;
		}

		replaceFormDoAll = true;
		replaceFormConfirmed = true;

		handleFormSubmission();
	} );
	replaceFormButtonSelect.addEventListener( 'click', function() {
		replaceFormDoAll = false;
		replaceFormConfirmed = true;

		handleFormSubmission();
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
