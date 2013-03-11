jQuery(document).ready(function($) {
	/* !Validate each form on the page */
	$( '.visual-form-builder' ).each( function() {
		$( this ).validate({
			rules: {
				"vfb-secret":{
					required: true,
					digits: true,
					maxlength:2
				}
			},
			errorPlacement: function(error, element) {
				if ( element.is( ':radio' ) || element.is( ':checkbox' ) )
					error.appendTo( element.parent().parent() );
				else if ( element.is( ':password' ) )
					error.hide();
				else
					error.insertAfter( element );
			} 
		});
	});
	
	
	/* !Display jQuery UI date picker */
	$( '.vfb-date-picker' ).datepicker();	
		
	// !Custom validation method to check multiple emails
	$.validator.addMethod( 'phone', function( value, element ) {
		// Strip out all spaces, periods, dashes, parentheses, and plus signs
		value = value.replace(/[\+\s\(\)\.\-\ ]/g, '');

		return this.optional(element) || value.length > 9 &&
			value.match( /^((\+)?[1-9]{1,2})?([-\s\.])?((\(\d{1,4}\))|\d{1,4})(([-\s\.])?[0-9]{1,12}){1,2}$/ );

		}, $.validator.format( 'Please enter a valid phone number. Most US/Canada and International formats accepted.' )
	);	
});