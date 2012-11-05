<?php
$client = new Google_Client();
$client->setApplicationName('GA Lib');
$client->setClientId(GA_LIB_CLIENT_ID);
$client->setClientSecret(GA_LIB_CLIENT_SECRET);
$client->setRedirectUri('http://'.$_SERVER['HTTP_HOST'].'/auth');
$gaservice = new Google_AnalyticsService($client);
$infoservice = new Google_Oauth2Service($client);

if ($user = has_session()) {  
  try {
    $client->setAccessToken($user->google_access_token);
  } catch (Google_AuthException $e) {
    $gaservice = false;
  }
} else {
  $gaservice = false;
}

function ga_sum_dimension($response, $dimension, $label, $metric) {
  $value = 0;
  if (!empty($response['rows'])) {
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
  }
  return $value;
}

function ga_get_profile_chart($profile_id, $start, $end, $metrics = 'ga:pageviews,ga:visitors', $dimensions = 'ga:date', $filters = null, $debug = false) {
  global $gaservice;

  $cache_key = md5("ga_get_profile_chart({$profile_id},{$start},{$end},{$metrics},{$dimensions},{$filters})");
  if (!$debug && ($cache = cached($cache_key))) {
    return $cache;
  }

  $opts = array(
    'dimensions' => $dimensions
  );
  if (!empty($filters)) {
    $opts['filters'] = $filters;
  }

  $response = $gaservice->data_ga->get(
    'ga:'.$profile_id, 
    $start,
    $end, 
    $metrics,
    $opts
  );

  $data = array(
    'start' => $response['query']['start-date'],
    'end' => $response['query']['end-date'],
    'totals' => $response['totalsForAllResults'],
    'metrics' => $metrics,
    'filters' => $filters,
    'dimensions' => $dimensions,
    'points' => $response['rows']
  );

  $data['cached'] = time();
  // TODO: use longer timeout for older data
  cache($cache_key, $data, 300);

  if ($debug) {
    $data['response'] = $response;
  } 

  return $data;
} 

function ga_get_profile_data($profile_id, $start, $end, $debug = false) {
  global $gaservice;

  $cache_key = md5("ga_get_profile_data({$profile_id},{$start},{$end})");
  if (!$debug && ($cache = cached($cache_key))) {
    return $cache;
  }

  $response = $gaservice->data_ga->get(
    'ga:'.$profile_id, 
    $start,
    $end, 
    'ga:pageviews,ga:visitors,ga:organicSearches',
    array(
      'dimensions' => 'ga:socialNetwork,ga:source'
    )
  );

  $data = array(
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

  $data['cached'] = time();
  cache($cache_key, $data, 300);  

  if ($debug) {
    $data['response'] = $response;
  }  

  return $data;
}

function ga_get_profile($profile_id) {
  global $gaservice;


}