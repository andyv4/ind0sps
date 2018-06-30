<?php

define('CODE_STATUS_RESERVED', 1);
define('CODE_STATUS_COMMITTED', 2);

function code_reserve($type, $year, $format){

  if(!$format) exc("Please input valid code format in Company page for $type");
  $start_separator = '{';
  $end_separator = '}';

  // Retrieve all existing index
  // - Exlusion
  if($type == 'SIN' && date('Y') == 2017)
    $index = pmc("select `index` from code where `type` = ? and `year` = ? AND `status` = 0 AND `index` != 8 ORDER BY `index` LIMIT 1", [ $type, $year ]);
  // - Normal
  else
    $index = pmc("select `index` from code where `type` = ? and `year` = ? AND `status` = 0 ORDER BY `index` LIMIT 1", [ $type, $year ]);

  if(!$index) $index = 1;

  // Generate code based on format
  $code = $format;
  preg_match_all('/' . $start_separator . '\w+' . $end_separator . '/', $code, $matches);
  if(isset($matches[0]) && is_array($matches[0])){
    foreach($matches[0] as $match){
      $key = str_replace($start_separator, '', str_replace($end_separator, '', $match));
      $value = '';
      switch(strtolower($key)){
        case 'year':
          $year2 = date('y', mktime(0, 0, 0, 1, 1, $year));
          $code = str_replace($match, $year2, $code);
          break;
        case 'index':
          $index_padded = str_pad(substr($index, 0, 5), 5, '0', STR_PAD_LEFT);
          $code = str_replace($match, $index_padded, $code);
          break;
      }

    }
  }

  // Check if code exists in the module
  switch($type){

    case 'PIN':
    case 'PIT':
      $exists = pmc("select count(*) from purchaseinvoice where code = ?", [ $code ]);
      if($exists){
        pm("update code set `format` = ?, `code` = ?, `status` = ? where `type` = ? and `year` = ? and `index` = ?",
          [ $format, $code, CODE_STATUS_RESERVED, $type, $year, $index ]);
        $code = code_reserve($type, $year, $format);
      }
      break;

    case 'SIN':
    case 'SIT':
      $exists = pmc("select count(*) from salesinvoice where code = ?", [ $code ]);
      if($exists){
        pm("update code set `format` = ?, `code` = ?, `status` = ? where `type` = ? and `year` = ? and `index` = ?",
          [ $format, $code, CODE_STATUS_RESERVED, $type, $year, $index ]);
        $code = code_reserve($type, $year, $format);
      }
      break;
      
  }

  // Save code
  pm("update code set `format` = ?, `code` = ?, `status` = ? where `type` = ? and `year` = ? and `index` = ?",
    [ $format, $code, CODE_STATUS_RESERVED, $type, $year, $index ]);

  return $code;

}

function code_remove($code){

  pm("update code set `status` = 0, `format` = '', `code` = '' where `code` = ?", [ $code ]);

}

function code_release($code){

  pm("update code set `status` = 0, `format` = '', `code` = '' where `code` = ?", [ $code ]);

}

function code_commit($code){

  // Update code data
  pm("update `code` set `status` = ? where `code` = ?", [ CODE_STATUS_COMMITTED, $code ]);

}

function code_build($year = null){

  if(!$year) $year = date('Y');
  $year_2 = substr($year, 2);

  $types = [
    'SI'=>[ 'SIN', 'SIT' ],
    'PI'=>[ 'PIN', 'PIT' ],
  ];

  $start_range = 1;
  $end_range = 99999;
  $createdon = date('YmdHis');
  $query = $params = [];

  // Sales invoice existing index
  $salesinvoices = pmrs("select code from salesinvoice where code like 'SPS/{$year_2}/%'");
  $salesinvoice_indexes = [];
  if(is_array($salesinvoices))
    foreach($salesinvoices as $salesinvoice){
      $index = intval(str_replace("SPS/{$year_2}/", '', $salesinvoice['code']));
      $salesinvoice_indexes[$index] = $salesinvoice['code'];
    }

  // Purchase invoice existing index
  $purchaseinvoices = pmrs("select code from purchaseinvoice where code like 'PI/{$year_2}/%'");
  $purchaseinvoice_indexes = [];
  if(is_array($purchaseinvoices))
    foreach($purchaseinvoices as $purchaseinvoice){
      $index = intval(str_replace("PI/{$year_2}/", '', $purchaseinvoice['code']));
      $purchaseinvoice_indexes[$index] = $purchaseinvoice['code'];
    }

  foreach($types as $type=>$subtypes){
    foreach($subtypes as $subtype){

      for($index = $start_range ; $index <= $end_range ; $index++){

        $status = 0;
        $format = '';
        $code = '';
        switch($subtype){
          case 'SIN':
            if(isset($salesinvoice_indexes[$index])){
              $status = 1;
              $code = $salesinvoice_indexes[$index];
              $format = 'SPS/{YEAR}/{INDEX}';
            }
            break;
          case 'PIN':
            if(isset($purchaseinvoice_indexes[$index])){
              $status = 1;
              $code = $purchaseinvoice_indexes[$index];
              $format = 'PPI/{YEAR}/{INDEX}';
            }
            break;
        }

        $query[] = "(?, ?, ?, ?, ?, ?, ?)";
        array_push($params, $subtype, $year, $index, $format, $code, $createdon, $status);

        if(count($query) > 10000){
          pm("INSERT INTO code (`type`, `year`, `index`, `format`, `code`, createdon, `status`) VALUES " . implode(',', $query) .
            ' ON DUPLICATE KEY UPDATE `status` = VALUES(`status`), `code` = VALUES(`code`), `format` = VALUES(`format`)', $params);
          $query = $params = [];
        }

      }

    }
  }

  if(count($query) > 0){
    pm("INSERT INTO code (`type`, `year`, `index`, `format`, `code`, createdon, `status`) VALUES " . implode(',', $query) .
      ' ON DUPLICATE KEY UPDATE `status` = VALUES(`status`), `code` = VALUES(`code`), `format` = VALUES(`format`)', $params);
  }


}

function code_release_unused(){

  pm("UPDATE code SET `format` = '', `code` = '', `status` = 0 where `year` = ? AND (type = 'SIN' OR type = 'SIT') AND 
    code != '' and code not in (select code from salesinvoice where year(`date`) = ?);", [ date('Y'), date('Y') ]);
  pm("UPDATE code SET `format` = '', `code` = '', `status` = 0 where `year` = ? AND (type = 'PIN' OR type = 'PIT') AND 
    code != '' and code not in (select code from purchaseinvoice where year(`date`) = ?);", [ date('Y'), date('Y') ]);

}


?>