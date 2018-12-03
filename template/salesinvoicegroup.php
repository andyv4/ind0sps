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

$rowsperpage = 10;
$pages = $totalpages = ceil(count($salesinvoices) / $rowsperpage);
if($page > 0){ $page = $page - 1; $pages = $page + 1; }
else $page = 0;

?>

<?php for($i = $page ; $i < $pages ; $i++){ ?>
  <div class="paper" style="width:201mm;height:124mm;overflow:hidden;position: relative">

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
      <h1>Faktur Penjualan<?=$totalpages > 1 ? ' (' . ($i + 1) . '/' . $totalpages . ')' : ''?></h1>
      <label><?=$salesinvoicegroup['code']?></label>
    </div>

    <div style="position: absolute;right:0;top:0mm;text-align: left;width:60mm">
      <label>Customer:</label>
      <br />
      <div style="max-width:60mm;"><?=$salesinvoicegroup['customerdescription']?></div>
      <div style="max-width:60mm;"><?=$salesinvoicegroup['address']?></div>
    </div>

    <table cellspacing="0" cellpadding="0" style="position: absolute;top:22mm;left:5mm" class="grid-type0">
      <thead>
        <tr>
          <th style="text-align: right">No.</th>
          <th style="text-align: center">Date</th>
          <th style="text-align: left">Invoice #</th>
          <th style="text-align: right">Total</th>
        </tr>
      </thead>
      <tbody>
        <?php
        for($j = 0 ; $j < count($salesinvoices) && $j < $rowsperpage ; $j++){
          if(!isset($salesinvoices[$j + ($i * $rowsperpage)])) continue;
          $salesinvoice = $salesinvoices[$j + ($i * $rowsperpage)];
          ?>
          <tr>
            <td style="width:10mm;text-align:right"><?=$j + 1 + ($i * $rowsperpage)?></td>
            <td style="width:36mm;text-align:center"><?=date('j M Y', strtotime($salesinvoice['date']))?></td>
            <td style="width:100mm"><?=$salesinvoice['code']?></td>
            <td style="width:40mm;text-align:right"><?=number_format($salesinvoice['total'])?></td>
          </tr>
        <?php } ?>
      </tbody>
    </table>

    <div style="position: absolute;left: 5mm;bottom: 2mm;display:none">
      <label>Beneficiary Detail :</label>
      <p><?=nl2br(systemvarget('beneficiarydetail'))?></p>
    </div>

    <div style="position: absolute;top:93mm;right:3mm">
      <table cellspacing="0" cellpadding="0">
        <tr>
          <th style="width:30mm;text-align: right">Total</th>
          <td style="width:30mm;text-align: right"><?=number_format($salesinvoicegroup['total'])?></td>
        </tr>
      </table>
    </div>

    <div style="position: absolute;bottom:2mm;right:2mm">
      <div style="width:40mm;height:22mm;border-bottom: solid 1px #000;text-align: center">
        Penerima
      </div>
    </div>

  </div>
<?php } ?>