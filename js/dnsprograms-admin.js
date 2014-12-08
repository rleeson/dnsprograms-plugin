/**
 * DNS Programs JavaScript admin functionality
 * 
 * @since 1.3
 * @version 1.4
 */

var dns_programs_admin = (function($) {
	/**
	 * Check if a featured image has been associated with the program, disables/enabled based on image association
	 */
	function detectFeaturedImage() {
		if ( $.find('#postimagediv').length !== 0 ) {
			insertImageWarning();
			if ( $('#postimagediv').find('img').length===0 ) {
				$('#nofeature-message').addClass("error").html('<p>Featured Image Required</p>');
				$('#publish').attr('disabled','disabled');
			} 
			else {
				$('#nofeature-message').remove();
				$('#publish').removeAttr('disabled');
			}
		}
	}
	
	/**
	 * Register error message area in the post/program message area
	 */
	function insertImageWarning() {
	    if ($('body').find("#nofeature-message").length===0) {
			$('h2').after('<div id="nofeature-message"></div>');
	    }
	}
	
	function readyHandler() { 
		$(document).ready(function() {
			registerImageWarning();
			
			$('#dns_export_program_csv').on( 'click', function(e) {
				e.preventDefault();
				
				var brochure = $('#brochure').val();
				
				// Ensures a clean pass of the appropriate export command
				var url = this.href.split('?')[0] + '?export=programs&brochure=' + brochure;
								
				// Validate correct date format is attached
				window.location.href = url;
			});
		});
	}
	
	/**
	 * Initialize featured image check for programs
	 */
	function registerImageWarning() {
		insertImageWarning();
		setInterval(detectFeaturedImage, 5000);
		detectFeaturedImage();		
	}
	
	return {
		Init: function() {
			readyHandler();
		}
	};
})(jQuery);

dns_programs_admin.Init();