<?php
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?>
<?php if ( $format == "datetime" ) : ?>
    <input
        class='crm-text crm-datetime crm-datetimepicker form-control' <?php echo( ( $disabled ) ? "disabled='disabled'" : "" ); ?> <?php echo( ( $readonly ) ? "readonly='readonly'" : "" ); ?>
        type='text' id='<?php echo $inputname; ?>' name='<?php echo $inputname; ?>'
        value='<?php echo ( $value ) ? date( "m/d/Y H:s", $value ) : ""; ?>'/>
<?php endif; ?>

<?php if ( $format == "dateonly" ) : ?>
    <input class='crm-text crm-datetime crm-datepicker form-control' type='text' id='<?php echo $inputname; ?>'
           name='<?php echo $inputname; ?>' <?php echo( ( $disabled ) ? "disabled='disabled'" : "" ); ?> <?php echo( ( $readonly ) ? "readonly='readonly'" : "" ); ?>
           value='<?php echo ( $value ) ? date( "m/d/Y", $value ) : ""; ?>'/>
<?php endif; ?>
