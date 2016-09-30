<?php
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?>
<select class='selectmenu crm-select form-control' id='<?php echo $inputname; ?>'
        name='<?php echo $inputname; ?>' <?php echo( ( $disabled ) ? "disabled='disabled'" : "" ); ?> <?php echo( ( $readonly ) ? "readonly='readonly'" : "" ); ?>>
    <option value=''></option>
    <?php foreach ( $options as $key => $val ) : ?>
        <option value='<?php echo $key; ?>' <?php if ( $value == $key ) {
            echo "selected='selected'";
        } ?>><?php echo $val; ?></option>
    <?php endforeach; ?>
</select>

