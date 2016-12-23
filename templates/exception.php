<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
?><div class="alert alert-danger" role="alert">
    <p><?php echo $error; ?></p>
    <?php if ( isset( $exception ) ) { ?>
        <p></p>
        <p style="color: black;"><?php echo $exception->getMessage(); ?></p>
    <?php } ?>
</div><?php
