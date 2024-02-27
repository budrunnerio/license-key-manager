jQuery(function($) {

	/**
	 * Update the frequency for subscription
	 */
	$('button[name="sf_change_frequency_save"]').click(function(e) {
		e.preventDefault();

		$.ajax({
			url: sf_change_frequency.root + '/update',
			method: 'POST',
			data: {
				frequency: $('#sf_subscription_frequency option:selected').val(),
				subscription_id: $('#sf_subscription_frequency').attr('data-subscription_id')
			},
			beforeSend: function ( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', sf_change_frequency.nonce )
			},
		}).done( function ( response ) {

			// Update Notification
			$('#sf_change_frequency_subscription_row .view .notification').slideDown().text( response.data.html ).delay(10000).slideUp();

			// Update view state text
			$('#sf_change_frequency_subscription_row .view span').text( response.data.frequency_text );

			// Update table row status
			$("#sf_change_frequency_subscription_row .view").show();
			$("#sf_change_frequency_subscription_row .edit").hide();

			setTimeout(function () {
				location.reload(true);
			}, 10000);
		} );

	});

	// Edit state
	$("#sf_change_frequency_subscription_row a#sf_change_frequency_edit_button").click(function(e) {
		e.preventDefault();

		$("#sf_change_frequency_subscription_row .edit").show();
		$("#sf_change_frequency_subscription_row .view").hide();
	});

	// Cancel button
	$("#sf_change_frequency_subscription_row a#sf_change_frequency_cancel").click(function(e) {
		e.preventDefault();

		$("#sf_change_frequency_subscription_row .view").show();
		$("#sf_change_frequency_subscription_row .edit").hide();
	});
});