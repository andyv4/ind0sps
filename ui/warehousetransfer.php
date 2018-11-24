     <?php

require_once 'api/warehousetransfer.php';

function ui_warehousetransferdetail($id, $mode = 'read'){

  $obj = warehousetransferdetail(null, array('id'=>$id));

  if($mode != 'read' && $obj && !privilege_get('warehousetransfer', 'modify')) $mode = 'read';
  if($mode == 'read' && !$obj) throw new Exception('Pemindahan gudang dengan nomor ini tidak ada.');
  $readonly = $mode == 'write' ? 0 : 1;
  $closable = $readonly ? 1 : 0;
  $is_new = !$readonly && !$obj ? true : false;
  if($is_new && !privilege_get('warehousetransfer', 'new')) exc("Anda tidak dapat membuat pindah gudang.");
  $code = ov('code', $obj);
  $date = ov('date', $obj);

  $is_new = !$obj && $mode == 'write' ? true : false;
  $date = $is_new ? date('Ymd') : $date;
  $code = $is_new ? warehousetransfercode() : $code;

  $controls = array(
    'id'=>array('type'=>'hidden', 'name'=>'id', 'value'=>ov('id', $obj)),
    'date'=>array('type'=>'datepicker', 'name'=>'date', 'value'=>$date, 'readonly'=>$readonly),
    'code'=>array('type'=>'textbox', 'name'=>'code', 'value'=>$code, 'width'=>100, 'readonly'=>$readonly),
    'description'=>array('type'=>'textarea', 'name'=>'description', 'value'=>ov('description', $obj), 'width'=>300, 'height'=>60, 'readonly'=>$readonly),
    'fromwarehouseid'=>array('type'=>'dropdown', 'name'=>'fromwarehouseid', 'value'=>ov('fromwarehouseid', $obj), 'width'=>150, 'items'=>array_cast(warehouselist(), array('text'=>'name', 'value'=>'id')), 'readonly'=>$readonly),
    'towarehouseid'=>array('type'=>'dropdown', 'name'=>'towarehouseid', 'value'=>ov('towarehouseid', $obj), 'width'=>150, 'items'=>array_cast(warehouselist(), array('text'=>'name', 'value'=>'id')), 'readonly'=>$readonly),
  );

  $columns = array(
      array('active'=>1, 'name'=>'col4', 'text'=>'Kode', 'type'=>'html', 'html'=>'ui_warehousetransferdetail_col5', 'width'=>80),
      array('active'=>1, 'name'=>'col0', 'text'=>'Barang', 'type'=>'html', 'html'=>'ui_warehousetransferdetail_col0', 'width'=>300),
      array('active'=>1, 'name'=>'col1', 'text'=>'Kuantitas', 'type'=>'html', 'html'=>'ui_warehousetransferdetail_col1', 'width'=>60),
      array('active'=>1, 'name'=>'col1', 'text'=>'Stok', 'type'=>'html', 'html'=>'ui_warehousetransferdetail_col4', 'width'=>60, 'align'=>'center'),
      array('active'=>1, 'name'=>'col2', 'text'=>'Catatan', 'type'=>'html', 'html'=>'ui_warehousetransferdetail_col2', 'width'=>120),
      array('active'=>1, 'name'=>'col3', 'text'=>'', 'type'=>'html', 'html'=>'ui_warehousetransferdetail_col3', 'width'=>24),
  );

  // Action Controls
  $actions = array();
  if($readonly && $obj && privilege_get('warehousetransfer', 'modify')) $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_warehousetransferdetail', [ $id, 'write' ], { waitel:this })\"><span class='fa fa-edit'></span><label>" . lang('001') . "</label></button></td>";
  if(!$readonly && !$obj && privilege_get('warehousetransfer', 'new')) $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_warehousetransfersave', [ ui.container_value(ui('.modal')) ], { waitel:this })\"><span class='fa fa-check'></span><label>" . lang('002') . "</label></button></td>";
  if(!$readonly && $obj && privilege_get('warehousetransfer', 'modify')) $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_warehousetransfersave', [ ui.container_value(ui('.modal')) ], { waitel:this })\"><span class='fa fa-check'></span><label>" . lang('002') . "</label></button></td>";
  $actions[] = "<td><button class='hollow' onclick=\"ui.modal_close(ui('.modal'))\"><span class='fa fa-times'></span><label>Close</label></button></td>";

  $c = "<element exp='.modal'>";
  $c .= "
  <div class='scrollable padding1020'>
      " . ui_control($controls['id']) . "
    <table class='form'>
      " . ui_formrow('Kode', ui_control($controls['code'])) . "
      " . ui_formrow('Tanggal', ui_control($controls['date'])) . "
      " . ui_formrow('Deskripsi', ui_control($controls['description'])) . "
      " . ui_formrow('Gudang Asal', ui_control($controls['fromwarehouseid'])) . "
      " . ui_formrow('Gudang Tujuan', ui_control($controls['towarehouseid'])) . "
    </table>
    <div>
      " . ui_gridhead(array('columns'=>$columns)) . "
      " . ui_grid(array('columns'=>$columns, 'name'=>'inventories', 'value'=>ov('inventories', $obj), 'mode'=>'write', 'readonly'=>$readonly, 'id'=>'inventories')) . "
    </div>
  </div>
  <div class='foot'>
    <table cellspacing='5'>
      <tr>
        <td style='width: 100%'></td>
        " . implode('', $actions) . "
      </tr>
    </table>
  </div>
	";
  $c .= "</element>";
  $c .= "
	<script>
		ui.loadscript('rcfx/js/warehousetransfer.js', \"ui.modal_open(ui('.modal'), { closeable:$closable, width:840, autoheight:true });\");
	</script>
	";
  return $c;

}

function ui_warehousetransferdetail_columnresize($name, $width){

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

function ui_warehousetransferdetail_col0($obj, $params){

  return ui_autocomplete(array(
    'name'=>'inventorydescription',
    'src'=>'ui_warehousetransferdetail_col0_completion',
    'value'=>ov('inventorydescription', $obj),
    'readonly'=>$params['readonly'],
    'onchange'=>"ui_warehousetransferdetail_col0_posthint(obj, this)",
    'prehint'=>"ui_warehousetransferdetail_col0_prehint",
    'width'=>'99%'
  ));

}

function ui_warehousetransferdetail_col0_completion($param){

  $warehouseid = $param['param'];
  $date = $param['param1'];

  $hint = ov('hint', $param);

  if($warehouseid > 0){
    $items = pmrs("SELECT t1.code, t1.description, ((SELECT SUM(`in` - `out`) FROM inventorybalance WHERE `date` <= ? AND warehouseid = ? AND inventoryid = t1.id)) as current_qty FROM inventory t1 WHERE t1.isactive = 1 AND (t1.code LIKE ? OR t1.description LIKE ?)", array($date, $warehouseid, "%$hint%", "%$hint%"));
  }
  else{
    $items = pmrs("SELECT t1.code, t1.description, '-' as current_qty FROM inventory t1 WHERE t1.isactive = 1 AND (t1.code LIKE ? OR t1.description LIKE ?)", array("%$hint%", "%$hint%"));
  }
  $items = array_cast($items, array('text'=>'description', 'value'=>'description', 'code'=>'code', 'current_qty'=>'current_qty'));

  return $items;

}

function ui_warehousetransferdetail_col1($obj, $params){

  return ui_textbox(array(
      'name'=>'qty',
      'value'=>ov('qty', $obj),
      'readonly'=>$params['readonly'],
      'class'=>'block',
      'onchange'=>''
  ));

}

function ui_warehousetransferdetail_col2($obj, $params){

  return ui_textbox(array(
      'name'=>'remark',
      'value'=>ov('remark', $obj),
      'readonly'=>$params['readonly'],
      'class'=>'block',
      'onchange'=>'a(value, this)'
  ));

}

function ui_warehousetransferdetail_col3($obj, $params){

  if(!$params['readonly'])
    return "<div class='align-center'><span class='fa fa-times-circle color-red' onclick=\"ui.grid_remove(this.parentNode.parentNode.parentNode)\"></span></div>";
  return '';

}

function ui_warehousetransferdetail_col4($obj, $params){

  return "<div class='align-center'>" . ui_label(array('name'=>'current_qty', 'onclick'=>"ui_warehousetransferdetail_currentqty_click(this)")) . "</div>";

}

function ui_warehousetransferdetail_col5($obj){

  return "<div class='align-center'>" . ui_label(array('name'=>'inventorycode', 'value'=>ov('inventorycode', $obj))) . "</div>";

}

function ui_warehousetransfersave($obj){

  isset($obj['id']) && intval($obj['id']) > 0 ? warehousetransfermodify($obj) : warehousetransferentry($obj);
  return m_load() . uijs("ui.modal_close(ui('.modal'))");

}

function ui_warehousetransferremove($id){

  warehousetransferremove(array('id'=>$id));
  return m_load();

}

 function ui_warehousetransferexport(){

   global $module;
   $preset = $module['presets'][$module['presetidx']];
   $columns = $preset['columns'];
   $sorts = $preset['sorts'];
   $quickfilters = ov('quickfilters', $preset);
   $filters = $preset['filters'];
   $filters = m_quickfilter_to_filters($filters, $quickfilters);

   $columnaliases = array(
     'id'=>'t1.id',
     'code'=>'t1.code',
     'date'=>'t1.date',
     'description'=>'t1.description',
     'inventoryid'=>'t2.inventoryid',
     'inventorycode'=>'t5.code as inventorycode',
     'inventorydescription'=>'t5.description as inventorydescription',
     'fromwarehouseid'=>'t1.fromwarehouseid',
     'towarehouseid'=>'t1.towarehouseid',
     'fromwarehousename'=>'t3.name as fromwarehousename',
     'towarehousename'=>'t4.name as towarehousename',
     'totalqty'=>'t1.totalqty',
     'inventoryid'=>'t2.inventoryid',
     'qty'=>'t2.qty',
     'unit'=>'t5.unit',
     'remark'=>'t2.remark',
     'createdon'=>'t1.createdon'
   );

   $params = array();
   $columnquery = columnquery_from_columnaliases($columns, $columnaliases);
   $wherequery = 'WHERE t1.id = t2.warehousetransferid AND t1.fromwarehouseid = t3.id AND t1.towarehouseid = t4.id AND t2.inventoryid = t5.id ' .
     str_replace('WHERE', 'AND', wherequery_from_filters($params, $filters, $columnaliases));
   $sortquery = sortquery_from_sorts($sorts, $columnaliases);

   if(strlen($columnquery) > 0) $columnquery = ', ' . $columnquery;

   $query = "SELECT 'warehousetransfer' as `type`, t1.id $columnquery
    FROM warehousetransfer t1, warehousetransferinventory t2, warehouse t3, warehouse t4, inventory t5 $wherequery $sortquery";
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

   $filepath = 'usr/warehouse-transfer-' . date('j-M-Y') . '.xlsx';
   array_to_excel($items, $filepath);

   echo uijs("ui('#downloader').href = '$filepath';ui('#downloader').click();");

 }


?>