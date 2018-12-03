<?php

$customerid = $salesinvoicegroup['customerid'];
$customer = customerdetail(null, [ 'id'=>$customerid ]);

$taxable = false;
$salesinvoices = $salesinvoicegroup['items'];
foreach($salesinvoices as $item){
  $type = ov('type', $item, 1);
  $typeid = ov('typeid', $item);
  $salesinvoice = pmr("SELECT customerid, customerdescription, taxable FROM salesinvoice WHERE `id` = ?", [ $typeid ]);
  if(!$salesinvoice) throw new Exception('Faktur yang dimasukkan salah.');

  if($salesinvoice['taxable']) $taxable = true;
}

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

$printermargin = systemvarget('printermargin');
$width = 210 - ($printermargin * 2);
$height = 297 - ($printermargin * 2);
$itemflexwidth = $width - (10 + 36 + 40);

$rowsperpage = 40;
$salesinvoices = $salesinvoicegroup['items'];

$pages = $totalpages = ceil(count($salesinvoices) / $rowsperpage);
if($page > 0){ $page = $page - 1; $pages = $page + 1; }
else $page = 0;
?>

<?php for($i = $page ; $i < $pages ; $i++){ ?>
  <div class="paper-10pt" style="width:<?=$width?>mm;height:<?=$height?>mm;overflow:hidden;position: relative;font-size:20pt;margin-top:4mm">

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

    <div style="position: absolute;top:24mm;width:100%;text-align:center">
      <h1>Faktur Penjualan<?=$totalpages > 1 ? ' (' . ($i + 1) . '/' . $totalpages . ')' : ''?></h1>
      <label><?=$salesinvoicegroup['code']?></label>
    </div>

    <div style="position: absolute;right:0;top:0mm;text-align: left;width:60mm">
      <label>Customer:</label>
      <br />
      <div style="max-width:60mm;"><?=$salesinvoicegroup['customerdescription']?></div>
    </div>

    <div style="position: absolute;top:40mm;left:5mm">
      <table cellspacing="0" cellpadding="0" class="grid-type0<?=count($salesinvoices) > 35 ? ' font7pt' : ''?>">
        <thead>
          <tr>
            <th style="text-align: right">No.</th>
            <th style="text-align: center">Date</th>
            <th style="text-align: left">Invoice #</th>
            <th style="text-align: right">Total</th>
          </tr>
        </thead>
        <tbody style="border-bottom: solid 1px #000">
          <?php
          for($j = 0 ; $j < count($salesinvoices) && $j < $rowsperpage ; $j++){
            if(!isset($salesinvoices[$j + ($i * $rowsperpage)])) continue;
            $salesinvoice = $salesinvoices[$j + ($i * $rowsperpage)];
            ?>
            <tr>
              <td style="width:10mm;text-align:right"><?=$j + 1 + ($i * $rowsperpage)?></td>
              <td style="width:36mm;text-align:center"><?=date('j M Y', strtotime($salesinvoice['date']))?></td>
              <td style="width:<?=$itemflexwidth?>mm"><?=$salesinvoice['code']?></td>
              <td style="width:32mm;text-align:right"><?=number_format($salesinvoice['total'])?></td>
              <td style="width:8mm"></td>
            </tr>
          <?php } ?>
        </tbody>
      </table>

      <table cellspacing="0" cellpadding="0" style="margin: 7mm">
        <tr>
          <td colspan="2" style="text-align: right">

            <div style="">
              <table cellspacing="0" cellpadding="0">
                <tr>
                  <td style="width:110mm"></td>
                  <th style="width:30mm;text-align: right;white-space:nowrap;font-size:12pt;white-space: nowrap"><h2>Total</h2></th>
                  <td style="width:40mm;text-align: right;font-size:12pt;white-space: nowrap"><h2><?=number_format($salesinvoicegroup['total'])?></h2></td>
                  <td style="width:2mm"></td>
                </tr>
              </table>
            </div>

          </td>
        </tr>
        <tr>
          <td style="width:100%;text-align: left;vertical-align: bottom">

            <div style="display:none">
              <label>Beneficiary Detail :</label>
              <p><?=nl2br(systemvarget('beneficiarydetail'))?></p>
            </div>

          </td>
          <td style="width:75;text-align: center;vertical-align: bottom">

            <div style="">
              <div style="width:40mm;height:22mm;border-bottom: solid 1px #000;text-align: center">
                Authorized Sign,
              </div>
            </div>

          </td>
        </tr>
      </table>

    </div>
  </div>
<?php } ?>