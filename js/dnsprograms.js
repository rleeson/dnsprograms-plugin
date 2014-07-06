/**
 * DNS Programs JavaScript functionality
 * 
 * @since 1.1
 */

// Bind to the Add/Update post and check text length
var dns_program_character_limit = 500;
var dns_program_title_limit	= 60;
var overiFrame = -1;

var dns_programs = (function($) {
	// Character count limit for TinyMCE form
	function editor_character_count() {
		// Check to see if the editor exists, if not, return
		var textcontainer = $('textarea.wp-editor-area')[0];
		if (typeof textcontainer == 'undefined') {
			return;
		}
		
		// Text counter
		var posttext = 0;
		
		// If the TinyMCE editor is hidden (Text is selected),
		// check the character count of the Text area
		if (textcontainer.style.display == 'none'
				|| (textcontainer.offsetWidth == 0 || textcontainer.offsetHeight == 0)
				|| textcontainer.parentNode.style.display == 'none') {
			posttext = $('#content_ifr').contents().find('body#tinymce').text().length;
		}
		else {
			posttext = textcontainer.textLength;	
		}
		
		// Set the character count
		$('#edit-character-count .character-count').html(posttext);
		
		// Error highlighting for a character counter beneath the editor area
		if (posttext > dns_program_character_limit) {
			$('#edit-character-count .character-count, #edit-character-count .character-limit').addClass( 'red' );
		}
		else {
			$('#edit-character-count .character-count, #edit-character-count .character-limit').removeClass( 'red' );		
		}
	}
	// Character count limit the post title
	function title_character_count() {
		// Check to see if the editor exists, if not, return
		var textcontainer = $('input[name="post_title"]');
		if (typeof textcontainer == 'undefined') {
			return;
		}
		
		// Text counter
		var titlelength = textcontainer.val().length;
				
		// Set the character count
		$('#title-character-count .character-count').html(titlelength);
		
		// Error highlighting for a character counter beneath the editor area
		if (titlelength > dns_program_title_limit) {
			$('#title-character-count .character-count, #title-character-count .character-limit').addClass( 'red' );
		}
		else {
			$('#title-character-count .character-count, #title-character-count .character-limit').removeClass( 'red' );		
		}
	}
	function enableCheck($enable,$field) {
		$($enable).change(function() {
			if($($enable).is(':checked')) {
			    $($field).prop('disabled',false);
			} else {
			    $($field).prop('disabled',true);
			}
		});
	}
	function ready_handler() {
		$(document).ready(function() {
			// Hide the WYSIWYG Editor
			$('#ed_toolbar, #content-tmce').hide();
			$('#content-html').trigger('click');
			
			// Initialize the post editor character count
			editor_character_count();
			title_character_count();
			setInterval(function() {
				editor_character_count(); 
				title_character_count();
			}, 5000);
			
			// Initialize calendar date/time pickers
			$('input[name="dns_start_date"]').datepicker({ 
				autoSize: true,
				changeMonth: true,
				closeText: 'Close',
				dateFormat: 'mm/dd/yy',
				onClose: function(dateText, inst){
					console.log($(this));
					// Ensures the entered text matches the requested format
					var field_id = $(this).attr('id');
					var field_date = $(this).datepicker('getDate');
					
					if ( field_date ) {
						var field_date = $.datepicker.formatDate( 'mm/dd/yy', field_date );
					}
					else {
						// Clears the input on empty date
						field_date = '';
					}
					$('#' + field_id).val(field_date);
				},
				showButtonPanel: true
			});
			$('input[name="dns_end_date"]').datepicker({
				autoSize: true,
				changeMonth: true,
				closeText: 'Close',
				dateFormat: 'mm/dd/yy',
				onClose: function(dateText, inst){
					// Ensures the entered text matches the requested format
					var field_id = $(this).attr('id');
					var field_date = $(this).datepicker('getDate');
					
					if ( field_date ) {
						var field_date = $.datepicker.formatDate( 'mm/dd/yy', field_date );
					}
					else {
						// Clears the input on empty date
						field_date = '';
					}
					$('#' + field_id).val(field_date);
				},
				showButtonPanel: true
			});
			$('input[name="dns_start_time"]').timepicker({'timeFormat':'h:i A'});
			$('input[name="dns_end_time"]').timepicker({'timeFormat':'h:i A'});
			
			// Initialize price field disable functionality
			enableCheck('input[name="dns_pa_enable"]','input[name="dns_price_adult"]');
			enableCheck('input[name="dns_mpa_enable"]','input[name="dns_mem_price_adult"]');
			
			$('input[name="dns_frequency"]:radio').change(function() {
				var check_val = parseInt($('input[name="dns_frequency"]:checked').val());
				switch(check_val) {
					case 0:
						$('#days_container, #month_container').hide();
						break;
					case 1:
						$('#days_container').show();
						$('#month_container').hide();
						break;
					case 2:
						$('#days_container').hide();
						$('#month_container').show();
						break;
					default:
						break;
				}
			});
			
			// Special post validation rules using the $ Validate plugin
			// TODO: Figure out why this is throwing a browser navigation message
			$('#post').validate({
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
					var posttext = $('#content_ifr').contents().find('body#tinymce').text();
					posttext.replace(/(<([^>]+)>)/ig,"");
					if ( posttext.length > dns_program_character_limit ) {
						var desc_error = "Description length is {0} characters.  The limit is {1} characters, please shorten the description and try again.";
						desc_error = desc_error.format( posttext.length, dns_program_character_limit );
						alert( desc_error );
						$('#publish').removeClass('button-primary-disabled'); 
						$('.spinner').css('visibility', 'hidden'); 
						return false;
					}
					else {
						form.submit();
					}
				},
				invalidHandler: function() { 
					$('#publish').removeClass('button-primary-disabled'); 
					$('.spinner').css('visibility', 'hidden'); 
				} 
			});
			
			// Special post validation rules using the $ Validate plugin
			$('#posts-filter').validate({
				rules: {
					dns_start_date: { datecompare: 'true' },
				},
				messages: {
				},
				submitHandler: function(form) {
						form.submit();
				},
				invalidHandler: function() { 
					$('#publish').removeClass('button-primary-disabled'); 
					$('.spinner').css('visibility', 'hidden'); 
				} 
			});
			
			$('#save-post').click(function(){
				$('#post').validate().cancelSubmit = true;		
			});
		});
	}
	return {
		init: function() {
			ready_handler();
		}
	}
})(jQuery);

dns_programs.init();

/**
 * Add additional validation methods for the jQuery Validator
 */

// jQuery Validation Rule for Select Options
jQuery.validator.addMethod( 'valueNotEquals', function( value, element, arg ){ return arg != value; }, 
		'Value must not equal default option.' );

jQuery.validator.addMethod( 'teachers_required', function ( value ) { 
	return jQuery( '.teachers_required:checked' ).size() > 0; }, 'Please select at least on teacher.');

// jQuery Validation date comparison method
jQuery.validator.addMethod( 'datecompare', function( value ) {
	datematch = '^((0?[1-9]|1[012])[- /.](0?[1-9]|[12][0-9]|3[01])[- /.](19|20)?[0-9]{2})*$';
	timematch = '^((0?[1-9]|1[012]):[0-5][0-9] (A|P)M)$';
	start = jQuery( 'input[name="dns_start_date"]' ).val().trim();
	start_time = jQuery('input[name="dns_start_time"]').val().trim();
	end = jQuery( 'input[name="dns_end_date"]' ).val().trim();
	end_time = jQuery('input[name="dns_end_time"]').val().trim();

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
