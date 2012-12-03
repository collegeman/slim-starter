<?php
/* Handy /info endpoint for running phpinfo()
$app->get('/info', function() {
  phpinfo();
});
/* @end /info */

/* Handy /server endpoint for running /server
$app->get('/server', function() {
  echo '<pre>';
  print_r($_SERVER);
});
/* @end /server */

$app->get('/', function() use ($app, $gaservice) {
  $app->render('index.php', array(
    'pageTitle' => 'Untitled'
  ));
});

$app->run();