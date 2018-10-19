( function( $ ) {

  var post_id;
  var post_deadline = '';

	$( function() {
		// Init the datepicker.
	  $( "#p2-post-deadline-datepicker" ).datepicker( p2postdeadlines.datepicker_settings );
	} );

  // Get deadline on o2 before save hook, because field is empty on save hook already.
  $( document ).on( 'pre-post-save.o2', function() {
    post_deadline = $( 'input#p2-post-deadline-datepicker' ).val();
  } );

	// Hook to p2_new_post_submit_success JS action and maybe send post deadline.
	$( document ).on( 'p2_new_post_submit_success post-post-save.o2', function( event, data ) {

    // Get post id on o2 installation.
    post_id = parseInt( data.id );

		// Get post id and deadline on P2 installation.
    if ( event.namespace !== 'o2' ) {
      post_id = parseInt( data.post_id );
      post_deadline = $( 'input#p2-post-deadline-datepicker' ).val();
    }

		// If deadline is set, send it.
		if ( post_id !== null && '' !== post_deadline ) {
			$.post(
				p2postdeadlines.ajaxurl, {
					'action': 'p2post_save_deadline',
					'post_id': post_id,
					'post_deadline': post_deadline,
					'p2post_save_deadline_nonce': $( '#p2post_save_deadline_nonce' ).val()
				}
			);

			// Empty deadline field after submission.
			$( 'input#p2-post-deadline-datepicker' ).val('')
		}
	} );
} )( jQuery );
