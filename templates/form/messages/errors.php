<?php
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?>
<?php if ( $errors ) : ?>

    <?php do_action( 'wordpresscrm_before_form_errors' ); ?>
    <div class="form-errors">
    <?php foreach ( $errors as $error ) : ?>
        <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
    <?php endforeach; ?>
    </div>
    <?php do_action( 'wordpresscrm_after_form_errors' ); ?>

<?php endif; ?>
