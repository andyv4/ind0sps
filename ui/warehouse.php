<?php

require_once 'api/warehouse.php';

function ui_warehousedetail($id, $mode = 'read'){

  // Inventory Object
  $warehouse = warehousedetail(null, array('id'=>$id));
  if($mode != 'read' && $warehouse && !privilege_get('warehouse', 'modify')) $mode = 'read';
  if($mode == 'read' && !$warehouse) throw new Exception('Gudang dengan nomor ini tidak ada.');

  // Controls
  $readonly = $mode == 'read' ? 1 : 0;
  $closable = $readonly ? 1 : 0;
  $is_new = !$readonly && !$warehouse ? true : false;
  if($is_new && !privilege_get('warehouse', 'new')) exc("Anda tidak dapat membuat gudang.");
  $controls = array(
      'id'=>array('type'=>'hidden', 'name'=>'id', 'value'=>ov('id', $warehouse)),
      'isdefault'=>array('type'=>'checkbox', 'name'=>'isdefault', 'value'=>ov('isdefault', $warehouse), 'readonly'=>$readonly),
      'code'=>array('type'=>'textbox', 'name'=>'code', 'value'=>ov('code', $warehouse), 'width'=>100, 'readonly'=>$readonly),
      'name'=>array('type'=>'textbox', 'name'=>'name', 'value'=>ov('name', $warehouse), 'width'=>300, 'readonly'=>$readonly),
      'address'=>array('type'=>'textarea', 'name'=>'address', 'value'=>ov('address', $warehouse), 'width'=>300, 'height'=>80, 'readonly'=>$readonly),
      'city'=>array('type'=>'textbox', 'name'=>'city', 'value'=>ov('city', $warehouse), 'width'=>150, 'readonly'=>$readonly),
      'country'=>array('type'=>'textbox', 'name'=>'country', 'value'=>ov('country', $warehouse), 'width'=>150, 'readonly'=>$readonly),
      'total'=>array('type'=>'textbox', 'name'=>'total', 'value'=>ov('total', $warehouse), 'width'=>150, 'readonly'=>$readonly),
      'amount'=>array('type'=>'textbox', 'name'=>'amount', 'value'=>ov('amount', $warehouse), 'width'=>150, 'readonly'=>$readonly),
  );

  // Action Controls
  $actions = array();
  if($readonly && $warehouse && privilege_get('warehouse', 'modify')) $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_warehousedetail', [ $id, 'write' ], { waitel:this })\"><span class='fa fa-edit'></span><label>Ubah</label></button></td>";
  if(!$readonly && !$warehouse && privilege_get('warehouse', 'new')) $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_warehousesave', [ ui.container_value(ui('.modal')) ], { waitel:this })\"><span class='fa fa-check'></span><label>Simpan</label></button></td>";
  if(!$readonly && $warehouse && privilege_get('warehouse', 'modify')) $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_warehousesave', [ ui.container_value(ui('.modal')) ], { waitel:this })\"><span class='fa fa-check'></span><label>Simpan</label></button></td>";
  $actions[] = "<td><button class='hollow' onclick=\"ui.modal_close(ui('.modal'))\"><span class='fa fa-times'></span><label>Tutup</label></button></td>";

  // Tabs
  $tabs = array();

  if(count($tabs) > 0){
    $tabhead = "
      <div class='padding10 align-center'>
        <div class='tabhead' data-tabbody='.tabbody'>" . implode('', $tabs) . "</div>
      </div>
      ";
  }

  // UI HTML
  $c = "<element exp='.modal'>";
  $c .= "
  " . $tabhead . "
  <div class='scrollable padding10'>
    <div class='tabbody'>
      <!-- Tab-1 -->
      <div class='tab'>
        " . ui_control($controls['id']) . "
        <table class='form'>
          <tr>
            <th><label>Default</label></th>
            <td>" . ui_control($controls['isdefault']) . "</td>
          </tr>
          <tr>
            <th><label>Kode Gudang</label></th>
            <td>" . ui_control($controls['code']) . "</td>
          </tr>
          <tr>
            <th><label>Nama Gudang</label></th>
            <td>" . ui_control($controls['name']) . "</td>
          </tr>
          <tr>
            <th><label>Alamat</label></th>
            <td>" . ui_control($controls['address']) . "</td>
          </tr>
          <tr>
            <th><label>Kota</label></th>
            <td>" . ui_control($controls['city']) . "</td>
          </tr>
          <tr>
            <th><label>Negara</label></th>
            <td>" . ui_control($controls['country']) . "</td>
          </tr>
        </table>
      </div>
      <!-- Tab-2 -->
      <div class='tab off mutationdetail'></div>
    </div>
  </div>
  <div class='foot'>
    <table cellspacing='5'>
      <tr>
        <td style='width: 100%'></td>
        " . implode('', $actions) . "
    </table>
  </div>
  ";
  $c .= "</element>";
  $c .= uijs("
    ui.loadscript('rcfx/js/warehouse.js', \"ui.modal_open(ui('.modal'), { closeable:$closable, width:840, autoheight:1 })\");
  ");
  return $c;

}

function ui_warehousesave($obj){

  isset($obj['id']) && intval($obj['id']) > 0 ? warehousemodify($obj) : warehouseentry($obj);
  return m_load() . uijs("ui.modal_close(ui('.modal'))");

}

function ui_warehouseremove($id){

  warehouseremove(array('id'=>$id));
  return m_load();

}

function ui_warehousemove($id){

  warehousemove($id);
  return
      uijs("
        ui('#grid1').querySelector(\"tr[data-id='$id']\").querySelector('.warehousemove').innerHTML = \"<span class='fa fa-check color-green'></span>\";
      ") .
      ui_dialog('Info', 'Gudang berhasil dipindah.');

}

function ui_warehouseexport(){

  global $module;
  $preset = $module['presets'][$module['presetidx']];
  $columns = $preset['columns'];
  $sorts = $preset['sorts'];
  $quickfilters = ov('quickfilters', $preset);
  $filters = $preset['filters'];
  $filters = m_quickfilter_to_filters($filters, $quickfilters);

  $warehouse_columnaliases = array(
    'code'=>'t1.code',
    'name'=>'t1.name',
    'createdon'=>'t1.createdon',
    'moved'=>'t1.moved'
  );

  $params = array();
  $columnquery = columnquery_from_columnaliases($columns, $warehouse_columnaliases);
  $wherequery = wherequery_from_filters($params, $filters, $warehouse_columnaliases);
  $sortquery = sortquery_from_sorts($sorts, $warehouse_columnaliases);

  $query = "SELECT 'warehouse' as `type`, t1.id, $columnquery FROM warehouse t1 $wherequery $sortquery";
  $items = pmrs($query, $params);

  // Generate header
  $item = $items[0];
  $headers = array();
  foreach($item as $key=>$value)
    $headers[$key] = $key;

  $temp = array();
  $temp[] = $headers;
  foreach($items as $item)
    $temp[] = $item;
  $items = $temp;

  $filepath = 'usr/warehouse-' . date('j-M-Y') . '.xlsx';
  array_to_excel($items, $filepath);

  echo uijs("ui('#downloader').href = '$filepath';ui('#downloader').click();");

}


?>