<?php
// Bootstrap Slim:
define('APP', __FILE__);
define('ROOT', realpath(dirname(__FILE__).'/../'));
require(ROOT.'/common/bootstrap.php');

// Load any additional libraries:
// require_once(ROOT.'/common/google/Google_Client.php');
// require_once(ROOT.'/common/google/contrib/Google_Oauth2Service.php');
// require_once(ROOT.'/common/google.php');

// Load dispatcher, which will run the app:
require(ROOT.'/dispatcher.php');