<?php
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?>
<div class="container-fluid"><?php
    ?><form id="<?php echo esc_attr( $id ); ?>" method="POST" name="entity-form" enctype="multipart/form-data" <?php
          ?>class="form-horizontal entity-form <?php echo esc_attr( implode( ' ', $classes ) ); ?>" autocomplete="off" role="form"><?php
        ?><fieldset><?php
