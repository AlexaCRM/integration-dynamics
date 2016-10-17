<?php

namespace AlexaCRM\WordpressCRM\Admin\Metabox;

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
</script>
<?php
    }

    /**
     * Enqueues front-end scripting for the wizard.
     */
    public function enqueueScripts() {
        $scriptPath = ACRM()->plugin_url() . '/resources/front/js/shortcode-wizard.js';

        wp_enqueue_script( 'wordpresscrm-shortcode-wizard', $scriptPath, [ 'jquery', 'underscore', 'backbone' ], false, true );

        wp_localize_script( 'wordpresscrm-shortcode-wizard', 'wpcrmShortcodeWizard', [
            'shortcodes' => [
                'view' => [
                    'name' => 'view',
                    'displayName' => 'View',
                    'description' => 'Renders a Dynamics CRM View as a table.',
                    'fields' => [
                        'entity' => [
                            'name' => 'entity',
                            'displayName' => 'Entity name',
                            'description' => 'Name of the entity to display a view of.',
                            'type' => 'dropdown',
                            'action' => 'wpcrm_sw_entities',
                        ],
                    ],
                ],
            ],
        ] );
    }

}
