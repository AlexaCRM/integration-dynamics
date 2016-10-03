<?php
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?>
<input type='file' id='<?php echo $inputname; ?>' name='<?php echo $inputname; ?>'
       class="form-control" <?php echo( ( $disabled ) ? "disabled='disabled'" : "" ); ?> <?php echo( ( $readonly ) ? "readonly='readonly'" : "" ); ?>><?php
