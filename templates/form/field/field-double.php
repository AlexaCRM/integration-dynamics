<?php
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?>
<input type="text" id='<?php echo $inputname; ?>' name='<?php echo $inputname; ?>' value="<?php echo $value; ?>"
       class="form-control" <?php echo( ( $disabled ) ? "disabled='disabled'" : "" ); ?> <?php echo( ( $readonly ) ? "readonly='readonly'" : "" ); ?>/>
