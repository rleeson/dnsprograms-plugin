/**
 * WP Inline Edit override for Quick Edit functionality of Program Data
 */

(function($) {
	// Create a copy of WP inline edit post
	var $wp_inline_edit = inlineEditPost.edit;
	
	// Override the function
	inlineEditPost.edit = function( id ) {
		// Call the original WP edit function
		$wp_inline_edit.apply( this, arguments );

		// Get the post ID
		var $post_id = 0;
		if ( typeof( id ) == 'object' )
			$post_id = parseInt( this.getId( id ) );

		if ( $post_id > 0 ) {
			// Define the edit row
			var $edit_row = $( '#edit-' + $post_id );
			var $post_row = $( '#post-' + $post_id );
			var $text_fields = ['dns_program_number',
								'dns_price_adult',
								'dns_mem_price_adult',
								'dns_max_participants',
								'dns_start_date', 
								'dns_end_date', 
								'dns_start_time', 
								'dns_end_time',
								'dns_frequency', 
								'dns_days_week', 
								'dns_day_month'];
			
			// Retrieve data from the WP_List_Table column for the post, insert it into the quick edit field
			$text_fields.forEach( function( $field ) {
				var $field_value = $( '.column-' + $field, $post_row ).html();
				$( ':input[name="' + $field + '"]', $edit_row ).val( $field_value );
			});
		}
	};
})(jQuery);