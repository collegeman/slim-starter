<?php
// Bootstrap slim:
define('APP', __FILE__);
define('ROOT', realpath(dirname(__FILE__).'/../'));
require(ROOT.'/common/bootstrap.php');

/**
 * Middleware that handles encoding API result to JSON
 * and setting all proper headers.
 */
class ApiMiddleware extends \Slim\Middleware {
  private static $result;
  static function result($result) {
    self::$result = $result;
  }

  private static $disabled = false;
  static function disable() {
    self::$disabled = true;
  }

  function call() {
    $app = $this->app;
    $res = $app->response();

    
    try {
      $this->next->call();
      if (self::$disabled) {
        return;
      }
      if ($res->status() !== 200) {
        return;
      }
      $res['Content-Type'] = 'application/json';
      $result = self::prepForEncoding(self::$result);
      $res->status(200);
    } catch (Exception $e) {
      $res['Content-Type'] = 'application/json';
      $result = array('error' => $e->getMessage());
      $res->status(400);
    }

    // encode
    $res->write(json_encode($result));
  }

  private function prepForEncoding($r) {
    if ($r instanceof Model) {
      return self::$result->as_array();
    } else if (is_array($r)) {
      $array = array();
      foreach($r as $value) {
        $array[] = self::as_array($value);
      }
      return $array;
    } else {
      return $r;
    }
  }
}

$app->add(new ApiMiddleware());

// Setup generic model access: GET, POST, PUT and DELETE
$app->map('/api/:model(/:id(/:function))?', function($model, $id = false, $function = false) use ($app) {
  $req = $app->request();

  if ($id !== false && !is_numeric($id)) {
    $function = $id;
    $id = false;
  }

  if (!$id && !$function) {
    return $app->response()->redirect('/api#'.strtolower($model));
  }
  
  if (!class_exists($model)) {
    throw new Exception("Model does not exist");
  }
      
  if ($id !== false) {
    $instance = Model::factory(ucwords($model))->where('id', $id)->find_one();
    if (!$instance) {
      throw new Exception("Model instance not found for [{$id}]");
    }
  } else {
    $instance = Model::factory(ucwords($model))->create();
  }
  
  if ($req->isGet()) {
    if (!$function) {
      return ApiMiddleware::result( $instance );  
    } 
  }

  if ($req->isPut() || $req->isPost()) {
    if (!$function) {
      return ApiMiddleware::result( call_user_func_array(array($instance, 'apply'), array()) );
    }
  }

  if ($req->isDelete()) {
    if ($id !== false) {
      return ApiMiddleware::result( array('success' => $instance->delete() ) );
    }
  }

  if ($function) {
    $callable = array($instance, '_'.$function);
    if (!is_callable($callable)) {
      throw new Exception("Invalid method {$model}::_{$function}");
    }
    
    return ApiMiddleware::result( call_user_func_array($callable, array(json_decode(file_get_contents('php://input')), $app)) );
  }

})->via('GET', 'POST', 'PUT', 'DELETE');

// Setup documentation URL
$app->get('/api', function() use ($app) {
  ApiMiddleware::disable();
  $app->render('api.php', array('pageTitle' => 'API Documentation'));
});

$app->run();