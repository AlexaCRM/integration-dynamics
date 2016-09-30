<?php

namespace AlexaCRM\WordpressCRM\Admin\Tab;

use AlexaCRM\WordpressCRM\Admin;
use AlexaCRM\WordpressCRM\Admin\Tab;

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
        return __( 'About', 'wordpresscrm' );
    }

    public function render() {
        ?>
        <div class="wrap">
            <?php Admin::renderSettingsTabs(); ?>

            <?php $this->renderAboutInformation(); ?>
        </div>
        <?php
    }

    public function renderAboutInformation() {
        ?>

        <h2><?php _e( "About", "wordpresscrm" ) ?></h2>

        <p>
            <?php echo ACRM()->full_plugin_name; ?><br>
            <?php printf( __( 'Version: %s', 'wordpresscrm' ), ACRM()->version ); ?><br>
            <?php printf( __( 'Copyright &copy; %1$d %2$s', 'wordpresscrm' ), date( 'Y' ), ACRM()->author_name ); ?><br>
            <?php printf( __( '<a href="%s">Plugin website</a>', 'wordpresscrm' ), ACRM()->plugin_homepage ) ?><br>
            <?php printf( __( '<a href="http://%s">Documentation</a>', 'wordpresscrm' ), ACRM()->plugin_documentation_homepage ); ?><br>
            <a href="mailto:<?php echo ACRM()->support_email; ?>"><?php echo ACRM()->support_email; ?></a>
        </p>

        <?php

        do_action( 'wordpresscrm_after_settings_about', $this );
    }
}

