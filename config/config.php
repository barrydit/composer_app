<?php
declare(strict_types=1); // First Line Only!

date_default_timezone_set('America/Vancouver');

$errors = []; // (object)

// Custom error handler
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    global $errors;
    !defined('APP_ERROR') and define('APP_ERROR', true); // $hasErrors = true;
    !defined('APP_DEBUG') and define('APP_DEBUG', APP_ERROR);

    foreach([
        E_ERROR => 'Error',
        E_WARNING => 'Warning',
        E_PARSE => 'Parse Error',
        E_NOTICE => 'Notice',
        E_CORE_ERROR => 'Core Error',
        E_CORE_WARNING => 'Core Warning',
        E_COMPILE_ERROR => 'Compile Error',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_ERROR => 'User Error',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        E_STRICT => 'Strict Notice',
        E_RECOVERABLE_ERROR => 'Recoverable Error',
        E_DEPRECATED => 'Deprecated',
        E_USER_DEPRECATED => 'User Deprecated',
    ] as $key => $value) {
        if ($errno == $key) {
            $errors[$key] = $key . ' => ' . $value . "\n";
            $errors[] = $value . ': ' . $errstr . ' in ' . $errfile . ' on line ' . $errline . "\n";
            break;
        }
    }
    return false;
}

// Set the custom error handler
set_error_handler("customErrorHandler");

!defined('APP_ERROR') and define('APP_ERROR', false);
!defined('APP_DEBUG') and define('APP_DEBUG', isset($_GET['debug']) ? TRUE : FALSE);

if (APP_DEBUG || APP_ERROR) {
    $errors['APP_DEBUG'] = 'Debugging is enabled.';
    $errors['APP_ERROR'] = 'Error handling is enabled.';
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL/*E_STRICT |*/);
} else {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL/*E_STRICT |*/);
}

ini_set('error_log', is_dir($path = dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'config') ? dirname($path, 1) . DIRECTORY_SEPARATOR . 'error_log' : 'error_log');
ini_set('log_errors', 'true');

ini_set('xdebug.debug', '0'); // remote_enable
ini_set('xdebug.mode', 'develop'); // default_enable mode=develop,coverage,debug,gcstats,profile,trace
//ini_set('xdebug.mode', 'profile'); // profiler_enable

putenv("XDEBUG_MODE=off");

// Enable output buffering
ini_set('output_buffering', 'On');

ini_set("include_path", "src"); // PATH_SEPARATOR ;:

if (count(get_included_files()) == ((version_compare(PHP_VERSION, '5.0.0', '>=')) ? 1:0 )):
  exit('Direct access is not allowed.');
endif;

if (isset($_ENV['SHELL']['EXPR_DOMAIN']) && !defined('DOMAIN_EXPR'))
  define('DOMAIN_EXPR', $_ENV['SHELL']['EXPR_DOMAIN']); // const DOMAIN_EXPR = 'string only/non-block/ternary';
elseif(!defined('DOMAIN_EXPR'))
  define('DOMAIN_EXPR', '/(?:[a-z]+\:\/\/)?(?:[a-z0-9\-]+\.)+[a-z]{2,6}(?:\/\S*)?/i'); // /(?:\.(?:([-a-z0-9]+){1,}?)?)?\.[a-z]{2,6}$/';

if (isset($_ENV['SHELL']['PHP_EXEC']) && !defined('PHP_EXEC'))
  define('PHP_EXEC', $_ENV['SHELL']['PHP_EXEC'] ?? '/usr/bin/php'); // const DOMAIN_EXPR = 'string only/non-block/ternary';
// /(?:\.(?:([-a-z0-9]+){1,}?)?)?\.[a-z]{2,6}$/';

//die(var_dump($_SERVER['PHP_SELF'] . DIRECTORY_SEPARATOR . basename($_SERVER['PHP_SELF'])));

//$path = realpath((basename(__DIR__) != 'config' ? NULL : __DIR__ . DIRECTORY_SEPARATOR ) . 'functions.php');

// (basename(__DIR__) != 'config' ?


//!is_file( dirname($_SERVER['PHP_SELF']) . basename($_SERVER['PHP_SELF']) ?? __FILE__) // (!empty(get_included_files()) ? get_included_files()[0] : __FILE__)


