<?php
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( $control->visible ) {
    wordpresscrm_field_start();
    wordpresscrm_field_label( $control );
    echo ( !empty( $control->recordName ) ) ? $control->recordName : $control->value;
    wordpresscrm_field_end();
}
