<?php
namespace AlexaCRM\WordpressCRM\Admin\Tab;

use AlexaCRM\WordpressCRM\Admin;
use AlexaCRM\WordpressCRM\Admin\Tab;

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Views extends Tab {

    public $pageId = 'views';

    public $displayName = 'Views';

    protected $settingsField = 'views';

    public static $default_settings = [
        'use_images_for_boolean' => false,
    ];

    public function getDisplayName() {
        return __( 'Views', 'integration-dynamics' );
    }

    public function initializeTab( $tabHookName ) {
    }

    public function render() {
        ?>
        <div class="wrap">
            <?php Admin::renderSettingsTabs(); ?>
            <form method="post" action="options.php">
                <?php settings_fields( $this->settingsField ); ?>
                <?php do_settings_sections( $this->settingsField ); ?>

                <h3><?php _e( "Views settings", 'integration-dynamics' ); ?></h3>
                <table class="form-table">
                    <tbody>
                    <tr>
                        <th scope="row"><label><?php _e( "Images for boolean fields", 'integration-dynamics' ); ?></label></th>
                        <td>
                            <label><input
                                    type="checkbox"
                                    name="<?php echo $this->get_field_name( 'use_images_for_boolean' ); ?>"
                                    value="1"<?php if ( isset( $this->options['use_images_for_boolean'] ) ) {
                                    checked( 1 == $this->get_field_value( 'use_images_for_boolean' ) );
                                } ?> /> <?php _e( "Use images for boolean field values in views", 'integration-dynamics' ); ?>
                            </label>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
