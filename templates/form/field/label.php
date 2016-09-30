<?php
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?>
<?php if ( $showlabel && $name != 'fullname' ) : ?>
    <label class="control-label <?php echo $labelClass; ?>"
           for="<?php echo $inputname; ?>"><?php echo $label; ?><?php if ( $required ) {
            echo " *";
        } ?></label>
<?php endif; ?>
