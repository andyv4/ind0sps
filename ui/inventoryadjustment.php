<?php

require_once 'api/inventoryadjustment.php';

function ui_inventoryadjustmentdetail($id, $mode = 'read'){

  $obj = inventoryadjustmentdetail(null, array('id'=>$id));

  if($mode != 'read' && $obj && !privilege_get('inventoryadjustment', 'modify')) $mode = 'read';
  if(!$obj && $mode == 'read') throw new Exception('Penyesuaian dengan nomor ini tidak ada.');
  $readonly = $mode == 'write' ? 0 : 1;
  $closable = $readonly ? 1 : 0;
  $is_new = !$readonly && !$obj ? true : false;
  if($is_new && !privilege_get('inventoryadjustment', 'new')) exc("Anda tidak dapat membuat penyesuaian barang.");
  $module = m_loadstate();
  $date = ov('date', $obj);
  $code = ov('code', $obj);

  $is_new = !$obj && $mode == 'write' ? true : false;
  $date = $is_new ? date('Ymd') : $date;
  $code = $is_new ? inventoryadjustmentcode() : $code;

  $controls = array(
    'id'=>array('type'=>'hidden', 'name'=>'id', 'value'=>ov('id', $obj)),
    'date'=>array('type'=>'datepicker', 'name'=>'date', 'value'=>$date, 'readonly'=>$readonly),
    'code'=>array('type'=>'textbox', 'name'=>'code', 'value'=>$code, 'width'=>100, 'readonly'=>$readonly),
    'description'=>array('type'=>'textbox', 'name'=>'description', 'value'=>ov('description', $obj), 'width'=>300, 'readonly'=>$readonly),
    'warehouseid'=>array('type'=>'dropdown', 'name'=>'warehouseid', 'value'=>ov('warehouseid', $obj), 'width'=>150, 'items'=>array_cast(warehouselist(), array('text'=>'name', 'value'=>'id')), 'readonly'=>$readonly)
  );

  $detailcolumns = array(
      array('active'=>1, 'name'=>'col0', 'text'=>'Barang', 'type'=>'html', 'html'=>'ui_inventoryadjustmentdetail_col0', 'width'=>360),
      array('active'=>1, 'name'=>'col1', 'text'=>'Kuantitas', 'type'=>'html', 'html'=>'ui_inventoryadjustmentdetail_col1', 'width'=>50),
      array('active'=>1, 'name'=>'col2', 'text'=>'Satuan', 'type'=>'html', 'html'=>'ui_inventoryadjustmentdetail_col2', 'width'=>50),
      array('active'=>1, 'name'=>'col2', 'text'=>'Harga Satuan', 'type'=>'html', 'html'=>'ui_inventoryadjustmentdetail_col3', 'width'=>100),
      array('active'=>1, 'name'=>'col2', 'text'=>'Catatan', 'type'=>'html', 'html'=>'ui_inventoryadjustmentdetail_col4', 'width'=>100),
      array('active'=>1, 'name'=>'col3', 'text'=>'', 'type'=>'html', 'html'=>'ui_inventoryadjustmentdetail_col5', 'width'=>24),
  );

  // Action Controls
  $actions = array();
  if($readonly && $obj && privilege_get('inventoryadjustment', 'modify')) $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_inventoryadjustmentdetail', [ $id, 'write' ], { waitel:this })\"><span class='fa fa-edit'></span><label>" . lang('001') . "</label></button></td>";
  if(!$readonly && !$obj && privilege_get('inventoryadjustment', 'new')) $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_inventoryadjustmentsave', [ ui.container_value(ui('.modal')) ], { waitel:this })\"><span class='fa fa-check'></span><label>" . lang('002') . "</label></button></td>";
  if(!$readonly && $obj && privilege_get('inventoryadjustment', 'modify')) $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_inventoryadjustmentsave', [ ui.container_value(ui('.modal')) ], { waitel:this })\"><span class='fa fa-check'></span><label>" . lang('002') . "</label></button></td>";
  $actions[] = "<td><button class='hollow' onclick=\"ui.modal_close(ui('.modal'))\"><span class='fa fa-times'></span><label>Close</label></button></td>";

  $c = "<element exp='.modal'>";
  $c .= "
    <div class='scrollable padding1020'>
      " . ui_control($controls['id']) . "
      <table class='form'>
        " . ui_formrow('Tanggal', ui_control($controls['date'])) . "
        " . ui_formrow('Kode', ui_control($controls['code'])) . "
        " . ui_formrow('Deskripsi', ui_control($controls['description'])) . "
        " . ui_formrow('Gudang', ui_control($controls['warehouseid'])) . "
      </table>
      <div>
        " . ui_gridhead(array('columns'=>$detailcolumns)) . "
        " . ui_grid(array('columns'=>$detailcolumns, 'name'=>'details', 'value'=>ov('details', $obj), 'mode'=>'write', 'readonly'=>$readonly, 'id'=>'details')) . "
      </div>
    </div>
    <div class='foot'>
      <table cellspacing='5'>
        <tr>
          <td style='width: 99%'></td>
          " . implode('', $actions) . "
        </tr>
      </table>
    </div>
	";
  $c .= "</element>";
  $c .= "
	<script>
		ui.loadscript('rcfx/js/inventoryadjustment.js', \"ui.modal_open(ui('.modal'), { closeable:$closable, width:900, autoheight:true });\");
	</script>
	";
  return $c;

}

function ui_inventoryadjustmentdetail_columnresize($name, $width){

  $module = m_loadstate();
  $preset = $module['detailpresets'][$module['detailpresetidx']];

  for($i = 0 ; $i < count($preset['columns']) ; $i++){
    if($preset['columns'][$i]['name'] == $name){
      $preset['columns'][$i]['width'] = $width;
    }
  }

  $module['detailpresets'][$module['detailpresetidx']] = $preset;
  m_savestate($module);

}

function ui_inventoryadjustmentdetail_col0($obj, $params){

  $inventorycode = ov('inventorycode', $obj);
  $inventorydescription = ov('inventorydescription', $obj);
  $text = '';
  if(strlen($inventorycode) > 0)
    $text = $inventorycode . ' - ' . $inventorydescription;

  return ui_autocomplete(array(
      'name'=>'inventoryid',
      'src'=>'ui_inventoryadjustmentdetail_col0_completion',
      'text'=>$text,
      'value'=>ov('inventoryid', $obj),
      'readonly'=>$params['readonly'],
      'width'=>'98%',
      'onchange'=>"inventoryadjustment_inventorychange(value, this)"
  ));

}

function ui_inventoryadjustmentdetail_col0_completion($param){

  $hint = ov('hint', $param);
  $items = pmrs("SELECT `id`, code, description FROM inventory WHERE isactive = 1 AND (code LIKE ? OR description LIKE ?)", array("%$hint%", "%$hint%"));
  $temp = [];
  if(is_array($items))
    for($i = 0 ; $i < count($items) ; $i++){
      $obj = [];
      $obj['text'] = $items[$i]['code'] . ' - ' . $items[$i]['description'];
      $obj['value'] = $items[$i]['id'];
      $temp[] = $obj;
    }
  $items = $temp;
  return $items;

}

function ui_inventoryadjustmentdetail_col0_completion2($inventoryid, $trid){

  $inventory = inventorydetail(null, array('id'=>$inventoryid));
  $unit = isset($inventory['unit']) ? $inventory['unit'] : '';
  return uijs("ui.label_setvalue(ui('%unit', ui('$" . $trid . "')), \"$unit\")");

}

function ui_inventoryadjustmentdetail_col1($obj, $params){

  return ui_textbox(array(
      'name'=>'qty',
      'value'=>ov('qty', $obj),
      'readonly'=>$params['readonly'],
      'class'=>'block',
      'onchange'=>'inventoryadjustmentdetail_rowcalculate(this)'
  ));

}

function ui_inventoryadjustmentdetail_col2($obj, $params){

  return ui_label(array(
    'name'=>'unit',
    'value'=>ov('unit', $obj),
    'readonly'=>$params['readonly'],
    'class'=>'block',
  ));

}

function ui_inventoryadjustmentdetail_col3($obj, $params){

  return ui_textbox(array(
    'name'=>'unitprice',
    'value'=>ov('unitprice', $obj),
    'readonly'=>$params['readonly'],
    'class'=>'block',
    'datatype'=>'money',
    'onchange'=>'inventoryadjustmentdetail_rowcalculate(this)'
  ));

}

function ui_inventoryadjustmentdetail_col4($obj, $params){

  return ui_textbox(array(
    'name'=>'remark',
    'value'=>ov('remark', $obj),
  'readonly'=>$params['readonly'],
  'class'=>'block',
    'onchange'=>'inventoryadjustmentdetail_rowcalculate(this)'
  ));

}

function ui_inventoryadjustmentdetail_col5($obj, $params){

  if(!$params['readonly'])
    return "<div class='align-center'><span class='fa fa-times-circle color-red' onclick=\"ui.grid_remove(this.parentNode.parentNode.parentNode)\"></span></div>";
  return '';

}

function ui_inventoryadjustmentsave($obj){

  isset($obj['id']) && intval($obj['id']) > 0 ? inventoryadjustmentmodify($obj) : inventoryadjustmententry($obj);
  return m_load() . uijs("ui.modal_close(ui('.modal'))");

}

function ui_inventoryadjustmentremove($id){

  inventoryadjustmentremove(array('id'=>$id));
  return m_load();

}

function ui_inventoryadjustmentexport(){
  global $module;
  $preset = $module['presets'][$module['presetidx']];
  $columns = $preset['columns'];
  $sorts = $preset['sorts'];
  $quickfilters = ov('quickfilters', $preset);
  $filters = $preset['filters'];
  $filters = m_quickfilter_to_filters($filters, $quickfilters);

  $columnaliases = array(
    'id'=>'t1.id',
    'warehouseid'=>'t1.warehouseid',
    'warehousename'=>'t3.name as warehousename',
    'code'=>'t1.code',
    'date'=>'t1.date',
    'description'=>'t1.description',
    'inventoryid'=>'t2.inventoryid',
    'inventorycode'=>'t2.inventorycode',
    'inventorydescription'=>'t2.inventorydescription',
    'qty'=>'t2.qty',
    'unit'=>'t2.unit',
    'unitprice'=>'t2.unitprice',
    'amount'=>'t2.amount',
    'remark'=>'t2.remark',
    'createdon'=>'t1.createdon'
  );

  $params = array();
  $columnquery = columnquery_from_columnaliases($columns, $columnaliases);
  $wherequery = 'WHERE t1.id = t2.inventoryadjustmentid AND t1.warehouseid = t3.id ' . str_replace('WHERE', 'AND', wherequery_from_filters($params, $filters, $columnaliases));
  $sortquery = sortquery_from_sorts($sorts, $columnaliases);
  $limitquery = limitquery_from_limitoffset($limits);

  $query = "SELECT 'inventoryadjustment' as `type`, t1.id, $columnquery
    FROM inventoryadjustment t1, inventoryadjustmentdetail t2, warehouse t3 $wherequery $sortquery";
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

  $filepath = 'usr/inventory-adjustment_' . date('j-M-Y') . '.xlsx';
  array_to_excel($items, $filepath);

  echo uijs("ui('#downloader').href = '$filepath';ui('#downloader').click();");

}

?>