<?php

namespace AlexaCRM\WordpressCRM\Admin\Tab;

use AlexaCRM\WordpressCRM\Admin;
use AlexaCRM\WordpressCRM\Admin\Tab;
use Monolog\Logger;

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class About extends Tab {

    public $pageId = 'about';

    public $displayName = 'About';

    protected $settingsField = 'options';

    public static $default_settings = array(
        "OrgName"        => '',
        "Email"          => '',
        "OrgId"          => '',
        "ProductName"    => '',
        "UserNumberCode" => '',
        "CrmVersion"     => '',
        "Version"        => '',
        "IsCreated"      => false
    );

    public function getDisplayName() {
        return __( 'About', 'integration-dynamics' );
    }

    public function render() {
        ?>
        <div class="wrap">
            <?php
            Admin::renderSettingsTabs();
            $this->renderAboutInformation();
            do_action( 'wordpresscrm_after_settings_about', $this );
            ?>
        </div>
        <?php
    }

    public function renderAboutInformation() {
        ?>
        <h2><?php _e( "About", 'integration-dynamics' ) ?></h2>
        <p>
            Dynamics 365 Integration<br>
            <?php printf( __( 'Version: %s', 'integration-dynamics' ), WORDPRESSCRM_VERSION ); ?><br>
            <?php printf( __( 'Copyright &copy; %1$d %2$s', 'integration-dynamics' ), date( 'Y' ), 'AlexaCRM' ); ?><br>
            <?php printf( __( '<a href="%s" target="_blank">Plugin website</a>', 'integration-dynamics' ), 'https://wordpress.org/plugins/integration-dynamics/' ) ?><br>
            <?php printf( __( '<a href="%s" target="_blank">Documentation</a>', 'integration-dynamics' ), 'http://docs.alexacrm.com/wpcrm/' ); ?><br>
            <a href="mailto:support@alexacrm.com">support@alexacrm.com</a>
        </p>

        <?php if ( !defined( 'WORDPRESSCRM_PREMIUM_PLUGIN' ) ) { ?>
        <p><?php printf( __( '<strong>Want to take more from your Dynamics?</strong> Check out the <a href="%s">premium plugin</a>.', 'integration-dynamics' ), 'https://alexacrm.com/dynamics-crm-integration-premium/' ); ?></p>
        <?php } ?>

        <h3><?php _e( 'Error reporting', 'integration-dynamics' ); ?></h3>
        <p><?php
            $message = __( 'If you experience problems while using Dynamics 365 Integration plugin and eventually report them, attach log files which are stored in <code>%s</code>. <a href="%s">Download log files</a>', 'integration-dynamics' );
            if ( !class_exists( 'ZipArchive' ) ) {
                $message = __( 'If you experience problems while using Dynamics 365 Integration plugin and eventually report them, attach log files which are stored in <code>%s</code>. <a href="%s">Download the latest log file</a>', 'integration-dynamics' );
            }

            printf( $message, WORDPRESSCRM_STORAGE, admin_url( 'admin-ajax.php?action=wpcrm_log') ); ?></p>
        <p>
            <label><?php _e( 'Change log verbosity:', 'integration-dynamics' ); ?>
                <select name="verbosity" id="wpcrmVerbositySelector" style="vertical-align: baseline;">
                <?php
                $logLevels = [
                    Logger::DEBUG => 'Debug',
                    Logger::INFO => 'Info',
                    Logger::NOTICE => 'Notice',
                    Logger::WARNING => 'Warning',
                    Logger::ERROR => 'Error',
                    Logger::CRITICAL => 'Critical',
                    Logger::ALERT => 'Alert',
                    Logger::EMERGENCY => 'Emergency',
                ];
                foreach ( $logLevels as $logLevel => $label ) {
                    ?><option <?php selected( WORDPRESSCRM_EFFECTIVE_LOG_LEVEL, $logLevel ); ?> value="<?php echo esc_attr( $logLevel ); ?>"><?php echo $label; ?></option><?php
                }
                ?>
                </select>
                <img src="/wp-content/plugins/integration-dynamics/resources/front/images/progress.gif" width="18" height="18" alt="<?php esc_attr_e( 'Saving...', 'integration-dynamics' ); ?>" style="display: none; vertical-align: baseline;" id="wpcrmVerbositySelectorSpinner">
            </label>
        </p>
        <script>
            ( function( $ ) {
                var $verbositySelector = $( '#wpcrmVerbositySelector' ),
                    $verbositySpinner = $( '#wpcrmVerbositySelectorSpinner' ),
                    verbosityPrevious = $verbositySelector.val();

                $verbositySelector.change( function( e ) {
                    $verbositySpinner.show();
                    $.post( ajaxurl, { action: 'wpcrm_log_verbosity', logVerbosity: $verbositySelector.val() } ).done( function() {

                    } ).fail( function() {
                        $verbositySelector.val( verbosityPrevious );
                        alert( '<?php echo esc_js( __( 'Couldn\'t save the new log verbosity setting.', 'integration-dynamics' ) ); ?>' );
                    } ).always( function() {
                        $verbositySpinner.hide();
                    } );
                } );
            }( jQuery ) );
        </script>
        <?php
    }
}

