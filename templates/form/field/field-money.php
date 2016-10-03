<?php
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?><input type="text" id="<?php echo esc_attr( $inputname ); ?>"<?php
echo( ( $disabled ) ? ' disabled="disabled" ' : ' ' );
echo( ( $readonly ) ? ' readonly="readonly" ' : ' ' );
?>id="<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $inputname ); ?>" <?php
?>class="form-control" value="<?php echo esc_attr( $value ); ?>"><?php
