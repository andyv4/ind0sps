<?php
if(!systemvarget('salesable') || privilege_get('salesinvoice', 'list') < 1){ include 'notavailable.php'; return; }

$modulename = 'salesinvoice';
$groupable = true;

require_once 'api/salesinvoice.php';
require_once 'ui/salesinvoice.php';
require_once 'ui/salesinvoicegroup.php';

function datasource($columns = null, $sorts = null, $filters = null, $limits = null, $groups = null){

  if(!is_array($filters)) $filters = [];

  $privilege_salesinvoicetype = userkeystoreget($_SESSION['user']['id'], 'privilege.salesinvoicetype', 'non-tax');
  switch($privilege_salesinvoicetype){
    case 'non-tax':
      $filters[] = [ 'name'=>'taxable', 'operator'=>'=', 'value'=>'0' ];
      break;
    case 'tax':
      $filters[] = [ 'name'=>'taxable', 'operator'=>'=', 'value'=>'1' ];
      break;
  }

  $tax_mode = isset($_SESSION['tax_mode']) && $_SESSION['tax_mode'] > 0 ? true : false;
  if($tax_mode){
    $filters[] = [
      'name'=>'taxable',
      'operator'=>'=',
      'value'=>1
    ];
  }

  // For GF Culinary
  if($_SESSION['user']['id'] == 1004){
    if(count($filters) > 0) $filters[] = [ 'type'=>'and' ];
    $filters[] = [ 'type'=>'(' ];
    $filters[] = [ 'name'=>'customerdescription', 'operator'=>'contains', 'value'=>'FISH N CO' ];
    $filters[] = [ 'type'=>'or' ];
    $filters[] = [ 'name'=>'customerdescription', 'operator'=>'contains', 'value'=>'DUO VINI DICI' ];
    $filters[] = [ 'type'=>'or' ];
    $filters[] = [ 'name'=>'customerdescription', 'operator'=>'contains', 'value'=>'PT. FAJAR INDO SUKSES HARMONI' ];
    $filters[] = [ 'type'=>')' ];
  }
  else if($_SESSION['user']['id'] == 1010){
    if(count($filters) > 0) $filters[] = [ 'type'=>'and' ];
    $filters[] = [ 'type'=>'(' ];
    $filters[] = [ 'name'=>'inventorycode', 'operator'=>'contains', 'value'=>'KOB' ];
    $filters[] = [ 'type'=>')' ];
  }

  // Apply allowed_salesman (_self,*,<name>,<name>)
  $sales_allowed_salesman = userkeystoreget($_SESSION['user']['id'], 'privilege.sales_allowed_salesman');
  if(strpos($sales_allowed_salesman, '*') === false){
    $sales_allowed_salesman = explode(',', $sales_allowed_salesman);
    $salesman_count = 0;
    if(count($filters) > 0) $filters[] = [ 'type'=>'and' ];
    $filters[] = [ 'type'=>'(' ];
    foreach($sales_allowed_salesman as $salesman){
      if(empty($salesman)) continue;
      $salesman = $salesman == '_self' ? $_SESSION['user']['userid'] : $salesman;
      if($salesman_count > 0) $filters[] = [ 'type'=>'or' ];
      $filters[] = [ 'name'=>'salesmanname', 'operator'=>'contains', 'value'=>$salesman ];
      $salesman_count++;
    }
    if(!$salesman_count) $filters[] = [ 'name'=>'salesmanname', 'operator'=>'contains', 'value'=>$_SESSION['user']['userid'] ];
    $filters[] = [ 'type'=>')' ];
  }

  return salesinvoicelist($columns, $sorts, $filters, $limits, $groups);

}
function customheadcolumns(){

  $html = [];
  if(privilege_get('salesinvoice', 'new')){
    $html[] = "<td><button class='blue new-btn' data-tooltip='Faktur Non Pajak' onclick=\"ui.async('ui_salesinvoicenew', [ 0 ], { waitel:this })\"><span class='mdi mdi-plus'></span></button></td>";
    if(in_array(userkeystoreget($_SESSION['user']['id'], 'privilege.salesinvoicetype'), [ '*', 'tax' ])) $html[] = "<td><button class='mint new-btn new-btn-tax' data-tooltip='Faktur Pajak' onclick=\"ui.async('ui_salesinvoicenew', [ 1 ], { waitel:this })\"><span class='mdi mdi-plus'></span></button></td>";
  }
  if(privilege_get('salesinvoicegroup', 'new')) $html[] = "<td id='groupable' style='display:none'><button class='green group-btn' onclick=\"salesinvoicegroup_create()\"><span class='mdi mdi-plus'></span></button></td>";
  if(ui_hasmoreoptions()) $html[] = "<td><button class='hollow' onclick=\"ui.async('ui_moreoptions', [])\"><span class='mdi mdi-menu'></span></button></td>";
  return implode('', $html);

}
function defaultcolumns(){

  $columns = salesinvoice_uicolumns();

  if($_SESSION['user']['id'] == 1010){
    $columns = [
      array('active'=>1, 'name'=>'date', 'text'=>'Tanggal', 'width'=>100, 'datatype'=>'date'),
      array('active'=>1, 'name'=>'code', 'text'=>'Kode', 'width'=>110),
      array('active'=>1, 'name'=>'customerdescription', 'text'=>'Pelanggan', 'width'=>200),
      array('active'=>0, 'name'=>'customeraddress', 'text'=>'Alamat', 'width'=>200),
      array('active'=>1, 'name'=>'inventorycode', 'text'=>'Kode Barang', 'width'=>80),
      array('active'=>1, 'name'=>'inventorydescription', 'text'=>'Barang', 'width'=>200),
      array('active'=>1, 'name'=>'qty', 'text'=>'Kts', 'width'=>60, 'datatype'=>'number'),
      array('active'=>1, 'name'=>'unitprice', 'text'=>'Harga Satuan', 'width'=>80, 'datatype'=>'money'),
      array('active'=>1, 'name'=>'unittotal', 'text'=>'Total Barang', 'width'=>80, 'datatype'=>'money'),
      array('active'=>0, 'name'=>'createdon', 'text'=>'Dibuat', 'width'=>120, 'datatype'=>'datetime'),
    ];
  }

  return $columns;

}
function defaultpresets(){

  $columns = defaultcolumns();

  $presets = [
    array(
      'text'=>'Semua Faktur',
      'columns'=>$columns,
      'viewtype'=>'list',
      'filters'=>[
      ],
      'sorts'=>array(
        array('name'=>'createdon', 'sorttype'=>'desc')
      )
    ),
    array(
      'text'=>'Bulan ini',
      'columns'=>$columns,
      'viewtype'=>'list',
      'filters'=>[
        [ 'name'=>'date', 'operator'=>'thismonth' ]
      ],
      'sorts'=>array(
        array('name'=>'createdon', 'sorttype'=>'desc')
      )
    ),
    array(
      'text'=>'Bulan lalu',
      'columns'=>$columns,
      'viewtype'=>'list',
      'filters'=>[
        [ 'name'=>'date', 'operator'=>'prevmonth' ]
      ],
      'sorts'=>array(
        array('name'=>'createdon', 'sorttype'=>'desc')
      )
    ),
  ];
  return $presets;

}
function defaultmodule(){

  $columns = defaultcolumns();

  $module = array(
    'title'=>'Sales Invoice',
    'columns'=>$columns,
    'presets'=>defaultpresets(),
    'presetidx'=>0,
    'quickfilterscolumns'=>array(
      array('text'=>'', 'value'=>'code|customerdescription|inventorydescription|inventorycode&contains&'),
      array('text'=>'ID:', 'value'=>'id&equals&'),
      array('text'=>'Inventory ID:', 'value'=>'inventoryid&equals&'),
      array('text'=>'Taxable:', 'value'=>'taxable&equals&'),
    ),
    'onrowdoubleclick'=>"ui.async('ui_salesinvoicedetail', [ this.dataset['id'] ], { waitel:this })"
  );
  return $module;

}
function m_griddoubleclick(){

  return "ui.async('ui_salesinvoiceopen', [ this.dataset['id'] ], {})";

}
function systemmodule(){

  $columns = salesinvoice_uicolumns();
  $module = array(
    'title'=>'salesinvoice',
    'columns'=>$columns,
    'presets'=>array(
      array(
        'text'=>'Cash Belum Bayar',
        'columns'=>$columns,
        'viewtype'=>'list',
        'filters'=>[
          [ 'name'=>'ispaid', 'operator'=>'=', 'value'=>0 ],
          [ 'name'=>'customerdescription', 'operator'=>'contains', 'value'=>'cash' ],
        ]
      ),
    ),
    'presetidx'=>0,
  );
  return $module;

}

