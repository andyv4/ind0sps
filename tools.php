<?php
if(privilege_get('tools', 'list') < 1){ include 'notavailable.php'; return; }

require_once 'api/chartofaccount.php';
require_once 'api/customer.php';
require_once 'api/warehouse.php';
require_once 'api/inventory.php';
require_once 'api/purchaseinvoice.php';
require_once 'api/system.php';

function rsearch($folder, $pattern) {
  $iti = new RecursiveDirectoryIterator($folder);
  foreach(new RecursiveIteratorIterator($iti) as $file){
    if(strpos($file , $pattern) !== false){
      return $file;
    }
  }
}

function ui_update_preset(){

  $modules = [
    'customer',
    'salesinvoice',
    'salesinvoicegroup',
    'salesreceipt',
    'purchaseorder',
    'purchaseinvoice',
  ];

  foreach($modules as $modulename){

    require_once "api/{$modulename}.php";

    if(function_exists("{$modulename}_uicolumns")){
      $module_columns = call_user_func("{$modulename}_uicolumns");

      $modulefile = md5($modulename);
      $files = find_files('usr', $modulefile);
      foreach($files as $file){
        $file_content = file_get_contents($file);
        if(!is_string($file_content)) continue;
        $template = unserialize($file_content);
        if(is_array($template)){

          if(isset($template['columns']) && is_array_object($template['columns']))
            $template['columns'] = array_object_compare($template['columns'], $module_columns, [ 'name' ], RESULT_CLEAN);

          if(isset($template['presets']) && is_array_object($template['presets'])){
            foreach($template['presets'] as $index=>$preset)
              $template['presets'][$index]['columns'] = array_object_compare($template['presets'][$index]['columns'], $module_columns, [ 'name' ], RESULT_CLEAN);
          }

        }
        file_put_contents($file, serialize($template));
      }
    }
    else
      exc("Function not exists: {$modulename}_uicolumns");

  }

  echo ui_dialog("Update Preset", "All presets successfully updated.");

}

function ui_inventory_taxable_replicate($mode, $code){

  $result = inventory_taxable_replicate($mode, $code);

  // Generate text;
  $text = [];
  $text[] = "Succeeded: " . $result['success'];
  $text[] = "Failed: " . $result['failed'];
  $text[] = count($result['verbose']) > 0 ? "Message: " . "\n" . implode("\n", $result['verbose']) : '';
  return ui_dialog('Report', implode("\n", $text));

}

function ui_inventory_taxable_remove($mode, $code){

  $result = inventory_taxable_remove($mode, $code);

  // Generate text;
  $text = [];
  $text[] = "Succeeded: " . $result['success'];
  $text[] = "Failed: " . $result['failed'];
  $text[] = count($result['verbose']) > 0 ? "Message: " . "\n" . implode("\n", $result['verbose']) : '';
  return ui_dialog('Report', implode("\n", $text));

}

function ui_system_update_3_5(){

  system_update_3_5();
  return ui_dialog('Result', 'Database upgraded.');

}

