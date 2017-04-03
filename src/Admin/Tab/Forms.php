<?php
namespace AlexaCRM\WordpressCRM\Admin\Tab;

use AlexaCRM\WordpressCRM\Admin;
use AlexaCRM\WordpressCRM\Admin\Tab;

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Forms extends Tab {

    public $pageId = 'forms';

    public $displayName = 'Forms';

    protected $settingsField = 'forms';

    public static $default_settings = array(
        'enable_captcha' => false,
        'sitekey'        => '',
        'secret'         => '',
    );

    public function getDisplayName() {
        return __( 'Forms', 'integration-dynamics' );
    }

    public function initializeTab( $tabHookName ) {
    }

    public function render() {
        ?>
        <div class="wrap">
            <?php Admin::renderSettingsTabs(); ?>
            <form method="post" action="<?php echo esc_attr( admin_url( 'options.php' ) ); ?>">
                <p><?php _e( 'This page allows you to configure how your CRM forms look and feel.', 'integration-dynamics' ); ?> <a href="http://docs.alexacrm.com/wpcrm/configuration/forms/" target="_blank"><?php _e( 'Documentation &raquo;', 'integration-dynamics' ); ?></a></p>
                <hr>

                <?php settings_fields( $this->settingsField ); ?>
                <?php do_settings_sections( $this->settingsField ); ?>

                <h3><?php _e( 'reCAPTCHA settings', 'integration-dynamics' ); ?></h3>
                <p><?php _e( 'Plugin supports reCAPTCHA that you can add to the forms to protect your WordPress site from spam.', 'integration-dynamics' ); ?></p>
                <table class="form-table">
                    <tbody>
                    <tr>
                        <th scope="row"></th>
                        <td>
                            <label><input
                                    type="checkbox" name="<?php echo $this->get_field_name( 'enable_captcha' ); ?>"
                                    value="1"<?php if ( isset( $this->options['enable_captcha'] ) ) {
                                    checked( 1 == $this->get_field_value( 'enable_captcha' ) );
                                } ?> /> <?php _e( 'Enable reCAPTCHA', 'integration-dynamics' ); ?></label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wpcrmFormRecaptchaSiteKey"><?php _e( 'Site key <span class="description">(required)</span>', 'integration-dynamics' ); ?></label></th>
                        <td>
                            <input id="wpcrmFormRecaptchaSiteKey" type="text" class="regular-text code"
                                   name="<?php echo $this->get_field_name( 'sitekey' ); ?>"
                                   value="<?php echo esc_attr( $this->get_field_value( 'sitekey' ) ); ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wpcrmFormRecaptchaSecret"><?php _e( 'Secret key <span class="description">(required)</span>', 'integration-dynamics' ); ?></label></th>
                        <td>
                            <input id="wpcrmFormRecaptchaSecret" type="text" class="regular-text code"
                                   name="<?php echo $this->get_field_name( 'secret' ); ?>"
                                   value="<?php echo esc_attr( $this->get_field_value( 'secret' ) ); ?>"/>
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
