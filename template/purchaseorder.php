<?php
$printermargin = systemvarget('printermargin');
$paperwidth = 210;
$paperheight = 297;
$contentwidth = $paperwidth - ($printermargin * 2);
$contentheight = $paperheight - ($printermargin * 2);

$row0col1width = 25 + 10;
$row0col0width = $row0col2width = floor(($contentwidth - $row0col1width) / 2);
$row0col1left = $row0col0width;
$row0col2left = $row0col0width + $row0col1width + 5 - 10; // was 20
$row0height = 32;

$row1height = 205;
$row1width = $contentwidth;
$row1top = $row0height + 8;
$row1col2width = $contentwidth - (8 + 18 + 14 + 14 + 20 + 20);

$row2top = $row1top + $row1height + 2;
$row2col1width = 60;
$row2col0width = $contentwidth - $row2col1width;
$row2col1left = $row2col0width;
$row2height = 40;

$id = $purchaseorder['id'];
$purchaseorder = purchaseorderdetail(null, array('id'=>$id));
$code = $purchaseorder['code'];
$currencyid = $purchaseorder['currencyid'];
$currency = pmr("select code from currency where `id` = ?", [ $currencyid ]);
$currency_code = $currency['code'];
$date = $purchaseorder['date'];
$supplierdescription = $purchaseorder['supplierdescription'];
$address = $purchaseorder['address'];
$inventories = $purchaseorder['inventories'];
$subtotal = $purchaseorder['subtotal'];
$discountamount = $purchaseorder['discountamount'];
$taxamount = $purchaseorder['taxamount'];
$deliverycharge = $purchaseorder['deliverycharge'];
$note = $purchaseorder['note'];
$total = $purchaseorder['total'];
$refno = $purchaseorder['refno'];
$term = $purchaseorder['term'];
$eta = $purchaseorder['eta'];

$date = date('j M Y', strtotime($date));
$eta = date('j M Y', strtotime($eta));
if(date('Ymd', strtotime($ta)) < 20150101) $eta = '-';

$companyname = systemvarget('purchase_company_name');
$logo = systemvarget('logo');
$addressline1 = systemvarget('purchase_addressline1');
$addressline2 = systemvarget('purchase_addressline2');
$addressline3 = systemvarget('purchase_addressline3');

foreach($inventories as $index=>$inventory){
  $inventories[$index]['qty'] = number_format_auto($inventories[$index]['qty']);
}

$row_per_page = 30;
$page = ceil(count($inventories) / $row_per_page);
?>