//if (APP_ROOT != '') {}

// Retrieve the latest commit SHA of the main branch from the remote repository

// Directory of this script

is_file($path = __DIR__ . DIRECTORY_SEPARATOR . 'functions.php') ? 
  $paths[] = $path : 
    (is_file($path = 'config/functions.php') ? 
      $paths[] = $path :
      $paths[] = 'functions.php') or die(var_dump("$path was not found. file=$path"));

//$paths[] = __DIR__ . DIRECTORY_SEPARATOR . 'constants.php'; //require('constants.php'); 

while ($path = array_shift($paths)) {
  if (is_file($path = realpath($path))) require_once $path;
  else die(var_dump(basename($path) . ' was not found. file=' . $path));
}

(!function_exists('dd'))
  and $errors['FUNCTIONS'] = 'functions.php failed to load. Therefore function dd() does not exist (yet).';

if (!empty($_GET['client']) || !empty($_GET['domain'])) {
  $path = /*'../../'.*/ 'clientele/' . $_GET['client'] . '/';
  $dirs = array_filter(glob(dirname(__DIR__) . '/' . $path . '*'), 'is_dir');

  if (count($dirs) == 1) {
    foreach($dirs as $dir) {
      $dirs[0] = $dirs[array_key_first($dirs)];
      if (preg_match(DOMAIN_EXPR, strtolower(basename($dirs[0])))) {
        $_GET['domain'] = basename($dirs[0]);
        break;
      } else {
        unset($dirs[array_key_first($dirs)]);
        continue;
      }
    }
  }

  $dirs = array_filter(glob(dirname(__DIR__) . '/' . $path . '*'), 'is_dir');

  if (!empty($_GET['domain']))
    foreach($dirs as $key => $dir) {
      if (basename($dir) == $_GET['domain']) {
        //if (is_dir($dirs[$key].'/public/')) $path .= basename($dirs[$key]).'/public/';
        $path .= basename($dirs[$key]) . DIRECTORY_SEPARATOR;
        break;
      }
    }
  else if (!isset($_GET['domain']) && count($dirs) >= 1) {
    if (preg_match(DOMAIN_EXPR, strtolower(basename(array_values($dirs)[0])))) {
      $_GET['domain'] = basename(array_values($dirs)[0]);
      $path .= basename(array_values($dirs)[0]) . DIRECTORY_SEPARATOR;
    } else {
      $path .= ($_GET['domain'] = basename(array_values($dirs)[0])) . DIRECTORY_SEPARATOR;
    }
  //die(var_dump($path));
  }
  if (is_dir(APP_PATH . $path)) {
    (defined('APP_CLIENT') ?: define('APP_CLIENT', new clientOrProj($path)));

  }
} else if (!empty($_GET['project']) && is_dir(APP_PATH . 'projects/' . $_GET['project'])) {
  $path = /*'../../'.*/ 'projects/' . $_GET['project'] . '/';
  //$dirs = array_filter(glob(dirname(__DIR__) . '/' . $path . '*'), 'is_dir');
/*
  if (count($dirs) == 1) {
    foreach($dirs as $dir) {
      $dirs[0] = $dirs[array_key_first($dirs)];
      if (preg_match(DOMAIN_EXPR, strtolower(basename($dirs[0])))) {
        $_GET['domain'] = basename($dirs[0]);
        break;
      } else {
        unset($dirs[array_key_first($dirs)]);
        continue;
      }
    }
  }*/
}
unset($dirs);
// else { if (isset($_GET['path']) && is_dir(APP_PATH . $_GET['path'])) $path = $_GET['path']; }

!defined('APP_ROOT') and define('APP_ROOT', $path = realpath(APP_PATH . $path) ? $path : null); // dirname(APP_SELF, (basename(getcwd()) != 'public' ?: 2))

//else dd('test');

// dd(getenv('PATH') . ' -> ' . PATH_SEPARATOR);   

!defined('APP_CONFIG') and define('APP_CONFIG',  str_replace(APP_PATH, '', basename(dirname(__FILE__))) == 'config' ? __FILE__ : __FILE__);

//$errors->{'CONFIG'} = 'OK';

