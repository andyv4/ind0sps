<?php
if(!systemvarget('salesable') || privilege_get('staff', 'modify') < 1){ include 'notavailable.php'; return; }

require_once 'api/chartofaccount.php';
require_once 'api/customer.php';
require_once 'api/warehouse.php';
require_once 'api/warehousetransfer.php';
require_once 'api/inventory.php';
require_once 'api/purchaseinvoice.php';
require_once 'api/system.php';

function ui_detailopen($ui){

  require_once 'ui/' . $ui . '.php';
  return call_user_func_array('ui_' . $ui . 'detail', array(rand(1, 100)));

}

function ui_progresscallback($current, $total, $errors = null){

  echo uijs("ui.simpleprogressbar_setvalue(ui('#progress'), $current, $total)");


}

$ui_availables = array();
$uifiles = glob('ui/*.php');
foreach($uifiles as $uifile)
  $ui_availables[] = array('text'=>explode('.', basename($uifile))[0], 'id'=>explode('.', basename($uifile))[0]);

ui_async();

$a = unserialize(file_get_contents('usr/dc5ace4afe06201150ee30de65e80d96/3fc0faff5e000dcd00f2e0dc4a5e8a6c'));
$a['presetidx'] = 0;
file_put_contents('usr/dc5ace4afe06201150ee30de65e80d96/3fc0faff5e000dcd00f2e0dc4a5e8a6c', serialize($a));

