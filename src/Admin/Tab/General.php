<?php
namespace AlexaCRM\WordpressCRM\Admin\Tab;

use AlexaCRM\CRMToolkit\Client;
use AlexaCRM\CRMToolkit\Settings;
use AlexaCRM\WordpressCRM\Admin;
use AlexaCRM\WordpressCRM\Admin\Tab;
use Error;
use Exception;
use AlexaCRM\WordpressCRM\Connection;

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class General extends Tab {

    public $pageId = 'general';

    public $displayName = 'Connection';

    /**
     * Settings field in WordPress (without prefix)
     *
     * @var string
     */
    protected $settingsField = 'options';

    public static $default_settings = array(
        "discoveryUrl"           => "",
        "username"               => "",
        "password"               => "",
        "loginUrl"               => "",
        "serverUrl"              => "",
        "authMode"               => "",
        "crmRegion"              => "",
        "port"                   => "",
        "useSsl"                 => false,
        "organizationUrl"        => "",
        "organizationDataUrl"    => "",
        "organizationName"       => "",
        "organizationUniqueName" => "",
        "organizationId"         => "",
        "organizationVersion"    => "",
        "connected"              => false,

        /**
         * @since 1.1.2
         */
        'last_metadata_purge'    => 0,
    );

    public function init() {
        static::$default_settings = apply_filters( 'wordpresscrm_default_options', static::$default_settings );

        parent::init();

        if ( isset( $_POST["clear_cache"] ) && $_POST["clear_cache"] ) {
            ACRM()->purgeCache();

            self::set_notice( __( 'Metadata cache has been purged and now will be rebuilt gradually.', 'integration-dynamics' ) );

            $this->options['last_metadata_purge'] = current_time( 'timestamp' );
            update_option( $this->settingsField, $this->options );
        }
    }

    public function getDisplayName() {
        return __( 'Connection', 'integration-dynamics' );
    }

    public function initializeTab( $tabHookName ) {
        add_action( 'load-' . $tabHookName, [ $this, 'checkCrmConnection' ] );
    }

    public function checkCrmConnection() {
        if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ) {

            /* Delete cache section */
            ACRM()->purgeCache();

            Connection::setConnectionStatus( false );

            $options = get_option( ACRM()->prefix . 'options' );

            if ( !isset( $options["serverUrl"] ) ||
                 !isset( $options["username"] ) ||
                 !isset( $options["password"] ) ||
                 !$options["serverUrl"] ||
                 !$options["username"] ||
                 !$options["password"]
            ) {
                self::set_errors( "Please fill in the fields that are marked as required *" );

                return;
            }

            try {
                $clientSettings = new Settings( $options );
                $client         = new Client( $clientSettings, ACRM()->cache );

                // next settings are retrieved during Client instantiation
                $options['organizationName']       = $clientSettings->organizationName;
                $options['organizationUniqueName'] = $clientSettings->organizationUniqueName;
                $options['organizationVersion']    = $clientSettings->organizationVersion;
                $options['organizationId']         = $clientSettings->organizationId;

                if ( isset( $client ) && $whoAmI = $client->executeAction( "WhoAmI" ) ) {
                    $options['connected'] = true;
                    update_option( ACRM()->prefix . 'options', $options );
                    $this->options = $options;

                    $noticeText = sprintf( __( 'Connection to Dynamics CRM <%s> has been successfully established.', 'integration-dynamics' ), $options['organizationName'] );
                    self::set_notice( $noticeText );
                } else {
                    $this->set_error( __( 'Unable to connect to Dynamics CRM using provided address, username and/or password.', 'integration-dynamics' ) );
                }
            } catch ( Exception $e ) {
                $this->set_error( $e->getMessage() );
            } catch ( Error $err ) {
                $this->set_error( sprintf( __( 'Unable to connect to Dynamics CRM: %s', 'integration-dynamics' ), $err->getMessage() ) );
            }
        }
    }

    function set_error( $string ) {
        parent::set_errors( $string );

        Connection::setConnectionStatus( false );
    }

    /*
      Sanitize our plugin settings array as needed.
     */
    public function sanitize_theme_options( $options ) {
        if ( isset( $options["serverUrl"] ) && $options["serverUrl"] ) {
            $options["serverUrl"] = trim( $options["serverUrl"] );
        }

        if ( isset( $options["username"] ) && $options["username"] ) {
            $options["username"] = trim( $options["username"] );
        }

        if ( isset( $options["password"] ) && $options["password"] ) {
            $options["password"] = trim( $options["password"] );
        }

        return $options;
    }

    function clearOptions() {
        update_option( $this->settingsField, self::$default_settings );

        $this->options = get_option( $this->settingsField );
    }

    /**
     * Render settings page.
     */
    public function render() {
        $authMode = ( $this->get_field_value( 'authMode' ) != null ) ? ( $this->get_field_value( 'authMode' ) ) : 'OnlineFederation';

        $this->options = ACRM()->option( 'options' );
        $isConnected   = ( isset( $this->options['connected'] ) && $this->options['connected'] );
        $connectLabel  = $isConnected ? __( 'Reconnect', 'integration-dynamics' ) : __( 'Connect', 'integration-dynamics' );
        ?>
        <div class="wrap">
            <?php Admin::renderSettingsTabs(); ?>
            <div class="metabox-holder">
                <div class="postbox-container" style="width: 99%;">

                    <p><?php _e( 'Configure the plugin to connect to your Dynamics CRM instance.', 'integration-dynamics' ) ?></p>

                    <hr>

                    <table class="form-table">
                        <tbody>
                        <tr>
                            <th scope="row"><?php _e( 'Deployment Type', 'integration-dynamics' ) ?></th>
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text">
                                        <label><?php _e( 'Deployment Type', 'integration-dynamics' ) ?></label>
                                    </legend>
                                    <p>
                                        <label>
                                            <input type="radio" value="OnlineFederation" class="wpcrm-setting"
                                                   name="<?php echo $this->get_field_name( 'authMode' ); ?>" <?php echo ( $authMode == "OnlineFederation" ) ? "checked='checked'" : ""; ?>
                                                   onClick='jQuery("#table-Federation").hide();
                                                            jQuery("#table-OnlineFederation").show();'> CRM Online
                                        </label>
                                        <label style="margin-left:12px!important;">
                                            <input type="radio" value="Federation" class="wpcrm-setting"
                                                   name="<?php echo $this->get_field_name( 'authMode' ); ?>" <?php echo ( $authMode == "Federation" ) ? "checked='checked'" : ""; ?>
                                                   onClick='jQuery("#table-Federation").show();
                                                            jQuery("#table-OnlineFederation").hide();'> On-premises
                                        </label>
                                        <input type="hidden" id="alexasdkauthmode"
                                               value="<?php echo esc_attr( $this->get_field_value( 'authMode' ) ); ?>">
                                    </p>
                                </fieldset>
                            </td>
                        </tr>
                        </tbody>
                    </table>

                    <div
                        id="table-Federation" <?php echo ( $authMode == "Federation" ) ? "style=''" : "style='display: none'"; ?>
                        style="">
                        <form method="post" action="options.php">
                            <?php settings_fields( $this->settingsField ); ?>
                            <table class="form-table">
                                <tbody>
                                <tr>
                                    <th scope="row"><label
                                            for="wpcrmFAddress"><?php _e( 'Dynamics CRM Address (URL) <span class="description">(required)</span>', 'integration-dynamics' ); ?></label>
                                    </th>
                                    <td>
                                        <input id="wpcrmFAddress" type="text" class="regular-text code wpcrm-setting"
                                               placeholder="https://contoso.yourdomain.com"
                                               name="<?php echo $this->get_field_name( 'serverUrl' ); ?>"
                                               value="<?php echo esc_attr( $this->get_field_value( 'serverUrl' ) ); ?>"/>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label
                                            for="wpcrmFAuthSource"><?php _e( 'Authentication Source', 'integration-dynamics' ) ?></label>
                                    </th>
                                    <td>
                                        <select id="wpcrmFAuthSource" disabled class="wpcrm-setting">
                                            <option><?php _e( 'Internet-facing deployment (IFD)', 'integration-dynamics' ) ?></option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label
                                            for="wpcrmFUsername"><?php _e( 'User Name <span class="description">(required)</span>', 'integration-dynamics' ); ?></label>
                                    </th>
                                    <td>
                                        <input id="wpcrmFUsername" type="text" class="regular-text wpcrm-setting"
                                               name="<?php echo $this->get_field_name( 'username' ); ?>"
                                               value="<?php echo esc_attr( $this->get_field_value( 'username' ) ); ?>"/>
                                        <p class="description"><?php _e( 'CRM user login', 'integration-dynamics' ); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label
                                            for="wpcrmFPassword"><?php _e( 'Password <span class="description">(required)</span>', 'integration-dynamics' ); ?></label>
                                    </th>
                                    <td>
                                        <input id="wpcrmFPassword" type="password" class="regular-text wpcrm-setting"
                                               name="<?php echo $this->get_field_name( 'password' ); ?>"
                                               value="<?php echo esc_attr( $this->get_field_value( 'password' ) ); ?>"/>
                                        <p class="description"><?php _e( 'CRM user password', 'integration-dynamics' ); ?></p>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                            <?php submit_button( $connectLabel, 'primary', 'submit', true, ( $isConnected ? 'disabled' : null ) ); ?>
                            <input type="hidden" name="<?php echo $this->get_field_name( 'authMode' ); ?>"
                                   value="Federation">
                        </form>
                    </div>

                    <div
                        id="table-OnlineFederation" <?php echo ( $authMode == "OnlineFederation" ) ? "style=''" : "style='display: none'"; ?>>
                        <form method="post" action="options.php">
                            <?php settings_fields( $this->settingsField ); ?>
                            <table class="form-table">
                                <tbody>
                                <tr>
                                    <th scope="row"><label
                                            for="wpcrmOFAddress"><?php _e( 'Dynamics CRM Address (URL) <span class="description">(required)</span>', 'integration-dynamics' ); ?></label>
                                    </th>
                                    <td>
                                        <input id="wpcrmOFAddress" type="text" class="regular-text code wpcrm-setting"
                                               placeholder="https://contoso.crm.dynamics.com"
                                               name="<?php echo $this->get_field_name( 'serverUrl' ); ?>"
                                               value="<?php echo esc_attr( $this->get_field_value( 'serverUrl' ) ); ?>"/>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label
                                            for="wpcrmOFUsername"><?php _e( 'User Name <span class="description">(required)</span>', 'integration-dynamics' ); ?></label>
                                    </th>
                                    <td>
                                        <input id="wpcrmOFUsername" class="regular-text wpcrm-setting" type="text"
                                               name="<?php echo $this->get_field_name( 'username' ); ?>"
                                               value="<?php echo esc_attr( $this->get_field_value( 'username' ) ); ?>"/>
                                        <p class="description"><?php _e( 'CRM user login', 'integration-dynamics' ); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label
                                            for="wpcrmOFPassword"><?php _e( 'Password <span class="description">(required)</span>', 'integration-dynamics' ); ?></label>
                                    </th>
                                    <td>
                                        <input id="wpcrmOFPassword" class="regular-text wpcrm-setting" type="password"
                                               name="<?php echo $this->get_field_name( 'password' ); ?>"
                                               value="<?php echo esc_attr( $this->get_field_value( 'password' ) ); ?>"/>
                                        <p class="description"><?php _e( 'CRM user password', 'integration-dynamics' ); ?></p>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                            <input type="hidden" name="<?php echo $this->get_field_name( 'authMode' ); ?>"
                                   value="OnlineFederation">
                            <?php submit_button( $connectLabel, 'primary', 'submit', true, ( $isConnected ? 'disabled' : null ) ); ?>
                        </form>
                    </div>

                    <hr>

                    <h3 class="title"><?php _e( 'Metadata Settings', 'integration-dynamics' ); ?></h3>
                    <p><?php _e( 'If you have changed your Dynamics CRM entities metadata recently, please regenerate local metadata cache to keep it up to date.', 'integration-dynamics' ) ?></p>
                    <form method="post" action="">
                        <input type="hidden" name="clear_cache" value="1"/>
                        <p class="submit">
                            <?php submit_button( __( 'Regenerate Metadata Cache', 'integration-dynamics' ), 'primary', 'submit', false, [ 'style' => 'vertical-align:middle;' ] ); ?>
                            <?php
                            $metadataRegeneratedTime = array_key_exists( 'last_metadata_purge', $this->options ) ? $this->options['last_metadata_purge'] : null;
                            if ( $metadataRegeneratedTime ) {
                                $humanReadableTime = date_i18n( __( 'F j, Y \a\t g:i A', 'integration-dynamics' ), $metadataRegeneratedTime )
                                ?>
                                <span style="padding-left:1em;vertical-align:middle;">
								<?php printf( __( 'Metadata cache was last regenerated on %s.', 'integration-dynamics' ), $humanReadableTime ); ?>
							</span>
                            <?php } ?>
                        </p>
                    </form>

                    <?php do_action( 'wordpresscrm_after_settings_general' ); ?>
                </div>
            </div>
        </div>
        <!-- Needed to allow metabox layout and close functionality. -->
        <script type="text/javascript">
            //<![CDATA[
            (function ($, authModeSelector) {
                /* Radio buttons selector */
                if (!$(authModeSelector + ":checked").length) {
                    $(authModeSelector).val('OnlineFederation');
                } else {
                    $(authModeSelector + ':checked').prop('checked', true);
                }

                $(authModeSelector).click(function () {
                    var amode = $(this).val();
                    var currmode = $('#alexasdkauthmode').val();
                    if (amode !== currmode) {
                        $('#table-' + amode).find("input[type='text'], input[type='password']").val('');
                    }
                });

                var activateReconnect = function () {
                    $('#table-Federation input[type=submit], #table-OnlineFederation input[type=submit]').prop('disabled', false);
                };
                $('.wpcrm-setting').keypress(activateReconnect);
                $('.wpcrm-setting[type=radio]').change(activateReconnect);
            })(jQuery, "[name='<?php echo ACRM()->prefix . 'options'; ?>[authMode]']");
            //]]>
        </script>
        <?php
    }

}
