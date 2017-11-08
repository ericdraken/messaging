<?php
/**
 * Messaging - registerErrorHandler.php
 * Created by: Eric Draken
 * Date: 2017/11/6
 * Copyright (c) 2017
 */

// Composer autoload for Monolog
require_once __DIR__ . '/../../../vendor/autoload.php';

use Draken\Messaging\Exceptions\ExceptionLogger;
use Monolog\Logger;

$slackIni = __DIR__ . '/../../../ini/slackExceptionLogger.yaml';

// Register the logger for exception logging
ExceptionLogger::setupExceptionLogger( $slackIni, Logger::NOTICE );