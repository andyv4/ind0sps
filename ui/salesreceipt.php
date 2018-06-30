<?php

require_once 'api/salesreceipt.php';
require_once 'api/customer.php';

function ui_salesreceiptdetail($id, $mode = 'read'){

  $obj = is_array($id) ? $id : (intval($id) > 0 ? salesreceiptdetail(null, array('id'=>$id)) : null);

  if($mode != 'read' && $obj && !privilege_get('salesreceipt', 'modify')) $mode = 'read';
  if($mode == 'read' && !$obj) throw new Exception('Kwitansi dengan nomor ini tidak ada.');
  $readonly = $mode == 'write' ? 0 : 1;
  $closable = $readonly ? 1 : 0;
  $is_new = !$readonly && !$obj ? true : false;
  if($is_new && !privilege_get('salesreceipt', 'new')) exc("Anda tidak dapat membuat kwitansi.");
  $beneficiarydetails = objectToArray(json_decode(systemvarget('beneficiarydetail')));
  $code = ov('code', $obj);
  $date = ov('date', $obj);

  $code = $is_new ? salesreceiptcode() : $code;
  $date = $is_new ? date('Ymd') : $date;

  $detailcolumns = array(
      array('active'=>1, 'name'=>'col0', 'text'=>'Kode', 'type'=>'html', 'html'=>'ui_salesreceiptdetail_col0', 'width'=>150),
      array('active'=>1, 'name'=>'col2', 'text'=>'Tanggal', 'type'=>'html', 'html'=>'ui_salesreceiptdetail_col2', 'width'=>150),
      array('active'=>1, 'name'=>'col3', 'text'=>'Total', 'type'=>'html', 'html'=>'ui_salesreceiptdetail_col3', 'width'=>100),
      array('active'=>1, 'name'=>'col4', 'text'=>'Lunas', 'type'=>'html', 'html'=>'ui_salesreceiptdetail_col4', 'width'=>50),
      array('active'=>1, 'name'=>'col5', 'text'=>'Pembayaran', 'type'=>'html', 'html'=>'ui_salesreceiptdetail_col5', 'width'=>100),
      array('active'=>1, 'name'=>'col6', 'text'=>'', 'type'=>'html', 'html'=>'ui_salesreceiptdetail_col6', 'width'=>24),
  );

  $controls = array(
      'id'=>array('type'=>'hidden', 'name'=>'id', 'value'=>ov('id', $obj)),
      'code'=>array('type'=>'textbox', 'name'=>'code', 'value'=>$code, 'width'=>100, 'readonly'=>$readonly),
      'date'=>array('type'=>'datepicker', 'name'=>'date', 'value'=>$date, 'readonly'=>$readonly),
      'customerdescription'=>array('type'=>'textbox', 'name'=>'customerdescription', 'readonly'=>$readonly, 'value'=>ov('customerdescription', $obj), 'width'=>600, 'src'=>'ui_salesreceiptdetail_customerlookup', 'onchange'=>"ui.async('ui_salesreceiptdetail_customerapply', [ value ], {})"),
      'address'=>array('type'=>'textarea', 'name'=>'address', 'value'=>ov('address', $obj), 'width'=>600, 'height'=>80, 'readonly'=>$readonly),
      'note'=>array('type'=>'textarea', 'width'=>400, 'height'=>80, 'name'=>'note', 'value'=>ov('note', $obj), 'readonly'=>$readonly),
      'total'=>array('type'=>'label', 'name'=>'total', 'value'=>ov('total', $obj), 'width'=>150, 'datatype'=>'money'),
      'ispaid'=>array('type'=>'checkbox', 'name'=>'ispaid', 'onchange'=>'salesreceiptdetail_onpaidchange(value, this)', 'value'=>ov('ispaid', $obj), 'readonly'=>$readonly),
      'paymentamount'=>array('type'=>'textbox', 'name'=>'paymentamount','width'=>'150px','datatype'=>'money', 'value'=>ov('paymentamount', $obj), 'readonly'=>$readonly),
      'paymentaccountid'=>array('type'=>'dropdown', 'name'=>'paymentaccountid','width'=>'150px','items'=>array_cast(chartofaccountlist2(null, array('accounttype'=>'Asset')), array('text'=>'name', 'value'=>'id')), 'readonly'=>$readonly, 'value'=>ov('paymentaccountid', $obj, 0, chartofaccountdetail(null, array('code'=>'000.00'))['id'])),
      'paymentdate'=>array('type'=>'datepicker', 'name'=>'paymentdate', 'width'=>'75px', 'readonly'=>$readonly, 'value'=>ov('paymentdate', $obj)),
      'beneficiarydetail'=>array('type'=>'dropdown', 'name'=>'beneficiarydetail', 'readonly'=>$readonly, 'items'=>$beneficiarydetails, 'width'=>400, 'value'=>ov('beneficiarydetail', $obj))
  );

  // Action Controls
  $actions = array();
  if($obj && privilege_get('salesreceipt', 'print')) $actions[] = "<td><button class='green' onclick=\"ui.async('ui_salesreceiptprint', [ ui.container_value(ui('.modal')) ], { waitel:this })\"><span class='mdi mdi-printer'></span><label>Cetak</label></button></td>";
  if($readonly && $obj && privilege_get('salesreceipt', 'modify')) $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_salesreceiptdetail', [ $id, 'write' ], { waitel:this })\"><span class='fa fa-edit'></span><label>Ubah</label></button></td>";
  if(!$readonly && !$obj && privilege_get('salesreceipt', 'new')) $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_salesreceiptsave', [ ui.container_value(ui('.modal')) ], { waitel:this })\"><span class='fa fa-save'></span><label>Simpan</label></button></td>";
  if(!$readonly && $obj && privilege_get('salesreceipt', 'modify')) $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_salesreceiptsave', [ ui.container_value(ui('.modal')) ], { waitel:this })\"><span class='fa fa-save'></span><label>Simpan</label></button></td>";
  $actions[] = "<td><button class='hollow' onclick=\"ui.modal_close(ui('.modal'))\"><span class='fa fa-times'></span><label>Close</label></button></td>";

  $c = "<element exp='.modal'>";
  $c .= "
    <div class='scrollable padding1020'>
      " . ui_control($controls['id']) . "
      <table class='form'>
        " . ui_formrow('Kode', ui_control($controls['code'])) . "
        " . ui_formrow('Tanggal', ui_control($controls['date'])) . "
        " . ui_formrow('Pelanggan', ui_control($controls['customerdescription'])) . "
        " . ui_formrow('Alamat', ui_control($controls['address'])) . "
      </table>
      <div style='height:22px'></div>
      <div id='cont33120'>
        " . ui_gridhead(array('columns'=>$detailcolumns, 'oncolumnresize'=>"ui.async('ui_salesreceiptdetail_columnresize', [ name, width ], {})", 'gridexp'=>'#items')) . "
        " . ui_grid(array('columns'=>$detailcolumns, 'name'=>'items', 'value'=>ov('items', $obj), 'readonly'=>$readonly, 'id'=>'items')) . "
      </div>
      <div style='height:22px'></div>
      <table class='form'>
        " . ui_formrow('Beneficiary', ui_control($controls['beneficiarydetail'])) . "
        " . ui_formrow('Catatan', ui_control($controls['note'])) . "
      </table>
      <table class='form'>
        " . ui_formrow('Total', ui_control($controls['total'])) . "
        <tr><th width='150px'><label>Lunas</label></th><td align='right'>" . ui_control($controls['ispaid']) . "&nbsp;" . ui_control($controls['paymentamount']) . "</td></tr>
        <tr><th><label>Tanggal Lunas</label></th><td align='right'>" . ui_control($controls['paymentdate']) . "</td></tr>
        <tr><th><label>Akun Pelunasan</label></th><td align='right'>" . ui_control($controls['paymentaccountid']) . "</td></tr>
      </table>
    </div>
    <div class='foot'>
      <table cellspacing='5'>
        <tr>
          " . ($mode == 'write' ? "<td><button class='blue' onclick=\"ui.async('ui_salesreceipt_salesinvoicegroupselector', [], {})\"><label>Cari Grup Faktur...</label></button></td>" : '') . "
          <td style='width: 100%'></td>
          " . implode('', $actions) . "
        </tr>
      </table>
    </div>
	";
  $c .= "</element>";
  $c .= "
	<script>
		ui.loadscript('rcfx/js/salesreceipt.js', \"salesreceipt_open({ closeable:$closable, width:900, autoheight:true })\");
	</script>
	";
  return $c;

}

