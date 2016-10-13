<?php
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( $control->visible ) {
    $control->labelClass .= " col-sm-4";

    wordpresscrm_field_start( $control );
    wordpresscrm_field_label( $control );
    ?><div class="col-sm-8"><p class="form-control-static"><?php echo ( !empty( $control->recordName ) ) ? $control->recordName : $control->value; ?></p></div><?php
    wordpresscrm_field_end();
}
