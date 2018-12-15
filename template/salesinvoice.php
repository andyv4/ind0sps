<?php
$printermargin = systemvarget('printermargin');
$paperwidth = systemvarget('paperwidth');
$paperheight = systemvarget('paperheight');
$contentwidth = $paperwidth - ($printermargin * 2);
$contentheight = $paperheight - ($printermargin * 2);

$row0col1width = 25 + 10;
$row0col0width = $row0col2width = floor(($contentwidth - $row0col1width) / 2);
$row0col1left = $row0col0width;
$row0col2left = $row0col0width + $row0col1width + 5; // was 20
$row0height = 20;

$row1height = 62;
$row1width = $contentwidth;
$row1top = $row0height + 2;
$row1col2width = $contentwidth - (8 + 18 + 14 + 14 + 20 + 20);

$row2top = $row1top + $row1height + 2;
$row2col1width = 60;
$row2col0width = $contentwidth - $row2col1width;
$row2col1left = $row2col0width;
$row2height = 40;

$id = $salesinvoice['id'];
$salesinvoice = salesinvoicedetail([ '*', 'customer' ], array('id'=>$id));


$taxable = $salesinvoice['taxable'];
$customerid = $salesinvoice['customerid'];
$customer = customerdetail(null, [ 'id'=>$customerid ]);

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

$pocode = $salesinvoice['pocode'];
$code = $salesinvoice['code'];
$date = date('M j, Y', strtotime($salesinvoice['date']));
$customerdescription = $salesinvoice['customerdescription'];
$address = $salesinvoice['address'];
$creditterm = $salesinvoice['creditterm'];
$inventories = $salesinvoice['inventories'];
$subtotal = $salesinvoice['subtotal'];
$discountamount = $salesinvoice['discountamount'];
$taxamount = $salesinvoice['taxamount'];
$deliverycharge = $salesinvoice['deliverycharge'];
$total = $salesinvoice['total'];
$note = $salesinvoice['note'];
$customer_tax_registration_number = $salesinvoice['customer_tax_registration_number'];
//$salesmanname = $salesinvoice['salesmanname'];

$temp = [];
for($i = 0 ; $i < 7 ; $i++)
  $temp[] = $inventories[0];
$inventories = $temp;

$page = ceil(count($inventories) / 7);

$payment_label = trim(systemvarget('sales_payment_account1_label'));

?>

