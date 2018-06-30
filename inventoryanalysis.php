<?php
if(privilege_get('inventoryanalysis', 'list') < 1){ include 'notavailable.php'; return; }

$modulename = 'inventoryanalysis';

require_once 'api/inventory.php';

function defaultmodule(){

  $uid = 7;

  $defined_columns = [
    'id'=>[ 'active'=>0, 'name'=>'id', 'text'=>'ID', 'width'=>50 ],
    'code'=>[ 'active'=>0, 'name'=>'code', 'text'=>'Kode', 'width'=>60 ],
    'description'=>[ 'active'=>1, 'name'=>'description', 'text'=>'Barang', 'width'=>100 ],
    'unit'=>[ 'active'=>0, 'name'=>'unit', 'text'=>'Satuan', 'width'=>40 ],
    'qty_in_stock'=>[ 'active'=>1, 'name'=>'qty_in_stock', 'text'=>'Stok', 'width'=>60, 'datatype'=>'number' ],
    'selected'=>[ 'active'=>1, 'name'=>'selected', 'text'=>"Pesanan", 'width'=>60, 'align'=>'center', 'type'=>'html' ,'html'=>'grid_selected', 'style'=>'padding:0 10px', 'nodittomark'=>1 ],
    'avg_qty_sold_per_month'=>[ 'active'=>1, 'name'=>'avg_qty_sold_per_month', 'text'=>'Rata2 Jual', 'width'=>70, 'datatype'=>'number', 'decimals'=>'0' ],
    'avg_qty_purchased_per_month'=>[ 'active'=>0, 'name'=>'avg_qty_purchased_per_month', 'text'=>'Rata2 Beli', 'width'=>70, 'datatype'=>'number', 'decimals'=>'0' ],
    'n_days_remaining_stock'=>[ 'active'=>1, 'name'=>'n_days_remaining_stock', 'text'=>'Sisa Stok', 'width'=>60, 'type'=>'html', 'html'=>'grid_n_days', 'align'=>'right' ],
    'qty_ordered'=>[ 'active'=>1, 'name'=>'qty_ordered', 'text'=>'Kts Dipesan', 'width'=>70, 'datatype'=>'number' ],
    'qty_purchased'=>[ 'active'=>1, 'name'=>'qty_purchased', 'text'=>'Kts Dibeli', 'width'=>70, 'datatype'=>'number' ],
    'qty_sold'=>[ 'active'=>1, 'name'=>'qty_sold', 'text'=>'Kts Terjual', 'width'=>70, 'datatype'=>'number' ],
    'qty_ordered_detail'=>[ 'active'=>0, 'name'=>'qty_ordered_detail', 'text'=>'Detil Dipesan', 'width'=>'250px', 'type'=>'html', 'html'=>'grid_column9', 'style'=>'padding:0 10px' ],
  ];

  for($i = 6 ; $i >= 1 ; $i--){
    $timestamp = date('Ymd', mktime(0, 0, 0, date('m') - $i, 1, date('Y')));
    $timestamp2 = date('Ym', mktime(0, 0, 0, date('m') - $i, 1, date('Y')));
    $defined_columns['qty_sold_' . $timestamp2] = [ 'active'=>1, 'name'=>'qty_sold_' . $timestamp2, 'text'=>date('M Y', strtotime($timestamp)), 'width'=>60, 'type'=>'html', 'html'=>'grid_qty_sold_year_month', 'align'=>'right' ];
  }
  $columns = [];

  $table_exists = pmc("select count(*) from information_schema.TABLES where TABLE_NAME = 'vv_inventory$uid'");
  if($table_exists){

    $table_columns = pmrs("SHOW COLUMNS FROM vv_inventory$uid;");

    $suppliers = pmrs("select `id`, description from supplier");
    $suppliers = array_index($suppliers, [ 'id' ], 1);
    $supplierids = [];
    foreach($table_columns as $table_column){

      if(isset($defined_columns[$table_column['Field']])){
        $columns[] = $defined_columns[$table_column['Field']];
      }
      else if(strpos($table_column['Field'], 'qty_ordered_') !== false){
        $supplierid = str_replace('qty_ordered_', '', $table_column['Field']);
        $supplier = $suppliers[$supplierid];

        $columns[] = [ 'active'=>0, 'name'=>$table_column['Field'], 'text'=>$supplier['description'], 'width'=>50, 'datatype'=>'number' ];
        $supplierids[] = $supplierid;
      }
      else if(strpos($table_column['Field'], 'qty_purchased_') !== false){

        $supplierid = str_replace('qty_purchased_', '', $table_column['Field']);
        $supplier = $suppliers[$supplierid];
        $columns[] = [ 'active'=>0, 'name'=>$table_column['Field'], 'text'=>'Qty Purchased from ' . $supplier['description'], 'width'=>50, 'datatype'=>'number' ];

      }

    }
    $columns[] = $defined_columns['selected'];
    $columns[] = $defined_columns['qty_ordered_detail'];

  }
  $presets = [];

  $presets[] = [
    'text'=>'Semua',
    'columns'=>$columns,
    'sorts'=>[
      [ 'name'=>'qty_in_stock', 'sorttype'=>'desc' ],
    ],
    'viewtype'=>'list'
  ];


  $preset_columns_visible = [
    'description', 'qty_in_stock', 'qty_ordered', 'n_days_remaining_stock',
    'selected',
  ];
  $preset_columns = [];
  foreach($columns as $column){

    if(in_array($column['name'], $preset_columns_visible)){
      $column['active'] = 1;
    }
    else if(strpos($column['name'], 'qty_sold_') !== false){
      $column['active'] = 1;
    }
    else
      $column['active'] = 0;
    $preset_columns[] = $column;
  }
  $presets[] = [
    'text'=>'Stok Sisa < 14 Hari',
    'columns'=>$preset_columns,
    'sorts'=>[
      [ 'name'=>'n_days_remaining_stock', 'sorttype'=>'asc' ],
      [ 'name'=>'qty_in_stock', 'sorttype'=>'asc' ],
    ],
    'filters'=>[
      [ 'name'=>'n_days_remaining_stock', 'operator'=>'<', 'value'=>'14' ],
      [ 'name'=>'qty_sold', 'operator'=>'>', 'value'=>'0' ],
    ],
    'viewtype'=>'list'
  ];

  if(is_array($supplierids)){

    $preset_columns_visible = [
      'description', 'unit', 'qty_in_stock', 'selected', 'avg_qty_sold_per_month',
      'avg_qty_purchased_per_month', 'n_days_remaining_stock'
    ];

    foreach($supplierids as $supplierid){

      $preset_name = '';
      $preset_columns = [];
      foreach($columns as $column){

        if(in_array($column['name'], $preset_columns_visible)){
          $column['active'] = 1;
        }
        else if(strpos($column['name'], 'qty_ordered_') !== false){
          $column_supplierid = str_replace('qty_ordered_', '', $column['name']);

          if($supplierid == $column_supplierid){
            $column['active'] = 1;
            $preset_name = $column['text'];
          }
          else{
            $column['active'] = 0;
          }
        }
        else
          $column['active'] = 0;

        if($column['name'] == 'description')
          $column['width'] = '350px';

        $preset_columns[] = $column;

      }

      $presets[] = [
        'text'=>$preset_name,
        'columns'=>$preset_columns,
        'filters'=>[
          [ 'name'=>'qty_purchased_' . $supplierid, 'operator'=>'>', 'value'=>0 ],
        ],
        'sorts'=>[
          [ 'name'=>'qty_ordered_' . $supplierid, 'sorttype'=>'desc' ]
        ],
        'viewtype'=>'list'
      ];

    }

  }

  $module = array(
    'title'=>'inventoryanalysis',
    'columns'=>$columns,
    'presets'=>$presets,
    'presetidx'=>1,
    'quickfilterscolumns'=>array(
      array('text'=>'', 'value'=>'code|description&contains&')
    )
  );
  return $module;

}

