<?php
/**
 * Messaging - SlackHandlerExtended.php
 * Created by Eric Draken
 * Date: 2017/2/19
 * Copyright (c) 2017
 */

namespace Draken\Messaging\Slack;

use Monolog\Logger;
use Monolog\Handler\SlackHandler;
use Monolog\Formatter\LineFormatter;

/**
 * Sends notifications through Slack API
 * using all 8 levels and unique icons
 * using just one socket connection
 */
class SlackHandlerExtended extends SlackHandler
{
	// Set all the timeouts to this value
	// to prevent blocking for too long
	private $timeoutSeconds = 4;

	// Map the SlackBot icons to the log level
	private $slackIconToLevelMap = [
		Logger::DEBUG     => ':beetle:',
		Logger::INFO      => ':bulb:',
		Logger::NOTICE    => ':speech_balloon:',
		Logger::WARNING   => ':warning:',
		Logger::ERROR     => ':heavy_exclamation_mark:',
		Logger::CRITICAL  => ':bangbang:',
		Logger::ALERT     => ':anger:',
		Logger::EMERGENCY => ':skull:'
	];

	/**
	 * @param  string $channel Slack channel (encoded ID or name)
	 * @param  string|null $token Slack API token
	 * @param  string|null $envVariable Environment variable to hold the slackbot token
	 * @param  string|null $messageFormat Message format of the Slack message
	 * @param  string|null $dateFormat The format of the timestamp: one supported by DateTime::format
	 * @param  bool $includeStackTrace Include a stack trace or not
	 */
	public function __construct(
		string $channel,
		string $token = null,
		string $envVariable = null,
		string $messageFormat = null,
		string $dateFormat = null,
		bool $includeStackTrace = false
	) {
		// Get the slackbot token from the ENV variable
		$token ?: $token = getenv( $envVariable ? $envVariable : 'SLACKBOT_TOKEN' );
		if ( ! $token )
		{
			throw new \RuntimeException( "Slackbot token could not be found, so no Slack log messages will be sent" );
		}

		// Call the parent constructor with no regard for the icon
		// and the lowest log level (both null) as they will be set
		// in prepareContentData() below
		parent::__construct(
			$token,
			$channel,
			null,
			false,
			null,
			Logger::DEBUG,
			true,
			false,
			false
		);

		$formatter = $this->slackFormatter( $messageFormat, $dateFormat );

		// Optional stack trace
		$formatter->includeStacktraces( $includeStackTrace );

		// Set the Slack formatter now, here
		parent::setFormatter( $formatter );

		// Set a short timeout to prevent blocking for too long
		self::setTimeout( $this->timeoutSeconds );
		self::setWritingTimeout( $this->timeoutSeconds );
		self::setConnectionTimeout( $this->timeoutSeconds );
	}

	/**
	 * Prepares content data
	 *
	 * @param  array $record
	 *
	 * @return array
	 */
	protected function prepareContentData( $record )
	{
		// Process the record normally is with the original Slack handler
		$dataArray = parent::prepareContentData( $record );

		// Set the username to the log level (e.g. Debug, Warning)
		$dataArray['username'] = ucfirst( strtolower( $record['level_name'] ) );

		// Set the emoji based on the log level
		$level                   = (int) $record['level'];
		$dataArray['icon_emoji'] = $this->slackIconToLevelMap[ $level ];

		return $dataArray;
	}

	/**
	 * Create a formatter for Slack
	 *
	 * @param string|null $messageFormat
	 * @param string|null $dateFormat
	 *
	 * @return LineFormatter
	 */
	private function slackFormatter( string $messageFormat = null, string $dateFormat = null ): LineFormatter
	{
		// The default output format is "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n"
		// $format = "%datetime% > %level_name% > %message% %context% %extra%\n";

		// Our messages will have an icon and the bot name will be the log level
		$format = $messageFormat ?? "[%datetime%] %message% %context% %extra%";

		// Create the formatter
		return new LineFormatter( $format, $dateFormat, false, true );
	}
}