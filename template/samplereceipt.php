<?php

$companyname = systemvarget('companyname');
$logo = systemvarget('logo');
$addressline1 = systemvarget('addressline1');
$addressline2 = systemvarget('addressline2');
$addressline3 = systemvarget('addressline3');
$addressline4 = systemvarget('addressline4');

$details = $inventoryadjustment['details'];

?>

<div class="paper" style="width:201px;height:124px">

  <table cellspacing="0" cellpadding="0" style="position: fixed;left:0mm;top:0mm">
    <tr>
      <td rowspan="5" width="60mm" style="vertical-align: top"><img width="60mm" height="60mm" src="<?=$logo?>"/></td>
      <td>&nbsp;</td>
      <td width="100%" style="white-space: nowrap"><h1 style="white-space: nowrap"><?=$companyname?></h1></td>
    </tr>
    <tr><td>&nbsp;</td><td><?=$addressline1?></td></tr>
    <tr><td>&nbsp;</td><td><?=$addressline2?></td></tr>
    <tr><td>&nbsp;</td><td><?=$addressline3?></td></tr>
  </table>

  <div style="position: fixed;right:0;top:0;text-align: right">
    <strong>Tanda Terima Barang Sampel</strong>
  </div>

  <div style="position: fixed;right:0;top:8mm;text-align: left;width:42mm">
    <label>Kepada Yth:</label>
    <br />
    <div style="max-width:42mm;">
      <?=$inventoryadjustment['description']?>
    </div>
  </div>

  <table cellspacing="0" cellpadding="0" style="position: fixed;top:25mm;left:5mm" class="grid">
    <tr>
      <th>Barang</th>
      <th>Kts</th>
      <th>Satuan</th>
      <th>Catatan</th>
    </tr>
    <?php
    for($i = 0 ; $i < count($details) ; $i++){
      $detail = $details[$i];
    ?>
    <tr>
      <td style="width:100mm"><?=$detail['inventorydescription']?></td>
      <td style="width:20mm"><?=abs($detail['qty'])?></td>
      <td style="width:20mm"><?=$detail['unit']?></td>
      <td style="width:80mm"><?=$detail['remark']?></td>
    </tr>
    <?php } ?>
  </table>

  <div style="position: fixed;top:88mm;right:5mm">
    <div style="width:40mm;height:22mm;border-bottom: solid 1px #000;text-align: center">
      Penerima
    </div>
  </div>

</div>