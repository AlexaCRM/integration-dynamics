( function( $ ) {
    "use strict";

    $( function() {
        $( '.crm-datepicker' ).datetimepicker( {
            timepicker: false,
            format: window.wpcrm.dateformat,
            scrollInput: false
        } );

        $( '.crm-datetimepicker' ).datetimepicker( {
            format: window.wpcrm.datetimeformat,
            scrollInput: false
        } );
    } );
}( jQuery ) );
