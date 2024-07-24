<?php
  //phpinfo();
  //die(var_dump($_GET));



  // if ($path = (basename(getcwd()) == 'public') chdir('..');
//APP_PATH == dirname(APP_PUBLIC) 
if ($path = (basename(getcwd()) == 'public') ? (is_file('../config/config.php') ? '../config/config.php' : 'config.php') :
  (is_file('config.php') ? 'config.php' : APP_PATH . APP_BASE['config'] . 'config.php' ))

    //? (is_file('../config.php') ? '../config.php' : 'config.php')
    //: (is_file('config.php') ? 'config.php' : (is_file('config/config.php') ? 'config/config.php' : null)))

  require_once $path;
else
  die(var_dump("$path was not found. file=config.php"));


if (APP_DEBUG) {
  defined('PHP_ZTS') and $errors['PHP_ZTS'] = "PHP was built with ZTS enabled.\n";
  defined('PHP_DEBUG') and $errors['PHP_DEBUG'] = "PHP was built with DEBUG enabled.\n";
  defined('PHP_VERSION') and $errors['PHP_VERSION'] = "PHP Version: " . PHP_VERSION . "\n";
  defined('PHP_OS') and $errors['PHP_OS'] = "PHP_OS: " . PHP_OS . "\n";
  defined('PHP_SAPI') and $errors['PHP_SAPI'] = "PHP_SAPI: " . PHP_SAPI . "\n";
  defined('PHP_BINARY') and $errors['PHP_BINARY'] = "PHP_BINARY: " . PHP_BINARY . "\n";
  defined('PHP_BINDIR') and $errors['PHP_BINDIR'] = "PHP_BINDIR: " . PHP_BINDIR . "\n";
  defined('PHP_CONFIG_FILE_PATH') and $errors['PHP_CONFIG_FILE_PATH'] = "PHP_CONFIG_FILE_PATH: " . PHP_CONFIG_FILE_PATH . "\n";
  defined('PHP_CONFIG_FILE_SCAN_DIR') and $errors['PHP_CONFIG_FILE_SCAN_DIR'] = "PHP_CONFIG_FILE_SCAN_DIR: " . PHP_CONFIG_FILE_SCAN_DIR . "\n";
  defined('PHP_SHLIB_SUFFIX') and $errors['PHP_SHLIB_SUFFIX'] = "PHP_SHLIB_SUFFIX: " . PHP_SHLIB_SUFFIX . "\n";
  defined('PHP_EOL') and $errors['PHP_EOL'] = "PHP_EOL: " . PHP_EOL . "\n";
  defined('PHP_INT_MIN') and $errors['PHP_INT_MIN'] = "PHP_INT_MIN: " . PHP_INT_MIN . "\n";
  defined('PHP_INT_MAX') and $errors['PHP_INT_MAX'] = "PHP_INT_MAX: " . PHP_INT_MAX . "\n";
  defined('PHP_FLOAT_DIG') and $errors['PHP_FLOAT_DIG'] = "PHP_FLOAT_DIG: " . PHP_FLOAT_DIG . "\n";
  defined('PHP_FLOAT_EPSILON') and $errors['PHP_FLOAT_EPSILON'] = "PHP_FLOAT_EPSILON: " . PHP_FLOAT_EPSILON . "\n";
  defined('PHP_FLOAT_MIN') and $errors['PHP_FLOAT_MIN'] = "PHP_FLOAT_MIN: " . PHP_FLOAT_MIN . "\n";
  defined('PHP_FLOAT_MAX') and $errors['PHP_FLOAT_MAX'] = "PHP_FLOAT_MAX: " . PHP_FLOAT_MAX . "\n";
}



  require_once realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR  . 'config' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'class.sockets.php');
//dd(get_defined_constants(true)['user']);

