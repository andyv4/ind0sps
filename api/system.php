<?php

function systemdb(){

  mysql_droptable('systemvar');

  mysql_createtable('systemvar', array(
    array('type'=>'id', 'name'=>'id'),
    array('type'=>'string', 'name'=>'key', 'maxlength'=>'200'),
    array('type'=>'string', 'name'=>'value', 'maxlength'=>'200'),
    array('type'=>'datetime', 'name'=>'lastupdatedon')
  ));

}

function systemvarget($key, $defaultvalue = ''){

  $exists = pmc("SELECT COUNT(*) FROM systemvar WHERE `key` = ?", array($key));
  if(!$exists) return $defaultvalue;

  $value = pmc("SELECT `value` FROM systemvar WHERE `key` = ? LIMIT 1", array($key), array('log'=>0));
  return $value ? $value : $defaultvalue;

}

function system_date($format, $timestamp = null){

  if(!$timestamp) $timestamp = time();
  return date($format, $timestamp);

}

function systemvarset($key, $value){
  $lastupdatedon = date('YmdHis');
  pm("INSERT INTO systemvar(`key`, `value`, lastupdatedon) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE `value` = ?,
    lastupdatedon = ?", array($key, $value, $lastupdatedon, $value, $lastupdatedon), array('log'=>0));
}

function datacleartransact(){
  global $mysqlpdo_host, $mysqlpdo_username, $mysqlpdo_password, $mysqlpdo_database;
  $mysqli = new mysqli($mysqlpdo_host, $mysqlpdo_username, $mysqlpdo_password, $mysqlpdo_database);
  $mysqli->query('SET foreign_key_checks = 0;');

  $transactiontables = array('inventoryadjustment', 'inventoryadjustmentdetail', 'inventorybalance', 'inventorywarehouse',
      'inventoryopeningvalue', 'notification',
      'journalvoucher', 'journalvoucherdetail', 'log', 'pettycash', 'pettycashdebitaccount', 'purchaseinvoice', 'purchaseinvoiceinventory',
      'purchaseorder', 'purchaseorderinventory', 'salesinvoice', 'salesinvoiceinventory', 'sampleinvoice', 'sampleinvoiceinventory', 'salesinvoicegroup',
      'salesinvoicegroupitem', 'salesreceipt', 'salesreceiptinvoice', 'session', 'vv_inventory7',
      'warehousetransfer', 'warehousetransferinventory', 'code', 'taxcodereservation', 'taxcodereservationpool');

  for($i = 0 ; $i < count($transactiontables) ; $i++){
    $tablename = $transactiontables[$i];
    $mysqli->query("DELETE FROM $tablename");
  }

  $schema = 'indosps';

  $mysqli->query("UPDATE $schema.chartofaccount SET `amount` = 0");
  $mysqli->query("UPDATE $schema.customer SET `receivable` = 0, avgsalesmargin = 0, avgsalesmarginlastupdate = 0");
  $mysqli->query("UPDATE $schema.inventory SET qty = 0, `avgcostprice` = 0, avgsalesmargin = 0, avgsalesmarginlastupdate = 0");
  $mysqli->query("UPDATE $schema.supplier SET `payable` = 0");
  $mysqli->query("UPDATE $schema.warehouse SET `total` = 0");

  $mysqli->query('SET foreign_key_checks = 1;');
  $mysqli->close();

  if(file_exists(__DIR__ . '/../usr/system/.bgp'))
    unlink(__DIR__ . '/../usr/system/.bgp');

}

$__SYSTEMDEBUGS = array();
function systemdebug_add($description, $params = null, $result = null){
  global $__SYSTEMDEBUGS, $requestid;
  $__SYSTEMDEBUGS[] = array('requestid'=>$requestid, 'description'=>$description, 'timestamp'=>microtime(1), 'params'=>serialize($params),
    'result'=>serialize($result));
}
function systemdebug_save(){

  global $__SYSTEMDEBUGS;
  $queries = $params = array();
  foreach($__SYSTEMDEBUGS as $obj){
    $queries[] = "(?, ?, ?, ?, ?)";
    array_push($params, $obj['requestid'], $obj['description'], $obj['timestamp'], $obj['params'], $obj['result']);
  }
  if(count($queries) > 0)
    pm("INSERT INTO systemdebug (requestid, description, `timestamp`, params, result) VALUES " . implode(', ', $queries), $params);

}
function systemdebug_clear(){
  pm("DELETE FROM systemdebug");
}

function system_update081715(){

  // System update
  pm("create table salesinvoicegroupitem (`id` int(10) auto_increment, salesinvoicegroupid int(10), `type` varchar(2), typeid int(10), primary key(`id`), foreign key (salesinvoicegroupid) references salesinvoicegroup(`id`) on delete cascade on update cascade)engine=InnoDB, default charset=utf8;");
  pm("create table sampleinvoice(`id` int(10) auto_increment, code varchar(20), `date` date, customerdescription varchar(100), address varchar(255), warehouseid int(10), note text, createdon datetime, createdby int(10), lastupdatedon datetime, lastupdatedby int(10), primary key(`id`))engine=InnoDB, default charset=utf8;");
  pm("create table sampleinvoiceinventory(`id` int(10) auto_increment, sampleinvoiceid int(10), inventoryid int(10), inventorycode varchar(20), inventorydescription varchar(100), qty double(16,2), unit varchar(100), primary key(`id`), foreign key(sampleinvoiceid) references sampleinvoice(`id`) on update cascade on delete cascade)engine=InnoDB, default charset=utf8;");

  pm("alter table salesinvoice add column isreconciled int(1)");
  pm("alter table salesinvoice drop foreign key `fk_salesinvoice_2`");
  pm("ALTER TABLE `salesinvoice` ADD COLUMN `senttime` DATETIME ");
  pm("ALTER TABLE `salesinvoice` ADD COLUMN `sentby` INT(10)");
  pm("alter table inventory add column purchaseorderqty double(16,2);");
  pm("ALTER TABLE salesinvoice add column issent int(1);");
  pm("alter table salesreturn add note varchar(255);");
  pm("alter table salesreturninventory add warehouseid int(10);");
  pm("delete from purchaseinvoiceinventory where purchaseinvoiceid not in (select id from purchaseinvoice);");
  pm("ALTER TABLE purchaseinvoiceinventory ADD FOREIGN KEY (purchaseinvoiceid) REFERENCES purchaseinvoice(`id`) ON DELETE CASCADE ON UPDATE CASCADE");
  pm("ALTER TABLE purchaseinvoiceinventory MODIFY COLUMN `qty` DOUBLE(16,4) DEFAULT NULL;");
  pm("ALTER TABLE purchaseorderinventory MODIFY COLUMN `qty` DOUBLE(16,4) DEFAULT NULL;");
  pm("ALTER TABLE `salesreturn` ADD COLUMN `ispaid` INT(1) AFTER `lastupdatedby`;");
  pm("ALTER TABLE `salesreturn` ADD COLUMN `returnamount` DOUBLE(16,4) AFTER `ispaid`;");
  pm("ALTER TABLE `salesreturn` ADD COLUMN `salesinvoicegroupid` INT AFTER `returnamount`, ADD COLUMN `isgroup` INT(1) AFTER `salesinvoicegroupid`;");
  pm("ALTER TABLE `chartofaccount` ADD COLUMN `moved` INT(1)");
  pm("ALTER TABLE `user` ADD COLUMN `accesslevel` VARCHAR(15)");
  pm("ALTER TABLE `currency` ADD COLUMN `moved` INT(1)");
  pm("ALTER TABLE `customer` ADD COLUMN `moved` INT(1)");
  pm("ALTER TABLE `inventory` ADD COLUMN `moved` INT(1)");
  pm("ALTER TABLE `journalvoucher` ADD COLUMN `moved` INT(1)");
  pm("ALTER TABLE `pettycash` ADD COLUMN `moved` INT(1)");
  pm("ALTER TABLE `purchaseinvoice` ADD COLUMN `moved` INT(1)");
  pm("ALTER TABLE `inventoryadjustment` ADD COLUMN `moved` INT(1)");
  pm("ALTER TABLE `purchaseorder` ADD COLUMN `moved` INT(1)");
  pm("ALTER TABLE `salesinvoice` ADD COLUMN `moved` INT(1)");
  pm("ALTER TABLE `salesreceipt` ADD COLUMN `moved` INT(1)");
  pm("ALTER TABLE `salesreconcile` ADD COLUMN `moved` INT(1)");
  pm("ALTER TABLE `salesreturn` ADD COLUMN `moved` INT(1)");
  pm("ALTER TABLE `supplier` ADD COLUMN `moved` INT(1)");
  pm("ALTER TABLE `warehouse` ADD COLUMN `moved` INT(1)");

  pm("insert into chartofaccount(`id`, code, `name`, `type`, currencyid, amount, createdon, createdby, accounttype, moved)
values (999, '00.000', 'Belum Dipilih', 'D', 1, 0, NOW(), 1, 'None', 0)");
  pm("insert into `user`(`id`, isactive, `name`, salesable) values (999, 1, '-', 1);");
  pm("insert into chartofaccount(`id`, code, `name`, `type`, currencyid, amount, createdon, createdby, accounttype, moved)
values (62, '500.04', 'Retur Penjualan', 'D', 1, 0, NOW(), 1, 'None', 0);");
  pm("update `user` set accesslevel = 'USER'");

  // Index
  pm("create index salesinvoiceinventory_extidx_0 on salesinvoiceinventory(inventorycode, inventorydescription);");
  pm("create index salesinvoice_extidx_0 on salesinvoice(code, customerdescription);");

  // Generate salesinvoicegroupitem data
  $salesinvoicegroups = pmrs("SELECT `id` FROM salesinvoicegroup");
  $salesinvoiceids = array();
  if(is_array($salesinvoicegroups)){
    foreach($salesinvoicegroups as $salesinvoicegroup){
      $id = $salesinvoicegroup['id'];
      $salesinvoiceids[] = $id;
    }
  }
  $salesinvoices = pmrs("SELECT `id`, salesinvoicegroupid FROM salesinvoice WHERE `id` IN (" . implode(', ', $salesinvoiceids) . ")");
  $salesinvoices = array_index($salesinvoices, array('salesinvoicegroupid'));

  $queries = $params = array();
  foreach($salesinvoicegroups as $salesinvoicegroup){
    $id = $salesinvoicegroup['id'];
    if(isset($salesinvoices[$id])){
      foreach($salesinvoices[$id] as $salesinvoice){
        $queries[] = "(?, ?, ?)";
        array_push($params, $id, 'SI', $salesinvoice['id']);
      }
    }
  }
  pm("INSERT INTO salesinvoicegroupitem (salesinvoicegroupid, `type`, typeid) VALUES " . implode(', ', $queries), $params);

  // Data update
  //pm("update purchaseinvoiceinventory set unitprice = 21.25 where purchaseinvoiceid = 369");

  // PI/15/00038, wrong paymentamount
  // C23 cost price wrong currency

}

function system_update_3_5(){

  try{

    pm("ALTER TABLE `journalvoucher` ADD INDEX `fk_journalvoucher1` (`description` ASC);");
    pm("ALTER TABLE `salesinvoice` ADD INDEX `salesinvoice_code` (`code` ASC);");
    pm("ALTER TABLE `purchaseinvoice` ADD INDEX `fk_purchaseinvoice_code` (`code` ASC);");
    pm("ALTER TABLE `taxcodereservationpool` ADD INDEX `TCR_TYPE_TYPEID` (`type` ASC, `typeid` ASC);");

    pm("CREATE TABLE `code` (
      `type` varchar(3) NOT NULL,
      `year` int(4) NOT NULL,
      `index` int(8) NOT NULL,
      `format` varchar(40) DEFAULT NULL,
      `code` varchar(40) DEFAULT NULL,
      `createdon` datetime DEFAULT NULL,
      `status` int(1) DEFAULT NULL,
      PRIMARY KEY (`type`,`year`,`index`),
      KEY `INDEX1` (`code`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    ");
    pm("ALTER TABLE `inventory` ADD COLUMN `taxable` INT(1) DEFAULT 0;");
    pm("ALTER TABLE `salesinvoice` ADD COLUMN `isactive` INT(1) DEFAULT 1;");
    pm("ALTER TABLE `purchaseinvoice` ADD COLUMN `isactive` INT(1) DEFAULT 1;");
    pm("ALTER TABLE `customer` ADD COLUMN `totalsales` DOUBLE(16,2) DEFAULT 0;");
    pm("ALTER TABLE `supplier` ADD COLUMN `tax_registration_number` VARCHAR(30) DEFAULT '';");
    pm("ALTER TABLE `customer` ADD COLUMN `tax_registration_number` VARCHAR(30) DEFAULT '';");
    pm("ALTER TABLE `salesinvoice` ADD COLUMN `tax_code` VARCHAR(15) DEFAULT '';");
    pm("CREATE TABLE IF NOT EXISTS `taxcodereservation` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `createdon` datetime DEFAULT NULL,
      `type` varchar(2) DEFAULT NULL,
      `prefix` varchar(4) DEFAULT NULL,
      `midfix` varchar(4) NOT NULL,
      `start_index` int(11) DEFAULT NULL,
      `end_index` int(11) DEFAULT NULL,
      `index_length` int(2) DEFAULT NULL,
      PRIMARY KEY (`id`,`midfix`)
    ) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8;");
    pm("CREATE TABLE IF NOT EXISTS `taxcodereservationpool` (
      `code` varchar(50) NOT NULL,
      `tid` int(11) NOT NULL,
      `type` varchar(2) DEFAULT NULL,
      `typeid` int(10) DEFAULT NULL,
      `status` int(1) DEFAULT NULL,
      `order` int(11) DEFAULT NULL,
      PRIMARY KEY (`code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    pm("CREATE TABLE IF NOT EXISTS `userlog` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `action` varchar(100) DEFAULT NULL,
      `data1` longtext,
      `data2` longtext,
      `timestamp` datetime DEFAULT NULL,
      `userid` int(10) DEFAULT NULL,
      `refid` int(10) DEFAULT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=45623 DEFAULT CHARSET=utf8;");
  }
  catch(Exception $ex){
    console_log($ex->getMessage());
  }

  // Code build
  code_build();

  // User default keystore
  $users = pmrs("select `id` from `user`");
  if(is_array($users))
    foreach($users as $user){
      userkeystoreadd($user['id'], 'privilege.salesinvoicetype', 'non-tax');
      userkeystoreadd($user['id'], 'privilege.purchaseinvoicetype', 'non-tax');

      $chartofaccounttype = '';
      if(in_array($user['id'], [ 3, 8, 7 ])) $chartofaccounttype = '*';
      userkeystoreadd($user['id'], 'privilege.chartofaccounttype', $chartofaccounttype);
    }

  systemvarset('salesinvoice_nontax_code', 'SPS/{YEAR}/{INDEX}');
  systemvarset('purchaseinvoice_nontax_code', 'PI/{YEAR}/{INDEX}');

}

function usr_to_template(){

  $available_presets = [];
  $paths = glob(__DIR__ . '/../usr/*');
  foreach($paths as $path){
    if(is_dir($path) && strpos($path, '/system') === false && strpos($path, '/cache') === false){

      $files = glob($path . '/*');
      foreach($files as $file){
        $module = unserialize(file_get_contents($file));
        $presets = isset($module['presets']) ? $module['presets'] : [];
        $title = $module['title'];

        foreach($presets as $preset){
          $text = $preset['text'];

          if(!isset($available_presets[$title])) $available_presets[$title] = [];
          $available_presets[$title][$text] = $preset;

        }

      }

    }
  }

  $path = __DIR__ . '/../usr/template';
  foreach($available_presets as $module=>$presets){

    if(!file_exists($path . '/' . $module)) mkdir($path . '/' . $module);
    foreach($presets as $presetname=>$preset){

      file_put_contents($path . '/' . $module. '/' . $presetname . '.txt', serialize($preset));


    }

  }

}

function system_update_3_1_160(){

  pm("create table category (`id` int(10) auto_increment, `name` varchar(32), frontend_active int(1), primary key(`id`)) engine=InnoDB, default charset=utf8;");
  pm("create table categoryinventory (categoryid int(10), inventoryid int(10), primary key(categoryid, inventoryid),
   foreign key (categoryid) references category(`id`) on update cascade on delete cascade,
   foreign key (inventoryid) references inventory(`id`) on update cascade on delete cascade)engine=InnoDB, default charset=utf8");
  pm("ALTER TABLE `inventory` ADD COLUMN `imageurl` VARCHAR(100)");
  pm("CREATE TABLE `news` (
    `id` INT NOT NULL auto_increment,
    isactive int(1),
    imageurl varchar(200),
    `date` DATE,
    `title` VARCHAR(200),
    `description` TEXT,
    `createdon` DATETIME,
    `createdby` INT,
    `lastupdatedon` DATETIME,
    `lastupdatedby` INT,
    PRIMARY KEY (`id`)
  )
  ENGINE = InnoDB
  CHARACTER SET utf8;
  ");
  pm("ALTER TABLE `inventory` ADD COLUMN `website_isactive` INT(1) ");

}

function system_update100915(){

  pm("create table session (uid varchar(32), userid int(10), starttime datetime, requestcount int(10), lastupdatedon datetime,
    useragent text, remoteip varchar(20), isopen int(1), lasturl varchar(20), dbschema varchar(20), lang varchar(2),
    primary key(uid))engine=InnoDB, default charset=utf8;");
  pm("drop table staff");

}

function system_update042117(){

  pm("CREATE TABLE `inventoryformula` (
  `inventoryid` INT UNSIGNED NOT NULL,
  `date` DATETIME NOT NULL,
  `m3` DOUBLE(19,6) NULL,
  `cbmperkg` DOUBLE(19,6) NULL,
  `freightcharge` DOUBLE(16,2) NULL,
  `lastupdatedon` DATETIME NULL,
  PRIMARY KEY (`inventoryid`, `date`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_bin;");

  pm("ALTER TABLE `inventorybalance` 
ADD COLUMN `detail` TEXT NULL,
ADD COLUMN `qty` DOUBLE(16,2) NULL,
ADD COLUMN `costprice` DOUBLE(16,2) NULL,
ADD INDEX `fk_inventorybalance_3` (`ref` ASC, `refid` ASC);");

  pm("ALTER TABLE `inventory` 
ADD INDEX `fk_inventory_idx_0` (`code` ASC, `description` ASC);");

  pm("ALTER TABLE `customer` 
ADD COLUMN `billingaddress` TEXT NULL AFTER `address`;");

  pm("update inventorybalance set `section` = 1 where `in` > 0;");
  pm("update inventorybalance set `section` = 2 where `out` > 0;");

}

function datarecreatedata2(){

  /********************* START CONFIGURATION *********************/
  $DB_SRC_HOST='localhost';
  $DB_SRC_USER='root';
  $DB_SRC_PASS='webapp';
  $DB_SRC_NAME='indosps';
  $DB_DST_HOST='localhost';
  $DB_DST_USER='root';
  $DB_DST_PASS='webapp';
  $DB_DST_NAME='indosps2';
  /*********************** GRAB OLD SCHEMA ***********************/
  $db1 = mysql_connect($DB_SRC_HOST,$DB_SRC_USER,$DB_SRC_PASS) or die(mysql_error());
  mysql_select_db($DB_SRC_NAME, $db1) or die(mysql_error());
  $result = mysql_query("SHOW TABLES;",$db1) or die(mysql_error());
  $buf="set foreign_key_checks = 0;\n";
  $constraints='';
  $tables = array();

  while($row = mysql_fetch_array($result))
  {
    $result2 = mysql_query("SHOW CREATE TABLE ".$row[0].";",$db1) or die(mysql_error());
    $res = mysql_fetch_array($result2);
    /*console_log($res);
    if(preg_match("/[ ]*CONSTRAINT[ ]+.*\n/",$res[1],$matches))
    {
      $res[1] = preg_replace("/,\n[ ]*CONSTRAINT[ ]+.*\n/","\n",$res[1]);
      $constraints.="ALTER TABLE ".$row[0]." ADD ".trim($matches[0]).";\n";
    }*/
    $buf.=$res[1].";\n";
    $tables[] = $res[0];
  }
  $//buf.=$constraints;
  $buf.="set foreign_key_checks = 1";
  /**************** CREATE NEW DB WITH OLD SCHEMA ****************/
  $db2 = mysql_connect($DB_DST_HOST,$DB_DST_USER,$DB_DST_PASS) or die(mysql_error());
  $sql = 'DROP DATABASE IF EXISTS '.$DB_DST_NAME;
  if(!mysql_query($sql, $db2)) die(mysql_error());
  $sql = 'CREATE DATABASE '.$DB_DST_NAME;
  if(!mysql_query($sql, $db2)) die(mysql_error());
  mysql_select_db($DB_DST_NAME, $db2) or die(mysql_error());
  $queries = explode(';',$buf);
  foreach($queries as $query){
    if(empty($query)) continue;
    mysql_query($query, $db2);
    //if(!mysql_query($query, $db2)) die($query . ', ' . mysql_error());
  }

  // Copy master data
  $mastertables = array(
    'chartofaccount',
    'currency',
    'customer',
    'inventory',
    'staff',
    'user',
    'supplier',
    'warehouse',
  );
  foreach($tables as $table){
    if(in_array($table, $mastertables))
      mysql_query("INSERT INTO $DB_DST_NAME.$table SELECT * FROM $DB_SRC_NAME.$table", $db2);
  }

  // Clear transaction
  global $mysqlpdo_database;
  $last_database = $mysqlpdo_database;
  $mysqlpdo_database = 'indosps2';
  datacleartransact();
  $mysqlpdo_database = $last_database;

  // Generate salesinvoicegroupitem data
  $salesinvoicegroups = pmrs("SELECT `id` FROM salesinvoicegroup");
  $salesinvoiceids = array();
  if(is_array($salesinvoicegroups)){
    foreach($salesinvoicegroups as $salesinvoicegroup){
      $id = $salesinvoicegroup['id'];
      $salesinvoiceids[] = $id;
    }
  }
  $salesinvoices = pmrs("SELECT `id`, salesinvoicegroupid FROM salesinvoice WHERE `id` IN (" . implode(', ', $salesinvoiceids) . ")");
  $salesinvoices = array_index($salesinvoices, array('salesinvoicegroupid'));

  $queries = $params = array();
  foreach($salesinvoicegroups as $salesinvoicegroup){
    $id = $salesinvoicegroup['id'];
    if(isset($salesinvoices[$id])){
      foreach($salesinvoices[$id] as $salesinvoice){
        $queries[] = "(?, ?, ?)";
        array_push($params, $id, 'SI', $salesinvoice['id']);
      }
    }
  }
  //console_log($queries);
  pm("INSERT INTO salesinvoicegroupitem (salesinvoicegroupid, `type`, typeid) VALUES " . implode(', ', $queries), $params);

}

function system_presetidx_set($path, $index){

  if(file_exists($path)){

    $presets = file_get_contents($path);
    $presets = unserialize($presets);

    if(isset($presets['presets']) && is_array($presets['presets']) && $index >= 0 && $index < count($presets['presets'])){
      $presets['presetidx'] = $index;
      file_put_contents($path, serialize($presets));
      echo "<script>alert('Updated')</script>";
    }
    else{
      echo "<script>alert('Not Updated')</script>";
    }

    console_log($presets);


  }
  else{
    throw new Exception('Not exists');
  }

}

function system_worker_run($resume = false){

  global $__SYSTEM_WORKER_PAUSED;
  if($resume) $__SYSTEM_WORKER_PAUSED = false;
  if(!$__SYSTEM_WORKER_PAUSED){
    $path =  __DIR__ . '/../cmd/bgp.php' . ((isset($_SESSION['db']) && strlen($_SESSION['db']) > 0) ? ' ' . $_SESSION['db'] : '');
    $cmd = "/usr/bin/php $path > /dev/null 2>/dev/null &";
    $response = shell_exec($cmd);
  }

}
function system_worker_pause(){

  global $__SYSTEM_WORKER_PAUSED;
  $__SYSTEM_WORKER_PAUSED = true;

}

function system_presets_update(){

  // Available modules
  $modules = [
    md5('salesinvoice')=>'salesinvoice',
  ];


  $dirs = glob(__DIR__ . '/../usr/*', GLOB_ONLYDIR);
  foreach($dirs as $path){

    $dirname = basename($path);

    if(strlen($dirname) == 32){

      //echo $dirname . PHP_EOL;

      $mod_dirs = glob($path . '/*');

      //echo $path . '/*' . PHP_EOL;

      foreach($mod_dirs as $mod_path){

        $modulename = isset($modules[basename($mod_path)]) ? $modules[basename($mod_path)] : '';

        if($modulename){
          echo $mod_path . PHP_EOL;

          $module = unserialize(file_get_contents($mod_path));
          $module_columns = salesinvoice_ui_columns();
          $module_presets = salesinvoice_ui_presets();



        }


      }

    }

    break;

  }

}

function update_presets($modulename){

  switch($modulename){

    case 'salesinvoice':
      $files = glob('usr/*/' . md5($modulename));
      $columns = salesinvoice_uicolumns();
      foreach($files as $file){

        

      }
      break;

  }
  
}

?>