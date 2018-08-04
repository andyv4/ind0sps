<?php

require_once 'api/customer.php';
require_once 'api/salesinvoicegroup.php';
require_once 'ui/salesreceipt.php';

$salesinvoicegroup_detailcolumns = [
  [ 'active'=>1, 'name'=>'col0', 'text'=>'Tanggal', 'type'=>'html', 'html'=>'ui_salesinvoicegroupdetail_col0', 'width'=>100, 'nodittomark'=>1 ],
  [ 'active'=>1, 'name'=>'col4', 'text'=>'Pajak', 'type'=>'html', 'html'=>'ui_salesinvoicegroupdetail_col7', 'width'=>60, 'nodittomark'=>1 ],
  [ 'active'=>1, 'name'=>'code', 'text'=>'Nomor Faktur', 'width'=>100, 'nodittomark'=>1 ],
  [ 'active'=>1, 'name'=>'customerdescription', 'text'=>'Pelanggan', 'width'=>200, 'nodittomark'=>1 ],
  [ 'active'=>1, 'name'=>'total', 'text'=>'Total', 'width'=>100, 'nodittomark'=>1, 'datatype'=>'money', 'type'=>'html', 'html'=>'ui_salesinvoicegroupdetail_col3' ],
  [ 'active'=>1, 'name'=>'col1', 'text'=>'Lunas', 'type'=>'html', 'html'=>'ui_salesinvoicegroupdetail_col4', 'width'=>36, 'nodittomark'=>1 ],
  [ 'active'=>1, 'name'=>'col2', 'text'=>'Pembayaran', 'type'=>'html', 'html'=>'ui_salesinvoicegroupdetail_col5', 'width'=>100, 'nodittomark'=>1 ],
  [ 'active'=>1, 'name'=>'col3', 'text'=>'', 'type'=>'html', 'html'=>'ui_salesinvoicegroupdetail_col6', 'width'=>24, 'nodittomark'=>1 ],
];

