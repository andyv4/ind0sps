<?php

require_once 'api/purchaseorder.php';
require_once 'api/purchaseinvoice.php';

function ui_purchaseorderdetail($id, $mode = 'read', $obj = null){

  $obj = $id ? purchaseorderdetail(null, array('id'=>$id)) : null;

  if($mode != 'read' && $obj && !privilege_get('purchaseorder', 'modify')) $mode = 'read';
  if($mode == 'read' && !$obj) throw new Exception('Order pembelian dengan nomor ini tidak ada.');
  $code = ov('code', $obj);
  $date = ov('date', $obj);
  $inventories = $obj['inventories'];
  $readonly = $mode == 'write' ? 0 : 1;
  $closable = $readonly ? 1 : 0;
  $is_new = !$readonly && !$obj ? true : false;
  if($is_new && !privilege_get('purchaseorder', 'new')) exc("Anda tidak dapat membuat pesanan pembelian.");
  $module = m_loadstate();
  $preset = $module['presets'][$module['presetidx']];
  $removable = purchaseorderremovable(array('id'=>ov('id', $obj)));
  $modifiable = true;

  $is_new = !$obj && $mode == 'write' ? true : false;
  $code = $is_new ? purchaseordercode() : $code;
  $date = $is_new ? date('Ymd') : $date;
  $terms = [
    [ 'text'=>'COD', 'value'=>0 ],
    [ 'text'=>'15 Hari', 'value'=>15 ],
    [ 'text'=>'30 Hari', 'value'=>30 ],
    [ 'text'=>'60 Hari', 'value'=>60 ],
    [ 'text'=>'90 Hari', 'value'=>90 ],
  ];

  $purchaseinvoice = purchaseinvoicedetail(null, array('purchaseorderid'=>ov('id', $obj)));
  $purchaseinvoiceid = ov('id', $purchaseinvoice);

  if($purchaseinvoiceid){
    $modifiable = false;
    $readonly = true;
  }

  $ispaid = $obj['ispaid'];
  $isbaddebt = $obj['isbaddebt'];
  $isinvoiced = $obj['isinvoiced'];
  $pph = ov('pph', $obj, 0, 0);
  $kso = ov('kso', $obj, 0, 0);
  $ski = ov('ski', $obj, 0, 0);
  $clearance_fee = ov('clearance_fee', $obj, 0, 0);
  $taxamount = ov('taxamount', $obj, 0, 0);
  $taxdate = ov('taxdate', $obj, 0, 0);
  $taxaccountid = ov('taxaccountid', $obj, 0, 0);
  $pphdate = ov('pphdate', $obj, 0, 0);
  $pphaccountid = ov('pphaccountid', $obj, 0, 0);
  $ksodate = ov('ksodate', $obj, 0, 0);
  $ksoaccountid = ov('ksoaccountid', $obj, 0, 0);
  $skidate = ov('skidate', $obj, 0, 0);
  $skiaccountid = ov('skiaccountid', $obj, 0, 0);
  $clearance_fee_date = ov('clearance_fee_date', $obj, 0, 0);
  $clearance_fee_accountid = ov('clearance_fee_accountid', $obj, 0, 0);
  $import_cost = ov('import_cost', $obj, 0, 0);
  $import_cost_date = ov('import_cost_date', $obj, 0, 0);
  $import_cost_accountid = ov('import_cost_accountid', $obj, 0, 0);

  $chartofaccounts = chartofaccountlist(null, null, [ [ 'name'=>'code', 'operator'=>'contains', 'value'=>'100.' ] ]);
  $bad_chartofaccounts = chartofaccountlist(null, null, [ [ 'name'=>'code', 'operator'=>'contains', 'value'=>'600.37' ] ]);

  $controls = array(
    'id'=>array('type'=>'hidden', 'name'=>'id', 'value'=>ov('id', $obj)),
    'code'=>array('type'=>'textbox', 'name'=>'code', 'value'=>$code, 'width'=>100, 'readonly'=>$readonly),
    'date'=>array('type'=>'datepicker', 'name'=>'date', 'value'=>$date, 'readonly'=>$readonly),
    'supplierdescription'=>array('type'=>'autocomplete', 'name'=>'supplierdescription', 'value'=>ov('supplierdescription', $obj), 'src'=>'ui_purchaseorderdetail_supplierhint', 'width'=>400, 'readonly'=>$readonly, 'onchange'=>"purchaseorder_supplierchange(value)"),
    'address'=>array('type'=>'textarea', 'name'=>'address', 'value'=>ov('address', $obj, 0), 'width'=>400, 'height'=>60, 'readonly'=>$readonly),
    'currencyid'=>array('type'=>'dropdown', 'name'=>'currencyid', 'value'=>ov('currencyid', $obj, 0, 1), 'width'=>150, 'items'=>array_cast(currencylist(), array('text'=>'name', 'value'=>'id')), 'readonly'=>$readonly),
    'currencyrate'=>array('type'=>'textbox', 'name'=>'currencyrate', 'value'=>ov('currencyrate', $obj, 0, 1), 'width'=>120, 'readonly'=>$readonly, 'datatype'=>'money', 'align'=>'left'),
    'note'=>array('type'=>'textarea', 'name'=>'note', 'value'=>ov('note', $obj, 0), 'width'=>380, 'height'=>60, 'readonly'=>$readonly),
    'subtotal'=>array('type'=>'label', 'name'=>'subtotal', 'value'=>ov('subtotal', $obj, 0), 'width'=>150, 'datatype'=>'money', 'readonly'=>$readonly),
    'discount'=>array('type'=>'textbox', 'name'=>'discount', 'value'=>ov('discount', $obj, 0), 'width'=>60, 'datatype'=>'number', 'readonly'=>$readonly, 'onchange'=>"purchaseorder_discountchange()"),
    'discountamount'=>array('type'=>'textbox', 'name'=>'discountamount', 'value'=>ov('discountamount', $obj, 0), 'width'=>150, 'datatype'=>'money', 'readonly'=>$readonly, 'onchange'=>"purchaseorder_discountamountchange()"),
    'taxable'=>array('type'=>'checkbox', 'name'=>'taxable', 'value'=>ov('taxable', $obj, 0), 'readonly'=>$readonly, 'onchange'=>"purchaseorder_taxchange()"),
    'taxamount'=>array('type'=>'label', 'name'=>'taxamount', 'value'=>ov('taxamount', $obj, 0), 'width'=>100, 'datatype'=>'money', 'readonly'=>$readonly),
    'freightcharge'=>array('type'=>'textbox', 'name'=>'freightcharge', 'value'=>ov('freightcharge', $obj, 0), 'width'=>150, 'datatype'=>'money', 'readonly'=>$readonly, 'onchange'=>"purchaseorder_total()"),
    'total'=>array('type'=>'label', 'name'=>'total', 'value'=>ov('total', $obj, 0), 'width'=>150, 'datatype'=>'money', 'readonly'=>$readonly),
    'ispaid'=>array('type'=>'checkbox', 'name'=>'ispaid', 'value'=>ov('ispaid', $obj, 0), 'readonly'=>1, 'onchange'=>"purchaseorder_ispaid()"),
    'paymentamount'=>array('type'=>'textbox', 'name'=>'paymentamount', 'value'=>ov('paymentamount', $obj, 0), 'readonly'=>1, 'width'=>120, 'datatype'=>'money', 'onchange'=>"purchaseorder_paymentamountchange()"),
    'paymentdate'=>array('type'=>'datepicker', 'name'=>'paymentdate', 'value'=>ov('paymentdate', $obj, 0), 'readonly'=>$readonly, 'onchange'=>"", 'align'=>'right'),
    'paymentaccountid'=>array('type'=>'dropdown', 'name'=>'paymentaccountid', 'value'=>ov('paymentaccountid', $obj, 0, 2), 'items'=>array_cast($chartofaccounts, array('text'=>'name', 'value'=>'id')), 'readonly'=>$readonly, 'width'=>150, 'onchange'=>"", 'align'=>'right'),
    'purchaseinvoicecode'=>array('type'=>'label', 'value'=>ov('code', $purchaseinvoice), 'onclick'=>"ui.async('ui_purchaseinvoiceopen', [ $purchaseinvoiceid, 'read', { callback:'ui_purchaseorderdetail', params:[ $id, 'read' ] } ], { waitel:this })"),
    'handlingfeepaymentamount'=>array('type'=>'textbox', 'name'=>'handlingfeepaymentamount', 'value'=>ov('handlingfeepaymentamount', $obj, 0), 'readonly'=>$readonly, 'width'=>150, 'datatype'=>'money', 'onchange'=>"purchaseorder_onhandlingfeechange()"),
    'refno'=>[ 'type'=>'textbox', 'name'=>'refno', 'value'=>ov('refno', $obj, 0), 'readonly'=>$readonly, 'width'=>100 ],
    'eta'=>[ 'type'=>'datepicker', 'name'=>'eta', 'value'=>ov('eta', $obj, 0), 'readonly'=>$readonly ],
    'term'=>[ 'type'=>'dropdown', 'name'=>'term', 'items'=>$terms, 'value'=>ov('term', $obj, 0), 'readonly'=>$readonly ],
    'taxamount'=>array('type'=>'textbox', 'name'=>'taxamount', 'value'=>$taxamount, 'width'=>120, 'datatype'=>'money', 'readonly'=>$readonly, 'onchange'=>"purchaseorder_total()"),
    'taxdate'=>array('type'=>'datepicker', 'name'=>'taxdate', 'value'=>$taxdate, 'readonly'=>$readonly, 'onchange'=>"", 'align'=>'right', 'readonly'=>$readonly, 'onchange'=>"purchaseorder_total()"),
    'taxaccountid'=>array('type'=>'dropdown', 'name'=>'taxaccountid', 'value'=>$taxaccountid, 'items'=>array_cast($chartofaccounts, array('text'=>'name', 'value'=>'id')), 'readonly'=>$readonly, 'width'=>150, 'onchange'=>"purchaseorder_total()", 'align'=>'right'),
    'pph'=>array('type'=>'textbox', 'name'=>'pph', 'value'=>$pph, 'width'=>120, 'datatype'=>'money', 'readonly'=>$readonly, 'onchange'=>"purchaseorder_total()"),
    'pphdate'=>array('type'=>'datepicker', 'name'=>'pphdate', 'value'=>$pphdate, 'datatype'=>'money', 'readonly'=>$readonly, 'onchange'=>"purchaseorder_total()", 'align'=>'right'),
    'pphaccountid'=>array('type'=>'dropdown', 'name'=>'pphaccountid', 'value'=>$pphaccountid, 'width'=>150, 'items'=>array_cast($chartofaccounts, array('text'=>'name', 'value'=>'id')), 'readonly'=>$readonly, 'onchange'=>"purchaseorder_total()", 'align'=>'right'),
    'kso'=>array('type'=>'textbox', 'name'=>'kso', 'value'=>$kso, 'width'=>120, 'datatype'=>'money', 'readonly'=>$readonly, 'onchange'=>"purchaseorder_total()"),
    'ksodate'=>array('type'=>'datepicker', 'name'=>'ksodate', 'value'=>$ksodate, 'datatype'=>'money', 'readonly'=>$readonly, 'onchange'=>"purchaseorder_total()", 'align'=>'right'),
    'ksoaccountid'=>array('type'=>'dropdown', 'name'=>'ksoaccountid', 'value'=>$ksoaccountid, 'width'=>150, 'items'=>array_cast($chartofaccounts, array('text'=>'name', 'value'=>'id')), 'readonly'=>$readonly, 'onchange'=>"purchaseorder_total()", 'align'=>'right'),
    'ski'=>array('type'=>'textbox', 'name'=>'ski', 'value'=>$ski, 'width'=>120, 'datatype'=>'money', 'readonly'=>$readonly, 'onchange'=>"purchaseorder_total()"),
    'skidate'=>array('type'=>'datepicker', 'name'=>'skidate', 'value'=>$skidate, 'datatype'=>'money', 'readonly'=>$readonly, 'onchange'=>"purchaseorder_total()", 'align'=>'right'),
    'skiaccountid'=>array('type'=>'dropdown', 'name'=>'skiaccountid', 'value'=>$skiaccountid, 'width'=>150, 'items'=>array_cast($chartofaccounts, array('text'=>'name', 'value'=>'id')), 'readonly'=>$readonly, 'onchange'=>"purchaseorder_total()", 'align'=>'right'),
    'clearance_fee'=>array('type'=>'textbox', 'name'=>'clearance_fee', 'value'=>$clearance_fee, 'width'=>120, 'datatype'=>'money', 'readonly'=>$readonly, 'onchange'=>"purchaseorder_total()"),
    'clearance_fee_date'=>array('type'=>'datepicker', 'name'=>'clearance_fee_date', 'value'=>$clearance_fee_date, 'datatype'=>'money', 'readonly'=>$readonly, 'onchange'=>"purchaseorder_total()", 'align'=>'right'),
    'clearance_fee_accountid'=>array('type'=>'dropdown', 'name'=>'clearance_fee_accountid', 'value'=>$clearance_fee_accountid, 'width'=>150, 'items'=>array_cast($chartofaccounts, array('text'=>'name', 'value'=>'id')), 'readonly'=>$readonly, 'onchange'=>"purchaseorder_total()", 'align'=>'right'),
    'import_cost'=>array('type'=>'textbox', 'name'=>'import_cost', 'value'=>$import_cost, 'width'=>120, 'datatype'=>'money', 'readonly'=>1, 'onchange'=>"purchaseorder_total()"),
    'import_cost_date'=>array('type'=>'datepicker', 'name'=>'import_cost_date', 'value'=>$import_cost_date, 'datatype'=>'money', 'readonly'=>$readonly, 'onchange'=>"purchaseorder_total()", 'align'=>'right'),
    'import_cost_accountid'=>array('type'=>'dropdown', 'name'=>'import_cost_accountid', 'value'=>$import_cost_accountid, 'width'=>150, 'items'=>array_cast($chartofaccounts, array('text'=>'name', 'value'=>'id')), 'readonly'=>$readonly, 'onchange'=>"purchaseorder_total()", 'align'=>'right'),
    'handlingfeepaymentamount'=>array('type'=>'textbox', 'name'=>'handlingfeepaymentamount', 'value'=>ov('handlingfeepaymentamount', $obj), 'readonly'=>$readonly, 'width'=>120, 'datatype'=>'money', 'onchange'=>"purchaseorder_total()"),
    'handlingfeedate'=>array('type'=>'datepicker', 'name'=>'handlingfeedate', 'value'=>ov('handlingfeedate', $obj), 'readonly'=>$readonly, 'align'=>'right'),
    'handlingfeeaccountid'=>array('type'=>'dropdown', 'name'=>'handlingfeeaccountid', 'value'=>ov('handlingfeeaccountid', $obj), 'items'=>array_cast($chartofaccounts, array('text'=>'name', 'value'=>'id')), 'readonly'=>$readonly, 'width'=>150, 'onchange'=>"", 'align'=>'right'),
    'isbaddebt'=>array('type'=>'checkbox', 'name'=>'isbaddebt', 'value'=>ov('isbaddebt', $obj, 0, 0), 'readonly'=>$readonly, 'onchange'=>"purchaseorder_isbaddebt()"),
    'baddebtamount'=>array('type'=>'textbox', 'name'=>'baddebtamount', 'value'=>ov('baddebtamount', $obj), 'readonly'=>$readonly, 'width'=>120, 'datatype'=>'money'),
    'baddebtamountdate'=>array('type'=>'datepicker', 'name'=>'baddebtdate', 'value'=>ov('baddebtdate', $obj), 'readonly'=>$readonly, 'align'=>'right'),
    'baddebtaccountid'=>array('type'=>'dropdown', 'name'=>'baddebtaccountid', 'value'=>ov('baddebtaccountid', $obj), 'items'=>array_cast($bad_chartofaccounts, array('text'=>'name', 'value'=>'id')), 'readonly'=>$readonly, 'width'=>150, 'onchange'=>"", 'align'=>'right'),
  );

  $payments = $obj['payments'];
  for($i = 0 ; $i < 5 ; $i++){

    $n_paymentamount = isset($payments[$i]['paymentamount']) ? $payments[$i]['paymentamount'] : 0;
    $n_paymentcurrencyrate = isset($payments[$i]['paymentcurrencyrate']) ? $payments[$i]['paymentcurrencyrate'] : 0;
    $n_paymentdate = isset($payments[$i]['paymentdate']) ? $payments[$i]['paymentdate'] : '';
    $n_paymentaccountid = isset($payments[$i]['paymentaccountid']) ? $payments[$i]['paymentaccountid'] : 0;

    if($n_paymentcurrencyrate < 1) $n_paymentcurrencyrate = 1;

    $controls["paymentamount_$i"] = array('type'=>'textbox', 'class'=>'paymentamount', 'name'=>"paymentamount-$i", 'value'=>$n_paymentamount,
      'readonly'=>$readonly, 'width'=>150, 'datatype'=>'money', 'onchange'=>"purchaseorder_paymentamountchange()");
    $controls["paymentcurrencyrate_$i"] = array('type'=>'textbox', 'class'=>'paymentcurrencyrate', 'name'=>"paymentcurrencyrate-$i",
      'value'=>$n_paymentcurrencyrate, 'readonly'=>$readonly, 'width'=>150, 'datatype'=>'money', 'onchange'=>"purchaseorder_paymentamountchange()");
    $controls["paymentdate_$i"] = array('type'=>'datepicker', 'class'=>'paymentdate', 'name'=>"paymentdate-$i",
      'value'=>$n_paymentdate, 'readonly'=>$readonly, 'onchange'=>"purchaseorder_paymentamountchange()", 'align'=>'right');
    $controls["paymentaccountid_$i"] = array('type'=>'dropdown', 'class'=>'paymentaccountid', 'name'=>"paymentaccountid-$i",
      'value'=>$n_paymentaccountid, 'items'=>array_cast($chartofaccounts, array('text'=>'name', 'value'=>'id')),
      'readonly'=>$readonly, 'onchange'=>"purchaseorder_paymentamountchange()", 'width'=>150, 'align'=>'right');

  }

  $detailcolumns = array(
    array('active'=>1, 'name'=>'col7', 'text'=>'Kode', 'type'=>'html', 'html'=>'ui_purchaseorderdetail_col7', 'width'=>80),
    array('active'=>1, 'name'=>'col0', 'text'=>'Barang', 'type'=>'html', 'html'=>'ui_purchaseorderdetail_col0', 'width'=>300, 'nodittomark'=>1),
    array('active'=>1, 'name'=>'col1', 'text'=>'Kts', 'type'=>'html', 'html'=>'ui_purchaseorderdetail_col1', 'width'=>50, 'nodittomark'=>1),
    array('active'=>1, 'name'=>'col2', 'text'=>'Satuan', 'type'=>'html', 'html'=>'ui_purchaseorderdetail_col2', 'width'=>60, 'nodittomark'=>1),
    array('active'=>1, 'name'=>'col2', 'text'=>'Harga', 'type'=>'html', 'html'=>'ui_purchaseorderdetail_col3', 'width'=>100, 'nodittomark'=>1),
    array('active'=>1, 'name'=>'col2', 'text'=>'Total', 'type'=>'html', 'html'=>'ui_purchaseorderdetail_col5', 'width'=>100, 'nodittomark'=>1),
    array('active'=>1, 'name'=>'col3', 'text'=>'', 'type'=>'html', 'html'=>'ui_purchaseorderdetail_col6', 'width'=>24, 'nodittomark'=>1),
    array('active'=>1, 'name'=>'col8', 'text'=>"Bea Masuk", 'type'=>'html', 'html'=>'ui_purchaseorderdetail_col9', 'width'=>90, 'align'=>'right', 'class'=>'bg-light-yellow'),
  );

  // Inventories completion
  if(is_array($inventories)){
    for($i = 0 ; $i < count($inventories) ; $i++){

      $inventoryid = ov('inventoryid', $inventories[$i]);
      $inventory_data = $inventoryid ? inventorydetail([ 'unit' ], [ 'id'=>$inventoryid ]) : null;

      $inventories[$i]['inventorydescription'] = !isset($inventories[$i]['inventorydescription']) || !$inventories[$i]['inventorydescription'] ? ov('description', $inventory_data) : $inventories[$i]['inventorydescription'];
      $inventories[$i]['unit'] = !isset($inventories[$i]['unit']) || !$inventories[$i]['unit'] ? ov('unit', $inventory_data) : $inventories[$i]['unit'];

    }
  }


  // Action Controls
  $actions = array();
  if(!$readonly && $obj && privilege_get('purchaseorder', 'delete')) $actions[] = "<td><button class='red' onclick=\"if(confirm('Hapus $code?')) ui.async('ui_purchaseorderremove', [ $id ], { waitel:this, callback:'purchaseorder_onremovecompleted()' })\"><span class='fa fa-times'></span><label>Hapus</label></button></td>";
  if(privilege_get('purchaseorder', 'print')) $actions[] = "<td><button class='green' onclick=\"ui.async('ui_purchaseorderprint', [ ui.container_value(ui('.modal')) ], { waitel:this })\"><span class='mdi mdi-printer'></span><label>Cetak</label></button></td>";
  if($readonly && $obj && privilege_get('purchaseorder', 'modify') && $modifiable) $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_purchaseorderdetail', [ $id, 'write' ], { waitel:this })\"><span class='fa fa-save'></span><label>" . lang('001') . "</label></button></td>";
  if(!$readonly && !$obj && privilege_get('purchaseorder', 'new')) $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_purchaseordersave', [ ui.container_value(ui('.modal')) ], { waitel:this })\"><span class='fa fa-edit'></span><label>" . lang('002') . "</label></button></td>";
  if(!$readonly && $obj && privilege_get('purchaseorder', 'modify') && $modifiable) $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_purchaseordersave', [ ui.container_value(ui('.modal')) ], { waitel:this })\"><span class='fa fa-edit'></span><label>" . lang('002') . "</label></button></td>";
  $actions[] = "<td><button class='hollow' onclick=\"ui.modal_close(ui('.modal'))\"><span class='fa fa-times'></span><label>Close</label></button></td>";

  $c = "<element exp='.modal'>";
  $c .= "
	  <div class='head'>
      <div class='status'>
	      " . ($obj ? "<label>Pesanan Pembelian &sdot; " . $obj['code'] . ($obj['ispaid'] ? "&sdot;</label><span class='color-green'>Lunas</span>" : "</label>") : "<label>Buat Pesanan Pembelian</label>") . "
	      " . ($readonly && $purchaseinvoice ? "<label>Invoice: </label>" . ui_control($controls['purchaseinvoicecode']) : '') . "
	    </div>
	  </div>
    <div class='scrollable padding1020'>
      " . ui_control($controls['id']) . "
      <table class='form'>
        <tr><th><label>Nomor Order</label></th><td>" . ui_control($controls['code']) . "</td></tr>
        " . ui_formrow('Tanggal', ui_control($controls['date'])) . "
        " . ui_formrow('Supplier', ui_control($controls['supplierdescription'])) . "
        " . ui_formrow('Alamat', ui_control($controls['address'])) . "
      </table>
      <table class='form'>
        " . ui_formrow('Mata Uang', ui_control($controls['currencyid'])) . "
        " . ui_formrow('Ref No', ui_control($controls['refno'])) . "
        " . ui_formrow('ETA', ui_control($controls['eta'])) . "
        " . ui_formrow('Term', ui_control($controls['term'])) . "
      </table>
      <div style='height:22px'></div>
      <div>
        " . ui_gridhead(array('columns'=>$detailcolumns, 'gridexp'=>'#inventories')) . "
        " . ui_grid(array('columns'=>$detailcolumns, 'name'=>'inventories', 'value'=>$inventories, 'mode'=>'write', 'readonly'=>$readonly, 'id'=>'inventories')) . "
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
            <table class='form' style='float:right'>
              <tr><th><label>Subtotal</label></th><td></td><td align='right'>" . ui_control($controls['subtotal']) . "</td></tr>
              <tr><th><label>Diskon</label></th><td>" . ui_control($controls['discount']) . "</td><td align='right'>" . ui_control($controls['discountamount']) . "</td></tr>
              <tr><th><label>Freight</label></th><td></td><td align='right'>" . ui_control($controls['freightcharge']) . "</td></tr>
              <tr><th><label>Total</label></th><td></td><td align='right'>" . ui_control($controls['total']) . "</td></tr>
              <tr><td><div style='height:10px'></div></td></tr>
            </table>
          </td>
        </tr>
      </table>      
      <div style='height:20px'></div>
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

      $c .= "<tr class='$off'>
              <th style='text-align:left'>" . (!$readonly ? "<span class='fa fa-times-circle payment-remove-btn' style='color:red' onclick='purchaseorder_paymentremove(this)'></span>" : '') . "<label>Pembayaran " . ($i + 1) . "</label></th>
              <td>" . ui_control($controls["paymentamount_$i"]) . "</td>
              <td>" . ui_control($controls["paymentcurrencyrate_$i"]) . "</td>
              <td>" . ui_control($controls["paymentdate_$i"]) . "</td>
              <td>" . ui_control($controls["paymentaccountid_$i"]) . "</td>
            </tr>";

    }

    $c .= "</table>";

    if (!$readonly)
      $c .= "<div class='align-center'><span onclick='purchaseorder_paymentadd()'><span class='fa fa-plus-circle payment-add-btn padding10' style='color:green'></span>Tambah Pembayaran</span></div>";


    $c .= "</span>";
        
        $c .= "<div class='height15'></div>
        
        <span style='background:rgb(255, 250, 237);width:600px' class='padding10'>
          <table cellspacing='0' class='form'>";

          $c .= "<tr>
              <th><label>Total Pembayaran</label></th>
              <td class='align-right'>" . ui_control($controls['ispaid']) . "</td>
              <td>Rp. " . ui_control($controls['paymentamount']) . "</td>
              <td></td>
              <td></td>
            </tr>";

          if(systemvarget('purchaseinvoice_taxaccountid') > 0){
            $c .= "<tr>
              <th><label>PPn</label></th>
              <td class='align-right'></td>
              <td>Rp. " . ui_control($controls['taxamount']) . "</td>
              <td>" . ui_control($controls['taxdate']) . "</td>
              <td>" . ui_control($controls['taxaccountid']) . "</td>
            </tr>";
          }

          if(systemvarget('purchaseinvoice_pphaccountid') > 0){
            $c .= "<tr>
              <th><label>PPH</label></th>
              <td class='align-right'></td>
              <td>Rp. " . ui_control($controls['pph']) . "</td>
              <td>" . ui_control($controls['pphdate']) . "</td>
              <td>" . ui_control($controls['pphaccountid']) . "</td>
            </tr>";
          }

          if(systemvarget('purchaseinvoice_ksoaccountid') > 0){
            $c .= "<tr>
              <th><label>KSO</label></th>
              <td class='align-right'></td>
              <td>Rp. " . ui_control($controls['kso']) . "</td>
              <td>" . ui_control($controls['ksodate']) . "</td>
              <td>" . ui_control($controls['ksoaccountid']) . "</td>
            </tr>";
          }

          if(systemvarget('purchaseinvoice_skiaccountid') > 0){
            $c .= "<tr>
              <th><label>SKI</label></th>
              <td class='align-right'></td>
              <td>Rp. " . ui_control($controls['ski']) . "</td>
              <td>" . ui_control($controls['skidate']) . "</td>
              <td>" . ui_control($controls['skiaccountid']) . "</td>
            </tr>";
          }

          if(systemvarget('purchaseinvoice_clearance_fee_accountid') > 0){
            $c .= "<tr>
              <th><label>Clearance Fee</label></th>
              <td class='align-right'></td>
              <td>Rp. " . ui_control($controls['clearance_fee']) . "</td>
              <td>" . ui_control($controls['clearance_fee_date']) . "</td>
              <td>" . ui_control($controls['clearance_fee_accountid']) . "</td>
            </tr>";
          }

          if(systemvarget('purchaseinvoice_import_cost_accountid') > 0){
            $c .= "<tr>
              <th><label>Bea Masuk</label></th>
              <td class='align-right'></td>
              <td>Rp. " . ui_control($controls['import_cost']) . "</td>
              <td>" . ui_control($controls['import_cost_date']) . "</td>
              <td>" . ui_control($controls['import_cost_accountid']) . "</td>
            </tr>";
          }

          if(systemvarget('purchaseinvoice_handlingfeeaccountid') > 0){
            $c .= "<tr>
              <th><label>Handling Fee</label></th>
              <td class='align-right'></td>
              <td>Rp. " . ui_control($controls['handlingfeepaymentamount']) . "</td>
              <td>" . ui_control($controls['handlingfeedate']) . "</td>
              <td>" . ui_control($controls['handlingfeeaccountid']) . "</td>
              <td></td>
            </tr>";
          }

          $c .= " 
          </table>
        </span>
        <div class='height15'></div>";
        if(!$isinvoiced && ($ispaid || $isbaddebt)){
          $c .= "<span style='background:rgb(255, 230, 230);width:600px' class='padding10 row-baddebt'>
              <table cellspacing='0' class='form'>
                <tr>
                  <th><label>Bad Debt</label></th>
                  <td class='align-right'>" . ui_control($controls['isbaddebt']) . "</td>
                  <td>" . ui_control($controls['baddebtamount']) . "</td>
                  <td>" . ui_control($controls['baddebtamountdate']) . "</td>
                  <td>" . ui_control($controls['baddebtaccountid']) . "</td>
                </tr>
              </table>
            </span>";
        }
          $c .= "
      </div>
      <div style='height:88px'></div>
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
		ui.loadscript('rcfx/js/purchaseorder.js', \"ui.modal_open(ui('.modal'), { closeable:$closable, width:1060, autoheight:true });\");
	</script>
	";
  return $c;

}

