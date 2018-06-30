<?php
if(!systemvarget('salesable') || privilege_get('salesinvoice', 'list') < 1){ include 'notavailable.php'; return; }

$modulename = 'salesinvoice_taxable';

require_once 'api/salesinvoice.php';

function customheadcolumns(){

  $html = [];
  if(privilege_get('salesinvoice', 'download')) $html[] = "<td><button id='exportbtn' class='hollow' onclick=\"ui.async('ui_salesinvoicetaxableexport', [])\"><span class='mdi mdi-download'></span></button></td>";
  return implode('', $html);

}
function datasource($columns = null, $sorts = null, $filters = null, $limits = null){

  return salesinvoicelist2($columns, $sorts, $filters, $limits);

}
function defaultcolumns(){

  $columns = [
    array('active'=>1, 'name'=>'id', 'text'=>'Id', 'width'=>40),
    array('active'=>1, 'name'=>'', 'text'=>'Id', 'width'=>40),
  ];
  return $columns;

}
function defaultpresets(){

  $columns = defaultcolumns();

  $presets = [
    array(
      'text'=>date('M Y'),
      'columns'=>$columns,
      'viewtype'=>'list',
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
    'title'=>'Faktur Penjualan Pajak',
    'columns'=>$columns,
    'presets'=>defaultpresets(),
    'presetidx'=>0,
    'quickfilterscolumns'=>array(
      array('text'=>'', 'value'=>'code|customerdescription&contains&'),
      array('text'=>'ID:', 'value'=>'id&equals&'),
    ),
    'onrowdoubleclick'=>""
  );
  return $module;

}

function ui_salesinvoicetaxableexport(){

  global $module;
  $preset = $module['presets'][$module['presetidx']];
  $columns = $preset['columns'];
  $sorts = $preset['sorts'];
  $quickfilters = ov('quickfilters', $preset);
  $filters = $preset['filters'];
  $filters = m_quickfilter_to_filters($filters, $quickfilters);

  $rows = salesinvoicelist2($columns, $sorts, $filters, null);

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

  if(is_array($rows)){
    foreach($rows as $obj){

      $items = $obj['items'];
      $customerdescription = $obj['customerdescription'];

      if(!$customerdescription || count($items) <= 0) continue;

      $customer_tax_registration_number = $obj['customer_tax_registration_number'];
      $customeraddress = $obj['customeraddress'];
      $index2 = str_pad($obj['index'], 1, '0', STR_PAD_LEFT);
      $index3 = str_pad($obj['index'], 3, '0', STR_PAD_LEFT);
      $tax_decimal = $obj['tax_decimal'];
      $tax_period = $obj['tax_period'];
      $tax_year = $obj['tax_year'];
      $tax_registration_number = $obj['tax_registration_number'];
      $tax_company_name = $obj['tax_company_name'];
      $tax_company_address = $obj['tax_company_address'];
      $code = $obj['code'];
      $date = date('d/m/Y', strtotime($obj['date']));
      $subtotal = $obj['subtotal'];
      $subtotal = number_format($subtotal, $tax_decimal);
      $subtotal_with_discount = $obj['subtotal_with_discount'];
      $subtotal_with_discount = number_format($subtotal_with_discount, 0, '', '');
      $taxamount = $obj['taxamount'];
      $taxamount = number_format($taxamount, 0, '', '');

      $month_names_in_id = [
        '-',
        'JAN', 'FEB', 'MAR', 'APR', 'MEI', 'JUN',
        'JUL', 'AGU', 'SEP', 'OKT', 'NOV', 'DES'
      ];

      $data[] = [
        'FK',
        1,
        '0',
        '181773541097',
        date('n', strtotime($obj['date'])),
        $tax_year,
        $date,
        '21940317059000',
        $customerdescription,
        trim(preg_replace('/\s+/', ' ', $customeraddress)),
        $subtotal_with_discount,
        $taxamount,
        '0',
        '',
        '0',
        '0',
        '0',
        '0',
        $code
      ];
      $data[] = [
        'F' . $month_names_in_id[date('n', strtotime($obj['date']))],
        $tax_company_name,
        trim(preg_replace('/\s+/', ' ', $tax_company_address)),
        'MORRIS HARMOKHO',
        '0',
        'JAKARTA BARAT',
        '0',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
      ];
      for($i = 0 ; $i < count($items) ; $i++){
        $item = $items[$i];
        $unitprice = $item['unitprice'];
        $qty = $item['qty'];
        $unittotal = $item['unittotal'];
        $unitdiscountamount = $item['unitdiscountamount'];
        $unittaxamount = $unittotal * .1;

        $qty = number_format($qty, 1, '.', '');
        $unitprice = number_format($unitprice, 1, '.', '');
        $unittotal = number_format($unittotal, 1, '.', '');
        $unitdiscountamount = number_format($unitdiscountamount, 1, '.', '');
        $unittaxamount = number_format($unittaxamount, 1, '.', '');

        $data[] = [
          'OF',
          $item['inventorycode'],
          trim(preg_replace('/\s+/', ' ', $item['inventorydescription'])),
          $unitprice,
          $qty,
          $unittotal,
          $unitdiscountamount,
          $unittotal,
          $unittaxamount,
          '0',
          '0.0',
          '',
          '',
          '',
          '',
          '',
          '',
          '',

        ];
      }

    }
  }

  $filepath = 'usr/salesinvoicetaxable-' . date('j-M-Y') . '.csv';
  array_to_csv($data, $filepath);
  echo uijs("ui('#downloader').href = '$filepath';ui('#downloader').click();");

}

include 'rcfx/dashboard1.php';
?>