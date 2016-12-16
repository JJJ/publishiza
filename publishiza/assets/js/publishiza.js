jQuery( function( $ ) {
	var $publish_btn  = $( '#publish' ),
	    $edit         = $( '#edit-publishiza' ),
	    $save         = $( '#save-publishiza' ),
	    $cancel       = $( '#cancel-publishiza' ),
	    $select       = $( '#publishiza-select' ),
	    $display      = $( '#publishiza-display' ),
	    $publish_text = $publish_btn.val();

	$( '.misc-pub-section.curtime.misc-pub-section-last' ).removeClass( 'misc-pub-section-last' );

	$edit.on( 'click', function( e ) {
		$( this ).hide();
		$select.slideDown();
		e.preventDefault();
	} );

	$save.on( 'click', function( e ) {
		var $select_text = $( '#publishiza :selected' ).text();

		$select.slideUp();
		$edit.show();
		$display.text( $select_text );

		if ( 'on' === $select_text ) {
			$publish_btn.val( '\u{1F4A9}\u{1F329}' );
		} else {
			$publish_btn.val( $publish_text );
		}

		e.preventDefault();
	} );

	$cancel.on( 'click', function( e ) {
		$select.slideUp();
		$edit.show();
		e.preventDefault();
	} );
} );
