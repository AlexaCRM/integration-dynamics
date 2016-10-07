<?php
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( $showlabel && $name != 'fullname' ) {
    ?><label class="control-label col-form-label <?php echo trim( esc_attr( $labelClass ) ); ?>" for="<?php echo esc_attr( $inputname ); ?>"><?php
    echo $label;

    if ( $required && !$readonly ) {
        echo ' *';
    } ?></label><?php
}
