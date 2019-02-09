<?php

require_once 'api/purchaseinvoice.php';

function ui_purchaseinvoicenew(){

  if(!privilege_get('purchaseinvoice', 'new')) exc("Anda tidak dapat membuat faktur pembelian.");
  return ui_purchaseinvoicedetail([], false, [ 'confirm_on_close'=>true ]);

}
function ui_purchaseinvoicemodify($id){

  if(!privilege_get('purchaseinvoice', 'modify')) return ui_purchaseinvoiceopen($id);
  $purchaseinvoice = purchaseinvoicedetail(null, array('id'=>$id));
  return ui_purchaseinvoicedetail($purchaseinvoice, false, [ 'isactive_modifiable'=>true ]);


}
function ui_purchaseinvoiceopen($id){

  $purchaseinvoice = purchaseinvoicedetail(null, array('id'=>$id));
  return ui_purchaseinvoicedetail($purchaseinvoice);

}

function ui_purchaseinvoicedetail($purchaseinvoice, $readonly = true, $options = null){

  $id = ov('id', $purchaseinvoice);
  $code = ov('code', $purchaseinvoice);
  $date = ov('date', $purchaseinvoice);
  $inventories = ov('inventories', $purchaseinvoice, 0, []);
  $pocode = ov('pocode', $purchaseinvoice);
  $address = ov('address', $purchaseinvoice, 0);
  $ispaid = ov('ispaid', $purchaseinvoice);
  $currencyid = ov('currencyid', $purchaseinvoice, 0, 1);
  $currencyrate = ov('currencyrate', $purchaseinvoice, 0, 1);
  $note = ov('note', $purchaseinvoice);
  $subtotal = ov('subtotal', $purchaseinvoice, 0);
  $discount = ov('discount', $purchaseinvoice, 0);
  $taxable = ov('taxable', $purchaseinvoice, 0);
  $pph = ov('pph', $purchaseinvoice, 0);
  $kso = ov('kso', $purchaseinvoice, 0);
  $ski = ov('ski', $purchaseinvoice, 0);
  $clearance_fee = ov('clearance_fee', $purchaseinvoice, 0);
  $import_cost = ov('import_cost', $purchaseinvoice, 0);
  $freightcharge = ov('freightcharge', $purchaseinvoice, 0);
  $total = ov('total', $purchaseinvoice, 0);
  $paymentdate = ov('paymentdate', $purchaseinvoice, 0);
  $handlingfeevolume = ov('handlingfeevolume', $purchaseinvoice, 0);
  $handlingfeedate = ov('handlingfeedate', $purchaseinvoice, 0);
  $handlingfeeaccountid = ov('handlingfeeaccountid', $purchaseinvoice);
  $handlingfeepaymentamount = ov('handlingfeepaymentamount', $purchaseinvoice, 0);
  $paymentamount = ov('paymentamount', $purchaseinvoice, 0);
  $supplierdescription = ov('supplierdescription', $purchaseinvoice);
  $discountamount = ov('discountamount', $purchaseinvoice, 0);
  $warehouseid = ov('warehouseid', $purchaseinvoice, 0, 1);
  $purchaseorderid = ov('purchaseorderid', $purchaseinvoice, 0, 0);
  $tax_code = ov('tax_code', $purchaseinvoice, 0, '');

  $downpaymentamount = ov('downpaymentamount', $purchaseinvoice, 0, 0);
  $downpaymentamount_in_currency = ov('downpaymentamount_in_currency', $purchaseinvoice, 0, 0);
  $downpaymentdate = ov('downpaymentdate', $purchaseinvoice, 0);
  $downpaymentaccountid = ov('downpaymentaccountid', $purchaseinvoice);

  $taxamount = ov('taxamount', $purchaseinvoice, 0, 0);
  $taxdate = ov('taxdate', $purchaseinvoice);
  $taxaccountid = ov('taxaccountid', $purchaseinvoice);

  $paymentaccountid = ov('paymentaccountid', $purchaseinvoice);
  $paymentamount_in_currency = ov('paymentamount_in_currency', $purchaseinvoice);

  $pphdate = ov('pphdate', $purchaseinvoice, 0);
  $ksodate = ov('ksodate', $purchaseinvoice, 0);
  $skidate = ov('skidate', $purchaseinvoice, 0);
  $clearance_fee_date = ov('clearance_fee_date', $purchaseinvoice, 0);
  $import_cost_date = ov('import_cost_date', $purchaseinvoice, 0);

  $pphaccountid = ov('pphaccountid', $purchaseinvoice, 0);
  $ksoaccountid = ov('ksoaccountid', $purchaseinvoice, 0);
  $skiaccountid = ov('skiaccountid', $purchaseinvoice, 0);
  $clearance_fee_accountid = ov('clearance_fee_accountid', $purchaseinvoice, 0);
  $import_cost_accountid = ov('import_cost_accountid', $purchaseinvoice, 0);

  $is_new = !isset($purchaseinvoice['id']) && !$readonly ? 1 : 0;
  $date = $is_new ? date('Ymd') : $date;
  $code = $is_new ? purchaseinvoicecode($date, $taxable) : $code;
  $closable = $readonly ? 1 : 0;
  $removable = purchaseinvoiceremovable(array('id'=>$id));
  $title = $taxable ? 'Faktur Pembelian' : '';

  $purchaseorder = purchaseorderdetail(null, array('id'=>$purchaseinvoice['purchaseorderid']));

  $tax_readonly = $readonly;
  if(isset($purchaseorder['taxamount']) && $purchaseorder['taxamount'] > 0){
    $taxamount = $purchaseorder['taxamount'];
    $taxdate = $purchaseorder['taxdate'];
    $taxaccountid = $purchaseorder['taxaccountid'];
    $tax_readonly = true;
  }

  $pph_readonly = $readonly;
  if(isset($purchaseorder['pph']) && $purchaseorder['pph'] > 0){
    $pph = $purchaseorder['pph'];
    $pphdate = $purchaseorder['pphdate'];
    $pphaccountid = $purchaseorder['pphaccountid'];
    $pph_readonly = true;
  }

  $kso_readonly = $readonly;
  if(isset($purchaseorder['kso']) && $purchaseorder['kso'] > 0){
    $kso = $purchaseorder['kso'];
    $ksodate = $purchaseorder['ksodate'];
    $ksoaccountid = $purchaseorder['ksoaccountid'];
    $kso_readonly = true;
  }

  $ski_readonly = $readonly;
  if(isset($purchaseorder['ski']) && $purchaseorder['ski'] > 0){
    $ski = $purchaseorder['ski'];
    $skidate = $purchaseorder['skidate'];
    $skiaccountid = $purchaseorder['skiaccountid'];
    $ski_readonly = true;
  }

  $cf_readonly = $readonly;
  if(isset($purchaseorder['clearance_fee']) && $purchaseorder['clearance_fee'] > 0){
    $clearance_fee = $purchaseorder['clearance_fee'];
    $clearance_fee_date = $purchaseorder['clearance_fee_date'];
    $clearance_fee_accountid = $purchaseorder['clearance_fee_accountid'];
    $cf_readonly = true;
  }

  $import_cost_readonly = $readonly;
  if(isset($purchaseorder['import_cost']) && $purchaseorder['import_cost'] > 0){
    foreach($inventories as $index=>$inventory){
      $inventories[$index]['purchaseorder_exists'] = 1;
      $inventories[$index]['purchaseorder_import_cost'] = $purchaseorder['import_cost'];
    }
    $import_cost = $purchaseorder['import_cost'];
    $import_cost_date = $purchaseorder['import_cost_date'];
    $import_cost_accountid = $purchaseorder['import_cost_accountid'];
    $import_cost_readonly = true;
  }

  $hf_readonly = $readonly;
  if(isset($purchaseorder['handlingfeepaymentamount']) && $purchaseorder['handlingfeepaymentamount'] > 0){
    $handlingfeepaymentamount = $purchaseorder['handlingfeepaymentamount'];
    $handlingfeedate = $purchaseorder['handlingfeedate'];
    $handlingfeeaccountid = $purchaseorder['handlingfeeaccountid'];
    $hf_readonly = true;
  }

  $detailcolumns = array(
    array('active'=>1, 'name'=>'col7', 'text'=>'Kode', 'type'=>'html', 'html'=>'ui_purchaseinvoicedetail_col7', 'width'=>80),
    array('active'=>1, 'name'=>'col0', 'text'=>'Barang', 'type'=>'html', 'html'=>'ui_purchaseinvoicedetail_col0', 'width'=>200),
    array('active'=>1, 'name'=>'col1', 'text'=>'Kts', 'type'=>'html', 'html'=>'ui_purchaseinvoicedetail_col1', 'width'=>70, 'align'=>'right'),
    array('active'=>1, 'name'=>'col2', 'text'=>'Satuan', 'type'=>'html', 'html'=>'ui_purchaseinvoicedetail_col2', 'width'=>50),
    array('active'=>1, 'name'=>'col3', 'text'=>'Harga', 'type'=>'html', 'html'=>'ui_purchaseinvoicedetail_col3', 'width'=>100, 'align'=>'right'),
    array('active'=>0, 'name'=>'col4', 'text'=>'Diskon', 'type'=>'html', 'html'=>'ui_purchaseinvoicedetail_col4', 'width'=>60, 'align'=>'right'),
    array('active'=>1, 'name'=>'col5', 'text'=>'Total', 'type'=>'html', 'html'=>'ui_purchaseinvoicedetail_col5', 'width'=>100, 'align'=>'right'),
    array('active'=>1, 'name'=>'col6', 'text'=>"<span class='fa fa-times-circle color-red'></span>", 'type'=>'html', 'html'=>'ui_purchaseinvoicedetail_col6', 'width'=>24, 'align'=>'center'),
    array('active'=>1, 'name'=>'col8', 'text'=>"Bea Masuk", 'type'=>'html', 'html'=>'ui_purchaseinvoicedetail_col9', 'width'=>90, 'align'=>'right', 'class'=>'bg-light-yellow'),
    array('active'=>1, 'name'=>'col8', 'text'=>"Harga Modal", 'type'=>'html', 'html'=>'ui_purchaseinvoicedetail_col8', 'width'=>100, 'align'=>'right', 'class'=>'bg-light-yellow'),
  );

  $chartofaccounts = chartofaccountlist(null, null, [ [ 'name'=>'code', 'operator'=>'contains', 'value'=>'100.' ] ]);
  $chartofaccounts = array_cast($chartofaccounts, array('text'=>'name', 'value'=>'id'));

  $date_onchanged = $is_new ? "ui.async('ui_purchaseinvoicedetail_datechanged', [ value, $('#taxable').val(), $('#code').val() ]);" : '';
  $closebtn_onclicked = $is_new ? "if(confirm('Batalkan transaksi ini?')) ui.async('ui_purchaseinvoiceclose', [ $('#code').val() ], { waitel:this })" : "ui.modal_close(ui('.modal'))";

  $controls = [

    'id'=>array('type'=>'hidden', 'name'=>'id', 'id'=>'id', 'value'=>$id),
    'date'=>array('type'=>'datepicker', 'name'=>'date', 'value'=>$date, 'width'=>150, 'readonly'=>$readonly, 'onchange'=>$date_onchanged, 'text_empty'=>"Pilih Tanggal..."),
    'taxable'=>array('type'=>'hidden', 'id'=>'taxable', 'name'=>'taxable', 'value'=>$taxable ? 1 : 0),
    'tax_code'=>array('type'=>'textbox', 'id'=>'tax_code', 'name'=>'tax_code', 'width'=>'360px', 'value'=>$tax_code, 'readonly'=>$readonly),
    'purchaseorderid'=>array('type'=>'hidden', 'name'=>'purchaseorderid', 'value'=>$purchaseorderid),
    'code'=>array('type'=>'textbox', 'id'=>'code', 'name'=>'code', 'value'=>$code, 'width'=>150, 'readonly'=>$readonly),
    'supplierdescription'=>array('type'=>'autocomplete', 'name'=>'supplierdescription', 'value'=>$supplierdescription, 'src'=>'ui_purchaseinvoicedetail_supplierhint', 'width'=>400, 'readonly'=>$readonly, 'onchange'=>"purchaseinvoice_supplierchange(value)"),
    'poid'=>array('type'=>'hidden', 'name'=>'poid', 'value'=>ov('purchaseorderid', $purchaseinvoice)),
    'pocode'=>array('type'=>'label', 'name'=>'pocode', 'value'=>$pocode),
    'address'=>array('type'=>'textarea', 'name'=>'address', 'value'=>$address, 'width'=>400, 'height'=>60, 'readonly'=>$readonly),
    'currencyid'=>array('type'=>'dropdown', 'name'=>'currencyid', 'value'=>$currencyid, 'width'=>150, 'items'=>array_cast(currencylist(), array('text'=>'name', 'value'=>'id')), 'readonly'=>$readonly),
    'currencyrate'=>array('type'=>'hidden', 'name'=>'currencyrate', 'value'=>$currencyrate, 'width'=>120, 'readonly'=>$readonly, 'align'=>'left', 'datatype'=>'money', 'onchange'=>"purchaseinvoice_total()"),
    'note'=>array('type'=>'textarea', 'name'=>'note', 'value'=>$note, 'width'=>380, 'height'=>90, 'readonly'=>$readonly),
    'subtotal'=>array('type'=>'label', 'name'=>'subtotal', 'value'=>$subtotal, 'width'=>150, 'datatype'=>'money', 'readonly'=>$readonly),
    'discount'=>array('type'=>'textbox', 'name'=>'discount', 'value'=>$discount, 'width'=>60, 'datatype'=>'number', 'readonly'=>$readonly, 'onchange'=>"purchaseinvoice_discountchange()"),
    'discountamount'=>array('type'=>'textbox', 'name'=>'discountamount', 'value'=>$discountamount, 'width'=>150, 'datatype'=>'money', 'readonly'=>$readonly, 'onchange'=>"purchaseinvoice_discountamountchange()"),

    'downpaymentamount'=>array('type'=>'textbox', 'name'=>'downpaymentamount', 'datatype'=>'money', 'value'=>$downpaymentamount, 'align'=>'right', 'readonly'=>1, 'width'=>150),

    'downpaymentamount_in_currency'=>array('type'=>'textbox', 'name'=>'downpaymentamount_in_currency', 'datatype'=>'money', 'value'=>$downpaymentamount_in_currency, 'align'=>'right', 'readonly'=>1, 'width'=>150),
    'downpaymentdate'=>array('type'=>'datepicker', 'name'=>'downpaymentdate', 'value'=>$downpaymentdate, 'readonly'=>$readonly, 'onchange'=>"", 'align'=>'right', 'readonly'=>1),
    'downpaymentaccountid'=>array('type'=>'dropdown', 'name'=>'downpaymentaccountid', 'value'=>$downpaymentaccountid, 'items'=>$chartofaccounts, 'readonly'=>1, 'width'=>150, 'onchange'=>"", 'align'=>'right'),

    'paymentdate'=>array('type'=>'datepicker', 'name'=>'paymentdate', 'value'=>$paymentdate, 'readonly'=>$readonly, 'onchange'=>"", 'align'=>'right'),
    'paymentaccountid'=>array('type'=>'dropdown', 'name'=>'paymentaccountid', 'value'=>$paymentaccountid, 'items'=>$chartofaccounts, 'readonly'=>$readonly, 'width'=>150, 'onchange'=>"", 'align'=>'right'),
    'paymentamount'=>array('type'=>'textbox', 'name'=>'paymentamount', 'value'=>$paymentamount, 'readonly'=>1, 'width'=>150, 'datatype'=>'money', 'onchange'=>"purchaseinvoice_onpaymentamountchange()"),
    'paymentamount_in_currency'=>array('type'=>'textbox', 'name'=>'paymentamount_in_currency', 'value'=>$paymentamount_in_currency, 'readonly'=>1, 'width'=>150, 'datatype'=>'money', 'onchange'=>"purchaseinvoice_onpaymentamountchange()"),

    'taxamount'=>array('type'=>'textbox', 'name'=>'taxamount', 'value'=>$taxamount, 'width'=>150, 'datatype'=>'money', 'readonly'=>$tax_readonly, 'onchange'=>"purchaseinvoice_total()"),
    'taxdate'=>array('type'=>'datepicker', 'name'=>'taxdate', 'value'=>$taxdate, 'readonly'=>$readonly, 'onchange'=>"", 'align'=>'right', 'readonly'=>$tax_readonly, 'onchange'=>"purchaseinvoice_total()"),
    'taxaccountid'=>array('type'=>'dropdown', 'name'=>'taxaccountid', 'value'=>$taxaccountid, 'items'=>$chartofaccounts, 'readonly'=>$tax_readonly, 'width'=>150, 'onchange'=>"purchaseinvoice_total()", 'align'=>'right'),

    'pph'=>array('type'=>'textbox', 'name'=>'pph', 'value'=>$pph, 'width'=>150, 'datatype'=>'money', 'readonly'=>$pph_readonly, 'onchange'=>"purchaseinvoice_total()"),
    'pphdate'=>array('type'=>'datepicker', 'name'=>'pphdate', 'value'=>$pphdate, 'datatype'=>'money', 'readonly'=>$pph_readonly, 'onchange'=>"purchaseinvoice_total()", 'align'=>'right'),
    'pphaccountid'=>array('type'=>'dropdown', 'name'=>'pphaccountid', 'value'=>$pphaccountid, 'width'=>150, 'items'=>$chartofaccounts, 'readonly'=>$pph_readonly, 'onchange'=>"purchaseinvoice_total()", 'align'=>'right'),

    'kso'=>array('type'=>'textbox', 'name'=>'kso', 'value'=>$kso, 'width'=>150, 'datatype'=>'money', 'readonly'=>$kso_readonly, 'onchange'=>"purchaseinvoice_total()"),
    'ksodate'=>array('type'=>'datepicker', 'name'=>'ksodate', 'value'=>$ksodate, 'datatype'=>'money', 'readonly'=>$kso_readonly, 'onchange'=>"purchaseinvoice_total()", 'align'=>'right'),
    'ksoaccountid'=>array('type'=>'dropdown', 'name'=>'ksoaccountid', 'value'=>$ksoaccountid, 'width'=>150, 'items'=>$chartofaccounts, 'readonly'=>$kso_readonly, 'onchange'=>"purchaseinvoice_total()", 'align'=>'right'),

    'ski'=>array('type'=>'textbox', 'name'=>'ski', 'value'=>$ski, 'width'=>150, 'datatype'=>'money', 'readonly'=>$ski_readonly, 'onchange'=>"purchaseinvoice_total()"),
    'skidate'=>array('type'=>'datepicker', 'name'=>'skidate', 'value'=>$skidate, 'datatype'=>'money', 'readonly'=>$ski_readonly, 'onchange'=>"purchaseinvoice_total()", 'align'=>'right'),
    'skiaccountid'=>array('type'=>'dropdown', 'name'=>'skiaccountid', 'value'=>$skiaccountid, 'width'=>150, 'items'=>$chartofaccounts, 'readonly'=>$ski_readonly, 'onchange'=>"purchaseinvoice_total()", 'align'=>'right'),

    'clearance_fee'=>array('type'=>'textbox', 'name'=>'clearance_fee', 'value'=>$clearance_fee, 'width'=>150, 'datatype'=>'money', 'readonly'=>$cf_readonly, 'onchange'=>"purchaseinvoice_total()"),
    'clearance_fee_date'=>array('type'=>'datepicker', 'name'=>'clearance_fee_date', 'value'=>$clearance_fee_date, 'datatype'=>'money', 'readonly'=>$cf_readonly, 'onchange'=>"purchaseinvoice_total()", 'align'=>'right'),
    'clearance_fee_accountid'=>array('type'=>'dropdown', 'name'=>'clearance_fee_accountid', 'value'=>$clearance_fee_accountid, 'width'=>150, 'items'=>$chartofaccounts, 'readonly'=>$cf_readonly, 'onchange'=>"purchaseinvoice_total()", 'align'=>'right'),

    'import_cost'=>array('type'=>'textbox', 'name'=>'import_cost', 'value'=>$import_cost, 'width'=>150, 'datatype'=>'money', 'readonly'=>1, 'onchange'=>"purchaseinvoice_total()"),
    'import_cost_date'=>array('type'=>'datepicker', 'name'=>'import_cost_date', 'value'=>$import_cost_date, 'datatype'=>'money', 'readonly'=>$import_cost_readonly, 'onchange'=>"purchaseinvoice_total()", 'align'=>'right'),
    'import_cost_accountid'=>array('type'=>'dropdown', 'name'=>'import_cost_accountid', 'value'=>$import_cost_accountid, 'width'=>150, 'items'=>$chartofaccounts, 'readonly'=>$import_cost_readonly, 'onchange'=>"purchaseinvoice_total()", 'align'=>'right'),

    'handlingfeepaymentamount'=>array('type'=>'textbox', 'name'=>'handlingfeepaymentamount', 'value'=>$handlingfeepaymentamount, 'readonly'=>$hf_readonly, 'width'=>150, 'datatype'=>'money', 'onchange'=>"purchaseinvoice_total()"),
    'handlingfeedate'=>array('type'=>'datepicker', 'name'=>'handlingfeedate', 'value'=>$handlingfeedate, 'readonly'=>$hf_readonly, 'align'=>'right'),
    'handlingfeeaccountid'=>array('type'=>'dropdown', 'name'=>'handlingfeeaccountid', 'value'=>$handlingfeeaccountid, 'items'=>$chartofaccounts, 'readonly'=>$hf_readonly, 'width'=>150, 'onchange'=>"", 'align'=>'right'),

    'freightcharge'=>array('type'=>'textbox', 'name'=>'freightcharge', 'value'=>$freightcharge, 'width'=>150, 'datatype'=>'money', 'readonly'=>$readonly, 'onchange'=>"purchaseinvoice_total()"),
    'total'=>array('type'=>'label', 'name'=>'total', 'value'=>$total, 'width'=>150, 'datatype'=>'money', 'readonly'=>$readonly),
    'ispaid'=>array('type'=>'checkbox', 'name'=>'ispaid', 'value'=>$ispaid, 'readonly'=>1, 'onchange'=>"purchaseinvoice_ispaid()"),
    'warehouseid'=>array('type'=>'dropdown', 'name'=>'warehouseid', 'value'=>$warehouseid, 'items'=>array_cast(warehouselist(), array('text'=>'name', 'value'=>'id')), 'readonly'=>$readonly, 'width'=>150, 'onchange'=>""),
    'handlingfeevolume'=>array('type'=>'textbox', 'name'=>'handlingfeevolume', 'value'=>$handlingfeevolume, 'placeholder'=>'Volume...', 'readonly'=>$readonly, 'width'=>80, 'datatype'=>'number'),
    'items'=>array('columns'=>$detailcolumns, 'name'=>'inventories', 'value'=>$inventories, 'mode'=>'write', 'readonly'=>$readonly, 'id'=>'inventories', 'onremove'=>"purchaseinvoice_total()", 'write_no_add'=>($purchaseorder ? 1 : 0)),
    'itemshead'=>[ 'columns'=>$detailcolumns, 'gridexp'=>'#inventories' ],

  ];

  $payments = isset($purchaseinvoice['payments']) ? $purchaseinvoice['payments'] : [];
  for($i = 0 ; $i < 5 ; $i++){

    $n_paymentamount = isset($payments[$i]['paymentamount']) ? $payments[$i]['paymentamount'] : 0;
    $n_paymentcurrencyrate = isset($payments[$i]['paymentcurrencyrate']) ? $payments[$i]['paymentcurrencyrate'] : 0;
    $n_paymentdate = isset($payments[$i]['paymentdate']) ? $payments[$i]['paymentdate'] : '';
    $n_paymentaccountid = isset($payments[$i]['paymentaccountid']) ? $payments[$i]['paymentaccountid'] : 0;

    if($n_paymentcurrencyrate < 1) $n_paymentcurrencyrate = 1;

    $controls["paymentamount_$i"] = array('type'=>'textbox', 'class'=>'paymentamount', 'name'=>"paymentamount-$i", 'value'=>$n_paymentamount,
      'readonly'=>$readonly, 'width'=>150, 'datatype'=>'money', 'onchange'=>'purchaseinvoice_calculate()');
    $controls["paymentcurrencyrate_$i"] = array('type'=>'textbox', 'class'=>'paymentcurrencyrate', 'name'=>"paymentcurrencyrate-$i",
      'value'=>$n_paymentcurrencyrate, 'readonly'=>$readonly, 'width'=>150, 'datatype'=>'money', 'onchange'=>'purchaseinvoice_calculate()');
    $controls["paymentdate_$i"] = array('type'=>'datepicker', 'class'=>'paymentdate', 'name'=>"paymentdate-$i",
      'value'=>$n_paymentdate, 'readonly'=>$readonly, 'align'=>'right', 'onchange'=>'purchaseinvoice_calculate()');
    $controls["paymentaccountid_$i"] = array('type'=>'dropdown', 'class'=>'paymentaccountid', 'name'=>"paymentaccountid-$i",
      'value'=>$n_paymentaccountid, 'items'=>$chartofaccounts,
      'readonly'=>$readonly, 'width'=>150, 'align'=>'right', 'onchange'=>'purchaseinvoice_calculate()');

  }

  // Controls setup change on existence of purchase order
  if(is_array($purchaseorder)){

    // Unable to set this property if purchase order exists

    $controls['code']['readonly'] = 1;
    $controls['supplierdescription']['readonly'] = 1; // Supplier name is unmodifiable
    $controls['address']['readonly'] = 1;

    //$controls['items']['readonly'] = 1;

    $detailcolumns[2]['readonly'] = 1;
    $detailcolumns[4]['readonly'] = 1;
    $controls['items']['columns'] = $detailcolumns;

    if($purchaseorder['paymentamount'] > 0){

      $controls['discount']['readonly'] = 1;
      $controls['discountamount']['readonly'] = 1;
      $controls['freightcharge']['readonly'] = 1;

    }

  }

  // Action Controls
  $actions = array();
  if($removable && !$readonly && $purchaseinvoice && privilege_get('purchaseinvoice', 'delete')) $actions[] = "<td><button class='red' style='margin-right:50px;width:88px' onclick=\"if(confirm('Hapus $code?')) ui.async('ui_purchaseinvoiceremove', [ $id ], { waitel:this, callback:'purchaseinvoice_onremovecompleted()' })\"><span class='fa fa-trash-o''></span><label>Hapus</label></button></td>";
  $actions[] = "<td style='width: 100%'></td>";
  if(!$readonly && !$purchaseinvoice && privilege_get('purchaseinvoice', 'new')) $actions[] = "<td><button class='blue' style='width:88px' onclick=\"ui.async('ui_purchaseinvoicesave', [ ui.container_value(ui('.modal')) ], { waitel:this })\"><span class='fa fa-check'></span><label>" . lang('002') . "</label></button></td>";
  if(!$readonly && $purchaseinvoice && privilege_get('purchaseinvoice', 'modify')) $actions[] = "<td><button class='blue' style='width:88px' onclick=\"ui.async('ui_purchaseinvoicesave', [ ui.container_value(ui('.modal')) ], { waitel:this })\"><span class='fa fa-check'></span><label>" . lang('002') . "</label></button></td>";
  if($readonly && $purchaseinvoice && privilege_get('purchaseinvoice', 'modify')) $actions[] = "<td><button class='blue' style='width:88px' onclick=\"ui.async('ui_purchaseinvoicemodify', [ $id ], { waitel:this })\"><span class='fa fa-edit'></span><label>" . lang('001') . "</label></button></td>";
  $actions[] = "<td><button style='width:88px' class='hollow' onclick=\"$closebtn_onclicked\"><span class='fa fa-times'></span><label>Close</label></button></td>";

  //<div class='statusbar'>$status</div>


  $c = "<element exp='.modal'>";
  $c .= "
    <div class='head padding10'><h5>$title</h5></div>    
    <div class='scrollable padding1020'>
      " . ui_control($controls['id']) . "
      " . ui_control($controls['purchaseorderid']) . "
      " . ui_control($controls['currencyrate']) . "
      <table class='form'>
        " . ui_formrow('Tanggal', ui_control($controls['date'])) . "
        " . ui_formrow('Kode', ui_control($controls['code'])) . "
        " . ui_formrow('Supplier', ui_control($controls['supplierdescription'])) . "
        " . ui_formrow('Alamat', ui_control($controls['address'])) . "
      </table>
      <table class='form'>
        " . ui_formrow('Mata Uang', ui_control($controls['currencyid'])) . "
        " . "
        " . ui_formrow('Gudang', ui_control($controls['warehouseid'])) . "
        " . (ov('purchaseorderid', $purchaseinvoice) > 0 ? "<tr><th><label>Nomor PO</label></th><td>" . ui_control($controls['pocode']) . "<button class='hollow' onclick=\"ui.async('ui_purchaseorderdetail', [ " . ov('purchaseorderid', $purchaseinvoice) . ", 'read', { callback:'ui_purchaseinvoicedetail', params:[ $id, 'read' ] } ], { waitel:this })\"><label>Buka Pesanan</label></button></td></tr>" : '') . "
        <tr class='tax-ctl' style='display:none'><th><label>Kode Pajak</label></th><td>" . ui_control($controls['tax_code']) . "</td></tr>
      </table>
      <div style='height:22px'></div>
      <div>
        " . ui_gridhead($controls['itemshead']) . "
        " . ui_grid($controls['items']) . "
      </div>
      <div style='height:22px'></div>
      <table cellspacing='0'>
        <tr>
          <td class='valign-top'>
            <table class='form'>
              " . ui_formrow('Catatan', ui_control($controls['note'])) . "
            </table>
          </td>
          <td style='width:100%'></td>
          <td class='valign-top'>
            <table class='form'>
              <tr><th><label>Subtotal</label></th><td></td><td align='right'>" . ui_control($controls['subtotal']) . "</td></tr>
              <tr><th><label>Diskon</label></th><td>" . ui_control($controls['discount']) . "</td><td align='right'>" . ui_control($controls['discountamount']) . "</td></tr>
              <tr><th><label>Freight</label></th><td></td><td align='right'>" . ui_control($controls['freightcharge']) . "</td></tr>
              <tr><th><label>Total</label></th><td></td><td align='right'>" . ui_control($controls['total']) . "</td></tr>
            </table>
          </td>
        </tr>
      </table>
      <div class='height20'></div>
      <div class='align-right'>";

        $c .= "<span style='background:rgb(240, 250, 237);width:700px' class='padding10 payment-section'>
            <table cellspacing='0' class='form'>";

        $c .= "<tr>
                    <th style='text-align:left'></th>
                    <td align='right' style='color:rgba(0, 0, 0, .2);font-weight:600;padding:6px'>JUMLAH</td>
                    <td align='right' style='color:rgba(0, 0, 0, .2);font-weight:600;padding:6px'>NILAI TUKAR</td>
                    <td align='right' style='color:rgba(0, 0, 0, .2);font-weight:600;padding:6px'>TANGGAL</td>
                    <td align='right' style='color:rgba(0, 0, 0, .2);font-weight:600;padding:6px'>AKUN</td>
                  </tr>";

        for ($i = 0; $i < 5; $i++) {

          $off = !isset($payments[$i]) && $i > 0 ? 'off' : '';

          $c .= "<tr class='$off payment-row'>
                  <th style='text-align:left'>" . (!$readonly ? "<span class='fa fa-times-circle payment-remove-btn' style='color:red' onclick='purchaseinvoice_paymentremove(this)'></span>" : '') . "<label>Pembayaran " . ($i + 1) . "</label></th>
                  <td>" . ui_control($controls["paymentamount_$i"]) . "</td>
                  <td>" . ui_control($controls["paymentcurrencyrate_$i"]) . "</td>
                  <td>" . ui_control($controls["paymentdate_$i"]) . "</td>
                  <td>" . ui_control($controls["paymentaccountid_$i"]) . "</td>
                </tr>";

        }

        $c .= "</table>";

        if (!$readonly)
          $c .= "<div class='align-center'><span onclick='purchaseinvoice_paymentadd()'><span class='fa fa-plus-circle payment-add-btn padding10' style='color:green'></span>Tambah Pembayaran</span></div>";

        $c .= "</span>";

        $c .= "<div class='height15'></div>
        <span style='background:rgb(255, 250, 237);width:700px' class='padding10'>
          <table cellspacing='0' class='form'>";

          if($downpaymentamount > 0){
            $c .= "<tr>
              <th><label>Uang Muka</label></th>
              <td></td>
              <td>" . ui_control($controls['downpaymentamount']) . "</td>
              <td></td>
              <td style='background:rgb(240, 250, 237)' align='right'>" . ui_control($controls['downpaymentamount_in_currency']) . "</td>
            </tr>";
          }

          $c .= "<tr>
              <th><label>Total Pembayaran</label></th>
              <td class='align-right'></td>
              <td>" . ui_control($controls['paymentamount']) . "</td>
              <td></td>
              <td style='background:rgb(240, 250, 237)' align='right'>" . ui_control($controls['paymentamount_in_currency']) . "</td>
            </tr>";

          $c .= "<tr>
              <th><label>Lunas</label></th>
              <td class='align-right'></td>
              <td align='right'>" . ui_control($controls['ispaid']) . "&nbsp;</td>
              <td></td>
              <td></td>
            </tr>";

          if(systemvarget('purchaseinvoice_taxaccountid') > 0) {
            $c .= "<tr>
              <th><label>PPn</label></th>
              <td class='align-right'></td>
              <td>" . ui_control($controls['taxamount']) . "</td>
              <td>" . ui_control($controls['taxdate']) . "</td>
              <td>" . ui_control($controls['taxaccountid']) . "</td>
            </tr>";
          }


          if(systemvarget('purchaseinvoice_pphaccountid') > 0) {
            $c .= "<tr>
              <th><label>PPH</label></th>
              <td class='align-right'></td>
              <td>" . ui_control($controls['pph']) . "</td>
              <td>" . ui_control($controls['pphdate']) . "</td>
              <td>" . ui_control($controls['pphaccountid']) . "</td>
            </tr>";
          }

          if(systemvarget('purchaseinvoice_ksoaccountid') > 0) {
            $c .= "<tr>
              <th><label>KSO</label></th>
              <td class='align-right'></td>
              <td>" . ui_control($controls['kso']) . "</td>
              <td>" . ui_control($controls['ksodate']) . "</td>
              <td>" . ui_control($controls['ksoaccountid']) . "</td>
            </tr>";
          }

          if(systemvarget('purchaseinvoice_skiaccountid') > 0) {
            $c .= "<tr>
              <th><label>SKI</label></th>
              <td class='align-right'></td>
              <td>" . ui_control($controls['ski']) . "</td>
              <td>" . ui_control($controls['skidate']) . "</td>
              <td>" . ui_control($controls['skiaccountid']) . "</td>
            </tr>";
          }

          if(systemvarget('purchaseinvoice_clearance_fee_accountid') > 0) {
            $c .= "<tr>
              <th><label>Clearance Fee</label></th>
              <td class='align-right'></td>
              <td>" . ui_control($controls['clearance_fee']) . "</td>
              <td>" . ui_control($controls['clearance_fee_date']) . "</td>
              <td>" . ui_control($controls['clearance_fee_accountid']) . "</td>
            </tr>";
          }

          if(systemvarget('purchaseinvoice_import_cost_accountid') > 0) {
            $c .= "<tr>
              <th><label>Bea Masuk</label></th>
              <td class='align-right'></td>
              <td>" . ui_control($controls['import_cost']) . "</td>
              <td>" . ui_control($controls['import_cost_date']) . "</td>
              <td>" . ui_control($controls['import_cost_accountid']) . "</td>
            </tr>";
          }

          if(systemvarget('purchaseinvoice_handlingfeeaccountid') > 0) {
            $c .= "<tr>
              <th><label>Handling Fee</label></th>
              <td class='align-right'></td>
              <td>" . ui_control($controls['handlingfeepaymentamount']) . "</td>
              <td>" . ui_control($controls['handlingfeedate']) . "</td>
              <td>" . ui_control($controls['handlingfeeaccountid']) . "</td>
              <td></td>
            </tr>";
          }

          $c .= " 
          </table>
        </span>
      </div>
      <div style='height:88px'></div>
    </div>
    <div class='foot'>
      <table cellspacing='5'>
        <tr>
           " . implode('', $actions) . "
        </tr>
      </table>
    </div>
	";
  $c .= "</element>";
  $c .= "
	<script>
	  ui.loadscript('rcfx/js/purchaseinvoice.js', \"ui.modal_open(ui('.modal'), { closeable:$closable, width:1060, autoheight:true });purchaseinvoice_total()\");
	</script>
	";
  return $c;

}
function ui_purchaseinvoicedetail_by_purchaseorderid($purchaseorderid){

  $purchaseinvoiceid = pmc("SELECT `id` FROM purchaseinvoice WHERE purchaseorderid = ?", array($purchaseorderid));
  return ui_purchaseinvoiceopen($purchaseinvoiceid);

}
function ui_purchaseinvoicedetail_supplierhint($param){

  $hint = $param['hint'];
  $suppliers = supplierlist(null, null, array(
      array('name'=>'description', 'operator'=>'contains', 'value'=>$hint)
  ));
  $suppliers = array_cast($suppliers, array('text'=>'description', 'value'=>'description'));
  return $suppliers;

}
function ui_purchaseinvoicedetail_suppliercompletion($supplierdescription){

  $supplier = supplierdetail(null, array('description'=>$supplierdescription));
  $address = isset($supplier['address']) ? $supplier['address'] : '';

  $obj = array(
      'address'=>$address
  );
  return uijs("
    var obj = " . json_encode($obj) . ";
    ui.container_setvalue(ui('.modal'), obj, 1);
  ");

}
function ui_purchaseinvoicedetail_columnresize($name, $width){

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
function ui_purchaseinvoicedetail_col0($obj, $params){

  $readonly = $params['readonly'] || ov('purchaseorder_exists', $obj);
  return ui_autocomplete(array(
      'prehint'=>"return [ $('#taxable').val() ]",
      'name'=>'inventorydescription',
      'src'=>'ui_purchaseinvoicedetail_col0_completion',
      'value'=>ov('inventorydescription', $obj),
      'readonly'=>$readonly,
      'class'=>'flex',
      'onchange'=>"ui.async('ui_purchaseinvoicedetail_col0_completion2', [ value, $('#taxable').val(), ui.uiid(this.parentNode.parentNode)], { waitel:this })",
      'ischild'=>1,
  )) . ui_hidden(array('name'=>'inventoryid', 'value'=>$obj['inventoryid'])) .
    ui_hidden(array('name'=>'purchaseinvoiceinventoryid', 'value'=>$obj['id']));

}
function ui_purchaseinvoicedetail_col0_completion($param0){

  $hint = $param0['hint'];
  $inventories = pmrs("SELECT code, CONCAT(code, ' - ', description) as `text` FROM inventory WHERE code LIKE ? OR description LIKE ? AND isactive = 1", [ "%$hint%", "%$hint%" ]);
  $result = array_cast($inventories, array('text'=>'text', 'value'=>'code'));
  return $result;

}
function ui_purchaseinvoicedetail_col0_completion2($inventorycode, $taxable, $trid){

  $inventory = inventorydetail(null, array('code'=>$inventorycode));
  $obj = array(
    'unit'=>$inventory['unit'],
    'inventoryid'=>$inventory['id'],
    'inventorycode'=>$inventory['code'],
    'inventorydescription'=>$inventory['description'],
    'taxable'=>$inventory['taxable']
  );
  return uijs("
    ui.container_setvalue(ui('$" . $trid . "'), " . json_encode($obj) . ", 1);
    purchaseinvoice_rowtotal(ui('$" . $trid . "'));
  ");

}
function ui_purchaseinvoicedetail_col1($obj, $params){

  $readonly = $params['readonly'] || ov('purchaseorder_exists', $obj);
  return ui_textbox(array(
    'name'=>'qty',
    'value'=>ov('qty', $obj),
    'readonly'=>$readonly,
    'class'=>'block',
    'onchange'=>'purchaseinvoice_rowtotal(this.parentNode.parentNode)',
    'datatype'=>'money',
    'ischild'=>1
  ));

}
function ui_purchaseinvoicedetail_col2($obj, $params){

  return ui_label(array(
    'name'=>'unit',
    'value'=>ov('unit', $obj),
    'class'=>'block',
    'ischild'=>1
  ));

}
function ui_purchaseinvoicedetail_col3($obj, $params){

  $readonly = $params['readonly'] || ov('purchaseorder_exists', $obj);
  return ui_textbox(array(
      'name'=>'unitprice',
      'value'=>ov('unitprice', $obj),
      'readonly'=>$readonly,
      'width'=>'70%',
      'datatype'=>'money',
      'onchange'=>'purchaseinvoice_rowtotal(this.parentNode.parentNode)',
      'ischild'=>1
  )) . "<span class='fa fa-info-circle color-blue' style='margin-left:5px' onclick=\"purchaseinvoicedetail_inventoryhistory(this)\"></span>";

}
function ui_purchaseinvoicedetail_col4($obj, $params){

  $readonly = $params['readonly'] || ov('purchaseorder_exists', $obj);
  return ui_textbox(array(
    'name'=>'unitdiscount',
    'value'=>ov('unitdiscount', $obj),
    'readonly'=>$readonly,
    'class'=>'block',
    'datatype'=>'money',
    'onchange'=>'purchaseinvoice_rowtotal(this.parentNode.parentNode)',
    'ischild'=>1
  ));

}
function ui_purchaseinvoicedetail_col5($obj, $params){

  $readonly = $params['readonly'] || ov('purchaseorder_exists', $obj);
  return "<div class='align-right'>" . ui_label(array(
    'name'=>'unittotal',
    'value'=>ov('unittotal', $obj),
    'readonly'=>$readonly,
    'class'=>'block',
    'datatype'=>'money',
    'ischild'=>1,
  )) . "</div>";

}
function ui_purchaseinvoicedetail_col6($obj, $params){

  $readonly = $params['readonly'] || ov('purchaseorder_exists', $obj);
  if(!$readonly)
    return "<div class='align-center'><span class='fa fa-times-circle color-red' onclick=\"ui.grid_remove(this.parentNode.parentNode.parentNode)\"></span></div>";
  return '';

}
function ui_purchaseinvoicedetail_col7($obj, $params){

  $c = ui_label(array('name'=>'inventorycode', 'class'=>'block', 'value'=>ov('inventorycode', $obj)));
  $c .= ui_hidden(array('name'=>'taxable', 'class'=>'block', 'value'=>ov('taxable', $obj)));
  return $c;

}
function ui_purchaseinvoicedetail_col8($obj, $params){

  return "<div style='position:relative'>" .
    ui_checkbox([
      'name'=>'unitcostpriceflag',
      'value'=>ov('unitcostpriceflag', $obj, 0, 0),
      'style'=>"position:absolute;left:1px;top:6px",
      'onchange'=>"purchaseinvoice_unitcostpriceflag_changed(this)",
      'readonly'=>$params['readonly'],
    ]) .
    ui_textbox([
      'name'=>'unitcostprice',
      'value'=>ov('unitcostprice', $obj),
      'readonly'=>$params['readonly'],
      'class'=>'block',
      'datatype'=>'money',
      'onchange'=>"purchaseinvoice_unitcostprice_changed(this)",
      'ischild'=>1
    ]) .
  "</div>";


  $html = [];
  $html[] = "<table cellspacing='0' cellpadding='0'><tr>";
  /*$html[] =
    "<td style='padding:0'>" . ui_checkbox([
      'name'=>'unitcostpriceflag',
      'value'=>ov('unitcostpriceflag', $obj, 0, 0)
    ]) . "</td>";*/
  $html[] =
    "<td style='padding:0' width='100%'>" . ui_textbox([
      'name'=>'unitcostprice',
      'value'=>ov('unitcostprice', $obj),
      'readonly'=>$params['readonly'],
      'class'=>'block',
      'datatype'=>'money',
      'onchange'=>'',
      'ischild'=>1
    ]) .
    "</td>";
  $html[] = "</tr></table>";
  return implode('', $html);

}
function ui_purchaseinvoicedetail_col9($obj, $params){

  if(systemvarget('purchaseinvoice_import_cost_accountid') > 0){

    $readonly = $params['readonly'] || ov('purchaseorder_import_cost', $obj);

    return ui_textbox(array(
      'name'=>'unittax',
      'value'=>ov('unittax', $obj),
      'readonly'=>$readonly,
      'class'=>'block',
      'datatype'=>'money',
      'onchange'=>'purchaseinvoice_total()',
      'ischild'=>1
    ));
  }
  else
    return "<div class='align-right'>-</div>";

}
function ui_purchaseinvoicedetail_datechanged($date, $taxable, $release = ''){

  $code = purchaseinvoicecode($date, $taxable, $release);
  $script = [];
  $script[] = "$(\"*[data-name='code']\").val(\"$code\")";
  return uijs(implode(';', $script));

}
function ui_purchaseinvoiceclose($code){

  code_release($code);
  return uijs("ui.modal_close(ui('.modal'))");

}

function ui_purchaseinvoicesave($obj){

  $result = isset($obj['id']) && intval($obj['id']) > 0 ? purchaseinvoicemodify($obj) : purchaseinvoiceentry($obj);
  $c = m_load() . uijs("ui.modal_close(ui('.modal'))");
  if(isset($result['warnings']) && is_array($result['warnings'])) $c .= ui_dialog('Faktur Berhasil Dibuat', implode("<br />", $result['warnings']));
  return $c;

}
function ui_purchaseinvoiceremove($id){

  purchaseinvoiceremove(array('id'=>$id));
  return m_load();

}

function ui_purchaseinvoicemove($id){ return ''; }

function ui_purchaseinvoicedetail_inventoryhistory($inventoryid){

  $rows = pmrs("SELECT t1.date, t1.supplierdescription, t2.qty, t2.unitprice FROM purchaseinvoice t1, purchaseinvoiceinventory t2
    WHERE t2.inventoryid = ? AND t2.purchaseinvoiceid = t1.id ORDER BY t1.date ASC", array($inventoryid));

  $c = "<element exp='.dialog'>";
  $c .= "
      <div class='box-dialog'>
        <div>
          <div>
            " . ui_gridhead(array('columns'=>ui_purchaseinvoicedetail_inventoryhistory_columns(), 'gridexp'=>'#ih')) . "
            <div id='ih_scrollable' class='scrollable' style='height:200px'>" . ui_grid(array('id'=>'ih', 'columns'=>ui_purchaseinvoicedetail_inventoryhistory_columns(), 'value'=>$rows, 'scrollel'=>'#ih_scrollable')) . "</div>
          </div>
        </div>
        <div style='height: 15px'></div>
        <table cellspacing='0'>
          <tr>
            <td style='width:100%'></td>
            <td><button class='hollow' onclick=\"ui.dialog_close()\"><span class='fa fa-times'></span><label>Close</label></button></td>
          </tr>
        </table>
      </div>
      ";
  $c .= "</element>";
  $c .= uijs("
        ui.dialog_open({ width:600 });
      ");
  return $c;


}

function ui_purchaseinvoicedetail_inventoryhistory_columns(){

  return array(
    array('active'=>1, 'name'=>'date', 'text'=>'Tanggal', 'width'=>90, 'nodittomark'=>1),
    array('active'=>1, 'name'=>'supplierdescription', 'text'=>'Supplier', 'width'=>200, 'nodittomark'=>1),
    array('active'=>1, 'name'=>'qty', 'text'=>'Kts', 'width'=>60, 'datatype'=>'number', 'nodittomark'=>1),
    array('active'=>1, 'name'=>'unitprice', 'text'=>'Harga Satuan', 'width'=>110, 'datatype'=>'money', 'nodittomark'=>1)
  );

}

function ui_purchaseinvoiceexport(){

  global $module;
  $preset = $module['presets'][$module['presetidx']];
  $columns = $preset['columns'];
  $sorts = $preset['sorts'];
  $quickfilters = ov('quickfilters', $preset);
  $filters = $preset['filters'];
  $filters = m_quickfilter_to_filters($filters, $quickfilters);

  $purchaseinvoice_columnaliases = array(
    'ispaid'=>'t1.ispaid',
    'code'=>'t1.code',
    'date'=>'t1.date',
    'supplierdescription'=>'t1.supplierdescription',
    'address'=>'t1.address',
    'currencycode'=>'t3.code',
    'currencyname'=>'t3.name',
    'currencyrate'=>'t1.currencyrate',
    'subtotal'=>'t1.subtotal',
    'discount'=>'t1.discount',
    'discountamount'=>'t1.discountamount',
    'taxable'=>'t1.taxable',
    'taxamount'=>'t1.taxamount',
    'freightcharge'=>'t1.freightcharge',
    'handlingfeeaccountname'=>'t4.name',
    'handlingfeeamount'=>'t1.handlingfeeamount',
    'total'=>'t1.total',
    'paymentaccountname'=>'t5.name',
    'paymentaccountdate'=>'t1.paymentaccountdate',
    'paymentaccountamount'=>'t1.paymentaccountamount',
    'note'=>'t1.note',
    'inventorycode'=>'t2.inventorycode',
    'inventorydescription'=>'t2.inventorydescription',
    'qty'=>'t2.qty',
    'unit'=>'t2.unit',
    'unitprice'=>'t2.unitprice',
    'unitdiscount'=>'t2.unitdiscount',
    'unitdiscountamount'=>'t2.unitdiscountamount',
    'unittotal'=>'t2.unittotal',
    'unithandlingfee'=>'t2.unithandlingfee',
    'createdon'=>'t1.createdon'
  );

  $params = array();
  $columnquery = columnquery_from_columnaliases($columns, $purchaseinvoice_columnaliases);
  $wherequery = 'WHERE t1.id = t2.purchaseinvoiceid AND t1.currencyid = t3.id AND t1.handlingfeeaccountid = t4.id AND t1.paymentaccountid = t5.id' .
    str_replace('WHERE', 'AND', wherequery_from_filters($params, $filters, $purchaseinvoice_columnaliases));
  $sortquery = sortquery_from_sorts($sorts, $purchaseinvoice_columnaliases);

  if(strlen($columnquery) > 0) $columnquery = ', ' . $columnquery;

  $query = "SELECT 'purchaseinvoice' as `type`, t1.id, t1.supplierid, t1.currencyid, t1.handlingfeeaccountid, t1.paymentaccountid, t2.inventoryid $columnquery
    FROM purchaseinvoice t1, purchaseinvoiceinventory t2, currency t3, chartofaccount t4, chartofaccount t5 $wherequery $sortquery";
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

  $filepath = 'usr/purchase-invoice-' . date('j-M-Y') . '.xlsx';
  array_to_excel($items, $filepath);

  echo uijs("ui('#downloader').href = '$filepath';ui('#downloader').click();");

}

?>