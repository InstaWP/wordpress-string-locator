( function ( wp, $ ) {
	'use strict';

	if ( ! wp ) {
		return;
	}

	$( function () {
		$( document ).on( 'click', '.sl-instawp-install-now', function ( event ) {
			const $button = $( event.target );

			if ( $button.hasClass( 'instawp-activate-now' ) ) {
				return true;
			}

			event.preventDefault();

			if (
				$button.hasClass( 'updating-message' ) ||
				$button.hasClass( 'button-disabled' )
				) 
			{
				return;
			}

			if (
				wp.updates.shouldRequestFilesystemCredentials &&
				! wp.updates.ajaxLocked
				) 
			{
				wp.updates.requestFilesystemCredentials( event );

				$( document ).on( 'credential-modal-cancel', function () {
					const $message = $( '.sl-instawp-install-now.updating-message' );

					$message
					.removeClass( 'updating-message' )
					.text( wp.updates.l10n.installNow);

					wp.a11y.speak( wp.updates.l10n.updateCancel, 'polite' );
				} );
			}

			wp.updates.installPlugin( {
				slug: $button.data( 'slug' ),
			} );
		} );
	} );
} )( window.wp, jQuery );