function ui_salesreceiptdetail_customerlookup($param){

  $hint = ov('hint', $param);
  $query = "SELECT customerdescription FROM salesinvoicegroup WHERE `customerdescription` LIKE ? AND
    (`isreceipt` is null OR `isreceipt` = 0 OR `isreceipt` != 1) GROUP BY customerdescription ORDER BY customerdescription";
  $customers = pmrs($query, array("%$hint%"));
  return array_cast($customers, array('text'=>'customerdescription', 'value'=>'customerdescription'));

}

function ui_salesreceiptdetail_customerapply($customerdescription){

  $query = "SELECT `id`, code, `date`, customerdescription, address, total, ispaid, paymentamount
    FROM salesinvoicegroup WHERE `customerdescription` = ? AND
    (`isreceipt` is null OR `isreceipt` = 0 OR `isreceipt` != 1)
    GROUP BY code ORDER BY `date`, `id`";
  $data = pmrs($query, array($customerdescription));

  $module = m_loadstate();
  $c = ui_grid_add(array(
      'name'=>'items',
      'columns'=>$module['detailcolumns'],
      'value'=>$data
  ));
  $c .= uijs("
    salesreceipttotal();
    salesreceiptpaymentamount();
  ");
  return $c;

}

function ui_salesreceiptdetail_additems($ids){

  $query = "SELECT `id`, code, `date`, customerdescription, address, total, ispaid, paymentamount
    FROM salesinvoicegroup WHERE `id` IN (" . implode(', ', $ids) . ") AND
    (`isreceipt` is null OR `isreceipt` = 0 OR `isreceipt` != 1)
    GROUP BY code ORDER BY `date`, `id`";
  $data = pmrs($query);

  $module = m_loadstate();
  $c = ui_grid_add(array(
    'name'=>'items',
    'columns'=>$module['detailcolumns'],
    'value'=>$data
  ));
  return $c;

}

function ui_salesreceiptdetail_columnresize($name, $width){

  $module = m_loadstate();
  for($i = 0 ; $i < count($module['detailcolumns']) ; $i++){
    if($module['detailcolumns'][$i]['name'] == $name){
      $module['detailcolumns'][$i]['width'] = $width;
    }
  }
  m_savestate($module);

}

function ui_salesreceiptdetail_col0($obj, $params){

  return ui_label(array(
    'name'=>'code',
    'value'=>ov('code', $obj),
    'readonly'=>$params['readonly'],
    'width'=>'100%',
    'ischild'=>1,
    'src'=>'ui_salesreceiptdetail_groupinvoice_hint',
    'onchange'=>"salesreceipt_invoicegrouphint(value, this)",
    'ischild'=>1
  )) . ui_hidden(array('name'=>'id', 'value'=>$obj['id'], 'ischild'=>1));

}

function ui_salesreceiptdetail_col2($obj, $params){

  return ui_datepicker(array(
    'name'=>'date',
    'value'=>ov('date', $obj),
    'readonly'=>$params['readonly'],
    'width'=>'100%',
    'readonly'=>1,
    'ischild'=>1
  ));

}

function ui_salesreceiptdetail_col3($obj, $params){

  return ui_label(array(
    'name'=>'total',
    'value'=>ov('total', $obj),
    'readonly'=>$params['readonly'],
    'width'=>'100%',
    'datatype'=>'money',
    'ischild'=>1
  ));

}

function ui_salesreceiptdetail_col4($obj, $params){

  return "<div class='align-center'>" .
  ui_checkbox(array(
    'name'=>'ispaid',
    'value'=>ov('ispaid', $obj),
    'readonly'=>$params['readonly'],
    'width'=>'100%',
    'datatype'=>'money',
    'ischild'=>1,
    'onchange'=>"salesreceiptitem_paidstatuschange(value, this)"
  )) .
  "</div>";

}

function ui_salesreceiptdetail_col5($obj, $params){

  return ui_textbox(array(
    'name'=>'paymentamount',
    'value'=>ov('paymentamount', $obj),
    'readonly'=>1,
    'width'=>'100%',
    'datatype'=>'money',
    'ischild'=>1
  ));

}

function ui_salesreceiptdetail_col6($obj, $params){

  $readonly = ov('readonly', $params);
  $c = "<div class='align-center'>";
  $c .= !$readonly ? "<span class='fa fa-times-circle color-red' onclick=\"ui.grid_remove(this.parentNode.parentNode.parentNode)\"></span>" : '';
  $c .= "</div>";
  return $c;

}

function ui_salesreceiptsave($obj){

  isset($obj['id']) && intval($obj['id']) > 0 ? salesreceiptmodify($obj) : salesreceiptentry($obj);
  return m_load() . uijs("ui.modal_close(ui('.modal'))");

}

function ui_salesreceiptprint($obj){

  isset($obj['id']) && intval($obj['id']) > 0 ? $obj = salesreceiptmodify($obj) : $obj = salesreceiptentry($obj);

  $id = $obj['id'];

  $c = '';
  $c .= "<element exp='.printarea'>";
  ob_start();
  include 'template/salesreceipt.php';
  $c .= ob_get_clean();
  $c .= "</element>";
  $c .= "<script type='text/javascript'>
    window.print();
    ui('%id').value = '$id';
  </script>";
  return $c;
}

function ui_salesreceiptremove($id){

  salesreceiptremove(array('id'=>$id));
  return m_load();

}

function ui_salesreceiptdetail_groupinvoice_hint($param){

  // Retrieve hint data
  $hint = ov('hint', $param);
  $data = pmrs("SELECT `id`, code, customerdescription FROM salesinvoicegroup WHERE (code LIKE ? or customerdescription LIKE ?) AND isreceipt < 1", array("%$hint%", "%$hint%"));

  // Cast data
  $items = array();
  if(is_array($data))
    foreach($data as $obj){
      $items[] = array(
        'text'=>$obj['code'] . ' ' . $obj['customerdescription'],
        'value'=>$obj['id']
      );
    }
  return $items;

}

function ui_salesreceiptdetail_groupinvoice_addbycustomerdescription($id){

  $salesinvoicegroup = salesinvoicegroupdetail(null, array('id'=>$id));
  $c = uijs("salesinvoicereceipt_setitemrow(" . json_encode($salesinvoicegroup) . ");");
  return $c;

}

function ui_salesreceipt_salesinvoicegroupselector(){

  $c = "<element exp='.dialog'>";
  $c .= "
      <div class='box-dialog'>
        <div>
          " . ui_autocomplete(array(
                'name'=>'', 'width'=>475,
                'placeholder'=>'Nomor Grup / Nama Pelanggan',
                'src'=>'ui_salesreceipt_salesinvoicegroupselector_lookup',
                'onchange'=>"ui.async('ui_salesreceipt_salesinvoicegroupselector_select', [ value ])"
          )) . "
          <div></div>
          <div>
            " . ui_gridhead(array('columns'=>ui_salesreceipt_salesinvoicegroupselector_columns())) . "
            <div id='selector1cont' class='scrollable' style='height:200px'><label>Tidak ada grup faktur yang dipilih.</label></div>
          </div>
        </div>
        <div style='height: 15px'></div>
        <button class='blue' onclick=\"salesreceipt_selectorapply()\"><span class='fa fa-check'></span><label>Tambah Grup Faktur</label></button>
        <button class='hollow' onclick=\"ui.dialog_close()\"><span class='fa fa-times'></span><label>Close</label></button>
      </div>
      ";
  $c .= "</element>";
  $c .= uijs("
        ui.dialog_open({ width:500 });
      ");
  return $c;

}

