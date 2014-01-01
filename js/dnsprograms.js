// Bind to the Add/Update post and check text length
var dns_program_character_limit = 500;
var overiFrame = -1;

// Detect which editor is showing within the Post Edit area, determine the length and update the counter
function editor_character_count() {
	var textcontainer = jQuery('textarea.wp-editor-area')[0];
	if (typeof textcontainer == 'undefined') {
		return;
	}
	var posttext = 0;
	if (textcontainer.style.display == 'none'
			|| (textcontainer.offsetWidth == 0 || textcontainer.offsetHeight == 0)
			|| textcontainer.parentNode.style.display == 'none') {
		posttext = jQuery('#content_ifr').contents().find('body#tinymce').text().length;
	}
	else {
		posttext = textcontainer.textLength;	
	}
	jQuery('.character-count').html(posttext);
	
	if (posttext > dns_program_character_limit) {
		jQuery('.character-count, .character-limit').addClass( 'red' );
	}
	else {
		jQuery('.character-count, .character-limit').removeClass( 'red' );		
	}
}

jQuery(document).ready(function() {
	// Hide the WYSIWYG Editor
	jQuery('#ed_toolbar, #content-tmce').hide();
	jQuery('#content-html').trigger('click');
	
	// Initialize the post editor character count
	editor_character_count();
	setInterval(function() { 
		editor_character_count(); 
	}, 5000);
	
	// Initialize calendar date/time pickers
	jQuery('#dns_start_date').datepicker();
	jQuery('#dns_end_date').datepicker();
	jQuery('#dns_start_time').timepicker({'timeFormat':'h:i A'});
	jQuery('#dns_end_time').timepicker({'timeFormat':'h:i A'});
	
	// Initialize price field disable functionality
	enableCheck('input#dns_pa_enable','input[name="dns_price_adult"]');
	enableCheck('input#dns_pc_enable','input[name="dns_price_child"]');
	enableCheck('input#dns_mpa_enable','input[name="dns_mem_price_adult"]');
	enableCheck('input#dns_mpc_enable','input[name="dns_mem_price_child"]');
	
	jQuery('input[name="dns_frequency"]:radio').change(function() {
		var check_val = parseInt(jQuery('input[name="dns_frequency"]:checked').val());
		switch(check_val) {
			case 0:
				jQuery('#days_container, #month_container').hide();
				break;
			case 1:
				jQuery('#days_container').show();
				jQuery('#month_container').hide();
				break;
			case 2:
				jQuery('#days_container').hide();
				jQuery('#month_container').show();
				break;
			default:
				break;
		}
	});
	
	// Special post validation rules using the jQuery Validate plugin
	jQuery('#post').validate({
		rules: {
			dns_start_date: { datecompare: 'true' },
			post_age_ranges: { valueNotEquals: 'none' },
			post_brochure_editions: { valueNotEquals: 'none' },
			post_program_locations: { valueNotEquals: 'none' },
			teachers_required: 'required'
		},
		messages: {
			post_age_ranges: { valueNotEquals: 'Select an age range' },
			post_brochures_editions: { valueNotEquals: 'Select a brochure edition' },
			post_program_locations: { valueNotEquals: 'Select a location' },
			teachers_required: 'required'
		},
		submitHandler: function(form) {
			var posttext = jQuery('#content_ifr').contents().find('body#tinymce').text();
			posttext.replace(/(<([^>]+)>)/ig,"");
			if ( posttext.length > dns_program_character_limit ) {
				var desc_error = "Description length is {0} characters.  The limit is {1} characters, please shorten the description and try again.";
				desc_error = desc_error.format( posttext.length, dns_program_character_limit );
				alert( desc_error );
				jQuery('#publish').removeClass('button-primary-disabled'); 
				jQuery('.spinner').css('visibility', 'hidden'); 
				return false;
			}
			else {
				form.submit();
			}
		},
		invalidHandler: function() { 
			jQuery('#publish').removeClass('button-primary-disabled'); 
			jQuery('.spinner').css('visibility', 'hidden'); 
		} 
	});
	
	jQuery('#save-post').click(function(){
		jQuery('#post').validate().cancelSubmit = true;		
	});

});

function enableCheck($enable,$field) {
	jQuery($enable).change(function() {
		if(jQuery($enable).is(':checked')) {
		    jQuery($field).prop('disabled',false);
		} else {
		    jQuery($field).prop('disabled',true);
		}
	});
}

// jQuery Validation Rule for Select Options
jQuery.validator.addMethod( 'valueNotEquals', function( value, element, arg ){ return arg != value; }, 
		'Value must not equal default option.' );

jQuery.validator.addMethod( 'teachers_required', function ( value ) { 
	return jQuery( '.teachers_required:checked' ).size() > 0; }, 'Please select at least on teacher.');

// jQuery Validation date comparison method
jQuery.validator.addMethod( 'datecompare', function( value ) {
	datematch = '^((0?[1-9]|1[012])[- /.](0?[1-9]|[12][0-9]|3[01])[- /.](19|20)?[0-9]{2})*$';
	timematch = '^((0?[1-9]|1[012]):[0-5][0-9] (A|P)M)$';
	start = jQuery( '#dns_start_date' ).val().trim();
	start_time = jQuery('#dns_start_time').val().trim();
	end = jQuery( '#dns_end_date' ).val().trim();
	end_time = jQuery('#dns_end_time').val().trim();
	
	// Validate correct date format
	if ( !start.match(datematch) || !end.match(datematch) ) {
		return false;
	}

	// Validate correct time format
	if ( !start_time.match(timematch) || !end_time.match(timematch) ) {
		return false;
	}

	// Prepare the valid date and time strings to make Date object for comparison
	start_array = start.split('/');
	end_array = end.split('/');

	start_time = start_time.split(':');
	end_time = end_time.split(':');
	
	if(start_time[1].match('PM')) {
		start_time[0] = parseInt(start_time[0]);
		if(start_time[0] != 12) {
			start_time[0] = start_time[0] + 12;
		}
	}
	else if(start_time[1].match('AM')) {
		start_time[0] = parseInt(start_time[0]);
		if(start_time[0] == 12) {
			start_time[0] = 0;
		}
	}
	start_time[1] = parseInt(start_time[1].substr(0, 2));

	if(end_time[1].match('PM')) {
		end_time[0] = parseInt(end_time[0]);
		if(end_time[0] != 12) {
			end_time[0] = end_time[0] + 12;
		}
	}
	else if(end_time[1].match('AM')) {
		end_time[0] = parseInt(end_time[0]);
		if(end_time[0] == 12) {
			end_time[0] = 0;
		}
	}
	end_time[1] = parseInt(end_time[1].substr(0, 2));

	start_date = new Date(start_array[2], (start_array[0] - 1), start_array[1], start_time[0], start_time[1]);
	end_date = new Date(end_array[2], (end_array[0] - 1), end_array[1], end_time[0], end_time[1]);
	
	if ( start_date > end_date ) {
		return false;
	}
	return true;
}, 'Start date must be before the End date.');

// Basic string format function, like printf in PHP
if (!String.prototype.format) {
	String.prototype.format = function() {
		var args = arguments;
		return this.replace(/{(\d+)}/g, function(match, number) { 
			return typeof args[number] != 'undefined' ? args[number] : match;
		});
	};
}
