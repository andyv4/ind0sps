<?php

define('RESULT_NORMAL', 1);
define('RESULT_CLEAN', 2);
$__DEBUGS = array();
$__WORDS = "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus gravida nisl a neque elementum gravida. Donec commodo maximus nulla, ac fringilla tellus tristique eget. Nullam fermentum, tellus sit amet mattis convallis, leo neque semper nunc, eu finibus nunc tellus vel lectus. Nam tempus felis quis gravida maximus. Nunc efficitur luctus purus, id suscipit sem vulputate eget. Sed lobortis, lacus quis laoreet suscipit, lacus dolor scelerisque orci, sed finibus felis nulla at ipsum. Maecenas nunc libero, aliquam vel accumsan lacinia, iaculis id risus. Nam fermentum, quam vel consequat lacinia, dui mauris pulvinar tellus, eget eleifend augue erat ac arcu. Fusce ut felis orci. Vestibulum malesuada orci sit amet dui luctus, sagittis feugiat turpis dictum.";
$__APP_DIR = '';

function debug($arr){
  global $__DEBUGS;
  $__DEBUGS[] = $arr;
}

function randomdate($start = -10, $end = 0){
  $date = date('Ymd', mktime(0, 0, 0, date('n'), date('j') + ($start + rand(0, $end - $start)), date('Y')));
  return $date;
}
function randompastelcolors(){
  $r = dechex(rand(0, 127) + 127);
  $g = dechex(rand(0, 127) + 127);
  $b = dechex(rand(0, 127) + 127);
  return '#' . $r . $g . $b;
}
function randompassword($length){
  $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
  $pass = array();
  $alphaLength = strlen($alphabet) - 1;
  for ($i = 0; $i < $length; $i++) {
    $n = rand(0, $alphaLength);
    $pass[] = $alphabet[$n];
  }
  return implode($pass);
}
function randomwords($count = 1){
  global $__WORDS;

  $words = array();
  $explodes = explode(' ', $__WORDS);
  for($i = 0 ; $i < $count ; $i++){
    $words[] = $explodes[rand(0, count($explodes) - 1)];
  }
  return $words;
}
function randomsentences($minword, $maxword){
  $count = $minword + (rand(0, $maxword - $minword));
  $words = randomwords($count);
  return implode(' ', $words);
}

function array2csv($array, &$title, &$data) {
  foreach($array as $key => $value) {
    if(is_array($value)) {
      $title .= $key . ",";
      $data .= "" . ",";
      array2csv($value, $title, $data);
    } else {
      $title .= $key . ",";
      $data .= '"' . $value . '",';
    }
  }
}
function xml_prettyprint($xmlstring){
  return $xmlstring;
  try{
    libxml_use_internal_errors(true);
    $obj = simplexml_load_string($xmlstring);

    if(!$obj){
      $errorMessage = "";
      foreach (libxml_get_errors() as $error)
        $errorMessage .= $error->message;
      libxml_clear_errors();
      throw new Exception($errorMessage);
    }

    //Format XML to save indented tree rather than one line
    $dom = new DOMDocument('1.0');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML($obj->asXML());

    $result = $dom->saveXML();
    $result = ltrim($result);
    $result = rtrim($result);
    return $result;
  }
  catch(Exception $ex){
    return $xmlstring;
  }
}
function xml_friendlytext($text){
  $text = html_entity_decode(urldecode($text));
  return $text;
}
function xml_to_array($text){

  $xml = simplexml_load_string($text);
  $json = json_encode($xml);
  $array = json_decode($json,TRUE);
  return $array;

}
function array_to_phparray($arr){

  $exp = array();
  $exp[] = "array(";
  foreach($arr as $key=>$value)
    $exp[] = "'$key'=>'$value'";
  $exp[] = ")";
  return implode(',', $exp);

}

function object_keys($obj, $keys){

  if(!is_assoc($obj)) return $obj;
  if(!is_array($keys)) return $obj;

  $result = array();
  for($i = 0 ; $i < count($keys) ; $i++){
    $key = $keys[$i];
    if(!isset($obj[$key])) continue;
    $result[$key] = $obj[$key];
  }
  return $result;

}

function app_dir(){

  global $__APP_DIR;
  if(!$__APP_DIR) $__APP_DIR = realpath(__DIR__ . '/../../');
  return $__APP_DIR;

}

function usr_dir(){

  return app_dir() . '/usr/';

}

function is_assoc($array) {
  if(gettype($array) == "array")
    return (bool)count(array_filter(array_keys($array), 'is_string'));
  return false;
}
function array_index($arr, $indexes, $objResult = false){
  if(!is_array($arr)) return null;
  $result = array();

  for($i = 0 ; $i < count($arr) ; $i++){
    $obj = $arr[$i];

    switch(count($indexes)){
      case 1 :
        $idx0 = $indexes[0];
        if(!isset($obj[$idx0])) continue;
        if(!isset($result[$obj[$idx0]])) $result[$obj[$idx0]] = array();
        $result[$obj[$idx0]][] = $obj;
        break;
      case 2 :
        $idx0 = $indexes[0];
        $idx1 = $indexes[1];
        if(!isset($obj[$idx0]) || !isset($obj[$idx1])) continue;
        $key0 = $obj[$idx0];
        $key1 = $obj[$idx1];
        if(!isset($result[$key0])) $result[$key0] = array();
        if(!isset($result[$key0][$key1])) $result[$key0][$key1] = array();
        $result[$key0][$key1][] = $obj;
        break;
      case 3 :
        $idx0 = $indexes[0];
        $idx1 = $indexes[1];
        $idx2 = $indexes[2];
        if(!isset($obj[$idx0]) || !isset($obj[$idx1]) || !isset($obj[$idx2])) continue;
        $key0 = $obj[$idx0];
        $key1 = $obj[$idx1];
        $key2 = $obj[$idx2];
        if(!isset($result[$key0])) $result[$key0] = array();
        if(!isset($result[$key0][$key1])) $result[$key0][$key1] = array();
        $result[$key0][$key1][$key2] = $obj;
        break;
      default:
        throw new Exception("Unsupported index level.");
    }
  }

  // If array count = 1, remove array
  if($objResult){
    switch(count($indexes)){
      case 1:
        foreach($result as $key=>$arr)
          if(count($arr) == 1) $result[$key] = $arr[0];
        break;
      case 2:
        foreach($result as $key=>$arr1){
          foreach($arr1 as $key1=>$arr){
            if(count($arr) == 1) $result[$key][$key1] = $arr[0];
          }
        }
        break;
      case 3:
        foreach($result as $key=>$arr1){
          foreach($arr1 as $key1=>$arr2){
            foreach($arr2 as $key2=>$arr)
              if(count($arr) == 1) $result[$key][$key1][$key2] = $arr[0];
          }
        }
        break;
    }
  }

  return $result;
}
function array_sanitize($obj){

  if(is_array($obj)){
    foreach($obj as $key=>$value){
      $obj[$key] = gettype($value) == 'string' ? trim($value) : $value;
    }
  }

  return $obj;
}
function array_exclude($arr, $excludes){

  $results = array();
  for($i = 0 ; $i < count($arr) ; $i++){
    if(in_array($arr[$i], $excludes)) continue;
    $results[] = $arr[$i];
  }
  return $results;

}
function array_cast($arr, $maps){

  $results = array();
  for($i = 0 ; $i < count($arr) ; $i++){
    $obj = $arr[$i];
    $mapobj = array();
    foreach($maps as $key=>$value)
      $mapobj[$key] = $obj[$value];
    $results[] = $mapobj;
  }
  return $results;

}
function object_cast($obj, $maps){

  $result = array();
  foreach($maps as $key=>$value){
    $result[$key] = ov($value, $obj);
  }
  return $result;

}
function isdate($value){

  $date_format_regexes = array(
      "/^\\d{4}-\\d{2}-\\d{2}$/",
      "/^\\d{2}-\\d{2}-\\d{4}$/",
      "/^\\d{8}$/"
  );

  for($i = 0 ; $i < count($date_format_regexes) ; $i++){
    $regex = $date_format_regexes[$i];

    if(preg_match($regex, $value) && strtotime($value) > 0) return true;
  }

  //$d = DateTime::createFromFormat('Y-m-d', $value);
  //return $d && $d->format('Y-m-d') == $value;

  return false;
}
function money_is_equal($val1, $val2, $epsilon = 0.9){
  return abs($val1 - $val2) <= $epsilon ? true : false;
}
function isdatetime($value){

  $date_format_regexes = array(
      "/^\\d{4}-\\d{2}-\\d{2} \\d{2}:\\d{2}:\\d{2}$/",
      "/^\\d{2}-\\d{2}-\\d{4} \\d{2}:\\d{2}:\\d{2}$/",
  );

  for($i = 0 ; $i < count($date_format_regexes) ; $i++){
    $regex = $date_format_regexes[$i];

    if(preg_match($regex, $value) && strtotime($value) > 0) return true;
  }
  return false;
}
function isbool($value){
  if(strval($value) == '0' || strval($value) == '1') return true;
  return false;
}
function bool_parse($str){

  if(in_array(strtolower($str), [ 'ya', 'yes', '1' ])) return 1;
  return 0;

}
function isnumber($value){
  $strval = strval($value);
  return preg_match('/^-?[0-9]\d*(\.\d+)?$/', $value);
}
function getdatatype($value){

  if(isbool($value)) return 'bool';
  if(isdatetime($value)) return 'datetime';
  if(isdate($value)) return 'date';
  if(floatval($value) && !preg_match("/^(\\-)\\W/", $value)) return 'number';
  return 'text';

}
function objectToArray($d) {
  if (is_object($d)) {
    // Gets the properties of the given object
    // with get_object_vars function
    $d = get_object_vars($d);
  }

  if (is_array($d)) {
    /*
    * Return array converted to object
    * Using __FUNCTION__ (Magic constant)
    * for recursive call
    */
    return array_map(__FUNCTION__, $d);
  }
  else {
    // Return array
    return $d;
  }
}
function ov($name, $obj, $required = false, $param1 = ''){

  $value = null;
  if($required){
    if(!isset($obj[$name])) throw new Exception(ucwords($name) . ' salah.');
    if(is_array($param1)){
      $type = ov('type', $param1);
      switch($type){
        case 'array' :
          if(!is_array($obj[$name])) throw new Exception(ucwords($name) . ' salah.');
          break;
        case 'bool' :
          if(in_array($obj[$name], array(1, true, 'on', 'off', false, 0, 'false', 'true'))){
            $obj[$name] = in_array($obj[$name], array(1, true, 'on', 'true')) ? 1 : 0;
          }
          else
            throw new Exception(ucwords($name) . ' salah.');
        case 'date':
          if(!isdate($obj[$name])) throw new Exception(ucwords($name) . ' salah.');
          $obj[$name] = date('Ymd', strtotime($obj[$name]));
          break;
        case 'decimal':
          if(!floatval($obj[$name])) throw new Exception(ucwords($name) . ' salah.');
          if(isset($param1['notnull']) && $param1['notnull'] && !floatval($obj[$name])) throw new Exception(ucwords($name) . ' salah.');
          break;
        case 'int':
          if(!intval($obj[$name])) throw new Exception(ucwords($name) . ' salah.');
          $obj[$name] = intval($obj[$name]);
          break;
        case 'money':
          if(!floatval($obj[$name])) throw new Exception(ucwords($name) . ' salah.');
          $obj[$name] = floatval($obj[$name]);
          break;
        default:
          if(isset($param1['notempty']) && $param1['notempty'] == 1 && empty($obj[$name])) throw new Exception("$name is required.");
      }
    }
    $value = $obj[$name];
  }
  else{
    if(isset($obj[$name])) $value = $obj[$name];
    else $value = $param1;
  }
  if(is_string($value)) $value = trim($value);
  return $value;

}
function get_os(){
  $obj = array(
    "os" => PHP_OS,
    "php" => PHP_SAPI,
    "system"=> php_uname(),
    "unique"=> md5(php_uname(). PHP_OS . PHP_SAPI)
  );
  return $obj;
}
function date_tz($time, $timezone, $timestamp = -1){
  if($timestamp == -1) $timestamp = time();
  //$defaulttimezone = date_default_timezone_get();
  date_default_timezone_set($timezone);
  $result = date($time, $timestamp);
  //date_default_timezone_set($defaulttimezone);
  return $result;
}
function strtotime_tz($timestamp, $zone){
  //$defaulttimezone = date_default_timezone_get();
  date_default_timezone_set($zone);
  $timestamp = strtotime($timestamp);
  //date_default_timezone_set($defaulttimezone);
  return $timestamp;
}
function in_arrayobject($haystack, $needle){

  $match = false;
  if(is_array($haystack) && !is_assoc($haystack)){
    for($i = 0 ; $i < count($haystack) ; $i++){
      $obj = $haystack[$i];

      $match = true;
      foreach($needle as $key=>$value){
        if(isset($obj[$key]) && $obj[$key] == $value);
        else $match = false;
      }
      if($match) break;
    }
  }
  return $match;

}
function te($obj){
  throw new Exception(print_r($obj, 1));
}
function htmlattr($attr){

  $result = array();
  if($attr && is_array($attr))
    foreach($attr as $key=>$val)
      $result[] = $key . "=\"" . $val . "\"";
  return implode(' ', $result);

}
function print_var($var){
  throw new Exception(print_r($var, 1));
}

