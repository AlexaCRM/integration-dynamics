<?php

namespace AlexaCRM\WordpressCRM\Admin\Metabox;

use AlexaCRM\CRMToolkit\Entity\MetadataCollection;

/**
 * Shortcode Wizard for the WordPress Post edit screen
 *
 * @package AlexaCRM\WordpressCRM\Admin\Metabox
 */
class ShortcodeWizard {

    /**
     * ShortcodeWizard constructor.
     */
    public function __construct() {
        add_action( 'add_meta_boxes', [ $this, 'registerMetabox' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueueScripts' ] );

        // register AJAX handlers
        add_action( 'wp_ajax_wpcrm_sw_result', [ $this, 'handlerResult' ] );
        add_action( 'wp_ajax_wpcrm_sw_entities', [ $this, 'handlerEntities' ] );
        add_action( 'wp_ajax_wpcrm_sw_entity_views', [ $this, 'handlerEntityViews' ] );
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
                __( 'Dynamics CRM Shortcode Wizard', 'integration-dynamics' ),
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
<% fields.forEach( function( field ) {
    jQuery( '.wpcrm-sw-container .shortcode-container' ).append( field.getView().render().$el );
} ); %>
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
        <select class="dropdown-value">
            <option value=""><?php _e( '— Select —', 'integration-dynamics' ); ?></option>
            <% _.each( values, function( valueName, value ) { %>
                    <option value="<%- value %>"><%- valueName %></option>
            <% } ); %>
        </select>
    </label>
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

        wp_localize_script( 'wordpresscrm-shortcode-wizard', 'wpcrmShortcodeWizard', [
            'shortcodes' => [
                'view' => [
                    'name' => 'view',
                    'displayName' => __( 'View', 'integration-dynamics' ),
                    'description' => __( 'Renders a Dynamics CRM View as a table.', 'integration-dynamics' ),
                    'fields' => [
                        'entity' => [
                            'name' => 'entity',
                            'displayName' => __( 'Entity name', 'integration-dynamics' ),
                            'description' => __( 'Name of the entity to display a view of.', 'integration-dynamics' ),
                            'type' => 'dropdown',
                            'value' => [
                                'source' => 'ajax',
                                'action' => 'wpcrm_sw_entities',
                            ],
                        ],
                        'view' => [
                            'name' => 'view',
                            'displayName' => __( 'Entity View name', 'integration-dynamics' ),
                            'description' => __( 'Name of the view to display.', 'integration-dynamics' ),
                            'type' => 'dropdown',
                            'value' => [
                                'source' => 'ajax',
                                'action' => 'wpcrm_sw_entity_views',
                                'args' => [ 'entity' ], // cannot use 'arguments' per JavaScript restrictions
                            ],
                        ],
                    ],
                ],
            ],
        ] );
    }

    public function handlerResult() {
        if ( !array_key_exists( 'name', $_POST ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid request - shortcode name is absent', 'integration-dynamics' ) ] );
        }

        if ( !array_key_exists( 'fields', $_POST ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid request - shortcode arguments are absent', 'integration-dynamics' ) ] );
        }

        $shortcodeName = trim( $_POST['name'] );
        $shortcodeFields = $_POST['fields'];

        if ( !is_array( $shortcodeFields ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid request - shortcode arguments must be an array', 'integration-dynamics' ) ] );
        }

        if ( $shortcodeName !== 'view' ) {
            wp_send_json_error( [ 'message' => __( 'Shortcode is not supported', 'integration-dynamics' ) ] );
        }

        $resultTemplate = '[msdyncrm_view entity="%1$s" name="%2$s"]';

        $result = sprintf( $resultTemplate, $shortcodeFields['entity'], $shortcodeFields['view'] );

        wp_send_json_success( [ 'result' => $result ] );
    }

    /**
     * Returns the list of CRM entities.
     */
    public function handlerEntities() {
        try {
            $entities = MetadataCollection::instance()->getEntitiesList();
            asort( $entities );

            wp_send_json_success( $entities );
        } catch ( \Exception $e ) {
            wp_send_json_error( [ 'message' => $e->getMessage() ] );
        }
    }

    /**
     * Returns the list of views for a CRM entity.
     */
    public function handlerEntityViews() {
        $views = [];

        $entityName = trim( $_POST['entity'] );

        if ( $entityName === '' ) {
            wp_send_json_error( [ 'message' => __( 'Empty entity name in the request', 'integration-dynamics' ) ] );
        }

        $entity = ASDK()->entity( $entityName );

        $fetch = '<fetch version="1.0" output-format="xml-platform" mapping="logical" distinct="false">
						<entity name="userquery">
							<attribute name="name" />
							<attribute name="returnedtypecode" />
							 <filter type="and">
								<condition attribute="returnedtypecode" operator="eq" value="' . $entity->metadata()->objectTypeCode . '" />
							  </filter>
						</entity>
					  </fetch>';

        $userQueries = ASDK()->retrieveMultiple( $fetch );

        $fetch = '<fetch version="1.0" output-format="xml-platform" mapping="logical" distinct="false">
						<entity name="savedquery">
							<attribute name="name" />
							<attribute name="returnedtypecode" />
							 <filter type="and">
								<condition attribute="returnedtypecode" operator="eq" value="' . $entity->metadata()->objectTypeCode . '" />
							  </filter>
						</entity>
					  </fetch>';

        $savedQueries = ASDK()->retrieveMultiple( $fetch );

        $viewEntities = array_merge( $userQueries->Entities, $savedQueries->Entities );

        foreach ( $viewEntities as $viewEntity ) {
            $views[$viewEntity->displayname] = $viewEntity->displayname;
        }

        asort( $views );

        wp_send_json_success( $views );
    }

}
