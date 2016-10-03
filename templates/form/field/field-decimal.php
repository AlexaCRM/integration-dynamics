<?php
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?><input type="text" id="<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $inputname ); ?>" <?php
?>value="<?php echo esc_attr( $value ); ?>" class="form-control"<?php
echo( ( $disabled ) ? ' disabled="disabled" ' : ' ' );
echo( ( $readonly ) ? ' readonly="readonly"' : '' ); ?>><?php