<?php for($a = 0 ; $a < $page ; $a++){ ?>

<div class="paper-9pt" style="width:<?=$contentwidth?>mm;height:<?=$contentheight?>mm;padding:0;margin:0;overflow:hidden;position:relative">

  <!-- Row 0 -->
  <div style="position: absolute;left: 0;top: 0;width: <?=$row0col0width?>mm;height:<?=$row0height?>mm;z-index:2">
    <div style="position: relative">
      <table cellspacing="0" cellpadding="0">
        <tr>
          <td rowspan="5" width="60mm" style="vertical-align: top"><img width="60mm" height="60mm" src="<?=$logo?>"/></td>
          <td>&nbsp;</td>
          <td width="100%" style="white-space: nowrap"><h1 style="white-space: nowrap"><?=$companyname?></h1></td>
        </tr>
        <tr><td>&nbsp;</td><td><?=$addressline1?></td></tr>
        <tr><td>&nbsp;</td><td><?=$addressline2?></td></tr>
        <tr><td>&nbsp;</td><td><?=$addressline3?></td></tr>
      </table>
    </div>
  </div>
  <div style="position: absolute;left: <?=$row0col1left?>mm;top: 14mm;height: <?=$row0height?>mm;text-align: center;vertical-align: bottom">
    <br /><br /><br />
    <table cellspacing="0" cellpadding="0" width="100%">
      <tr><td style="text-align: center"><h2 style="line-height:1.1em">PURCHASE ORDER<br /><?=$code?></h2></td></tr>
    </table>
  </div>
  <div style="position: absolute;left: <?=$row0col2left?>mm;top: 0;width: <?=$row0col2width?>mm;height:<?=$row0height?>mm">
    <table cellspacing="0" cellpadding="0">
      <tr>
        <th width="15mm" style="text-align: right;white-space: nowrap">Supplier</th>
        <td>&nbsp;:&nbsp;</td>
        <td style="white-space:nowrap;text-overflow:ellipsis;overflow:hidden;font-size:8pt"><?=$supplierdescription?></td>
      </tr>
      <tr>
        <th width="15mm" style="text-align: right;white-space: nowrap"></th>
        <td>&nbsp;&nbsp;</td>
        <td style="font-size:8pt">
          <?=$address?>
        </td>
      </tr>
      <tr>
        <th width="15mm" style="text-align: right;white-space: nowrap">Ref No.</th>
        <td>&nbsp;:&nbsp;</td>
        <td><?=$refno?></td>
      </tr>
      <tr>
        <th width="15mm" style="text-align: right;white-space: nowrap">Date</th>
        <td>&nbsp;:&nbsp;</td>
        <td>
          <?=$date?>
        </td>
      </tr>
      <tr>
        <th width="15mm" style="text-align: right;white-space: nowrap">ETA</th>
        <td>&nbsp;:&nbsp;</td>
        <td>
          <?=$eta?>
        </td>
      </tr>
    </table>
  </div>

  <!-- Row 1 -->
  <div style="position: absolute;left: 0;top:<?=$row1top?>mm;width: <?=$row1width?>mm;height:<?=$row1height?>mm">
    <table cellspacing="0" cellpadding="0" class="grid">
      <tr>
        <th width="8mm" style="width:8mm;text-align: right">#</th>
        <th width="17mm" style="width:17mm">Code</th>
        <th width="<?=$row1col2width?>mm" style="width:<?=$row1col2width?>mm">Item Description</th>
        <th width="15mm" style="width:15mm;text-align: right">Qty</th>
        <th width="14mm" style="width:14mm">Unit</th>
        <th width="20mm" style="width:20mm;text-align: right">Unit Price</th>
        <th width="20mm" style="width:20mm;text-align: right">Total</th>
      </tr>
      <?php
        for($i = 0 ; $i < count($inventories) ; $i++){
          $index = ($a * $row_per_page) + $i;
          $inventory = isset($inventories[$index]) ? $inventories[$index] : null;
      ?>
      <?php if($inventory){ ?>
        <tr>
          <td style="text-align: right"><?=$i + 1?></td>
          <td><?=$inventory['inventorycode']?></td>
          <td><?=$inventory['inventorydescription']?></td>
          <td style="text-align: right"><?=$inventory['qty']?></td>
          <td style="text-align:center"><?=$inventory['unit']?></td>
          <td style="text-align: right"><?=number_format($inventory['unitprice'])?></td>
          <td style="text-align: right"><?=number_format($inventory['unittotal'])?></td>
        </tr>
        <?php } else { ?>
        <tr>
          <td>&nbsp;</td>
          <td></td>
          <td></td>
          <td></td>
          <td></td>
          <td></td>
          <td></td>
        </tr>
        <?php } ?>
      <?php } ?>
    </table>

    <div style="height:5mm"></div>

    <table style="width:100%" width="100%">
      <tr>
        <td colspan="2">
          <table cellspacing="0" cellpadding="0">
            <tr>
              <td valign="top" style="width:12mm"><label>Note: </label></td>
              <td><?=$note?></td>
            </tr>
          </table>
        </td>
      </tr>
      <tr>
        <td>
          <div>
            <table cellspacing="8" cellpadding="0">
              <tr>
                <td style="vertical-align: top">
                  <div style="width:50mm;height:22mm;border-bottom: solid 1px #000;text-align: center">
                    Purchase Order Originator
                  </div>
                  <div style="width:40mm;text-align: center"><!--NAME--></div>
                </td>
                <td><div style="width:10mm"></div></td>
                <td style="vertical-align: top">
                  <div style="width:50mm;height:22mm;border-bottom: solid 1px #000;text-align: center">
                    PO Approval
                  </div>
                  <div style="font-size:.9em;width:40mm;text-align: center"><!--NAME--></div>
                </td>
              </tr>
            </table>
          </div>
        </td>
        <td align="right">
          <div style="margin-top:5mm">
            <table cellspacing="4mm" cellpadding="0" style="display: inline-block">
              <tr>
                <th style="text-align: right;white-space: nowrap">Subtotal</th>
                <td>&nbsp;:&nbsp;<?=$currency_code?></td>
                <td style="width:40mm;text-align: right"><?=number_format($subtotal)?></td>
              </tr>
              <tr>
                <th style="text-align: right;white-space: nowrap">Discount</th>
                <td>&nbsp;:&nbsp;<?=$currency_code?></td>
                <td style="width:40mm;text-align: right"><?=number_format($discountamount)?></td>
              </tr>
              <tr>
                <th style="text-align: right;white-space: nowrap">VAT</th>
                <td>&nbsp;:&nbsp;<?=$currency_code?></td>
                <td style="width:40mm;text-align: right"><?=number_format($taxamount)?></td>
              </tr>
              <?php if($deliverycharge > 0){ ?>
                <tr>
                  <th style="text-align: right;white-space: nowrap">Delivery Charge</th>
                  <td>&nbsp;:&nbsp;<?=$currency_code?></td>
                  <td style="width:40mm;text-align: right"><?=number_format($deliverycharge)?></td>
                </tr>
              <?php } ?>
              <tr>
                <th style="text-align: right;white-space: nowrap">Total</th>
                <td>&nbsp;:&nbsp;<?=$currency_code?></td>
                <td style="width:40mm;text-align: right"><?=number_format($total)?></td>
              </tr>
              <tr><td><div style="height:7mm">&nbsp;</div></td></tr>
              <tr>
                <th style="text-align: right;white-space: nowrap"></th>
                <td></td>
                <td style="font-size:.8em;width:20mm;text-align: right"><?=$salesinvoice['salesmanname']?></td>
              </tr>
            </table>
          </div>
        </td>
      </tr>
    </table>

    <!-- Row 2 -->


  </div>



</div>

<?php } ?>