<?php
if(privilege_get('inventory', 'list') < 1){ include 'notavailable.php'; return; }

$modulename = 'inventory2';
$deletable = privilege_get('inventory', 'delete');
$user_privilege_costprice = privilege_get('inventory', 'costprice');

require_once 'api/inventory.php';
require_once 'api/warehouse.php';
require_once 'ui/inventory.php';

function defaultcolumns(){

  $columns = array(
    array('active'=>1, 'name'=>'options', 'text'=>'', 'width'=>40, 'type'=>'html', 'html'=>'grid_options'),
    array('active'=>0, 'name'=>'type', 'text'=>'Tipe', 'width'=>30),
    array('active'=>0, 'name'=>'id', 'text'=>'Id', 'width'=>30),
    array('active'=>0, 'name'=>'isactive', 'text'=>'Aktif', 'align'=>'center', 'width'=>50, 'type'=>'html', 'html'=>'grid_isactive'),
    array('active'=>0, 'name'=>'taxable', 'text'=>'Pajak', 'width'=>30, 'type'=>'html', 'html'=>'grid_taxable'),
    array('active'=>1, 'name'=>'code', 'text'=>'Kode', 'width'=>60),
    array('active'=>1, 'name'=>'description', 'text'=>'Nama Barang', 'width'=>300),
    array('active'=>1, 'name'=>'qty', 'text'=>'Kts', 'width'=>80, 'datatype'=>'number', 'type'=>'html', 'html'=>'grid_qty'),
    array('active'=>1, 'name'=>'unit', 'text'=>'Satuan', 'width'=>48),
    array('active'=>1, 'name'=>'price', 'text'=>'Harga Jual', 'width'=>80, 'datatype'=>'money'),
    array('active'=>1, 'name'=>'lowestprice', 'text'=>'Harga Jual Terendah', 'width'=>80, 'datatype'=>'money'),
    array('active'=>1, 'name'=>'avgcostprice', 'text'=>'Harga Modal', 'width'=>80, 'datatype'=>'money'),
    array('active'=>1, 'name'=>'categories', 'text'=>'Kategori', 'width'=>200, 'type'=>'html', 'html'=>'grid_categories'),
    array('active'=>0, 'name'=>'purchaseorderqty', 'text'=>'Kts Dipesan', 'width'=>50, 'datatype'=>'number'),
    array('active'=>0, 'name'=>'minqty', 'text'=>'Minimum Kts', 'width'=>50, 'datatype'=>'number'),
    array('active'=>0, 'name'=>'avgsalesmargin', 'text'=>'Margin Penjualan Rata2', 'width'=>50, 'datatype'=>'number'),
    array('active'=>1, 'name'=>'unitvolume', 'text'=>'Unit Volume', 'align'=>'center', 'width'=>100, 'type'=>'html', 'html'=>'grid_unitvolume', 'style'=>'padding:0', 'nodittomark'=>1),
    array('active'=>0, 'name'=>'createdon', 'text'=>'Dibuat Pada', 'width'=>100, 'datatype'=>'date')
  );

  if($_SESSION['user']['id'] == 1004){
    array_splice($columns, 2, 1);
    array_splice($columns, 7, 8);
  }
  return $columns;

}
function defaultpresets(){

  $columns = defaultcolumns();
  $presets = array();
  $presets[] = array(
    'text'=>'Semua Barang',
    'columns'=>columns_active_set($columns, [ 'options', 'code', 'description', 'qty', 'unit', 'price', 'lowestprice' ]),
    'viewtype'=>'list'
  );

  if($_SESSION['user']['id'] != 1004) {
    $presets[] = array(
      'text' => 'Harga Modal',
      'columns' => columns_active_set($columns, ['categories', 'options', 'code', 'description', 'qty', 'unit', 'price', 'avgcostprice', 'unitvolume']),
      'viewtype' => 'list'
    );
    $presets[] = array(
      'text' => 'Barang Kena Pajak',
      'columns' => columns_active_set($columns, ['categories', 'options', 'code', 'description', 'qty', 'unit', 'price', 'avgcostprice', 'unitvolume']),
      'filters' => [
        ['name' => 'taxable', 'operator' => '=', 'value' => '1']
      ],
      'viewtype' => 'list'
    );
  }

  return $presets;

}
function defaultmodule(){

  $columns = defaultcolumns();
  $presets = defaultpresets();

  $module = array(
    'title'=>'inventory',
    'columns'=>$columns,
    'presets'=>$presets,
    'presetidx'=>0,
    'quickfilterscolumns'=>array(
        array('text'=>'', 'value'=>'code|description&contains&')
    )
  );
  return $module;

}

