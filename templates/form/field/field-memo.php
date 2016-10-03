<?php
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?><textarea class="crm-textarea crm-memo form-control"<?php
echo( ( $disabled ) ? ' disabled="disabled" ' : ' ' );
echo( ( $readonly ) ? ' readonly="readonly" ' : ' ' );
?>id="<?php echo esc_attr( $inputname ); ?>" name="<?php echo esc_attr( $inputname ); ?>"><?php echo $value; ?></textarea><?php
