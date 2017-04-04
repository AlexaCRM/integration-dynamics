<?php

namespace AlexaCRM\WordpressCRM;

/**
 * Allows adding custom notification in WordPress admin UI.
 */
class Notifier {

    /**
     * Informational notice. Corresponds to 'notice-info' class in HTML.
     */
    const NOTICE_INFO = 1;

    /**
     * Success notice. Corresponds to 'notice-success' class in HTML.
     */
    const NOTICE_SUCCESS = 2;

    /**
     * Warning notice. Corresponds to 'notice-warning' class in HTML.
     */
    const NOTICE_WARNING = 4;

    /**
     * Error notice. Corresponds to 'notice-error' class in HTML.
     */
    const NOTICE_ERROR = 8;

    /**
     * @param $content
     * @param $type
     * @param bool $isDismissible
     */
    public function add( $content, $type = Notifier::NOTICE_INFO, $isDismissible = true ) {
        $session = ACRM()->getSession();

        $session->getFlashBag()->add( 'admin-notice', [
            'content' => $content,
            'type' => $type,
            'isDismissible' => $isDismissible,
        ] );
    }

    /**
     * Retrieves all notifications.
     *
     * @return array
     */
    public function getNotifications() {
        return ACRM()->getSession()->getFlashBag()->get( 'admin-notice', [] );
    }

    /**
     * Converts the given notification type (one of Notifier::NOTICE_* constants)
     * to a class name.
     *
     * @param int $noticeType
     *
     * @return string
     */
    public static function getNoticeClass( $noticeType ) {
        switch ( $noticeType ) {
            case Notifier::NOTICE_ERROR:
                return 'notice-error';
            case Notifier::NOTICE_WARNING:
                return 'notice-warning';
            case Notifier::NOTICE_SUCCESS:
                return 'notice-success';
            case Notifier::NOTICE_INFO:
            default:
                return 'notice-info';
        }
    }

}
