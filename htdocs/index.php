<?php
define('GA_LIB', __FILE__);
define('GA_LIB_DIR', realpath(dirname(__FILE__).'/../'));

// Local configuration only
@include(GA_LIB_DIR.'/config.php');

require(GA_LIB_DIR.'/Slim/Slim.php');

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim(array(
  'templates.path' => GA_LIB_DIR.'/templates',
  'log.enabled' => true
));  

$log = $app->getLog();
$log->setLevel(\Slim\Log::DEBUG);

require(GA_LIB_DIR.'/lib/config.php');
require(GA_LIB_DIR.'/lib/google/Google_Client.php');
require(GA_LIB_DIR.'/lib/google/contrib/Google_AnalyticsService.php');
require(GA_LIB_DIR.'/lib/google/contrib/Google_Oauth2Service.php');
require(GA_LIB_DIR.'/lib/memcached.php');
require(GA_LIB_DIR.'/lib/db.php');
require(GA_LIB_DIR.'/lib/session.php');
require(GA_LIB_DIR.'/lib/google.php');

require(GA_LIB_DIR.'/lib/dispatcher.php');