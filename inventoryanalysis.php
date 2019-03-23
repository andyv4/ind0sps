<?php
if(privilege_get('inventoryanalysis', 'list') < 1){ include 'notavailable.php'; return; }

$modulename = 'inventoryanalysis';

require_once 'api/inventory.php';

function defaultmodule(){

  $columns = [
    'id'=>[ 'active'=>0, 'name'=>'id', 'text'=>'ID', 'width'=>50 ],
    'code'=>[ 'active'=>1, 'name'=>'code', 'text'=>'Kode', 'width'=>60 ],
    'description'=>[ 'active'=>1, 'name'=>'description', 'text'=>'Barang', 'width'=>100 ],
    'unit'=>[ 'active'=>0, 'name'=>'unit', 'text'=>'Satuan', 'width'=>40 ],
    'qty'=>[ 'active'=>1, 'name'=>'qty', 'text'=>'Stok', 'width'=>60, 'datatype'=>'number' ],
    'selected'=>[ 'active'=>0, 'name'=>'selected', 'text'=>"Pesanan", 'width'=>60, 'align'=>'center', 'type'=>'html' ,'html'=>'grid_selected', 'style'=>'padding:0 10px', 'nodittomark'=>1 ],
    'avg_qty_sold_per_month'=>[ 'active'=>0, 'name'=>'avg_qty_sold_per_month', 'text'=>'Rata2 Jual', 'width'=>70, 'datatype'=>'number', 'decimals'=>'0' ],
    'avg_qty_purchased_per_month'=>[ 'active'=>0, 'name'=>'avg_qty_purchased_per_month', 'text'=>'Rata2 Beli', 'width'=>70, 'datatype'=>'number', 'decimals'=>'0' ],
    'n_days_remaining_stock'=>[ 'active'=>1, 'name'=>'n_days_remaining_stock', 'text'=>'Sisa Stok', 'width'=>60, 'datatype'=>'number', 'type'=>'html', 'html'=>'grid_n_days', 'align'=>'right' ],
    'qty_ordered'=>[ 'active'=>1, 'name'=>'qty_ordered', 'text'=>'Kts Dipesan', 'width'=>70, 'datatype'=>'number' ],
    'qty_purchased'=>[ 'active'=>0, 'name'=>'qty_purchased', 'text'=>'Kts Dibeli', 'width'=>70, 'datatype'=>'number' ],
    'qty_sold'=>[ 'active'=>0, 'name'=>'qty_sold', 'text'=>'Kts Terjual', 'width'=>70, 'datatype'=>'number' ],
  ];

  $presets = [];

  $presets[] = [
    'text'=>'Semua',
    'columns'=>$columns,
    'sorts'=>[
      [ 'name'=>'n_days_remaining_stock', 'sorttype'=>'asc' ],
      [ 'name'=>'qty', 'sorttype'=>'asc' ],
    ],
    'viewtype'=>'list'
  ];

  $presets[] = [
    'text'=>'Stok Sisa < 14 Hari',
    'columns'=>$columns,
    'sorts'=>[
      [ 'name'=>'n_days_remaining_stock', 'sorttype'=>'asc' ],
      [ 'name'=>'qty', 'sorttype'=>'asc' ],
    ],
    'filters'=>[
      [ 'name'=>'n_days_remaining_stock', 'operator'=>'<', 'value'=>'14' ],
      [ 'name'=>'qty', 'operator'=>'>', 'value'=>'0' ],
    ],
    'viewtype'=>'list'
  ];

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

  $inventoryid = $obj['id'];
  $date = str_replace('qty_sold_', '', $column['name']);

  $value = number_format($value);
  $value = $value == 0 ? '-' : $value;

  $html = "<div class='align-right' onclick=\"ui.async('ui_iad', [ $inventoryid, '$date' ])\">$value</div>";
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

function m_loadstate_ex($reset = false){

  global $module;

  $presetidx = $module['presetidx'];

  // Remove qty_sold columns
  $temp = [];
  foreach($module['presets'][$presetidx]['columns'] as $column){
    if(!in_array($column['name'], [ 'avg_qty_sold_per_month' ]) && strpos($column['name'], 'qty_sold_') !== false);
    else
      $temp[] = $column;
  }
  $module['presets'][$presetidx]['columns'] = $temp;

  // Add qty_sold columns
  $extended_columns = [];
  for($i = -6 ; $i < 0 ; $i++){

    $name = 'qty_sold_' . date('Ym', mktime(0, 0, 0, date('m') + $i, 1, date('Y')));
    $text = date('M Y', mktime(0, 0, 0, date('m') + $i, 1, date('Y')));

    $extended_columns[] = [
      'active'=>1,
      'name'=>$name,
      'text'=>$text,
      'width'=>65,
      'datatype'=>'number',
      'type'=>'html',
      'html'=>'grid_qty_sold_year_month'
    ];
  }

  $module['presets'][$presetidx]['columns'] = array_merge($module['presets'][$presetidx]['columns'], $extended_columns);

  console_log($module);

}

function ui_iad($inventoryid, $date){

  $date .= '01';
  $Y = date('Y', strtotime($date));
  $m = date('m', strtotime($date));
  $d = date('d', strtotime($date));

  $inventory = pmr("select code, description from inventory where `id` = ?", [ $inventoryid ]);

  $start_date = date('Ymd', mktime(0, 0, 0, $m, 1, $Y));
  $end_date = date('Ymd', mktime(0, 0, 0, $m + 1, 0, $Y));

  $sold_qty = pmc("select sold_qty from inventorymonthly where inventoryid = ? and `date` = ?", [ $inventoryid, $start_date ]);
  $purchased_qty = pmc("select purchased_qty from inventorymonthly where inventoryid = ? and `date` = ?", [ $inventoryid, $start_date ]);

  $pis = pmrs("select t3.code, t3.description, sum(t2.qty) as qty from purchaseinvoice t1, purchaseinvoiceinventory t2, supplier t3 
    where t1.id = t2.purchaseinvoiceid and t1.supplierid = t3.id
    and t2.inventoryid = ? and t1.date between ? and ? group by t1.supplierid", [ $inventoryid, $start_date, $end_date ]);

  $html = [];
  $html[] = "<element exp='.modal'>";
  $html[] = "<div class='padding10'>";
  $html[] = "<table class='form' style='width:100%'>";
  $html[] = "<tr><td colspan='2' style='white-space: pre-wrap'><label><b>$inventory[code] - $inventory[description]<br />" . date('M Y', strtotime($start_date)) . "</b></label></td></tr>";
  $html[] = "<tr><td colspan='2' style='border-top:solid 1px #ccc'></td></tr>";
  $html[] = "<tr><th style='text-align:left'><label>Sold Qty</label></th><td style='width:100%'><label>" . number_format($sold_qty) . "</label></td></tr>";
  $html[] = "<tr><th style='text-align:left'><label>Purchased Qty</label></th><td><label>" . number_format($purchased_qty) . "</label></td></tr>";
  if(is_array($pis) && count($pis) > 0){
    $html[] = "<tr><td colspan='2' style='border-top:solid 1px #ccc'></td></tr>";
    foreach($pis as $pi){
      $html[] = "<tr><th><label>$pi[description]</label></th><td><label>" . number_format($pi['qty']) . "</label></td></tr>";
    }
  }
  $html[] = "</table>";
  $html[] = "</div>";
  $html[] = "</element>";
  $html[] = uijs("ui.modal_open(ui('.modal'), { closeable:1, width:300, autoheight:1 })");

  return implode('', $html);

}

include 'rcfx/dashboard1.php';
?>
