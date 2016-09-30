<?php
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?>
<input type="text"
       id="<?php echo $inputname; ?>" <?php echo( ( $disabled ) ? "disabled='disabled'" : "" ); ?> <?php echo( ( $readonly ) ? "readonly='readonly'" : "" ); ?>
       id='<?php echo $name; ?>' name='<?php echo $inputname; ?>' class="form-control" value="<?php echo $value; ?>"/>
