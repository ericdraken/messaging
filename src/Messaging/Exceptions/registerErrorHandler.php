<?php
/**
 * Messaging - registerErrorHandler.php
 * Created by: Eric Draken
 * Date: 2017/11/6
 * Copyright (c) 2017
 */

// Composer autoload for Monolog
require_once __DIR__ . '/../../../vendor/autoload.php';

// Slack channel
$channel = apache_getenv('SLACKBOT_CHANNEL');
if (!$channel) {
	$channel = 'general';
}

ExceptionLogger::setupExceptionLogger( $channel, \Monolog\Logger::NOTICE );