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

			// Retrieve data from the WP_List_Table column for the post
			var $program_number = $( '.column-dns_program_number', $post_row ).html();

			// Populate the quick edit form
			$( ':input[name="dns_program_number"]', $edit_row ).val( $program_number );
		}
	};
})(jQuery);