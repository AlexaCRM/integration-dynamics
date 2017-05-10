<?php

namespace AlexaCRM\WordpressCRM\Admin\Metabox;

use AlexaCRM\WordpressCRM\Admin\Metabox\ShortcodeWizard\Shortcode;

/**
 * Shortcode Wizard for the WordPress Post edit screen
 *
 * @package AlexaCRM\WordpressCRM\Admin\Metabox
 */
class ShortcodeWizard {

    /**
     * Collection of registered shortcodes.
     *
     * @var Shortcode[]
     */
    protected $shortcodes = [];

    /**
     * ShortcodeWizard constructor.
     */
    public function __construct() {
        add_action( 'add_meta_boxes', [ $this, 'registerMetabox' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueueScripts' ] );

        /**
         * Allows to register shortcodes for the wizard.
         *
         * @param ShortcodeWizard $shortcodeWizard
         */
        do_action( 'wordpresscrm_sw_register', $this );

        // register AJAX handlers
        add_action( 'wp_ajax_wpcrm_sw_result', [ $this, 'handleResultRequest' ] );
        add_action( 'wp_ajax_wpcrm_sw_field', [ $this, 'handleFieldRequest' ] );
    }

    /**
     * Registers a shortcode in the Shortcode Wizard.
     *
     * If the shortcode has been registered already, it is overwritten with the new object reference.
     *
     * @param Shortcode $shortcode
     *
     * @return $this
     */
    public function registerShortcode( Shortcode $shortcode ) {
        $this->shortcodes[$shortcode->name] = $shortcode;

        return $this;
    }

    /**
     * Returns a shortcode definition for the Shortcode Wizard.
     *
     * @param string $shortcodeName
     *
     * @return Shortcode
     */
    public function getShortcode( $shortcodeName ) {
        if ( !$this->isSupported( $shortcodeName ) ) {
            throw new \InvalidArgumentException( "Shortcode [{$shortcodeName}] is not supported" );
        }

        return $this->shortcodes[$shortcodeName];
    }

    /**
     * Tells whether the given shortcode is supported.
     *
     * @param string $shortcodeName
     *
     * @return bool
     */
    public function isSupported( $shortcodeName ) {
        return array_key_exists( $shortcodeName, $this->shortcodes );
    }

    /**
     * Registers the metabox in WordPress.
     */
    public function registerMetabox() {
        $enabledPostTypes = get_option( 'wordpresscrm_custom_post_types', array() );
        $supportedPostTypes = apply_filters( 'wp_access_supported_pages', array_merge( array(
            'page',
            'post'
        ), $enabledPostTypes ) );

        /*
         * TODO: Pass $supportedPostTypes directly to add_meta_box() after WordPress <4.4 is phased out.
         */
        foreach ( $supportedPostTypes as $postType ) {
            add_meta_box(
                'wpcrmShortcodeWizardContainer',
                __( 'Dynamics 365 Shortcode Wizard', 'integration-dynamics' ),
                [ $this, 'render' ],
                $postType, 'normal', 'high'
            );
        }
    }

    /**
     * Renders the metabox.
     */
    public function render() {
        ?>
        <div id="wpcrmShortcodeWizard"></div>
<script type="text/template" id="tpl-wpcrmShortcodeWizard">
<p>
    <label><?php _e( 'Pick a shortcode:', 'integration-dynamics' ); ?>
        <select class="wpcrm-sw-selector">
            <option value=""><?php _e( '— Select —', 'integration-dynamics' ); ?></option>
            <% shortcodes.forEach( function( shortcodeDefinition ) {
            %><option value="<%- shortcodeDefinition.get( 'name' ) %>"><%- shortcodeDefinition.get( 'displayName' ) %></option><%
            } ); %>
        </select>
    </label>
</p>
<div class="wpcrm-sw-container" style="border-top: 1px solid #eceeef; padding-top: 10px; display: none;">
    <div class="shortcode-description">
        <strong class="name"></strong>. <em class="description"></em>
    </div>
    <div class="shortcode-container">

    </div>
</div>
</script>
<script type="text/template" id="tpl-wpcrmShortcodeWizardShortcode">
<div class="shortcode-fields"></div>
<div class="shortcode-result" style="border-top: 1px solid #eceeef; padding-top: 10px; display: none;">
    <textarea style="width:100%" rows="6" readonly></textarea>
    <p class="description"><?php _e( 'Copy the generated code and paste into the editor window.', 'integration-dynamics' ); ?></p>
</div>
</script>
<script type="text/template" id="tpl-wpcrmShortcodeWizardShortcodeField">
Ditch it.
</script>
<script type="text/template" id="tpl-wpcrmShortcodeWizardShortcodeDropdownField">
<p>
    <label>
        <%- field.get( 'displayName' ) %><br>
        <select class="value">
            <option value=""><?php _e( '— Select —', 'integration-dynamics' ); ?></option>
            <% _.each( values, function( valueName, value ) { %>
                    <option value="<%- value %>"><%- valueName %></option>
            <% } ); %>
        </select>
    </label><br>
    <span class="description"><%- field.get( 'description' ) %></span>
</p>
</script>
<script type="text/template" id="tpl-wpcrmShortcodeWizardShortcodeNumberField">
<p>
    <label>
        <%- field.get( 'displayName' ) %><br>
        <input type="number" class="value" value="<%- values %>" min="0">
        <!-- variable available: values -->
    </label><br>
    <span class="description"><%- field.get( 'description' ) %></span>
</p>
</script>
<script type="text/html" id="tpl-wpcrmShortcodeWizardShortcodeFieldError">
<p style="color:#f00;">
    <% if ( message ) { %>
    <?php printf( __( 'Error: %s', 'integration-dynamics' ), '<%- message %>' ); ?>
    <% } else { %>
    <?php _e( 'Unknown error occurred.', 'integration-dynamics' ); ?>
    <% } %>
</p>
</script>
<script type="text/html" id="tpl-wpcrmShortcodeWizardShortcodeFieldLoading">
<p>
    <%- fieldName %><br>
    <img src="<?php echo ACRM()->getPluginURL(); ?>/resources/front/images/progress.gif" width="18" height="18" alt="Loading...">
</p>
</script>
<?php
    }

    /**
     * Enqueues front-end scripting for the wizard.
     */
    public function enqueueScripts() {
        $scriptPath = ACRM()->getPluginURL() . '/resources/front/js/shortcode-wizard.js';

        wp_enqueue_script( 'wordpresscrm-shortcode-wizard', $scriptPath, [ 'jquery', 'underscore', 'backbone' ], false, true );

        $wizardDefinition = [ 'shortcodes' => [] ];
        foreach ( $this->shortcodes as $shortcodeName => $shortcode ) {
            $shortcodeDefinition = [
                'name' => $shortcode->name,
                'displayName' => $shortcode->displayName,
                'description' => $shortcode->description,
                'fields' => [],
            ];

            foreach ( $shortcode->getFields() as $fieldName => $field ) {
                $fieldDefinition = [
                    'name' => $field->name,
                    'displayName' => $field->displayName,
                    'description' => $field->description,
                    'type' => $field::TYPE,
                    'value' => [
                        'source' => 'none',
                        'args' => $field->bindingFields,
                    ],
                ];

                if ( $field->isApiAvailable() ) {
                    $fieldDefinition['value']['source'] = 'api';
                } elseif ( $field->isStaticValueAvailable() ) {
                    $fieldDefinition['value']['source'] = 'static';
                    $fieldDefinition['value']['values'] = $field->getValue();
                }

                $shortcodeDefinition['fields'][$fieldName] = $fieldDefinition;
            }

            $wizardDefinition['shortcodes'][$shortcodeName] = $shortcodeDefinition;
        }

        wp_localize_script( 'wordpresscrm-shortcode-wizard', 'wpcrmShortcodeWizard', $wizardDefinition );

        wp_localize_script( 'wordpresscrm-shortcode-wizard', 'wpcrmShortcodeWizardI18n', [
            'generating-shortcode' => __( 'Generating the shortcode...', 'integration-dynamics' ),
        ] );
    }

    /**
     * Handles the "result" request from the wizard.
     *
     * Generates the shortcode based on given field values.
     */
    public function handleResultRequest() {
        $query = ACRM()->request->request;

        if ( !$query->has( 'name' ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid request - shortcode name is absent', 'integration-dynamics' ) ] );
        }

        if ( !$query->has( 'fields' ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid request - shortcode arguments are absent', 'integration-dynamics' ) ] );
        }

        $shortcodeName = trim( $query->get( 'name' ) );
        $shortcodeFields = $query->get( 'fields' );

        if ( !is_array( $shortcodeFields ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid request - shortcode arguments must be an array', 'integration-dynamics' ) ] );
        }

        if ( !$this->isSupported( $shortcodeName ) ) {
            wp_send_json_error( [ 'message' => __( 'Shortcode is not supported', 'integration-dynamics' ) ] );
        }

        $result = $this->getShortcode( $shortcodeName )->generateCode( $shortcodeFields );

        wp_send_json_success( [ 'result' => $result ] );
    }

    /**
     * Handles the "field" request.
     *
     * Returns the value(s) for the requested shortcode field, with optional binding values
     * that may influence the output.
     */
    public function handleFieldRequest() {
        $query = ACRM()->request->request;

        if ( !$query->has( 'shortcode' ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid request - shortcode name is absent', 'integration-dynamics' ) ] );
        }

        if ( !$query->has( 'field' ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid request - field name is absent', 'integration-dynamics' ) ] );
        }

        $shortcodeName = trim( $query->get( 'shortcode' ) );
        $fieldName = trim( $query->get( 'field' ) );
        $values = $query->get( 'values', [] ); // values that the field depends on

        if ( !is_array( $values ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid request - values for the field must be an array', 'integration-dynamics' ) ] );
        }

        try {
            $fieldValue = $this->getShortcode( $shortcodeName )->getFieldValue( $fieldName, $values );

            wp_send_json_success( $fieldValue );
        } catch ( \InvalidArgumentException $e ) {
            wp_send_json_error( [ 'message' => sprintf( __( 'Invalid request. %s', 'integration-dynamics' ), $e->getMessage() ) ] );
        } catch( \Exception $e ) {
            wp_send_json_error( [ 'message' => $e->getMessage() ] );
        }
    }

}
