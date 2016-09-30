<?php
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?>
<textarea class="crm-textarea crm-memo form-control" <?php echo( ( $disabled ) ? "disabled='disabled'" : "" ); ?>
          id='<?php echo $inputname; ?>' <?php echo( ( $readonly ) ? "readonly='readonly'" : "" ); ?>
          name='<?php echo $inputname; ?>'><?php echo $value; ?></textarea>
