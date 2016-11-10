<?php
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

do_action( 'wordpresscrm_before_form_start' );

wordpresscrm_form_start( array( "crm-entity-" . $entity->logicalname ), $form->uid );

wordpresscrm_form_errors( apply_filters( "wordpresscrm_form_template_errors", $form->errors ) );
wordpresscrm_form_notices( apply_filters( "wordpresscrm_form_template_notices", $form->notices ) );

do_action( 'wordpresscrm_before_form_fields' );

if ( $form->showform ) {
    foreach ( $form->controls as $column ) {
        if ( !empty( $column["controls"] ) ) {
            foreach ( $column["controls"] as $control ) {
                if ( $mode == "readonly" ) {
                    wordpresscrm_readonly_field( $control );
                } else {
                    wordpresscrm_field( $control );
                }
            }
        }
    }

    if ( $mode != "readonly" ) {
        if ( $form->captcha->enable_captcha ) {
            wordpresscrm_field_start();
            ?><div class="g-recaptcha" data-sitekey="<?php echo $form->captcha->sitekey ?>"></div><?php
            wordpresscrm_field_end();
        }

        wordpresscrm_field_start();
        wordpresscrm_form_submit( array( "form" => $form, 'entity' => $entity ) );
        wordpresscrm_field_end();
    }
}

do_action( 'wordpresscrm_after_form_fields' );

wordpresscrm_form_end();

wordpresscrm_form_validation( $form->uid, $form );

do_action( 'wordpresscrm_after_form_end', $form );