function ui_salesreceipt_salesinvoicegroupselector_lookup($param){

  $hint = ov('hint', $param);
  $query = "SELECT customerdescription as text, customerdescription as value FROM salesinvoicegroup
    WHERE customerdescription LIKE ? AND (`isreceipt` is null OR `isreceipt` = 0 OR `isreceipt` != 1)
    GROUP BY customerdescription";
  $rows = pmrs($query, array("%$hint%"));
  return $rows;

}

function ui_salesreceipt_salesinvoicegroupselector_select($customerdescription){

  $query = "SELECT `id`, code, `date`, customerdescription, address, total, ispaid, paymentamount
    FROM salesinvoicegroup WHERE customerdescription = ? AND
    (`isreceipt` is null OR `isreceipt` = 0 OR `isreceipt` != 1)
    GROUP BY code ORDER BY `date`, `id`";
  $data = pmrs($query, array($customerdescription));

  $c = "<element exp='#selector1cont'>";
  $c .= ui_grid(array(
    'id'=>'selector1',
    'columns'=>ui_salesreceipt_salesinvoicegroupselector_columns(),
    'value'=>$data
  ));
  $c .= "</element>";
  return $c;

}

function ui_salesreceipt_salesinvoicegroupselector_columns(){

  $columns = array(
    array('active'=>1, 'name'=>'', 'text'=>'', 'width'=>30, 'type'=>'html', 'html'=>'ui_salesreceipt_salesinvoicegroupselector_checkbox'),
    array('active'=>1, 'name'=>'code', 'text'=>'Kode Grup', 'width'=>80),
    array('active'=>1, 'name'=>'date', 'text'=>'Tanggal', 'width'=>100, 'datatype'=>'date'),
    array('active'=>1, 'name'=>'customerdescription', 'text'=>'Pelanggan', 'width'=>150),
  );
  return $columns;

}