//$path = "/path/to/your/logfile.log"; // Replace with your actual log file path
if (is_readable($path = APP_PATH . APP_ROOT . $_ENV['ERROR_LOG_FILE']) && filesize($path) >= 0 ) {
  $errors['ERROR_PATH'] = $path . "\n";
  //if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
  //  $errors['ERROR_LOG'] = shell_exec("powershell Get-Content -Tail 10 $path") . "\n";
  //} else {
  //  $errors['ERROR_LOG'] = shell_exec(APP_SUDO . " tail $path") . "\n";
  //}

  $shellOutput = shell_exec(stripos(PHP_OS, 'LIN') === 0 ? "tail $path" : "powershell Get-Content -Tail 10 $path");
    
  $pattern = '/^\[\d+-\w*-\d*\s+\d+:\d+:\d+\s+\w*\/\w+\]\s+Shutdown\s+constructor\s+called.$/';
  $matches = [];
    
  // Parse the shell output line by line
  foreach (explode("\n", $shellOutput) as $line) {
    if ($line == '') continue;
    elseif (preg_match($pattern, $line)) {
      $matches[] = $line;
    } else {
      // If the line doesn't match the pattern, reset the matches array
      $log_matches[] = $line;
    }
  }
    
  $log_matches[] = end($matches) . ' [x' . count($matches) . ']';

  if (count($matches) >= 10 && count($log_matches) <= 2) unlink($path) and $errors['ERROR_PATH'] = (!is_file($path) ? trim($errors['ERROR_PATH']) . ' was completely removed.' : 'Error_log failed to be removed completely.') . "\n"; // header('Location: ' . APP_WWW);

  $errors['ERROR_LOG'] = implode("\n", $log_matches) . "\n\n";

  if (isset($_GET[$error_log = basename($path)]) && $_GET[$error_log] == 'unlink') 
    unlink($path);
}

$previousFilename = '';

!isset($_GET['app']) || $_GET['app'] != 'git' ? 
  (APP_SELF == APP_PUBLIC ? (!defined('APP_ROOT') || empty(APP_ROOT) ?: $dirs[] = APP_PATH . APP_BASE['config'] . 'git.php') :
    $dirs[] = APP_PATH . APP_BASE['config'] . 'git.php') :
  $dirs[] = APP_PATH . APP_BASE['config'] . 'git.php';

!isset($_GET['app']) || $_GET['app'] != 'composer' ? (APP_SELF == APP_PUBLIC ? (!defined('APP_ROOT') || empty(APP_ROOT) ? (!is_file($autoload = APP_PATH . APP_BASE['vendor'] . 'autoload.php') ?: $dirs[] = $autoload) : $dirs[] = APP_PATH . APP_ROOT . APP_BASE['vendor'] . 'autoload.php') : $dirs[] = APP_PATH . APP_BASE['config'] . 'composer.php') :
  $dirs[] = APP_PATH . APP_BASE['config'] . 'composer.php';

$dirs[] = APP_PATH . APP_BASE['config'] . 'npm.php';

  //$dirs = [
    //0 => APP_PATH . APP_BASE['config'] . 'git.php',
    //1 => APP_PATH . APP_BASE['config'] . 'composer.php',
    //2 => APP_PATH . APP_BASE['config'] . 'npm.php',
    //2 => APP_PATH . 'composer-setup.php',
    //1 => APP_PATH . 'config.php',
    //1 => APP_PATH . 'constants.php',
    //2 => APP_PATH . 'functions.php',
    //4 => APP_PATH . APP_BASE['vendor'] . 'autoload.php',
  //]; // array_filter(glob(__DIR__ . DIRECTORY_SEPARATOR . '*.php'), 'is_file');

  usort($dirs, function ($a, $b) {
      // Define your sorting criteria here
    //if (basename($a) === 'composer-setup.php')
    //    return 1; // $a comes after $b
    //elseif (basename($b) === 'composer-setup.php')
    //    return -1; // $a comes before $b/
/*
    //elseif (basename($a) === 'composer.php')
    //    return -1; // $a comes after $b
    //elseif (basename($b) === 'composer.php')
    //    return 1; // $a comes before $b
*/
    if (basename($a) === 'git.php')
        return -1; // $a comes after $b
    elseif (basename($b) === 'git.php')
        return 1; // $a comes before $b
    else 
        return strcmp(basename($a), basename($b)); // Compare other filenames alphabetically
  });

  foreach ($dirs as $includeFile) {
    //dd('Trying file: ' . basename($includeFile), false);
    $path = dirname($includeFile);

    if (in_array($includeFile, get_required_files())) continue; // $includeFile == __FILE__

    if (basename($includeFile) === 'composer-setup.php') continue;

    if (!file_exists($includeFile)) {
      error_log("Failed to load a necessary file: " . $includeFile . PHP_EOL);
      break;
    }

    $currentFilename = substr(basename($includeFile), 0, -4);
    
    // $pattern = '/^' . preg_quote($previousFilename, '/')  . /*_[a-zA-Z0-9-]*/'(_\.+)?\.php$/'; // preg_match($pattern, $currentFilename)

    if (!empty($previousFilename) && strpos($currentFilename, $previousFilename) !== false) continue;

    // dd('file:'.$currentFilename,false);

    require_once $includeFile;

    $previousFilename = $currentFilename;
  }

