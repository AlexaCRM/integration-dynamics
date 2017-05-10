<?php
namespace AlexaCRM\WordpressCRM\Admin\Tab;

use AlexaCRM\WordpressCRM\Admin;
use AlexaCRM\WordpressCRM\Admin\Tab;
use AlexaCRM\WordpressCRM\Plugin;

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Messages extends Tab {

    public $pageId = 'messages';

    public $displayName = 'Messages';

    protected $settingsField = 'messages';

    public static $default_settings = [];

    function __construct() {
        static::$default_settings = $this->getDefaultSettings();

        parent::__construct();

        $this->updateMessages();
    }

    private function getDefaultSettings() {
        $messages = [
            'general' => [
                'not_connected' => __( 'WordPress is not connected to Dynamics 365', 'integration-dynamics' ),
            ],
            'form' => [
                'validation_error' => __( 'Form validation failed', 'integration-dynamics' ),
                'crm_error'        => __( 'An error has occurred submitting the form', 'integration-dynamics' ),
                'invalid_captcha'  => __( 'Error: invalid captcha', 'integration-dynamics' ),
            ],
        ];

        /**
         * Filters the list of default message strings
         *
         * @param array $messages messageGroup => ( messageId => string )
         */
        return apply_filters( 'wordpresscrm_messages', $messages );
    }

    private function getMessagesSchema() {
        $schema = [
            'general' => [
                'displayName' => __( 'General messages', 'integration-dynamics' ),
                'fields' => [
                    'not_connected' => __( 'Not connected error', 'integration-dynamics' ),
                ],
            ],
            'form' => [
                'displayName' => __( 'Form errors', 'integration-dynamics' ),
                'fields' => [
                    'validation_error' => __( 'Validation error', 'integration-dynamics' ),
                    'crm_error' => __( 'Form submit error', 'integration-dynamics' ),
                    'invalid_captcha' => __( 'Invalid captcha error', 'integration-dynamics' ),
                ],
            ]
        ];

        /**
         * Filters the schema of messages
         *
         * @param array $schema messageGroup => ( displayName => string, description => string, fields => ( messageId => string ) )
         */
        return apply_filters( 'wordpresscrm_messages_schema', $schema );
    }

    public function getDisplayName() {
        return __( 'Messages', 'integration-dynamics' );
    }

    public function updateMessages() {
        if ( !$this->options ) {
            update_option( Plugin::PREFIX . 'messages', static::$default_settings );
        } else {
            foreach ( static::$default_settings as $sectionKey => $section ) {
                if ( !isset( $this->options[ $sectionKey ] ) ) {
                    $this->options[ $sectionKey ] = $section;
                } else {
                    foreach ( $section as $fieldKey => $field ) {
                        if ( !isset( $this->options[ $sectionKey ] ) ) {
                            $this->options[ $sectionKey ][ $fieldKey ] = $field;
                        }
                    }
                }
            }
            update_option( $this->settingsField, $this->options );
        }
    }

    public function initializeTab( $tabHookName ) {}

    protected function get_field_name( $name, $key = null ) {
        if ( $key ) {
            return sprintf( '%s[%s][%s]', $this->settingsField, $key, $name );
        } else {
            return sprintf( '%s[%s]', $this->settingsField, $name );
        }
    }

    protected function get_field_id( $id ) {
        return sprintf( '%s[%s]', $this->settingsField, $id );
    }

    protected function get_field_value( $name, $key = null ) {
        if ( $key ) {
            return $this->options[ $key ][ $name ];
        } else {
            return $this->options[ $name ];
        }
    }

    public function render() {
        $schema = $this->getMessagesSchema(); ?>
        <div class="wrap">
            <?php Admin::renderSettingsTabs(); ?>

            <p><?php _e( 'This page allows you to adjust messages that are displayed to users.', 'integration-dynamics' ); ?> <a href="http://docs.alexacrm.com/wpcrm/configuration/messages/" target="_blank"><?php _e( 'Documentation &raquo;', 'integration-dynamics' ); ?></a></p>

            <hr>
            <form method="post" action="options.php">
                <?php
                settings_fields( $this->settingsField );
                do_settings_sections( $this->settingsField );

                foreach ( $schema as $sectionName => $section ) {
                    ?><h2><?php echo $section['displayName']; ?></h2>
                    <?php if ( array_key_exists( 'description', $section ) ) {
                          ?><p><?php echo $section['description']; ?></p><?php } ?>
                    <table class="form-table">
                    <?php
                    foreach ( $section['fields'] as $fieldName => $fieldDisplayName ) {
                        $fieldId = 'wpcrm' . ucfirst( $sectionName ) . ucfirst( $fieldName );
                        ?><tr><th scope="row"><label for="<?php echo esc_attr( $fieldId ); ?>"><?php
                        echo $fieldDisplayName; ?></label></th><td><textarea id="<?php echo esc_attr( $fieldId );
                            ?>" class="large-text code" rows="3" name="<?php echo $this->get_field_name( $fieldName, $sectionName );
                            ?>"><?php echo $this->get_field_value( $fieldName, $sectionName ); ?></textarea></td></tr><?php
                    }
                    ?></table><?php
                    submit_button();
                }
                ?>
            </form>
        </div>
        <?php
    }
}
