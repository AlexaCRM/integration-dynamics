<?php

namespace AlexaCRM\WordpressCRM;

use AlexaCRM\CRMToolkit\LoggerInterface;

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
    private static $levels = [
        0 => 'unknown',
        1 => 'emergency',
        2 => 'alert',
        4 => 'critical',
        8 => 'error',
        16 => 'warning',
        32 => 'notice',
        64 => 'info',
        128 => 'debug',
    ];

    /**
     * Target to write logs into.
     *
     * @var string
     */
    private $logTarget = '';

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
     * Emergency: system is unusable.
     *
     * @param string $message
     * @param mixed $context
     *
     * @return void
     */
    public function emergency( $message, $context = null ) {
        $this->recordMessage( $message, static::LOG_EMERGENCY, $context );
    }

    /**
     * Alert: action must be taken immediately.
     *
     * @param string $message
     * @param mixed $context
     *
     * @return void
     */
    public function alert( $message, $context = null ) {
        $this->recordMessage( $message, static::LOG_ALERT, $context );
    }

    /**
     * Critical: critical conditions.
     *
     * @param string $message
     * @param mixed $context
     *
     * @return void
     */
    public function critical( $message, $context = null ) {
        $this->recordMessage( $message, static::LOG_CRITICAL, $context );
    }

    /**
     * Error: error conditions.
     *
     * @param string $message
     * @param mixed $context
     *
     * @return void
     */
    public function error( $message, $context = null ) {
        $this->recordMessage( $message, static::LOG_ERROR, $context );
    }

    /**
     * Warning: warning conditions.
     *
     * @param string $message
     * @param mixed $context
     *
     * @return void
     */
    public function warning( $message, $context = null ) {
        $this->recordMessage( $message, static::LOG_WARNING, $context );
    }

    /**
     * Notice: normal but significant condition.
     *
     * @param string $message
     * @param mixed $context
     *
     * @return void
     */
    public function notice( $message, $context = null ) {
        $this->recordMessage( $message, static::LOG_NOTICE, $context );
    }

    /**
     * Informational: informational messages.
     *
     * @param string $message
     * @param mixed $context
     *
     * @return void
     */
    public function info( $message, $context = null ) {
        $this->recordMessage( $message, static::LOG_INFO, $context );
    }

    /**
     * Debug: debug-level messages.
     *
     * @param string $message
     * @param mixed $context
     *
     * @return void
     */
    public function debug( $message, $context = null ) {
        $this->recordMessage( $message, static::LOG_DEBUG, $context );
    }

    /**
     * Records the message into log.
     *
     * @param string $message
     * @param int $level
     * @param mixed $context
     */
    private function recordMessage( $message, $level, $context = null ) {
        $shouldBeRecorded = (bool)( $this->logLevels & $level );
        if ( !$shouldBeRecorded ) {
            return;
        }

        if ( !array_key_exists( $level, static::$levels ) ) {
            $level = 0;
        }

        $levelLabel = strtoupper( static::$levels[$level] );
        $timestamp = \DateTime::createFromFormat( 'U.u', microtime( true ) );

        $record = "[{$levelLabel}]\t({$timestamp->format( 'Y-m-d H:i:s.u' )}) {$message}\n";
        if ( $context !== null ) {
            $record .= "-----------------------\n"
                . "Message context:\n"
                . print_r( $context, true )
                . "-----------------------\n";
        }

        file_put_contents( $this->logTarget, $record, FILE_APPEND );
    }
}
