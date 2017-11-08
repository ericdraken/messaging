<?php
/**
 * Messaging - ExceptionLogger.php
 * Created by: Eric Draken
 * Date: 2017/11/6
 * Copyright (c) 2017
 */

namespace Draken\Messaging\Exceptions;

use Draken\Messaging\Slack\SlackConfig;
use Draken\Messaging\Slack\SlackHandlerExtended;
use Monolog\Handler\FingersCrossed\ErrorLevelActivationStrategy;
use Monolog\Handler\FingersCrossedHandler;
use Monolog\ErrorHandler;
use Monolog\Logger;

/**
 * Using configurations set in an external config file, register a Monolog
 * logger that will log exceptions and notices above a set threshold. This logger
 * uses a fingers crossed handler to only connect to Slack when a message is ready,
 * and to batch messages until a log level threshold is crossed
 * @package Draken\Messaging\Exceptions
 */
class ExceptionLogger
{
	/** @var Logger */
	private $logger;

	/** @var ExceptionLogger */
	private static $instance;

	/** @var SlackConfig */
	private static $config;

	/**
	 * ExceptionLogger constructor.
	 *
	 * @param int $activationLevel
	 */
	private function __construct( int $activationLevel = Logger::NOTICE )
	{
		// Create a new logger
		$this->logger = new Logger( __CLASS__ );

		// Register a Slack handler
		$this->registerSlackFingersCrossedHandler( $activationLevel );

		// Register the global error handler
		ErrorHandler::register( $this->logger );
	}

	/**
	 * @return Logger
	 */
	public function getLogger()
	{
		return $this->logger;
	}

	/**
	 * Call once before calling getInstance()
	 *
	 * @param string|null $iniFile
	 * @param int $activationLevel
	 */
	public static function setupExceptionLogger( string $iniFile = null, int $activationLevel = Logger::NOTICE )
	{
		if ( is_null( self::$instance ) )
		{
			self::$config = new SlackConfig( $iniFile );

			// Get the timezone in order from supplied, environment variables, or default
			$timezone =
				self::$config->get( 'timezone' ) ?:
				(
					! empty( $tz = date_default_timezone_get() ) ? $tz :
					(
						! empty( $tz = ini_get('date.timezone') ) ? $tz :
						'America/Vancouver'
					)
				);

			// Set the timezone used for the messages here
			Logger::setTimezone( new \DateTimeZone( $timezone ) );

			self::$instance = new self( $activationLevel );
		}
	}

	/**
	 * Return a singleton instance of this class
	 * @return ExceptionLogger
	 */
	public static function getInstance()
	{
		if ( is_null( self::$instance ) )
		{
			throw new \RuntimeException( "Call setupInstallLogger() first" );
		}

		return self::$instance;
	}

	/**
	 * @param int $activationLevel
	 */
	private function registerSlackFingersCrossedHandler( int $activationLevel = Logger::NOTICE )
	{
		// Activate the handler after this threshold
		$activationStrategy = new ErrorLevelActivationStrategy( $activationLevel );

		// Setup the FingersCrossed handler which contains the SlackHandlerExtended
		$fingersCrossedHandler = new FingersCrossedHandler(
			\Closure::bind(
				function ()
				{
					// Get the channel in order from environment variable or default.
					// Use the environment variable first in case it is desired to
					// send an exception log to a specific channel at exception time
					$channel =
						getenv( self::$config->get( 'channelEnvVarName' ) ) ?:
						(
							self::$config->get( 'defaultChannel' ) ??
							'general'
						);

					// The handler below is only called upon activation
					// to save the overhead of setting up a WebSocket to Slack
					// until the actual error is triggered
					$slackHandler = new SlackHandlerExtended(
						$channel,
						self::$config->get( 'slackApiToken' ),
						self::$config->get( 'slackApiTokenEnvVarName' ),
						self::$config->get( 'messageFormat' ),
						self::$config->get( 'dateFormat' ),
						self::$config->get( 'includeStackTrace', false )
					);

					// Add extra information to the log by pushing Slack handlers
					// from the config file, if they are present
					$processors = self::$config->get( 'processors', [] );

					if ( is_array( $processors ) )
					{
						// Remove duplicate processors
						$processors = array_unique( $processors, SORT_STRING );

						foreach ( $processors as $processor )
						{
							if ( class_exists( $processor ) )
							{
								$slackHandler->pushProcessor( new $processor() );
							} else
							{
								throw new \RuntimeException( "Processor could not be found: $processor" );
							}
						}
					}

					// Return the new handler
					return $slackHandler;
				},
				$this
			),
			$activationStrategy
		);

		// Add this handler
		$this->logger->pushHandler( $fingersCrossedHandler );
	}
}