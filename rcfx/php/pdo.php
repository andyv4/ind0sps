<?php

$pdo_con = null;
$pdo_logs = array();
$pdo_transaction_counter = 0;

function pdo_con($emulate_prepare = true, $forceinit = false){
  global $pdo_con, $mysqlpdo_database, $mysqlpdo_username, $mysqlpdo_password, $mysqlpdo_host;
  if(!$pdo_con || $forceinit){
    $pdo_con = new PDO("mysql:dbname=$mysqlpdo_database;host=$mysqlpdo_host;charset=utf8", $mysqlpdo_username, $mysqlpdo_password);
    $pdo_con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo_con->setAttribute(PDO::ATTR_TIMEOUT, 1);
    //$pdo_con->setAttribute(PDO::ATTR_PERSISTENT, true);
    $pdo_con->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
    $pdo_con->exec("set names utf8");
  }
  return $pdo_con;
}
function pdo_transact($callable){

  $result = null; // Initial result

  $pdo_con = pdo_con();

  $pdo_con->beginTransaction();

  try{

    $result = call_user_func_array($callable, []);

    $pdo_con->commit();

  }
  catch(Exception $ex){

    file_put_contents(app_dir() . '/usr/system/error-query.log', json_encode([
        date('Y-m-d H:i:s'),
        $ex->getMessage()
      ], JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);

    $pdo_con->rollBack();

  }

  return $result;

}
function pdo_begin_transaction(){

  global $pdo_transaction_counter;

  $pdo_con = pdo_con();

  if($pdo_con->inTransaction()){
    $pdo_transaction_counter++;
    return;
  }

  $pdo_con->beginTransaction();
  $pdo_transaction_counter = 0;
  return $pdo_con;

}
function pdo_commit(){

  global $pdo_transaction_counter;

  if($pdo_transaction_counter > 0){
    $pdo_transaction_counter--;
    return;
  }

  $pdo_con = pdo_con();
  if($pdo_con->inTransaction())
    $pdo_con->commit();
  return $pdo_con;

}
function pdo_rollback(){

  $pdo_con = pdo_con();
  if($pdo_con->inTransaction())
    $pdo_con->rollBack();
  return $pdo_con;

}
function pdo_close(){
  global $pdo_con;
  if($pdo_con) $pdo_con = null;
}
function pm($query, $arr = null, $params = null){

  try{
    $pdo_con = pdo_con(true);
    $pdo_res = $pdo_con->prepare($query);
    $pdo_res->execute($arr);

  }
  catch(Exception $ex){

    file_put_contents(app_dir() . '/usr/system/error-query.log', json_encode([
        date('Y-m-d H:i:s'),
        $query,
        $params,
        $ex->getMessage()
      ], JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
    throw $ex;

  }

}
function pmc($query, $arr = null, $params = null){

  try{

    $pdo_con = pdo_con(true);
    $pdo_res = $pdo_con->prepare($query);
    $pdo_res->setFetchMode(PDO::FETCH_ASSOC);
    $pdo_res->execute($arr);
    $value = null;
    while($row = $pdo_res->fetch()){
      foreach($row as $key=>$val){
        $value = $val;
        break;
      }
    }
    return $value;

  }
  catch(Exception $ex){

    file_put_contents(app_dir() . '/usr/system/error-query.log', json_encode([
        date('Y-m-d H:i:s'),
        $query,
        $params,
        $ex->getMessage()
      ], JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
    throw $ex;

  }
}
function pmi($query, $arr = null, $params = null){

  try{

    $pdo_con = pdo_con(true);
    $pdo_res = $pdo_con->prepare($query);
    $pdo_res->execute($arr);
    $id = $pdo_con->lastInsertId();
    return $id;

  }
  catch(Exception $ex){

    file_put_contents(app_dir() . '/usr/system/error-query.log', json_encode([
        date('Y-m-d H:i:s'),
        $query,
        $params,
        $ex->getMessage()
      ], JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
    throw $ex;

  }

}
function pmr($query, $arr = null, $params = null){

  try{

    $pdo_con = pdo_con(true);
    $pdo_res = $pdo_con->prepare($query);
    $pdo_res->setFetchMode(PDO::FETCH_ASSOC);
    $pdo_res->execute($arr);
    while($row = $pdo_res->fetch())
      return $row;

  }
  catch(Exception $ex){

    file_put_contents(app_dir() . '/usr/system/error-query.log', json_encode([
        date('Y-m-d H:i:s'),
        $query,
        $params,
        $ex->getMessage()
      ], JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
    throw $ex;

  }

}
function pmrs($query, $arr = null, $params = null){

  try{

    $pdo_con = pdo_con(true);
    $pdo_res = $pdo_con->prepare($query);
    $pdo_res->setFetchMode(PDO::FETCH_ASSOC);
    $pdo_res->execute($arr);
    $items = array();
    while($row = $pdo_res->fetch()) {
      array_push($items, $row);
    }
    return $items;

  }
  catch(Exception $ex){

    file_put_contents(app_dir() . '/usr/system/error-query.log', json_encode([
      date('Y-m-d H:i:s'),
      $query,
      $params,
      $ex->getMessage()
    ], JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
    throw $ex;

  }
}

function mysql_createtable($name, $columns){
  if(gettype($name) != "string" || !is_array($columns)) return;

  global $mysqlpdo_database;

  $childtables = array();

  $columnqueries = array();
  $primarykeys = array();
  $foreignkeys = array();
  $foreignkeycount = 0;
  foreach($columns as $properties){
    $key = $properties['name'];
    switch($properties["type"]){
      case "date" :
        array_push($columnqueries, "`$key` DATE");
        break;
      case "datetime" :
        array_push($columnqueries, "`$key` DATETIME");
        break;
      case "double" :
        $maxlength = ov("maxlength", $properties, "10,2");
        array_push($columnqueries, "`$key` DOUBLE($maxlength)");
        break;
      case "fk" :
        $fkref = ov("fkref", $properties);
        $fkrefid = ov("fkrefid", $properties);
        $fkonupdate = ov('fkonupdate', $properties, 0, 'CASCADE');
        $fkondelete = ov('fkondelete', $properties, 0, 'CASCADE');
        array_push($columnqueries, "`$key` INT(10)");
        $foreignkeys[] = "CONSTRAINT `fk_" . $name . "_" . $foreignkeycount . "` FOREIGN KEY (`$key`) REFERENCES $fkref(`$fkrefid`) ON UPDATE $fkonupdate ON DELETE $fkondelete";
        $foreignkeycount++;
        break;
      case 'pf' :
        array_push($primarykeys, "`$key`");
        $fkref = ov("fkref", $properties);
        $fkrefid = ov("fkrefid", $properties);
        array_push($columnqueries, "`$key` INT(10)");
        $foreignkeys[] = "CONSTRAINT `fk_" . $name . "_" . $foreignkeycount . "` FOREIGN KEY (`$key`) REFERENCES $fkref(`$fkrefid`) ON UPDATE CASCADE ON DELETE CASCADE";
        $foreignkeycount++;
        break;
      case "table" :
        $childtables[$key] = $properties;
        break;
      case "id" :
        array_push($columnqueries, "`$key` INT(10) AUTO_INCREMENT");
        array_push($primarykeys, "`$key`");
        break;
      case "int" :
        $maxlength = ov("maxlength", $properties, false, 10);
        array_push($columnqueries, "`$key` INT($maxlength)");
        break;
      case "money" :
        array_push($columnqueries, "`$key` DOUBLE(14, 2)");
        break;
      case "string" :
        $maxlength = ov("maxlength", $properties, false, 40);
        array_push($columnqueries, "`$key` VARCHAR($maxlength)");
        break;
      case "text" :
        array_push($columnqueries, "`$key` TEXT");
        break;
      case "timestamp" :
        array_push($columnqueries, "`$key` DOUBLE(16, 4)");
        break;
    }
  }
  if(count($primarykeys) > 0)
    array_push($columnqueries, "PRIMARY KEY(" . implode(", ", $primarykeys) . ")");
  if(count($foreignkeys) > 0)
    for($i = 0 ; $i < count($foreignkeys) ; $i++)
      array_push($columnqueries, $foreignkeys[$i]);
  $columnquery = implode(", ", $columnqueries);

  $query = "CREATE DATABASE IF NOT EXISTS $mysqlpdo_database";
  pm($query);

  $query = "CREATE TABLE IF NOT EXISTS $mysqlpdo_database.$name($columnquery) ENGINE=InnoDB DEFAULT CHARSET=utf8";
  pm($query);

  foreach($childtables as $name=>$properties){
    $childfks = mysql_createtable($mysqlpdo_database, $name, $properties["columns"]);
    $foreignkeys = array_merge($foreignkeys, $childfks);
  }

  return $query;
}
function mysql_droptable($tablename){
  global $mysqlpdo_host, $mysqlpdo_username, $mysqlpdo_password, $mysqlpdo_database;
  $mysqli = new mysqli($mysqlpdo_host, $mysqlpdo_username, $mysqlpdo_password, $mysqlpdo_database);
  $mysqli->query('SET foreign_key_checks = 0;');
  $mysqli->query('DROP TABLE IF EXISTS '. $tablename .';');
  $mysqli->query('SET foreign_key_checks = 1;');
  $mysqli->close();
}
function mysql_insert_row($name, $obj, $properties = null){

  global $mysqlpdo_database;
  $database = isset($properties['database']) ? $properties['database'] : $mysqlpdo_database;

  // Retrieve table fields
  // returns: array object
  // - Field
  // - Type
  // - Null [YES|NO]
  // - Key [PRI|MUL|<empty>]
  // - Default
  // - Extra
  $table_columns = pmrs("show columns from $database.$name");
  $table_columns = array_index($table_columns, [ 'Field' ], true);

  $columns = $values = $params = [];
  foreach($obj as $key=>$value){
    if(isset($table_columns[$key])){
      $columns[] = "`$key`";
      $values[] = "?";
      array_push($params, $value);
    }
  }

  $id = 0;
  if(count($columns) > 0)
    $id = pmi("INSERT INTO `$database`.`$name` (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ")", $params);
  return $id;

}
function mysql_update_row($name, $obj, $condition, $properties = null){

  global $mysqlpdo_database;
  $database = isset($properties['database']) ? $properties['database'] : $mysqlpdo_database;

  $skip_table_checking = isset($properties['skip_table_checking']) && $properties['skip_table_checking'] ? true : false;

  // Retrieve table fields
  // returns: array object
  // - Field
  // - Type
  // - Null [YES|NO]
  // - Key [PRI|MUL|<empty>]
  // - Default
  // - Extra
  if(!$skip_table_checking){
    $table_columns = pmrs("show columns from $database.$name");
    $table_columns = array_index($table_columns, [ 'Field' ], true);
  }

  $params = array();
  $columns = array();
  foreach($obj as $key=>$value){
    if($skip_table_checking || (!$skip_table_checking && isset($table_columns[$key]))){
      $columns[] = "`$key` = ?";
      $params[] = $value;
    }
  }

  if(count($columns) > 0){
    $columnquery = implode(', ', $columns);

    $wheres = array();
    foreach($condition as $key=>$value){
      $wheres[] = "`$key` = ?";
      $params[] = $value;
    }
    $wherequery = implode(' AND ', $wheres);

    $query = "UPDATE `$database`.`$name` SET $columnquery WHERE $wherequery";
    pm($query, $params);
  }

}
function mysql_delete_row($name, $condition, $properties = null){
  global $mysqlpdo_database;
  $database = isset($properties['database']) ? $properties['database'] : $mysqlpdo_database;

  $params = array();
  $wheres = array();
  foreach($condition as $key=>$value){
    $wheres[] = "`$key` = ?";
    $params[] = $value;
  }
  $wherequery = implode(', ', $wheres);

  $query = "DELETE FROM `$database`.`$name` WHERE $wherequery";
  pm($query, $params);

}
function mysql_insert($name, $arr){
  for($i = 0 ; $i < count($arr) ; $i++){
    $obj = $arr[$i];

    $valuequeries = array();
    $columns = array();
    $params = array();
    foreach($obj as $key=>$value){
      $columns[] = '`' . $key . '`';
      $params[] = $value;
      $valuequeries[] = '?';
    }
    $columnquery = implode(', ', $columns);
    $valuequery = implode(', ', $valuequeries);
    $query = "INSERT INTO $name ($columnquery) VALUES ($valuequery)";
    pm($query, $params);
  }
}
function mysql_get_row($name, $filters, $columns, $properties = null, $flag = 0){
  try{
    global $mysqlpdo_database;
    $database = isset($properties['database']) ? $properties['database'] : $mysqlpdo_database;
    $columnqueries = array();
    if($columns == null){
      $columnqueries[] = '*';
    }
    else{
      for($i = 0 ; $i < count($columns) ; $i++){
        if($columns[$i] == '*'){ $columnqueries[] = '*'; break; }
        else
          $columnqueries[] = "`" . $columns[$i] . "`";
      }
    }
    $where = array();
    $params = array();
    foreach($filters as $key=>$value){
      $where[] = "`$key` = ?";
      $params[] = $value;
    }
    $query = "SELECT " . implode(', ', $columnqueries) . " FROM $name";
    if(count($where) > 0) $query .= " WHERE " . implode(' AND ', $where);
    if($flag == 2) exc([ $query, $params ]);
    return pmr($query, $params);
  }
  catch(Exception $ex){
    $c = $ex->getMessage() . "\n";
    $c .= "name: $name \n";
    $c .= "filters: " . json_encode($filters) . " \n";
    $c .= "columns: " . json_encode($columns);
    throw new Exception($c);
  }
}
function mysql_get_rows($name, $columns = null, $filters = null, $properties = null){
  try{
    global $mysqlpdo_database;
    $database = isset($properties['database']) ? $properties['database'] : $mysqlpdo_database;
    $columnqueries = array();
    if($columns == null){
      $columnqueries[] = '*';
    }
    else{
      for($i = 0 ; $i < count($columns) ; $i++){
        if($columns[$i] == '*'){ $columnqueries[] = '*'; break; }
        else
          $columnqueries[] = "`" . $columns[$i] . "`";
      }
    }
    $where = array();
    $params = array();
    if(is_assoc($filters))
      foreach($filters as $key=>$value){
        $where[] = "`$key` = ?";
        $params[] = $value;
      }
    $query = "SELECT " . implode(', ', $columnqueries) . " FROM $database.$name";
    if(count($where) > 0) $query .= " WHERE " . implode(' AND ', $where);
    return pmrs($query, $params);
  }
  catch(Exception $ex){
    $c = $ex->getMessage() . "\n";
    $c .= "name: $name <br />\n";
    $c .= "filters: " . print_r($filters, 1) . " <br />\n";
    $c .= "columns: " . print_r($columns, 1) . " <br />\n";
    $c .= "columns: " . print_r($query, 1) . " <br />\n";
    $c .= "columns: " . print_r($params, 1) . " <br />\n";
    throw new Exception($c);
  }
}
function mysql_dropschema($schema){
  pm("DROP DATABASE IF EXISTS `$schema`");
}
function mysql_createschema($schema){
  pm("CREATE DATABASE IF NOT EXISTS `$schema`");
}

function mysqli_exec_multiples($queries){

  global $mysqlpdo_database, $mysqlpdo_username, $mysqlpdo_password, $mysqlpdo_host;
  $maxsize = 2000;
  $count = count($queries);
  $results = array();

  $mysqli = new mysqli($mysqlpdo_host, $mysqlpdo_username, $mysqlpdo_password, $mysqlpdo_database);
  if(mysqli_connect_errno()) throw new Exception(mysqli_connect_error());

  for($i = 0 ; $i < ceil($count / $maxsize) ; $i++){

    $current_queries = array_splice($queries, 0, count($queries) > $maxsize ? $maxsize : count($queries));
    $current_queries = implode(';', $current_queries);

    if ($mysqli->multi_query($current_queries)) {

      do {
        if ($result = $mysqli->store_result()) {
          $rows = array();
          while($row = $result->fetch_row())
            $rows[] = $row;
          $results[] = $rows;
          $result->free();
        }
      }
      while ($mysqli->next_result());

    }

  }

  $mysqli->close();

  return $results;

}

?>