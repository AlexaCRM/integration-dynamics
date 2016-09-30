<?php
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?>
<?php do_action( 'wordpresscrm_before_form_start' ); ?>

<?php wordpresscrm_form_start( array( "crm-entity-" . $entity->logicalname ), $form->uid ); ?>

<?php wordpresscrm_form_errors( apply_filters( "wordpresscrm_form_template_errors", $form->errors ) ); ?>

<?php wordpresscrm_form_notices( apply_filters( "wordpresscrm_form_template_notices", $form->notices ) ); ?>

<?php do_action( 'wordpresscrm_before_form_fields' ); ?>

<?php if ( $form->showform ) : ?>

    <?php foreach ( $form->controls as $column ) : ?>

        <?php if ( !empty( $column["controls"] ) ) : ?>

            <?php foreach ( $column["controls"] as $control ) : ?>

                <?php if ( $mode == "readonly" ) : ?>

                    <?php wordpresscrm_readonly_field( $control ); ?>

                <?php else : ?>

                    <?php wordpresscrm_field( $control ); ?>

                <?php endif; ?>

            <?php endforeach; ?>

        <?php endif; ?>

    <?php endforeach; ?>

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

<?php endif; ?>

<?php do_action( 'wordpresscrm_after_form_fields' ); ?>

<?php wordpresscrm_form_end(); ?>

<?php wordpresscrm_form_validation( $form->uid, $form ); ?>

<?php do_action( 'wordpresscrm_after_form_end' ); ?>