function ui_salesreceipt_salesinvoicegroupselector_checkbox($obj){

  return ui_hidden(array('name'=>'id', 'value'=>$obj['id'])) . ui_checkbox(array('name'=>'checked'));

}

function ui_salesreceipt_salesinvoicegroupselector_apply($salesinvoicegroupids){

  $query = "SELECT `id`, code, `date`, customerdescription, address, total, ispaid, paymentamount
    FROM salesinvoicegroup WHERE `id` IN (" . implode(', ', $salesinvoicegroupids) . ") AND
    (`isreceipt` is null OR `isreceipt` = 0 OR `isreceipt` != 1)
    GROUP BY code ORDER BY `date`, `id`";
  $data = pmrs($query);

  $module = m_loadstate();
  $c = ui_grid_add(array(
      'name'=>'items',
      'columns'=>$module['detailcolumns'],
      'value'=>$data
  ));
  $c .= uijs("
    salesreceipttotal();
    salesreceiptpaymentamount();
  ");
  return $c;


}

function ui_salesreceiptdetail_createfromgroups($salesinvoicegroupids){

  if(is_debugmode()) console_warn("Begin ui_salesreceiptdetail_createfromgroups");
  if(is_debugmode()) console_warn("Input:");
  if(is_debugmode()) console_log($salesinvoicegroupids);

  // Validation
  // 1. Validate parameter
  if(gettype($salesinvoicegroupids) != 'array' || count($salesinvoicegroupids) <= 0) throw new Exception('Tidak dapat membuat kwitansi, tidak ada faktur dipilih.');
  // 2. Check if salesinvoiceids is grouped and is the same customerid
  $salesinvoicegroups = array(); // Retrieve each salesinvoice data
  $customerid = null;
  $salesinvoicegroupids = array_unique($salesinvoicegroupids);
  foreach($salesinvoicegroupids as $salesinvoicegroupid){
    $salesinvoicegroup = salesinvoicegroupdetail(null, array('id'=>$salesinvoicegroupid));
    if(!$salesinvoicegroup) throw new Exception('Tidak dapat membuat kwitansi, grup tidak terdaftar.');
    if($salesinvoicegroup['isreceipt']) throw new Exception('Tidak dapat membuat kwitansi, grup sudah ada kwitansi');
    $salesinvoicegroups[] = $salesinvoicegroup;
  }

  // Generate salesinvoicegroup object
  // - Retrieve group data from last salesinvoice
  $obj = array(
      'date'=>date('Ymd'),
      'code'=>salesreceiptcode(),
      'customerdescription'=>ov('customerdescription', $salesinvoicegroup),
      'address'=>ov('address', $salesinvoicegroup),
      'items'=>$salesinvoicegroups
  );

  if(is_debugmode()) console_log($salesinvoicegroup);
  if(is_debugmode()) console_log($obj);

  return ui_salesreceiptdetail($obj, 'write');

}

function ui_salesreceiptexport(){

  global $module;
  $preset = $module['presets'][$module['presetidx']];
  $columns = $preset['columns'];
  $sorts = $preset['sorts'];
  $quickfilters = ov('quickfilters', $preset);
  $filters = $preset['filters'];
  $filters = m_quickfilter_to_filters($filters, $quickfilters);

  $salesreceipt_columnaliases = array(
    'ispaid'=>'t1.ispaid',
    'date'=>'t1.date',
    'code'=>'t1.code',
    'customerid'=>'t1.customerid',
    'customerdescription'=>'t1.customerdescription',
    'address'=>'t1.address',
    'note'=>'t1.note',
    'total'=>'t1.total',
    'paymentaccountname'=>'t3.name as paymentaccountname',
    'paymentdate'=>'t1.paymentdate',
    'paymentamount'=>'t1.paymentamount',
    'beneficiarydetail'=>'t1.beneficiarydetail',
    'createdon'=>'t1.createdon',
    'salesinvoicegroupcode'=>'t2.code as salesinvoicegroupcode'
  );

  $params = array();
  $columnquery = columnquery_from_columnaliases($columns, $salesreceipt_columnaliases);
  $wherequery = 'WHERE t1.id = t2.salesreceiptid AND t1.paymentaccountid = t3.id' . str_replace('WHERE', 'AND', wherequery_from_filters($params, $filters, $salesreceipt_columnaliases));
  $sortquery = sortquery_from_sorts($sorts, $salesreceipt_columnaliases);
  $limitquery = limitquery_from_limitoffset($limits);

  $query = "SELECT 'salesreceipt' as `type`, t1.id, $columnquery FROM
    salesreceipt t1, salesinvoicegroup t2, chartofaccount t3 $wherequery $sortquery $limitquery";
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

  $filepath = 'usr/sales-receipt-' . date('j-M-Y') . '.xlsx';
  array_to_excel($items, $filepath);

  echo uijs("ui('#downloader').href = '$filepath';ui('#downloader').click();");

}

?>