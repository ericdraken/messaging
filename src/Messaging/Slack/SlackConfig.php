<?php
/**
 * Messaging - SlackConfig.php
 * Created by: Eric Draken
 * Date: 2017/11/8
 * Copyright (c) 2017
 */

namespace Draken\Messaging\Slack;

use Noodlehaus\Config;

class SlackConfig extends Config
{
	protected function getDefaults()
	{
		return [
			// Default timezone
			'timezone' => 'America/Vancouver',

			// This must be set in an ini file on by an environment variable
			'slackApiToken' => null,

			// Alternatively check for this environment variable
			'slackApiTokenEnvVarName' => 'SLACKBOT_TOKEN',

			// Channel to use when the channel cannot be found via environment variables
			'defaultChannel' => 'general',

			// Alternatively check for this environment variable
			'channelEnvVarName' => 'SLACK_EXCEPTION_CHANNEL',

			// Simple message format
			'messageFormat' => '[%datetime%] %message% %context% %extra%',

			// Date format
			'dateFormat' => 'Y-m-d H:i:s',

			// Include a stack trace
			'includeStackTrace' => false,

			// Additional processors
			'processors' => [

				// e.g. {"url":"/monolog/test.php","ip":"192.168.40.1","http_method":"GET","server":"api.example.local","referrer":null}
				//'\Monolog\Processor\WebProcessor',

				// e.g. {"memory_peak_usage":"2 MB"}
				// '\Monolog\Processor\MemoryPeakUsageProcessor'
			],
		];
	}
}