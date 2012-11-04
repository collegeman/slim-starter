<?php
define('GALIB', __FILE__);
define('GALIB_DIR', realpath('../'.dirname(GA_LIB)));

require(GALIB_DIR.'/config.php');
require(GALIB_DIR.'/Slim/Slim.php');
require(GALIB_DIR.'/lib/google/Google_Client.php');
require(GALIB_DIR.'/lib/google/contrib/Google_AnalyticsService.php');

function ga_sum_dimension($response, $dimension, $label, $metric) {
  $value = 0;
  $dcount = count(explode(',', $response['query']['dimensions']));
  $mi = $dcount + $metric;
  foreach($response['rows'] as $row) {
    $d = $row[$dimension];
    if (strpos($label, '%')) {
      $l = str_replace('%', '', $label);
      if (strpos($d, $l) !== false) {
        $value += $row[$mi];
      }
    } else if ($d === $label) {
      $value += $row[$mi];
    }
  }
  return $value;
}

function ga_get_profile_data($profile_id, $start, $end) {
  global $service;

  $response = $service->data_ga->get(
    'ga:'.$profile_id, 
    $start,
    $end, 
    'ga:pageviews,ga:visitors,ga:organicSearches',
    array(
      'dimensions' => 'ga:socialNetwork,ga:source,ga:week'
    )
  );

  return array(
    'start' => $response['query']['start-date'],
    'end' => $response['query']['end-date'],
    'pageviews' => $response['totalsForAllResults']['ga:pageviews'],
    'visitors' => $response['totalsForAllResults']['ga:visitors'],
    'pageviewbysource' => array(
      'facebook' => ga_sum_dimension($response, 0, 'Facebook', 1),
      'twitter' => ga_sum_dimension($response, 0, 'Twitter', 1),
      'pinterest' => ga_sum_dimension($response, 0, 'Pinterest', 1),
      'google' => ga_sum_dimension($response, 1, 'google%', 0)
    )
  );  
}

function ga_get_profile($profile_id) {
  global $service;


}

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim(array(
  'templates.path' => GALIB_DIR.'/templates'
));

$client = new Google_Client();
$client->setApplicationName('GA Lib');
$client->setClientId(GA_CLIENT_ID);
$client->setClientSecret(GA_CLIENT_SECRET);
$client->setRedirectUri('http://'.$_SERVER['HTTP_HOST'].'/auth');
$service = new Google_AnalyticsService($client);
  
@session_start();

if (!empty($_SESSION['token'])) {  
  $client->setAccessToken($_SESSION['token']);
} else {
  $service = false;
}

$app->get('/', function() use ($app, $service) {
  $app->render('index.php');
});

$app->get('/api/accounts', function() use ($app, $service) {
  $app->response()->header('Content-Type', 'application/json');
  if ($service) {
    $response = $service->management_accounts->listManagementAccounts();
    $accounts = $response['items'];
    usort($accounts, function($a, $b) {
      return strcasecmp($a['name'], $b['name']);
    });
    echo json_encode($accounts);
  }
});

$app->get('/api/profiles/:account_id', function($account_id = null) use ($app, $service) {
  $app->response()->header('Content-Type', 'application/json');
  if ($service && $account_id) {
    $response = $service->management_profiles->listManagementProfiles($account_id, '~all');
    $profiles = $response['items'];
    usort($profiles, function($a, $b) {
      return strcasecmp($a['name'], $b['name']);
    });
    echo json_encode($profiles);
  }
});

$app->get('/api/profile/:profile_id', function($profile_id = null) use ($app, $service) {
  $app->response()->header('Content-Type', 'application/json');
  if ($service && $profile_id) {

    $data = array();
    
    // try {
      
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

      $data['cached'] = false;
      $data['id'] = $profile_id;

      echo json_encode($data);

    // } catch (Exception $e) {
    //   $app->response()->status(400);
    //   echo json_encode(array('error' => $e->getMessage()));
    // }

  }
});

$app->get('/login', function() use ($app, $client) {
  $app->response()->redirect( $client->createAuthUrl() );
});

$app->get('/logout', function() use ($app) {
  session_destroy();
  $app->response()->redirect('/');
});

$app->get('/auth', function() use ($app, $client) {
  if ($app->request()->get('code')) {
    $client->authenticate();
    $_SESSION['token'] = $client->getAccessToken();
  }
  return $app->response()->redirect('/');
});

$app->run();
