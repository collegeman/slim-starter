<?php
/**
 * Access a configuration property. Looks first for a constant,
 * finding none it looks for an environment value in $_SERVER,
 * finding none there it looks to Slim's $app->config, falling
 * back lastly on $default.
 * @param String Name of config. For searching constants and $_SERVER
 * environment values, $name is converted as follows: periods 
 * are converted to underscores,
 * and all content is converted to uppercase.
 * @param (optional) boolean The default value; defaults to false
 * @param (optional) Slim\Slim A Slim app to source config values from,
 * defaults to the global $app
 * @return A configuration value if found; otherwise, $default
 */
function config($name, $default = false, $use_app = null) {
  global $app;
  $a = is_null($use_app) ? $app : $use_app;
  $constant = strtoupper(str_replace('.', '_', $name));
  if (defined($constant)) {
    return constant($constant);
  } else if (isset($_SERVER[$constant])) {
    return $_SERVER[$constant];
  } else if (!empty($a)) {
    $value = $a->config($name);
    return empty($value) ? $default : $value;
  } else {
    return $default;
  }
}