<?php
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( $control->visible ) {
    $control->labelClass .= " col-sm-4";

    wordpresscrm_field_start( $control );
    wordpresscrm_field_label( $control );

    $value = $control->value;
    if ( $control->type === 'datetime' && !empty( $value ) ) {
        $format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
        if ( $control->format === 'dateonly' ) {
            $format = get_option( 'date_format' );
        }

        $value = date( $format, $value );
    }
    ?><div class="col-sm-8"><p class="form-control-static"><?php echo ( !empty( $control->recordName ) ) ? $control->recordName : $value; ?></p></div><?php
    wordpresscrm_field_end();
}
