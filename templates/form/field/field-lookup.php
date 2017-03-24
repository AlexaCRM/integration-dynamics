<?php
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( $disabled || $readonly ) { ?>
    <input type="text" class="crm-lookup crm-lookup-textfield form-control" <?php
    ?>readonly="readonly" disabled="disabled" value="<?php echo esc_attr( $recordName ); ?>"><?php
} else {
    ?><div class="crm-lookup-wrapper">
        <div class="crm-lookup-field-wrapper form-control">
            <input type="text" class="crm-lookup crm-lookup-textfield form-control" readonly="readonly"
                   value="<?php echo $recordName; ?>"/>
            <span class="crm-lookup-textfield-button"></span>
            <span class="crm-lookup-textfield-delete-value" style="<?php if ( !$value ) {
                echo "display:none;";
            } ?>"></span>
            <input type="hidden" class="crm-lookup-hiddenfield" id='<?php echo $name; ?>'
                   name='<?php echo $inputname; ?>' value="<?php echo $value; ?>"/>
            <input type="hidden" class="crm-lookup-lookup-types"
                   value="<?php echo urlencode( json_encode( $lookupTypes ) ); ?>"/>
        </div>
        <div class="crm-lookup-popup-overlay">
            <div class="crm-lookup-popup-overlay-bg"></div>
            <div class="crm-lookup-popup">
                <div class="crm-lookup-popup-header">
                    <a title="<?php _e( 'Cancel', 'integration-dynamics' ); ?>" class="crm-popup-cancel" href="#" tabindex="2">
                        <img style="height:16px;width:16px;"
                             src="<?php echo ACRM()->getPluginURL(); ?>/resources/front/images/CloseDialog.png" alt="x"/>
                    </a>
                    <div class="crm-header-title"><?php _e( 'Look up record', 'integration-dynamics' ); ?></div>
                </div>
                <div class="crm-lookup-search-area">
                    <table>
                        <tr>
                            <td class="label-td"><label><?php _e( 'Look for', 'integration-dynamics' ); ?></label></td>
                            <td>
                                <select class="crm-lookup-lookuptype" <?php if ( count( $lookupTypes ) <= 1 ) {
                                    echo "disabled";
                                } ?>>
                                    <?php foreach ( $lookupTypes as $key => $value ) {
                                        $entityMetadata = ACRM()->getMetadata()->getEntityDefinition( $value );
                                        ?>
                                        <option value="<?php echo $key; ?>"><?php echo $entityMetadata->entityDisplayName; ?></option>
                                    <?php } ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td class="label-td"><label><?php _e( 'Search', 'integration-dynamics' ); ?></label></td>
                            <td>
                                <input type="text" class="crm-lookup-searchfield" placeholder="<?php _e( 'Search for records', 'integration-dynamics' ); ?>"/>
                                <span class="crm-lookup-searchfield-button"></span>
                                <span class="crm-lookup-searchfield-delete-search"></span>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="crm-lookup-popup-body">
                    <div class="crm-lookup-body-grid"></div>
                    <div class="crm-lookup-popup-body-loader">
                        <table>
                            <tr>
                                <td align="center" style="vertical-align: middle">
                                    <img src="<?php echo ACRM()->getPluginURL(); ?>/resources/front/images/progress.gif"
                                         alt=""
                                         id="DialogLoadingDivImg">
                                    <br><?php _e( 'Loading...', 'integration-dynamics' ); ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="crm-lookup-popup-body-foot">
                        <table>
                            <tr>
                                <td></td>
                                <td>
                                    <button disabled class="crm-lookup-popup-first-page"></button>
                                    <button disabled class="crm-lookup-popup-prev-page" data-pagingcookie=""></button>
                                    <?php _e( 'Page <span class="crm-lookup-popup-page-counter">1</span>', 'integration-dynamics' ); ?>
                                    <button disabled class="crm-lookup-popup-next-page" data-pagingcookie=""></button>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="crm-lookup-popup-foot">
                    <div class="crm-lookup-popup-foot-left"></div>
                    <div class="crm-lookup-popup-foot-right">
                        <button class="crm-popup-add-button"><?php _e( 'Add', 'integration-dynamics' ); ?></button>
                        <button class="crm-popup-cancel-button"><?php _e( 'Cancel', 'integration-dynamics' ); ?></button>
                        <button <?php if ( !$value ) {
                            echo "disabled";
                        } ?> class="crm-popup-remove-value-button"><?php _e( 'Remove Value', 'integration-dynamics' ); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div><?php
}

wp_enqueue_script( 'wordpresscrm-lookup-dialog' );
