<?php
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?>
<input type="text" id='<?php echo $name; ?>' name='<?php echo $inputname; ?>' value="<?php echo $value; ?>"
       class="form-control" <?php echo( ( $disabled ) ? "disabled='disabled'" : "" ); ?> <?php echo( ( $readonly ) ? "readonly='readonly'" : "" ); ?> />
