<?php
if(privilege_get('inventoryanalysis', 'list') < 1){ include 'notavailable.php'; return; }

$modulename = 'inventorycard';

include 'api/inventory.php';

function defaultmodule(){

  $columns = array(
    array('active'=>0, 'name'=>'id', 'text'=>'ID', 'width'=>50, 'nodittomark'=>1),
    array('active'=>0, 'name'=>'inventoryid', 'text'=>'Inventory ID', 'width'=>30, 'nodittomark'=>1),
    array('active'=>0, 'name'=>'section', 'text'=>'Section', 'width'=>30, 'nodittomark'=>1),
    array('active'=>1, 'name'=>'inventorycode', 'text'=>'Kode', 'width'=>80, 'nodittomark'=>1),
    array('active'=>1, 'name'=>'inventorydescription', 'text'=>'Nama', 'width'=>120, 'nodittomark'=>0),
    array('active'=>1, 'name'=>'date', 'text'=>'Tanggal', 'width'=>80, 'datatype'=>'date', 'nodittomark'=>1),
    array('active'=>0, 'name'=>'createdon', 'text'=>'Tanggal Dibuat', 'width'=>90, 'datatype'=>'date', 'nodittomark'=>1),
    array('active'=>1, 'name'=>'ref', 'text'=>'Tipe', 'align'=>'center', 'width'=>50, 'nodittomark'=>1),
    array('active'=>0, 'name'=>'refid', 'text'=>'ID Tipe', 'align'=>'center', 'width'=>50, 'nodittomark'=>1),
    array('active'=>1, 'name'=>'in', 'text'=>'Masuk', 'width'=>70, 'datatype'=>'number', 'nodittomark'=>1),
    array('active'=>1, 'name'=>'out', 'text'=>'Keluar', 'width'=>70, 'datatype'=>'number', 'nodittomark'=>1),
    array('active'=>1, 'name'=>'unitamount', 'text'=>'Harga', 'width'=>80, 'datatype'=>'money', 'nodittomark'=>1),
    array('active'=>1, 'name'=>'qty', 'text'=>'Saldo Akhir', 'width'=>70, 'datatype'=>'number', 'nodittomark'=>1),
    array('active'=>0, 'name'=>'lastupdatedon', 'text'=>'Update Terakhir', 'width'=>100, 'datatype'=>'datetime', 'nodittomark'=>1),
    array('active'=>1, 'name'=>'detail', 'text'=>'Stok Akhir', 'type'=>'html', 'html'=>'mod_col1', 'width'=>250, 'nodittomark'=>1),
  );

  $presets = array();
  $presets[] = array(
    'text'=>'Semua Barang',
    'columns'=>$columns,
    'sorts'=>[
      [ 'name'=>'inventoryid', 'sorttype'=>'asc' ],
      [ 'name'=>'date', 'sorttype'=>'asc' ],
      [ 'name'=>'in', 'sorttype'=>'asc' ],
      [ 'name'=>'createdon', 'sorttype'=>'asc' ],
    ],
    'viewtype'=>'list'
  );

  $module = array(
    'title'=>'inventorycard',
    'columns'=>$columns,
//    'rows'=>"mod_row1",
    'presets'=>$presets,
    'presetidx'=>0,
    'quickfilterscolumns'=>array(
      array('text'=>'', 'value'=>'inventorycode|inventorydescription&contains&'),
      array('text'=>'Code:', 'value'=>'inventorycode&equals&'),
      array('text'=>'Inventory ID:', 'value'=>'inventoryid&equals&'),
    )
  );
  return $module;

}

