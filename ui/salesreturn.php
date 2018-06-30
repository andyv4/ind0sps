<?php

require_once 'api/salesreturn.php';

$ui_salesreturndetail_columns = array(
    array('active'=>1, 'text'=>'Kode', 'width'=>90, 'type'=>'html', 'html'=>'ui_salesreturndetail_column0'),
    array('active'=>1, 'text'=>'Lunas', 'width'=>36, 'type'=>'html', 'html'=>'ui_salesreturndetail_column6'),
    array('active'=>1, 'text'=>'Barang', 'width'=>220, 'type'=>'html', 'html'=>'ui_salesreturndetail_column1'),
    array('active'=>1, 'text'=>'Kts', 'width'=>80, 'type'=>'html', 'html'=>'ui_salesreturndetail_column2', 'align'=>'right'),
    array('active'=>1, 'text'=>'Satuan', 'width'=>80, 'type'=>'html', 'html'=>'ui_salesreturndetail_column3', 'align'=>'center'),
    array('active'=>1, 'text'=>'Harga Jual', 'width'=>100, 'type'=>'html', 'html'=>'ui_salesreturndetail_column4', 'align'=>'right'),
    array('active'=>1, 'text'=>'', 'width'=>40, 'type'=>'html', 'html'=>'ui_salesreturndetail_column5', 'align'=>'center'),
);

