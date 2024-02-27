jQuery(function($) {

	var available_frequencies = {
		data: {
			frequencies: {}
		},

		/**
		 * Run when page loads and bring data.frequencies up to date with what is saved on DB
		 */
		update_backend_data: function() {
			var freqs = $('input#sf_change_frequency_available_frequencies').val();

			this.data.frequencies = JSON.parse( freqs );
		},

		// Append html row on backend
		append: function( id ) {

			if( ! $('table#sf_admin_field_user_frequencies tr[data-id="'+ id +'"]').length ) {
				$('table#sf_admin_field_user_frequencies').append(
					'<tr data-id="'+ id +'">'+
						'<td class="frequency">'+ this.data.frequencies[id].interval_text +' '+ this.data.frequencies[id].period_text +'</td>'+
						'<td class="delete">'+
							'<button name="delete_user_frequency" data-id="'+ id +'" class="button-primary" type="submit" value="Delete">Delete</button>'+
						'</td>'+
					'</tr>');
			} else {
				$('span.frequency_notice').slideDown().delay(4000).slideUp();
			}
			
		},

		// Delete a single frequency html from the list
		remove: function( id ) {
			$('tr[data-id="'+ id +'"]').remove();
		},

		// Add frequency to data object
		add: function() {

			// Get data
			var period = $("#available_frequency_period option:selected").val();
			var period_text = $("#available_frequency_period option:selected").text();
			var interval = $("#available_frequency_interval option:selected").val();
			var interval_text = $("#available_frequency_interval option:selected").text();
			var id = period + '_' + interval;


			// Add data
			this.data.frequencies[ id ] = {
				period: period,
				period_text: period_text,
				interval: interval,
				interval_text: interval_text
			}
			
			return id;
		},
		exists: function( id ) {
			return this.data.frequencies.hasOwnProperty(id);
		},

		// Delete a frequency, main method
		_delete: function( id ) {
			this.remove( id );
			this.delete( id );
			this.update_data();
		},

		// Delete a frequency from data object
		delete: function( id ) {
			delete this.data.frequencies[id];
		},

		// Add a frequency, main method
		_add: function() {
			var id = this.add();

			this.update_data();
			this.append( id );
		},

		// Serialize data.frequencies object
		serialize: function() {
			return JSON.stringify( this.data.frequencies );
		},

		// Update serialized data to the field
		update_data: function() {
			$('input#sf_change_frequency_available_frequencies').val( this.serialize() );
		}
	}

	/**
	 * Run event to add frequency
	 */
	$('button[name="add_available_frequency"]').on('click', function(e) {
		e.preventDefault();

		available_frequencies._add();
	});

	/**
	 * Run event for frequency deletion
	 */
	$(document).on('click', 'button[name="delete_user_frequency"]', function(e) {
		e.preventDefault();
		available_frequencies._delete( $(this).attr('data-id') );
	});

	$(document).ready(function() {
		available_frequencies.update_backend_data();

		// Hide the field
		$('input[name="sf_change_frequency_available_frequencies"]').parents('tbody').hide();

	});

});

