( function( $ ) {
	$( function() {
	  $( "#p2-post-deadline-datepicker" ).datepicker( p2postdeadlines );
	} );

	$( document ).on( 'p2_new_post_submit_success', function( event, data ) {
		var post_deadline = $( 'input#p2-post-deadline' ).val();

		if ( '' !== post_deadline ) {
			$.post(
				ajaxUrl.replace( '?p2ajax=true', '' ), {
					'action': 'p2post_save_deadline',
					'post_id': parseInt( data.post_id ),
					'post_deadline': $( 'input#p2-post-deadline' ).val(),
					'p2post_save_deadline_nonce': $( '#p2post_save_deadline_nonce' ).val()
				}
			);

			$( 'input#p2-post-deadline' ).val('')
		}
	} )
} )( jQuery );
