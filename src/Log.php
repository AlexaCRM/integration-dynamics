<?php

namespace AlexaCRM\WordpressCRM;

use Psr\Log\LoggerInterface;

/**
 * Class Log.
 *
 * @package AlexaCRM\WordpressCRM
 */
class Log implements LoggerInterface {

    /**
     * Don't log anything.
     */
    const LOG_NONE = 0;

    /**
     * Log emergency only.
     */
    const LOG_EMERGENCY = 1;

    /**
     * Log alert only.
     */
    const LOG_ALERT = 2;

    /**
     * Log critical only.
     */
    const LOG_CRITICAL = 4;

    /**
     * Log error only.
     */
    const LOG_ERROR = 8;

    /**
     * Log warning only.
     */
    const LOG_WARNING = 16;

    /**
     * Log notice only.
     */
    const LOG_NOTICE = 32;

    /**
     * Log info only.
     */
    const LOG_INFO = 64;

    /**
     * Log debug only.
     */
    const LOG_DEBUG = 128;

    /**
     * Log all messages.
     */
    const LOG_ALL = 255;

    /**
     * Log all faults, don't log notice, info, and debug messages.
     */
    const LOG_FAULTS = 31;

    /**
     * Human-readable severity level names.
     *
     * @var array
     */
    private static $levels = array(
        0 => 'unknown',
        1 => 'emergency',
        2 => 'alert',
        4 => 'critical',
        8 => 'error',
        16 => 'warning',
        32 => 'notice',
        64 => 'info',
        128 => 'debug',
    );

    /**
     * Target to write logs into.
     *
     * @var string
     */
    public $logTarget = '';

    /**
     * Allowed log levels.
     *
     * @var int
     */
    private $logLevels = 31;

    /**
     * Log constructor.
     *
     * @param string $storagePath
     * @param int $logLevels
     */
    public function __construct( $storagePath, $logLevels = 31 ) {
        $this->logTarget = $storagePath . '/integration-dynamics.log';
        $this->logLevels = $logLevels;

        register_shutdown_function( function() {
            $this->info( "Request complete.\n=======================\n" );
        } );
    }

    /**
     * System is unusable.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function emergency( $message, array $context = [] ) {
        $this->log( self::LOG_EMERGENCY, $message, $context );
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function alert( $message, array $context = [] ) {
        $this->log( self::LOG_ALERT, $message, $context );
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function critical( $message, array $context = [] ) {
        $this->log( self::LOG_CRITICAL, $message, $context );
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function error( $message, array $context = [] ) {
        $this->log( self::LOG_ERROR, $message, $context );
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function warning( $message, array $context = [] ) {
        $this->log( self::LOG_WARNING, $message, $context );
    }

    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function notice( $message, array $context = [] ) {
        $this->log( self::LOG_NOTICE, $message, $context );
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function info( $message, array $context = [] ) {
        $this->log( self::LOG_INFO, $message, $context );
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function debug( $message, array $context = [] ) {
        $this->log( self::LOG_DEBUG, $message, $context );
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param int    $level
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function log( $level, $message, array $context = [] ) {
        $shouldBeRecorded = (bool)( $this->logLevels & $level );
        if ( !$shouldBeRecorded ) {
            return;
        }

        if ( !array_key_exists( $level, self::$levels ) ) {
            $level = 0;
        }

        $levelLabel = strtoupper( self::$levels[$level] );
        $timestamp = \DateTime::createFromFormat( 'U.u', microtime( true ) );

        $record = "[{$levelLabel}]\t({$timestamp->format( 'Y-m-d H:i:s.u' )}) {$message}\n";
        if ( count( $context ) ) {
            $record .= "-----------------------\n"
                . "Message context:\n"
                . print_r( $context, true )
                . "-----------------------\n";
        }

        file_put_contents( $this->logTarget, $record, FILE_APPEND );
    }
}