?>
<div class="padding10">


  <pre><?=print_r($a['presetidx'], 1)?></pre>

  <?=ui_simpleprogressbar(array('id'=>'progress', 'width'=>'100%'))?>

  <br />

  <table class="form" cellspacing="10">
    <tr>
      <th><label>COA Balance at Date</label></th>
      <td>
        <?=ui_textbox(array('width'=>'40px','name'=>'coaid', 'id'=>'coaid'))?>
        <?=ui_datepicker(array('name'=>'coaiddate', 'id'=>'coaiddate'))?>
        <button class="blue" onclick="ui.async('chartofaccountrecalculate', [ ui.textbox_value(ui('#coaid')), ui.datepicker_value(ui('#coaiddate')) ], { waitel:this, partial:1 })"><span class="fa fa-cog"></span><label>Start</label></button>
      </td>
    </tr>
    <tr><th colspan="2"><label>Recalculate All</label></th></tr>
    <tr>
      <th><label>Inventory Cost Price Recalculate All</label></th>
      <td><button class="blue" onclick="ui.async('inventorycostpricecalculateall', [ 'ui_progresscallback' ], { waitel:this, partial:1 })"><span class="fa fa-cog"></span><label>Start</label></button></td>
    </tr>
    <tr>
      <th><label>Inventory Order Qty Recalculate</label></th>
      <td><button class="blue" onclick="ui.async('inventory_purchaseorderqty', [ 'ui_progresscallback' ], { waitel:this, partial:1 })"><span class="fa fa-cog"></span><label>Start</label></button></td>
    </tr>
    <tr>
      <th><label>Duplikat Barang Non-Pajak menjadi Pajak</label></th>
      <td><button class="blue" onclick="ui.async('inventory_taxable_replicate', [ 'ui_progresscallback' ], { waitel:this, partial:1 })"><span class="fa fa-cog"></span><label>Start</label></button></td>
    </tr>
    <tr>
      <th><label>Chartofaccount recalculate all</label></th>
      <td><button class="blue" onclick="ui.async('chartofaccountrecalculateall', [], { waitel:this })"><span class="fa fa-cog"></span><label>Start</label></button></td>
    </tr>
    <tr>
      <th><label>Customer Receivable Recalculate All</label></th>
      <td><button class="blue" onclick="ui.async('customerreceivablecalculateall', [ 'ui_progresscallback' ], { waitel:this, partial:1 })"><span class="fa fa-cog"></span><label>Start</label></button></td>
    </tr>
    <tr>
      <th><label>Inventory Qty Recalculate All</label></th>
      <td><button class="blue" onclick="ui.async('inventoryqty_calculateall', [ 'ui_progresscallback' ], { waitel:this, partial:1 })"><span class="fa fa-cog"></span><label>Start</label></button></td>
    </tr>
    <tr>
      <th><label>Purchase Invoice Recalculate All</label></th>
      <td><button class="blue" onclick="ui.async('purchaseinvoicecalculateall', [ 'ui_progresscallback' ], { waitel:this, partial:1 })"><span class="fa fa-cog"></span><label>Start</label></button></td>
    </tr>
    <tr>
      <th><label>Warehouse Recalculate All</label></th>
      <td><button class="blue" onclick="ui.async('warehousecalculateall', [ 'ui_progresscallback' ], { waitel:this, partial:1 })"><span class="fa fa-cog"></span><label>Start</label></button></td>
    </tr>
    <tr>
      <th><label>Sales Invoice Group Items Fix</label></th>
      <td><button class="blue" onclick="ui.async('salesinvoicegroup_itemfix', [ 'ui_progresscallback' ], { waitel:this, partial:1 })"><span class="fa fa-cog"></span><label>Start</label></button></td>
    </tr>
    <tr>
      <th><label>Customer Salesman Default</label></th>
      <td><button class="blue" onclick="ui.async('customer_defaultsalesman', [ 'ui_progresscallback' ], { waitel:this, partial:1 })"><span class="fa fa-cog"></span><label>Start</label></button></td>
    </tr>
    <tr>
      <th><label>Sales Invoice Patch</label></th>
      <td><button class="blue" onclick="ui.async('salesinvoice_patch', [], { waitel:this })"><span class="fa fa-cog"></span><label>Start</label></button></td>
    </tr>
    <tr>
      <th><label>Warehouse Transfer Fix 1</label></th>
      <td><button class="blue" onclick="ui.async('warehousetransfer_fix1', [], { waitel:this })"><span class="fa fa-cog"></span><label>Start</label></button></td>
    </tr>
    <tr><th colspan="2"><label>Move State</label></th></tr>
    <tr>
      <th><label>Update customer move state</label></th>
      <td><button class="blue" onclick="ui.async('customer_updatemovestate', [], { waitel:this })"><span class="fa fa-cog"></span><label>Start</label></button></td>
    </tr>
    <tr>
      <th><label>Update chartofaccount move state</label></th>
      <td><button class="blue" onclick="ui.async('chartofaccount_updatemovestate', [], { waitel:this })"><span class="fa fa-cog"></span><label>Start</label></button></td>
    </tr>
    <tr>
      <th><label>Update inventory move state</label></th>
      <td><button class="blue" onclick="ui.async('inventory_updatemovestate', [], { waitel:this })"><span class="fa fa-cog"></span><label>Start</label></button></td>
    </tr>
    <tr>
      <th><label>Update warehouse move state</label></th>
      <td><button class="blue" onclick="ui.async('warehouse_updatemovestate', [], { waitel:this })"><span class="fa fa-cog"></span><label>Start</label></button></td>
    </tr>
    <tr>
      <th><label>Update preset selected index</label></th>
      <td>
        <?=ui_textbox(array('width'=>'300px','name'=>'a1', 'id'=>'a1'))?>
        <?=ui_textbox(array('width'=>'50px','name'=>'a2', 'id'=>'a2'))?>
        <button class="blue" onclick="ui.async('system_presetidx_set', [ ui.textbox_value(ui('#a1')), ui.textbox_value(ui('#a2')) ], { waitel:this })"><span class="fa fa-cog"></span><label>Start</label></button>
      </td>
    </tr>
    <tr><th colspan="2"><label>Updates</label></th></tr>
    <tr>
      <th><label>Update 21-04-17</label></th>
      <td><button class="red" onclick="ui.async('system_update042117', [], { waitel:this })"><span class="fa fa-cog"></span><label>Start</label></button></td>
    </tr>
    <tr>
      <th><label>Update 17-08-15</label></th>
      <td><button class="red" onclick="ui.async('system_update081715', [], { waitel:this })"><span class="fa fa-cog"></span><label>Start</label></button></td>
    </tr>
    <tr>
      <th><label>Update 09-10-15</label></th>
      <td><button class="red" onclick="ui.async('system_update100915', [], { waitel:this })"><span class="fa fa-cog"></span><label>Start</label></button></td>
    </tr>
    <tr>
      <th><label>Update 3.1.160</label></th>
      <td><button class="red" onclick="ui.async('system_update_3_1_160', [], { waitel:this })"><span class="fa fa-cog"></span><label>Start</label></button></td>
    </tr>
    <?php if($_SESSION['user']['userid'] == 'andy'){ ?>
    <tr>
      <th><label>Clear Transaction</label></th>
      <td><button class="red" onclick="ui.async('datacleartransact', [], { waitel:this })"><span class="fa fa-cogs"></span><label>Remove Transaction Data</label></button></td>
    </tr>
    <?php } ?>
    <tr>
      <th><label>Recreate data 2</label></th>
      <td><button class="red" onclick="ui.async('datarecreatedata2', [], { waitel:this })"><span class="fa fa-cogs"></span><label>Recreate Data 2</label></button></td>
    </tr>
    <tr><th colspan="2"><label>Misc</label></th></tr>
    <tr>
      <th><label>Detail UI Test</label></th>
      <td>
        <span style="width:200px;height:200px" class="scrollable valign-top">
          <?=
          ui_grid(array(
            'columns'=>array(
              array('active'=>1, 'text'=>'Text', 'name'=>'text', 'width'=>150)
            ),
            'value'=>$ui_availables,
            'id'=>'grid1'
          ))
          ?>
        </span>
        <button class="blue valign-top" onclick="ui.async('ui_detailopen', [ ui.grid_selectedid(ui('#grid1')) ], { waitel:this })"><span class="fa fa-send"></span><label>Open...</label></button>
      </td>
    </tr>
  </table>

</div>