/** Loading Time: 4.65s **/

  // dd(null, true);

  //dd($_SERVER); php_self, script_name, request_uri /folder/

  // dd(getenv('PATH'));

  switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':    
      //dd(get_required_files(), false);
      //dd($_ENV);
      if (isset($_POST['environment'])) {
        switch ($_POST['environment']) {
          case 'product':
            define('APP_ENV', 'production');
            break;
          case 'develop':
            define('APP_ENV', 'development');
            break;
          case 'math':
            define('APP_ENV', 'math');
            break;
          default:
            define('APP_ENV', 'production');
            break;
        }
        $_ENV['APP_ENV'] = APP_ENV;
        Shutdown::setEnabled(false)->setShutdownMessage(function() {
          return header('Location: ' . APP_WWW); // -wow
        })->shutdown();
      }

    break;
  case 'GET':
      if (isset($_ENV['APP_ENV']) && !empty($_ENV)) define('APP_ENV', $_ENV['APP_ENV']);
      //if (!empty($_GET['path']) && !isset($_GET['app'])) !!infinite loop
      //  exit(header('Location: ' . APP_WWW . $_GET['path']));
// http://localhost/?app=composer&path=vendor

      if (isset($_GET['hide']) && $_GET['hide'] == 'update-notice') {
        $_ENV['HIDE_UPDATE_NOTICE'] = true; // var_export(true, true); // true
        Shutdown::setEnabled(true)->setShutdownMessage(function() {
          return header('Location: ' . APP_WWW); // -wow
        })->shutdown();
      }
        

      if (isset($_GET['category']) && !empty($_GET['category'])) {
        
        if ($_GET['category'] == 'projects')
          exit(header('Location: ' . APP_WWW . '?project='));
        if ($_GET['category'] == 'vendor')
          exit(header('Location: ' . APP_WWW . '?app=composer&path=' . $_GET['category']));
        //if ($_GET['category'] == 'applications')
        //  exit(header('Location: ' . APP_WWW . '?path=' . $_GET['category']));
        exit(header('Location: ' . APP_WWW . '?' . $_GET['category']));
      } elseif (isset($_GET['category']) && empty($_GET['category']))
        exit(header('Location: ' . APP_WWW . '?path'));
        
      if (isset($_GET['path']) && !is_dir(APP_PATH . APP_ROOT)) {
        //dd(APP_PATH . APP_ROOT . ' test');
        die(header('Location: ' . APP_URL_BASE));
      }
      break;
  }
  //dd(APP_PATH . APP_ROOT);
  /*
  switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':    
      //dd($_POST);
  
      $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS | FILTER_SANITIZE_ENCODED, FILTER_REQUIRE_ARRAY ) ?? [];
  
      break;
    case 'GET':
      $_GET = filter_input_array(INPUT_GET, FILTER_SANITIZE_FULL_SPECIAL_CHARS | FILTER_SANITIZE_ENCODED, FILTER_REQUIRE_ARRAY ) ?? [];
      break;
    default:
      foreach(${'_'.$_SERVER['REQUEST_METHOD']} as $key => $value) {
        ${'_'.$_SERVER['REQUEST_METHOD']}[$key] = filter_var($value, (
          is_string($value) ? FILTER_SANITIZE_STRING : (
            is_int($value) ? FILTER_VALIDATE_INT : FILTER_SANITIZE_STRING)
          )
        );
      }
      /*$request_method = '_'.$_SERVER['REQUEST_METHOD'];
      foreach($$request_method as $key => $value) {
        $$request_method[$key] = filter_var($value, (
          is_string($value) ? FILTER_SANITIZE_STRING : (
            is_int($value) ? FILTER_VALIDATE_INT : FILTER_SANITIZE_STRING
          )
        ));
      }*/
  //}
  /**/
 
 if (defined('APP_ENV') and APP_ENV == 'production' )
   require_once 'idx.product.php';
 elseif (defined('APP_ENV') and APP_ENV == 'development')
   require_once 'idx.develop.php';
 elseif (defined('APP_ENV') and APP_ENV == 'math')
   require_once 'idx.math.php';
 else {
   !defined('APP_ENV') and define('APP_ENV', 'production');
   require_once 'idx.product.php';
 }