function m_quickfilter_custom($param){

  $hint = ov('hint', $param);
  $items = [];
  $items[] = array('text'=>"Cari: $hint", 'value'=>"customerdescription|inventorydescription&contains&" . $hint);
  $items[] = array('text'=>"Kode: $hint", 'value'=>"code&contains&$hint");
  $items[] = array('text'=>"Kode Barang: $hint", 'value'=>"inventorycode&contains&$hint");
  $items[] = array('text'=>"Salesman: $hint", 'value'=>"salesmanname&contains&$hint");
  $items[] = array('text'=>"Hanya Pajak", 'value'=>"taxable&equals&1");
  $items[] = array('text'=>"Hanya Non Pajak", 'value'=>"taxable&equals&0");
  $items[] = array('text'=>"Belum Lunas", 'value'=>"ispaid&equals&0");
  $items[] = array('text'=>"Terkirim", 'value'=>"issent&equals&1");
  $items[] = array('text'=>"Belum Terkirim", 'value'=>"issent&!=&1");
  $items[] = array('text'=>"Hari Ini", 'value'=>"date&today&");
  $items[] = array('text'=>"Bulan Ini", 'value'=>"date&thismonth&");

  return $items;

}
function m_quickfilter_apply_custom($value, $operator = 'and'){

  global $module;
  $presetidx = $module['presetidx'];
  $module['presets'][$presetidx]['quickfilters'] = $value;
  $module['presets'][$presetidx]['quickfilters_operator'] = $operator;
  m_savestate($module);
  return m_load();

}
function m_quickfilter_items_from_value_custom($value){

  $arr = explode(',', $value);
  $results = [];
  foreach($arr as $obj){
    if(empty($obj)) continue;
    $text = $obj;
    if(strpos($text, 'customerdescription|inventorydescription&contains&') !== false) $text = str_replace('customerdescription|inventorydescription&contains&', '', $text);
    else if(strpos($text, 'inventorycode&contains&') !== false) $text = 'Kode Barang: ' . str_replace('inventorycode&contains&', '', $text);
    else if(strpos($text, 'code&contains&') !== false) $text = 'Kode: ' . str_replace('code&contains&', '', $text);
    else if(strpos($text, 'salesmanname&contains&') !== false) $text = 'Salesman: ' . str_replace('salesmanname&contains&', '', $text);
    else if(strpos($text, 'taxable&equals&1') !== false) $text = 'Hanya Pajak';
    else if(strpos($text, 'taxable&equals&0') !== false) $text = 'Hanya Non Pajak';
    else if(strpos($text, 'ispaid&equals&0') !== false) $text = 'Belum Lunas';
    else if(strpos($text, 'issent&equals&1') !== false) $text = 'Terkirim';
    else if(strpos($text, 'issent&!=&1') !== false) $text = 'Belum Terkirim';
    else if(strpos($text, 'date&today&') !== false) $text = 'Hari Ini';
    else if(strpos($text, 'date&thismonth&') !== false) $text = 'Bulan Ini';
    $results[] = array('text'=>$text, 'value'=>$obj);
  }
  return $results;

}

