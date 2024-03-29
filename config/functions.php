<?php

function dd(mixed $param = null, $die = true, $debug = true) {
    $output = ($debug == true && !defined('APP_END') ? 
          'Execution time: <b>'  . round(microtime(true) - APP_START, 3) . '</b> secs' : 
          //APP_END . ' == APP_END'
          'Execution time: <b>'  . round(APP_END - APP_START, 3) . '</b> secs'
          ) . "<br />\n";
    if ($die)
      Shutdown::setEnabled(false)->setShutdownMessage(function() use ($param, $output) {
        return '<pre><code>' . var_export($param, true) . '</code></pre>' . $output; //.  // var_dump
      })->shutdown();
    else
      return var_dump('<pre><code>' . var_export($param, true) . '</code></pre>' . $output); // If you want to return the parameter after printing it

}
/*
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

        $response = curl_exec($curl);
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);
        return ($http_status == 200 ? true : false);
*/

function check_ping($ip = '8.8.8.8') {
  $status = null;
  // Ping the host to check network connectivity
  if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
    exec('ping -n 1 -w 1 -W 20 ' . $ip ?? '8.8.8.8', $output, $status);  // parse_url($ip, PHP_URL_HOST)
  else
    exec('sudo /bin/ping -c2 -w2 ' /*-c 1 -W 1*/ . $ip ?? '8.8.8.8' . ' 2>&1', $output, $status); // var_dump(\$status)

  return ($status == 0 ? true : false);
}

/* HTTP status of a URL and the network connectivity using ping */
function check_http_200($url = 'http://8.8.8.8') {
  if (APP_CONNECTIVITY === true) { // check_ping() was 2 == fail | 0 == success
    if ($url !== 'http://8.8.8.8') {
      $headers = get_headers($url);
      return (strpos($headers[0], '200') !== false ? false : true );
    } else
      return true; // Special case for the default URL
  }
  return false; // Ping or HTTP request failed //$connected = @fsockopen("www.google.com", 80); //fclose($connected);
}

function packagist_return_source($vendor, $package) {
  $url = 'https://packagist.org/packages/' . $vendor . '/' . $package;
  $initial_url = '';
  
  libxml_use_internal_errors(true); // Prevent HTML errors from displaying
  $dom = new DOMDocument(1.0, 'utf-8');
  if (check_http_200($url)) {
    $dom->loadHTML(file_get_contents($url));

    $anchors = $dom->getElementsByTagName('a');

    foreach ($anchors as $anchor) {
      if ($anchor->getAttribute('rel') == 'nofollow noopener external noindex ugc') {
        //echo $anchor->nodeValue;
        if ($anchor->nodeValue == 'Source')
            $initial_url = $anchor->getAttribute('href'); //  '/composer.json'
      }
    }
  }
  // $initial_url = "https://github.com/php-fig/log/tree/3.0.0/";

  if (preg_match('/^https:\/\/(?:www.?)github.com\//', $initial_url)) {

  // Extract username, repository, and version from the initial URL
    $parts = explode("/", rtrim($initial_url, "/"));
    dd($initial_url);
    dd($parts);
    
    $username = $parts[3];
    $repository = $parts[4];
    $version = $parts[6];

  //$blob_url = "https://github.com/$username/$repository/blob/$version/composer.json";
    return "https://raw.githubusercontent.com/$username/$repository/$version/composer.json";

  }
  return $initial_url;
}


function htmlsanitize(mixed $input = '') {

    if (is_array($input)) $input = var_export($input, true);
    
    if (is_null($input)) return;

    // Convert HTML entities to their corresponding characters
    $decoded = html_entity_decode($input, ENT_QUOTES, 'UTF-8');
    
    // Remove any invalid UTF-8 characters
    $cleaned = mb_convert_encoding($decoded, 'UTF-8', 'UTF-8');
    
    // Convert special characters to HTML entities
    $sanitized = htmlspecialchars($cleaned, ENT_QUOTES, 'UTF-8');

    return $sanitized;
}

//function htmlsanitize($argv = '') {
//  return html_entity_decode(mb_convert_encoding(htmlspecialchars($argv, ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8' ), 'HTML-ENTITIES', 'utf-8');
//}


