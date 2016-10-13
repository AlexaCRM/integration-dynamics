<?php
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
if ( $error ) { ?><label id="<?php echo esc_attr( $inputname . '-error' ); ?>" for="<?php echo esc_attr( $inputname ); ?>" class="help-block form-control-feedback"><?php echo $error; ?></label><?php }
