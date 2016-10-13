<?php
use AlexaCRM\WordpressCRM\Shortcode\Form\Control;

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?><div class="form-group row<?php if ( $args instanceof Control && $args->error ) { echo ' has-error has-danger has-feedback'; } ?>"><?php