/**
This function takes in two parameters: $base and $path, which represent the base path and the path to be made relative, respectively.

It first detects the directory separator used in the base path, then splits both paths into arrays using that separator. It then removes the common base elements from the beginning of the path array, leaving only the difference.

Finally, it returns the relative path by joining the remaining elements in the path array using the separator detected earlier, with the separator prepended to the resulting string.

* Return a relative path to a file or directory using base directory. 
* When you set $base to /website and $path to /website/store/library.php
* this function will return /store/library.php
* 
* Remember: All paths have to start from "/" or "\" this is not Windows compatible.
* 
* @param   String   $base   A base path used to construct relative path. For example /website
* @param   String   $path   A full path to file or directory used to construct relative path. For example /website/store/library.php
* 
* @return  String
*/
function getRelativePath($base, $path) {
  // Detect directory separator
  $separator = substr($base, 0, 1);
  $base = array_slice(explode($separator, rtrim($base,$separator)),1);
  $path = array_slice(explode($separator, rtrim($path,$separator)),1);

  return $separator.implode($separator, array_slice($path, count($base)));
}


function readlinkToEnd($linkFilename) {
  if(!is_link($linkFilename)) return $linkFilename;
  $final = $linkFilename;
  while(true) {
    $target = readlink($final);
    if(substr($target, 0, 1)=='/') $final = $target;
    else $final = dirname($final).'/'.$target;
    if(substr($final, 0, 2)=='./') $final = substr($final, 2);
    if(!is_link($final)) return $final;
  }
}

/**
 * This function is to replace PHP's extremely buggy realpath().
 * @param string The original path, can be relative etc.
 * @return string The resolved path, it might not exist.
 */
function truepath($path){
    // whether $path is unix or not
    $unipath=strlen($path)==0 || $path[0]!='/';
    // attempts to detect if path is relative in which case, add cwd
    if(strpos($path,':')===false && $unipath)
        $path=getcwd().DIRECTORY_SEPARATOR.$path;
    // resolve path parts (single dot, double dot and double delimiters)
    $path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
    $parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');
    $absolutes = array();
    foreach ($parts as $part) {
        if ('.'  == $part) continue;
        if ('..' == $part) {
            array_pop($absolutes);
        } else {
            $absolutes[] = $part;
        }
    }
    $path=implode(DIRECTORY_SEPARATOR, $absolutes);
    // resolve any symlinks
    if(file_exists($path) && linkinfo($path)>0)$path=readlink($path);
    // put initial separator that could have been lost
    $path=!$unipath ? '/'.$path : $path;
    return $path;
}


// Snippet from PHP Share: http://www.phpshare.org

function formatSizeUnits($bytes)
{
  if ($bytes >= 1073741824)
    $bytes = number_format($bytes / 1073741824, 2) . ' GB';
  elseif ($bytes >= 1048576)
    $bytes = number_format($bytes / 1048576, 2) . ' MB';
  elseif ($bytes >= 1024)
    $bytes = number_format($bytes / 1024, 2) . ' KB';
  elseif ($bytes > 1)
    $bytes = $bytes . ' bytes';
  elseif ($bytes == 1)
    $bytes = $bytes . ' byte';
  else
    $bytes = '0 bytes';
  return $bytes;
}

function convertToBytes($value) {
    $unit = strtolower(substr($value, -1, 1));
    return (int) $value * pow(1024, array_search($unit, array(1 =>'k','m','g')));
}

/**
* Converts bytes into human readable file size.
*
* @param string $bytes
* @return string human readable file size (2,87 ??)
* @author Mogilev Arseny
* @bug Does not handle 0 bytes
*/
function FileSizeConvert($bytes)
{
  $bytes = floatval($bytes);
  $arBytes = array(
    0 => array(
      "UNIT" => "TB",
      "VALUE" => pow(1024, 4)
    ),
    1 => array(
      "UNIT" => "GB",
      "VALUE" => pow(1024, 3)
    ),
    2 => array(
      "UNIT" => "MB",
      "VALUE" => pow(1024, 2)
    ),
    3 => array(
      "UNIT" => "KB",
      "VALUE" => 1024
    ),
    4 => array(
      "UNIT" => "B",
      "VALUE" => 1
    ),
  );

  foreach($arBytes as $arItem)
  {
    if($bytes >= $arItem["VALUE"])
    {
      $result = $bytes / $arItem["VALUE"];
      $result = str_replace(".", "," , strval(round($result, 2)))." ".$arItem["UNIT"];
      break;
    }
  }
  return $result;
}

function getElementsByClass(&$parentNode, $tagName, $className) {
    $nodes=array();

    $childNodeList = $parentNode->getElementsByTagName($tagName);
    for ($i = 0; $i < $childNodeList->length; $i++) {
        $temp = $childNodeList->item($i);
        if (stripos($temp->getAttribute('class'), $className) !== false) {
            $nodes[]=$temp;
        }
    }

    return $nodes;
}
