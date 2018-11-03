<?php

ui_async();

class testA{

  public function handle(){

    echo '1';

//    $id = pmi("insert into purchaseorder(`date`) values (?)", [ '2018-01-01' ]);
//    pm("insert into purchaseorderinventory (purchaseorderid) values (?)", [ $id ]);

  }

}


$callable = new testA;
echo serialize($callable);
$testA = unserialize($callable);
$testA->handle();
//pdo_transact();

?>