<?php
if(!systemvarget('purchaseable') || privilege_get('purchaseinvoice', 'list') < 1){ include 'notavailable.php'; return; }

$modulename = 'purchaseinvoice';
$groupable = true;

require_once 'api/purchaseinvoice.php';
require_once 'ui/purchaseinvoice.php';

function defaultmodule(){

  $columns = purchaseinvoice_uicolumns();

  // Remove moved column on next database
  if($_SESSION['dbschema'] == 'indosps2'){
    $index = -1;
    for($i = 0 ; $i < count($columns) ; $i++)
      if($columns[$i]['name'] == 'moved'){
        $index = $i;
        break;
      }
    array_splice($columns, $index, 1);
  }

  $module = array(
      'title'=>'purchaseinvoice',
      'columns'=>$columns,
      'presets'=>array(
          array(
            'text'=>'Detil',
            'columns'=>$columns,
            'sorts'=>array(
              array('name'=>'createdon', 'sorttype'=>'desc')
            ),
            'viewtype'=>'list'
          ),
      ),
      'presetidx'=>0,
      'quickfilterscolumns'=>array(
        array('text'=>'', 'value'=>'code|description|supplierdescription|paymentaccountname|inventorycode|inventorydescription&contains&'),
        array('text'=>'Lunas:', 'value'=>'ispaid&bool&'),
        array('text'=>'ID:', 'value'=>'id&equals&'),
      )
  );
  return $module;

}

