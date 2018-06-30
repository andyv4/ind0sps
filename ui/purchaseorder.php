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
    'ispaid'=>array('type'=>'checkbox', 'name'=>'ispaid', 'value'=>ov('ispaid', $obj, 0), 'readonly'=>$readonly, 'onchange'=>"purchaseorder_ispaid()"),
    'paymentamount'=>array('type'=>'textbox', 'name'=>'paymentamount', 'value'=>ov('paymentamount', $obj, 0), 'readonly'=>$readonly, 'width'=>150, 'datatype'=>'money', 'onchange'=>"purchaseorder_paymentamountchange()"),
    'paymentdate'=>array('type'=>'datepicker', 'name'=>'paymentdate', 'value'=>ov('paymentdate', $obj, 0), 'readonly'=>$readonly, 'onchange'=>"", 'align'=>'right'),
    'paymentaccountid'=>array('type'=>'dropdown', 'name'=>'paymentaccountid', 'value'=>ov('paymentaccountid', $obj, 0, 2), 'items'=>array_cast(chartofaccountlist(), array('text'=>'name', 'value'=>'id')), 'readonly'=>$readonly, 'width'=>150, 'onchange'=>"", 'align'=>'right'),
    'purchaseinvoicecode'=>array('type'=>'label', 'value'=>ov('code', $purchaseinvoice), 'onclick'=>"ui.async('ui_purchaseinvoiceopen', [ $purchaseinvoiceid, 'read', { callback:'ui_purchaseorderdetail', params:[ $id, 'read' ] } ], { waitel:this })"),
    'handlingfeepaymentamount'=>array('type'=>'textbox', 'name'=>'handlingfeepaymentamount', 'value'=>ov('handlingfeepaymentamount', $obj, 0), 'readonly'=>$readonly, 'width'=>150, 'datatype'=>'money', 'onchange'=>"purchaseorder_onhandlingfeechange()"),
    'refno'=>[ 'type'=>'textbox', 'name'=>'refno', 'value'=>ov('refno', $obj, 0), 'readonly'=>$readonly, 'width'=>100 ],
    'eta'=>[ 'type'=>'datepicker', 'name'=>'eta', 'value'=>ov('eta', $obj, 0), 'readonly'=>$readonly ],
    'term'=>[ 'type'=>'dropdown', 'name'=>'term', 'items'=>$terms, 'value'=>ov('term', $obj, 0), 'readonly'=>$readonly ],
  );

  $detailcolumns = array(
    array('active'=>1, 'name'=>'col7', 'text'=>'Kode', 'type'=>'html', 'html'=>'ui_purchaseorderdetail_col7', 'width'=>80),
    array('active'=>1, 'name'=>'col0', 'text'=>'Barang', 'type'=>'html', 'html'=>'ui_purchaseorderdetail_col0', 'width'=>300, 'nodittomark'=>1),
    array('active'=>1, 'name'=>'col1', 'text'=>'Kts', 'type'=>'html', 'html'=>'ui_purchaseorderdetail_col1', 'width'=>50, 'nodittomark'=>1),
    array('active'=>1, 'name'=>'col2', 'text'=>'Satuan', 'type'=>'html', 'html'=>'ui_purchaseorderdetail_col2', 'width'=>60, 'nodittomark'=>1),
    array('active'=>1, 'name'=>'col2', 'text'=>'Harga', 'type'=>'html', 'html'=>'ui_purchaseorderdetail_col3', 'width'=>100, 'nodittomark'=>1),
    array('active'=>1, 'name'=>'col2', 'text'=>'Diskon', 'type'=>'html', 'html'=>'ui_purchaseorderdetail_col4', 'width'=>60, 'nodittomark'=>1),
    array('active'=>1, 'name'=>'col2', 'text'=>'Total', 'type'=>'html', 'html'=>'ui_purchaseorderdetail_col5', 'width'=>100, 'nodittomark'=>1),
    array('active'=>1, 'name'=>'col3', 'text'=>'', 'type'=>'html', 'html'=>'ui_purchaseorderdetail_col6', 'width'=>24, 'nodittomark'=>1),
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
  if(privilege_get('purchaseorder', 'print')) $actions[] = "<td><button class='green' onclick=\"ui.async('ui_purchaseorderprint', [ ui.container_value(ui('.modal')) ], { waitel:this })\"><span class='mdi mdi-printer'></span><label>Cetak</label></button></td>";
  if($readonly && $obj && privilege_get('purchaseorder', 'modify') && $modifiable) $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_purchaseorderdetail', [ $id, 'write' ], { waitel:this })\"><span class='fa fa-save'></span><label>" . lang('001') . "</label></button></td>";
  if(!$readonly && !$obj && privilege_get('purchaseorder', 'new')) $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_purchaseordersave', [ ui.container_value(ui('.modal')) ], { waitel:this })\"><span class='fa fa-edit'></span><label>" . lang('002') . "</label></button></td>";
  if(!$readonly && $obj && privilege_get('purchaseorder', 'modify') && $modifiable) $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_purchaseordersave', [ ui.container_value(ui('.modal')) ], { waitel:this })\"><span class='fa fa-edit'></span><label>" . lang('002') . "</label></button></td>";
  if(!$readonly && $obj && privilege_get('purchaseorder', 'delete')) $actions[] = "<td><button class='red' onclick=\"if(confirm('Hapus $code?')) ui.async('ui_purchaseorderremove', [ $id ], { waitel:this, callback:'purchaseorder_onremovecompleted()' })\"><span class='fa fa-times'></span><label>Hapus</label></button></td>";
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
        " . ui_formrow('Nilai Tukar', ui_control($controls['currencyrate'])) . "
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
      <table class='form'>
        " . ui_formrow('Catatan', ui_control($controls['note'])) . "
      </table>
      <table class='form' style='float:right'>
        <tr><th><label>Subtotal</label></th><td></td><td align='right'>" . ui_control($controls['subtotal']) . "</td></tr>
        <tr><th><label>Diskon</label></th><td>" . ui_control($controls['discount']) . "</td><td align='right'>" . ui_control($controls['discountamount']) . "</td></tr>
        <tr><th><label>PPn</label></th><td>" . ui_control($controls['taxable']) . "</td><td align='right'>" . ui_control($controls['taxamount']) . "</td></tr>
        <tr><th><label>Freight</label></th><td></td><td align='right'>" . ui_control($controls['freightcharge']) . "</td></tr>
        <tr><th><label>Total</label></th><td></td><td align='right'>" . ui_control($controls['total']) . "</td></tr>
        <tr><td><div style='height:10px'></div></td></tr>
        <tr><th><label>Pelunasan</label></th><td>" . ui_control($controls['ispaid']) . "</td><td align='right'>" . ui_control($controls['paymentamount']) . "</td></tr>
        <tr><th><label>Tanggal Pelunasan</label></th><td></td><td align='right'>" . ui_control($controls['paymentdate']) . "</td></tr>
        <tr><th><label>Akun Pelunasan</label></th><td></td><td align='right'>" . ui_control($controls['paymentaccountid']) . "</td></tr>
      </table>
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

function ui_purchaseorderdetail_col4($obj, $params){

  return ui_textbox(array(
      'name'=>'unitdiscount',
      'value'=>ov('unitdiscount', $obj),
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

?>