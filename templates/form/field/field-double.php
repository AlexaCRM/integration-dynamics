<?php
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?><input type="text" id="<?php echo esc_attr( $inputname ); ?>" name="<?php echo esc_attr( $inputname ); ?>" <?php
?>value="<?php echo $value; ?>" class="form-control"<?php
echo( ( $disabled ) ? ' disabled="disabled" ' : ' ' );
echo( ( $readonly ) ? ' readonly="readonly"' : '' ); ?>><?php
