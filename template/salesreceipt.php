<?php
$salesreceipt = salesreceiptdetail(null, array('id'=>$id));
$items = $salesreceipt['items'];

$customerid = $salesreceipt['customerid'];
$customer = customerdetail(null, [ 'id'=>$customerid ]);

$taxable = pmc("select count(*) from salesinvoice where `id` in (
  select t1.typeid from salesinvoicegroupitem t1, salesinvoicegroup t2 where t1.type = 'SI'
  and t1.salesinvoicegroupid = t2.id and salesreceiptid = ?
  ) and taxable = 1", [ $salesreceipt['id'] ]) > 0 ? true : false;

if($customer['override_sales']){
  $logo = '';
  $companyname = $customer['sales_companyname'];
  $addressline1 = $customer['sales_addressline1'];
  $addressline2 = $customer['sales_addressline2'];
  $addressline3 = $customer['sales_addressline3'];
}
else if($taxable){
  $logo = systemvarget('logo');
  $companyname = systemvarget('companyname');
  $addressline1 = systemvarget('addressline1');
  $addressline2 = systemvarget('addressline2');
  $addressline3 = systemvarget('addressline3');
}
else{
  $logo = '';
  $companyname = systemvarget('companyname');
  $companyname = systemvarget('companyname_nontax', $companyname);
  $addressline1 = systemvarget('salesinvoice_nontax_addressline1');
  $addressline2 = systemvarget('salesinvoice_nontax_addressline2');
  $addressline3 = systemvarget('salesinvoice_nontax_addressline3');
}

$beneficiarydetail = $salesreceipt['beneficiarydetail'];

$printermargin = systemvarget('printermargin');
$width = 210 - ($printermargin * 2);
$height = 297 - ($printermargin * 2);
$itemflexwidth = $width - (15 + 40 + 40);

$invoicefontsize = count($items) >= 20 ? 'font6pt' : (count($items) >= 10 ? 'font7pt' : 'font8pt');

?>
<style type="text/css" media="print">

  *{
    font-family: OpenSansRegular;
    font-size: 8pt;
  }
  body{
    margin: 0;
    padding: 0;
  }
  h1{
    font-size: 14pt;
    padding: 0;
    margin: 0;
  }

  .paper{
  }

  .form td, .form th{
    padding: .5mm;
    white-space: nowrap !important;
  }
  .form th{
    text-align: right;
  }

  td, th{
    white-space: nowrap;
    vertical-align: top;
  }
  label{
    display: inline-block;
  }

  .grid{
    width: 100%;
    border-collapse: collapse;
  }
  .grid th, .grid td{
    text-align: left;
    border: solid 1px #000;
  }

  .content{
    border-collapse: collapse;
    width: 100%;
  }
  .content tr{
    border: solid 1px #000;
  }

  .tbl-mid td{
    vertical-align: middle;
  }