function console_info($msg){
  echo uijs("console.info(" . json_encode($msg) . ")");
}
function console_log($obj){
  echo uijs("console.log(" . json_encode($obj) . ")");
}
function console_warn($msg){
  echo uijs("console.warn(\"" . htmlentities($msg) . "\")");
}
function uijs($script){
  return "<script type='text/javascript'>$script</script>";
}
function uiwarn($message, $return = false){
  if(!$return) echo uijs("console.warn(\"$message\")");
  return uijs("console.warn(\"$message\")");
}
function uilog($json, $return = false){
  if(!$return) uijs("console.log($json)");
  return uijs("console.log($json)");
}

function tail($filename, $lines = 10, $buffer = 4096, $lineseparator = "\n")
{
  // Open the file
  $f = fopen($filename, "rb");

  // Jump to last character
  fseek($f, -1, SEEK_END);

  // Read it and adjust line number if necessary
  // (Otherwise the result would be wrong if file doesn't end with a blank line)
  if(fread($f, 1) != $lineseparator) $lines -= 1;

  // Start reading
  $output = '';
  $chunk = '';

  // While we would like more
  while(ftell($f) > 0 && $lines >= 0)
  {
    // Figure out how far back we should jump
    $seek = min(ftell($f), $buffer);

    // Do the jump (backwards, relative to where we are)
    fseek($f, -$seek, SEEK_CUR);

    // Read a chunk and prepend it to our output
    $output = ($chunk = fread($f, $seek)).$output;

    // Jump back to where we started reading
    fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);

    // Decrease our line counter
    $lines -= substr_count($chunk, $lineseparator);
  }

  // While we have too many lines
  // (Because of buffer size we might have read too many)
  while($lines++ < 0)
  {
    // Find first newline and remove all text before that
    $output = substr($output, strpos($output, $lineseparator) + 1);
  }

  // Close file and return
  fclose($f);
  return $output;
}
function find_files($path, $pattern){

  $files = [];
  $it = new RecursiveDirectoryIterator($path);
  foreach(new RecursiveIteratorIterator($it) as $file){
    if(strpos($file, $pattern) !== false) $files[] = $file;
  }
  return $files;

}

function ui_async_put(){

  $url = $mimetype = '';
  if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
    $usr_dir = str_replace("rcfx\\php", "", dirname(__FILE__)) . 'usr\\';
  else
    $usr_dir = str_replace("rcfx/php", "", dirname(__FILE__)) . 'usr/';

  if($_SERVER['CONTENT_TYPE'] == 'application/vnd.ms-excel' && $_SERVER['CONTENT_LENGTH'] > 0){
    $url = $usr_dir . uniqid() . ".xls";
    $mimetype = 'application/vnd.ms-excel';
    $putdata = fopen("php://input", "r");

    if(!file_exists($usr_dir)) throw new Exception('Directory not exists.');
    if(!is_writable($usr_dir)) throw new Exception('Write permission denied.');

    $fp = fopen($url, "w");
    while ($data = fread($putdata, 1024))
      fwrite($fp, $data);
    fclose($fp);
    fclose($putdata);
  }
  else if($_SERVER['CONTENT_TYPE'] == 'text/csv' || $_SERVER['CONTENT_TYPE'] == 'text/comma-separated-values'){
    $url = $usr_dir . uniqid() . '.csv';
    $mimetype = 'text/csv';
    $putdata = fopen("php://input", "r");

    if(!file_exists($usr_dir)) throw new Exception('Directory not exists.');
    if(!is_writable($usr_dir)) throw new Exception('Write permission denied.');

    $fp = fopen($url, "w");
    while ($data = fread($putdata, 1024))
      fwrite($fp, $data);
    fclose($fp);
    fclose($putdata);
  }
  else if($_SERVER['CONTENT_TYPE'] == 'text/plain'){
    $url = $usr_dir . uniqid() . '.txt';
    $mimetype = 'text/plain';
    $putdata = fopen("php://input", "r");

    if(!file_exists($usr_dir)) throw new Exception('Directory not exists.');
    if(!is_writable($usr_dir)) throw new Exception('Write permission denied.');

    $fp = fopen($url, "w");
    while ($data = fread($putdata, 1024))
      fwrite($fp, $data);
    fclose($fp);
    fclose($putdata);
  }
  else if(in_array($_SERVER['CONTENT_TYPE'], array('image/jpeg', 'image/png', 'image/jpg'))){
    $ext = str_replace('image/', '', $_SERVER['CONTENT_TYPE']);
    $filename = uniqid() . '.' . $ext;
    $url = $usr_dir . 'img/' . $filename;
    $mimetype = $_SERVER['CONTENT_TYPE'];
    $putdata = fopen("php://input", "r");

    if(!file_exists($usr_dir)) throw new Exception('Directory not exists.');
    if(!is_writable($usr_dir)) throw new Exception('Write permission denied.');

    $fp = fopen($url, "w");
    while ($data = fread($putdata, 1024))
      fwrite($fp, $data);
    fclose($fp);
    fclose($putdata);
  }
  else
    throw new Exception('Unsupported mime type. [' . $_SERVER['CONTENT_TYPE'] . ']');

  $method = $_GET['_asyncm'];
  if(ob_get_contents()) ob_end_clean();
  if(function_exists($method)){
    $qs = $_SERVER['QUERY_STRING'];
    $params = array();
    parse_str($qs, $params);
    $params['fileurl'] = $url;
    $params['filename'] = $filename;
    $params['mimetype'] = $mimetype;

    try{
      $c = call_user_func_array($method, array($params));
      echo $c;
    }
    catch(Exception $ex){
      header("HTTP/1.1 500 Internal Server Error");
      die($ex->getMessage());
    }
  }
  else{
    if(!empty($method)){
      header("HTTP/1.1 500 Internal Server Error");
      die("Method not exists. [$method]");
    }
  }

  //echo json_encode(array('url'=>$url, 'mimetype'=>$mimetype));
}
function ui_async_post(){

  $method = $_GET['_asyncm'];

  // Auto include
  if(substr($method, 0, 3) == 'ui_'){
    $uifiles = glob('ui/*.php');
    foreach($uifiles as $uifile){
      $ui = explode('.', basename($uifile))[0];
      if(strpos($method, $ui) !== false){
        require_once $uifile;
      }
    }
  }

  if(function_exists($method)){
    $params = objectToArray(json_decode(file_get_contents("php://input")));

    if(!$params) $params = [];
    $params = array_sanitize($params);

    $c = call_user_func_array($method, $params);
    echo $c;
  }
  else
    throw new Exception("Method not exists. [$method]");
}
function ui_async(){

  if(isset($_GET['_async'])){

    header('Content-Type: text/html; charset=utf-8');
    ob_implicit_flush(true);
    if(ob_get_contents()) ob_end_clean();
    ob_start();

    global $starttime, $url;
    $starttime = microtime(1);
    if(!session_isopen() && $_GET['_asyncm'] != 'ui_login'){
      echo uijs("window.location = '';");
    }
    else{
      try{
        switch($_SERVER['REQUEST_METHOD']){
          case 'PUT' :
            ui_async_put();
            break;
          case 'POST' :
            ui_async_post();
            break;
        }
      }
      catch(Exception $ex){
        //header("HTTP/1.1 500 Internal Server Error");
        //die($ex->getMessage());

        $errorlines = array();
        $traces = debug_backtrace(true);
        for($i = 0 ; $i < count($traces) ; $i++)
          $errorlines[] = $traces[$i]['file'] . ":" . $traces[$i]['line'];
        $errorlines = array();

        $isdebug = isset($_SESSION['debugmode']) && $_SESSION['debugmode'] > 0 ? true : false;
        echo ui_dialog("Error", $ex->getMessage() . ($isdebug ? "\n\n" . implode("\n", $errorlines) . "\n\n" . json_encode($traces) : ''));
      }
      //log_write_end();

      // Debug print
      global $starttime, $__LOGS, $__DEBUGS, $pdo_logs;
      $debug = array(
          'ram_used'=>memory_get_usage() * 0.001 . " KB",
          'execution_time'=>(microtime(1) - $starttime) . " s",
          'pdo_logs'=>$pdo_logs,
          'debugs'=>$__DEBUGS
      );
      if(is_array($__DEBUGS) && count($__DEBUGS) > 0) echo uijs("debug = " . json_encode($debug) . ";console.log(debug)");
    }

//    $endtime = microtime(1) - $starttime;
//    $asyncm = isset($_GET['_asyncm']) ? $_GET['_asyncm'] : '-';
//    $memory_used = memory_get_usage(false);
//    $verbose = debug_backtrace(true);
//    pm("INSERT INTO indosps.systemlog(module_name, method_name, memory_used, duration, end_time, `verbose`)
//      VALUES (?, ?, ?, ?, ?, ?)",
//      array($url, $asyncm, $memory_used, $endtime, date('YmdHis'), json_encode($verbose)));

    exit();

  }

}

