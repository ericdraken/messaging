<?php
/**
 * Messaging - ExceptionLogger.php
 * Created by: Eric Draken
 * Date: 2017/11/6
 * Copyright (c) 2017
 */

use Draken\Messaging\Slack\SlackHandlerExtended;
use Monolog\ErrorHandler;
use Monolog\Handler\FingersCrossed\ErrorLevelActivationStrategy;
use Monolog\Handler\FingersCrossedHandler;
use Monolog\Logger;

/**
 * FIXME: Redo this text
 * Class \SlackApacheException\Logger
 * This class sets up an Apache logger and a
 * Slack logger using for both FingersCrossed handler
 * to delay the invocation of the handlers until a
 * significant event occurs. Plus, the global error
 * handler will trigger an alert as well
 * @package SlackApacheException
 */
class ExceptionLogger
{
	/**
	 * Default timezone of messages
	 * @var string
	 */
	private $defaultTimezone = "America/Los_Angeles";

	/**
	 * Hold the principle logger
	 * @var Logger
	 */
	private $logger;

	/** @var ExceptionLogger */
	private static $instance;

	/**
	 * ExceptionLogger constructor.
	 *
	 * @param string $channel
	 * @param int $activationLevel
	 */
	private function __construct( string $channel, int $activationLevel = Logger::NOTICE )
	{
		// Create a new logger
		$this->logger = new Logger( $channel );

		// Set the timezone used for the messages here
		// FIXME: Use system timezone if available
		Logger::setTimezone( new \DateTimeZone( $this->defaultTimezone ) );

		// Register a Slack handler
		$this->registerSlackFingersCrossedHandler( $channel, $activationLevel );

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
	 * @param string $channel
	 * @param int $activationLevel
	 */
	public static function setupExceptionLogger( string $channel = 'general', int $activationLevel = Logger::NOTICE )
	{
		if ( is_null( self::$instance ) )
		{
			self::$instance = new self( $channel, $activationLevel );
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
	 * @param string $channel
	 * @param int $activationLevel
	 */
	private function registerSlackFingersCrossedHandler( string $channel, int $activationLevel = Logger::NOTICE )
	{
		// Activate the handler after this threshold
		$activationStrategy = new ErrorLevelActivationStrategy( $activationLevel );

		// Setup the FingersCrossed handler which contains the SlackHandlerExtended
		$fingersCrossedHandler = new FingersCrossedHandler( function () use ( $channel ) {
			// The handler below is only called upon activation
			// to save the overhead of setting up a WebSocket to Slack
			// until the actual error is triggered
			$slackHandler = new SlackHandlerExtended( $channel );

			// Add extra information to the log
			// e.g. {"url":"/monolog/test.php","ip":"192.168.40.1","http_method":"GET","server":"api.example.local","referrer":null}
			$slackHandler->pushProcessor( new \Monolog\Processor\WebProcessor() );

			// Return the new handler
			return $slackHandler;
		},
			$activationStrategy
		);

		// Add this handler
		$this->logger->pushHandler( $fingersCrossedHandler );
	}
}