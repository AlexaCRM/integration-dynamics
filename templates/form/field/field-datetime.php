<?php
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?>
<?php if ( $format == "datetime" ) {
    $dateTimeFormat = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
    ?>
    <input
        class='crm-text crm-datetime crm-datetimepicker form-control' <?php echo( ( $disabled ) ? "disabled='disabled'" : "" ); ?> <?php echo( ( $readonly ) ? "readonly='readonly'" : "" ); ?>
        type='text' id='<?php echo $inputname; ?>' name='<?php echo $inputname; ?>'
        value='<?php echo ( $value ) ? date( $dateTimeFormat, $value ) : ""; ?>'/>
<?php }

if ( $format == "dateonly" ) {
    $dateFormat = get_option( 'date_format' );
    ?>
    <input class='crm-text crm-datetime crm-datepicker form-control' type='text' id='<?php echo $inputname; ?>'
           name='<?php echo $inputname; ?>' <?php echo( ( $disabled ) ? "disabled='disabled'" : "" ); ?> <?php echo( ( $readonly ) ? "readonly='readonly'" : "" ); ?>
           value='<?php echo ( $value ) ? date( $dateFormat, $value ) : ""; ?>'>
<?php }
