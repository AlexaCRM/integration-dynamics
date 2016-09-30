<?php
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?>
<?php if ( $control->visible ) : ?>

    <?php wordpresscrm_field_start(); ?>
    <?php wordpresscrm_field_label( $control ); ?>
    <?php echo ( !empty( $control->recordName ) ) ? $control->recordName : $control->value; ?>
    <?php wordpresscrm_field_end(); ?>

<?php endif; ?>