function datasource($columns = null, $sorts = null, $filters = null, $limits = null){

  $inventory_columnaliases = array(
    'id'=>'t1.id!',
    'section'=>'t1.section!',
    'inventoryid'=>'t1.inventoryid!',
    'date'=>'t1.date',
    'createdon'=>'t1.createdon',
    'inventorycode'=>'t2.code',
    'inventorydescription'=>'t2.description',
    'ref'=>'t1.ref',
    'refid'=>'t1.refid',
    'in'=>'t1.in',
    'out'=>'t1.out',
    'unitamount'=>'t1.unitamount',
    'qty'=>'t1.qty',
    'detail'=>'t1.detail',
    'lastupdatedon'=>'t1.lastupdatedon'
  );

  $sorts = [
    [ 'name'=>'inventoryid', 'sorttype'=>'asc' ],
    [ 'name'=>'date', 'sorttype'=>'asc' ],
    [ 'name'=>'section', 'sorttype'=>'asc' ],
    [ 'name'=>'id', 'sorttype'=>'asc' ],
  ];

  $params = [ ];
  $columnquery = columnquery_from_columnaliases($columns, $inventory_columnaliases);
  $wherequery = wherequery_from_filters($params, $filters, $inventory_columnaliases);
  $sortquery = sortquery_from_sorts($sorts, $inventory_columnaliases);
  $limitquery = limitquery_from_limitoffset($limits);

  $query = "SELECT $columnquery FROM inventorybalance t1
    LEFT JOIN inventory t2 ON t1.inventoryid = t2.id
    $wherequery
    $sortquery
    $limitquery
  ";
  $data = pmrs($query, $params);

  return $data;

}

function mod_row1($obj){

  if(isset($obj['section']) && $obj['section'] != 9) return false;

  return "<tr><td colspan='100'>Total Row</td></tr>";

}

function mod_col1($obj){

  $detail = json_decode($obj['detail'], 1);
  $items = $detail['items'];

  $html = [];
  if(is_array($items)){
    foreach($items as $item){

      $qty = number_format($item['qty'], 2);
      $costprice = $item['price'];
      $html[] = "<span class='tag' onclick='ui.tooltip(this.nextElementSibling, this);return ui.preventDefault(event);'><span class='tag-key'>$qty</span><span class='tag-val'>" . number_format_auto($costprice, 2) . " <span class='fa fa-info-circle' style='display:text'></span></span></span>";

      $detail = ov('detail', $item);
      $m3 = ov('m3', $detail, 0, 0);
      $purchaseprice = ov('purchaseprice', $detail, 0, 0);
      $unitamount = ov('unitamount', $detail, 0, 0);
      $m3amount = ov('m3amount', $detail, 0, 0);
      $handlingfeevolume = ov('handlingfeevolume', $detail, 0, 0);
      $totalhandlingfeeamount = ov('totalhandlingfeeamount', $detail, 0, 0);
      $freightcharge = ov('freightcharge', $detail, 0, 0);
      $code = ov('code', $detail);

      $html[] = "<span class='tooltip padding5'>";
      $html[] = "<table class='form form-align-left'>";
      $html[] = "<tr><th>Code</th><td>$code</td></tr>";
      $html[] = "<tr><th>Purchase Price</th><td>" . number_format_auto($purchaseprice, 2) . "</td></tr>";
      $html[] = "<tr><th>U. Volume Price</th><td>" . number_format_auto($m3amount, 6) . "</td></tr>";
      $html[] = "<tr><th>Cost Price</th><td>" . number_format_auto($unitamount, 6) . "</td></tr>";
      $html[] = "<tr><th>U. Volume</th><td>" . number_format_auto($m3, 6) . "</td></tr>";
      $html[] = "<tr><th>T. Volume</th><td>" . number_format_auto($handlingfeevolume, 2) . "</td></tr>";
      $html[] = "<tr><th>T. Volume Amount</th><td>" . number_format_auto($totalhandlingfeeamount, 2) . "</td></tr>";
      $html[] = "<tr><th>Freight Charge</th><td>" . number_format_auto($freightcharge, 2) . "</td></tr>";
      $html[] = "</table>";
      $html[] = "</span>";
    }
  }
  return implode('', $html);

}

function m_griddoubleclick(){

  return "ui.async('ui_inventorycarddetail', [ this.dataset['id'] ], {})";

}

function ui_inventorycarddetail($id){

  $inventorybalance = pmr("select * from inventorybalance where `id` = ?", [ $id ]);
  if(!$inventorybalance) exc("Tidak dapat membuka detail ini. [$id]");

  $ref = $inventorybalance['ref'];
  $refid = $inventorybalance['refid'];

  return ui_moduleopen($ref, $refid);

}

include 'rcfx/dashboard1.php';
?>