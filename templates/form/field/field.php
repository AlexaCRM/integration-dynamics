<?php
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?>

<?php if ( $control->visible ) : ?>
    <?php wordpresscrm_field_start(); ?>

    <?php switch ( $control->labelPosition ) :

        case "Top":
            ?>
            <?php wordpresscrm_field_label( $control ); ?>

            <?php wordpresscrm_field_type( $control ); ?>

            <?php wordpresscrm_field_error( $control ); ?>
            <?php
            break;
        case "Left":
        default:
            ?>
            <?php $control->labelClass .= " col-sm-6"; ?>

            <?php wordpresscrm_field_label( $control ); ?>
            <div class="col-sm-6">
                <?php wordpresscrm_field_type( $control ); ?>
            </div>
            <?php wordpresscrm_field_error( $control ); ?>
            <?php
            break;
    endswitch; ?>

    <?php wordpresscrm_field_end(); ?>

<?php else : ?>

    <?php wordpresscrm_field_type_hidden( $control ); ?>

<?php endif; ?>