function m_quickfilter_custom($param){

  $hint = ov('hint', $param);
  $items = [];
  $items[] = array('text'=>"Kode Barang: $hint", 'value'=>"code&contains&" . $hint);
  $items[] = array('text'=>"Nama Barang: $hint", 'value'=>"description&contains&" . $hint);
  $items[] = array('text'=>"Hanya Pajak", 'value'=>"taxable&equals&1");
  $items[] = array('text'=>"Hanya Non Pajak", 'value'=>"taxable&equals&0");

  return $items;

}
function m_quickfilter_apply_custom($value, $operator = 'and'){

  global $module;
  $presetidx = $module['presetidx'];
  $module['presets'][$presetidx]['quickfilters'] = $value;
  $module['presets'][$presetidx]['quickfilters_operator'] = $operator;
  m_savestate($module);
  return m_load();

}
function m_quickfilter_items_from_value_custom($value){

  $arr = explode(',', $value);
  $results = [];
  foreach($arr as $obj){
    if(empty($obj)) continue;
    $text = $obj;
    if(strpos($text, 'code&contains&') !== false) $text = 'Kode Barang: ' . str_replace('code&contains&', '', $text);
    else if(strpos($text, 'description&contains&') !== false) $text = 'Nama Barang: ' . str_replace('description&contains&', '', $text);
    else if(strpos($text, 'taxable&equals&1') !== false) $text = 'Hanya Pajak';
    else if(strpos($text, 'taxable&equals&0') !== false) $text = 'Hanya Non Pajak';
    $results[] = array('text'=>$text, 'value'=>$obj);
  }
  return $results;

}

function datasource($columns = null, $sorts = null, $filters = null, $limits = null, $groups = null){

  if(!is_array($filters)) $filters = [];

  if($_SESSION['user']['id'] == 1004){
    if(!isset($filters) || !is_array($filters)) $filters = [];
    $filters[] = [
      'name'=>'code',
      'operator'=>'contains',
      'value'=>'sp0'
    ];
    $filters[] = [
      'name'=>'taxable',
      'operator'=>'=',
      'value'=>'0'
    ];
  }

  return inventorylist($columns, $sorts, $filters, $limits, $groups);

}

function customheadcolumns(){

  $html = array();
  if(privilege_get('inventory', 'new')) $html[] = "<td><button class='blue' onclick=\"ui.async('ui_inventorydetail', [ null, 'write' ], { waitel:this })\"><span class='mdi mdi-plus'></span></button></td>";
  if(privilege_get('inventory', 'download')) $html[] = "<td><button class='hollow' onclick=\"ui.async('ui_inventoryexport', [ null ], { waitel:this })\"><span class='mdi mdi-download'></span></button></td>";
  if(ui_hasmoreoptions()) $html[] = "<td><button class='hollow' onclick=\"ui.async('ui_taxoptions', [])\"><span class='mdi mdi-menu'></span></button></td>";
  return implode('', $html);

}

