<?php

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

?><input type="hidden" name="wordpresscrm_databinding_nonce" id="wordpresscrm_databinding_nonce"
       value="<?php echo wp_create_nonce( 'wordpresscrm_databinding' ); ?>"/>

<div id="wordpresscrm_databinding_container">
    <p><strong><?php _e( 'Entity', 'integration-dynamics' ); ?></strong></p>
    <select id="wordpresscrmDatabindingEntity" name="wordpresscrm_databinding_entity">
        <option value="0"><?php _e( '— Select —', 'integration-dynamics' ); ?></option>
        <?php foreach ( $entities as $entity ) : ?>

            <?php $selected = ( $post_entity && $entity["LogicalName"] == $post_entity ) ? "selected" : ""; ?>
            <option
                value="<?php echo $entity["LogicalName"]; ?>" <?php echo $selected ?>><?php echo $entity["Label"]; ?> (<?php echo $entity['LogicalName']; ?>)</option>

        <?php endforeach; ?>
    </select>

    <p><strong><?php _e( 'Parameter name', 'integration-dynamics' ); ?></strong></p>
    <select id="wordpressDatabindingParametername" name="wordpresscrm_databinding_parametername">
        <option value="id" <?php selected( ( $post_parametername == 'id' || !$post_parametername ) ); ?>>ID</option>
        <?php
        if ( $post_entity ) {
            $entityKeys = ASDK()->entity( $post_entity )->metadata()->keys;
            foreach ( $entityKeys as $entityKey ) {
                ?>
                <option
                value="<?php echo esc_attr( $entityKey->logicalName ); ?>" <?php selected( $post_parametername, $entityKey->logicalName ); ?>>
                <?php echo esc_html( $entityKey->displayName . ' [' . implode( ',', $entityKey->keyAttributes ) . ']' ) ?>
                </option><?php
            }
        }

        ?>
    </select> <img id="wordpressDatabindingParameternameProgress"
                   src="<?php echo ACRM()->getPluginURL(); ?>/resources/front/images/progress.gif" alt="" width="16"
                   height="16" style="vertical-align: middle;display:none;">

    <p><strong><?php _e( 'Query string parameter name', 'integration-dynamics' ); ?></strong></p>
    <input type="text" value="<?php echo ( isset( $post_querystring ) ) ? $post_querystring : "id"; ?>" size="10"
           name="wordpresscrm_databinding_querystring"/>

    <p><strong><?php _e( 'Empty parameter behavior', 'integration-dynamics' ); ?></strong></p>
    <select name="wordpresscrm_databinding_empty_behavior">
        <option
            value="" <?php echo ( $post_empty_behavior == "" ) ? "selected" : ""; ?>><?php _e( 'Ignore', 'integration-dynamics' ); ?></option>
        <option
            value="404" <?php echo ( $post_empty_behavior == "404" ) ? "selected" : ""; ?>><?php _e( 'Page Not Found', 'integration-dynamics' ); ?></option>
    </select>

    <p><label for="wpcrmDataBindingIsDefaultView"><input id="wpcrmDataBindingIsDefaultView" type="checkbox"
                                                         name="wordpresscrm_databinding_isdefaultview"
                                                         value="true" <?php echo $post_isdefaultview; ?> /> <?php _e( 'Set default for views', 'integration-dynamics' ); ?>
        </label></p>

</div>
<script>
    (function ($) {
        var $entitySelector = $('#wordpresscrmDatabindingEntity'),
            $parameterNameSelector = $('#wordpressDatabindingParametername'),
            $progressIcon = $('#wordpressDatabindingParameternameProgress');

        $entitySelector.change(function () {
            var newEntityLogicalName = $entitySelector.val(),
                nonce = $('#wordpresscrm_databinding_nonce').val();

            console.log(newEntityLogicalName);
            if (newEntityLogicalName == '0') {
                return;
            }

            $progressIcon.show();

            wp.ajax.send('retrieve_entity_keys', {
                data: {
                    _wpnonce: nonce,
                    entityLogicalName: newEntityLogicalName
                }
            })
                .done(function (result) {
                    $parameterNameSelector.empty();
                    $parameterNameSelector.append('<option value="id">ID</option>');
                    _.each(result, function (entityKey) {
                        var entityKeyLabel = entityKey.displayName + ' [' + entityKey.keyAttributes.join(',') + ']';
                        $parameterNameSelector.append('<option value="' + entityKey.logicalName + '">' + entityKeyLabel + '</option>');
                    });
                    console.log(result);
                })
                .always(function () {
                    $progressIcon.hide();
                });
        });
    })(jQuery);
</script>
