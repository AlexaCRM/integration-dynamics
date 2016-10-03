<?php
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?><input type="hidden" id="<?php echo esc_attr( $inputname ); ?>" name="<?php echo esc_attr( $inputname ); ?>"<?php
echo( ( $disabled ) ? ' disabled="disabled" ' : ' ' );
echo( ( $readonly ) ? ' readonly="readonly" ' : ' ' );
?>value="<?php echo esc_attr( $value ); ?>" class="form-control"><?php
