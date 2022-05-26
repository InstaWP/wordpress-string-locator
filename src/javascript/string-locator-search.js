/* global string_locator */
jQuery( document ).ready( function( $ ) {
	let stringLocatorSearchActive = false;
	const resultTemplate = wp.template( 'string-locator-search-result' );

	function addNotice( title, message, format ) {
		$( '.notices' ).append( '<div class="notice notice-' + format + ' is-dismissible"><p><strong>' + title + '</strong><br />' + message + '</p></div>' );
	}

	function throwError( title, message ) {
		stringLocatorSearchActive = false;
		$( '.string-locator-feedback' ).hide();
		addNotice( title, message, 'error' );
	}

	function finalizeStringLocatorSearch() {
		stringLocatorSearchActive = false;

		$( '#string-locator-feedback-text' ).text( '' );

		$.post(
			string_locator.url.clean,
			{
				_wpnonce: string_locator.rest_nonce,
			},
			function() {
				$( '.string-locator-feedback' ).hide();
				if ( $( 'tbody', '.tools_page_string-locator' ).is( ':empty' ) ) {
					$( 'tbody', '.tools_page_string-locator' ).html( '<tr><td colspan="3">' + string_locator.search_no_results + '</td></tr>' );
				}
			}
		).fail( function( xhr, textStatus, errorThrown ) {
			throwError( xhr.status + ' ' + errorThrown, string_locator.search_error );
		} );
	}

	function clearStringLocatorResultArea() {
		$( '.notices' ).html( '' );
		$( '#string-locator-search-progress' ).removeAttr( 'value' );
		$( 'tbody', '.tools_page_string-locator' ).html( '' );
	}

	function performStringLocatorSingleSearch( maxCount, thisCount ) {
		if ( thisCount >= maxCount || ! stringLocatorSearchActive ) {
			$( '#string-locator-feedback-text' ).html( string_locator.saving_results_string );
			finalizeStringLocatorSearch();
			return false;
		}

		const searchRequest = {
			filenum: thisCount,
			_wpnonce: string_locator.rest_nonce,
		};

		$.post(
			string_locator.url.search,
			searchRequest,
			function( response ) {
				if ( ! response.success ) {
					if ( false === response.data.continue ) {
						throwError( string_locator.warning_title, response.data.message );
						return false;
					}

					addNotice( string_locator.warning_title, response.data.message, 'warning' );
				}

				if ( undefined !== response.data.search ) {
					$( '#string-locator-search-progress' ).val( response.data.filenum );
					$( '#string-locator-feedback-text' ).html( string_locator.search_current_prefix + response.data.next_file );

					stringLocatorAppendResult( response.data.search );
				}
				const nextCount = response.data.filenum + 1;
				performStringLocatorSingleSearch( maxCount, nextCount );
			},
			'json'
		).fail( function( xhr, textStatus, errorThrown ) {
			throwError( xhr.status + ' ' + errorThrown, string_locator.search_error );
		} );
	}

	function stringLocatorAppendResult( totalEntries ) {
		if ( $( '.no-items', '.tools_page_string-locator' ).is( ':visible' ) ) {
			$( '.no-items', '.tools_page_string-locator' ).hide();
		}
		if ( Array !== totalEntries.constructor ) {
			return false;
		}

		totalEntries.forEach( function( entries ) {
			if ( entries ) {
				for ( let i = 0, amount = entries.length; i < amount; i++ ) {
					const entry = entries[ i ];

					if ( undefined !== entry.stringresult ) {
						$( 'tbody', '.tools_page_string-locator' ).append( resultTemplate( entry ) );
					}
				}
			}
		} );
	}

	$( '#string-locator-search-form' ).on( 'submit', function( e ) {
		e.preventDefault();
		$( '#string-locator-feedback-text' ).text( string_locator.search_preparing );
		$( '.string-locator-feedback' ).show();
		stringLocatorSearchActive = true;
		clearStringLocatorResultArea();

		const directoryRequest = JSON.stringify(
			{
				directory: $( '#string-locator-search' ).val(),
				search: $( '#string-locator-string' ).val(),
				regex: $( '#string-locator-regex' ).is( ':checked' )
			}
		);

		$( 'table.tools_page_string-locator' ).show();

		$.post(
			string_locator.url.directory_structure,
			{
				data: directoryRequest,
				_wpnonce: string_locator.rest_nonce,
			},
			function( response ) {
				if ( ! response.success ) {
					addNotice( response.data, 'alert' );
					return;
				}
				$( '#string-locator-search-progress' ).attr( 'max', response.data.total ).val( response.data.current );
				$( '#string-locator-feedback-text' ).text( string_locator.search_started );
				performStringLocatorSingleSearch( response.data.total, 0 );
			},
			'json'
		).fail( function( xhr, textStatus, errorThrown ) {
			throwError( xhr.status + ' ' + errorThrown, string_locator.search_error );
		} );
	} );
} );