ui_async();
?>
<div class="padding10">

  <table class="form" cellspacing="10">

    <!-- INVENTORY SECTION -->
    <tr><td colspan="2"><h5>Barang</h5></td></tr>
    <tr>
      <th><label>Copy Barang Non-Pajak</label></th>
      <td>
        <?=ui_dropdown([
          'id'=>'itp1',
          'items'=>[
            [ 'value'=>'start-with', 'text'=>'Diawali dengan' ],
            [ 'value'=>'end-with', 'text'=>'Diakhiri dengan' ],
          ],
          'width'=>'130px'
        ])?>
      </td>
      <td>
        <?=ui_textbox([
          'id'=>'itp2',
          'placeholder'=>'Kode Barang',
          'width'=>'100px',
        ])
        ?>
      </td>
      <td><button class="blue" onclick="ui.async('ui_inventory_taxable_replicate', [ $('#itp1').val(), $('#itp2').val() ], { waitel:this })"><span class="fa fa-cog"></span><label>Go</label></button></td>
    </tr>
    <tr>
      <th><label>Hapus Barang</label></th>
      <td>
        <?=ui_dropdown([
          'id'=>'itr1',
          'items'=>[
            [ 'value'=>'start-with', 'text'=>'Diawali dengan' ],
            [ 'value'=>'end-with', 'text'=>'Diakhiri dengan' ],
          ],
          'width'=>'130px'
        ])?>
      </td>
      <td>
        <?=ui_textbox([
          'id'=>'itr2',
          'placeholder'=>'Kode Barang',
          'width'=>'100px',
        ])
        ?>
      </td>
      <td><button class="blue" onclick="ui.async('ui_inventory_taxable_remove', [ $('#itr1').val(), $('#itr2').val() ], { waitel:this, partial:1 })"><span class="fa fa-cog"></span><label>Go</label></button></td>
    </tr>

    <!-- TAX SECTION -->
    <tr><td colspan="2"><div class="height50"></div></td></tr>
    <tr><td colspan="2"><h5>Pajak</h5></td></tr>
    <tr>
      <th><label>Perbaiki Faktur Penjualan tanpa Kode Pajak</label></th>
      <td>
        <?=ui_dropdown([
          'id'=>'itr3',
          'placeholder'=>'Tipe',
          'items'=>[
            [ 'value'=>'salesinvoice', 'text'=>'Faktur Penjualan' ]
          ],
          'width'=>'100px'
        ])?>
        &nbsp;
        <button class="blue" onclick="ui.async('m_updatestate', [ $('#itr3').val() ], { waitel:this })"><span class="fa fa-cog"></span><label>Go</label></button>
      </td>
    </tr>

    <!-- OTHERS SECTION -->
    <tr><td colspan="2"><div class="height50"></div></td></tr>
    <tr><td colspan="2"><h5>Lain Lain</h5></td></tr>
    <tr>
      <th><label>Reset Background Process</label></th>
      <td><button class="blue" onclick="ui.async('m_resetbackgroundprocess', [ $('#itr3').val() ], { waitel:this })"><span class="fa fa-cog"></span><label>Go</label></button></td>
    </tr>
    <tr>
      <th><label>Update Preset</label></th>
      <td><button class="blue" onclick="ui.async('ui_update_preset', [ ], { waitel:this })"><span class="fa fa-cog"></span><label>Go</label></button></td>
    </tr>

    <!-- UPDATE SECTION -->
    <tr><td colspan="2"><div class="height50"></div></td></tr>
    <tr><td colspan="2"><h5>Update</h5></td></tr>
    <tr>
      <th><label>Update Presets</label></th>
      <td>
        <?=ui_dropdown([
          'id'=>'itr3',
          'placeholder'=>'Tipe',
          'items'=>[
            [ 'value'=>'salesinvoice', 'text'=>'Faktur Penjualan' ]
          ],
          'width'=>'100px'
        ])?>
        &nbsp;
        <button class="blue" onclick="ui.async('m_updatestate', [ $('#itr3').val() ], { waitel:this })"><span class="fa fa-cog"></span><label>Go</label></button>
      </td>
    </tr>
    <tr>
      <th><label>Update 3.5</label></th>
      <td><button class="blue" onclick="ui.async('ui_system_update_3_5', [  ], { waitel:this })"><span class="fa fa-cog"></span><label>Go</label></button></td>
    </tr>

    <!-- DANGER ZONE -->
    <?php if(isset($_GET['debug'])){ ?>
    <tr><td colspan="2"><div class="height50"></div></td></tr>
    <tr><td colspan="2"><h5>Danger Zone</h5></td></tr>
    <tr>
      <th><label>Clear Transaction</label></th>
      <td><button class="red" onclick="ui.async('datacleartransact', [], { waitel:this })"><span class="fa fa-cogs"></span><label>Remove Transaction Data</label></button></td>
    </tr>
    <?php } ?>

  </table>

</div>