function ui_salesreturndetail($id = null, $mode = 'read'){

  $obj = is_array($id) ? $id : (intval($id) > 0 ? salesreturndetail(null, array('id'=>$id)) : null);

  if($mode != 'read' && $obj && !privilege_get('salesreturn', 'modify')) $mode = 'read';
  if($mode == 'read' && !$obj) throw new Exception('Retur penjualan dengan nomor ini tidak ada.');
  $readonly = $mode == 'write' ? 0 : 1;
  $closeable = $readonly ? 1 : 0;
  $is_new = !$readonly && !$obj ? true : false;
  if($is_new && !privilege_get('salesreturn', 'new')) exc("Anda tidak dapat membuat retur penjualan.");
  $status = '';
  global $ui_salesreturndetail_columns;

  $controls = array(
    'id'=>array('type'=>'hidden', 'name'=>'id', 'value'=>ov('id', $obj)),
    'code'=>array('type'=>'textbox', 'name'=>'code', 'width'=>'150px', 'readonly'=>$readonly, 'value'=>ov('code', $obj, null, salesreturncode())),
    'date'=>array('type'=>'datepicker', 'name'=>'date', 'width'=>'120px', 'readonly'=>$readonly, 'value'=>ov('date', $obj)),
    'customerid'=>array('type'=>'autocomplete', 'name'=>'customerid', 'width'=>'400px', 'src'=>'customerlist_hints_asitems', 'readonly'=>$readonly, 
    	'text'=>ov('customerdescription', $obj), 'value'=>ov('customerid', $obj), 'placeholder'=>'Masukkan nama pelanggan...'
    ),
    'total'=>array('type'=>'label', 'name'=>'total', 'datatype'=>'money', 'value'=>ov('total', $obj), 'width'=>150),
    'note'=>array('type'=>'textarea', 'name'=>'note', 'width'=>400, 'height'=>60, 'readonly'=>$readonly, 'value'=>ov('note', $obj), 'placeholder'=>'Catatan mengenai retur disini...'),
    'ispaid'=>array('type'=>'checkbox', 'name'=>'ispaid', 'value'=>ov('ispaid', $obj), 'readonly'=>$readonly, 'onchange'=>"salesreturn_ispaidchange(value, this)"),
    'returnaccountid'=>array('type'=>'dropdown', 'name'=>'returnaccountid', 'value'=>ov('returnaccountid', $obj, 0, 999), 'width'=>150, 'items'=>salesreturndetail_accounts(), 'readonly'=>$readonly, 'align'=>'right'),
    'returnamount'=>array('type'=>'textbox', 'name'=>'returnamount', 'value'=>ov('returnamount', $obj), 'readonly'=>$readonly, 'align'=>'right', 'datatype'=>'money')
  );

  $actions = array();
  if($readonly && $obj && privilege_get('salesreceipt', 'modify')) $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_salesreturndetail', [ $id, 'write' ], { waitel:this })\"><span class='fa fa-edit'></span><label>Ubah</label></button></td>";
  if(!$readonly && !$obj && privilege_get('salesreceipt', 'new')) $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_salesreturndetail_save', [ ui.container_value(ui('.modal')) ], { waitel:this })\"><span class='fa fa-save'></span><label>Simpan</label></button></td>";
  if(!$readonly && $obj && privilege_get('salesreceipt', 'modify')) $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_salesreturndetail_save', [ ui.container_value(ui('.modal')) ], { waitel:this })\"><span class='fa fa-save'></span><label>Simpan</label></button></td>";
  $actions[] = "<td><button class='hollow' onclick=\"ui.modal_close(ui('.modal'))\"><span class='fa fa-times'></span><label>Close</label></button></td>";

  $c = "<element exp='.modal'>";
  $c .= "
	  <div class='statusbar'>$status</div>
    <div class='scrollable padding1020'>
      " . ui_control($controls['id']) . "
      <table class='form'>
        <tr><th style='width:80px'><label>Kode</label></th><td>" . ui_control($controls['code']) . "</td></tr>
        <tr><th><label>Tanggal</label></th><td>" . ui_control($controls['date']) . "</td></tr>
        <tr>
        	<th><label>Pelanggan</label></th>
        	<td>
        		" . ui_control($controls['customerid']) . "
        		" . (!$readonly ? "<button class='hollow' onclick=\"ui.async('ui_salesreturndetail_pickinvoice', [ ui.control_value(ui('%customerid')), ui.grid_value(ui('#grid4')) ], { waitel:this })\"><span class='fa fa-list'></span><label>Pilih Faktur</label></button>" : '') . "
        	</td>
        </tr>
      </table>
      <div style='height:40px'></div>
      " . ui_gridhead(array('columns'=>$ui_salesreturndetail_columns, 'gridexp'=>'#grid4')) . "
      " . ui_grid(array('columns'=>$ui_salesreturndetail_columns, 'id'=>'grid4', 'name'=>'items', 'value'=>ov('items', $obj), 'readonly'=>$readonly)) . "
      <div style='height:40px'></div>
      <table cellspacing='0' cellpadding='0'>
        <tr>
          <td valign='top'>
            <table class='form'>
              <tr>
                <th style='width:80px'><label>Catatan</label></th>
                <td>" . ui_control($controls['note']) . "</td>
              </tr>
            </table>
          </td>
          <td style='width:100%'></td>
          <td valign='top'>
            <table class='form'>
              <tr>
                <th valign='top'><label>Total</label></th>
                <td style='text-align:right'>" . ui_control($controls['total']) . "</td>
                <td style='width:40px'></td>
              </tr>
              <tr>
                <th valign='top'><label>Akun Pembayaran</label></th>
                <td style='text-align:right'>
                  " . ui_control($controls['ispaid']) . "
                  " . ui_control($controls['returnaccountid']) . "
                </td>
                <td style='width:40px'></td>
              </tr>
              <tr>
                <th valign='top'><label>Total Pembayaran</label></th>
                <td style='text-align:right'>" . ui_control($controls['returnamount']) . "</td>
                <td style='width:40px'></td>
              </tr>
             </table>
          </td>
        </tr>
      </table>
      <div style='height:88'></div>
    </div>
    <div class='foot'>
      <table cellspacing='5'>
        <tr>
          <td style='width: 100%'></td>
          " . implode('', $actions) . "
        </tr>
      </table>
    </div>
  ";
  $c .= "</element>";
  $c .= "
	<script>
		ui.loadscript('rcfx/js/salesreturn.js', \"ui.modal_open(ui('.modal'), { closeable:$closeable, width:880, autoheight:true });\");
	</script>
	";
  return $c;

}
function ui_salesreturndetail_column0($obj){

  return ui_textbox(array('name'=>'code', 'width'=>'100%', 'value'=>ov('code', $obj), 'readonly'=>1, 'ischild'=>1)) .
      ui_hidden(array('name'=>'salesinvoiceinventoryid', 'value'=>ov('salesinvoiceinventoryid', $obj), 'ischild'=>1)).
      ui_hidden(array('name'=>'warehouseid', 'value'=>ov('warehouseid', $obj), 'ischild'=>1));

}
function ui_salesreturndetail_column1($obj){

  return ui_textbox(array('name'=>'inventorydescription', 'width'=>'100%', 'readonly'=>1, 'value'=>ov('inventorydescription', $obj), 'ischild'=>1)) .
      ui_hidden(array('name'=>'id', 'value'=>ov('id', $obj), 'ischild'=>1));

}
function ui_salesreturndetail_column2($obj, $params){

  return ui_textbox(array('name'=>'qty', 'width'=>'100%', 'value'=>ov('qty', $obj), 'readonly'=>0, 'datatype'=>'number', 'onchange'=>"salesreturndetail_total()", 'align'=>'right', 'ischild'=>1,
    'readonly'=>ov('readonly', $params))) .
    ui_hidden(array('name'=>'qty_original', 'value'=>ov('qty', $obj), 'ischild'=>1));

}
function ui_salesreturndetail_column3($obj){

  return ui_textbox(array('name'=>'unit', 'width'=>'100%', 'value'=>ov('unit', $obj), 'readonly'=>1, 'ischild'=>1));

}
function ui_salesreturndetail_column4($obj){

  return ui_textbox(array('name'=>'unitprice', 'width'=>'100%', 'value'=>ov('unitprice', $obj), 'readonly'=>1, 'datatype'=>'money', 'ischild'=>1));

}
function ui_salesreturndetail_column5($obj, $params){

  return ov('readonly', $params) != 1 ? "<div class='align-center'><span class='fa fa-times-circle color-red' onclick=\"salesreturndetail_onrowremove(event, this)\"></span></div>" : '';

}
function ui_salesreturndetail_column6($obj){

  return "<div class='align-center'>" .
  ($obj['ispaid'] ? "<span class='fa fa-check color-green'></span>" : "<span class='fa fa-minus color-red'></span>") .
  "</div>" . ui_hidden(array('name'=>'ispaid', 'value'=>ov('ispaid', $obj)));

}

