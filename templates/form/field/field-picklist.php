<?php
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( !$value ) {
    $value        = new stdClass();
    $value->value = -1;
}

if ( is_string( $value ) ) {
    $v = $value;

    $value        = new stdClass();
    $value->value = $v;
}
?><select class="selectmenu crm-select form-control" id="<?php echo esc_attr( $inputname ); ?>"<?php
?>name="<?php echo esc_attr( $inputname ); ?>"<?php
echo( ( $disabled ) ? ' disabled="disabled" ' : ' ' );
echo( ( $readonly ) ? ' readonly="readonly" ' : ' ' ); ?>><?php
    ?><option value=""></option><?php
    foreach ( $options as $key => $val ) {
        ?><option value="<?php echo esc_attr( $key ); ?>"<?php if ( $value->value == $key ) {
            echo ' selected="selected"';
        } ?>><?php echo $val; ?></option><?php
    } ?></select><?php