function grid_options($obj){

  global $deletable;
  $id = $obj['id'];
  $code = $obj['code'];

  $c = "<div>";
  $c .= "<span class='fa fa-bars' onclick=\"ui.async(event.altKey ? 'ui_salesinvoicemodify' : 'ui_salesinvoiceopen', [ $id ], { waitel:this })\"></span>";
  if($deletable) $c .= "<span class='fa fa-times' onclick=\"if(confirm('Hapus $code?')) ui.async('ui_salesinvoiceremove', [ $id ], { waitel:this })\"></span>";
  $c .= "</div>";
  return $c;

}
function grid_isactive($obj){
  $isactive = ov('isactive', $obj);
  return "<div class='align-center'>" . ($isactive ? "<span class='fa fa-check-circle color-green'></span>" : "<span class='fa fa-minus-circle color-red'></span>") . "</div>";
}
function grid_ispaid($obj){
  return "<div class='align-center'>" . ($obj['ispaid'] ? "<span class='fa fa-check-circle color-green'></span>" : "<span class='fa fa-minus-circle color-red'></span>") . "</div>";
}
function grid_taxable($obj){
  $taxable = ov('taxable', $obj);
  return "<div class='align-center'>" . ($taxable ? "<span class='fa fa-check-circle color-green'></span>" : "<span class='fa fa-minus-circle color-red'></span>") . "</div>";
}
function grid_isprint($obj){
  return "<div class='align-center'>" . ($obj['isprint'] ? "<span class='fa fa-check-circle color-green'></span>" : "<span class='fa fa-minus-circle color-red'></span>") . "</div>";
}
function grid_isreceipt($obj){
  return "<div class='align-center'>" . ($obj['isreceipt'] ? "<span class='fa fa-check-circle color-green'></span>" : "<span class='fa fa-minus-circle color-red'></span>") . "</div>";
}
function grid_issent($obj){

  $id = $obj['id'];
  $issent = $obj['issent'];
  $accesslevel = $_SESSION['user']['accesslevel'];
  $checked = $issent ? ' checked' : '';
  $readonly = $obj['issent'] && $accesslevel != 'ADMIN' ? ' disabled' : '';
  return "<div class='align-center'><input type='checkbox' $checked $readonly onchange=\"ui.async('salesinvoice_issent', [ $id, this.checked ])\"/></div>";

}
function grid_isgroup($obj){

  $isgroup = ov('isgroup', $obj);
  $taxable = ov('taxable', $obj);

  $c = "<div class='align-center'>";
  if($isgroup)
    $c .= "<span class='fa fa-check-circle color-green'></span>";
  else
    $c .= "<input type='checkbox' data-name='isgroup' data-taxable='$taxable' onchange=\"salesinvoicelist_groupopt_checkstate()\"/>";
  $c .= "</div>";
  return $c;
}
function grid_isreconciled($obj){

  $c = "<div class='align-center'>";
  if($obj['isreconciled']){
    $c .= "<span class='fa fa-check-circle color-green'></span>";
  }
  else{
    $c .= "<span class='fa fa-times-circle color-red'></span>";
  }
  $c .= "</div>";
  return $c;

}
function grid_journaloption($obj){

  if(!privilege_get('chartofaccount', 'list')) return;

  $id = $obj['id'];
  return "
  <div class='align-center'>
    <span class='padding5 fa fa-folder-open' onclick=\"ui.async('ui_salesinvoicedetail_journal', [ $id ], { waitel:this })\"></span>
  </div>
  ";

}