// Add items for sales return
function ui_salesreturndetail_additems($items){

  global $ui_salesreturndetail_columns;
  $query = "SELECT t1.code, t2.id, t1.ispaid, t1.warehouseid, t2.inventorydescription, t2.id as salesinvoiceinventoryid, t2.qty, t2.unit, t2.unitprice FROM salesinvoice t1, salesinvoiceinventory t2 WHERE t2.id IN (" . implode(', ', $items) . ")
    AND t1.id = t2.salesinvoiceid";
  $salesinvoiceinventoryitems = pmrs($query);
  $trs = array();
  if($salesinvoiceinventoryitems)
    foreach($salesinvoiceinventoryitems as $salesinvoiceinventoryitem)
      $trs[] = ui_gridrow($salesinvoiceinventoryitem, array('columns'=>$ui_salesreturndetail_columns));

  return uijs("
    ui.grid_add_bytrs(ui('#grid4'), " . json_encode($trs) . ");
    ui.dialog_close();
    salesreturndetail_total();
  ");

}

function ui_salesreturndetail_pickinvoice($customerid, $excludes = null){

	if(intval($customerid) > 0); else throw new Exception('Silakan pilih pelanggan dulu.');

  $excludes_query = array();
  if(is_array($excludes)){
    foreach($excludes as $exclude)
      $excludes_query[] = $exclude['salesinvoiceinventoryid'];
  }
  $excludes_query = count($excludes_query) > 0 ? 'AND t2.id NOT IN(' . implode(', ', $excludes_query) . ')' : '';

	// Retrieve invoice data
	$query = "SELECT t1.id, t1.`date`, t1.code, t1.ispaid, t2.id as salesinvoiceinventoryid, t2.inventorydescription,
		t2.qty, t2.unitprice
		FROM salesinvoice t1, salesinvoiceinventory t2 WHERE t1.id = t2.salesinvoiceid AND t2.qty > COALESCE (t2.returnqty, 0) AND t1.customerid = ? $excludes_query";
	$rows = pmrs($query, array($customerid));	
	
	$c = "<element exp='.dialog'>";
  $c .= "
      <div class='box-dialog'>
        <div class='scrollable' style='height:200px'>
          <div>" . ui_gridhead(array('columns'=>ui_salesreturndetail_pickinvoice_columns())) . "</div>
          <div class='scrollable' style='max-height:150px'>
            " . ui_grid(array('columns'=>ui_salesreturndetail_pickinvoice_columns(), 'id'=>'grid3', 'value'=>$rows, 'onrowclick'=>"salesreturn_pickinvoice_rowclick(event, this)")) . "
          </div>
        </div>
        <div style='height: 15px'></div>
        <button class='blue' onclick=\"salesreturn_pickinvoice_apply()\"><span class='fa fa-check'></span><label>Tambah Grup Faktur</label></button>
        <button class='hollow' onclick=\"ui.dialog_close()\"><span class='fa fa-times'></span><label>Close</label></button>
      </div>
      ";
  $c .= "</element>";
  $c .= uijs("
        ui.dialog_open({ width:800 });
      ");
  return $c;

}

function ui_salesreturndetail_pickinvoice_columns(){

	$columns = array(
		array('active'=>1, 'name'=>'checked', 'text'=>'', 'width'=>40, 'align'=>'center', 'type'=>'html', 'html'=>'ui_salesreturndetail_pickinvoice_column_checked', 'nodittomark'=>1),
		array('active'=>1, 'name'=>'date', 'text'=>'Tanggal', 'width'=>90, 'datatype'=>'date'),
		array('active'=>1, 'name'=>'code', 'text'=>'Kode', 'width'=>90),
    array('active'=>1, 'name'=>'ispaid', 'text'=>'Lunas', 'width'=>36, 'type'=>'html', 'html'=>'ui_salesreturndetail_pickinvoice_column_ispaid', 'nodittomark'=>1),
		array('active'=>1, 'name'=>'inventorydescription', 'text'=>'Barang', 'width'=>180),
		array('active'=>1, 'name'=>'qty', 'text'=>'Kts', 'width'=>50, 'datatype'=>'number'),
		array('active'=>1, 'name'=>'unitprice', 'text'=>'Harga Satuan', 'width'=>100, 'datatype'=>'money')
	);
	return $columns;

}

function ui_salesreturndetail_pickinvoice_column_checked($obj){

	$salesinvoiceinventoryid = $obj['salesinvoiceinventoryid'];
	return "<div class='align-center'><input type='checkbox' name='salesreturndetail_pickinvoice_checked' data-id='$salesinvoiceinventoryid'/></div>";

}

function ui_salesreturndetail_pickinvoice_column_ispaid($obj){

  return "<div class='align-center'>" .
  ($obj['ispaid'] ? "<span class='fa fa-check color-green'></span>" : "<span class='fa fa-minus color-red'></span>") .
  "</div>";

}

function ui_salesreturndetail_save($obj){

  $obj = $obj['id'] > 0 ? salesreturnmodify($obj) : salesreturnentry($obj);
  return uijs("ui.modal_close(ui('.modal'))") . m_load();

}

function ui_salesreturnremove($id){

  salesreturnremove(array('id'=>$id));
  return m_load();

}

function ui_salesreturnexport(){

  global $module;
  $preset = $module['presets'][$module['presetidx']];
  $columns = $preset['columns'];
  $sorts = $preset['sorts'];
  $quickfilters = ov('quickfilters', $preset);
  $filters = $preset['filters'];
  $filters = m_quickfilter_to_filters($filters, $quickfilters);

  $salesreturn_columnaliases = array(
    'date'=>'t1.date',
    'code'=>'t1.code',
    'customerid'=>'t1.customerid',
    'customerdescription'=>'t1.customerdescription',
    'total'=>'t1.total',
    'inventoryid'=>'t2.inventoryid',
    'inventorydescription'=>'t2.inventorydescription',
    'qty'=>'t2.qty',
    'unit'=>'t2.unit',
    'unitprice'=>'t2.unitprice',
    'unittotal'=>'t2.unittotal',
    'createdon'=>'t1.createdon',
  );

  $params = array();
  $columnquery = columnquery_from_columnaliases($columns, $salesreturn_columnaliases);
  $wherequery = 'WHERE t1.id = t2.salesreturnid' . str_replace('WHERE', 'AND', wherequery_from_filters($params, $filters, $salesreturn_columnaliases));
  $sortquery = sortquery_from_sorts($sorts, $salesreturn_columnaliases);

  $query = "SELECT 'salesreturn' as `type`, t1.id, $columnquery FROM salesreturn t1, salesreturninventory t2 $wherequery $sortquery";
  $items = pmrs($query, $params);

  // Generate header
  $item = $items[0];
  $headers = array();
  foreach($item as $key=>$value)
    $headers[$key] = $key;

  $temp = array();
  $temp[] = $headers;
  foreach($items as $item)
    $temp[] = $item;
  $items = $temp;

  $filepath = 'usr/sales-return-' . date('j-M-Y') . '.xlsx';
  array_to_excel($items, $filepath);

  echo uijs("ui('#downloader').href = '$filepath';ui('#downloader').click();");

}

?>