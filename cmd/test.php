<?php

/*$pdo_con = new PDO('mysql:host=127.0.0.1;dbname=indosps', 'root', 'webapp', array(
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_EMULATE_PREPARES => false
));*/

require_once __DIR__ . '/../rcfx/php/pdo.php';

$mysqlpdo_host = '127.0.0.1';
$mysqlpdo_database = 'indosps';
$mysqlpdo_username = 'root';
$mysqlpdo_password = 'webapp';

$pdo_con = pdo_con();
$pdo_con = pdo_con();

$pdo_con->beginTransaction();

try{

  $query = "insert into purchaseorder(`date`) values (?)";
  $statement = $pdo_con->prepare($query);
  $statement->execute([
    '2018-01-01'
  ]);

  $id = $pdo_con->lastInsertId();

  $query = "insert into purchaseorderinventory (purchaseorderid) values (?)";
  $statement = $pdo_con->prepare($query);
  $statement->execute([
    $id
  ]);

  $pdo_con->commit();

  echo "OK";

}
catch(Exception $e){

  echo $e->getMessage();

  $pdo_con->rollBack();

}