function ui_purchaseorderdetail_supplierhint($param){

  $hint = $param['hint'];
  $suppliers = supplierlist(null, null, array(
      array('name'=>'description', 'operator'=>'contains', 'value'=>$hint)
  ));
  $suppliers = array_cast($suppliers, array('text'=>'description', 'value'=>'description'));
  return $suppliers;

}

function ui_purchaseorderdetail_suppliercompletion($supplierdescription){

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

function ui_purchaseorderdetail_columnresize($name, $width){

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

function ui_purchaseorderdetail_col0($obj, $params){

  return ui_autocomplete(array(
      'name'=>'inventorydescription',
      'src'=>'ui_purchaseorderdetail_col0_completion',
      'value'=>ov('inventorydescription', $obj),
      'readonly'=>$params['readonly'],
      'width'=>'99%',
      'onchange'=>"ui.async('ui_purchaseorderdetail_col0_completion2', [ value, ui.uiid(this.parentNode.parentNode)], { waitel:this })",
  ));

}

function ui_purchaseorderdetail_col0_completion($param){

  $hint = ov('hint', $param);
  $items = pmrs("SELECT CONCAT(code, ' - ', description) as text, code as `value` FROM inventory WHERE code LIKE ? OR description LIKE ?", array("%$hint%", "%$hint%"));
  $items = array_cast($items, array('text'=>'text', 'value'=>'value'));
  return $items;

}

function ui_purchaseorderdetail_col0_completion2($inventorycode, $trid){

  $inventory = inventorydetail(null, array('code'=>$inventorycode));
  $obj = array(
    'unit'=>$inventory['unit'],
    'inventorycode'=>$inventory['code'],
    'inventorydescription'=>$inventory['description'],
  );
  return uijs("
    ui.container_setvalue(ui('$" . $trid . "'), " . json_encode($obj) . ", 1);
    purchaseorder_rowtotal(ui('$" . $trid . "'));
  ");

}

function ui_purchaseorderdetail_col1($obj, $params){

  return ui_textbox(array(
    'name'=>'qty',
    'value'=>ov('qty', $obj),
    'readonly'=>$params['readonly'],
    'class'=>'block',
    'datatype'=>'number',
    'onchange'=>'purchaseorder_rowtotal(this.parentNode.parentNode)'
  ));

}

function ui_purchaseorderdetail_col2($obj, $params){

  return ui_label(array(
      'name'=>'unit',
      'class'=>'block',
      'value'=>ov('unit', $obj),
      'readonly'=>$params['readonly'],
  ));

}

function ui_purchaseorderdetail_col3($obj, $params){

  return ui_textbox(array(
      'name'=>'unitprice',
      'value'=>ov('unitprice', $obj),
      'readonly'=>$params['readonly'],
      'class'=>'block',
      'datatype'=>'money',
      'onchange'=>'purchaseorder_rowtotal(this.parentNode.parentNode)'
  ));

}

function ui_purchaseorderdetail_col5($obj, $params){

  return ui_label(array(
      'name'=>'unittotal',
      'value'=>ov('unittotal', $obj),
      'readonly'=>$params['readonly'],
      'class'=>'block',
      'datatype'=>'money'
  ));

}

function ui_purchaseorderdetail_col6($obj, $params){

  if(!$params['readonly'])
    return "<div class='align-center'><span class='fa fa-times-circle color-red' onclick=\"ui.grid_remove(this.parentNode.parentNode.parentNode)\"></span></div>";
  return '';

}

function ui_purchaseorderdetail_col7($obj, $params){

  $c = ui_label(array('name'=>'inventorycode', 'class'=>'block', 'value'=>ov('inventorycode', $obj)));
  $c .= ui_hidden(array('name'=>'taxable', 'class'=>'block', 'value'=>ov('taxable', $obj)));
  return $c;

}

function ui_purchaseorderdetail_col9($obj, $params){

  if(systemvarget('purchaseinvoice_import_cost_accountid') > 0){
    return ui_textbox(array(
      'name'=>'unittax',
      'value'=>ov('unittax', $obj),
      'readonly'=>$params['readonly'],
      'class'=>'block',
      'datatype'=>'money',
      'onchange'=>'purchaseorder_total()',
      'ischild'=>1
    ));
  }
  else
    return "<div class='align-right'>-</div>";

}

function ui_purchaseordersave($obj){

  isset($obj['id']) && intval($obj['id']) > 0 ? purchaseordermodify($obj) : purchaseorderentry($obj);
  return m_load() . uijs("ui.modal_close(ui('.modal'))");

}

function ui_purchaseorderremove($id){

  purchaseorderremove(array('id'=>$id));
  return m_load();

}

function ui_purchaseorderexport(){

  global $module;
  $preset = $module['presets'][$module['presetidx']];
  $columns = $preset['columns'];
  $sorts = $preset['sorts'];
  $quickfilters = ov('quickfilters', $preset);
  $filters = $preset['filters'];
  $filters = m_quickfilter_to_filters($filters, $quickfilters);

  $purchaseorder_columnaliases = array(
    'ispaid'=>'t1.ispaid',
    'isinvoiced'=>'t1.isinvoiced',
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
  $columnquery = columnquery_from_columnaliases($columns, $purchaseorder_columnaliases);
  $wherequery = 'WHERE t1.id = t2.purchaseorderid AND t1.currencyid = t3.id AND t1.handlingfeeaccountid = t4.id AND t1.paymentaccountid = t5.id' .
    str_replace('WHERE', 'AND', wherequery_from_filters($params, $filters, $purchaseorder_columnaliases));
  $sortquery = sortquery_from_sorts($sorts, $purchaseorder_columnaliases);

  if(strlen($columnquery) > 0) $columnquery = ', ' . $columnquery;

  $query = "SELECT 'purchaseorder' as `type`, t1.supplierid, t1.id, t1.currencyid, t1.handlingfeeaccountid, t1.paymentaccountid, t2.inventoryid $columnquery
    FROM purchaseorder t1, purchaseorderinventory t2, currency t3, chartofaccount t4, chartofaccount t5 $wherequery $sortquery";
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

  $filepath = 'usr/purchase-order-' . date('j-M-Y') . '.xlsx';
  array_to_excel($items, $filepath);

  echo uijs("ui('#downloader').href = '$filepath';ui('#downloader').click();");

}

function ui_purchaseorderprint($purchaseorder){

  $purchaseorder = isset($purchaseorder['id']) && intval($purchaseorder['id']) > 0 ? purchaseordermodify($purchaseorder) : purchaseorderentry($purchaseorder);
  $id = $purchaseorder['id'];
  $c = '';
  $c .= "<element exp='.printarea'>";
  ob_start();
  include 'template/purchaseorder.php';
  $c .= ob_get_clean();
  $c .= "</element>";
  $c .= "<script type='text/javascript'>
    window.print();
    ui.control_setvalue(ui('%id', ui('.modal')), " . $id . ");
  </script>";
  return $c;

}

/**
 * Get purchaseinvoice journal vouchers
 * @param $purchaseinvoiceid
 * @return string
 * @throws Exception
 */
function ui_purchaseorderdetail_journal($id){

  $jv = journalvoucherlist('*', null, [
    [ 'type'=>'(' ],
    [ 'type'=>'(' ],
    [ 'name'=>'ref', 'operator'=>'=', 'value'=>'PO' ],
    [ 'name'=>'refid', 'operator'=>'=', 'value'=>$id ],
    [ 'type'=>')' ],
    [ 'type'=>')' ],
  ]);

  $piid = pmc("select `id` from purchaseinvoice where purchaseorderid = ?", [ $id ]);
  if($piid > 0){
    $pi_jv = journalvoucherlist('*', null, [
      [ 'type'=>'(' ],
      [ 'type'=>'(' ],
      [ 'name'=>'ref', 'operator'=>'=', 'value'=>'PI' ],
      [ 'name'=>'refid', 'operator'=>'=', 'value'=>$piid ],
      [ 'type'=>')' ],
      [ 'type'=>')' ],
    ]);
    $jv = array_merge($jv, $pi_jv);
  }

  if(!$jv) exc("ERROR: Tidak ada jurnal untuk pesanan ini.");

  $columns = [
    [ 'active'=>1, 'name'=>'ref', 'text'=>'Tipe', 'width'=>30, 'nodittomark'=>1 ],
    [ 'active'=>1, 'name'=>'date', 'text'=>'Tanggal', 'width'=>90, 'nodittomark'=>1, 'datatype'=>'date' ],
    [ 'active'=>1, 'name'=>'coaname', 'text'=>'Nama Akun', 'width'=>190, 'nodittomark'=>1 ],
    [ 'active'=>1, 'name'=>'debit', 'text'=>'Debit', 'width'=>90, 'nodittomark'=>1, 'datatype'=>'money' ],
    [ 'active'=>1, 'name'=>'credit', 'text'=>'Kredit', 'width'=>90, 'nodittomark'=>1, 'datatype'=>'money' ],
    [ 'active'=>1, 'name'=>'description', 'text'=>'Deskripsi', 'width'=>200, 'nodittomark'=>1 ],
  ];

  $c = "<element exp='.dialog'>";
  $c .= "
      <div class='box-dialog'>
        <div>
          <div>
            " . ui_gridhead(array('columns'=>$columns, 'gridexp'=>'#ih')) . "
            <div id='ih_scrollable' class='scrollable' style='height:200px'>" . ui_grid(array('id'=>'ih', 'columns'=>$columns, 'value'=>$jv, 'scrollel'=>'#ih_scrollable')) . "</div>
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
        ui.dialog_open({ width:900 });
      ");
  return $c;

}

?>
<script>

  $(function(){

    $('.payment-add-btn').click(function(){



    })

  })

</script>
