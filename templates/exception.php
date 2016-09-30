<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="alert alert-danger" role="alert">
    <p><?php echo $error; ?></p>

    <?php if ( isset( $exception ) ) : ?>
        <p>&nbsp;</p>
        <p style="color: black;">
            <?php echo $exception; ?>
        </p>
    <?php endif; ?>

</div>
