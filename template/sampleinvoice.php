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

$row1height = 60;
$row1width = $contentwidth;
$row1top = $row0height + 2;
$row1col2width = $contentwidth - (8 + 18 + 14 + 14 + 20 + 20);

$row2top = $row1top + $row1height + 2;
$row2col1width = 60;
$row2col0width = $contentwidth - $row2col1width;
$row2col1left = $row2col0width;
$row2height = 40;

$id = $salesinvoice['id'];
$salesinvoice = sampleinvoicedetail(null, array('id'=>$id));

$companyname = systemvarget('companyname');
$logo = systemvarget('logo');
$addressline1 = systemvarget('addressline1');
$addressline2 = systemvarget('addressline2');
$addressline3 = systemvarget('addressline3');
$addressline4 = systemvarget('addressline4');

$code = $salesinvoice['code'];
$date = date('M j, Y', strtotime($salesinvoice['date']));
$customerdescription = $salesinvoice['customerdescription'];
$address = $salesinvoice['address'];
$inventories = $salesinvoice['inventories'];
$note = $salesinvoice['note'];
//$salesmanname = $salesinvoice['salesmanname'];

$page = ceil(count($inventories) / 7);
?>

<?php
for($a = 0 ; $a < $page ; $a++){
  ?>
  <div class="paper" style="width:<?=$contentwidth?>mm;height:<?=$contentheight?>mm;padding:0;margin:0;overflow:hidden">
    <!-- Row 0 -->
    <div style="position: absolute;left: 0;top: 0;width: <?=$row0col0width?>mm;height:<?=$row0height?>mm">
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
    <div style="position: absolute;left: <?=$row0col1left?>mm;top: 0;width: <?=$row0col1width?>mm;height: <?=$row0height?>mm;text-align: center;vertical-align: bottom">
      <br /><br /><br />
      <table cellspacing="0" cellpadding="0" width="100%">
        <tr><td style="text-align: center"><strong>SAMPLE INVOICE</strong></td></tr>
        <tr><td style="text-align: center"><?=$code?></td></tr>
      </table>
    </div>
    <div style="position: absolute;left: <?=$row0col2left?>mm;top: 0;width: <?=$row0col2width?>mm;height:<?=$row0height?>mm">
      <table cellspacing="0" cellpadding="0">
        <tr>
          <th width="15mm" style="text-align: right;white-space: nowrap">Date</th>
          <td>&nbsp;:&nbsp;</td>
          <td><?=$date?></td>
        </tr>
        <tr>
          <th width="15mm" style="text-align: right;white-space: nowrap">Recipient</th>
          <td>&nbsp;:&nbsp;</td>
          <td><?=$customerdescription?></td>
        </tr>
        <tr>
          <th width="15mm" style="text-align: right;white-space: nowrap"></th>
          <td>&nbsp;&nbsp;</td>
          <td><?=$address?></td>
        </tr>
      </table>
    </div>

    <!-- Row 1 -->
    <div style="position: absolute;left: 0;top:<?=$row1top?>mm;width: <?=$row1width?>mm;height:<?=$row1height?>mm">
      <table cellspacing="0" cellpadding="0" class="grid">
        <tr>
          <th width="8mm" style="width:8mm;text-align: right">#</th>
          <th width="18mm" style="width:18mm">Code</th>
          <th width="<?=$row1col2width?>mm" style="width:<?=$row1col2width?>mm">Item Description</th>
          <th width="14mm" style="width:14mm;text-align: right">Qty</th>
          <th width="14mm" style="width:14mm">Unit</th>
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
          </tr>
        <?php } ?>
      </table>
      <div><?=$note?></div>
    </div>

    <!-- Row 2 -->
    <div style="position: absolute;left: 0;top:<?=$row2top?>mm;width: <?=$row2col0width?>mm;height:<?=$row2height?>mm">
      <table cellspacing="8" cellpadding="0">
        <tr>
          <td><div style="width:10mm"></div></td>
          <td style="vertical-align: top">
            <div style="width:40mm;height:22mm;border-bottom: solid 1px #000;text-align: center">
              Authorized Sign
            </div>
          </td>
          <td><div style="width:10mm"></div></td>
          <td style="vertical-align: top">
            <div style="width:40mm;height:22mm;border-bottom: solid 1px #000;text-align: center">
              Driver
            </div>
          </td>
          <td><div style="width:10mm"></div></td>
          <td style="vertical-align: top">
            <div style="width:40mm;height:22mm;border-bottom: solid 1px #000;text-align: center">
              Penerima
            </div>
          </td>
        </tr>
      </table>
    </div>

  </div>
<?php  } ?>
<?php console_info(get_defined_vars()); ?>