function ui_hasmoreoptions(){

  if(privilege_get('salesinvoice', 'download'))
    return true;
  return false;

}
function ui_moreoptions(){

  $html = [];
  $html[] = "<element exp='.modal'>";
  $html[] = "<div class='scrollable padding5'>";
  if(privilege_get('salesinvoice', 'download')){
    $html[] = "<button class='hover-blue width-full align-left' onclick=\"ui.async('ui_salesinvoiceexport', [])\"><span class='mdi mdi-download'></span><label>Download...</label></button>";
    if(userkeystoreget($_SESSION['user']['id'], 'privilege.salesinvoice_modifytaxcode')){
      $html[] = "<div class='height5'></div><button class='hover-blue width-full align-left' onclick=\"ui.async('ui_salesinvoicetaxdetail', [])\"><span class='mdi mdi-package'></span><label>Export E-Faktur</label></button>";
    }
  }
  $html[] = "</div>";
  $html[] = "</element>";
  $html[] = "<script>ui.modal_open(ui('.modal'), { closeable:true, width:300 });</script>";

  return implode('', $html);

}

function ui_salesinvoiceexport(){

  global $module;
  $preset = $module['presets'][$module['presetidx']];
  $columns = $preset['columns'];
  $sorts = $preset['sorts'];
  $groups = $preset['groups'];
  $quickfilters = ov('quickfilters', $preset);
  $filters = $preset['filters'];
  $filters = m_quickfilter_to_filters($filters, $quickfilters);

  if(privilege_get('inventory', 'costprice')){
    $columns['costprice'] = 't2.costprice';
    $columns['totalcostprice'] = 't2.totalcostprice';
  }

  if(is_array($groups) && count($groups) > 0){

    $items = datasource_group($columns, $sorts, $filters, $groups);
  }

  // Normal grid
  else{
    $items = datasource($columns, $sorts, $filters, null, $groups);
  }

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

  $filepath = 'usr/salesinvoice-' . date('j-M-Y') . '.xlsx';
  array_to_excel($items, $filepath);

  echo uijs("ui('#downloader').href = '$filepath';ui('#downloader').click();ui.modal_close(ui('.modal'));");

}

