<?php
if(privilege_get('company', 'list') < 1){ include 'notavailable.php'; return; }

require_once 'api/chartofaccount.php';
require_once 'api/system.php';

$chartofaccounts = array_cast(chartofaccountlist(), array('text'=>'name', 'value'=>'id'));
array_splice($chartofaccounts, 0, 0, [[ 'text'=>'Disabled', 'value'=>'' ]]);

ui_async();
?>
<div class="padding10">

  <table cellspacing="10" class="form">
    <tr>
      <th class="width240"><h5 class="padding10">Perusahaan</h5></th>
      <td></td>
    </tr>
    <tr>
      <th><label>Nama Perusahaan</label></th>
      <td>
        <?=ui_textbox([ 'width'=>'300px', 'value'=>systemvarget('companyname'), 'onchange'=>"ui.async('systemvarset', [ 'companyname', value ])" ])?>
      </td>
    </tr>
    <tr>
      <th><label>Nama Perusahaan Non Pajak</label></th>
      <td>
        <?=ui_textbox([ 'width'=>'300px', 'value'=>systemvarget('companyname_nontax'), 'onchange'=>"ui.async('systemvarset', [ 'companyname_nontax', value ])" ])?>
      </td>
    </tr>
  </table>

  <div class="height10" style="border-top: solid 1px #eee"></div>

  <table cellspacing="10" class="form">
    <tr>
      <th class="width240"><h5 class="padding10">Pelanggan</h5></th>
      <td></td>
    </tr>
    <tr>
      <th><label>Maksimal Lama Piutang</label></th>
      <td>
        <?=ui_textbox([ 'width'=>'50px', 'value'=>systemvarget('customer_creditterm'), 'onchange'=>"ui.async('systemvarset', [ 'customer_creditterm', value ])" ])?> Hari
      </td>
    </tr>
  </table>

  <div class="height10" style="border-top: solid 1px #eee"></div>

  <table cellspacing="10" class="form">
    <tr>
      <th class="width240"><h5 class="padding10">Penjualan</h5></th>
      <td>
        <?=ui_checkbox([ 'value'=>systemvarget('salesable'), 'onchange'=>"ui.async('systemvarset', [ 'salesable', value ])" ])?>
      </td>
    </tr>
    <tr>
      <th><label>Format Kode Invoice (Pajak)</label></th>
      <td>
        <?=ui_textbox([
          'value'=>systemvarget('salesinvoice_tax_code'),
          'onchange'=>"ui.async('systemvarset', [ 'salesinvoice_tax_code', value ])",
        ])?>
        <small>{INDEX} | {YEAR}</small>
      </td>
    </tr>
    <tr>
      <th><label>Format Kode Invoice (Non Pajak)</label></th>
      <td>
        <?=ui_textbox([
          'value'=>systemvarget('salesinvoice_nontax_code'),
          'onchange'=>"ui.async('systemvarset', [ 'salesinvoice_nontax_code', value ])",
        ])?>
      </td>
    </tr>
    <tr>
      <th><label>Metode Perhitungan Modal</label></th>
      <td><?=ui_dropdown([ 'value'=>systemvarget('costprice_method'), 'onchange'=>"ui.async('systemvarset', [ 'costprice_method', value ])", 'items'=>[ [ 'text'=>'Average', 'value'=>'average' ], [ 'text'=>'FIFO', 'value'=>'fifo' ] ] ])?></td>
    </tr>
    <tr>
      <th><label>Pakai Harga Jual Terendah</label></th>
      <td>
        <?=ui_checkbox([ 'value'=>systemvarget('uselowestprice'), 'onchange'=>"ui.async('systemvarset', [ 'uselowestprice', value ])" ])?>
      </td>
    </tr>
    <tr>
      <th><label>Harga Jual Terendah (%)</label></th>
      <td>
        <?=ui_textbox([ 'width'=>'60px', 'value'=>systemvarget('salesminimummargin'), 'onchange'=>"ui.async('systemvarset', [ 'salesminimummargin', value ])" ])?>
        <label>% dari harga modal</label>
      </td>
    </tr>
    <tr>
      <th><label>Kuantitas harus ada</label></th>
      <td>
        <?=ui_checkbox([ 'value'=>systemvarget('use_qty'), 'onchange'=>"ui.async('systemvarset', [ 'use_qty', value ])" ])?>
      </td>
    </tr>
    <tr>
      <th><label>Grup barang pada faktur penjualan</label></th>
      <td>
        <?=ui_checkbox([ 'value'=>systemvarget('salesinvoice_item_grouping'), 'onchange'=>"ui.async('systemvarset', [ 'salesinvoice_item_grouping', value ])" ])?>
      </td>
    </tr>
    <tr>
      <th><label>Print Alamat Pajak</label></th>
      <td>
        <?=ui_textbox([ 'width'=>'360px', 'value'=>systemvarget('addressline1'), 'onchange'=>"ui.async('systemvarset', [ 'addressline1', value ])" ])?>
        <div class="height10"></div>
        <?=ui_textbox([ 'width'=>'360px', 'value'=>systemvarget('addressline2'), 'onchange'=>"ui.async('systemvarset', [ 'addressline2', value ])" ])?>
        <div class="height10"></div>
        <?=ui_textbox([ 'width'=>'360px', 'value'=>systemvarget('addressline3'), 'onchange'=>"ui.async('systemvarset', [ 'addressline3', value ])" ])?>
      </td>
    </tr>
    <tr>
      <th><label>Print Alamat Non Pajak</label></th>
      <td>
        <?=ui_textbox([ 'width'=>'360px', 'value'=>systemvarget('salesinvoice_nontax_addressline1'), 'onchange'=>"ui.async('systemvarset', [ 'salesinvoice_nontax_addressline1', value ])" ])?>
        <div class="height10"></div>
        <?=ui_textbox([ 'width'=>'360px', 'value'=>systemvarget('salesinvoice_nontax_addressline2'), 'onchange'=>"ui.async('systemvarset', [ 'salesinvoice_nontax_addressline2', value ])" ])?>
        <div class="height10"></div>
        <?=ui_textbox([ 'width'=>'360px', 'value'=>systemvarget('salesinvoice_nontax_addressline3'), 'onchange'=>"ui.async('systemvarset', [ 'salesinvoice_nontax_addressline3', value ])" ])?>
      </td>
    </tr>
  </table>

  <div class="height10" style="border-top: solid 1px #eee"></div>

  <table cellspacing="10" class="form">
    <tr>
      <th class="width240"><h5 class="padding10">Pembelian</h5></th>
      <td>
        <?=ui_checkbox([ 'value'=>systemvarget('purchaseable'), 'onchange'=>"ui.async('systemvarset', [ 'purchaseable', value ])" ])?>
      </td>
    </tr>
  </table>

  <div></div>

  <table cellspacing="10" class="form">
    <tr>
      <th class="width240"><label>Format Kode Faktur Pembelian (Pajak)</label></th>
      <td>
        <?=ui_textbox([
          'value'=>systemvarget('purchaseinvoice_tax_code'),
          'onchange'=>"ui.async('systemvarset', [ 'purchaseinvoice_tax_code', value ])",
        ])?>
        <small>{INDEX} | {YEAR}</small>
      </td>
    </tr>
    <tr>
      <th><label>Format Kode Faktur Pembelian (Non Pajak)</label></th>
      <td>
        <?=ui_textbox([
          'value'=>systemvarget('purchaseinvoice_nontax_code'),
          'onchange'=>"ui.async('systemvarset', [ 'purchaseinvoice_nontax_code', value ])",
        ])?>
      </td>
    </tr>
    <tr>
      <th class="width240"><label>Print Nama Perusahaan</label></th>
      <td>
        <?=ui_textbox([
          'value'=>systemvarget('purchase_company_name'),
          'width'=>'400px',
          'onchange'=>"ui.async('systemvarset', [ 'purchase_company_name', value ])",
        ])?>
      </td>
    </tr>
    <tr>
      <th class="width240"><label>Print Alamat</label></th>
      <td>
        <?=ui_textbox([
          'value'=>systemvarget('purchase_addressline1'),
          'width'=>'400px',
          'onchange'=>"ui.async('systemvarset', [ 'purchase_addressline1', value ])",
        ])?>
        <div class="height10"></div>
        <?=ui_textbox([
          'value'=>systemvarget('purchase_addressline2'),
          'width'=>'400px',
          'onchange'=>"ui.async('systemvarset', [ 'purchase_addressline2', value ])",
        ])?>
      </td>
    </tr>
    <tr>
      <th><label>Grup barang pada faktur pembelian</label></th>
      <td>
        <?=ui_checkbox([ 'value'=>systemvarget('purchaseinvoice_item_grouping'), 'onchange'=>"ui.async('systemvarset', [ 'purchaseinvoice_item_grouping', value ])" ])?>
      </td>
    </tr>
    <tr>
      <th class="width240"><label>Akun Uang Muka Pembelian</label></th>
      <td>
        <?=ui_dropdown([
          'items'=>$chartofaccounts,
          'value'=>systemvarget('purchaseinvoice_downpaymentaccountid'),
          'width'=>'200px',
          'onchange'=>"ui.async('systemvarset', [ 'purchaseinvoice_downpaymentaccountid', value ])",
        ])?>
      </td>
    </tr>
    <tr>
      <th class="width240"><label>Akun Pembelian</label></th>
      <td>
        <?=ui_dropdown([
          'items'=>$chartofaccounts,
          'value'=>systemvarget('purchaseinvoice_accountid'),
          'width'=>'200px',
          'onchange'=>"ui.async('systemvarset', [ 'purchaseinvoice_accountid', value ])",
        ])?>
      </td>
    </tr>
    <tr>
      <th class="width240"><label>Akun PPn</label></th>
      <td>
        <?=ui_dropdown([
          'items'=>$chartofaccounts,
          'value'=>systemvarget('purchaseinvoice_taxaccountid'),
          'width'=>'200px',
          'onchange'=>"ui.async('systemvarset', [ 'purchaseinvoice_taxaccountid', value ])",
        ])?>
      </td>
    </tr>
    <tr>
      <th class="width240"><label>Akun PPh</label></th>
      <td>
        <?=ui_dropdown([
          'items'=>$chartofaccounts,
          'value'=>systemvarget('purchaseinvoice_pphaccountid'),
          'width'=>'200px',
          'onchange'=>"ui.async('systemvarset', [ 'purchaseinvoice_pphaccountid', value ])",
        ])?>
      </td>
    </tr>
    <tr>
      <th class="width240"><label>Akun KSO</label></th>
      <td>
        <?=ui_dropdown([
          'items'=>$chartofaccounts,
            'value'=>systemvarget('purchaseinvoice_ksoaccountid'),
          'width'=>'200px',
          'onchange'=>"ui.async('systemvarset', [ 'purchaseinvoice_ksoaccountid', value ])",
        ])?>
      </td>
    </tr>
    <tr>
      <th class="width240"><label>Akun SKI</label></th>
      <td>
        <?=ui_dropdown([
          'items'=>$chartofaccounts,
          'value'=>systemvarget('purchaseinvoice_skiaccountid'),
          'width'=>'200px',
          'onchange'=>"ui.async('systemvarset', [ 'purchaseinvoice_skiaccountid', value ])",
        ])?>
      </td>
    </tr>
    <tr>
      <th class="width240"><label>Akun Clearance Fee</label></th>
      <td>
        <?=ui_dropdown([
          'items'=>$chartofaccounts,
          'value'=>systemvarget('purchaseinvoice_clearance_fee_accountid'),
          'width'=>'200px',
          'onchange'=>"ui.async('systemvarset', [ 'purchaseinvoice_clearance_fee_accountid', value ])",
        ])?>
      </td>
    </tr>
    <tr>
      <th class="width240"><label>Akun Bea Masuk</label></th>
      <td>
        <?=ui_dropdown([
          'items'=>$chartofaccounts,
          'value'=>systemvarget('purchaseinvoice_import_cost_accountid'),
          'width'=>'200px',
          'onchange'=>"ui.async('systemvarset', [ 'purchaseinvoice_import_cost_accountid', value ])",
        ])?>
      </td>
    </tr>
    <tr>
      <th class="width240"><label>Akun Handling Fee</label></th>
      <td>
        <?=ui_dropdown([
          'items'=>$chartofaccounts,
          'value'=>systemvarget('purchaseinvoice_handlingfeeaccountid'),
          'width'=>'200px',
          'onchange'=>"ui.async('systemvarset', [ 'purchaseinvoice_handlingfeeaccountid', value ])",
        ])?>
      </td>
    </tr>
  </table>

  <div class="height10" style="border-top: solid 1px #eee"></div>

  <table cellspacing="10" class="form">
    <tr>
      <th class="width240"><h5 class="padding10">Print</h5></th>
      <td></td>
    </tr>
    <tr>
      <th><label>Alamat</label></th>
      <td>
        <?=ui_textbox([ 'width'=>'360px', 'value'=>systemvarget('addressline1'), 'onchange'=>"ui.async('systemvarset', [ 'addressline1', value ])" ])?>
        <div class="height10"></div>
        <?=ui_textbox([ 'width'=>'360px', 'value'=>systemvarget('addressline2'), 'onchange'=>"ui.async('systemvarset', [ 'addressline2', value ])" ])?>
        <div class="height10"></div>
        <?=ui_textbox([ 'width'=>'360px', 'value'=>systemvarget('addressline3'), 'onchange'=>"ui.async('systemvarset', [ 'addressline3', value ])" ])?>
        <div class="height10"></div>
        <?=ui_textbox([ 'width'=>'360px', 'value'=>systemvarget('addressline4'), 'onchange'=>"ui.async('systemvarset', [ 'addressline4', value ])" ])?>
      </td>
    </tr>
    <tr>
      <th><label>Account Receivable</label></th>
      <td>
        <?=ui_textbox([ 'width'=>'200px', 'value'=>systemvarget('accountreceivable'), 'onchange'=>"ui.async('systemvarset', [ 'accountreceivable', value ])" ])?>
      </td>
    </tr>
    <tr>
      <th><label>Beneficiary Detail</label></th>
      <td>
        <?=ui_textbox([ 'width'=>'200px', 'value'=>systemvarget('beneficiarydetail'), 'onchange'=>"ui.async('systemvarset', [ 'beneficiarydetail', value ])" ])?>
      </td>
    </tr>
  </table>

  <div class="height10" style="border-top: solid 1px #eee"></div>

  <table cellspacing="10" class="form">
    <tr>
      <th class="width240"><h5 class="padding10">Pajak</h5></th>
      <td></td>
    </tr>
    <tr>
      <th><label>Masa Pajak</label></th>
      <td>
        <?=ui_textbox([ 'width'=>'60px', 'value'=>systemvarget('tax_period'), 'onchange'=>"ui.async('systemvarset', [ 'tax_period', value ])" ])?>
        <label class="padding10">Bulan</label>
      </td>
    </tr>
    <tr>
      <th><label>NPWP</label></th>
      <td>
        <?=ui_textbox([ 'width'=>'360px', 'value'=>systemvarget('tax_registration_number'), 'onchange'=>"ui.async('systemvarset', [ 'tax_registration_number', value ])" ])?>
      </td>
    </tr>
    <tr>
      <th><label>Nama Perusahaan</label></th>
      <td>
        <?=ui_textbox([ 'width'=>'360px', 'value'=>systemvarget('tax_company_name'), 'onchange'=>"ui.async('systemvarset', [ 'tax_company_name', value ])" ])?>
      </td>
    </tr>
    <tr>
      <th><label>Alamat Perusahaan</label></th>
      <td>
        <?=ui_textbox([ 'width'=>'360px', 'value'=>systemvarget('tax_company_address'), 'onchange'=>"ui.async('systemvarset', [ 'tax_company_address', value ])" ])?>
      </td>
    </tr>
    <tr>
      <th><label>Jumlah Angka Desimal</label></th>
      <td>
        <?=ui_textbox([ 'width'=>'60px', 'value'=>systemvarget('tax_decimal'), 'onchange'=>"ui.async('systemvarset', [ 'tax_decimal', value ])" ])?>
      </td>
    </tr>
  </table>

</div>