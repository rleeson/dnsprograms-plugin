(function($) {
	$(document).ready(function() {
		$('#dns_export_program_csv').on('click',function(e){
			e.preventDefault();
			
			var brochure = $('#brochure').val();
			
			// Ensures a clean pass of the appropriate export command
			var url = this.href.split('?')[0] + '?export=programs&brochure=' + brochure;
							
			// Validate correct date format is attached
			window.location.href = url;
		});
	});
})(jQuery);