function m_customgridheadcolumns(){

  return array(
    "%soldqty_1%"=>date('M', mktime(0, 0, 0, date('m') - 1, 1, date('Y'))),
    "%soldqty_2%"=>date('M', mktime(0, 0, 0, date('m') - 2, 1, date('Y'))),
    "%soldqty_3%"=>date('M', mktime(0, 0, 0, date('m') - 3, 1, date('Y'))),
    "%soldqty_4%"=>date('M', mktime(0, 0, 0, date('m') - 4, 1, date('Y'))),
    "%soldqty_5%"=>date('M', mktime(0, 0, 0, date('m') - 5, 1, date('Y'))),
    "%soldqty_6%"=>date('M', mktime(0, 0, 0, date('m') - 6, 1, date('Y')))
  );

}
function grid_options($obj){

  global $deletable;
  $id = $obj['id'];
  $name = $obj['name'];

  $c = "<div class='align-center'>";
  $c .= "<span class='fa fa-bars' onclick=\"ui.async('ui_inventorydetail', [ $id, event.altKey ? 'write' : 'read' ], { waitel:this })\"></span>";
  if($deletable) $c .= "<span class='fa fa-times' onclick=\"if(confirm('Hapus $name?'))ui.async('ui_inventoryremove', [ $id ], { waitel:this })\"></span>";
  $c .= "</div>";
  return $c;

}
function grid_qty($obj){

  global $user_privilege_costprice;
  $id = $obj['id'];
  $qty = $obj['qty'];

  $html = [];
  $html[] = "<div class='align-right'>";
  if($user_privilege_costprice)
    $html[] = "<span class='text-clickable' onclick=\"ui.async('ui_inventoryqty', [ $id ])\">" . ($qty ? number_format_auto($qty, 2) : '-') . "</span>";
  else
    $html[] = "<span>" . ($qty ? number_format_auto($qty, 2) : '-') . "</span>";
  $html[] = "<span class='tooltip' id='tooltip-$id'></span>";
  $html[] = "</div>";

  return implode('', $html);

}
function grid_isactive($obj){

  $isactive = ov('isactive', $obj);

  $html = [];
  $html[] = "<div class='align-center'>";
  $html[] = $isactive ? "<span class='fa fa-check-circle color-green'></span>" : "<span class='fa fa-minus-circle color-red'></span>";
  $html[] = "</div>";

  return implode('', $html);

}
function grid_taxable($obj){

  $taxable = ov('taxable', $obj, 0, 0);
  $html = [];
  $html[] = "<div class='align-center'>";
  $html[] = $taxable ? "<span class='fa fa-check-circle cl-green'></span>" : "<span class='fa fa-minus-circle cl-red'></span>";
  $html[] = "</div>";

  return implode('', $html);

}
function ui_hasmoreoptions(){
  return false;
}
function ui_taxoptions(){

  $html = [];
  $html[] = "<element exp='.modal'>";
  $html[] = "<div class='scrollable padding5'>";
  $html[] = "<button class='hover-blue width-full align-left' onclick=\"ui.async('ui_inventory_movetax', [])\"><span class='mdi mdi-download'></span><label>Move Inventory</label></button>";
  $html[] = "</div>";
  $html[] = "</element>";
  $html[] = "<script>ui.modal_open(ui('.modal'), { closeable:true, width:300 });</script>";

  return implode('', $html);

}
function ui_inventory_movetax(){

  $inventory_codes = explode(PHP_EOL, file_get_contents(__DIR__ . '/cmd/a.txt'));

  $warehouses = warehouselist();

  $inventory_columns = [];
  $inventory_columns[] = [ 'active'=>1, 'name'=>'code' ];
  $inventory_columns[] = [ 'active'=>1, 'name'=>'avgcostprice' ];
  foreach($warehouses as $warehouse)
    $inventory_columns[] = [ 'active'=>1, 'name'=>'qty_' . $warehouse['id'] ];

  $inventories = inventorylist(
    $inventory_columns,
    null,
    [
      [ 'name'=>'code', 'operator'=>'in', 'value'=>implode(', ', $inventory_codes) ]
    ]
  );


  $wjv = [];
  foreach($inventories as $inventory){

    foreach($inventory as $key=>$qty){
      if(strpos($key, 'qty_') !== false){
        $warehouseid = str_replace('qty_', '', $key);
        $avgcostprice = $inventory['avgcostprice'];
        $inventoryid = $inventory['id'];
        $inventorycode = $inventory['code'];
        $tax_inventorycode = $inventorycode . 'T';
        $tax_inventory = inventorydetail('*', [ 'code'=>$tax_inventorycode ]);
        if($tax_inventory){
          $tax_inventoryid = $tax_inventory['id'];

          // Create warehouseid object
          if(!isset($wjv[$warehouseid]))
            $wjv[$warehouseid] = [
              'date'=>system_date('Ymd'),
              'warehouseid'=>$warehouseid,
              'details'=>[]
            ];

          $wjv[$warehouseid]['details'][] = [
            'inventoryid'=>$inventoryid,
            'qty'=>$qty * -1,
            'unit'=>'',
            'unitprice'=>$avgcostprice,
            'remark'=>''
          ];
          $wjv[$warehouseid]['details'][] = [
            'inventoryid'=>$tax_inventoryid,
            'qty'=>$qty,
            'unit'=>'',
            'unitprice'=>$avgcostprice,
            'remark'=>''
          ];
        }
      }
    }

  }
  exc($wjv);

}

function grid_categories($obj){

  $categories = ov('categories', $obj);

  $html = [];
  $html[] = "<div>";
  $html[] = $categories;
  $html[] = "</div>";

  return implode('', $html);

}

function grid_unitvolume($obj){

  $id = $obj['id'];
  $unitvolume = ov('unitvolume', $obj, '');

  $c = "<div class='align-center'>";
  $c .= ui_textbox(array(
    'align'=>'right',
    'datatype'=>'money',
    'class'=>'no-border',
    'placeholder'=>'Unit volume...',
    'width'=>'100%',
    'value'=>$unitvolume,
    'onchange'=>"ui.async('ui_inventoryformula_set', [ null, $id, 'm3', value ])",
  ));
  $c .= "</div>";
  return $c;

}

