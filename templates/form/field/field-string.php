<?php
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?>
<input type="text" id="<?php echo $inputname; ?>"
       name="<?php echo $inputname; ?>" <?php echo( ( $disabled ) ? "disabled='disabled'" : "" ); ?>  <?php echo( ( $readonly ) ? "readonly='readonly'" : "" ); ?>
       class="form-control" value="<?php echo $value; ?>"/>
