<?php

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

$activeTab   = $_GET['page'];
$options     = ACRM()->option( 'options' );
$isConnected = ( isset( $options['connected'] ) && $options['connected'] );
?>
<style>
    .wordpresscrm-status {
        font-size: 75%;
        cursor: default;
    }

    .wordpresscrm-status::before {
        content: '';
        display: inline-block;
        width: 8px;
        height: 8px;
        margin: 0 4px 0 8px;
        border-radius: 50%;
    }

    .wordpresscrm-status.connected::before {
        background: #00aa2c;
    }

    .wordpresscrm-status.disconnected::before {
        background: #a00;
    }
</style>
<h2>
    <span class="nav-title">Dynamics 365 Integration</span>
    <span class="wordpresscrm-status <?php echo $isConnected ? 'connected' : 'disconnected'; ?>"
          title="<?php $isConnected ? _e( 'WordPress is connected to your Dynamics 365 organization and is ready for use.', 'integration-dynamics' ) : _e( 'WordPress is not connected to Dynamics CRM.', 'integration-dynamics' ); ?>"
    ><?php $isConnected ? printf( __( 'Connected to &lt;%s&gt;', 'integration-dynamics' ), $options['organizationName'] ) : _e( 'Not connected', 'integration-dynamics' ); ?></span>
</h2>
<h2 class="nav-tab-wrapper">
    <?php
    $tabs = apply_filters( 'wordpresscrm_tabs', static::$tabs );
    uasort( $tabs, function ( $first, $second ) {
        if ( $first[1] === $second[1] ) {
            return 0;
        }

        return ( $first[1] < $second[1] ) ? - 1 : 1;
    } );

    foreach ( $tabs as $tabName => $tabSettings ) {
        /**
         * @var $tabInstance \AlexaCRM\WordpressCRM\Admin\Tab
         */
        $tabInstance = static::$tabsCollection[ $tabName ];

        $tabSlug = 'wordpresscrm_' . $tabInstance->pageId;
        if ( $tabInstance->pageId === 'general' ) {
            $tabSlug = 'wordpresscrm';
        }

        $tabDisplayName = $tabInstance->displayName;
        $isActive       = ( $tabSlug === $activeTab );

        ?><a href="?page=<?php echo esc_attr( $tabSlug ); ?>" class="nav-tab <?php if ( $isActive ) {
            echo ' nav-tab-active';
        } ?>"><?php echo $tabDisplayName; ?></a><?php
    }
    ?>
</h2>
