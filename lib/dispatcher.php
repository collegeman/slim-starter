<?php
$app->get('/', function() use ($app, $gaservice) {
  if (!has_session()) {
    return $app->response()->redirect('/login');
  }
  $app->render('index.php');
});

$app->get('/api/accounts', function() use ($app, $gaservice) {
  $app->response()->header('Content-Type', 'application/json');
  if ($gaservice) {
    try {
      $accounts = ga_get_accounts();
      echo json_encode($accounts);
    } catch (Exception $e) {
      $app->response()->status(400);
      echo json_encode( array('error' => $e->getMessage()) );
    }
  }
});

$app->get('/api/profiles/:account_id', function($account_id = null) use ($app, $gaservice) {
  $app->response()->header('Content-Type', 'application/json');
  if ($gaservice && $account_id) {
    try {
      $profiles = ga_get_profiles($account_id);
      echo json_encode($profiles);
    } catch (Exception $e) {
      $app->response()->status(400);
      echo json_encode( array('error' => $e->getMessage()) );
    }
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
    if (!$app->request()->get('flush') && ( $cache = memget($cache_key) )) {
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
      
      memset($cache_key, $data, 300);

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