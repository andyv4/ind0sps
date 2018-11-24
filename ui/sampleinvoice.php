<?php

require_once 'api/customer.php';
require_once 'api/sampleinvoice.php';

function ui_sampleinvoicedetail($id, $mode = 'read'){

  $sampleinvoice = sampleinvoicedetail(null, array('id'=>$id));

  if($mode != 'read' && $sampleinvoice && !privilege_get('sampleinvoice', 'modify')) $mode = 'read';
  if($mode == 'read' && !$sampleinvoice) throw new Exception('Sampel faktur dengan nomor ini tidak ada.');

  $columns = array(
      array('active'=>1, 'name'=>'col5', 'text'=>'Hapus', 'type'=>'html', 'html'=>'ui_sampleinvoicedetailcol5', 'width'=>40),
      array('active'=>1, 'name'=>'col0', 'text'=>'Barang', 'type'=>'html', 'html'=>'ui_sampleinvoicedetailcol0', 'width'=>360),
      array('active'=>1, 'name'=>'col1', 'text'=>'Kuantitas', 'type'=>'html', 'html'=>'ui_sampleinvoicedetailcol1', 'width'=>90),
      array('active'=>1, 'name'=>'col2', 'text'=>'Satuan', 'type'=>'html', 'html'=>'ui_sampleinvoicedetailcol2', 'width'=>100)
  );
  $value = $sampleinvoice['inventories'];
  $readonly = $mode == 'write' ? 0 : 1;
  $closeable = $readonly ? 1 : 0;
  $is_new = !$readonly && !$sampleinvoice ? true : false;
  if($is_new && !privilege_get('sampleinvoice', 'new')) exc("Anda tidak dapat membuat surat jalan sampel.");
  $code = ov('code', $sampleinvoice);
  $date = ov('date', $sampleinvoice);

  $is_new = !$sampleinvoice && $mode == 'write' ? true : false;
  $code = $is_new ? sampleinvoicecode() : $code;
  $date = $is_new ? date('Ymd') : $date;

  $controls = array(
    'id'=>array('type'=>'hidden', 'name'=>'id', 'value'=>ov('id', $sampleinvoice)),
    'code'=>array('type'=>'textbox', 'name'=>'code', 'width'=>'150px', 'readonly'=>$readonly, 'value'=>$code),
    'date'=>array('type'=>'datepicker', 'name'=>'date', 'width'=>'120px', 'readonly'=>$readonly, 'value'=>$date),
    'customerid'=>array('type'=>'autocomplete', 'name'=>'customerdescription', 'width'=>'300px', 'any_text'=>1, 'src'=>'customerlist_hints_asitems2', 'readonly'=>$readonly, 'text'=>ov('customerdescription', $sampleinvoice), 'value'=>ov('customerdescription', $sampleinvoice), 'onchange'=>"ui.async('ui_sampleinvoicedetail_customerchange', [ value ], { waitel:this })"),
    'address'=>array('type'=>'textarea', 'name'=>'address', 'width'=>'300px','height'=>'80px', 'readonly'=>$readonly, 'value'=>ov('address', $sampleinvoice)),
    'warehouseid'=>array('type'=>'dropdown', 'name'=>'warehouseid', 'items'=>array_cast(warehouselist(null, null), array('text'=>'name', 'value'=>'id')), 'readonly'=>$readonly, 'value'=>ov('warehouseid', $sampleinvoice, 0,  ov('lastwarehouseid', $sampleinvoice, 0, 1)), 'width'=>150),
    'salesmanid'=>array('type'=>'dropdown', 'name'=>'salesmanid', 'items'=>array_cast(userlist(null, null), array('text'=>'name', 'value'=>'id')), 'readonly'=>$readonly, 'value'=>ov('salesmanid', $sampleinvoice, 0, 999), 'width'=>150),
    'note'=>array('type'=>'textarea', 'width'=>400, 'height'=>80, 'name'=>'note', 'value'=>ov('note', $sampleinvoice), 'readonly'=>$readonly),
    'subtotal'=>array('type'=>'label', 'name'=>'subtotal', 'value'=>ov('subtotal', $sampleinvoice), 'width'=>150, 'datatype'=>'money'),
    'discount'=>array('type'=>'textbox', 'name'=>'discount', 'value'=>ov('discount', $sampleinvoice), 'width'=>40, 'readonly'=>$readonly, 'datatype'=>'number', 'onchange'=>'sampleinvoicedetail_discountchange()'),
    'discountamount'=>array('type'=>'textbox', 'name'=>'discountamount', 'value'=>ov('discountamount', $sampleinvoice), 'width'=>120, 'readonly'=>$readonly, 'datatype'=>'money', 'onchange'=>'sampleinvoicedetail_discountamountchange()'),
    'taxable'=>array('type'=>'checkbox', 'name'=>'taxable', 'value'=>ov('taxable', $sampleinvoice), 'width'=>40, 'readonly'=>$readonly, 'onchange'=>"sampleinvoicedetail_taxchange()"),
    'taxamount'=>array('type'=>'label', 'name'=>'taxamount', 'value'=>ov('taxamount', $sampleinvoice), 'width'=>120, 'readonly'=>$readonly, 'datatype'=>'money'),
    'deliverycharge'=>array('type'=>'textbox', 'name'=>'deliverycharge', 'value'=>ov('deliverycharge', $sampleinvoice), 'width'=>120, 'readonly'=>$readonly, 'datatype'=>'money', 'onchange'=>"sampleinvoicedetail_total()"),
    'total'=>array('type'=>'label', 'name'=>'total', 'value'=>ov('total', $sampleinvoice), 'width'=>150, 'datatype'=>'money'),
    'ispaid'=>array('type'=>'checkbox', 'name'=>'ispaid', 'onchange'=>'sampleinvoicedetail_paidchange(value)', 'value'=>ov('ispaid', $sampleinvoice), 'readonly'=>$readonly),
    'usecustomerbalance'=>array('type'=>'checkbox', 'name'=>'usecustomerbalance', 'onchange'=>'sampleinvoicedetail_usecustomerbalancechange(value)', 'value'=>ov('usecustomerbalance', $sampleinvoice), 'readonly'=>$readonly),
    'customerbalance'=>array('type'=>'textbox', 'name'=>'customerbalance','width'=>'150px','datatype'=>'money', 'value'=>ov('customerbalance', $sampleinvoice), 'readonly'=>1),
  );

  $actions = array();
  if(privilege_get('sampleinvoice', 'print')) $actions[] = "<td><button class='green' onclick=\"ui.async('ui_sampleinvoiceprint', [ ui.container_value(ui('.modal')) ], { waitel:this })\"><span class='mdi mdi-printer'></span><label>Cetak</label></button></td>";
  if($mode == 'write')
    $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_sampleinvoicesave', [ ui.container_value(ui('.modal')) ], { waitel:this })\"><span class='fa fa-save'></span><label>Simpan</label></button></td>";
  else{
    if(privilege_get('sampleinvoice', 'modify')) $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_sampleinvoicedetail', [ $id, 'write' ], { waitel:this })\"><span class='fa fa-edit'></span><label>Ubah</label></button></td>";
  }

  if(ov('isreconciled', $sampleinvoice)) $status = "<div class='status info'><label>Sudah Rekonsil</label></div>";

  $c = "<element exp='.modal'>";
  $c .= "
  <div class='statusbar'>$status</div>
  <div class='scrollable padding1020'>
    " . ui_control($controls['id']) . "
    <table class='form'>
      <tr><th><label>Kode</label></th><td>" . ui_control($controls['code']) . "</td><th><label>Gudang</label></th><td>" . ui_control($controls['warehouseid']) . "</td></tr>
      <tr><th><label>Tanggal</label></th><td>" . ui_control($controls['date']) . "</td></tr>
      <tr><th><label>Pelanggan</label></th><td>" . ui_control($controls['customerid']) . "</td></tr>
      <tr><th><label>Alamat</label></th><td>" . ui_control($controls['address']) . "</td></tr>
    </table>
    <div style='height:22px'></div>
    " . ui_gridhead(array('columns'=>$columns, 'gridexp'=>'#grid2')) . "
    " . ui_grid(array('id'=>'grid2', 'name'=>'inventories', 'columns'=>$columns, 'value'=>$value, 'mode'=>'write', 'readonly'=>$readonly)) . "
    <div style='height:22px'></div>
    <div style='height:88'></div>
  </div>
  <div class='foot'>
    <table cellspacing='5'>
      <tr>
        <td style='width: 100%'></td>
        " . implode('', $actions) . "
        <td><button class='hollow' onclick=\"ui.modal_close(ui('.modal'))\"><span class='fa fa-times'></span><label>Close</label></button></td>
      </tr>
    </table>
  </div>
	";
  $c .= "</element>";
  $c .= "
	<script>
		ui.loadscript('rcfx/js/sampleinvoice.js', \"ui.modal_open(ui('.modal'), { closeable:$closeable, width:880, autoheight:true });\");
	</script>
	";
  return $c;

}

function ui_sampleinvoicedetail_customerchange($id){

  $customer = customerdetail(null, array('id'=>$id));
  $customer = object_cast($customer, array('address'=>'address', 'creditterm'=>'creditterm', 'salesmanid'=>'defaultsalesmanid'));
  echo uijs("ui.container_setvalue(ui('.modal'), " . json_encode($customer) . ", 1)");

}
function ui_sampleinvoicedetailcol0($obj, $params){

  $inventorycode = $obj['inventorycode'];
  $inventorydescription = $obj['inventorydescription'];

  $text = '';
  if(strlen($inventorycode) > 0)
    $text = $inventorycode . ' - ' . $inventorydescription;

  $readonly = ov('readonly', $params);
  $value = $obj['inventoryid'];
  $c = ui_autocomplete(array(
      'name'=>'inventoryid',
      'class'=>'flex',
      'src'=>'ui_sampleinvoicedetailcol0_completion',
      'text'=>$text,
      'value'=>$value,
      'onchange'=>"ui.async('ui_sampleinvoicedetailcol0_ex', [ ui.autocomplete_value(ui('%customerdescription')), value, ui.uiid(this.parentNode.parentNode) ], { waitel:this })",
      'readonly'=>$readonly
  ));
  return $c;

}
function ui_sampleinvoicedetailcol0_ex($customerid, $inventorydescription, $trid){

  $inventory = inventorydetail(null, array('description'=>$inventorydescription));
  $inventoryid = $inventory['id'];
  $inventory = object_keys($inventory, array('id', 'unit', 'price'));
  $customer = customerdetail(array('inventories'), array('id'=>$customerid));
  $customerinventories = $customer['inventories'];
  $customerinventories = array_index($customerinventories, array('inventoryid'), 1);
  $inventory['unitprice'] = isset($customerinventories[$inventoryid]) && floatval($customerinventories[$inventoryid]['customerprice']) > 0 ?
      $customerinventories[$inventoryid]['customerprice'] : $inventory['price'];
  return uijs("ui.container_setvalue(ui('$" . $trid . "'), " . json_encode($inventory) . ", 1)");

}
function ui_sampleinvoicedetailcol0_completion($param0){

  $hint = $param0['hint'];
  $inventories = pmrs("SELECT `id` as `value`, concat(code, ' - ', description) as `text` 
      FROM inventory WHERE code LIKE ? OR description LIKE ? AND isactive = 1",
      array("%$hint%", "%$hint%"));
  return $inventories;

}
function ui_sampleinvoicedetailcol1($obj, $params){

  $readonly = ov('readonly', $params);
  $c = ui_textbox(array('name'=>'qty', 'class'=>'block', 'src'=>'', 'value'=>ov('qty', $obj), 'readonly'=>$readonly, 'datatype'=>'number',
      'onchange'=>'sampleinvoicedetail_calculaterow(this.parentNode.parentNode)'));
  return $c;

}
function ui_sampleinvoicedetailcol2($obj){

  $c = ui_label(array('name'=>'unit', 'class'=>'block', 'value'=>ov('unit', $obj)));
  return $c;

}
function ui_sampleinvoicedetailcol5($obj, $params){

  $readonly = ov('readonly', $params);
  $c = "<div class='align-center'>";
  $c .= !$readonly ? "<span class='fa fa-times-circle color-red' onclick=\"ui.grid_remove(this.parentNode.parentNode.parentNode)\"></span>" : '';
  $c .= "</div>";
  return $c;

}
function ui_sampleinvoicedetailcol6($obj, $params){

  $c = ui_label(array('name'=>'code', 'class'=>'block', 'value'=>ov('code', $obj)));
  return $c;

}

function ui_sampleinvoicesave($sampleinvoice){

  if(isset($sampleinvoice['id']) && $sampleinvoice['id'] > 0){
    sampleinvoicemodify($sampleinvoice);
  }
  else{
    sampleinvoiceentry($sampleinvoice);
  }

  return m_load() . uijs("ui.modal_close(ui('.modal'))");

}

function ui_sampleinvoiceremove($id){

  sampleinvoiceremove(array('id'=>$id));

  return m_load();

}

function ui_sampleinvoiceprint($salesinvoice){

  try{ isset($salesinvoice['id']) && intval($salesinvoice['id']) > 0 ? sampleinvoicemodify($salesinvoice) : sampleinvoiceentry($salesinvoice); } catch(Exception $ex) { }

  $c = '';
  $c .= "<element exp='.printarea'>";
  ob_start();
  include 'template/sampleinvoice.php';
  $c .= ob_get_clean();
  $c .= "</element>";
  $c .= "<script type='text/javascript'>
    window.print();
  </script>";
  return $c;

}

function ui_sampleinvoiceexport(){

  global $module;
  $preset = $module['presets'][$module['presetidx']];
  $columns = $preset['columns'];
  $sorts = $preset['sorts'];
  $quickfilters = ov('quickfilters', $preset);
  $filters = $preset['filters'];
  $filters = m_quickfilter_to_filters($filters, $quickfilters);

  $salesinvoice_columnaliases = array(
    'status'=>'t1.status',
    'date'=>'t1.date',
    'code'=>'t1.code',
    'creditterm'=>'t1.creditterm',
    'pocode'=>'t1.pocode',
    'customerid'=>'t1.customerid',
    'customerdescription'=>'t1.customerdescription',
    'avgsalesmargin'=>'t1.avgsalesmargin',
    'subtotal'=>'t1.subtotal',
    'discount'=>'t1.discount',
    'discountamount'=>'t1.discountamount',
    'taxable'=>'t1.taxable',
    'taxamount'=>'t1.taxamount',
    'deliverycharge'=>'t1.deliverycharge',
    'total'=>'t1.total',
    'paymentaccountid'=>'t1.paymentaccountid',
    'paymentamount'=>'t1.paymentamount',
    'paymentdate'=>'t1.paymentdate',
    'inventoryid'=>'t2.inventoryid',
    'inventorycode'=>'t2.inventorycode',
    'inventorydescription'=>'t2.inventorydescription',
    'qty'=>'t2.qty',
    'returnqty'=>'t2.returnqty',
    'unit'=>'t2.unit',
    'unitprice'=>'t2.unitprice',
    'costprice'=>'t2.costprice',
    'totalcostprice'=>'t2.totalcostprice',
    'margin'=>'t2.margin',
    'unitdiscount'=>'t2.unitdiscount',
    'unitdiscountamount'=>'t2.unitdiscountamount',
    'unittotal'=>'t2.unittotal',
    'createdon'=>'t1.createdon',
    'warehousename'=>'t3.name as warehousename',
    'moved'=>'t1.moved'
  );

  $params = array();
  $columnquery = columnquery_from_columnaliases($columns, $salesinvoice_columnaliases);
  $wherequery = 'WHERE t1.id = t2.sampleinvoiceid AND t1.warehouseid = t3.id' . str_replace('WHERE', 'AND', wherequery_from_filters($params, $filters, $salesinvoice_columnaliases));
  $sortquery = sortquery_from_sorts($sorts, $salesinvoice_columnaliases);

  $query = "SELECT 'Sample Invoice' as `type`, t1.id, $columnquery FROM sampleinvoice t1, sampleinvoiceinventory t2, warehouse t3 $wherequery $sortquery $limitquery";
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

  $filepath = 'usr/sample-invoice-' . date('j-M-Y') . '.xlsx';
  array_to_excel($items, $filepath);

  echo uijs("ui('#downloader').href = '$filepath';ui('#downloader').click();");

}

?>