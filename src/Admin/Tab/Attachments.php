<?php
namespace AlexaCRM\WordpressCRM\Admin\Tab;

use AlexaCRM\WordpressCRM\Admin;
use AlexaCRM\WordpressCRM\Admin\Tab;

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Attachments extends Tab {

    public $pageId = 'attachments';

    public $displayName = 'Images';

    protected $settingsField = 'attachments';

    public static $default_settings = array(
        'annotations_use_view'  => false,
        'annotations_view_name' => "",
        'custom_entity'         => "",
        'fields'                => array(
            'documentbody'  => "",
            'mimetype'      => "",
            "objectid"      => "",
            "objecttype"    => "",
            "default_image" => "",
        ),
    );

    public function getDisplayName() {
        return __( 'Images', 'integration-dynamics' );
    }

    public function initializeTab( $tabHookName ) {
    }

    public function getViews() {
        $annotation = ASDK()->entity( "annotation" );

        $fetch = '<fetch version="1.0" output-format="xml-platform" mapping="logical" distinct="false">
						<entity name="userquery">
							<attribute name="name" />
							<attribute name="returnedtypecode" />
							 <filter type="and">
								<condition attribute="returnedtypecode" operator="eq" value="' . $annotation->metadata()->objectTypeCode . '" />
							  </filter>
						</entity>
					  </fetch>';

        $userqueries = ASDK()->retrieveMultiple( $fetch );

        $fetch = '<fetch version="1.0" output-format="xml-platform" mapping="logical" distinct="false">
						<entity name="savedquery">
							<attribute name="name" />
							<attribute name="returnedtypecode" />
							 <filter type="and">
								<condition attribute="returnedtypecode" operator="eq" value="' . $annotation->metadata()->objectTypeCode . '" />
							  </filter>
						</entity>
					  </fetch>';

        $savedqueries = ASDK()->retrieveMultiple( $fetch );

        return array_merge( $userqueries->Entities, $savedqueries->Entities );
    }

    public function render() {
        $shouldUseView = isset( $this->options['annotations_use_view'] ) && $this->get_field_value( 'annotations_use_view' ) == 1;
        ?>
        <div class="wrap">
            <?php Admin::renderSettingsTabs(); ?>

            <p><?php _e( 'This page allows you to configure how images are fetched from CRM and displayed.', 'integration-dynamics' ); ?> <a href="http://docs.alexacrm.com/wpcrm/configuration/images/" target="_blank"><?php _e( 'Documentation &raquo;', 'integration-dynamics' ); ?></a></p>
            <hr>

            <form method="post" action="options.php">
                <?php settings_fields( $this->settingsField ); ?>
                <?php do_settings_sections( $this->settingsField ); ?>

                <h3><?php _e( 'Annotations settings', 'integration-dynamics' ); ?></h3>
                <p></p>
                <table class="form-table">
                    <tbody>
                    <tr>
                        <td>
                            <label id="wpcrmEnableAnnotationSelector"><input
                                    type="checkbox"
                                    name="<?php echo $this->get_field_name( 'annotations_use_view' ); ?>"
                                    value="1"<?php if ( $shouldUseView ) {
                                    checked( 1 == $this->get_field_value( 'annotations_use_view' ) );
                                } ?> /> <?php _e( 'Use a view to get images', 'integration-dynamics' ); ?></label>
                        </td>
                    </tr>
                    <tr id="wpcrmAnnotationSelector" <?php echo $shouldUseView ?: 'style="display:none"'; ?>>
                        <td>
                            <select name="<?php echo $this->get_field_name( 'annotations_view_name' ); ?>">
                                <option value=""><?php _e( '— Select —', 'integration-dynamics' ); ?></option>
                                <?php foreach ( $this->getViews() as $entity ) : ?>

                                    <?php $selected = ( $this->get_field_value( "annotations_view_name" ) && $entity->displayname == $this->get_field_value( "annotations_view_name" ) ) ? "selected" : ""; ?>
                                    <option
                                        value="<?php echo $entity->displayname; ?>" <?php echo $selected ?>><?php echo $entity->displayname . " (" . $entity->logicalname . ")"; ?></option>

                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <script>
            (function ($) {
                var $enableAnnotations = $('#wpcrmEnableAnnotationSelector input'),
                    $selector = $('#wpcrmAnnotationSelector');
                $enableAnnotations.change(function (e) {
                    if ($(this).prop('checked')) {
                        $selector.show();
                    } else {
                        $selector.hide().find('select').val('');
                    }
                });
            })(jQuery);
        </script>
        <?php
    }

}