function curl_get($url){

  $ch = curl_init();
  curl_setopt($ch,CURLOPT_URL, $url);
  curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
  $output = curl_exec($ch);
  curl_close($ch);

  return $output;
}

function umid($salt = ""){
  if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    /*$temp = sys_get_temp_dir().DIRECTORY_SEPARATOR."diskpartscript.txt";
    if(!file_exists($temp) && !is_file($temp)) file_put_contents($temp, "select disk 0\ndetail disk");
    $output = shell_exec("diskpart /s ".$temp);
    $lines = explode("\n",$output);
    $result = array_filter($lines,function($line) {
      return stripos($line,"ID:")!==false;
    });
    if(count($result)>0) {
      $result = array_shift(array_values($result));
      $result = explode(":",$result);
      $result = trim(end($result));
    } else $result = $output;*/
  } else {
    $result = shell_exec("blkid -o value -s UUID");
    if(stripos($result,"blkid")!==false) {
      $result = $_SERVER['HTTP_HOST'];
    }
  }
  return md5($salt.md5($result));
}
//if(umid('da41bceff97b1cf96078ffb249b3d66e') != '44f87ca5cd624a23e914edf919b48beb') exit();

function curl_post($url, $postdata){

  $fields = array();
  if(is_array($postdata))
    foreach($postdata as $key=>$value){
      $fields[$key] = urlencode($value);
    }
  $fields_string = '';
  foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
  rtrim($fields_string, '&');

  $ch = curl_init();
  curl_setopt($ch,CURLOPT_URL, $url);
  curl_setopt($ch,CURLOPT_POST, count($fields));
  curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
  curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
  $output = curl_exec($ch);
  curl_close($ch);

  $arr = objectToArray(json_decode($output));
  return $arr;
}

function excel_to_array($url){

  require_once 'rcfx/php/PHPExcel/IOFactory.php';

  $objReader = new PHPExcel_Reader_Excel5();
  $objPHPExcel = $objReader->load($url);
  $arr = $objPHPExcel->getActiveSheet()->toArray(null, true, true, false);

  //echo "<script type='text/javascript'>console.log(" . json_encode($arr) . ")</script>";

  $columns = array();
  $data0 = $arr[0];
  for($i = 0 ; $i < count($data0) ; $i++)
    $columns[] = array('name'=>$data0[$i], 'index'=>$i);

  $data = array();
  for($i = 1 ; $i < count($arr) ; $i++){
    $obj = $arr[$i];

    $datarow = array();
    foreach($columns as $column){
      $name = $column['name'];
      $value = $obj[$column['index']];

      $datarow[$name] = $value;
    }
    $data[] = $datarow;
  }

  return $data;
}

function array_to_excel($arr, $path){

  require_once dirname(__FILE__) . '/PHPExcel.php';
  $objPHPExcel = new PHPExcel();
  if(ob_get_contents()) ob_end_clean();
  ob_start();
  $objPHPExcel->getActiveSheet()->fromArray($arr, NULL, 'A1');
  $writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
  $writer->save($path);

}

function array_to_csv($arr, $path){

  $file = fopen($path, "w");
  foreach ($arr as $obj)
    fputcsv($file, $obj);
  fclose($file);
  return;


  require_once dirname(__FILE__) . '/PHPExcel.php';
  $objPHPExcel = new PHPExcel();
  if(ob_get_contents()) ob_end_clean();
  ob_start();
  $objPHPExcel->getActiveSheet()->fromArray($arr, NULL, 'A1');
  $writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
  $writer->save($path);

}

function php_to_array($code){

  $func = create_function("", $code);
  $arr = $func();
  return $arr;


}

function debug_write($message, $clear = false){

  $success = file_put_contents('usr/debug.log', $message . "\n", !$clear ? FILE_APPEND : null);
  if(!$success) throw new Exception('Unable to write debug.log');

}

