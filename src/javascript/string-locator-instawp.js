( function ( wp, $ ) {
	'use strict';

	if ( ! wp ) {
		return;
	}

	$( function () {
		$( document ).on( 'click', '.instawp-activate-now', function ( event ) {
			const $button = $( event.target );
			let slug = $button.data( 'slug' );

			$button.text('Activating plugin ...');

			event.preventDefault();

			let data = {
				action: 'install_activate_plugin',
				plugin: slug,
				security: my_ajax_object.security
			}; 

			$.post(ajaxurl, data, function(response) {
				if (response.success) {
					$button.text(response.data.anchor_text);
					// $button.attr
					$button.attr('onclick', `window.open('${response.data.href}', '_blank');`);
					$button.attr('data-originaltext', response.data.anchor_text);
					$button.attr('aria-label', response.data.anchor_text);
					$button.attr('target', '_blank');
					$button.removeClass('instawp-activate-now');
					$button.addClass('string-locator-instawp-button disabled');
				} else {
					document.querySelector('.wrap').insertAdjacentHTML('afterbegin', `<div class="notice notice-error is-dismissible"><p>Error installing and activating the plugin: ${response.data}</p><button type="button" class="notice-dismiss" onclick="this.parentNode.remove();"></button></div>`);
				}
			});
		});


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
			setTimeout(function() {

				$button.text('Installing plugin...');
			}, 200);

			wp.updates.installPlugin( {
				slug: $button.data( 'slug' ),
				success: function() {
					$button.text('Activating plugin ...');

					let data = {
						action: 'install_activate_plugin',
						security: my_ajax_object.security
					}; 
					$.post(ajaxurl, data, function(response) {
						if (response.success) {
							$button.text('Plugin installed'); 

							setTimeout(function() {

								$button.text(response.data.anchor_text);
								$button.attr('onclick', `window.open('${response.data.href}', '_blank');`);
								$button.attr('data-originaltext', response.data.anchor_text);
								$button.attr('aria-label', response.data.anchor_text);
								$button.attr('target', '_blank');
								$button.removeClass('sl-instawp-install-now install-now install-instawp-connect updating-message');
								$button.addClass('string-locator-instawp-button disabled');

								$(".update-nag").remove();

							}, 3000);
						} else {
							document.querySelector('.wrap').insertAdjacentHTML('afterbegin', `<div class="notice notice-error is-dismissible"><p>Error installing and activating the plugin: ${response.data}</p><button type="button" class="notice-dismiss" onclick="this.parentNode.remove();"></button></div>`);
						}
					});

				},
				error: function(error) {
					document.querySelector('.wrap').insertAdjacentHTML('afterbegin', `<div class="notice notice-error is-dismissible"><p>Error installing the plugin: ${error}</p><button type="button" class="notice-dismiss" onclick="this.parentNode.remove();"></button></div>`);
				}
			} );
		} );
	} );
} )( window.wp, jQuery );