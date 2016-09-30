<?php
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?>
<?php if ( $notices ) : ?>

    <?php do_action( 'wordpresscrm_before_form_notices' ); ?>

    <?php foreach ( $notices as $notice ) : ?>
        <div class="alert alert-success" role="alert"><?php echo $notice; ?></div>
    <?php endforeach; ?>

    <?php do_action( 'wordpresscrm_after_form_notices' ); ?>

<?php endif; ?>