function datasource($columns = null, $sorts = null, $filters = null, $limits = null){

  $data = inventoryanalysislist($columns, $sorts, $filters, $limits);
  return $data;

}

function customheadcolumns(){

  $html = [];
//  $html[] = "<td><button class='hollow' onclick=\"intro()\"><span class='mdi mdi-apps'></span></button></td>";
  if(privilege_get('inventoryanalysis', 'download')) $html[] = "<td><button class='hollow' onclick=\"ui.async('ui_inventoryanalysisexport', [ null ], { waitel:this })\"><span class='mdi mdi-download'></span></button></td>";
  if(privilege_get('purchaseorder', 'new')) $html[] = "<td><button class='hollow' onclick=\"purchaseorder_new()\"><span class='mdi mdi-plus'></span></button></td>";
  return implode('', $html);

}

function grid_n_days($obj){

  return "<div class='align-right'>" . ($obj['n_days_remaining_stock'] < 0 || !$obj['n_days_remaining_stock'] ? 0 : $obj['n_days_remaining_stock']) . ' hari' . "</div>";

}

function grid_qty_sold_year_month($obj, $unused, $column){

  $bgcolors = [
    '#fff',
    '#f6faff',
    '#f0f6ff',
    '#e6f1ff',
    '#e3efff',
    '#dceafc',
  ];

  $name = $column['name'];
  $value = $obj[$name];

  $depth = 0;
  foreach($obj as $obj_key=>$obj_value){
    if(strpos($obj_key, 'qty_sold_') !== false){
      $index = str_replace('qty_sold_', '', $obj_key);
      if($index > 0){
        if($value > $obj_value){
          $depth++;
        }
      }
    }
  }

  $value = number_format($value);
  $value = $value == 0 ? '-' : $value;

  $html = "<div class='align-right'>$value</div>";
  return [ 'html'=>$html, 'style'=>'background:' . $bgcolors[$depth] ];

}

function grid_options($obj){

  $id = $obj['id'];

  $c = "<div class='align-center'>";
  $c .= "<span class='fa fa-bars' onclick=\"ui.async('ui_categorydetail', [ $id ], { waitel:this })\"></span>";
  if(privilege_get('category', 'delete')) $c .= "<span class='fa fa-times' onclick=\"if(confirm('Hapus " . $obj['code'] . "')) ui.async('ui_categoryremove', [ $id ], { waitel:this })\"></span>";
  $c .= "</div>";
  return $c;

}

function grid_selected($obj){

  $id = $obj['id'];
  return "<div class='align-center'><input type='text' class='align-right' style='padding:1px;width:100%;' data-id='$id' /></div>";

}

function grid_column9($obj){

  $qty_ordered_detail = $obj['qty_ordered_detail'];

  $html = [];
  foreach($qty_ordered_detail as $key=>$value)
    if($value > 0 || $value < 0){
      $value = number_format($value);
      $html[] = "<span class='tag'><span class='tag-key'>$key</span><span class='tag-val'>$value</span></span>";
    }

  return implode('', $html);

}

function m_griddoubleclick(){

  //return "ui.async('ui_categorydetail', [ this.dataset['id'] ], {})";

}

$mod_reset = 1;

include 'rcfx/dashboard1.php';
?>