function datasource($columns = null, $sorts = null, $filters = null, $limits = null, $groups = null){

  if(!is_array($filters)) $filters = [];

  $privilege_purchaseinvoicetype = userkeystoreget($_SESSION['user']['id'], 'privilege.purchaseinvoicetype', 'non-tax');
  switch($privilege_purchaseinvoicetype){
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

  return purchaseinvoicelist($columns, $sorts, $filters, $limits, $groups);

}

function customheadcolumns(){

  $html = [];
  if(privilege_get('purchaseinvoice', 'new')){
    $html[] = "<td><button class='blue new-btn' onclick=\"ui.async('ui_purchaseinvoicenew', [ 0 ], { waitel:this })\"><span class='mdi mdi-plus'></span></button></td>";
    //$html[] = "<td><button class='mint new-btn new-btn-tax' onclick=\"ui.async('ui_purchaseinvoicenew', [ 1 ], { waitel:this })\"><span class='mdi mdi-plus'></span></button></td>";
  }
  $html[] = "<td><button class='hollow' onclick=\"ui.async('ui_purchaseinvoicemoreoptions', [])\"><span class='mdi mdi-menu'></span></button></td>";
  return implode('', $html);

}

function purchaseinvoicelist_options($obj){

  global $deletable;
  $id = $obj['id'];
  $code = $obj['code'];

  $c = "<div>";
  $c .= "<span class='fa fa-bars' onclick=\"ui.async(event.altKey ? 'ui_purchaseinvoicemodify' : 'ui_purchaseinvoiceopen', [ $id ], { waitel:this })\"></span>";
  if($deletable) $c .= "<span class='fa fa-times' onclick=\"if(confirm('Hapus $code?')) ui.async('ui_purchaseinvoiceremove', [ $id ], { waitel:this })\"></span>";
  $c .= "</div>";
  return $c;

}
function ui_purchaseinvoicemoreoptions(){

  $html = [];
  $html[] = "<element exp='.modal'>";
  $html[] = "<div class='scrollable padding5'>";
  if(privilege_get('salesinvoice', 'download')){
    if(privilege_get('purchaseinvoice', 'download')) $html[] = "<button class='hover-blue width-full align-left' onclick=\"ui.async('ui_purchaseinvoiceexport', [ null ], { waitel:this })\"><span class='mdi mdi-download'></span><label>Download...</label></button></td>";
    $html[] = "<div class='height5'></div><button class='hover-blue width-full align-left' onclick=\"ui.async('ui_purchaseinvoicetaxexport', [])\"><span class='mdi mdi-cloud-download'></span><label>Download Laporan Pajak</label></button>";
  }
  $html[] = "</div>";
  $html[] = "</element>";
  $html[] = "<script>ui.modal_open(ui('.modal'), { closeable:true, width:300 });</script>";

  return implode('', $html);

}
function ui_purchaseinvoicetaxexport(){

  global $module;
  $preset = $module['presets'][$module['presetidx']];
  $columns = $preset['columns'];
  $sorts = $preset['sorts'];
  $quickfilters = ov('quickfilters', $preset);
  $filters = $preset['filters'];
  $filters = m_quickfilter_to_filters($filters, $quickfilters);
  $filters[] = [
    'name'=>'taxable',
    'operator'=>'=',
    'value'=>'1',
  ];
  $rows = purchaseinvoicelist('*', $sorts, $filters, null);
  $data = [];

  $data[] = [
    'FM',
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
    'IS_CREDITABLE'
  ];

  if(is_array($rows)){
    $counter = 0;
    foreach($rows as $obj){

      $counter++;
      $supplierdescription = $obj['supplierdescription'];
      $supplieraddress = $obj['supplieraddress'];
      $tax_code = $obj['tax_code'];

      $tax_decimal = $obj['tax_decimal'];
      $tax_year = date('Y', strtotime($obj['date']));
      $code = $obj['code'];
      $date = date('d/m/Y', strtotime($obj['date']));
      $subtotal = $obj['subtotal'];
      $subtotal = number_format($subtotal, $tax_decimal);
      $subtotal_with_discount = $obj['subtotal_with_discount'];
      $subtotal_with_discount = number_format($subtotal_with_discount, 0, '', '');
      $taxamount = $obj['taxamount'];
      $taxamount = number_format($taxamount, 0, '', '');
      $supplier_tax_registration_number = '123'; // $obj['supplier_tax_registration_number']

      $month_names_in_id = [
        '-',
        'JAN', 'FEB', 'MAR', 'APR', 'MEI', 'JUN',
        'JUL', 'AGU', 'SEP', 'OKT', 'NOV', 'DES'
      ];

      $data[] = [
        'FM',
        '1',
        '0',
        str_pad($tax_code, 13, '0', STR_PAD_LEFT),
        date('n', strtotime($obj['date'])),
        $tax_year,
        $date,
        str_pad($supplier_tax_registration_number, 15, '0', STR_PAD_LEFT),
        trim(preg_replace('/\s+/', ' ', $supplierdescription)),
        trim(preg_replace('/\s+/', ' ', $supplieraddress)),
        $subtotal_with_discount,
        $taxamount,
        '0',
        '1'
      ];
//      $data[] = [
//        'F' . $month_names_in_id[date('n', strtotime($obj['date']))],
//        $tax_company_name,
//        trim(preg_replace('/\s+/', ' ', $tax_company_address)),
//        'MORRIS HARMOKHO',
//        '0',
//        'JAKARTA BARAT',
//        '0',
//        '',
//        '',
//        '',
//        '',
//        '',
//        '',
//        '',
//        '',
//      ];

    }
  }

  $filepath = 'usr/salesinvoicetax-' . date('j-M-Y') . '.csv';
  array_to_csv($data, $filepath);
  echo uijs("ui('#downloader').href = '$filepath';ui('#downloader').click();");

  return uijs("ui.modal_close(ui('.modal'))");

}
function purchaseinvoicelist_ispaid($obj){

  return "<div class='align-center'>" . ($obj['ispaid'] ? "<span class='fa fa-check-circle color-green'></span>" : "<span class='fa fa-minus-circle color-red'></span>") . "</div>";

}
function grid_moveoption($obj){

  $c = "<div class='align-center purchaseinvoicemove'>";
  $c .= $obj['moved'] ? "<span class='fa fa-check color-green'></span>" : "<span class='fa fa-plus color-blue' onclick=\"if(confirm('Pindah transaksi ini?')) ui.async('ui_purchaseinvoicemove', [ $obj[id] ])\"></span>";
  $c .= "</div>";
  return $c;

}
function grid_journaloption($obj){

  if(!privilege_get('chartofaccount', 'list')) return;

  $id = $obj['id'];
  return "
  <div class='align-center'>
    <span class='padding5 fa fa-folder-open' onclick=\"ui.async('ui_purchaseinvoicedetail_journal', [ $id ], { waitel:this })\"></span>
  </div>
  ";

}
function grid_inventorybalanceoption($obj){

  if(!privilege_get('inventory', 'list')) return;

  $id = $obj['id'];
  return "
  <div class='align-center'>
    <span class='padding5 fa fa-folder-open' onclick=\"ui.async('ui_purchaseinvoicedetail_inventorybalance', [ $id ], { waitel:this })\"></span>
  </div>
  ";

}
function m_griddoubleclick(){

  return "ui.async('ui_purchaseinvoiceopen', [ this.dataset['id'] ], {})";

}
function purchaseinvoicelist_supplierdescription($obj){

  $supplierdescription = $obj['supplierdescription'];
  $supplierid = $obj['supplierid'];
  $c = "<label class='text-clickable' onclick=\"ui.async('ui_supplierdetail', [ $supplierid, 'read' ], { waitel:this })\">" . $supplierdescription . "</label>";
  return $c;

}
function purchaseinvoicelist_inventorydescription($obj){

  $inventorydescription = $obj['inventorydescription'];
  $inventoryid = $obj['inventoryid'];
  $c = "<label class='text-clickable' onclick=\"ui.async('ui_inventorydetail', [ $inventoryid, 'read' ], { waitel:this })\">" . $inventorydescription . "</label>";
  return $c;

}

/**
 * Get purchaseinvoice journal vouchers
 * @param $purchaseinvoiceid
 * @return string
 * @throws Exception
 */
function ui_purchaseinvoicedetail_journal($purchaseinvoiceid){

  $purchaseinvoice = purchaseinvoicedetail(null, [ 'id'=>$purchaseinvoiceid ]);
  $purchaseorderid = $purchaseinvoice['purchaseorderid'];

  $jv = journalvoucherlist('*', null, [
    [ 'type'=>'(' ],
    [ 'type'=>'(' ],
    [ 'name'=>'ref', 'operator'=>'=', 'value'=>'PI' ],
    [ 'name'=>'refid', 'operator'=>'=', 'value'=>$purchaseinvoiceid ],
    [ 'type'=>')' ],
    [ 'type'=>'OR' ],
    [ 'type'=>'(' ],
    [ 'name'=>'ref', 'operator'=>'=', 'value'=>'PO' ],
    [ 'name'=>'refid', 'operator'=>'=', 'value'=>$purchaseorderid ],
    [ 'type'=>')' ],
    [ 'type'=>')' ],
  ]);
  if(!$jv) exc("ERROR: Tidak ada jurnal untuk faktur ini, tolong hubungi administrator.");

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

function ui_purchaseinvoicedetail_inventorybalance($purchaseinvoiceid){

  $mutation = inventorybalancelist('*', [ 'ref'=>'PI', 'refid'=>$purchaseinvoiceid ]);
  if(!$mutation) return;

  $columns = [
    [ 'active'=>1, 'name'=>'inventorycode', 'text'=>'Kode', 'width'=>60, 'nodittomark'=>1 ],
    [ 'active'=>1, 'name'=>'inventorydescription', 'text'=>'Nama Barang', 'width'=>150, 'nodittomark'=>1 ],
    [ 'active'=>1, 'name'=>'in', 'text'=>'Masuk', 'width'=>50, 'nodittomark'=>1, 'datatype'=>'number' ],
    [ 'active'=>1, 'name'=>'out', 'text'=>'Keluar', 'width'=>50, 'nodittomark'=>1, 'datatype'=>'number' ],
    [ 'active'=>1, 'name'=>'unitamount', 'text'=>'Harga Satuan', 'width'=>100, 'nodittomark'=>1, 'datatype'=>'money' ],
  ];

  $c = "<element exp='.dialog'>";
  $c .= "
      <div class='box-dialog'>
        <div>
          <div>
            " . ui_gridhead(array('columns'=>$columns, 'gridexp'=>'#ih')) . "
            <div id='ih_scrollable' class='scrollable' style='height:200px'>" . ui_grid(array('id'=>'ih', 'columns'=>$columns, 'value'=>$mutation, 'scrollel'=>'#ih_scrollable')) . "</div>
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

$deletable = privilege_get('purchaseinvoice', 'delete');
include 'rcfx/dashboard1.php';
?>