<?php
define('GA_LIB', __FILE__);
define('GA_LIB_DIR', realpath(dirname(__FILE__).'/../'));

require(GA_LIB_DIR.'/config.php');
require(GA_LIB_DIR.'/Slim/Slim.php');
require(GA_LIB_DIR.'/lib/google/Google_Client.php');
require(GA_LIB_DIR.'/lib/google/contrib/Google_AnalyticsService.php');
require(GA_LIB_DIR.'/lib/google/contrib/Google_Oauth2Service.php');
require(GA_LIB_DIR.'/lib/memcached.php');
require(GA_LIB_DIR.'/lib/db.php');
require(GA_LIB_DIR.'/lib/session.php');
require(GA_LIB_DIR.'/lib/google.php');

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim(array(
  'templates.path' => GA_LIB_DIR.'/templates'
));
  
$app->get('/', function() use ($app, $gaservice) {
  if (!has_session()) {
    return $app->response()->redirect('/login');
  }
  $app->render('index.php');
});

$app->get('/api/accounts', function() use ($app, $gaservice) {
  $app->response()->header('Content-Type', 'application/json');
  if ($gaservice) {
    $response = $gaservice->management_accounts->listManagementAccounts();
    $accounts = $response['items'];
    usort($accounts, function($a, $b) {
      return strcasecmp($a['name'], $b['name']);
    });
    echo json_encode($accounts);
  }
});

$app->get('/api/profiles/:account_id', function($account_id = null) use ($app, $gaservice) {
  $app->response()->header('Content-Type', 'application/json');
  if ($gaservice && $account_id) {
    $response = $gaservice->management_profiles->listManagementProfiles($account_id, '~all');
    $profiles = $response['items'];
    usort($profiles, function($a, $b) {
      return strcasecmp($a['name'], $b['name']);
    });
    echo json_encode($profiles);
  }
});

$app->get('/api/profile/:profile_id/chart', function($profile_id = null) use ($app, $gaservice) {
  $app->response()->header('Content-Type', 'application/json');
  if ($gaservice && $profile_id) {
    $metrics = $app->request()->get('metrics');
    $dimensions = $app->request()->get('dimensions');
    $start = $app->request()->get('start');
    $end = $app->request()->get('end');
    $filters = $app->request()->get('filters');
    $data = ga_get_profile_chart(
      $profile_id, 
      $start ? $start : date('Y-m-d', strtotime('-1 month')),
      $end ? $end : date('Y-m-d'),
      $metrics ? $metrics : 'ga:pageviews,ga:visitors',
      $dimensions ? $dimensions : 'ga:week,ga:date',
      $filters ? $filters : false
    );
    echo json_encode($data);
  }
});

$app->get('/api/profile/:profile_id', function($profile_id = null) use ($app, $gaservice) {
  $app->response()->header('Content-Type', 'application/json');
  if ($gaservice && $profile_id) {

    $cache_key = "profile/{$profile_id}";
    if (!$app->request()->get('flush') && ( $cache = cached($cache_key) )) {
      echo json_encode($cache);
      return;
    }

    $data = array('id' => $profile_id);
    
    try {

      $data['today'] = ga_get_profile_data(
        $profile_id, 
        date('Y-m-d'), 
        date('Y-m-d')
      );

      $data['yesterday'] = ga_get_profile_data(
        $profile_id, 
        date('Y-m-d', strtotime('-2 days')), 
        date('Y-m-d', strtotime('yesterday'))
      );

      $data['twodaysago'] = ga_get_profile_data(
        $profile_id, 
        date('Y-m-d', strtotime('-3 days')), 
        date('Y-m-d', strtotime('-2 days'))
      );

      $data['today']['pageviewschange'] = ($data['today']['pageviews'] - $data['yesterday']['pageviews'])/$data['yesterday']['pageviews'];
      $data['yesterday']['pageviewschange'] = ($data['yesterday']['pageviews'] - $data['twodaysago']['pageviews'])/$data['twodaysago']['pageviews'];
      
      $data['lastthirty'] = ga_get_profile_data(
        $profile_id,
        date('Y-m-d', strtotime('-1 month')),
        date('Y-m-d')
      );

      $data['thismonth'] = ga_get_profile_data(
        $profile_id,
        date('Y-m-01'),
        date('Y-m-d')
      );
      
      $data['lastmonth'] = ga_get_profile_data(
        $profile_id,
        date('Y-m-01', strtotime('-1 month')),
        date('Y-m-d', strtotime('-1 month'))
      );
      
      $data['thismonthlastyear'] = ga_get_profile_data(
        $profile_id,
        date('Y-m-01', strtotime('-1 year')),
        date('Y-m-d', strtotime('-1 year'))
      );

      $data['thismonth']['pageviewschange'] = ($data['thismonth']['pageviews'] - $data['lastmonth']['pageviews'])/$data['lastmonth']['pageviews'];

      $data['thisyear'] = ga_get_profile_data(
        $profile_id,
        date('Y-01-01'),
        date('Y-m-d')
      );
      
      $data['lastyear'] = ga_get_profile_data(
        $profile_id,
        date('Y-01-01', strtotime('-1 year')),
        date('Y-m-d', strtotime('-1 year'))
      );
      
      $data['thisyear']['pageviewschange'] = ($data['thisyear']['pageviews'] - $data['lastyear']['pageviews'])/$data['lastyear']['pageviews'];

      $data['cached'] = time();
      
      cache($cache_key, $data, 300);

      echo json_encode($data);

    } catch (Exception $e) {
      $app->response()->status(400);
      echo json_encode(array('error' => $e->getMessage()));
    }

  }
});

$app->get('/login', function() use ($app, $client) {
  $app->response()->redirect( $client->createAuthUrl() );
});

$app->get('/logout', function() use ($app) {
  session_destroy();
  $app->response()->redirect('/');
});

$app->get('/auth', function() use ($app, $client, $infoservice) {
  if ($app->request()->get('code')) {
    if ($client->authenticate()) {
      login_with_google();
    }
  }
  return $app->response()->redirect('/');
});

$app->run();
