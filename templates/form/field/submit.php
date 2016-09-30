<?php
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?>
<input type='hidden' name='entity_form_entity' value='<?php echo $entity->logicalname; ?>'/>
<input type='hidden' name='entity_form_name' value='<?php echo $form->formName; ?>'/>
<input type='submit' value='<?php echo apply_filters( "wordpresscrm_form_submit_button", __( 'Submit', 'integration-dynamics' ) ); ?>'
       class='btn btn-default' name='entity_form_submit'/>