function ui_inventoryformula_set($date, $inventoryid, $name, $value){

  $obj = inventoryformula_set($date, $inventoryid, $name, $value);
  $id = $obj['inventoryid'];
  $freightcharge = ov('freightcharge', $obj);

  return "<element exp='#iv$id'>" . number_format(floatval($freightcharge), get_round_precision($freightcharge)) . "</element>";

}

function m_loadstate_ex($reset = false){

  global $module;

  $warehouses = pmrs("SELECT `id`, code FROM warehouse");
  $warehouses_indexed = array_index($warehouses, array('id'), 1);
  $modulecolumns = $module['columns'];
  $modulecolumns_indexed = array_index($modulecolumns, array('name'), 1);

  $statechanged = false;

  // ****
  // Check if deleted or updated
  // ****
  $deleted_columnnames = array();
  for($i = 0 ; $i < count($modulecolumns) ; $i++){
    if(strpos($modulecolumns[$i]['name'], 'qty_') !== false){
      $qtyid = str_replace('qty_', '', $modulecolumns[$i]['name']);
      if(!isset($warehouses_indexed[$qtyid])){
        $deleted_columnnames[] = 'qty_' . $qtyid;
      }
      else if($modulecolumns[$i]['text'] != $warehouses_indexed[$qtyid]['code']){
        $modulecolumns[$i]['text'] = $warehouses_indexed[$qtyid]['code'];
        for($j = 0 ; $j < count($module['presets']) ; $j++){
          for($k = 0 ; $k < count($module['presets'][$j]['columns']) ; $k++){
            if($module['presets'][$j]['columns'][$k]['name'] == 'qty_' . $qtyid){
              $module['presets'][$j]['columns'][$k]['text'] = $warehouses_indexed[$qtyid]['code'];
            }
          }
        }
      }
    }
  }
  if(count($deleted_columnnames) > 0){
    for($i = count($module['columns']) - 1 ; $i >= 0 ; $i--){
      if(in_array($module['columns'][$i]['name'], $deleted_columnnames)){
        array_splice($module['columns'], $i, 1);
      }
    }
    for($i = 0 ; $i < count($module['presets']) ; $i++){
      for($j = count($module['presets'][$i]['columns']) ; $j >= 0 ; $j--){
        if(in_array($module['presets'][$i]['columns'][$j]['name'], $deleted_columnnames)){
          array_splice($module['presets'][$i]['columns'], $j, 1);
        }
      }
      for($j = count($module['presets'][$i]['sorts']) ; $j >= 0 ; $j--){
        if(in_array($module['presets'][$i]['sorts'][$j]['name'], $deleted_columnnames)){
          array_splice($module['presets'][$i]['sorts'], $j, 1);
        }
      }
      for($j = count($module['presets'][$i]['filters']) ; $j >= 0 ; $j--){
        if(in_array($module['presets'][$i]['filters'][$j]['name'], $deleted_columnnames)){
          array_splice($module['presets'][$i]['filters'], $j, 1);
        }
      }
      for($j = count($module['presets'][$i]['groups']) ; $j >= 0 ; $j--){
        if(in_array($module['presets'][$i]['groups'][$j]['name'], $deleted_columnnames)){
          array_splice($module['presets'][$i]['groups'], $j, 1);
        }
      }
    }
    $statechanged = 1;
  }
  // ****
  // Check if added
  // ****

  if($_SESSION['user']['id'] == 1004); // Exclude gfculinary
  else{
    for ($i = 0; $i < count($warehouses); $i++) {
      $warehouse = $warehouses[$i];
      $warehouseid = $warehouse['id'];
      $warehousecode = $warehouse['code'];

      if (!isset($modulecolumns_indexed['qty_' . $warehouseid])) {
        $module['columns'][] = array('active' => 1, 'name' => 'qty_' . $warehouseid, 'text' => $warehousecode, 'width' => 50, 'datatype' => 'number');
        for ($j = 0; $j < count($module['presets']); $j++) {
          $module['presets'][$j]['columns'][] = array('active' => 1, 'name' => 'qty_' . $warehouseid, 'text' => $warehousecode, 'width' => 50, 'datatype' => 'number');
        }
      }
    }
  }

  if($statechanged) m_savestate($module);

}

function m_griddoubleclick(){

  return "ui.async('ui_inventorydetail', [ this.dataset['id'] ], {})";

}

include 'rcfx/dashboard1.php';
?>
<script type="text/javascript" src="rcfx/js/inventory.js"></script>