$ob_content = NULL;
//var_dump(dirname(APP_SELF) . ' == ' . __DIR__);
//dd(APP_PATH  . '  ' .  __DIR__);

/*
if (!realpath($path = APP_PATH . 'CHANGELOG.md')) {
  if (!is_file($path))
    if (@touch($path))
      file_put_contents($path, <<<END
CHANGELOG
END
);
}

if (!realpath($path = APP_PATH . 'CONTRIBUTING.md')) {
  if (!is_file($path))
    if (@touch($path))
      file_put_contents($path, <<<END
CONTRIBUTING
END
);
}

if (!realpath($path = APP_PATH . 'LICENSE')) {
  if (!is_file($path))
    if (@touch($path))
      file_put_contents($path, <<<END
LICENSE
END
);
}

if (!realpath($path = APP_PATH . 'README.md')) {
  if (!is_file($path))
    if (@touch($path))
      file_put_contents($path, <<<END
README
END
);
}

if (!realpath($path = APP_PATH . 'docs/')) {
  if (!mkdir($path, 0755, true))
    $errors['DOCS'] = $path . ' does not exist';

  if (!is_file($path . 'getting-started.md'))
    if (@touch($path . 'getting-started.md'))   // https://github.com/auraphp/Aura.Session/docs/getting-started.md
      file_put_contents($path . 'getting-started.md', <<<END
getting-started
END
);
}

if (!realpath($path = APP_PATH . APP_BASE['public'] . 'policies/')) {
  if (!mkdir($path, 0755, true))
    $errors['POLICIES'] = $path . ' does not exist';

  if (!is_file($path . 'privacy-policy'))
    if (@touch($path . 'privacy-policy'))
      file_put_contents($path . 'privacy-policy', <<<END
Privacy Policy
END
);

  if (!is_file($path . 'terms-of-use'))
    if (@touch($path . 'terms-of-use'))
      file_put_contents($path . 'terms-of-use', <<<END
Terms of Use
END
);

  if (!is_file($path . 'legal/cookies'))
    if (@touch($path . 'legal/cookies'))
      file_put_contents($path . 'legal/cookies', <<<END
Cookies
END
);
}
*/
if (basename(dirname(APP_SELF)) == 'public') {
  if (!is_file('.htaccess')) {
    if (@touch('.htaccess')) {
      file_put_contents('.htaccess', <<<END
RewriteEngine On

# Redirect resource calls from /assets/ to /resources/
RewriteRule ^resources/(.*)$ ../resources/$1 [L]
END
);
    }
  }
} elseif (dirname(APP_SELF) == __DIR__) {
  if (!is_file($file = APP_PATH . '.env')) {
    if (@touch($file)) {
      file_put_contents($file, <<<END
APP_ENV=production
APP_DEBUG=false
CACHE_DRIVER=file
SESSION_DRIVER=file
DB_HOST=localhost
DB_NAME= 
DB_UNAME=root
DB_PWORD= 
DB_PORT=3306
DEFAULT_CLIENT=
DEFAULT_DOMAIN=
DEFAULT_PROJECT=123
ERROR_LOG_FILE=error_log
ERROR_REPORTING_LEVEL=E_ALL
HIDE_UPDATE_NOTICE=true
MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
[COMPOSER]
VENDOR=barrydit
PACKAGE=CodeHub
AUTHOR=Barry Dick
EMAIL=barryd.it@gmail.com
[GITHUB]
ORIGIN_URL=http://github.com/barrydit/CodeHub
OAUTH_TOKEN=
REMOTE_SHA=
END
);
    }
  }
  if (!is_file($file = APP_PATH . '.htaccess')) {
    if (@touch($file)) {
      file_put_contents($file, <<<END
RewriteEngine On

# Check if the request is for an existing file in the resources/ directory
RewriteCond %{DOCUMENT_ROOT}/resources%{REQUEST_URI} -f
RewriteRule ^(.*)$ ./resources/$1 [L]

# Redirect all requests to index.php (assuming a typical front controller pattern)
#RewriteCond %{REQUEST_FILENAME} !-f
#RewriteRule ^ index.php [L]
END
);
    }
  }
  
  if (!is_file($file = APP_PATH . '.gitignore')) {
    if (@touch($file)) {
      file_put_contents($file, <<<END
#/config
/applications
/clientele
/dist
/var/weekly-timesheet-????-??.json
/node_modules
#/public
/public/example.php
/public/example?.php
/project?
/resources/js
/resources/reels
/vendor
/var/*.html
/var/*.php
.env*
.htaccess
*.old
auth.json
composer.lock
composer.phar
composer-setup.php
error_log
main.js
notes.txt
package-lock.json
settings.json
*.config.js
END
);
    }
  }

  if (!is_file($file = APP_PATH . 'LICENSE')) {
    if (@touch($file)) {
      if (check_http_status('http://www.wtfpl.net/txt/copying')) {
        file_put_contents($file, file_get_contents('http://www.wtfpl.net/txt/copying'));
      } else {
        file_put_contents($file, <<<END
This is free and unencumbered software released into the public domain.

Anyone is free to copy, modify, publish, use, compile, sell, or
distribute this software, either in source code form or as a compiled
binary, for any purpose, commercial or non-commercial, and by any
means.

In jurisdictions that recognize copyright laws, the author or authors
of this software dedicate any and all copyright interest in the
software to the public domain. We make this dedication for the benefit
of the public at large and to the detriment of our heirs and
successors. We intend this dedication to be an overt act of
relinquishment in perpetuity of all present and future rights to this
software under copyright law.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
IN NO EVENT SHALL THE AUTHORS BE LIABLE FOR ANY CLAIM, DAMAGES OR
OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE,
ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
OTHER DEALINGS IN THE SOFTWARE.

For more information, please refer to <https://unlicense.org>
END
);
      }
    }
  }
}

ob_start();
// write content

// 

//echo APP_SELF;

//dd(getRelativePath(APP_SELF, '/public'));

// Check if the directory structure is /public_html/

//require('constants.php');


if (isset($_GET['project'])) {
  //require_once('composer.php');
  //require_once('project.php');

if (isset($_GET['app']) && $_GET['app'] == 'project') require_once 'app.project.php';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

if (!is_dir($path = APP_PATH . 'projects')) {
  $errors['projects'] = "projects/ directory does not exist.\n";
  mkdir($path, 0777, true);
}

if (!is_file($file = APP_PATH . 'projects/index.php')) {
  $errors['projects'] = 'projects/index.php does not exist.';

  file_put_contents($file, '<?php ' . <<<END

//if (__FILE__ != get_required_files()[0])
\$require = function(\$path) { require_once \$path; };
if (\$path = (basename(getcwd()) == 'public')
  ? (is_file('config.php') ? 'config.php' : '../config/config.php') : '') \$require(\$path);
else die(var_dump(\$path)); //die(var_dump(\$path . ' path was not found. file=config.php'));

require_once(APP_PATH . APP_BASE['vendor'] . 'autoload.php');
ob_start(); // dd('Break/test the dd();');
use noximo\PHPColoredAsciiLinechart\Settings;
use noximo\PHPColoredAsciiLinechart\Linechart;
use noximo\PHPColoredAsciiLinechart\Colorizers\HTMLColorizer;

\$linechart = new Linechart();
\$settings = new Settings();  // Settings are needed in this case
\$settings->setColorizer(new HTMLColorizer());  // Here you need to set up HTMLColorizer

\$lineA = [];
for (\$i = 0; \$i < +120; \$i++) {
    \$lineA[] = 5 * sin(\$i * ((M_PI * 4) / 120));
}

\$linechart->addLine(0, ['color:white'], Linechart::FULL_LINE);  // Use css styles instead of ascii color codes
\$linechart->addMarkers(\$lineA, ['color: green'], ['color: red']);
\$linechart->setSettings(\$settings);

echo \$linechart->chart();
?>

<div class="table-container">
<table border="1" style="">
<tr><td>Create</td><td>a</td><td>table</td><td>columns</td></tr>
<tr><td>A</td><td>new</td><td>row</td></tr>
<tr><td>And</td><td>some</td><td>more</td><td>rows</td></tr>
</table>

<table border="1" style="">
<tr><td>Create New</td><td>a</td><td>table</td><td>columns</td></tr>
<tr><td>A</td><td>new</td><td>row</td></tr>
<tr><td>And</td><td>some</td><td>more</td><td>rows</td></tr>
</table>
</div>
<div style="clear: both;"></div>

<?php

// Capture the output into a variable
\$output = ob_get_clean();
ob_end_clean();

\$output = (\$output == '' ? '�' : \$output);

return <<<END
<!DOCTYPE html>
<html>
<head>
  <title></title>

<style>
/* * { border: 1px dashed red; } */
pre {
    white-space: pre-wrap;
    background: hsl(220, 80%, 90%);
}
    .table-container {
      width: 100%;
      margin: 10px 0; /* Adjust margin as needed */
    }

    .table-container table {
      float: left;
      width: 45%; /* Adjust width as needed */
      margin-right: 5%; /* Adjust margin as needed */
      border-collapse: collapse;
    }

    .table-container table:last-child {
      margin-right: 0; /* Remove margin for the last table */
    }

</style>

</head>
<body style="background-color: #fff;">
<pre style="text-align: center;"><code>|||| Demonstrational Purposes ||||<br />--[Save] to Update--</code></pre>
{$output}
</body>
</html>
END
);
}
}

if (is_file(APP_PATH . 'projects/index.php') && isset($_GET['project']) && $_GET['project'] == 'show') {
  Shutdown::setEnabled(false)->setShutdownMessage(function() {
      return eval('?>' . file_get_contents(APP_PATH . 'projects/index.php')); // -wow
    })->shutdown(); // die();
} //elseif (!is_dir(APP_PATH . 'projects')) { }

$_ENV = parse_ini_file_multi(APP_PATH . '.env');

if (basename($dir = getcwd()) != 'config') {
  if (in_array(basename($dir), ['public', 'public_html']))
    chdir('../');

  chdir(APP_PATH . APP_ROOT);
  if (is_file($file = APP_PATH . APP_ROOT . '.env')) {
    $env = parse_ini_file_multi(APP_PATH . APP_ROOT . '.env');
    $_ENV = array_merge_recursive_distinct($_ENV, $env);
/*
        foreach($env as $key => $value) {
            if (is_array($value)) {
                foreach($value as $k => $v) {
                    // Convert boolean values to strings
                    $_ENV[$key][$k] = is_bool($v) ? ($v ? 'true' : 'false') : (string) $v;
                }
            } else {
                // Convert boolean values to strings
                $_ENV[$key] = is_bool($value) ? ($value ? 'true' : 'false') : (string) $value; // putenv($key.'='.$env_var);
            }
        }*/
    //}
  }

  chdir(APP_PATH);

  
} elseif (basename(dirname(APP_SELF)) == 'public_html') { // basename(__DIR__) == 'public_html'
  $errors['APP_PUBLIC'] = 'The `public_html` scenario was detected.' . "\n";
  
  if (is_dir(dirname(APP_SELF, 2) . '/config')) {
    $errors['APP_PUBLIC'] .= "\t" . dirname(APP_SELF, 2) . '/config/*' . ' was found. This is not generally safe-scenario.'; 
  }

  chdir(dirname(__DIR__, 1));  //dd(getcwd());
    // It is under the public_html scenario
    // Perform actions or logic specific to the public_html directory
    // For example:
    // include '/home/user_123/public_html/config.php';
} elseif (basename(dirname(APP_SELF)) == 'public') {    // strpos(APP_SELF, '/public/') !== false

  //dd(APP_SELF . '   ' . __DIR__);
  
  dd(APP_BASE);

  if (!is_file(APP_PATH . APP_BASE['public'] . 'install.php'))
    if (@touch(APP_PATH . APP_BASE['public'] . 'install.php'))
      file_put_contents(APP_PATH . APP_BASE['public'] . 'install.php', '<?php ' . <<<END
if (\$_SERVER['REQUEST_METHOD'] == 'POST') {
    foreach (['composer.php', 'config.php', 'constants.php', 'functions.php', 'git.php'] as \$file) {
        if (!rename(APP_PATH . \$file, APP_PATH . 'config' . DIRECTORY_SEPARATOR . \$file))
            \$errors['INSTALL_DESTPATH'] .= "(config) Failed to move '" . APP_PATH . "\$file'";
    }

    foreach (['composer_app.php', 'index.php'] as \$file) {
        if (!rename(APP_PATH . \$file, APP_PATH . 'public' . DIRECTORY_SEPARATOR . \$file))
            \$errors['INSTALL_DESTPATH'] .= "(public) Failed to move '" . APP_PATH . "\$file'";
    }

    if (!is_file(APP_PATH . 'index.php'))
        if (@touch(APP_PATH . 'index.php'))
            file_put_contents(APP_PATH . 'index.php', '<?php require_once \'public/index.php\';');

    unlink(__FILE__);
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  
  <meta http-equiv="cache-control" content="no-cache, no-store, must-revalidate">
  <meta http-equiv="pragma" content="no-cache">
  <meta http-equiv="expires" content="0">

<style>
html, body {
  height: 100%;
  margin: 0;
  padding: 0;
}
</style>
</head>
<body>
<div style="position: relative; margin: 0 auto; border: 1px solid #000;">
<div style="position: absolute; top: 0; left: 50%; transform: translate(-50%, 10%); text-align: center; width: 570px; height: 600px; background-position: center; background-size: cover; background-repeat: no-repeat; background-image: url('/resources/images/install-scenario-small.gif'); opacity: 0.8; z-index: 1; border: 1px solid #000;">

<div style="position: absolute; top: 225px; left: 129px; width: 230px; height: 200px; border: 1px dashed #000;">
<form>
<div style="position: absolute; top: 30px; left: 28px;"><input type="radio" name="scenario" value="1" checked /></div>

<div style="position: absolute; top: 30px; right: 20px;"><input type="radio" name="scenario" value="2" /></div>

<div style="position: absolute; bottom: 34px; right: 20px;"><input type="radio" name="scenario" value="3" /></div>
</form>
</div>

</div>
</div>
</body>
</html>
END
);

  if (basename(get_required_files()[0]) !== 'release-notes.php')
    if (is_dir('config')) {
      $previousFilename = ''; // Initialize the previous filename variable

//$files = glob(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . '*.php');
//$files = array_merge($files, glob(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . '**' . DIRECTORY_SEPARATOR . '*.php'));

//sort($files);

      foreach (array_filter(glob(__DIR__ . DIRECTORY_SEPARATOR . '*.php'), 'is_file') as $includeFile) {
        //echo $includeFile . "<br />\n";

        if (in_array($includeFile, get_required_files())) continue; // $includeFile == __FILE__

        if (!file_exists($includeFile)) {
          error_log("Failed to load a necessary file: " . $includeFile . PHP_EOL);
          break;
        } else {
          $currentFilename = substr(basename($includeFile), 0, -4);
    
          //$pattern = '/^' . preg_quote($previousFilename, '/')  . /*_[a-zA-Z0-9-]*/'(_\.+)?\.php$/'; // preg_match($pattern, $currentFilename)

          if (!empty($previousFilename) && strpos($currentFilename, $previousFilename) !== false) {
            continue;
          } else {
            // dd('file:'.$currentFilename,false);

            require_once $includeFile;

            $previousFilename = $currentFilename;
          }
        }
      }

    } else if (!in_array($path = realpath('config.php'), get_required_files()))
      require_once $path;

    if (defined('APP_PROJECT')) require_once 'public/install.php';
}


/*
if ($path = realpath((basename(__DIR__) != 'config' ? NULL : __DIR__ . DIRECTORY_SEPARATOR ) . 'constants.php')) // is_file('config/constants.php')) 
  if (!in_array($path, get_required_files()))
    require_once $path;
*/

//dd(get_defined_constants(true)['user']); // true

/*

// Define your installation constants
define('INSTALL_ROOT', $_SERVER['DOCUMENT_ROOT']);  // Document root
define('APP_ROOT', __DIR__);  // Directory of this script
define('SRC_DIR', '../src/');
define('PUBLIC_DIR', '../public/');
define('CONFIG_DIR', '../config/');

// Get the request path from the URL
$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Determine if installation is needed
$installNeeded = (realpath(INSTALL_ROOT) === realpath(APP_ROOT));

// Perform installation if needed
if ($installNeeded) {
    // Determine the target directories based on request path
    $targetDirs = [
        '/' => PUBLIC_DIR,
        '/subdir/' => SRC_DIR,
        '/config/' => CONFIG_DIR,
    ];

    // Find the appropriate target directory
    $targetDir = '';
    foreach ($targetDirs as $pathPrefix => $dir) {
        if (strpos($requestPath, $pathPrefix) === 0) {
            $targetDir = $dir;
            break;
        }
    }

    if (!$targetDir) {
        echo "Installation path not found for request path: $requestPath";
    } else {
        // Define source and destination paths
        $sourceFile = __FILE__;
        $destinationFile = $targetDir . basename($sourceFile);

        // Perform installation (copy the script)
        if (copy($sourceFile, $destinationFile)) {
            echo "Installation successful. Copied script to: $destinationFile";
        } else {
            echo "Installation failed. Unable to copy script.";
        }
    }
} else {
    echo "Installation not needed.";
}

*/

/* Install code ...

$installNeeded = (realpath($_SERVER['DOCUMENT_ROOT']) === realpath(APP_PATH));

if ($installNeeded) {
    // Define your target directories
    $srcDir = APP_PATH . '..' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
    $publicDir = APP_PATH . '..' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR;
    $configDir = APP_PATH . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;

    // Perform installation (example: copy files)
    // copy('source_path/file.php', $srcDir . 'file.php');
    // copy('source_path/index.php', $publicDir . 'index.php');
    // copy('source_path/config.php', $configDir . 'config.php');
    
    echo "Installation performed.";
} else {
    echo "Installation not needed.";
}


if (dirname(APP_SELF) == __DIR__) {
  if (dirname(APP_CONFIG) != 'config')
    if (!is_file(APP_PATH . 'install.php'))
      if (@touch(APP_PATH . 'install.php')) {
        file_put_contents(APP_PATH . 'install.php', '<?php ' . <<<END
foreach (['composer.php', 'config.php', 'constants.php', 'functions.php'] as \$file) {
    if (!rename(APP_PATH . \$file, APP_PATH . 'config' . DIRECTORY_SEPARATOR . \$file))
        \$errors['INSTALL_DESTPATH'] .= "(config) Failed to move '" . APP_PATH . "\$file'";
}

foreach (['composer_app.php', 'index.php'] as \$file) {
    if (!rename(APP_PATH . \$file, APP_PATH . 'public' . DIRECTORY_SEPARATOR . \$file))
        \$errors['INSTALL_DESTPATH'] .= "(public) Failed to move '" . APP_PATH . "\$file'";
}

if (!is_file(APP_PATH . 'index.php'))
    if (@touch(APP_PATH . 'index.php'))
        file_put_contents(APP_PATH . 'index.php', '<?php require_once \'public/index.php\';');

unlink(__FILE__);
END
);
      define('APP_INSTALL', true);
    }
}
*/

//(!extension_loaded('gd'))
//  and $errors['ext/gd'] = 'PHP Extension: <b>gd</b> must be loaded inorder to export to xls for (PHPSpreadsheet).';

//dd(); // dd(getcwd());

// var_dump(get_defined_constants(true)['user']);

//echo ;
/*
if (is_array($errors) && !empty($errors)) { ?>
<html>
<head><title>Error page</title></head>
<body>
<ul>
<?php foreach ($errors as $key => $error) { ?>
  <li><?= $key . ' => ' . $error ?></li>
<?php } ?>
</ul>
</body>
</html>
<?php
  die();
} */

use vlucas\phpdotenv;

if (class_exists('Dotenv')) {
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__, 1));
$dotenv->safeLoad();
}
//dd($_ENV);

// $dotenv->load();


/*
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__, 1));
$dotenv->safeLoad();
*/
define('APP_ERRORS', $errors ?? (($error = ob_get_contents()) == null ? null : "ob_get_contents() maybe populated/defined/errors... error=$error" ));
ob_end_clean();


//(defined('APP_DEBUG') && APP_DEBUG) and $errors['APP_DEBUG'] = (bool) var_export(APP_DEBUG, APP_DEBUG); // print('Debug (Mode): ' . var_export(APP_DEBUG, true) . "\n");
