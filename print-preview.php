<?php

$available_templates = glob('template/*.php');

$items = [];
foreach($available_templates as $available_template)
  $items[] = [ 'text'=>$available_template, 'value'=>$available_template ];

require_once 'api/salesinvoice.php';
$salesinvoice = salesinvoicedetail('*', [ 'id'=>2123 ]);

?>
<div class="padding20">
  <div class="padding10">

    <table cellspacing="5">
      <tr>
        <td>
          <?=ui_dropdown([
            'items'=>$items,
            'width'=>'200px'
          ])?>
        </td>
        <td>
          <button class="blue"><label>Preview</label></button>
        </td>
      </tr>
    </table>

    <div class="height20"></div>

    <div style="position: relative;">
      <?php include 'template/salesinvoice.php'; ?>
    </div>


  </div>
</div>