</style>
<div class="" style="width:<?=$width?>mm;height:<?=$height?>mm;padding:0;margin:0;overflow:hidden;position: relative;margin-top:3mm">

  <table cellspacing="0" cellpadding="0" style="position: absolute;left:0mm;top:0mm">
    <tr>
      <?php if($logo && file_exists(base_path() . '/' . $logo)){ ?>
      <td rowspan="5" width="60mm" style="vertical-align: top"><img width="60mm" height="60mm" src="<?=$logo?>"/></td>
      <?php } ?>
      <td>&nbsp;</td>
      <td width="100%" style="white-space: nowrap"><h1 style="white-space: nowrap"><?=$companyname?></h1></td>
    </tr>
    <tr><td>&nbsp;</td><td><?=$addressline1?></td></tr>
    <tr><td>&nbsp;</td><td><?=$addressline2?></td></tr>
    <tr><td>&nbsp;</td><td><?=$addressline3?></td></tr>
  </table>

  <div style="position: absolute;top:10mm;width:100%;text-align:center">
    <h1>KWITANSI</h1>
    <div style="border-bottom:solid 1px #000;width:25mm;display: inline-block"></div><br />
    <i>RECEIPT</i>
  </div>

  <table class="form" cellspacing="1" style="position: absolute;top:0;right:0">
    <tr><td><div style="width:10mm">Nomor</div></td><td><div style="width:5mm;text-align: center">:</div></td><td><div style="width:30mm"><?=$salesreceipt['code']?></div></td></tr>
    <tr><td><div>Tanggal</div></td><td><div style="width:5mm;text-align: center">:</div></td><td><div><?=date('j M Y', strtotime($salesreceipt['date']))?></div></td></tr>
  </table>


  <div style="position: absolute;left:2mm;right:2mm;top:23mm;border:solid 1px #000">


    <table cellspacing="0" cellpadding="4mm" style="border-collapse: collapse">
      <tr>
        <td style="text-align: center;width:40mm">
          <label>Sudah Terima Dari</label>
          <div style="border-bottom:solid 1px #000;width:30mm;display: inline-block"></div><br />
          <i>Received From</i>
        </td>
        <td style="width:8mm;text-align: center">:</td>
        <td><?=strtoupper($salesreceipt['customerdescription'])?></td>
      </tr>
      <tr style="border-bottom: solid 1px #000">
        <td style="text-align: center;width:40mm">
          <label>Banyaknya Uang</label>
          <div style="border-bottom:solid 1px #000;width:30mm;display: inline-block"></div><br />
          <i>Amount</i>
        </td>
        <td style="width:8mm;text-align: center">:</td>
        <td># <?=ucwords(terbilang(round($salesreceipt['total'])))?></td>
      </tr>
      <tr>
        <td style="text-align: center;width:40mm">
          <label>Untuk Pembayaran</label>
          <div style="border-bottom:solid 1px #000;width:30mm;display: inline-block"></div><br />
          <i>Amount</i>
        </td>
        <td style="width:8mm;text-align: center">:</td>
        <td>INVOICE INDOSPS</td>
      </tr>
      <tr style="border-bottom: solid 1px #000">
        <td colspan="3">

          <table cellspacing="1mm" class="<?=$invoicefontsize?>">
            <?php
            for($j = 0 ; $j < count($items) ; $j++){
              $item = $items[$j];
              ?>
              <tr>
                <td style="width:15mm;text-align: right"><?=$j + 1?>.</td>
                <td style="width:40mm"><?=$item['code']?></td>
                <td style="width:<?=$itemflexwidth?>mm"><?=strtoupper($item['description'])?></td>
                <td style="width:30mm;text-align: right;"><?=number_format($item['total'])?></td>
                <td style="width:10mm"></td>
              </tr>
            <?php } ?>
          </table>

        </td>
      </tr>
    </table>


    <table cellspacing="0" cellpadding="0" style="margin: 2mm">
      <tr>
        <td colspan="2" style="text-align: right">

          <div style="">
            <table cellspacing="0" cellpadding="0">
              <tr>
                <td style="width:110mm"></td>
                <th style="width:30mm;text-align: right;white-space:nowrap;font-size:12pt;white-space: nowrap"><h2>Grand Total</h2></th>
                <td style="width:40mm;text-align: right;font-size:12pt;white-space: nowrap"><h2><?=number_format($salesreceipt['total'])?></h2></td>
                <td style="width:8mm"></td>
              </tr>
            </table>
          </div>

        </td>
      </tr>
      <tr>
        <td style="width:100%;vertical-align: bottom">

          <div style="">
            <label>Beneficiary Detail :</label>
            <p style="font-size:1.3em;font-weight:bold;font-family:OpenSansBold"><?=nl2br($beneficiarydetail)?></p>
            <br />
            <i class="small">This receipt will consider valid after Bilyet Giro / Cheque can be cleared.</i>
          </div>

        </td>
        <td style="width: 40mm;text-align: center;vertical-align:bottom">

          <div style="height: 10mm"></div>
          <div style="">
            <div style="width:40mm;border-bottom: solid 1px #000;text-align: center">
              Account Receivable
            </div>
            <div style="width:40mm;text-align: center"><?=systemvarget('accountreceivable')?></div>
          </div>

        </td>
      </tr>
    </table>

  </div>







</div>