/**
 * Get salesinvoice journal vouchers
 * @param $salesinvoiceid
 * @return string
 * @throws Exception
 */
function ui_salesinvoicedetail_journal($salesinvoiceid){

  $jv = journalvoucherdetail('*', [ 'ref'=>'SI', 'refid'=>$salesinvoiceid ]);
  if(!$jv) return;

  $columns = [
    [ 'active'=>1, 'name'=>'coaname', 'text'=>'Nama Akun', 'width'=>200, 'nodittomark'=>1 ],
    [ 'active'=>1, 'name'=>'debitamount', 'text'=>'Debit', 'width'=>100, 'nodittomark'=>1, 'datatype'=>'money' ],
    [ 'active'=>1, 'name'=>'creditamount', 'text'=>'Kredit', 'width'=>100, 'nodittomark'=>1, 'datatype'=>'money' ],
  ];

  $c = "<element exp='.dialog'>";
  $c .= "
      <div class='box-dialog'>
        <div>
          <div>
            " . ui_gridhead(array('columns'=>$columns, 'gridexp'=>'#ih')) . "
            <div id='ih_scrollable' class='scrollable' style='height:200px'>" . ui_grid(array('id'=>'ih', 'columns'=>$columns, 'value'=>$jv['details'], 'scrollel'=>'#ih_scrollable')) . "</div>
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
        ui.dialog_open({ width:500 });
      ");
  return $c;

}

/* BEGIN DEPRECATED */
function grid_moveoption($obj){

  return '';

}
/* END DEPRECATED */

$deletable = privilege_get('salesinvoice', 'delete');
include 'rcfx/dashboard1.php';
$logo = systemvarget('logo');
?>
<script type="text/javascript" src="rcfx/js/salesinvoice.js"></script>
<img src="<?=$logo?>" style="display:none" />