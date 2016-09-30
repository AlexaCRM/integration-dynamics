<?php
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?>
<?php do_action( 'wordpresscrm_before_form_start' ); ?>

<?php wordpresscrm_form_start( array( "crm-entity-" . $entity->logicalname ), $form->uid ); ?>

<div id="output"></div>

<?php wordpresscrm_form_errors( apply_filters( "wordpresscrm_form_template_errors", $form->errors ) ); ?>

<?php wordpresscrm_form_notices( apply_filters( "wordpresscrm_form_template_notices", $form->notices ) ); ?>

<?php do_action( 'wordpresscrm_before_form_fields' ); ?>

<?php if ( $form->showform ) : ?>

    <div class="row">

        <?php foreach ( $controls as $column ) : ?>

            <?php if ( !empty( $column["controls"] ) ) : ?>

                <div class="col-sm-4">

                    <?php foreach ( $column["controls"] as $control ) : ?>

                        <?php if ( $mode == "readonly" ) : ?>

                            <?php wordpresscrm_readonly_field( $control ); ?>

                        <?php else : ?>

                            <?php wordpresscrm_field( $control ); ?>

                        <?php endif; ?>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        <?php endforeach; ?>

    </div>
    <div class="row">

        <?php if ( $mode != "readonly" ) : ?>

            <?php if ( $form->captcha->enable_captcha ) : ?>

                <?php wordpresscrm_field_start(); ?>
                <div class="g-recaptcha" data-sitekey="<?php echo $form->captcha->sitekey ?>"></div>
                <?php wordpresscrm_field_end(); ?>

            <?php endif; ?>

            <?php wordpresscrm_field_start(); ?>

            <?php wordpresscrm_form_submit( array( "form" => $form, 'entity' => $entity ) ); ?>

            <?php wordpresscrm_field_end(); ?>

        <?php endif; ?>

    </div>

<?php endif; ?>

<?php do_action( 'wordpresscrm_after_form_fields' ); ?>

<?php wordpresscrm_form_end(); ?>

<?php wordpresscrm_form_validation( $form->uid, $form ); ?>

<?php do_action( 'wordpresscrm_after_form_end' ); ?>
