<?php
if(privilege_get('inventoryanalysis', 'list') < 1){ include 'notavailable.php'; return; }

$modulename = 'inventoryformula';

include 'api/inventory.php';

function customheadcolumns(){

  global $module;
  $date = isset($module['presets'][$module['presetidx']]['date']) ? $module['presets'][$module['presetidx']]['date'] : date('Ymd');

  $html = [];
  $html[] = "<td>" . ui_datepicker([
    'id'=>'datepicker1',
    'onchange'=>"ui.async('m_datechanged', [ value ])",
    'value'=>$date,
  ]) . "</td>";
  return implode('', $html);

}

function m_datechanged($date){

  global $module;
  $module['presets'][$module['presetidx']]['date'] = $date;
  m_savestate($module);

  return m_load();

}

function defaultmodule(){

  $columns = array(
    array('active'=>0, 'name'=>'id', 'text'=>'ID', 'width'=>30),
    array('active'=>1, 'name'=>'code', 'text'=>'Kode', 'width'=>80),
    array('active'=>1, 'name'=>'description', 'text'=>'Nama Barang', 'width'=>300),
    array('active'=>0, 'name'=>'unitpurchaseprice', 'text'=>'Harga Beli', 'align'=>'center', 'width'=>130, 'type'=>'html', 'html'=>'if_col0', 'style'=>'padding:0', 'nodittomark'=>1),
    array('active'=>1, 'name'=>'m3', 'text'=>'M3', 'align'=>'center', 'width'=>100, 'type'=>'html', 'html'=>'if_col1', 'style'=>'padding:0', 'nodittomark'=>1),
    array('active'=>1, 'name'=>'costprice', 'text'=>'Harga Modal', 'width'=>100, 'datatype'=>'money', 'nodittomark'=>1),
    array('active'=>1, 'name'=>'soldqty', 'text'=>'Terjual', 'width'=>100, 'datatype'=>'number', 'nodittomark'=>1),

  );

  $presets = array();
  $presets[] = array(
    'text'=>'Semua Barang',
    'columns'=>$columns,
    'sorts'=>[
      [ 'name'=>'description', 'sorttype'=>'asc' ]
    ],
    'viewtype'=>'list'
  );

  $module = array(
    'title'=>'inventoryformula',
    'columns'=>$columns,
    'presets'=>$presets,
    'presetidx'=>0,
    'quickfilterscolumns'=>array(
      array('text'=>'', 'value'=>'code|description&contains&')
    )
  );
  return $module;

}

function datasource($columns = null, $sorts = null, $filters = null, $limits = null){

  $date = isset($module['presets'][$module['presetidx']]['date']) ? $module['presets'][$module['presetidx']]['date'] : date('Ymd');

  $inventory_columnaliases = array(
    'id'=>'t1.id!',
    'code'=>'t1.code',
    'description'=>'t1.description',
    'unitpurchaseprice'=>"'f'",
    'm3'=>"t2.m3",
    'costprice'=>"t1.avgcostprice",
    'soldqty'=>"t1.soldqty"
  );

  global $module;

  $params = [ $date ];
  $columnquery = columnquery_from_columnaliases($columns, $inventory_columnaliases);
  $wherequery = wherequery_from_filters($params, $filters, $inventory_columnaliases);
  $sortquery = sortquery_from_sorts($sorts, $inventory_columnaliases);
  $limitquery = limitquery_from_limitoffset($limits);

  $query = "SELECT $columnquery
    FROM inventory t1 left join 
    (
      SELECT * FROM 
        (SELECT * FROM inventoryformula WHERE `date` <= ? ORDER BY `date` DESC) as t
      GROUP BY inventoryid
    ) as t2
    on t1.id = t2.inventoryid
    $wherequery
    $sortquery
    $limitquery
  ";
  $data = pmrs($query, $params);

  return $data;

}

function if_col0($obj){

  $unitpurchaseprice = ov('unitpurchaseprice', $obj);

  $c = "<div class='align-center'>";
  $c .= ui_dropdown(array(
    'class'=>'dropdown no-border',
    'items'=>[
      [ 'text'=>'(sesuai faktur)', 'value'=>'f' ]
    ],
    'value'=>$unitpurchaseprice,
    'width'=>'100%'
  ));
  $c .= "</div>";
  return $c;

}

function if_col1($obj){

  $id = $obj['id'];
  $m3 = ov('m3', $obj, '');

  $c = "<div class='align-center' style='background: #dceafc'>";
  $c .= ui_textbox(array(
    'align'=>'right',
    'datatype'=>'money',
    'class'=>'no-border',
    'placeholder'=>'M3',
    'width'=>'100%',
    'value'=>$m3,
    'onchange'=>"ui.async('ui_inventoryformula_set', [ ui.datepicker_value(ui('#datepicker1')), $id, 'm3', value ])"
  ));
  $c .= "</div>";
  return $c;

}

function if_col2($obj){

  $id = $obj['id'];
  $cbmperkg = ov('cbmperkg', $obj, '');

  $c = "<div class='align-center'>";
  $c .= ui_textbox(array(
    'align'=>'right',
    'class'=>'no-border',
    'datatype'=>'money',
    'placeholder'=>'CBM/KG',
    'width'=>'100%',
    'value'=>$cbmperkg,
    'onchange'=>"ui.async('ui_inventoryformula_set', [ ui.datepicker_value(ui('#datepicker1')), $id, 'cbmperkg', value ])"
  ));
  $c .= "</div>";
  return $c;

}

function if_col3($obj){

  $id = $obj['id'];
  $freightcharge = ov('freightcharge', $obj);

  return "<div id='iv$id' class='align-right'>" . number_format(floatval($freightcharge), get_round_precision($freightcharge)) . "</div>";

}

function if_col4($obj){

  $id = $obj['id'];
  $costprice = 0;

  return "<div id='ivc$id' class='align-right'>" . number_format(floatval($costprice), get_round_precision($costprice)) . "</div>";

}

function ui_inventoryformula_set($date, $inventoryid, $name, $value){

  $obj = inventoryformula_set($date, $inventoryid, $name, $value);
  $id = $obj['inventoryid'];
  $freightcharge = ov('freightcharge', $obj);

  return "<element exp='#iv$id'>" . number_format(floatval($freightcharge), get_round_precision($freightcharge)) . "</element>";

}

include 'rcfx/dashboard1.php';
?>