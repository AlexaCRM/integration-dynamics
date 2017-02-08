( function( $ ) {
    "use strict";

    var validationSettings = {
        highlight: function( element, errorClass ) {
            var $element = $( element ),
                $formGroup = $element.parents( '.form-group' );

            if ( $formGroup.hasClass( 'has-error' ) || $formGroup.hasClass( 'has-danger' ) ) {
                return;
            }

            $formGroup.addClass( 'has-error has-danger has-feedback' );
            $element.after( '<span class="glyphicon glyphicon-remove form-control-feedback" aria-hidden="true"></span>' );
            $element.addClass( 'form-control-danger' );
        },
        unhighlight: function( element, errorClass ) {
            var $element = $( element ),
                $formGroup = $element.parents( '.form-group' );

            $formGroup.removeClass( 'has-error has-danger has-feedback' );
            $formGroup.find( '.form-control-feedback' ).remove();
            $element.removeClass( 'form-control-danger' );
        },
        errorPlacement: function( $error, $element ) {
            $error.appendTo( $element.parent() );
        },
        errorClass: 'help-block form-control-feedback'
    };

    $( function() {
        $( '.crm-datepicker' ).datetimepicker( {
            timepicker: false,
            format: window.wpcrm.dateformat
        } );

        $( '.crm-datetimepicker' ).datetimepicker( {
            format: window.wpcrm.datetimeformat
        } );

        // customize email validation regex
        $.validator.methods.email = function( value, element ) {
            return this.optional( element ) || /(?:[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*|"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*")@(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\[(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?|[a-z0-9-]*[a-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])/.test( value );
        };

        if ( window.wpcrmValidationSettings ) {
            $.each( window.wpcrmValidationSettings, function( formId, settings ) {
                $( '#' + formId ).validate( $.extend( {}, settings, validationSettings ) );
            } );
        }
    } );

} )( jQuery );
