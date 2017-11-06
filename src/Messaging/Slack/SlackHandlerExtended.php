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
use Monolog\Handler\MissingExtensionException;

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
	 * @param  string $token Slack API token
	 * @param  string $envVariable Environment variable to hold the slackbot token
	 * @param  bool $useAttachment Whether the message should be added to Slack as attachment (plain text otherwise)
	 * @param  bool $bubble Whether the messages that are handled can bubble up the stack or not
	 * @param  bool $useShortAttachment Whether the the context/extra messages added to Slack as attachments are in a short style
	 * @param  bool $includeContextAndExtra Whether the attachment should include context and extra data
	 *
	 * @throws MissingExtensionException If no OpenSSL PHP extension configured
	 */
	public function __construct(
		$channel,
		$token = null,
		$envVariable = null,
		$useAttachment = false,
		$bubble = true,
		$useShortAttachment = false,
		$includeContextAndExtra = false
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
			$useAttachment,
			null,
			Logger::DEBUG,
			$bubble,
			$useShortAttachment,
			$includeContextAndExtra
		);

		// Set the Slack formatter now, here
		parent::setFormatter( $this->slackFormatter() );

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
	 * @return LineFormatter
	 */
	private function slackFormatter()
	{
		// The default output format is "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n"
		// $output = "%datetime% > %level_name% > %message% %context% %extra%\n";

		// Our messages will have an icon and the bot name will be the log level
		$output = "[%datetime%] %message% %context% %extra%\n";

		// Create the formatter
		return new LineFormatter( $output, '', false, true ); // Ignore blank extras);
	}
}