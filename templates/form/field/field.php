<?php
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( $control->visible ) {
    wordpresscrm_field_start();
    switch ( $control->labelPosition ) {
        case "Top":
            wordpresscrm_field_label( $control );
            wordpresscrm_field_type( $control );
            wordpresscrm_field_error( $control );
            break;
        case "Left":
        default:
            $control->labelClass .= " col-sm-6";

            wordpresscrm_field_label( $control );
            ?><div class="col-sm-6"><?php
                wordpresscrm_field_type( $control );
            ?></div><?php
            wordpresscrm_field_error( $control );
            break;
    }
    wordpresscrm_field_end();
} else {
    wordpresscrm_field_type_hidden( $control );
}