<?php
for($a = 0 ; $a < $page ; $a++){
  ?>
  <div class="paper" style="width:<?=$contentwidth?>mm;height:<?=$contentheight?>mm;padding:0;margin:0;overflow:hidden">

    <?php if($creditterm == -1){ ?>
    <div style="position: absolute;right:10mm;top:0;z-index:1;padding:3px;border:solid 2px black;">
      <h1 style="font-size:14pt;color:rgba(0, 0, 0, 1);font-weight:bold">CASH</h1>
    </div>
    <?php } ?>
    
    <!-- Row 0 -->
    <div style="position: absolute;left: 0;top: 0;width: <?=$row0col0width?>mm;height:<?=$row0height?>mm;z-index:2">
      <div style="position: relative">
        <table cellspacing="0" cellpadding="0">
          <?php if($logo){ ?>
          <tr>
            <td rowspan="5" width="60mm" style="vertical-align: top"><img width="60mm" height="60mm" src="<?=$logo?>"/></td>
            <td>&nbsp</td>
            <td width="100%" style="white-space: nowrap"><h1 style="white-space: nowrap;font-size:14pt"><?=$companyname?></h1></td>
          </tr>
          <?php } else { ?>
          <tr>
            <td rowspan="5" style="vertical-align: top"></td>
            <td>&nbsp</td>
            <td width="100%" style="white-space: nowrap"><h1 style="white-space: nowrap;font-size:14pt"><?=$companyname?></h1></td>
          </tr>
          <?php } ?>
          <tr><td>&nbsp;</td><td><?=$addressline1?></td></tr>
          <tr><td>&nbsp;</td><td><?=$addressline2?></td></tr>
          <tr><td>&nbsp;</td><td><?=$addressline3?></td></tr>
        </table>
      </div>
    </div>
    <div style="position: absolute;left: <?=$row0col1left?>mm;top: 5mm;width: <?=$row0col1width?>mm;height: <?=$row0height?>mm;text-align: center;vertical-align: bottom">
      <br /><br /><br />
      <table cellspacing="0" cellpadding="0" width="100%">
        <tr><td style="text-align: center"><strong>INVOICE</strong></td></tr>
        <tr><td style="text-align: center"><?=$code?></td></tr>
      </table>
    </div>
    <div style="position: absolute;left: <?=$row0col2left?>mm;top: 0;width: <?=$row0col2width?>mm;height:<?=$row0height?>mm">
      <table cellspacing="0" cellpadding="0">
        <tr>
          <th width="15mm" style="text-align: right;white-space: nowrap">PO. No.</th>
          <td>&nbsp;:&nbsp;</td>
          <td><?=$pocode?></td>
        </tr>
        <tr>
          <th width="15mm" style="text-align: right;white-space: nowrap">Date</th>
          <td>&nbsp;:&nbsp;</td>
          <td>
            <?=$date?>
          </td>
        </tr>
        <tr>
          <th width="15mm" style="text-align: right;white-space: nowrap">Sold To</th>
          <td>&nbsp;:&nbsp;</td>
          <td><?=$customerdescription?></td>
        </tr>
        <tr>
          <th width="15mm" style="text-align: right;white-space: nowrap"></th>
          <td>&nbsp;&nbsp;</td>
          <td>
            <?=$address?>
          </td>
        </tr>
        <?php if($taxable){ ?>
        <tr>
          <th width="15mm" style="text-align: right;white-space: nowrap">NPWP</th>
          <td>&nbsp;:&nbsp;</td>
          <td><?=$customer_tax_registration_number?></td>
        </tr>
        <?php } ?>
      </table>
    </div>

    <!-- Row 1 -->
    <div style="position: absolute;left: 0;top:<?=$row1top + 5?>mm;width: <?=$row1width?>mm;height:<?=$row1height?>mm">
      <table cellspacing="0" cellpadding="0" class="grid">
        <tr>
          <th width="8mm" style="width:8mm;text-align: right">#</th>
          <th width="18mm" style="width:18mm">Code</th>
          <th width="<?=$row1col2width?>mm" style="width:<?=$row1col2width?>mm">Item Description</th>
          <th width="14mm" style="width:14mm;text-align: right">Qty</th>
          <th width="14mm" style="width:14mm">Unit</th>
          <th width="20mm" style="width:20mm;text-align: right">Unit Price</th>
          <th width="20mm" style="width:20mm;text-align: right">Total</th>
        </tr>
        <?php
          for($i = $a * 7 ; $i < count($inventories) && $i < ($a * 7) + 7 ; $i++){
            $inventory = $inventories[$i];
        ?>
        <tr>
          <td style="text-align: right"><?=$i + 1?></td>
          <td><?=$inventory['inventorycode']?></td>
          <td><?=$inventory['inventorydescription']?></td>
          <td style="text-align: right"><?=$inventory['qty']?></td>
          <td><?=$inventory['unit']?></td>
          <td style="text-align: right"><?=number_format($inventory['unitprice'])?></td>
          <td style="text-align: right"><?=number_format($inventory['unittotal'])?></td>
        </tr>
        <?php } ?>
      </table>
      <div><?=$note?></div>
    </div>

    <!-- Row 2 -->
    <div style="position: absolute;left: 0;top:<?=$row2top?>mm;width: <?=$row2col0width?>mm;height:<?=$row2height?>mm">
      <div style="height:7mm">
        <table cellspacing="0" cellpadding="0">
          <tr>
            <td colspan="3">
              <label style="font-style: italic">Say: <?=terbilang(99999999)?></label>
            </td>
          </tr>
        </table>
      </div>
      <table cellspacing="8" cellpadding="0">
        <tr>
          <td style="vertical-align: top">
            <div style="width:40mm;height:22mm;border-bottom: solid 1px #000;text-align: center">
              Received in good condition
            </div>
            <div style="width:40mm;text-align: center">Customer Sign & Stamp</div>
          </td>
          <td><div style="width:10mm"></div></td>
          <td style="vertical-align: top">
            <div style="width:40mm;height:22mm;border-bottom: solid 1px #000;text-align: center">
              Authorized Sign
            </div>
            <div style="font-size:.9em;width:40mm;text-align: center"><?=ucwords($_SESSION['user']['name'])?></div>
          </td>
          <td><div style="width:10mm"></div></td>
          <td style="vertical-align: top">
            <div style="width:40mm;height:22mm;border-bottom: solid 1px #000;text-align: center">
              Driver
            </div>
          </td>
        </tr>
      </table>
    </div>
    <div style="position: absolute;left: <?=$row2col1left?>mm;top:<?=$row2top?>mm;width:<?=$row2col1width?>mm;height:<?=$row2height?>mm;text-align: right;padding-right:8mm">
      <table cellspacing="4mm" cellpadding="0" style="display: inline-block">
        <tr>
          <th style="text-align: right;white-space: nowrap">Subtotal</th>
          <td>&nbsp;:&nbsp;</td>
          <td style="width:20mm;text-align: right"><?=number_format($subtotal)?></td>
        </tr>
        <tr>
          <th style="text-align: right;white-space: nowrap">Discount</th>
          <td>&nbsp;:&nbsp;</td>
          <td style="width:20mm;text-align: right"><?=number_format($discountamount)?></td>
        </tr>
        <?php if($taxamount > 0){ ?>
        <tr>
          <th style="text-align: right;white-space: nowrap">PPn</th>
          <td>&nbsp;:&nbsp;</td>
          <td style="width:20mm;text-align: right"><?=number_format($taxamount)?></td>
        </tr>
        <?php } ?>
        <?php if($deliverycharge > 0){ ?>
        <tr>
          <th style="text-align: right;white-space: nowrap">Delivery Charge</th>
          <td>&nbsp;:&nbsp;</td>
          <td style="width:20mm;text-align: right"><?=number_format($deliverycharge)?></td>
        </tr>
        <?php } ?>
        <tr>
          <th style="text-align: right;white-space: nowrap;font-size:1.2em">Total</th>
          <td>&nbsp;:&nbsp;</td>
          <td style="width:20mm;text-align: right;font-size:1.2em"><?=number_format(floor($total))?></td>
        </tr>
        <tr><td><div style="height:7mm">&nbsp;</div></td></tr>
        <tr>
          <th style="text-align: right;white-space: nowrap"></th>
          <td></td>
          <td style="font-size:.8em;width:20mm;text-align: right"><?=$salesinvoice['salesmanname']?></td>
        </tr>
      </table>
    </div>

    <div style="position: absolute;left: 0;top:<?=$row2top+37?>mm;">
      <?php if(strlen($payment_label) > 0){ ?>
        <div style="height:4mm;position:relative;top:-1mm;">
          <span style="font-size:11pt"><?=$payment_label?></span>
        </div>
      <?php } ?>
    </div>
  </div>
<?php  } ?>