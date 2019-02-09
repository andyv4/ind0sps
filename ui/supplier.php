<?php

require_once 'api/supplier.php';

function ui_supplierdetail($id = 0, $mode = 'read'){

  // Parameters:
  // - $obj : current item obj to open
  // - mode : read, write

  $supplier = supplierdetail(null, array('id'=>$id));
  if($mode != 'read' && $supplier && !privilege_get('supplier', 'modify')) $mode = 'read';
  if($mode == 'read' && !$supplier) throw new Exception('Supplier dengan nomor ini tidak ada.');
  $readonly = $mode == 'write' ? 0 : 1;
  $closeable = $readonly ? 1 : 0;
  $is_new = !$readonly && !$supplier ? true : false;
  if($is_new && !privilege_get('supplier', 'new')) exc("Anda tidak dapat membuat supplier.");

  $controls = array(
    'id'=>array('type'=>'hidden', 'name'=>'id', 'value'=>ov('id', $supplier)),
    'isactive'=>array('type'=>'checkbox', 'name'=>'isactive', 'readonly'=>$readonly, 'value'=>ov('isactive', $supplier, 0, 1)),
    'code'=>array('type'=>'textbox', 'name'=>'code', 'width'=>120, 'readonly'=>$readonly, 'value'=>ov('code', $supplier)),
    'description'=>array('type'=>'textbox', 'name'=>'description', 'width'=>300, 'readonly'=>$readonly, 'value'=>ov('description', $supplier)),
    'tax_registration_number'=>array('type'=>'textbox', 'name'=>'tax_registration_number', 'width'=>200, 'readonly'=>$readonly, 'value'=>ov('tax_registration_number', $supplier)),
    'address'=>array('type'=>'textarea', 'name'=>'address', 'width'=>300, 'height'=>80, 'readonly'=>$readonly, 'value'=>ov('address', $supplier)),
    'city'=>array('type'=>'textbox', 'name'=>'city', 'width'=>125, 'readonly'=>$readonly, 'value'=>ov('city', $supplier)),
    'country'=>array('type'=>'textbox', 'name'=>'country', 'width'=>125, 'readonly'=>$readonly, 'value'=>ov('country', $supplier)),
    'phone1'=>array('type'=>'textbox', 'name'=>'phone1', 'width'=>150, 'readonly'=>$readonly, 'value'=>ov('phone1', $supplier)),
    'phone2'=>array('type'=>'textbox', 'name'=>'phone2', 'width'=>150, 'readonly'=>$readonly, 'value'=>ov('phone2', $supplier)),
    'fax1'=>array('type'=>'textbox', 'name'=>'fax1', 'width'=>150, 'readonly'=>$readonly, 'value'=>ov('fax1', $supplier)),
    'fax2'=>array('type'=>'textbox', 'name'=>'fax2', 'width'=>150, 'readonly'=>$readonly, 'value'=>ov('fax2', $supplier)),
    'email'=>array('type'=>'textbox', 'name'=>'email', 'width'=>240, 'readonly'=>$readonly, 'value'=>ov('email', $supplier)),
    'contactperson'=>array('type'=>'textbox', 'name'=>'contactperson', 'width'=>240, 'readonly'=>$readonly, 'value'=>ov('contactperson', $supplier)),
    'note'=>array('type'=>'textarea', 'name'=>'note', 'width'=>300, 'height'=>80, 'readonly'=>$readonly, 'value'=>ov('note', $supplier)),
  );

  $actions = [];
  if(!$readonly && !$supplier && privilege_get('supplier', 'new')) $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_suppliersave', [ ui.container_value(ui('.modal')) ], { waitel:this })\"><span class='fa fa-check'></span><label>" . lang('002') . "</label></button></td>";
  if(!$readonly && $supplier && privilege_get('supplier', 'modify')) $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_suppliersave', [ ui.container_value(ui('.modal')) ], { waitel:this })\"><span class='fa fa-check'></span><label>" . lang('002') . "</label></button></td>";
  if($readonly && $supplier && privilege_get('supplier', 'modify')) $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_supplierdetail', [ $id, 'write' ], { waitel:this })\"><span class='fa fa-edit'></span><label>" . lang('001') . "</label></button></td>";
  $actions[] = "<td><button class='hollow' onclick=\"ui.modal_close(ui('.modal'))\"><span class='fa fa-times'></span><label>" . lang('003') . "</label></button></td>";

  $tabs = array();
  if($readonly && $supplier){
    $tabs[] = "<div class='tabitem active' onclick='ui.tabclick(event, this);supplierdetail_tabclick(0, $id)'><label>" . lang('s14') . "</label></div>";
    $tabs[] = "<div class='tabitem' onclick='ui.tabclick(event, this);supplierdetail_tabclick(1, $id)'><label>" . lang('s15') . "</label></div>";
  }

  if(count($tabs) > 0){
    $tabhead = "
      <div class='padding10 align-center'>
        <div class='tabhead' data-tabbody='.tabbody'>" . implode('', $tabs) . "</div>
      </div>
      ";
  }

  $c = "<element exp='.modal'>";

  $c .= "
    " . $tabhead . "
    <div class='scrollable'>
      <div class='tabbody'>

        <!-- Tab-1 -->
        <div class='tab'>
          <table class='form'>
            " . ui_control($controls['id']) . "
            <tr><th><label style='width:150px'>" . lang('s13') . "</label></th><td>" . ui_control($controls['isactive']) . "</td></tr>
            <tr><th><label>" . lang('s01') . "</label></th><td>" . ui_control($controls['code']) . "</td></tr>
            <tr><th><label>" . lang('s02') . "</label></th><td>" . ui_control($controls['description']) . "</td></tr>
            <tr><th><label>" . lang('s16') . "</label></th><td>" . ui_control($controls['tax_registration_number']) . "</td></tr>
            <tr><th><label>" . lang('s03') . "</label></th><td>" . ui_control($controls['address']) . "</td></tr>
            <tr><th><label>" . lang('s04') . "</label></th><td>" . ui_control($controls['city']) . "</td></tr>
            <tr><th><label>" . lang('s05') . "</label></th><td>" . ui_control($controls['country']) . "</td></tr>
            <tr><th><label>" . lang('s06') . "</label></th><td>" . ui_control($controls['phone1']) . "</td></tr>
            <tr><th><label>" . lang('s07') . "</label></th><td>" . ui_control($controls['phone2']) . "</td></tr>
            <tr><th><label>" . lang('s08') . "</label></th><td>" . ui_control($controls['fax1']) . "</td></tr>
            <tr><th><label>" . lang('s09') . "</label></th><td>" . ui_control($controls['fax2']) . "</td></tr>
            <tr><th><label>" . lang('s10') . "</label></th><td>" . ui_control($controls['email']) . "</td></tr>
            <tr><th><label>" . lang('s11') . "</label></th><td>" . ui_control($controls['contactperson']) . "</td></tr>
            <tr><th><label>" . lang('s12') . "</label></th><td>" . ui_control($controls['note']) . "</td></tr>
          </table>
        </div>

        <!-- Tab-1 -->
        <div class='tab off mutationdetail'></div>

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
		ui.loadscript('rcfx/js/supplier.js', \"ui.modal_open(ui('.modal'), { closeable:$closeable, width:800, autoheight:true });\");
	</script>
	";

  return $c;

}

function ui_suppliersave($obj){

  if(isset($obj['id']) && intval($obj['id']) > 0) suppliermodify($obj);
  else supplierentry($obj);

  return uijs("ui.modal_close(ui('.modal'))") . m_load();

}

function ui_supplierremove($id){

  supplierremove(array('id'=>$id));

  return m_load();

}

function ui_supplierdetail_mutationdetail($id){

  $obj = supplierdetail(null, array('id'=>$id));
  $items = $obj['purchaseinvoices'];

  $columns = array(
      array('active'=>1, 'name'=>'ispaid', 'text'=>'Lunas', 'width'=>25, 'type'=>'html', 'html'=>'ui_mutationlistispaid'),
      array('active'=>1, 'name'=>'date', 'text'=>'Tanggal', 'width'=>80, 'datatype'=>'date'),
      array('active'=>1, 'name'=>'code', 'text'=>'Kode', 'width'=>100),
      array('active'=>0, 'name'=>'inventorycode', 'text'=>'Kode Barang', 'width'=>80),
      array('active'=>1, 'name'=>'inventorydescription', 'text'=>'Nama Barang', 'width'=>180),
      array('active'=>1, 'name'=>'qty', 'text'=>'Kts', 'width'=>50, 'datatype'=>'number'),
      array('active'=>1, 'name'=>'unitprice', 'text'=>'Harga Satuan', 'width'=>90, 'datatype'=>'money'),
      array('active'=>1, 'name'=>'total', 'text'=>'Total', 'width'=>100, 'datatype'=>'money')
  );

  // Quick filter value
  $quickfiltervalue = array();
  $filters = array();
  $columns_indexed = array_index($columns, array('name'), 1);
  if(isset($preset['mutationdetailquickfilters']) && is_array($preset['mutationdetailquickfilters']))
    for($i = 0 ; $i < count($preset['mutationdetailquickfilters']) ; $i++){
      $filter = $preset['mutationdetailquickfilters'][$i];
      $filtername = $filter['name'];
      $filtervalue = $filter['value'];

      $filters[] = array('name'=>$filtername, 'operator'=>'contains', 'value'=>$filtervalue, 'type'=>'text');
      $quickfiltervalue[] = array('text'=>$columns_indexed[$filtername]['text'] . ' : ' . $filtervalue, 'value'=>json_encode(array('name'=>$filtername, 'value'=>$filtervalue)));
    }

  if(isset($preset['sorts']) && is_array($preset['sorts'])) data_sort($items, $preset['sorts']);
  if(isset($preset['filters']) && is_array($preset['filters'])) data_filter($items, $filters);

  $c = "<element exp='.mutationdetail'>";
  $c .= "<table cellspacing='4' style='width: 100%'><tr>";
  $c .= "<td style='width:100%'>" . ui_multicomplete(array('width'=>'100%', 'name'=>'search', 'src'=>'ui_supplierdetail_mutationdetail_quickfilter', 'placeholder'=>'Quick filter...',
          'value'=>$quickfiltervalue, 'separator'=>'|', 'onchange'=>"ui.async('ui_supplierdetail_mutationdetail_quickfilterapply', [ ui.multicomplete_value(this) ], {})")) . "</td>";
  $c .= "</tr></table>";
  $c .= ui_gridhead(array('columns'=>$columns, 'gridexp'=>'#mutationdetailgrid',
      'oncolumnresize'=>"ui_supplierdetail_mutationdetail_columnresize",
      'oncolumnclick'=>"ui_supplierdetail_mutationdetail_sortapply",
      'oncolumnapply'=>'ui_supplierdetail_mutationdetail_columnapply'));
  $c .= ui_grid(array('id'=>'mutationdetailgrid', 'columns'=>$columns, 'value'=>$items, 'scrollel'=>'.modal .scrollable'));
  $c .= "</element>";

  global $module;
  $module['mutationdetailid'] = $id;
  m_savestate($module);

  echo $c;

}
function ui_supplierdetail_mutationdetail_columnresize($name, $width){

  $module = m_loadstate();
  $preset = $module['mutationdetailpresets'][$module['mutationdetailpresetidx']];

  for($i = 0 ; $i < count($preset['columns']) ; $i++){
    if($preset['columns'][$i]['name'] == $name){
      $preset['columns'][$i]['width'] = $width;
    }
  }

  $module['mutationdetailpresets'][$module['mutationdetailpresetidx']] = $preset;
  m_savestate($module);

}
function ui_supplierdetail_mutationdetail_columnapply($columns){

  $columns = array_index($columns, array('name'), 1);
  $module = m_loadstate();
  $preset = $module['mutationdetailpresets'][$module['mutationdetailpresetidx']];

  for($i = 0 ; $i < count($preset['columns']) ; $i++){
    $name = $preset['columns'][$i]['name'];
    if(isset($columns[$name]))
      $preset['columns'][$i]['active'] = $columns[$name]['active'];
  }

  $module['mutationdetailpresets'][$module['mutationdetailpresetidx']] = $preset;
  m_savestate($module);

  return ui_supplierdetail_mutationdetail($module['mutationdetailid']);

}
function ui_supplierdetail_mutationdetail_sortapply($name){

  $module = m_loadstate();
  $preset = $module['mutationdetailpresets'][$module['mutationdetailpresetidx']];

  // If sort applied before is equal with this one, invert the sorttype
  if(isset($preset['sorts']) && count($preset['sorts']) == 1 && $preset['sorts'][0]['name'] == $name){
    $preset['sorts'][0]['sorttype'] = $preset['sorts'][0]['sorttype'] == 'desc' ? 'asc' : 'desc';
  }
  else{
    $preset['sorts'] = array();
    $preset['sorts'][] = array(
        'name'=>$name,
        'sorttype'=>'asc'
    );
  }

  $module['mutationdetailpresets'][$module['mutationdetailpresetidx']] = $preset;
  m_savestate($module);

  return ui_supplierdetail_mutationdetail($module['mutationdetailid']);

}
function ui_supplierdetail_mutationdetail_quickfilter($param0){

  $hint = $param0['hint'];
  $module = m_loadstate();
  $presetname = $module['mutationdetailpresets'][$module['mutationdetailpresetidx']];
  $columns = $presetname['columns'];
  for($i = 0 ; $i < count($columns) ; $i++){
    $column = $columns[$i];

    if(empty($column['name']) || empty($column['text'])) continue;

    $columntext = $column['text'];
    $items[] = array('text'=>"$columntext : $hint", 'value'=>json_encode(array('name'=>$column['name'], 'value'=>$hint)));
  }
  return $items;

}
function ui_supplierdetail_mutationdetail_quickfilterapply($exp){

  $quickfilters = array();
  $explodes = explode('|', $exp);
  for($i = 0 ; $i < count($explodes) ; $i++){
    $explode = $explodes[$i];
    $obj = objectToArray(json_decode($explode));
    if(is_array($obj)) $quickfilters[] = $obj;
  }

  $module = m_loadstate();
  $presetname = $module['mutationdetailpresets'][$module['mutationdetailpresetidx']];
  $presetname['mutationdetailquickfilters'] = $quickfilters;

  $module['mutationdetailpresets'][$module['mutationdetailpresetidx']] = $presetname;
  m_savestate($module);

  return ui_supplierdetail_mutationdetail($module['mutationdetailid']);

}

function ui_suppliermove($id){

  suppliermove($id);
  return
      uijs("
        ui('#grid1').querySelector(\"tr[data-id='$id']\").querySelector('.suppliermove').innerHTML = \"<span class='fa fa-check color-green'></span>\";
      ") .
      ui_dialog('Info', 'Supplier berhasil dipindah.');

}

function ui_supplierexport(){

  global $module;
  $preset = $module['presets'][$module['presetidx']];
  $columns = $preset['columns'];
  $sorts = $preset['sorts'];
  $quickfilters = ov('quickfilters', $preset);
  $filters = $preset['filters'];
  $filters = m_quickfilter_to_filters($filters, $quickfilters);

  $supplier_columnaliases = array(
    'isactive'=>'t1.isactive',
    'code'=>'t1.code',
    'description'=>'t1.description',
    'address'=>'t1.address',
    'city'=>'t1.city',
    'country'=>'t1.country',
    'payable'=>'t1.payable',
    'phone1'=>'t1.phone1',
    'phone2'=>'t1.phone2',
    'fax1'=>'t1.fax1',
    'fax2'=>'t1.fax2',
    'email'=>'t1.email',
  );

  $params = array();
  $columnquery = columnquery_from_columnaliases($columns, $supplier_columnaliases);
  $wherequery = wherequery_from_filters($params, $filters, $supplier_columnaliases);
  $sortquery = sortquery_from_sorts($sorts, $supplier_columnaliases);

  $query = "SELECT 'supplier' as `type`, t1.id, $columnquery FROM supplier t1 $wherequery $sortquery";
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

  $filepath = 'usr/supplier-' . date('j-M-Y') . '.xlsx';
  array_to_excel($items, $filepath);

  echo uijs("ui('#downloader').href = '$filepath';ui('#downloader').click();");

}

?>