function sanitize_text_output($text){
  return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function csvtext_to_array($text, &$headers){

  $file = 'usr/temp.csv';
  file_put_contents($file, $text);

  $headers = null;
  $results = array();
  if (($handle = fopen($file, "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
      if($headers == null){
        $headers = $data;
      }
      else{
        $obj = array();
        for($i = 0 ; $i < count($headers) ; $i++)
          $obj[$headers[$i]] = $data[$i];
        $results[] = $obj;
      }
    }
    fclose($handle);
  }

  return $results;
}

function gridcolumns_identify($data, $firstrowonly = true){

  $columns = array();
  if($firstrowonly){
    $data0 = $data[0];

    foreach($data0 as $key=>$value){

      $datatype = '';
      if(is_numeric($value)) $datatype = 'number';

      $columns[] = array(
        'active'=>1,
        'name'=>$key,
        'text'=>ucwords($key),
        'width'=>50,
        'datatype'=>$datatype
      );
    }
  }
  return $columns;

}

function data_filter_obj($obj, $filters){

  $match = true;

  for($i = 0 ; $i < count($filters) ; $i++){
    $filter = $filters[$i];
    $filtername = $filter['name'];
    $filtertype = ov('type', $filter);

    //if(!isset($obj[$filtername])) continue;
    $value = $obj[$filtername];
    switch($filtertype){
      case 'date':
        $operator = $filter['operator'];
        $value1 = $filter['value'];
        $value2 = $filter['value1'];

        switch($operator){
          case 'thisweek':
            $dayofweek = date('N', strtotime('now'));
            $thisweek_starttime = strtotime(date('Ymd') . '000000') - (($dayofweek - 1) * (60 * 60 * 24));
            $thisweek_endtime = strtotime(date('Ymd') . '235959') + ((7 - $dayofweek) * (60 * 60 * 24));
            if(strtotime($value) >= $thisweek_starttime && strtotime($value) <= $thisweek_endtime) $match = true;
            else $match = false;
            break;
          case 'thismonth':
            $thismonth_starttime = mktime(0, 0, 0, date('n'), 1, date('Y'));
            $thismonth_endtime = mktime(23, 59, 59, date('n') + 1, 0, date('Y'));
            if(strtotime($value) >= $thismonth_starttime && strtotime($value) <= $thismonth_endtime) $match = true;
            else $match = false;
            break;
          case 'thisyear':
            $thisyear_starttime = mktime(0, 0, 0, 1, 1, date('Y'));
            $thisyear_endtime = mktime(23, 59, 59, 12, 31, date('Y'));
            if(strtotime($value) >= $thisyear_starttime && strtotime($value) <= $thisyear_endtime) $match = true;
            else $match = false;
            break;
          case 'today':
            if(date('Ymd', strtotime($value)) == date('Ymd', strtotime('now'))) $match = true;
            else $match = false;
            break;
          case 'on':
            if(date('Ymd', strtotime($value)) == $value1) $match = true;
            else $match = false;
            break;
          case 'between':
            $between_starttime = strtotime($value1 . '000000');
            $between_endtime = strtotime($value2 . '235959');
            if(strtotime($value) >= $between_starttime && strtotime($value) <= $between_endtime) $match = true;
            else $match = false;
            break;
          case 'before':
            $before_starttime = strtotime($value2 . '000000');
            if(strtotime($value) < $before_starttime) $match = true;
            else $match = false;
            break;
          case 'after':
            $after_starttime = strtotime($value1 . '235959');
            trace(date('Ymd', $after_starttime), 1);
            if(strtotime($value) > $after_starttime) $match = true;
            else $match = false;
            break;
        }
        break;
      case 'number':
        if($filter['value'] != ''){
          $operator = $filter['operator'];
          $value1 = floatval($filter['value']);
          $value2 = floatval($filter['value1']);
          switch($operator){
            case '>': if($value > $value1); else $match = false; break;
            case '<': if($value < $value1); else $match = false; break;
            case '>=': if($value >= $value1); else $match = false; break;
            case '<=': if($value <= $value1); else $match = false; break;
            case '=': if($value == $value1); else $match = false; break;
            case '<>': if($value != $value1); else $match = false; break;
            case 'between': if($value >= $value1 && $value <= $value2); else $match = false; break;
          }
        }
        break;
      case 'money':
        if($filter['value'] != ''){
          $operator = $filter['operator'];
          $value1 = floatval($filter['value']);
          $value2 = floatval($filter['value1']);
          switch($operator){
            case '>': if($value > $value1); else $match = false; break;
            case '<': if($value < $value1); else $match = false; break;
            case '>=': if($value >= $value1); else $match = false; break;
            case '<=': if($value <= $value1); else $match = false; break;
            case '=': if($value == $value1); else $match = false; break;
            case '<>': if($value != $value1); else $match = false; break;
            case 'between': if($value >= $value1 && $value <= $value2); else $match = false; break;
          }
        }
        break;
      case 'bool':
        switch($filter['operator']){
          case '0': if($value) $match = false; break;
          case '1': if(!$value) $match = false; break;
        }
        break;
      default:
        $operator = $filter['operator'];
        $value1 = $filter['value'];
        if(strpos($value1, '|') !== false) $value1 = explode('|', $value1);

        if(strpos($filtername, '|') !== false){

          $filternames = explode('|', $filtername);

          $match = false;
          switch($operator){
            case 'contains':
              foreach($filternames as $filtername){
                $value = $obj[$filtername];
                if(is_array($value1))
                  foreach($value1 as $value1value){
                    if(strpos(strtolower($value), strtolower($value1value)) !== false)
                      return true;
                  }
                else
                  if(strpos(strtolower($value), strtolower($value1)) !== false)
                    return true;
              }
              break;
          }

        }
        else{

          switch($operator){
            case 'contains':
              if(strpos(strtolower($value), strtolower($value1)) !== false);
              else $match = false;
              break;
            case 'equals':
              if(strtolower($value) != strtolower($value1)) $match = false;
              break;
            case 'notempty':
              if(!empty($value)) $match = true;
              break;

          }

        }

    }

    if(!$match) break;
  }

  return $match;

}
function data_filter(&$data, $filters){

  $results = array();
  if(is_array($data) && is_array($filters)){
    foreach($data as $obj){
      $match = data_filter_obj($obj, $filters);
      if($match) $results[] = $obj;
    }
  }
  $data = $results;

}

function data_sort(&$arr, $sorts){
  if(is_array($sorts) && is_array($arr) && count($sorts) > 0){
    $sortfunctionexps = array();
    for($i = 0 ; $i < count($sorts) ; $i++){
      $sort = $sorts[$i];
      $sortname = $sort['name'];
      $sorttype = $sort['sorttype'];

      $sortfunctionexps[] = 'if($obj1["'.$sortname.'"] == $obj2["'.$sortname.'"]){ [equalexp] } return $obj1["'.$sortname.'"] ' . ($sorttype == 'asc' ? '>' : '<') . ' $obj2["'.$sortname.'"] ? 1 : -1;';
    }

    $sortfunctionexps[count($sortfunctionexps) - 1] = str_replace('[equalexp]', 'return 0;', $sortfunctionexps[count($sortfunctionexps) - 1]);
    for($i = count($sortfunctionexps) - 2 ; $i >= 0 ; $i--){
      $sortfunctionexps[$i] = str_replace('[equalexp]', $sortfunctionexps[$i + 1], $sortfunctionexps[$i]);
    }
    $sortfunctionexp = $sortfunctionexps[0];

    $sortfunction = create_function('$obj1,$obj2', $sortfunctionexp);
    usort($arr, $sortfunction);
  }
}
function data_group($data, $groups){

  $groupdata = data_group_depth($data, $groups, 0);

  $inheritedcolumns = array();
  foreach($groups as $group)
    $inheritedcolumns[] = $group['name'];

  //echo uijs("console.log(" . json_encode($inheritedcolumns) . ")");

  $groupdata = data_group_calc($groupdata, 0, $inheritedcolumns);
  return $groupdata;

  //$obj = array('__groupitems'=>$groupdata);
  //$obj = data_group_calc($obj, $groups);
  //return $obj['__groupitems'];

}
function data_group_calc($arr, $level = 0, $inheritedcolumns = null, $sorts = null, $groupname = null){

  for($i = 0 ; $i < count($arr) ; $i++){

    $obj = $arr[$i];
    $columns = $obj['__groupcolumns'];
    $groupname = $obj['__groupname'];
    $aggregrate = $obj['__groupaggregrate'];
    $sorts = $obj['__groupsorts'];

    if(count($obj['__groupitems']) > 0){

      // Calculate child object first if it's group
      if(isset($obj['__groupitems'][0]['__groupname']))
        $obj['__groupitems'] = data_group_calc($obj['__groupitems'], $level + 1, $inheritedcolumns, $sorts, $groupname);

      if(is_array($inheritedcolumns)){
        foreach($inheritedcolumns as $columnname){
          $columnlogic = 'first';
          data_group_calc_column($obj, $columnname, $columnlogic, $aggregrate);
        }
      }

      foreach($columns as $column){
        $columnname = $column['name'];
        $columnlogic = $column['logic'];

        data_group_calc_column($obj, $columnname, $columnlogic, $aggregrate);
      }

    }

    $arr[$i] = $obj;

  }
  data_sort($arr, $sorts);

  return $arr;
}
function data_group_calc_column(&$obj, $columnname, $columnlogic, $aggregrate){

  switch($columnlogic){
    case 'sum':
      $sum = 0;
      for($j = 0 ; $j < count($obj['__groupitems']) ; $j++){
        if(isset($obj['__groupitems'][$j][$columnname]))
          $sum += ov($columnname, $obj['__groupitems'][$j]);
        else if(isset($obj['__groupitems'][$j][$columnname . '.sum']))
          $sum += ov($columnname . '.sum', $obj['__groupitems'][$j]);
      }
      $obj[$columnname . '.sum'] = $sum;
      break;
    case 'min':
      $min = null;
      for($j = 0 ; $j < count($obj['__groupitems']) ; $j++){
        $item = $obj['__groupitems'][$j];
        if($min == null || $item[$columnname] < $min) $min = $item[$columnname];
      }
      $obj[$columnname . '.min'] = $min;
      break;
    case 'max':
      $max = 0;
      for($j = 0 ; $j < count($obj['__groupitems']) ; $j++){
        $item = $obj['__groupitems'][$j];
        if($item[$columnname] > $max) $max = $item[$columnname];
      }
      $obj[$columnname . '.max'] = $max;
      break;
    default:
      $obj[$columnname] = ov($columnname, $obj['__groupitems'][0]);
      if($columnname == $groupname){
        switch($aggregrate){
          case 'monthly':  $obj[$columnname] = date('M Y', strtotime($obj[$columnname])); break;

        }
      }
      break;
  }

}
function data_group_depth($data, $groups, $level){

  $groupdata = data_groupify($groups[$level], $data);

  if($level + 1 < count($groups))
    for($i = 0 ; $i < count($groupdata) ; $i++)
      $groupdata[$i]['__groupitems'] = data_group_depth($groupdata[$i]['__groupitems'], $groups, $level + 1);

  return $groupdata;
}
function data_groupify($group, $data){

  $groupname = $group['name'];
  $groupcolumns = $group['columns'];
  $groupaggregrate = $group['aggregrate'];
  $groupsorts = ov('sorts', $group);

  $datakey = array();
  for($i = 0 ; $i < count($data) ; $i++){

    $obj = $data[$i];
    $keyvalue = $obj[$groupname];

    switch($groupaggregrate){
      case 'monthly': $keyvalue = date('M Y', strtotime($keyvalue)); break;
    }

    if(!isset($datakey[$keyvalue])) $datakey[$keyvalue] = array(
      '__groupname'=>$groupname,
      '__groupvalue'=>$keyvalue,
      '__groupcolumns'=>$groupcolumns,
      '__groupaggregrate'=>$groupaggregrate,
      '__groupsorts'=>$groupsorts,
      '__groupitems'=>array()
    );

    $datakey[$keyvalue]['__groupitems'][] = $obj;
  }

  $datagroups = array();
  foreach($datakey as $key=>$datagroup){
    /*
    for($i = 0 ; $i < count($groupcolumns) ; $i++){
      $groupcolumn = $groupcolumns[$i];
      $groupcolumnname = $groupcolumn['name'];
      $groupcolumnlogic = $groupcolumn['logic'];
      $groupcolumnvalue = '';


      switch($groupcolumnlogic){
        case 'first':
          $groupcolumnvalue = count($datagroup['__groupitems']) > 0 ? $datagroup['__groupitems'][0][$groupcolumnname] : 'No item';
          break;
        case 'sum':
          $sum = 0;
          if(is_array($datagroup['__groupitems']))
            for($j = 0 ; $j < count($datagroup['__groupitems']) ; $j++){
              $item = $datagroup['__groupitems'][$j];
              $sum += floatval($item[$groupcolumnname]);
            }
          $groupcolumnvalue = $sum;
          break;
        case 'min':
          $min = null;
          if(is_array($datagroup['__groupitems']))
            for($j = 0 ; $j < count($datagroup['__groupitems']) ; $j++){
              $item = $datagroup['__groupitems'][$j];
              $val = floatval($item[$groupcolumnname]);
              if($min == null) $min = $val;
              else if($val < $min) $min = $val;
            }
          $groupcolumnvalue = $min;
          break;
        case 'max':
          $max = null;
          if(is_array($datagroup['__groupitems']))
            for($j = 0 ; $j < count($datagroup['__groupitems']) ; $j++){
              $item = $datagroup['__groupitems'][$j];
              $val = floatval($item[$groupcolumnname]);
              if($max == null) $max = $val;
              else if($val > $max) $max = $val;
            }
          $groupcolumnvalue = $max;
          break;
        case 'avg':
          $avg = 0;
          if(is_array($datagroup['__groupitems'])){
            for($j = 0 ; $j < count($datagroup['__groupitems']) ; $j++){
              $item = $datagroup['__groupitems'][$j];
              $val = floatval($item[$groupcolumnname]);
              $avg += $val;
            }
            $avg = round($avg / count($datagroup['__groupitems']));
          }
          $groupcolumnvalue = $avg;
          break;
      }


      $datagroup[$groupcolumnname . ($groupcolumnlogic != 'first' ? '.' . $groupcolumnlogic : '')] = $groupcolumnvalue;
    }
    */

    $datagroups[] = $datagroup;

  }

  /*
  data_sort($datagroups, $groupsorts);

  if(isset($group['limit']) && isset($group['offset'])){
    $limit = $group['limit'];
    $offset = $group['offset'];
    $datagroups = array_splice($datagroups, $offset, $limit);
  }
  */

  return $datagroups;

}
function data_calculate_logicalcolumn($arr, $columns){

  if(is_array($arr) && is_array($columns)){
    $logicalcolumns = array();
    for($i = 0 ; $i < count($columns) ; $i++)
      if(ov('type', $columns[$i]) == 'logical')
        $logicalcolumns[] = $columns[$i];

    if(count($logicalcolumns) > 0){
      for($i = 0 ; $i < count($arr) ; $i++){
        $obj = $arr[$i];
        $prevobj = $i - 1 >= 0 ? $arr[$i - 1] : null;

        foreach($logicalcolumns as $logicalcolumn){
          $logicalformula = ov('logicalformula', $logicalcolumn);
          $logicalformula = str_replace('PREV', '$prevobj', $logicalformula);
          $logicalformula = str_replace('CURR', '$obj', $logicalformula);
          $func = create_function('$obj,$prevobj', "return " . $logicalformula . ";");
          //if(!$func) throw new Exception(error_get_last()['message']);
          $value = $func($obj, $prevobj);

          $arr[$i][$logicalcolumn['name']] = $value;
        }
      }

    }
  }

  return $arr;

}

function sortquery_from_sorts($sorts, $columnaliases = null){

  // Sort queries as required by both view type
  $sortqueries = array();
  if(isset($sorts) && is_array($sorts)){
    for($i = 0 ; $i < count($sorts) ; $i++){
      $sort = $sorts[$i];
      $sortname = $sort['name'];
      $sorttype = $sort['sorttype'];

      if(is_array($columnaliases)){
        if(!isset($columnaliases[$sortname])) continue;

        $sortname = $columnaliases[$sortname];
        $sortname = str_replace('!', '', $sortname);
        if(strpos($sortname, 'as ') !== false)
          $sortname = substr($sortname, 0, strpos($sortname, 'as '));
        $sortqueries[] = "$sortname $sorttype";
      }
      else{
        $sortqueries[] = "$sortname $sorttype";
      }

    }
  }
  $sortqueries = implode(', ', $sortqueries);
  if(strlen($sortqueries) > 0) $sortqueries = " ORDER BY $sortqueries";

  return $sortqueries;
}

function wherequery_from_filters(&$params, $filters, $columnaliases = null, $columns = null){

  $columns = array_index($columns, [ 'name' ], true);

  $wherequeries = array();
  $prevtype = null;
  if(isset($filters) && is_array($filters)){
    for($i = 0 ; $i < count($filters) ; $i++){
      $filter = $filters[$i];
      $filtertype = ov('type', $filter, 0, 'text');
      $filtertype = trim($filtertype);

      switch($filtertype){

        case 'or':
        case 'OR':
          $wherequeries[] = 'OR';
          $prevtype = 'or';
          break;
        case 'and':
        case 'AND':
          $wherequeries[] = 'AND';
          $prevtype = 'and';
          break;
        case '(':
          $wherequeries[] = '(';
          $prevtype = '(';
          break;
        case ')':
          $wherequeries[] = ')';
          $prevtype = ')';
          break;

        default:

          if($prevtype == 'text' || $prevtype == ')')
            $wherequeries[] = 'AND';

          $filtername = $filter['name'];
          $filteroperator = $filter['operator'];
          $filtervalue = $filter['value'];
          $filtervalue1 = ov('value1', $filter);

          // Check datatype if columns parameter supplied
          if(isset($columns[$filtername]['datatype'])){
            switch($columns[$filtername]['datatype']){

              case 'bool':
              case 'boolean':
                $filtervalue = bool_parse($filtervalue);
                $filtervalue1 = bool_parse($filtervalue1);
                break;
            }
          }

          // Filter name contains "or" operator (name|description)
          if(strpos($filtername, '|') !== false){

            $innerwherequeries = array();
            $filternames = explode('|', $filtername);
            foreach($filternames as $filtername){
              $filtername = trim($filtername);

              if(is_assoc($columnaliases)){
                if(!isset($columnaliases[$filtername])) continue;

                if(strpos($columnaliases[$filtername], 'as ') !== false)
                  $columnaliases[$filtername] = substr($columnaliases[$filtername], 0, strpos($columnaliases[$filtername], 'as '));
                $filtername = $columnaliases[$filtername];
              }

              $filtername = str_replace('!', '', $filtername);

              if(in_array($filteroperator, array('>', '>=', '=', '<', '<=', '<>'))){
                $innerwherequeries[] = "$filtername $filteroperator ?";
                array_push($params, $filtervalue);
              }
              else{
                switch($filteroperator){
                  case 'today':
                    $innerwherequeries[] = "DATE($filtername) = ?";
                    array_push($params, date('Ymd'));
                    break;
                  case 'thisweek':
                    $dayofweek = date('N', strtotime('now'));
                    $thisweek_starttime = strtotime(date('Ymd') . '000000') - (($dayofweek - 1) * (60 * 60 * 24));
                    $thisweek_endtime = strtotime(date('Ymd') . '235959') + ((7 - $dayofweek) * (60 * 60 * 24));
                    $innerwherequeries[] = "DATE($filtername) >= ? AND DATE($filtername) <= ?";
                    array_push($params, date('YmdHis', $thisweek_starttime), date('YmdHis', $thisweek_endtime));
                    break;
                  case 'prevmonth':
                    $innerwherequeries[] = "MONTH($filtername) = ? AND YEAR($filtername) = ?";
                    $prevmonth = date('Ymd', mktime(0, 0, 0, date('m') - 1, date('j'), date('Y')));
                    array_push($params, date('m', strtotime($prevmonth)), date('Y', strtotime($prevmonth)));
                    break;
                  case 'thismonth':
                    $innerwherequeries[] = "MONTH($filtername) = ? AND YEAR($filtername) = ?";
                    array_push($params, date('m'), date('Y'));
                    break;
                  case 'thisyear':
                    $innerwherequeries[] = "YEAR($filtername) = ?";
                    array_push($params, date('Y'));
                    break;
                  case 'lastyear':
                    $innerwherequeries[] = "YEAR($filtername) = ?";
                    array_push($params, date('Y') - 1);
                    break;
                  case 'on':
                    $innerwherequeries[] = "DATE($filtername) = ?";
                    array_push($params, $filtervalue);
                    break;
                  case 'between':
                    $innerwherequeries[] = "DATE($filtername) >= ? AND DATE($filtername) <= ?";
                    array_push($params, $filtervalue, $filtervalue1);
                    break;
                  case 'before':
                    $innerwherequeries[] = "DATE($filtername) < ?";
                    array_push($params, $filtervalue);
                    break;
                  case 'after':
                    $innerwherequeries[] = "DATE($filtername) > ?";
                    array_push($params, $filtervalue);
                    break;
                  case 'equals':
                    $innerwherequeries[] = "$filtername = ?";
                    array_push($params, "$filtervalue");
                    break;
                  case 'contains':
                    $innerwherequeries[] = "$filtername LIKE ?";
                    array_push($params, "%$filtervalue%");
                    break;
                  case 'in':
                    $filtervalue_explodes = explode(',', $filtervalue);
                    $in_queries = array();
                    for($j = 0 ; $j < count($filtervalue_explodes) ; $j++){
                      $in_queries[] = "?";
                      array_push($params, trim($filtervalue_explodes[$j]));
                    }
                    $innerwherequeries[] = "$filtername IN (" . implode(', ', $in_queries) . ")";
                    break;
                }
              }
            }
            if(count($innerwherequeries) > 0){
              $innerwherequeries = "(" . implode(' OR ', $innerwherequeries) . ")";
              $wherequeries[] = $innerwherequeries;
            }

          }
          else{

            if(is_assoc($columnaliases)){
              if(isset($columnaliases[$filtername])){
                if(strpos($columnaliases[$filtername], 'as ') !== false)
                  $columnaliases[$filtername] = substr($columnaliases[$filtername], 0, strpos($columnaliases[$filtername], 'as '));
                $filtername = $columnaliases[$filtername];
              }
            }

            $filtername = str_replace('!', '', $filtername);

            if(in_array($filteroperator, array('>', '>=', '=', '<', '<=', '<>'))){
              $wherequeries[] = "$filtername $filteroperator ?";
              array_push($params, $filtervalue);
            }
            else{
              switch($filteroperator){
                case 'today':
                  $wherequeries[] = "DATE($filtername) = ?";
                  array_push($params, date('Ymd'));
                  break;
                case 'thisweek':
                  $dayofweek = date('N', strtotime('now'));
                  $thisweek_starttime = strtotime(date('Ymd') . '000000') - (($dayofweek - 1) * (60 * 60 * 24));
                  $thisweek_endtime = strtotime(date('Ymd') . '235959') + ((7 - $dayofweek) * (60 * 60 * 24));
                  $wherequeries[] = "DATE($filtername) >= ? AND DATE($filtername) <= ?";
                  array_push($params, date('YmdHis', $thisweek_starttime), date('YmdHis', $thisweek_endtime));
                  break;
                case 'thismonth':
                  $wherequeries[] = "MONTH($filtername) = ? AND YEAR($filtername) = ?";
                  array_push($params, date('m'), date('Y'));
                  break;
                case 'prevmonth':
                  $wherequeries[] = "MONTH($filtername) = ? AND YEAR($filtername) = ?";
                  $prevmonth = date('Ymd', mktime(0, 0, 0, date('m') - 1, date('j'), date('Y')));
                  array_push($params, date('m', strtotime($prevmonth)), date('Y', strtotime($prevmonth)));
                  break;
                case 'lastyear':
                  $wherequeries[] = "YEAR($filtername) = ?";
                  array_push($params, date('Y') - 1);
                  break;
                case 'thisyear':
                  $wherequeries[] = "YEAR($filtername) = ?";
                  array_push($params, date('Y'));
                  break;
                case 'on':
                  $wherequeries[] = "DATE($filtername) = ?";
                  array_push($params, $filtervalue);
                  break;
                case 'between':
                  $wherequeries[] = "DATE($filtername) >= ? AND DATE($filtername) <= ?";
                  array_push($params, $filtervalue, $filtervalue1);
                  break;
                case 'before':
                  $wherequeries[] = "DATE($filtername) < ?";
                  array_push($params, $filtervalue);
                  break;
                case 'after':
                  $wherequeries[] = "DATE($filtername) > ?";
                  array_push($params, $filtervalue);
                  break;
                case 'contains':
                  $wherequeries[] = "$filtername LIKE ?";
                  array_push($params, "%$filtervalue%");
                  break;
                case 'not_contains':
                  $wherequeries[] = "$filtername NOT LIKE ?";
                  array_push($params, "%$filtervalue%");
                  break;
                case '!=':
                  $wherequeries[] = "$filtername != ?";
                  array_push($params, "$filtervalue");
                  break;
                case 'in':
                  $filtervalue_explodes = explode(',', $filtervalue);
                  $in_queries = array();
                  for($j = 0 ; $j < count($filtervalue_explodes) ; $j++){
                    $in_queries[] = "?";
                    array_push($params, trim($filtervalue_explodes[$j]));
                  }
                  $wherequeries[] = "$filtername IN (" . implode(', ', $in_queries) . ")";
                  break;
                default:
                  $wherequeries[] = "$filtername = ?";
                  array_push($params, "$filtervalue");
                  break;
              }
            }

          }
          $prevtype = 'text';
          break;

      }

    }
  }

  $query = '';
  if(count($params) > 0)
    $query = " WHERE " . implode(' ', $wherequeries);
  return $query;

}

function columnquery_from_table($tablenames){

  $columnqueries = array();

  for($i = 0 ; $i < count($tablenames) ; $i++){
    $tablename =  $tablenames[$i];

    $columns = pmrs("SHOW COLUMNS FROM $tablename");
    foreach($columns as $column){
      $columnname = $column['Field'];

      if(isset($columnqueries[$columnname])) $columnname = $tablename . $columnname;
      $columnqueries[$columnname] = "t" . ($i + 1) . "." . $column['Field'] . " as `$columnname`";
    }

  }

  return $columnqueries;

}
function columnquery_from_columns($columns, $excludes = null){

  $columnqueries = array();
  if(is_array($columns)){
    foreach($columns as $column){
      if(!$column['active']) continue;
      if(isset($excludes[$column['name']])) continue;
      $exp = isset($column['sqlcolumn']) ? '`' . $column['sqlcolumn'] . '`' : '`' . $column['name'] . '`';
      if(empty($exp)) continue;
      $columnqueries[] = $exp;
    }
  }
  return implode(', ', $columnqueries);

}
function columnquery_from_columnaliases($columns, $aliases, $customcolumns = null){

  $query = [];

  // Static columns
  $defined_columns = [];
  if(isset($aliases) && is_array($aliases))
    foreach($aliases as $alias_key=>$alias_val){
      if(strpos($alias_val, '!') !== false || $columns == '*'){
        $query[] = str_replace('!', '', $alias_val) . " as `{$alias_key}`";
        $defined_columns[$alias_key] = 1;
      }
    }

  // Dynamic columns
  for($i = 0 ; $i < count($columns) ; $i++){

    $column = $columns[$i];
    $columnname = ov('name', $column);

    if(empty($columnname)) continue;
    if(!isset($aliases[$columnname])) continue;
    if(isset($column['active']) && !ov('active', $column) && strpos($aliases[$columnname], '!') === false) continue;

    $alias = $aliases[$columnname];
    $alias = str_replace('!', '', $alias);
    $logic = ov('logic', $column);

    if(isset($defined_columns[$columnname . ($logic ? '#' . $logic : '')])) continue;

    switch($logic){
      case 'sum': $alias = "SUM($alias) as `$columnname`"; break;
      case 'count': $alias = "COUNT($alias) as `$columnname`"; break;
      case 'min': $alias = "MIN($alias) as `$columnname`"; break;
      case 'max': $alias = "MAX($alias) as `$columnname`"; break;
      case 'avg': $alias = "AVG($alias) as `$columnname`"; break;
      default: $alias = "{$alias} as `{$columnname}`"; break;
    }

    $query[] = $alias;

  }
  if(is_array($customcolumns))
    for($i = 0 ; $i < count($customcolumns) ; $i++)
      $query[] = $customcolumns[$i];

  return implode(', ', $query);

}
function columnquery($columns){

  $query = '';
  if(is_array($columns)){
    $query = array();
    for($i = 0 ; $i < count($columns) ; $i++){
      $column = $columns[$i];
      $columnname = ov('name', $column);
      $columnlogic = ov('logic', $column);

      $alias = $columnname;
      $logic = ov('logic', $column);
      switch($logic){
        case 'sum': $alias = "SUM($alias) as `$alias`"; break;
        case 'count': $alias = "COUNT($alias) as `$alias`"; break;
        case 'min': $alias = "MIN($alias) as `$alias`"; break;
        case 'max': $alias = "MAX($alias) as `$alias`"; break;
        case 'avg': $alias = "AVG($alias) as `$alias`"; break;
      }
      $query[] = $alias;
    }
    $query = implode(', ', $query);
  }
  return $query;

}
function columns_setactive($columns, $activecolumns){

  for($i = 0 ; $i < count($columns) ; $i++)
    $columns[$i]['active'] = in_array($columns[$i]['name'], $activecolumns) ? 1 : 0;
  return $columns;

}
function columns_setwidth($columns, $widthcolumns){

  for($i = 0 ; $i < count($columns) ; $i++){
    if(isset($widthcolumns[$columns[$i]['name']]))
      $columns[$i]['width'] = $widthcolumns[$columns[$i]['name']];
  }
  return $columns;

}
function columns_fromdbtable($table){

  $results = array();
  $rows = pmrs("DESCRIBE $table");
  foreach($rows as $row){
    $field = $row['Field'];
    $type = $row['Type'];

    $datatype = '';
    if(strpos($type, 'int') !== false) $datatype = 'number';
    else if(strpos($type, 'double') !== false) $datatype = 'number';
    else if(strpos($type, 'datetime') !== false) $datatype = 'datetime';
    else if(strpos($type, 'date') !== false) $datatype = 'date';

    $results[] = array('active'=>1, 'name'=>$field, 'text'=>$field, 'datatype'=>$datatype, 'width'=>50);
  }
  return $results;

}

function columnquery_from_groupcolumns($columns){

  $columnqueries = array();
  for($i = 0 ; $i < count($columns) ; $i++){
    $column = $columns[$i];
    $columnname = $column['name'];
    $columnlogic = $column['logic'];

    switch($columnlogic){
      case 'sum': $columnqueries[] = "SUM(`$columnname`) as `$columnname.sum`"; break;
      default: $columnqueries[] = "`$columnname`"; break;
    }
  }
  return implode(', ', $columnqueries);

}

function quickfilters_to_filters($quickfilters){

  $filters = null;
  if(is_array($quickfilters)){
    for($i = 0 ; $i < count($quickfilters) ; $i++){
      $quickfilter = $quickfilters[$i];
      if($filters == null) $filters = array();
      $filters[] = array(
        'name'=>$quickfilter['name'],
        'operator'=>'contains',
        'value'=>$quickfilter['value']
      );
    }
  }
  return $filters;

}
function filter_merge($filters, $quickfilters){

  $result = array();
  if(is_array($filters) && count($filters) > 0) $result = array_merge($result, $filters);
  if(is_array($quickfilters)){
    $filters = null;
    for($i = 0 ; $i < count($quickfilters) ; $i++){
      $quickfilter = $quickfilters[$i];
      if($filters == null) $filters = array();
      $filters[] = array(
          'name'=>$quickfilter['name'],
          'operator'=>'contains',
          'value'=>$quickfilter['value']
      );
    }
    if(is_array($filters) && count($filters) > 0) $result = array_merge($result, $filters);
  }
  return $result;

}

function groupcolumns_from_groups($groups){

  $columns = array();
  foreach($groups as $group){
    $groupcolumns = $group['columns'];
    foreach($groupcolumns as $groupcolumn)
      $columns[] = $groupcolumn['name'];
  }
  return $columns;

}

function limitquery_from_limitoffset($limitoffset = null){

  $limitquery = '';
  if(is_array($limitoffset)){
    $limit = ov('limit', $limitoffset, 0, 0);
    $offset = ov('offset', $limitoffset, 0, 0);

    $limitquery = "LIMIT $limit OFFSET $offset";
  }
  return $limitquery;

}

function groupquery_from_groups($groups, $columns){

  /*
   * groups: array of group
   *   group: object
   *     name: string
   *     columns: array
   *     aggregrate: enum (first|sum)
   */

  $queries = [];
  if(is_array($groups) && count($groups) > 0){
    foreach($groups as $group){
      $group_name = ov('name', $group);
      $group_aggregrate = ov('aggregrate', $group);

      if(!$group_name) continue;
      if(!isset($columns[$group_name])) exc("Invalid group name. [$group_name]");

      switch($group_aggregrate){
        case 'monthly':
          $queries[] = "DATE_FORMAT($group_name, '%b %Y')";
          break;
        case 'yearly':
          $queries[] = "DATE_FORMAT($group_name, '%Y')";
          break;
        default:
          $queries[] = $group_name;
          break;
      }
    }
  }
  $queries = implode(', ', $queries);
  if(strlen($queries) > 0)
    $queries = 'GROUP BY ' . $queries;

  return $queries;

}

function groupcolumn_from_group($group, $columns){

  /*
   *   group: object
   *     name: string
   *     columns: array
   *     aggregrate: enum (monthly)
   */

  $queries = [];
  $name = ov('name', $group);
  $group_columns = ov('columns', $group);
  $aggregrate = ov('aggregrate', $group);
  foreach($group_columns as $index=>$group_column){
    $group_name = ov('name', $group_column);
    $group_logic = ov('logic', $group_column);

    if(!isset($columns[$group_name])) exc("Invalid group name. [$group_name]");

    switch($group_logic){

      case 'sum': $queries[] = "SUM($group_name) as `col-$index`"; break;
      case 'count': $queries[] = "COUNT($group_name) as `col-$index`"; break;
      case 'avg': $queries[] = "AVG($group_name) as `col-$index`"; break;
      case 'min': $queries[] = "MIN($group_name) as `col-$index`"; break;
      case 'max': $queries[] = "MAX($group_name) as `col-$index`"; break;
      default:
        switch($aggregrate){
          case 'monthly':
            if($group_name == $name){
              $queries[] = "DATE_FORMAT($group_name, '%b %Y') as `col-$index`";
            }
            break;
          case 'yearly':
            if($group_name == $name){
              $queries[] = "DATE_FORMAT($group_name, '%Y') as `col-$index`";
            }
            break;
          default:
            $queries[] = "$group_name as `col-$index`";
            break;
        }

    }

  }
  return implode(', ', $queries);

}

function array_replace_rows_with_arrayobject($arr, $arrobj, $key){

  $results = array();

  foreach($arrobj as $arrobjkey=>$arrobjvalue){
    $results[] = $arrobjvalue;
  }

  for($i = 0 ; $i < count($arr) ;$i++){
    if(isset($arrobj[$arr[$i][$key]])) continue;
    $results[] = $arr[$i];
  }

  return $results;

  if(is_array($arr)){
    for($i = 0 ; $i < count($arr) ; $i++){
      $obj = $arr[$i];
      if(isset($arrobj[$obj[$key]])) $arr[$i] = $arrobj[$obj[$key]];
    }
  }

}

function terbilang($x)
{

  $abil = array("", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas");
  if ($x < 12)
    return " " . $abil[$x];
  elseif ($x < 20)
    return Terbilang($x - 10) . "belas";
  elseif ($x < 100)
    return Terbilang($x / 10) . " puluh" . Terbilang($x % 10);
  elseif ($x < 200)
    return " seratus" . Terbilang($x - 100);
  elseif ($x < 1000)
    return Terbilang($x / 100) . " ratus" . Terbilang($x % 100);
  elseif ($x < 2000)
    return " seribu" . Terbilang($x - 1000);
  elseif ($x < 1000000)
    return Terbilang($x / 1000) . " ribu" . Terbilang($x % 1000);
  elseif ($x < 1000000000)
    return Terbilang($x / 1000000) . " juta" . Terbilang($x % 1000000);
}

function datadef_validation($obj, $def, $parent = ''){

  foreach($def as $name=>$spec){

    $type = ov('type', $spec);
    $required = ov('required', $spec, 0);
    $error_message = ov('error_message', $spec, 0, '');
    $has_error_message = !empty($error_message);
    $defaultvalue = ov('defaultvalue', $spec, 0, ' ');
    $nametext = $parent != '' ? $name . ' of ' . $parent : $name;

    if($required){
      if(!isset($obj[$name]))
        throw new Exception("Parameter $nametext required.");
      $value = $obj[$name];
    }
    else{
      if(!isset($obj[$name]))
        $obj[$name] = $value = $defaultvalue;
    }

    switch($type){
      case 'date':
        if(!isdate($value)) throw new Exception($has_error_message ? $error_message : "Invalid date parameter for $nametext. ($value)");
        break;
      case 'int':
        if(!preg_match('/^\d+$/', $value)) throw new Exception($has_error_message ? $error_message : "Invalid int parameter for $nametext. ($value)");
        break;
      case 'array':
        if(!is_array($value)) throw new Exception($has_error_message ? $error_message : "Invalid array parameter for $nametext. ($value)");
        $items = ov('items', $spec);
        if(!is_array($items)) throw new Exception($has_error_message ? $error_message : "Array definition required for $nametext.");
        if(is_assoc($items)){
          throw new Exception($has_error_message ? $error_message : 'assoc type unimplemented');
        }
        else{
          if(is_assoc($value)) throw new Exception($has_error_message ? $error_message : "Invalid array type, array type required for $nametext");
          if($required && count($value) == 0) throw new Exception($has_error_message ? $error_message : "Array value required, empty array supplied for $nametext");
          if(count($items) == 0) throw new Exception($has_error_message ? $error_message : "Array definition required for $nametext.");

          for($i = 0 ; $i < count($value) ; $i++)
            $value[$i] = datadef_validation($value[$i], $items[0], $name);

        }
        break;
      default:
        if($required && empty($value)) throw new Exception($has_error_message ? $error_message : "Invalid string parameter for $nametext. ($value)");
        break;

    }


  }

  return $obj;

}
function value_validate($text, $definition = null){


  if(isset($definition) && is_object($definition)){

    $datatype = isset($definition['datatype']) ? $definition['datatype'] : 'string';
    $invalid_message = isset($definition['invalid_message']) ? $definition['invalid_message'] : '';
    switch($datatype){
      case 'date':
        if(!isdate($text)) exc($invalid_message ? $invalid_message : "Unexpected date parameter: $text");
        break;
      case 'int':
        if(!preg_match('/^\d+$/', $text)) exc($invalid_message ? $invalid_message : "Unexpected int parameter: $value");
        break;
      default:
        break;

    }
  }
  return $text;

}

function array_data_findchanges($params, &$newrows, &$deletedrows, &$updatedrows){

  $items = $params['items'];
  $currentitems = $params['currentitems'];
  $keys = $params['keys'];

  //throw new Exception(print_r($currentitems, 1));
  //throw new Exception(print_r($items, 1));
  //throw new Exception(print_r($keys, 1));

  // Find new items
  foreach($items as $item){

    $item_exists = false;
    foreach($currentitems as $currentitem){

      $item_match = true;
      foreach($keys as $key){

        if($currentitem[$key] != $item[$key]){
          $item_match = false;
          break;
        }
      }

      if($item_match){
        $item_exists = true;
        break;
      }

    }
    //console_warn($currentitem['type'] . ' ' . $currentitem['typeid'] . ' ' . ' exists: ' . ($item_exists ? 'yes':'no'));

    if(!$item_exists) $newrows[] = $item;
    else{

      $item_updated = false;
      $updatedcols = array();
      foreach($item as $key=>$value){
        if(isset($currentitem[$key]) && $currentitem[$key] != $value){
          $item_updated = true;
          $updatedcols[$key] = $value;
        }
      }

      if($item_updated){
        $item['__updatedcols'] = $updatedcols;
        $updatedrows[] = $item;
      }
    }

  }


  // Find deleted items
  foreach($currentitems as $currentitem){

    $item_exists = false;
    foreach($items as $item){

      $item_match = true;
      foreach($keys as $key){

        if($currentitem[$key] != $item[$key]){
          $item_match = false;
          break;
        }
      }

      if($item_match){
        $item_exists = true;
        break;
      }

    }
    //console_warn($currentitem['type'] . ' ' . $currentitem['typeid'] . ' ' . ' exists: ' . ($item_exists ? 'yes':'no'));

    if(!$item_exists) $deletedrows[] = $currentitem;

  }


}

/*
 * Compare 2 array of object
 * Return false or TODO
 */
function array_object_is_modified($arr1, $arr2, $columns){

  if(!is_array_object($arr1)) return true;
  if(!is_array_object($arr2)) return true;
  if(!is_array($columns)) return true;

  $arr1_keys = [];
  foreach($arr1 as $obj){
    foreach($columns as $column){
      $datatype = 'string';
      $column_exploded = explode(':', $column);
      if(isset($column_exploded[1]) && in_array($column_exploded[1], [ 'string', 'number' ])) $datatype =  $column_exploded[1];
      $column = $column_exploded[0];

      switch($datatype){
        case 'number': $arr1_keys[] = number_format($obj[$column], 2); break;
        default: $arr1_keys[] = $obj[$column]; break;
      }
    }
  }

  $arr2_keys = [];
  foreach($arr2 as $obj){
    foreach($columns as $column){
      $datatype = 'string';
      $column_exploded = explode(':', $column);
      if(isset($column_exploded[1]) && in_array($column_exploded[1], [ 'string', 'number' ])) $datatype =  $column_exploded[1];
      $column = $column_exploded[0];

      switch($datatype){
        case 'number': $arr2_keys[] = number_format($obj[$column], 2); break;
        default: $arr2_keys[] = $obj[$column]; break;
      }
    }
  }

  $is_modified = implode($arr1_keys) == implode($arr2_keys) ? false : true;
  if(count($arr1) != count($arr2)) $is_modified = true;
  return $is_modified;

}

function array_diff_custom($data1, $data2, $keys){

  // Find new
  $new_obj = [];
  $modified_obj = [];
  $data1_indexed = array_index($data1, $keys);

  foreach($data2 as $data){
    if(count($keys) == 1){
      $key1 = $data[$keys[0]];
      if(!isset($data1_indexed[$key1]))
        $new_obj[] = $data;
      else
        $modified_obj[] = $data;
    }
    else if(count($keys) == 2){
      $key1 = $data[$keys[0]];
      $key2 = $data[$keys[1]];
      if(!isset($data1_indexed[$key1][$key2]))
        $new_obj[] = $data;
      else
        $modified_obj[] = $data;
    }
    else if(count($keys) == 3){
      $key1 = $data[$keys[0]];
      $key2 = $data[$keys[1]];
      $key3 = $data[$keys[2]];
      if(!isset($data1_indexed[$key1][$key2][$key3]))
        $new_obj[] = $data;
      else
        $modified_obj[] = $data;
    }
  }

  // Find deleted
  $deleted_obj = [];
  $data2_indexed = array_index($data2, $keys);
  foreach($data1 as $data){
    if(count($keys) == 1){
      $key1 = $data[$keys[0]];
      if(!isset($data2_indexed[$key1]))
        $deleted_obj[] = $data;
    }
    else if(count($keys) == 2){
      $key1 = $data[$keys[0]];
      $key2 = $data[$keys[1]];
      if(!isset($data2_indexed[$key1][$key2]))
        $deleted_obj[] = $data;
    }
    else if(count($keys) == 3){
      $key1 = $data[$keys[0]];
      $key2 = $data[$keys[1]];
      $key3 = $data[$keys[2]];
      if(!isset($data2_indexed[$key1][$key2][$key3]))
        $deleted_obj[] = $data;
    }
  }

  return [
    'new'=>$new_obj,
    'deleted'=>$deleted_obj,
    'modified'=>$modified_obj
  ];

}

function module_addcolumns(&$module, $columns){

  $changed = false;
  $modulecolumns = $module['columns'];
  $modulecolumns_indexed = array_index($modulecolumns, array('name'), 1);

  for($i = 0 ; $i < count($columns) ; $i++){
    $column = $columns[$i];
    $columnname = $column['name'];

    if(!isset($modulecolumns_indexed[$columnname])){

      $module['columns'][] = $column;
      for($j = 0 ; $j < count($module['presets']) ; $j++)
        $module['presets'][$j]['columns'][] = $column;
      $changed = true;

    }
  }

  return $changed;

}

function columns_active_set($arr, $cols, $mode = 'only_this'){

  switch($mode){
    case 'only_this':
      if(is_array($arr)){
        for($i = 0 ; $i < count($arr) ; $i++){
          if(in_array($arr[$i]['name'], $cols)) $arr[$i]['active'] = 1;
          else $arr[$i]['active'] = 0;
        }
      }
      break;

  }
  return $arr;

}

/**
 * Check whether input is array of object
 * @param $arr
 * @return bool
 */
function is_array_object($arr){

  if(!is_array($arr) || count($arr) <= 0) return false;
  foreach($arr as $obj)
    if(!is_assoc($obj)) return false;
  return true;

}

/**
 * Shorthand for throw exception
 * @param $message
 * @throws Exception
 */
function exc($message){

  if(is_assoc($message) || is_array($message))
    throw new Exception("<pre>" . print_r($message, 1) . "</pre>");
  else
    throw new Exception($message);

}

/**
 * Get minimum round precision of float value
 * @param $value
 * @return int
 */
function get_round_precision($value){

  $precision = round($value - floor($value), 8) > 0 ? strlen(round($value - floor($value), 8)) - 2 : 0;
  return $precision;

}

/**
 * Retrieve base path of app (no ending slash)
 * @return mixed
 */
function base_path(){

  return str_replace('/rcfx/php', '', dirname(__FILE__));

}

/**
 * Number format with auto precision
 * @param $number
 * @param int $decimals
 * @return string
 */
function number_format_auto($number, $decimals = 0){

  if(abs($number - round($number)) > 0);
  else $decimals = 0;
  $value = number_format($number, $decimals);
  return $value;

}

/**
 * Number format with auto precision for money type. Minus value use (xxx)
 * @param $number
 * @param int $decimals
 * @return string
 */
function number_format_auto_money($number, $decimals = 0){

  if(abs($number - round($number)) > 0);
  else $decimals = 0;

  if($number < 0)
    $value = '(' . number_format($number * -1, $decimals) . ')';
  else
    $value = number_format($number, $decimals);
  return $value;

}

/**
 * Convert module columns to ui columns
 * Usage:
 * - purchaseinvoice_uicolumns
 * @param $columns
 * @return array
 */
function ui_columns($columns){

  $c = [];
  foreach($columns as $name=>$column){
    $column['name'] = $name;
    $c[] = $column;
  }
  return $c;

}

/**
 * Retrieve updated obj compared to current object
 * @param $current
 * @param $next
 * @param null $definition
 * @return array
 */
function module_modify($current, $next, $definition = null){

  $updated = [];
  foreach($current as $key=>$value){

    if(isset($next[$key])){

      if(is_object($value)){

        // TODO

      }
      else if(is_array_object($value)){

        $arr1 = $value;
        $arr2 = $next[$key];

        if(false){
          if(isset($definition[$key])){

            $keys = isset($definition[$key]['keys']) ? $definition[$key]['keys'] : '';
            $keys = is_string($keys) ? explode(',', $keys) : [];

            if(count($keys) > 0){

              $next_value = [];
              foreach($arr1 as $index1=>$obj1){
                $obj1_matched = false;
                foreach($arr2 as $index2=>$obj2){
                  $obj2_matched = true;
                  foreach($keys as $key1){
                    if($obj1[$key1] != $obj2[$key1]){
                      $obj2_matched = false;
                      break;
                    }
                  }
                  if($obj2_matched){
                    $obj1_matched = true;
                    break;
                  }
                }
                if($obj1_matched){
                  $is_modified = [];
                  foreach($obj1 as $key1=>$val1){
                    if(isset($obj2[$key1]) && $obj1[$key1] != $obj2[$key1]){
                      $is_modified[] = $key1 . ':' . $obj1[$key1] . '-' . $obj2[$key1];
                    }
                  }
                  if(count($is_modified) > 0){
                    $obj2['__flag'] = 'modified';
                    $next_value[] = $obj2;
                  }
                  else{
                    $obj1['__flag'] = 'unchanged';
                    $next_value[] = $obj1;
                  }
                }
                else{
                  $obj1['__flag'] = 'removed';
                  $next_value[] = $obj1;
                }
              }
              foreach($arr2 as $index2=>$obj2){
                $obj2_matched = false;
                foreach($arr1 as $index1=>$obj1){
                  $obj1_matched = true;
                  foreach($keys as $key1){
                    if($obj2[$key1] != $obj1[$key1]){
                      $obj1_matched = false;
                      break;
                    }
                  }
                  if($obj1_matched){
                    $obj2_matched = true;
                    break;
                  }
                }
                if(!$obj2_matched){
                  $obj2['__flag'] = 'new';
                  $next_value[] = $obj2;
                }
              }
              if(count($next_value) > 0) $updated[$key] = $next_value;

            }
            else{
              $updated[$key] = $arr2;
            }

          }
          else{

            $next_value = [];
            foreach($arr1 as $obj1){
              $obj1_match = true;
              foreach($arr2 as $obj2){
                foreach($obj1 as $key1=>$val1){
                  if(!isset($obj2[$key1])){
                    $obj1_match = false;
                    break;
                  }
                  else if($obj2[$key1] != $obj1[$key1]){
                    $obj1_match = false;
                    break;
                  }
                }
              }
              if(!$obj1_match){
                $obj1['_flag'] = 'remove';
                $next_value[] = $obj1;
              }
            }
            foreach($arr2 as $obj2){
              $obj2_match = true;
              foreach($arr1 as $obj1){
                foreach($obj2 as $key2=>$val2){
                  if(!isset($obj1[$key2])){
                    $obj2_match = false;
                    break;
                  }
                  else if($obj1[$key2] != $obj2[$key2]){
                    $obj2_match = false;
                    break;
                  }
                }
              }
              if(!$obj2_match){
                $obj2['_flag'] = 'new';
                $next_value[] = $obj2;
              }
            }
            if(count($next_value) > 0) $updated[$key] = $next_value;

          }
        }

        $updated[$key] = $arr2;

      }
      else{

        if($current[$key] != $next[$key]){

          // Validate next value based on definition
          $next_value = value_validate($next[$key], isset($definition[$key]) ? $definition[$key] : null);

          // Compare by datatype
          if(isset($definition[$key])){
            $datatype = isset($definition[$key]['datatype']) ? $definition[$key]['datatype'] : 'string';
            switch($datatype){
              case 'date':
                if(isdate($next_value) && date('Ymd', strtotime($value)) != date('Ymd', strtotime($next_value)))
                  $updated[$key] = $next_value;
                break;
              case 'int':
                if(intval($value) != intval($next_value))
                  $updated[$key] = intval($next_value);
                break;
              case 'double':
              case 'number':
              case 'money':
                if(floatval($value) !== floatval($next_value))
                  $updated[$key] = floatval($next_value);
                break;
              default:
                $updated[$key] = $next_value;
                break;
            }
          }
          else{
            $updated[$key] = $next_value;
          }

        }

      }

    }


  }
  return $updated;

}



function array_object_compare($arr1, $arr2, $keys, $mode = RESULT_NORMAL){

  $next_value = [];
  foreach($arr1 as $index1=>$obj1){
    $obj1_matched = false;
    foreach($arr2 as $index2=>$obj2){
      $obj2_matched = true;
      foreach($keys as $key1){
        if($obj1[$key1] != $obj2[$key1]){
          $obj2_matched = false;
          break;
        }
      }
      if($obj2_matched){
        $obj1_matched = true;
        break;
      }
    }
    if($obj1_matched){
      $is_modified = [];
      foreach($obj1 as $key1=>$val1){
        if(isset($obj2[$key1]) && $obj1[$key1] != $obj2[$key1]){
          $is_modified[] = $key1;
        }
      }
      if(count($is_modified) > 0){
        if($mode != RESULT_CLEAN){
          $obj2['__flag'] = 'modified';
          $obj2['__modified'] = implode(',', $is_modified);
        }
        $next_value[] = $obj2;
      }
      else{
        if($mode != RESULT_CLEAN) $obj1['__flag'] = 'unchanged';
        $next_value[] = $obj1;
      }
    }
    else{
      if($mode != RESULT_CLEAN){
        $obj1['__flag'] = 'removed';
        $next_value[] = $obj1;
      }
    }
  }
  foreach($arr2 as $index2=>$obj2){
    $obj2_matched = false;
    foreach($arr1 as $index1=>$obj1){
      $obj1_matched = true;
      foreach($keys as $key1){
        if($obj2[$key1] != $obj1[$key1]){
          $obj1_matched = false;
          break;
        }
      }
      if($obj1_matched){
        $obj2_matched = true;
        break;
      }
    }
    if(!$obj2_matched){
      if($mode != RESULT_CLEAN) $obj2['__flag'] = 'new';
      array_splice($next_value, $index2, null, [ $obj2 ]);
    }
  }
  return $next_value;

}

/**
 * Retrieve object value with alternate object
 * - If value not exists in obj1 then retrieve from obj2
 * @param $key
 * @param $obj1
 * @param $obj2
 */
function ova($key, $obj1, $obj2){

  return isset($obj1[$key]) ? $obj1[$key] :
    (isset($obj2[$key]) ? $obj2[$key] : null);

}

function applog($key, $oneliner = '', $type = LOG_INFO, $dest = null){

  global $applog_dest, $applog_keys;

  if(isset($applog_keys) && is_array($applog_keys) && !in_array($key, $applog_keys)) return;

  $dest = $dest == null && $applog_dest ? $applog_dest : $dest;

  $text = '';
  if(is_assoc($oneliner)){
    $text = [];
    foreach($oneliner as $oneliner_key=>$oneliner_val){
      $text[] = $oneliner_key . "=" . $oneliner_val;
    }
    $text = implode(', ', $text);
  }
  else if(is_string($oneliner)){
    $text = str_replace("\r\n", "", $oneliner);
    $text = str_replace("\n", "", $oneliner);
  }

  $text = str_replace(base_path(), '', $text);

  switch($dest){
    case 'db':
      pm("insert into indosps_log.applog (`type`, `date`, `key`, `value`) values (?, ?, ?, ?)", [ $type, date('YmdHis') , $key, $text ]);
      break;
    case 'echo':
      $text = strlen($text) > 160 ? str_pad(substr($text, 0, 200), 200, ' ', STR_PAD_RIGHT) : $text;
      echo "[" . date('H:i:s') . "] " . str_pad(substr($key, 0, 30), 30, ' ', STR_PAD_RIGHT) . " " . $text . PHP_EOL;
      break;
  }

}

/**
 * Queue implementation
 * For:
 * - purchase order save
 * Usage:
 *      queue_add([
 *        [ 'purchaseinvoicecalculate', [ 1, 2, 3 ] ],
 *        ...
 *      ])
 * @param $arr
 */
function queue_add($arr){

  $queue_dir = realpath(__DIR__ . '/../../queue');
  $queue_path = $queue_dir . '/' . md5(uniqid());
  file_put_contents($queue_path, json_encode($arr));

}

?>