function ui_salesinvoicegroupdetail($id, $mode = 'read'){

  global $salesinvoicegroup_detailcolumns;
  $obj = is_array($id) ? $id : salesinvoicegroupdetail(null, array('id'=>$id));
  if($mode != 'read' && $obj && !privilege_get('salesinvoicegroup', 'modify')) $mode = 'read';
  if($mode == 'read' && !$obj) throw new Exception('Grup faktur dengan nomor ini tidak ada.');
  $readonly = $mode == 'write' ? 0 : 1;
  $closable = $readonly ? 1 : 0;
  $is_new = !$readonly && !$obj ? true : false;
  if($is_new && !privilege_get('salesinvoicegroup', 'new')) exc("Anda tidak dapat membuat grup faktur.");
  $code = ov('code', $obj);
  $date = ov('date', $obj);

  $is_new = !$obj && $mode == 'write' ? true : false;
  $code = $is_new ? salesinvoicegroupcode() : $code;
  $date = $is_new ? date('Ymd') : $date;

  if(!$readonly && $obj && $obj['isreceipt']) throw new Exception('Tidak dapat mengubah grup faktur, sudah ada kwitansi.');

  $controls = array(
    'id'=>array('type'=>'hidden', 'name'=>'id', 'value'=>ov('id', $obj)),
    'code'=>array('type'=>'textbox', 'name'=>'code', 'value'=>$code, 'width'=>100, 'readonly'=>$readonly),
    'date'=>array('type'=>'datepicker', 'name'=>'date', 'value'=>$date, 'readonly'=>$readonly),
    'customerdescription'=>array('type'=>'autocomplete', 'name'=>'customerdescription', 'width'=>440, 'src'=>'ui_salesinvoicegroupdetail_customerlookup',
    'readonly'=>$readonly, 'text'=>ov('customerdescription', $obj), 'value'=>ov('customerdescription', $obj),
    'onchange'=>"ui.async('ui_salesinvoicegroupdetail_customerchange', [ value ], { waitel:this })"),
    'address'=>array('type'=>'textarea', 'name'=>'address', 'value'=>ov('address', $obj), 'width'=>600, 'height'=>80, 'readonly'=>$readonly),
    'note'=>array('type'=>'textarea', 'width'=>400, 'height'=>80, 'name'=>'note', 'value'=>ov('note', $obj), 'readonly'=>$readonly),
    'total'=>array('type'=>'label', 'id'=>'total', 'name'=>'total', 'value'=>ov('total', $obj), 'width'=>150, 'datatype'=>'money'),
    'ispaid'=>array('type'=>'checkbox', 'name'=>'ispaid', 'onchange'=>'salesinvoicegroupdetail_paidchange(value)', 'value'=>ov('ispaid', $obj), 'readonly'=>$readonly),
    'paymentamount'=>array('type'=>'textbox', 'name'=>'paymentamount', 'id'=>'paymentamount', 'width'=>'150px','datatype'=>'money', 'value'=>ov('paymentamount', $obj), 'readonly'=>$readonly, 'align'=>'right'),
    'paymentaccountid'=>array('type'=>'dropdown', 'name'=>'paymentaccountid','width'=>'150px','items'=>array_cast(chartofaccountlist2(null, array('accounttype'=>'Asset')), array('text'=>'name', 'value'=>'id')), 'readonly'=>$readonly, 'value'=>ov('paymentaccountid', $obj, 0, chartofaccountdetail(null, array('code'=>'000.00'))['id']), 'align'=>'right'),
    'paymentdate'=>array('type'=>'datepicker', 'name'=>'paymentdate', 'width'=>'75px', 'readonly'=>$readonly, 'value'=>ov('paymentdate', $obj), 'align'=>'right'),
  );

  // Action Controls
  $actions = array();
  if($obj && privilege_get('salesinvoicegroup', 'print')) $actions[] = "<td><button class='green' onclick=\"ui.async('ui_salesinvoicegroupprint', [ ui.container_value(ui('.modal')) ], { waitel:this })\"><span class='mdi mdi-printer'></span><label>Cetak</label></button></td>";
  if($readonly && $obj && privilege_get('salesinvoicegroup', 'modify')) $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_salesinvoicegroupdetail', [ $id, 'write' ], { waitel:this })\"><span class='fa fa-edit'></span><label>Ubah</label></button></td>";
  if(!$readonly && !$obj && privilege_get('salesinvoicegroup', 'new')) $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_salesinvoicegroupsave', [ ui.container_value(ui('.modal')) ], { waitel:this })\"><span class='fa fa-save'></span><label>Simpan</label></button></td>";
  if(!$readonly && $obj && privilege_get('salesinvoicegroup', 'modify')) $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_salesinvoicegroupsave', [ ui.container_value(ui('.modal')) ], { waitel:this })\"><span class='fa fa-save'></span><label>Simpan</label></button></td>";
  $actions[] = "<td><button class='hollow' onclick=\"ui.modal_close(ui('.modal'))\"><span class='fa fa-times'></span><label>Close</label></button></td>";

  $c = "<element exp='.modal'>";
  $c .= "
    <div class='scrollable padding1020'>
      " . ui_control($controls['id']) . "
      <table class='form'>
        " . ui_formrow('Kode', ui_control($controls['code'])) . "
        " . ui_formrow('Tanggal', ui_control($controls['date'])) . "
        <tr>
          <th><label>Pelanggan</label></th>
          <td>
            " . ui_control($controls['customerdescription']) . "
            " . (!$readonly ? "<button class='hollow' onclick=\"salesinvoicegroup_itemlookup()\"><span class='fa fa-search'></span><label>Cari Faktur/Retur</label></button>" : '') . "
          </td>
        </tr>
        " . ui_formrow('Alamat', ui_control($controls['address'])) . "
      </table>
      <div style='height:22px'></div>
      <div id='cont33120'>
        " . ui_gridhead(array('columns'=>$salesinvoicegroup_detailcolumns, 'oncolumnresize'=>"ui.async('ui_salesinvoicegroupdetail_columnresize', [ name, width ], {})", 'gridexp'=>'#salesinvoicegroupdetail_items')) . "
        " . ui_grid(array('columns'=>$salesinvoicegroup_detailcolumns, 'name'=>'items', 'value'=>ov('items', $obj), 'readonly'=>$readonly, 'maxitemperpage'=>100, 'id'=>'salesinvoicegroupdetail_items', 'message_novalue'=>'Silakan pilih pelanggan', 'message_notassigned'=>'Silakan pilih pelanggan')) . "
      </div>
      <div style='height:22px'></div>
      <table class='form'>
        " . ui_formrow('Catatan', ui_control($controls['note'])) . "
      </table>
      <table class='form'>
        " . ui_formrow('Total', ui_control($controls['total'])) . "
        <tr><th width='150px'><label>Lunas</label></th><td align='right'>" . ui_control($controls['ispaid']) . "&nbsp;" . ui_control($controls['paymentamount']) . "</td></tr>
        <tr><th><label>Tanggal Lunas</label></th><td align='right'>" . ui_control($controls['paymentdate']) . "</td></tr>
        <tr><th><label>Akun Pelunasan</label></th><td align='right'>" . ui_control($controls['paymentaccountid']) . "</td></tr>
      </table>
      <div class='height20'></div>
      <div class='height20'></div>
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
	  ui.loadscript('rcfx/js/salesinvoicegroup.js', \"salesinvoicegroup_open({ closeable:$closable, width:940, autoheight:true })\");
	</script>
	";
  return $c;

}
function ui_salesinvoicegroupdetail_additems($ids){

  $items = array();
  foreach($ids as $id){
    $id = explode('#', $id);
    $type = $id[0];
    $typeid = $id[1];

    switch($type){
      case 'SI':

        $salesinvoice = pmr("SELECT `id`, `date`, code, customerdescription, total, ispaid, paymentamount, taxable FROM salesinvoice WHERE `id` = ?", array($typeid));
        $salesinvoice['type'] = 'SI';
        $salesinvoice['typeid'] = $salesinvoice['id'];
        $salesinvoice['typetext'] = 'Faktur';
        $items[] = $salesinvoice;

        break;
      case 'SN':

        $salesreturn = pmr("SELECT `id`, `date`, code, customerdescription, total, returnamount as paymentamount, ispaid FROM salesreturn WHERE `id` = ?", array($typeid));
        $salesreturn['type'] = 'SN';
        $salesreturn['typeid'] = $salesreturn['id'];
        $salesreturn['typetext'] = 'Retur';
        $items[] = $salesreturn;

        break;
    }

  }

  $trs = array();
  global $salesinvoicegroup_detailcolumns;
  foreach($items as $item){
    $trs[] = ui_gridrow($item, array(
      'columns'=>$salesinvoicegroup_detailcolumns
    ), 1);
  }

  $c = uijs("
    ui.grid_add_bytrs(ui('#salesinvoicegroupdetail_items'), " . json_encode($trs) . ");
    salesinvoicegroupdetail_total();
    salesinvoicegroupdetail_paymentamount();
    ui.dialog_close();
  ");
  return $c;

}
function ui_salesinvoicegroupdetail_customerlookup($param){

  $hint = ov('hint', $param);
  $customers = pmrs("SELECT description FROM customer WHERE code LIKE ? OR description LIKE ?", array("%$hint%", "%$hint%"));
  return array_cast($customers, array('text'=>'description', 'value'=>'description'));

}
function ui_salesinvoicegroupdetail_itemlookup($customerdescription, $exceptionitems = null){

  $exceptionitems = array_index($exceptionitems, array('type', 'typeid'));
  $items = array();

  // Find invoices
  $salesinvoices = pmrs("SELECT `id`, `date`, code, customerdescription, total, ispaid, paymentamount FROM salesinvoice WHERE
    customerdescription = ? AND (isgroup != 1 or isgroup is null) AND (ispaid = 0 or ispaid is null)", array($customerdescription));
  for($i = 0 ; $i < count($salesinvoices) ; $i++){
    if(isset($exceptionitems['SI'][$salesinvoices[$i]['id']])) continue;
    $salesinvoices[$i]['type'] = 'SI';
    $salesinvoices[$i]['typetext'] = 'Faktur';
    $items[] = $salesinvoices[$i];
  }

  // Find returns
  $salesreturns = pmrs("SELECT `id`, `date`, code, customerdescription, total FROM salesreturn WHERE
    customerdescription = ? AND (isgroup = 0 OR isgroup is null) AND (ispaid = 0 OR ispaid is null)", array($customerdescription));
  for($i = 0 ; $i < count($salesreturns) ; $i++){
    if(isset($exceptionitems['SN'][$salesreturns[$i]['id']])) continue;
    $salesreturns[$i]['type'] = 'SN';
    $salesreturns[$i]['typetext'] = 'Retur';
    $items[] = $salesreturns[$i];
  }

  $c = "<element exp='.dialog'>";
  $c .= "
      <div class='box-dialog'>
        <div class='scrollable' style='height:250px'>
          " . ui_gridhead(array('columns'=>ui_salesinvoicegroup_itemlookupcolumns(), 'gridexp'=>'#itemlookupgrid')) . "
          " . ui_grid(array('columns'=>ui_salesinvoicegroup_itemlookupcolumns(), 'value'=>$items, 'id'=>'itemlookupgrid', 'maxitemperpage'=>200)) . "
        </div>
        <div style='height: 15px'></div>
        <button class='blue' onclick=\"salesinvoicegroupdetail_itemlookupapply()\"><span class='fa fa-check'></span><label>Tambah Grup Faktur</label></button>
        <button class='hollow' onclick=\"ui.dialog_close()\"><span class='fa fa-times'></span><label>Close</label></button>
      </div>
      ";
  $c .= "</element>";
  $c .= uijs("
        ui.dialog_open({ width:600 });
      ");
  return $c;

}
function ui_salesinvoicegroup_itemlookupcolumns(){

  return array(
    array('active'=>1, 'name'=>'checked', 'text'=>'', 'width'=>30, 'type'=>'html', 'html'=>'ui_salesinvoicegroup_itemlookupcolumn0', 'nodittomark'=>1),
    array('active'=>1, 'name'=>'typetext', 'text'=>'Tipe', 'width'=>80),
    array('active'=>1, 'name'=>'date', 'text'=>'Tanggal', 'width'=>100, 'datatype'=>'date'),
    array('active'=>1, 'name'=>'code', 'text'=>'Kode', 'width'=>100),
    array('active'=>1, 'name'=>'total', 'text'=>'Total', 'width'=>100, 'datatype'=>'money')
  );

}
function ui_salesinvoicegroup_itemlookupcolumn0($obj){

  return ui_checkbox(array('name'=>'checked')) . ui_hidden(array('name'=>'id', 'value'=>$obj['type'] . '#' . $obj['id']));

}
function ui_salesinvoicegroupdetail_columnresize($name, $width){

  $module = m_loadstate();
  for($i = 0 ; $i < count($module['detailcolumns']) ; $i++){
    if($module['detailcolumns'][$i]['name'] == $name){
      $module['detailcolumns'][$i]['width'] = $width;
    }
  }
  m_savestate($module);

}
function ui_salesinvoicegroupdetail_col0($obj, $params){

  $c = ui_datepicker(array(
    'name'=>'date',
    'value'=>ov('date', $obj),
    'readonly'=>1,
    'width'=>'100%',
    'ischild'=>1
  ));
  $c .= ui_hidden(array('name'=>'typeid', 'value'=>$obj['typeid'], 'ischild'=>1)) .
      ui_hidden(array('name'=>'type', 'value'=>$obj['type'], 'ischild'=>1));
  return $c;

}
function ui_salesinvoicegroupdetail_col1($obj){

  return ui_label(array('value'=>ov('customerdescription', $obj), 'ischild'=>1));

}
function ui_salesinvoicegroupdetail_col3($obj, $params){

  return ui_textbox(array(
    'name'=>'total',
    'value'=>ov('total', $obj),
    'readonly'=>1,
    'width'=>'100%',
    'datatype'=>'money',
    'ischild'=>1
  ));

}
function ui_salesinvoicegroupdetail_col4($obj, $params){

  $readonly = ov('readonly', $params, 0);
  return "<div class='align-center'>" .
    ui_checkbox(array(
      'name'=>'ispaid',
      'value'=>ov('ispaid', $obj),
      'readonly'=>$readonly,
      'width'=>'100%',
      'datatype'=>'money',
      'ischild'=>1,
      'onchange'=>"salesinvoicegroupdetail_onrowpaid(value, this)"
    )) .
  "</div>";

}
function ui_salesinvoicegroupdetail_col5($obj, $params){

  return ui_textbox(array(
    'name'=>'paymentamount',
    'value'=>ov('paymentamount', $obj),
    'readonly'=>1,
    'width'=>'100%',
    'datatype'=>'money',
    'ischild'=>1
  ));

}
function ui_salesinvoicegroupdetail_col6($obj, $params){

  $readonly = ov('readonly', $params);
  $c = "<div class='align-center'>";
  $c .= !$readonly ? "<span class='fa fa-times-circle color-red' onclick=\"salesinvoicegroupdetail_oninvoiceremove(event, this.parentNode.parentNode.parentNode)\"></span>" : '';
  $c .= "</div>";
  return $c;

}
function ui_salesinvoicegroupdetail_col7($obj, $params){

  $taxable = ov('taxable', $obj);
  $c = "<div class='align-center'>";
  $c .= !$taxable ? "<span class='fa fa-times-circle color-red'></span>" : "<span class='fa fa-check-circle color-green'></span>";
  $c .= "</div>";
  return $c;

}
function ui_salesinvoicegroupdetail_customerchange($customerdescription){

  $customer = customerdetail(array('address'), array('description'=>$customerdescription));
  if($customer){
    $c = uijs("
      customer = " . json_encode($customer) . ";
      ui.container_setvalue(ui('.modal'), customer, 1);
    ");
    return $c;

  }

}

function ui_salesinvoicegroupdetail_createfrominvoices($salesinvoiceids){

  // Validation
  // 1. Validate parameter
  if(gettype($salesinvoiceids) != 'array' || count($salesinvoiceids) <= 0) throw new Exception('Tidak dapat membuat faktur, tidak ada faktur dipilih.');

  // Check if genki
  $is_genki = true;
  $customers = pmrs("select customerdescription from salesinvoice where `id` in (" . implode(', ', $salesinvoiceids) . ")");
  foreach($customers as  $customer){
    if(strpos(strtolower($customer['customerdescription']), 'genki') !== false ||
      strpos(strtolower($customer['customerdescription']), 'aeon') ||
      strpos(strtolower($customer['customerdescription']), 'suncity') ||
      strpos(strtolower($customer['customerdescription']), 'inti idola'));
    else $is_genki = false;
  }

  // 2. Check if salesinvoiceids is grouped and is the same customerid
  $items = array(); // Retrieve each salesinvoice data
  $customerid = null;
  $salesinvoiceids = array_unique($salesinvoiceids);
  $taxable = 0; $non_taxable = 0;
  foreach($salesinvoiceids as $salesinvoiceid){
    $salesinvoice = salesinvoicedetail(null, array('id'=>$salesinvoiceid));
    if(!$salesinvoice) throw new Exception('Tidak dapat membuat grup, faktur tidak terdaftar.');
    if($salesinvoice['isgroup']) throw new Exception('Tidak dapat membuat grup, faktur sudah ada grup');

    if(!$is_genki){
      if($customerid == null) $customerid = $salesinvoice['customerid'];
      else if($salesinvoice['customerid'] != $customerid) throw new Exception('Tidak dapat membuat grup faktur dari pelanggan yang berbeda.');
      if($salesinvoice['taxable']) $taxable = 1;
      else $non_taxable = 1;
      if($taxable + $non_taxable > 1) exc("Tidak dapat membuat grup faktur dari faktur non pajak dan pajak");
    }

    $salesinvoice['type'] = 'SI';
    $salesinvoice['typeid'] = $salesinvoice['id'];
    $items[] = $salesinvoice;
  }

  // Generate salesinvoicegroup object
  // - Retrieve group data from last salesinvoice
  $obj = array(
    'date'=>date('Ymd'),
    'code'=>salesinvoicegroupcode(),
    'customerdescription'=>ov('customerdescription', $salesinvoice),
    'address'=>ov('address', $salesinvoice),
    'items'=>$items
  );
  return ui_salesinvoicegroupdetail($obj, 'write');

}

function ui_salesinvoicegroupsave($obj){

  isset($obj['id']) && intval($obj['id']) > 0 ? salesinvoicegroupmodify($obj) : salesinvoicegroupentry($obj);
  return uijs("m_load();ui.modal_close(ui('.modal'));");

}

function ui_salesinvoicegroupremove($id){

  salesinvoicegroupremove(array('id'=>$id));
  return m_load();

}

function ui_salesinvoicegroupprint($salesinvoicegroup){

  isset($salesinvoicegroup['id']) && intval($salesinvoicegroup['id']) > 0 ? $salesinvoicegroup = salesinvoicegroupmodify($salesinvoicegroup) : $salesinvoicegroup = salesinvoicegroupentry($salesinvoicegroup);

  $printtemplate = 'A4';
  $id = $salesinvoicegroup['id'];
  $salesinvoicegroup = salesinvoicegroupdetail(null, array('id'=>$id));

  $c = "<element exp='.printarea'>";
  ob_start();
  $page = 0;
  if($printtemplate == 'Half A4')
    include 'template/salesinvoicegroup.php';
  else
    include 'template/salesinvoicegroup_a4.php';
  $c .= ob_get_clean();
  $c .= "</element>";
  $c .= "<script type='text/javascript'>
    window.print();
    ui.control_setvalue(ui('%id', ui('.modal')), " . $salesinvoicegroup['id'] . ");
  </script>";
  return $c;

}

function ui_salesinvoicegroupexport(){

  global $module;
  $preset = $module['presets'][$module['presetidx']];
  $columns = $preset['columns'];
  $sorts = $preset['sorts'];
  $quickfilters = ov('quickfilters', $preset);
  $filters = $preset['filters'];
  $filters = m_quickfilter_to_filters($filters, $quickfilters);

  $salesinvoicegroup_columnaliases = array(
    'ispaid'=>'t1.ispaid',
    'isreceipt'=>'t1.isreceipt',
    'date'=>'t1.date',
    'code'=>'t1.code',
    'customerdescription'=>'t1.customerdescription',
    'address'=>'t1.address',
    'note'=>'t1.note',
    'total'=>'t1.total',
    'paymentaccountname'=>'t3.name as paymentaccountname',
    'paymentdate'=>'t1.paymentdate',
    'paymentamount'=>'t1.paymentamount',
    'itemtype'=>"IF(t2.type = 'SI', 'Faktur' , 'Retur') as itemtype",
    'itemcode'=>"IF(t2.type = 'SI', (SELECT code FROM salesinvoice WHERE `id` = t2.typeid), (SELECT code FROM salesreturn WHERE `id` = t2.typeid)) as itemcode",
    'itemtotal'=>"IF(t2.type = 'SI', (SELECT total FROM salesinvoice WHERE `id` = t2.typeid), (SELECT total FROM salesreturn WHERE `id` = t2.typeid)) as itemtotal",
    'createdon'=>'t1.createdon'
  );

  $params = array();
  $columnquery = columnquery_from_columnaliases($columns, $salesinvoicegroup_columnaliases);
  $wherequery = 'WHERE t1.id = t2.salesinvoicegroupid AND t1.paymentaccountid = t3.id' . str_replace('WHERE', 'AND', wherequery_from_filters($params, $filters, $salesinvoicegroup_columnaliases));
  $sortquery = sortquery_from_sorts($sorts, $salesinvoicegroup_columnaliases);
  $limitquery = limitquery_from_limitoffset($limits);

  if(strlen($columnquery) > 0) $columnquery = ', ' . $columnquery;

  $query = "SELECT 'salesinvoicegroup' as `type`, t1.id, t1.paymentaccountid $columnquery
    FROM salesinvoicegroup t1, salesinvoicegroupitem t2, chartofaccount t3 $wherequery $sortquery $limitquery";
  $data = pmrs($query, $params);

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

  $filepath = 'usr/salesinvoicegroup-' . date('j-M-Y') . '.xlsx';
  array_to_excel($items, $filepath);

  echo uijs("ui('#downloader').href = '$filepath';ui('#downloader').click();");

}

?>