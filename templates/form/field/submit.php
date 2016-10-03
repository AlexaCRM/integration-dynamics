<?php
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?><div class="col-xs-12"><input type='hidden' name='entity_form_entity' value='<?php echo $entity->logicalname; ?>'><?php
?><input type='hidden' name='entity_form_name' value='<?php echo $form->formName; ?>'><?php
?><input type='submit' value='<?php echo esc_attr( apply_filters( "wordpresscrm_form_submit_button", __( 'Submit', 'integration-dynamics' ) ) ); ?>' class='btn btn-default' name='entity_form_submit'></div><?php
