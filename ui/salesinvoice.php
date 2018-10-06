<?php

require_once 'api/code.php';
require_once 'api/customer.php';
require_once 'api/journalvoucher.php';
require_once 'api/salesinvoice.php';

function ui_salesinvoicenew($taxable){

  if(!privilege_get('salesinvoice', 'new')) exc("Anda tidak dapat membuat faktur.");
  return ui_salesinvoicedetail([ 'taxable'=>$taxable ], false);

}
function ui_salesinvoicemodify($id){

  if(!privilege_get('salesinvoice', 'modify')) return ui_salesinvoiceopen($id);
  $salesinvoice = salesinvoicedetail(null, array('id'=>$id));
  return ui_salesinvoicedetail($salesinvoice, false, [ 'isactive_modifiable'=>true ]);

}
function ui_salesinvoiceopen($id){

  $salesinvoice = salesinvoicedetail(null, array('id'=>$id));
  return ui_salesinvoicedetail($salesinvoice);

}

/* Sales invoice detail ui */
function ui_salesinvoicedetail($salesinvoice, $readonly = true, $options = null){

  $id = ov('id', $salesinvoice);
  $code = ov('code', $salesinvoice);
  $isactive = ov('isactive', $salesinvoice, 0, 1);
  $issent = ov('issent', $salesinvoice, 0, 0);
  $date = ov('date', $salesinvoice);
  $customerid = ov('customerid', $salesinvoice);
  $customerdescription = ov('customerdescription', $salesinvoice);
  $taxable = ov('taxable', $salesinvoice);
  $isreconciled = ov('isreconciled', $salesinvoice);
  $pocode = ov('pocode', $salesinvoice);
  $creditterm = ov('creditterm', $salesinvoice, 0, 0);
  $subtotal = ov('subtotal', $salesinvoice);
  $discount = ov('discount', $salesinvoice);
  $discountamount = ov('discountamount', $salesinvoice);
  $taxamount = ov('taxamount', $salesinvoice);
  $deliverycharge = ov('deliverycharge', $salesinvoice);
  $total = ov('total', $salesinvoice);
  $ispaid = ov('ispaid', $salesinvoice);
  $note = ov('note', $salesinvoice);
  $paymentdate = ov('paymentdate', $salesinvoice);
  $usecustomerbalance = ov('usecustomerbalance', $salesinvoice);
  $customerbalance = ov('customerbalance', $salesinvoice);
  $paymentaccountid = ov('paymentaccountid', $salesinvoice, 0, '');
  $paymentaccounttext = ov('paymentaccountid', $salesinvoice, 0, chartofaccountdetail(null, array('code'=>'000.00'))['name']);
  $inventories = $salesinvoice['inventories'];

  $taxable_code = systemvarget('salesinvoice_tax_code');
  $non_taxable_code = systemvarget('salesinvoice_nontax_code');
  $type_code = $taxable && !empty(trim($taxable_code)) ? $taxable_code : $non_taxable_code;
  $type_code2 = $taxable && !empty(trim($taxable_code)) ? 'SIT' : 'SIN';

  // Is new
  $is_new = !isset($salesinvoice['id']) && !$readonly ? 1 : 0;
  if(!$is_new && $issent && in_array(strtolower($_SESSION['user']['position']), [ '' ])) $readonly = true; // Unable to modify salesinvoice that already sent
  $isactive_modifiable = ov('isactive_modifiable', $options);
  $modify_taxcode = userkeystoreget($_SESSION['user']['id'], 'privilege.salesinvoice_modifytaxcode');
  $date = $is_new ? date('Ymd') : $date;
  $code = $is_new ? code_reserve($type_code2, date('Y', strtotime($date)), $type_code) : $code;
  $code_onchanged = $is_new ? "ui.async('ui_salesinvoicenew_codereserve', [ value, $('#taxable').val(), $('#code').val() ]);" : '';
  $closebtn_onclicked = $is_new  ? "if(confirm('Batalkan transaksi ini?')) ui.async('ui_salesinvoicenew_cancel', [ $('#code').val()  ], { waitel:this })" : "ui.modal_close(ui('.modal'))";

  $creditterms = [
    [ 'text'=>'Cash', 'value'=>-1 ],
    [ 'text'=>'-', 'value'=>0 ],
    [ 'text'=>'7 Hari', 'value'=>7 ],
    [ 'text'=>'14 Hari', 'value'=>14 ],
    [ 'text'=>'30 Hari', 'value'=>30 ],
    [ 'text'=>'60 Hari', 'value'=>60 ],
    [ 'text'=>'90 Hari', 'value'=>90 ],
    [ 'text'=>'120 Hari', 'value'=>120 ],
  ];

  $controls = array(
    'id'=>array('type'=>'hidden', 'name'=>'id', 'id'=>'id', 'value'=>$id),
    'taxable'=>array('type'=>'hidden', 'id'=>'taxable', 'name'=>'taxable', 'value'=>$taxable ? 1 : 0),
    'isactive'=>array('type'=>'toggler', 'name'=>'isactive', 'value'=>$isactive, 'readonly'=>!$isactive_modifiable, 'text'=>"Tidak Aktif,Aktif"),
    'code'=>array('type'=>'textbox', 'id'=>'code', 'name'=>'code', 'width'=>'150px', 'readonly'=>$readonly, 'value'=>$code),
    'date'=>array('type'=>'datepicker', 'name'=>'date', 'width'=>'150px', 'readonly'=>$readonly, 'value'=>$date, 'onchange'=>$code_onchanged, 'text_empty'=>"Pilih Tanggal..."),
    'customerid'=>array('type'=>'autocomplete', 'id'=>'customerid', 'name'=>'customerid', 'width'=>'300px', 'src'=>'customerlist_hints_asitems', 'readonly'=>$readonly, 'text'=>$customerdescription, 'value'=>$customerid, 'onchange'=>"ui.async('ui_salesinvoicedetail_customerchange', [ value ], { waitel:this })"),
    'address'=>array('type'=>'textarea', 'name'=>'address', 'width'=>'300px','height'=>'80px', 'readonly'=>$readonly, 'value'=>ov('address', $salesinvoice)),
    'pocode'=>array('type'=>'textbox', 'name'=>'pocode', 'width'=>'150px', 'readonly'=>$readonly, 'value'=>$pocode),
    'creditterm'=>array('type'=>'dropdown', 'name'=>'creditterm', 'width'=>150, 'readonly'=>$readonly, 'value'=>$creditterm, 'items'=>$creditterms),
    'warehouseid'=>array('type'=>'dropdown', 'name'=>'warehouseid', 'items'=>array_cast(warehouselist(null, null), array('text'=>'name', 'value'=>'id')), 'readonly'=>$readonly, 'value'=>ov('warehouseid', $salesinvoice, 0,  ov('lastwarehouseid', $salesinvoice, 0, 1)), 'width'=>150),
    'salesmanid'=>array('type'=>'dropdown', 'name'=>'salesmanid', 'items'=>array_cast(userlist(null, null), array('text'=>'name', 'value'=>'id')), 'readonly'=>$readonly, 'value'=>ov('salesmanid', $salesinvoice, 0, 999), 'width'=>150),
    'tax_code'=>array('type'=>'textbox', 'name'=>'tax_code', 'readonly'=>!$modify_taxcode || $readonly, 'value'=>ov('tax_code', $salesinvoice, 0, ''), 'width'=>240, 'placeholder'=>''),
    'note'=>array('type'=>'textarea', 'width'=>400, 'height'=>80, 'name'=>'note', 'value'=>$note, 'readonly'=>$readonly),
    'subtotal'=>array('type'=>'label', 'name'=>'subtotal', 'value'=>$subtotal, 'width'=>150, 'datatype'=>'money'),
    'discount'=>array('type'=>'textbox', 'name'=>'discount', 'value'=>$discount, 'width'=>40, 'readonly'=>$readonly, 'datatype'=>'number', 'onchange'=>'salesinvoicedetail_discountchange()'),
    'discountamount'=>array('type'=>'textbox', 'name'=>'discountamount', 'value'=>$discountamount, 'width'=>120, 'readonly'=>$readonly, 'datatype'=>'money', 'onchange'=>'salesinvoicedetail_discountamountchange()'),
    'taxamount'=>array('type'=>'label', 'name'=>'taxamount', 'value'=>$taxamount, 'width'=>120, 'readonly'=>$readonly, 'datatype'=>'money'),
    'deliverycharge'=>array('type'=>'textbox', 'name'=>'deliverycharge', 'value'=>$deliverycharge, 'width'=>120, 'readonly'=>$readonly, 'datatype'=>'money', 'onchange'=>"salesinvoicedetail_total()"),
    'total'=>array('type'=>'label', 'name'=>'total', 'value'=>$total, 'width'=>150, 'datatype'=>'money'),
    'ispaid'=>array('type'=>'checkbox', 'name'=>'ispaid', 'onchange'=>'salesinvoicedetail_paidchange(value)', 'value'=>$ispaid, 'readonly'=>$readonly),
    'paymentamount'=>array('type'=>'textbox', 'name'=>'paymentamount','width'=>'150px','datatype'=>'money', 'value'=>ov('paymentamount', $salesinvoice), 'readonly'=>$readonly),
    'paymentaccountid'=>array('type'=>'dropdown', 'name'=>'paymentaccountid','width'=>'150px','items'=>salesinvoicepaymentaccountids(), 'readonly'=>$readonly, 'value'=>$paymentaccountid, 'text'=>$paymentaccounttext, 'align'=>'right', 'placeholder'=>'[Pilih Akun]'),
    'paymentdate'=>array('type'=>'datepicker', 'name'=>'paymentdate', 'width'=>'75px', 'readonly'=>$readonly, 'value'=>$paymentdate, 'align'=>'right', 'placeholder'=>'[Pilih Tanggal]'),
    'usecustomerbalance'=>array('type'=>'checkbox', 'name'=>'usecustomerbalance', 'onchange'=>'salesinvoicedetail_usecustomerbalancechange(value)', 'value'=>$usecustomerbalance, 'readonly'=>$readonly),
    'customerbalance'=>array('type'=>'textbox', 'name'=>'customerbalance','width'=>'150px','datatype'=>'money', 'value'=>$customerbalance, 'readonly'=>1),
  );
  $inventory_controls = array(
    array('active'=>1, 'name'=>'col6', 'text'=>'Kode', 'type'=>'html', 'html'=>'ui_salesinvoicedetailcol6', 'width'=>60),
    array('active'=>1, 'name'=>'col0', 'text'=>'Barang', 'type'=>'html', 'html'=>'ui_salesinvoicedetailcol0', 'width'=>300),
    array('active'=>1, 'name'=>'col1', 'text'=>'Kuantitas', 'type'=>'html', 'html'=>'ui_salesinvoicedetailcol1', 'width'=>80),
    array('active'=>1, 'name'=>'col2', 'text'=>'Satuan', 'type'=>'html', 'html'=>'ui_salesinvoicedetailcol2', 'width'=>60),
    array('active'=>1, 'name'=>'col3', 'text'=>'Harga Satuan', 'type'=>'html', 'html'=>'ui_salesinvoicedetailcol3', 'width'=>100),
    array('active'=>1, 'name'=>'col4', 'text'=>'Total', 'type'=>'html', 'html'=>'ui_salesinvoicedetailcol4', 'width'=>100),
    array('active'=>1, 'name'=>'col5', 'text'=>'Hapus', 'type'=>'html', 'html'=>'ui_salesinvoicedetailcol5', 'width'=>48),
  );
  $closeable = $readonly ? 1 : 0;
  $title = $taxable ? 'Faktur Penjualan Pajak' : 'Faktur Penjualan Tanpa Pajak';
  //$title .= "({$id})";
  $title_class = $taxable ? ' bg-title-light-mint' : ' bg-title-light-blue';
  $status = $isreconciled ? "<div class='status info'><label>Sudah Rekonsil</label></div>" : '';
  //if($is_new && $taxable) $controls['date']['readonly'] = 1; // Prohibit changing date on taxable invoice

  // Bottom options bar
  $actions = [];
  if(privilege_get('salesinvoice', 'print')) $actions[] = "<td><button class='green' onclick=\"ui.async('ui_salesinvoiceprint', [ ui.container_value(ui('.modal')) ], { waitel:this })\"><span class='mdi mdi-printer'></span><label>Cetak</label></button></td>";
  if(!$readonly && !$salesinvoice && privilege_get('salesinvoice', 'new')) $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_salesinvoicesave', [ ui.container_value(ui('.modal')) ], { waitel:this })\"><span class='fa fa-save'></span><label>Simpan</label></button></td>";
  if(!$readonly && $salesinvoice && privilege_get('salesinvoice', 'modify')) $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_salesinvoicesave', [ ui.container_value(ui('.modal')) ], { waitel:this })\"><span class='fa fa-save'></span><label>Simpan</label></button></td>";
  if($readonly && $salesinvoice && privilege_get('salesinvoice', 'modify') && !$is_new && !($issent && in_array(strtolower($_SESSION['user']['position']), [ '' ]))) $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_salesinvoicemodify', [ $id ], { waitel:this })\"><span class='fa fa-edit'></span><label>Ubah</label></button></td>";
  $actions[] = "<td><button class='hollow' onclick=\"$closebtn_onclicked\"><span class='fa fa-times'></span><label>Close</label></button></td>";

  $html = "<element exp='.modal'>";
  $html .= "<div class='head padding10'><h5>$title</h5></div>";
  $html .= "<div class='statusbar'>$status</div>";
  $html .= "	  
    <div class='scrollable padding1020'>
      " . ui_control($controls['id']) . "
      " . ui_control($controls['taxable']) . "
      <table class='form'>
        <tr><th><label>Tanggal</label></th><td>" . ui_control($controls['date']) . "</td><th style='padding-left:150px'><label>Nomor PO</label></th><td>" . ui_control($controls['pocode']) . "</td></tr>
        <tr><th><label>Kode</label></th><td>" . ui_control($controls['code']) . "</td><th><label>Lama Kredit</label></th><td>" . ui_control($controls['creditterm']) . "</td></tr>
        <tr><th><label>Pelanggan</label></th><td>" . ui_control($controls['customerid']) . "</td>" . ( $_SESSION['user']['id'] != 1004 ? "<th><label>Gudang</label></th><td>" . ui_control($controls['warehouseid']) . "</td>" : '<th></th><td></td>' ) . "</tr>
        <tr><th rowspan='2'><label>Alamat</label></th><td rowspan='2'>" . ui_control($controls['address']) . "</td><th><label>Salesman</label></th><td valign='top'>" . ui_control($controls['salesmanid']) . "</td></tr>
        " . ($taxable ? "<tr><th><label>Nomor Pajak</label></th><td valign='top'>" . ui_control($controls['tax_code']) . "</td></tr>" : '') . "
      </table>
      <div style='height:22px'></div>
      " . ui_gridhead(array('columns'=>$inventory_controls, 'gridexp'=>'#grid2')) . "
      " . ui_grid(array('id'=>'grid2', 'name'=>'inventories', 'columns'=>$inventory_controls, 'value'=>$inventories, 'mode'=>'write', 'readonly'=>$readonly)) . "
      <div style='height:22px'></div>
      <div style='height:88px'></div>
      <table class='form' width='99%'>
        <tr>
          <th><label>Catatan</label></th><td valign='top'>" . ui_control($controls['note']) . "</td>
          <td style='width: 99%;' align='right'>
            <table class='form'>
              <tr><th><label>Subtotal</label></th><td align='right'>" . ui_control($controls['subtotal']) . "</td></tr>
              <tr><th><label>Diskon</label></th><td align='right'>" . ui_control($controls['discount']) . "&nbsp;" . ui_control($controls['discountamount']) . "</td></tr>
              " . ($taxable ? "<tr id='taxable_row'><th><label>PPn</label></th><td align='right'>"  . ui_control($controls['taxamount']) . "</td></tr>" : '') . "
              <tr><th><label>Ongkos Kirim</label></th><td align='right'>" . ui_control($controls['deliverycharge']) . "</td></tr>
              <tr><th><label>Total</label></th><td align='right'>" . ui_control($controls['total']) . "</td></tr>
              <tr><td colspan='2'><div style='height:30px'></div></td></tr>
              <tr class='off'><th><label>Hutang Pelanggan</label></th><td align='right'>" . ui_control($controls['usecustomerbalance']) . "&nbsp;" . ui_control($controls['customerbalance']) . "</td></tr>
              <tr><th><label>Lunas</label></th><td align='right'>" . ui_control($controls['ispaid']) . "&nbsp;" . ui_control($controls['paymentamount']) . "</td></tr>
              <tr><th><label>Tanggal Lunas</label></th><td align='right'>" . ui_control($controls['paymentdate']) . "</td></tr>
              <tr><th><label>Akun Pelunasan</label></th><td align='right'>" . ui_control($controls['paymentaccountid']) . "</td></tr>
            </table>
          </td>
        </tr>
      </table>
      <div style='height:88'></div>
    </div>
	";
  $html .= "
    <div class='foot'>
      <table cellspacing='5'>
        <tr>
          <td>" . ($taxable && privilege_get('salesinvoice', 'print') ? "<td><button class='hollow' onclick=\"ui.async('ui_salesinvoiceprint_nontax', [ ui.container_value(ui('.modal')) ], { waitel:this })\"><span class='mdi mdi-printer'></span><label>Cetak Non Pajak</label></button></td>" : '') . "</td>
          <td style='width: 99%'></td>
          " . implode('', $actions) . "
        </tr>
      </table>
    </div>
  ";
  $html .= "</element>";
  $html .= "
	<script>
		ui.loadscript('rcfx/js/salesinvoice.js', \"ui.modal_open(ui('.modal'), { closeable:$closeable, width:980, autoheight:true });\");
	</script>
	";

  $_SESSION['salesinvoice']['id'] = $id;
  $_SESSION['salesinvoice']['taxable'] = $taxable;

  return $html;

}
function ui_salesinvoicedetail_customerchange($id){

  customer_has_due_invoice($id);
  $customer = customerdetail(null, array('id'=>$id));
  $customer = object_cast($customer, array('address'=>'address', 'creditterm'=>'creditterm', 'salesmanid'=>'defaultsalesmanid'));
  echo uijs("ui.container_setvalue(ui('.modal'), " . json_encode($customer) . ", 1)");

}
function ui_salesinvoicedetail_columnresize($name, $width){

  $module = m_loadstate();
  $preset = $module['detailpresets'][$module['detailpresetidx']];

  for($i = 0 ; $i < count($preset['columns']) ; $i++){
    if($preset['columns'][$i]['name'] == $name){
      $preset['columns'][$i]['width'] = $width;
    }
  }

  $module['detailpresets'][$module['detailpresetidx']] = $preset;
  m_savestate($module);

}
function ui_salesinvoicedetailcol0($obj, $params){

  $readonly = ov('readonly', $params);
  $c = ui_autocomplete(array(
    'prehint'=>"return [ $('#taxable').val() ]",
    'name'=>'inventorydescription',
    'class'=>'flex',
    'src'=>'ui_salesinvoicedetailcol0_completion',
    'value'=>ov('inventorydescription', $obj),
    'onchange'=>"ui.async('ui_salesinvoicedetailcol0_ex', [ $('#customerid').val(), value, $('#taxable').val(), ui.uiid(this.parentNode.parentNode) ], { waitel:this })",
    'readonly'=>$readonly,
    'ischild'=>1
  ));
  return $c;

}
function ui_salesinvoicedetailcol0_ex($customerid, $inventorydescription, $taxable, $trid){

  $inventory = inventorydetail(null, array('description'=>$inventorydescription, 'taxable'=>$taxable));
  $inventoryid = $inventory['id'];
  $inventory = object_keys($inventory, array('id', 'code', 'unit', 'price', 'taxable_excluded'));
  $inventory['inventorycode'] = $inventory['code'];
  $customer = customerdetail(array('inventories'), array('id'=>$customerid));
  $customerinventories = $customer['inventories'];
  $customerinventories = array_index($customerinventories, array('inventoryid'), 1);
  $inventory['unitprice'] = isset($customerinventories[$inventoryid]) && floatval($customerinventories[$inventoryid]['customerprice']) > 0 ?
      $customerinventories[$inventoryid]['customerprice'] : $inventory['price'];
  return uijs("ui.container_setvalue(ui('$" . $trid . "'), " . json_encode($inventory) . ", 1)");

}
function ui_salesinvoicedetailcol0_completion($param0){

  $hint = $param0['hint'];
  $taxable = $param0['param'];
  $inventories = pmrs("SELECT description FROM inventory WHERE (code LIKE ? OR description LIKE ?) AND isactive = 1
    and taxable = ?", [ "%$hint%", "%$hint%", $taxable ]);
  $result = array_cast($inventories, array('text'=>'description', 'value'=>'description'));
  return $result;

}
function ui_salesinvoicedetailcol1($obj, $params){

  $readonly = ov('readonly', $params);
  $c = ui_textbox(array('name'=>'qty', 'class'=>'block', 'src'=>'', 'value'=>ov('qty', $obj), 'readonly'=>$readonly, 'datatype'=>'number',
      'onchange'=>'salesinvoicedetail_calculaterow(this.parentNode.parentNode)', 'ischild'=>1));
  return $c;

}
function ui_salesinvoicedetailcol2($obj){

  $c = ui_label(array('name'=>'unit', 'class'=>'block', 'value'=>ov('unit', $obj), 'ischild'=>1));
  return $c;

}
function ui_salesinvoicedetailcol3($obj, $params){

  $readonly = ov('readonly', $params);
  $c = ui_textbox(array('name'=>'unitprice', 'class'=>'block', 'value'=>ov('unitprice', $obj), 'readonly'=>$readonly, 'datatype'=>'money',
      'onchange'=>'salesinvoicedetail_calculaterow(this.parentNode.parentNode)', 'ischild'=>1));
  return $c;

}
function ui_salesinvoicedetailcol4($obj){

  $c = ui_label(array('name'=>'unittotal', 'width'=>'90%', 'value'=>ov('unittotal', $obj), 'datatype'=>'money', 'ischild'=>1));
  return $c;

}
function ui_salesinvoicedetailcol5($obj, $params){

  $readonly = ov('readonly', $params);
  $c = "<div class='align-center'>";
  $c .= !$readonly ? "<span class='fa fa-times-circle color-red' onclick=\"ui.grid_remove(this.parentNode.parentNode.parentNode)\"></span>" : '';
  $c .= "</div>";
  return $c;

}
function ui_salesinvoicedetailcol6($obj){

  $c = ui_label(array('name'=>'inventorycode', 'width'=>'99%', 'value'=>ov('inventorycode', $obj), 'ischild'=>1));
  $c .= ui_hidden(array('name'=>'taxable_excluded', 'value'=>ov('taxable_excluded', $obj), 'ischild'=>1));
  return $c;

}

function ui_salesinvoicenew_codereserve($date, $taxable, $release = ''){

  $code = salesinvoicecode($date, $taxable, $release);
  $script = [];
  $script[] = "$(\"*[data-name='code']\").val(\"$code\")";
  return uijs(implode(';', $script));

}
function ui_salesinvoicenew_cancel($code){

  code_release($code);
  return uijs("ui.modal_close(ui('.modal'))");

}

function ui_salesinvoicesave($obj, $noreload = 0){

  if(isset($obj['id']) && intval($obj['id']) > 0){

    $result = salesinvoicemodify($obj); // Modify

    // Save current warehouseid
    $module = m_loadstate();
    $module['lastwarehouseid'] = $obj['warehouseid'];
    m_savestate($module);

  }
  else{
    $result = salesinvoiceentry($obj);
  }

  $c = '';
  if(!$noreload) $c .= m_load();
  $c .= uijs("ui.modal_close(ui('.modal'))");
  if(isset($result['warnings']) && is_array($result['warnings']) && count($result['warning']) > 0)
    $c .= ui_dialog('Faktur Berhasil Dibuat', implode("<br />", $result['warnings']));
  return $c;

}

function ui_salesinvoiceprint($salesinvoice){

  try{
    $salesinvoice = isset($salesinvoice['id']) && intval($salesinvoice['id']) > 0 ? salesinvoicemodify($salesinvoice) : salesinvoiceentry($salesinvoice);
  }
  catch(Exception $ex) {
    throw $ex;
  }

  $id = $salesinvoice['id'];
  $c = '';
  $c .= "<element exp='.printarea'>";
  ob_start();
  include 'template/salesinvoice.php';
  $c .= ob_get_clean();
  $c .= "</element>";
  $c .= "<script type='text/javascript'>
    window.print();
    ui.control_setvalue(ui('%id', ui('.modal')), " . $id . ");
  </script>";
  return $c;

}
function ui_salesinvoiceprint_nontax($salesinvoice){

  try{
    $salesinvoice = isset($salesinvoice['id']) && intval($salesinvoice['id']) > 0 ? salesinvoicemodify($salesinvoice) : salesinvoiceentry($salesinvoice);
  }
  catch(Exception $ex) {
    throw $ex;
  }

  $id = $salesinvoice['id'];
  $c = '';
  $c .= "<element exp='.printarea'>";
  ob_start();
  include 'template/salesinvoice_nontax.php';
  $c .= ob_get_clean();
  $c .= "</element>";
  $c .= "<script type='text/javascript'>
    window.print();
    ui.control_setvalue(ui('%id', ui('.modal')), " . $id . ");
  </script>";
  return $c;

}
function ui_salesinvoiceremove($id){

  salesinvoiceremove(array('id'=>$id));
  return m_load() . uijs("ui.modal_close(ui('.modal'))");

}
function ui_salesinvoiceclose($id){

  salesinvoicenew_cancel([ 'id'=>$id ]);
  return uijs("ui.modal_close(ui('.modal'))");

}

function ui_salesinvoicemove(){ return ''; }

/* Sales invoice tax ui */
function ui_salesinvoicetaxdetail(){

  $readonly = false;
  $start_date = date('Ymd', mktime(0, 0, 0, date('m'), 1, date('Y')));
  $end_date = date('Ymd', mktime(0, 0, 0, date('m'), date('j') - 14, date('Y')));
  if(strtotime($end_date) < strtotime($start_date)) $end_date = $start_date;

  $controls = [
    'start_date'=>array('type'=>'datepicker', 'name'=>'start_date', 'width'=>'150px', 'readonly'=>$readonly, 'value'=>$start_date, 'onchange'=>'', 'text_empty'=>"Dari"),
    'end_date'=>array('type'=>'datepicker', 'name'=>'end_date', 'width'=>'150px', 'readonly'=>$readonly, 'value'=>$end_date, 'onchange'=>'', 'text_empty'=>"Sampai"),

    'salesinvoices'=>array('type'=>'grid')

  ];

  $actions = [];
  $actions[] = "<td><button class='hollow' onclick=\"ui.modal_close(ui('.modal'))\"><span class='fa fa-times'></span><label>Close</label></button></td>";

  $html = "<element exp='.modal'>";
  $html .= "
    <div class='head padding10' style='border-bottom:none;padding-bottom:0'>
      <table>
        <tr>
          <td>
            " . ui_control($controls['start_date']) . "
            &nbsp;
            " . ui_control($controls['end_date']) . "
          </td>
          <td style='width:100%'></td>
          <td>
            <button id='btntx1' class='hollow' onclick=\"ui.async('ui_salesinvoicetaxdetail_load', [ ui.datepicker_value(ui('%start_date')), ui.datepicker_value(ui('%end_date')) ])\"><span class='fa fa-glass'></span><label>Lihat</label></button>
            <button class='blue' onclick=\"ui.async('ui_salesinvoicetaxcodegenerate', [ ui.datepicker_value(ui('%start_date')), ui.datepicker_value(ui('%end_date')) ])\"><span class='fa fa-play'></span><label>Proses</label></button>
            <button class='green' onclick=\"ui.async('ui_salesinvoicetaxexport', [ ui.datepicker_value(ui('%start_date')), ui.datepicker_value(ui('%end_date')) ])\"><span class='fa fa-download'></span><label>Download</label></button>
            <button class='green' onclick=\"ui.async('ui_salesinvoicetaxnontaxrecap', [ ui.datepicker_value(ui('%start_date')), ui.datepicker_value(ui('%end_date')) ])\"><span class='fa fa-file-text'></span><label>Rekap Non Pajak</label></button>
          </td>
        </tr>
      </table>
      " . ui_gridhead(array('id'=>'mutationdetail_gridhead', 'columns'=>ui_salesinvoicetaxdetail_columns(), 'gridexp'=>'#mutationdetailgrid',
      'oncolumnresize'=>"",
      'oncolumnclick'=>"",
      'oncolumnapply'=>'')) . "
    </div>";
  $html .= "	  
    <div id='scrollable9' class='scrollable' style='padding:0 10px 0 10px'><div class='align-center'>Data not loaded.</div></div>
	";
  $html .= "
    <div class='foot'>
      <table cellspacing='5'>
        <tr>
          <td style='width: 99%'></td>
          " . implode('', $actions) . "
        </tr>
      </table>
    </div>
  ";
  $html .= "</element>";
  $html .= "
	<script>
		ui.modal_open(ui('.modal'), { closeable:false, width:1080, autoheight:true });
	</script>
	";

  return $html;

}
function ui_salesinvoicetaxexport($start_date, $end_date){

  /*global $module;
  $preset = $module['presets'][$module['presetidx']];
  $columns = $preset['columns'];
  $sorts = $preset['sorts'];
  $quickfilters = ov('quickfilters', $preset);
  $filters = $preset['filters'];
  $filters = m_quickfilter_to_filters($filters, $quickfilters);*/

  $sorts = [
    [ 'name'=>'date', 'sorttype'=>'asc' ]
  ];

  $filters = [
    [ 'name'=>'taxable', 'operator'=>'=', 'value'=>1 ],
    [ 'name'=>'date', 'operator'=>'>=', 'value'=>$start_date ],
    [ 'name'=>'date', 'operator'=>'<=', 'value'=>$end_date ],
  ];

  $rows = salesinvoicelist_onlytax('*', $sorts, $filters, null);

  if(!$rows) exc('Tidak ada data untuk di download');

  /*foreach($rows as $row)
    if($row['column0'] == 'FK' && $row['column3'] == '0000000000000') exc('Terdapat faktur tanpa kode pajak, silakan cek terlebih dahulu.');*/

  $data = [];

  $data[] = [
    'FK',
    'KD_JENIS_TRANSAKSI',
    'FG_PENGGANTI',
    'NOMOR_FAKTUR',
    'MASA_PAJAK',
    'TAHUN_PAJAK',
    'TANGGAL_FAKTUR',
    'NPWP',
    'NAMA',
    'ALAMAT_LENGKAP',
    'JUMLAH_DPP',
    'JUMLAH_PPN',
    'JUMLAH_PPNBM',
    'ID_KETERANGAN_TAMBAHAN',
    'FG_UANG_MUKA',
    'UANG_MUKA_DPP',
    'UANG_MUKA_PPN',
    'UANG_MUKA_PPNBM',
    'REFERENSI',
  ];
  $data[] = [
    'LT',
    'NPWP',
    'NAMA',
    'JALAN',
    'BLOK',
    'NOMOR',
    'RT',
    'RW',
    'KECAMATAN',
    'KELURAHAN',
    'KABUPATEN',
    'PROPINSI',
    'KODE_POS',
    'NOMOR_TELEPON',
  ];
  $data[] = [
    'OF',
    'KODE_OBJEK',
    'NAMA',
    'HARGA_SATUAN',
    'JUMLAH_BARANG',
    'HARGA_TOTAL',
    'DISKON',
    'DPP',
    'PPN',
    'TARIF_PPNBM',
    'PPNBM',
  ];

  foreach($rows as $row){
    if($row['column0'] == 'FK' && $row['column3'] == '0000000000000') continue;
    $data[] = array_values($row);
  }

  $filepath = 'usr/salesinvoicetax-' . date('j-M-Y') . '.csv';
  array_to_csv($data, $filepath);
  echo uijs("ui('#downloader').href = '$filepath';ui('#downloader').click();");

  return uijs("ui.modal_close(ui('.modal'))");

}
function ui_salesinvoicetaxcodegenerate($start_date, $end_date){

  salesinvoicetaxcodegenerate($start_date, $end_date);
  return ui_dialog('Proses Berhasil', 'Kode faktur pajak bulan ini berhasil diisi.');

}
function ui_salesinvoicetaxnontaxrecap($start_date, $end_date){

  $result = salesinvoicetaxnontaxrecap($start_date, $end_date);
  return ui_dialog('Rekap Barang Non Pajak', "Jumlah Faktur: $result[count]\nTotal Penjualan: $result[total]
  ");

}
function ui_salesinvoicetaxdetail_columns(){

  $columns = array(
    array('active'=>1, 'name'=>'column0', 'text'=>'FK<br>OF', 'width'=>20, 'nodittomark'=>1),
    array('active'=>1, 'name'=>'column1', 'text'=>'KDJTM<br>NPWP', 'width'=>50, 'nodittomark'=>1),
    array('active'=>1, 'name'=>'column2', 'text'=>'FGPM<br>NAMA', 'width'=>100, 'nodittomark'=>1),
    array('active'=>1, 'name'=>'column3', 'text'=>'NMR FAKTUR<br>JALAN', 'width'=>100, 'nodittomark'=>1),
    array('active'=>1, 'name'=>'column4', 'text'=>'MP<br>BLOK', 'width'=>36, 'nodittomark'=>1),
    array('active'=>1, 'name'=>'column5', 'text'=>'TP<br>NOMOR', 'width'=>70, 'nodittomark'=>1),
    array('active'=>1, 'name'=>'column6', 'text'=>'TF<br>RT', 'width'=>70, 'nodittomark'=>1),
    array('active'=>1, 'name'=>'column7', 'text'=>'NPWP<br>RW', 'width'=>110, 'nodittomark'=>1),
    array('active'=>1, 'name'=>'column8', 'text'=>'NAMA<br>KEC', 'width'=>100, 'nodittomark'=>1),
    array('active'=>1, 'name'=>'column9', 'text'=>'ALMT<br>KEL', 'width'=>50, 'nodittomark'=>1),
    array('active'=>1, 'name'=>'column10', 'text'=>'JDPP<br>KAB', 'width'=>50, 'nodittomark'=>1),
  );
  return $columns;

}
function ui_salesinvoicetaxdetail_load($start_date, $end_date){

  $filters = [
    [ 'name'=>'date', 'operator'=>'>=', 'value'=>$start_date ],
    [ 'name'=>'date', 'operator'=>'<=', 'value'=>$end_date ],
  ];

  $sorts = [
    [ 'name'=>'date', 'sorttype'=>'asc' ],
    [ 'name'=>'id', 'sorttype'=>'asc' ],
  ];

  $c = "<element exp='#scrollable9'>
      " . ui_grid2(array('id'=>'mutationdetailgrid', 'columns'=>ui_salesinvoicetaxdetail_columns(), 'datasource'=>'salesinvoicelist_onlytax', 'filters'=>$filters,
      'sorts'=>$sorts, 'scrollel'=>'#scrollable9')) . "
    </element>";

  return $c;

}

?>