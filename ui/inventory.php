<?php

function ui_inventorydetail($id, $mode = 'read'){

  // Inventory Object
  $inventory = inventorydetail(null, array('id'=>$id));

  if($mode != 'read' && $inventory && !privilege_get('inventory', 'modify')) $mode = 'read';

  $categories = $inventory['categories'];
  if(strlen($categories) > 0)
    $categories = pmrs("select `name` as `text`, `id` as `value` from category where `id` in ($categories)");
  if($mode == 'read' && !$inventory) throw new Exception('Barang dengan nomor ini tidak ada.');
  $costprice_allowed = privilege_get('inventory', 'costprice');
  $taxable = ov('taxable', $inventory, 0, 0);

  // Controls
  $readonly = $mode == 'write' ? 0 : 1;
  $closable = $readonly ? 1 : 0;
  $is_new = !$readonly && !$inventory ? true : false;
  if($is_new && !privilege_get('inventory', 'new')) exc("Anda tidak dapat membuat barang.");
  $controls = array(
    'id'=>array('type'=>'hidden', 'name'=>'id', 'value'=>ov('id', $inventory)),
    'isactive'=>array('type'=>'checkbox', 'name'=>'isactive', 'value'=>ov('isactive', $inventory, 0, 1), 'readonly'=>$readonly),
    'taxable'=>array('type'=>'checkbox', 'name'=>'taxable', 'value'=>$taxable, 'readonly'=>$readonly, 'onchange'=>"inventorydetail_taxable_changed(name, value)"),
    'taxable_excluded'=>array('type'=>'checkbox', 'name'=>'taxable_excluded', 'value'=>ov('taxable_excluded', $inventory, 0, 0), 'readonly'=>$readonly, 'text'=>'PPn 0 Rp.'),
    'code'=>array('type'=>'textbox', 'name'=>'code', 'value'=>ov('code', $inventory), 'width'=>100, 'readonly'=>$readonly),
    'description'=>array('type'=>'textbox', 'name'=>'description', 'value'=>ov('description', $inventory), 'width'=>300, 'readonly'=>$readonly),
    'fulldescription'=>array('type'=>'textarea', 'name'=>'fulldescription', 'value'=>ov('fulldescription', $inventory), 'width'=>400, 'height'=>80, 'readonly'=>$readonly),
    'unit'=>array('type'=>'textbox', 'name'=>'unit', 'value'=>ov('unit', $inventory), 'width'=>100, 'readonly'=>$readonly),
    'price'=>array('type'=>'textbox', 'name'=>'price', 'value'=>ov('price', $inventory), 'width'=>150, 'readonly'=>$readonly),
    'lowestprice'=>array('type'=>'textbox', 'name'=>'lowestprice', 'value'=>ov('lowestprice', $inventory), 'width'=>150, 'readonly'=>$readonly),
    'handlingfeeunit'=>array('type'=>'textbox', 'name'=>'handlingfeeunit', 'value'=>ov('handlingfeeunit', $inventory), 'width'=>150, 'readonly'=>$readonly),
    'website_isactive'=>array('type'=>'checkbox', 'name'=>'website_isactive', 'value'=>ov('website_isactive', $inventory), 'readonly'=>$readonly),
    'imageurl'=>array('type'=>'image', 'name'=>'imageurl', 'src'=>ov('imageurl', $inventory), 'width'=>120, 'height'=>120),
    'uploader'=>array('type'=>'upload', 'src'=>'ui_inventorydetail_imageupload', 'id'=>'', 'text'=>'Pilih Gambar'),
    'categories'=>array('type'=>'multicomplete', 'name'=>'categories', 'src'=>'ui_inventorycategoryhint', 'width'=>400, 'readonly'=>$readonly, 'value'=>$categories)
  );

  global $module;
  $preset = $module['mutationdetailpresets'][$module['mutationdetailpresetidx']];
  $obj = inventorydetail([ 'detail' ], array('id'=>$id));
  $items = $obj['detail'];

  $columns = array(
    array('active'=>1, 'name'=>'warehousename', 'text'=>'Gudang', 'width'=>100),
    array('active'=>1, 'name'=>'date', 'text'=>'Tanggal', 'width'=>100, 'datatype'=>'date'),
    array('active'=>1, 'name'=>'description', 'text'=>'Pelanggan', 'width'=>200),
    array('active'=>1, 'name'=>'in', 'text'=>'Masuk', 'width'=>60, 'datatype'=>'number'),
    array('active'=>1, 'name'=>'out', 'text'=>'Keluar', 'width'=>60, 'datatype'=>'number'),
    array('active'=>1, 'name'=>'unitamount', 'text'=>'Harga Satuan', 'width'=>100, 'datatype'=>'money')
  );

  global $ui_inventorydetail_costpricedetail_columns;

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

  $actions = array();
  if($readonly && $inventory && privilege_get('inventory', 'modify')) $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_inventorydetail', [ $id, 'write' ], { waitel:this })\"><span class='fa fa-edit'></span><label>Ubah</label></button></td>";
  if(!$readonly && !$inventory && privilege_get('inventory', 'new')) $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_inventorysave', [ ui.container_value(ui('.modal')) ], { waitel:this })\"><span class='fa fa-check'></span><label>Simpan</label></button></td>";
  if(!$readonly && $inventory && privilege_get('inventory', 'modify')) $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_inventorysave', [ ui.container_value(ui('.modal')) ], { waitel:this })\"><span class='fa fa-check'></span><label>Simpan</label></button></td>";
  $actions[] = "<td><button class='hollow' onclick=\"ui.modal_close(ui('.modal'))\"><span class='fa fa-times'></span><label>Tutup</label></button></td>";

  $md_startdate = ov('mutationdetailstartdate', $preset, 0, date('Ym') . '01');
  $md_enddate = ov('mutationdetailenddate', $preset, 0, date('Ymd'));

  $costpricefilter1 = [
    [ 'text'=>'3 Months',  'value'=>'3-months' ],
    [ 'text'=>'This Year',  'value'=>'this-year' ],
    [ 'text'=>'All',  'value'=>'all' ],
  ];

  // UI HTML
  $c = "<element exp='.modal'>";
  $c .= "
  <div class='padding10 align-center'>
    <div class='tabhead' data-tabbody='.tabbody'>
      <div class='tabitem active' onclick='ui.tabclick(event, this);'><label>Detil Barang</label></div>
      <div class='tabitem' onclick=\"ui.tabclick(event, this);ui.async('ui_inventorydetail_mutationdetail', [ $id ])\"><label>Mutasi</label></div>
      " . ($costprice_allowed ? "<div class='tabitem' style='display:none' onclick=\"ui.tabclick(event, this);ui.async('ui_inventorydetail_costpricedetail', [ $id ])\"><label>Harga Modal</label></div>" : '') . "
    </div>
  </div>
  <div id='id_scrollable' class='scrollable padding10'>
    <div class='tabbody'>
      <!-- Tab-1 -->
      <div class='tab'>
        " . ui_control($controls['id']) . "
        <table class='form'>
          <tr>
            <th><label>Aktif</label></th>
            <td>" . ui_control($controls['isactive']) . "</td>
          </tr>
          <tr>
            <th><label>Barang Kena Pajak</label></th>
            <td>
              " . ui_control($controls['taxable']) . "
              <span class='width50'>&nbsp;</span>
              " . ui_control($controls['taxable_excluded']) . "
            </td>
          </tr>
          <tr>
            <th><label>Kode Barang</label></th>
            <td>" . ui_control($controls['code']) . "</td>
          </tr>
          <tr>
            <th><label>Nama Barang</label></th>
            <td>" . ui_control($controls['description']) . "</td>
          </tr>
          <tr>
            <th><label>Deskripsi Barang</label></th>
            <td>" . ui_control($controls['fulldescription']) . "</td>
          </tr>
          <tr>
            <th><label>Satuan</label></th>
            <td>" . ui_control($controls['unit']) . "</td>
          </tr>
          <tr>
            <th><label>Harga Satuan</label></th>
            <td>" . ui_control($controls['price']) . "</td>
          </tr>
          <tr>
            <th><label>Harga Jual Terendah</label></th>
            <td>" . ui_control($controls['lowestprice']) . "</td>
          </tr>
          <tr>
            <th><label>Handling Fee Unit Price</label></th>
              <td>" . ui_control($controls['handlingfeeunit']) . "</td>
          </tr>
          <tr><td>&nbsp;</td></tr>
          <tr>
            <th><label>Website</label></th>
              <td>" . ui_control($controls['website_isactive']) . "</td>
          </tr>
          <tr>
            <th><label>Kategori</label></th>
              <td>" . ui_control($controls['categories']) . "</td>
          </tr>
          <tr>
            <th><label>Gambar</label></th>
            <td>
              " . ui_control($controls['imageurl']) . "
              <div class='height10'></div>
              " . (!$readonly ? ui_control($controls['uploader']). "<button class='hollow' onclick=\"ui.image_setvalue(ui('%imageurl'), '')\"><span class='fa fa-times'></span></button>" : '') . "
            </td>
          </tr>
        </table>
      </div>
      <!-- Tab-2 -->
      <div class='tab off mutationdetail'>        
        <table id='mutationdetail_row0' cellspacing='4' style='width: 100%'>
          <tr>
            <td>" . ui_datepicker(array('id'=>'md_startdate', 'value'=>$md_startdate, 'onchange'=>"ui.async('ui_inventorydetail_mutationdetail_datechanged', [ $('#md_startdate').val(), $('#md_enddate').val() ], { waitel:this })")) . "</td>
            <td>" . ui_datepicker(array('id'=>'md_enddate', 'value'=>$md_enddate, 'onchange'=>"ui.async('ui_inventorydetail_mutationdetail_datechanged', [ $('#md_startdate').val(), $('#md_enddate').val() ], { waitel:this })")) . "</td>
            <td style='width:100%'>
              " . ui_multicomplete(array('width'=>'100%', 'name'=>'search', 'src'=>'ui_inventorydetail_mutationdetail_quickfilter', 'placeholder'=>'Quick filter...', 'value'=>$quickfiltervalue, 'separator'=>'|', 'onchange'=>"ui.async('ui_inventorydetail_mutationdetail_quickfilterapply', [ ui.multicomplete_value(this) ], {})")) . "
            </td>
            <td><button class='blue' onclick=\"ui.async('ui_inventorydetail_mutationdetail_export', [$id], { waitel:this })\" data-tooltip='Export'><span class='icon fa fa-download'></span></button></td>
          </tr>
        </table>
        <div class='height5'></div>
        " . ui_gridhead(array('id'=>'mutationdetail_gridhead', 'columns'=>$columns, 'gridexp'=>'#mutationdetailgrid',
            'oncolumnresize'=>"ui_inventorydetail_mutationdetail_columnresize",
            'oncolumnclick'=>"ui_inventorydetail_mutationdetail_sortapply",
            'oncolumnapply'=>'ui_inventorydetail_mutationdetail_columnapply')) . "
        <div id='scrollable9' class='scrollable'></div>      
      </div>
      <!-- Tab-3 -->
      <div class='tab off cont10'>
        <table id='costpricedetail_row0' cellspacing='4' style='width: 100%'>
          <tr>
            <td>" . ui_dropdown(array('id'=>'md_startdate', 'value'=>$md_startdate, 'items'=>$costpricefilter1, 'onchange'=>"ui.async('ui_inventorydetail_mutationdetail_datechanged', [ $('#md_startdate').val(), $('#md_enddate').val() ], { waitel:this })")) . "</td>
             <td style='width:100%'></td>
          </tr>
        </table>
      " . ui_gridhead(array('id'=>'costpricedetail_gridhead', 'columns'=>$ui_inventorydetail_costpricedetail_columns, 'gridexp'=>'#costpricegrid')) . " 
        <div id='scrollable10' class='scrollable'></div>
      </div>
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
    ui.loadscript('rcfx/js/inventory.js', \"ui.modal_open(ui('.modal'), { closeable:$closable, width:840, autoheight:1 });\");
  ");
  return $c;

}
function ui_inventorysave($obj){

  isset($obj['id']) && intval($obj['id']) > 0 ? inventorymodify($obj) : inventoryentry($obj);
  return m_load() . uijs("ui.modal_close(ui('.modal'))");

}
function ui_inventoryremove($id){

  inventoryremove(array('id'=>$id));
  return m_load();

}
function ui_inventoryexport(){

  global $module;
  $preset = $module['presets'][$module['presetidx']];
  $columns = $preset['columns'];
  $sorts = $preset['sorts'];
  $quickfilters = ov('quickfilters', $preset);
  $filters = $preset['filters'];
  $filters = m_quickfilter_to_filters($filters, $quickfilters);

  $items = inventorylist($columns, $sorts, $filters, null);

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

  foreach($items as $index=>$item){
    unset($items[$index]['type']);
    unset($items[$index]['id']);
  }

  $filepath = 'usr/inventory-' . date('j-M-Y') . '.xlsx';
  array_to_excel($items, $filepath);

  echo uijs("ui('#downloader').href = '$filepath';ui('#downloader').click();");

}

function ui_inventoryqty($id){

  $detail = json_decode(pmc("select `detail` from inventorybalance where inventoryid = ?
    order by `date` desc, `section` desc, `id` desc limit 1", [ $id ]), true);
  $items = ov('items', $detail, 0, []);

  $columns = [
    [ 'active'=>1, 'text'=>'Tanggal', 'name'=>'date', 'width'=>100, 'datatype'=>'date' ],
    [ 'active'=>1, 'text'=>'Kts', 'name'=>'qty', 'width'=>60, 'datatype'=>'number', 'nodittomark'=>1 ],
    [ 'active'=>1, 'text'=>'Harga', 'name'=>'price', 'width'=>100, 'datatype'=>'money', 'nodittomark'=>1 ],
  ];

  $gridhead = [
    'columns'=>$columns
  ];

  $grid = [
    'id'=>'inventoryqtygrid',
    'columns'=>$columns,
    'value'=>$items
  ];

  // UI HTML
  $c = "<element exp='.modal'>";
  $c .= "
  <div>
    " . ui_gridhead($gridhead) . "
  </div>
  <div class='scrollable' style='height:240px'>
    " . ui_grid($grid) . "
  </div>
  
  <div class='foot'>
    <table cellspacing='5'>
      <tr>
        <td style='width: 100%'></td>
        <td><button class='hollow' onclick=\"ui.modal_close(ui('.modal'))\"><span class='fa fa-times'></span><label>Tutup</label></button></td>
    </table>
  </div>
  
  ";
  $c .= "</element>";
  $c .= uijs("
    ui.loadscript('rcfx/js/inventory.js', \"ui.modal_open(ui('.modal'), { closeable:1, width:480 })\");
  ");
  return $c;

}
function ui_inventorydetail_imageupload($params){

  $url = "usr/img/" . $params['filename'];
  echo uijs("ui.control_setvalue(ui('%imageurl'), '$url')");
  
}
function ui_inventorycategoryhint($params){

  $hint = $params['hint'];

  $categories = pmrs("select * from category where `name` like ?", array("%$hint%"));
  $categories = array_cast($categories, array('text'=>'name', 'value'=>'id'));
  return $categories;

}

function ui_inventorydetail_mutationdetail($id){

  global $module;
  $preset = $module['mutationdetailpresets'][$module['mutationdetailpresetidx']];
  $mutationdetailquickfilters = $preset['mutationdetailquickfilters'];
  $mutationdetailstartdate = ov('mutationdetailstartdate', $preset, 0, date('Ym') . '01');
  $mutationdetailenddate = ov('mutationdetailenddate', $preset, 0, date('Ymd'));

  $columns = array(
    array('active'=>1, 'name'=>'warehousename', 'text'=>'Gudang', 'width'=>100),
    array('active'=>1, 'name'=>'date', 'text'=>'Tanggal', 'width'=>100, 'datatype'=>'date'),
    array('active'=>1, 'name'=>'description', 'text'=>'Pelanggan', 'width'=>200),
    array('active'=>1, 'name'=>'in', 'text'=>'Masuk', 'width'=>60, 'datatype'=>'number'),
    array('active'=>1, 'name'=>'out', 'text'=>'Keluar', 'width'=>60, 'datatype'=>'number'),
    array('active'=>1, 'name'=>'unitamount', 'text'=>'Harga Satuan', 'width'=>100, 'datatype'=>'money')
  );

  $filters = [
    [ 'name'=>'inventoryid', 'operator'=>'=', 'value'=>$id ],
    [ 'name'=>'date', 'operator'=>'>=', 'value'=>$mutationdetailstartdate ],
    [ 'name'=>'date', 'operator'=>'<=', 'value'=>$mutationdetailenddate ],
  ];
  if(is_array($mutationdetailquickfilters))
    $filters = array_merge($filters, $mutationdetailquickfilters);

  $sorts = [
    [ 'name'=>'date', 'sorttype'=>'asc' ],
    [ 'name'=>'id', 'sorttype'=>'asc' ],
  ];

  $c = "<element exp='#scrollable9'>";
  $c .= ui_grid2(array('id'=>'mutationdetailgrid', 'columns'=>$columns, 'datasource'=>'inventorymutation', 'filters'=>$filters,
    'sorts'=>$sorts, 'scrollel'=>'#scrollable9'));
  $c .= "</element>";
  $c .= uijs("inventorydetail_mutationdetail_resize();");

  global $module;
  $module['mutationdetailid'] = $id;
  m_savestate($module);

  echo $c;

}
function ui_inventorydetail_mutationdetail_export($id){

  global $module;
  $preset = $module['mutationdetailpresets'][$module['mutationdetailpresetidx']];
  $mutationdetailquickfilters = $preset['mutationdetailquickfilters'];
  $mutationdetailstartdate = ov('mutationdetailstartdate', $preset, 0, date('Ym') . '01');
  $mutationdetailenddate = ov('mutationdetailenddate', $preset, 0, date('Ymd'));

  $columns = array(
    array('active'=>1, 'name'=>'warehousename', 'text'=>'Gudang', 'width'=>100),
    array('active'=>1, 'name'=>'date', 'text'=>'Tanggal', 'width'=>100, 'datatype'=>'date'),
    array('active'=>1, 'name'=>'description', 'text'=>'Pelanggan', 'width'=>200),
    array('active'=>1, 'name'=>'in', 'text'=>'Masuk', 'width'=>60, 'datatype'=>'number'),
    array('active'=>1, 'name'=>'out', 'text'=>'Keluar', 'width'=>60, 'datatype'=>'number'),
    array('active'=>1, 'name'=>'unitamount', 'text'=>'Harga Satuan', 'width'=>100, 'datatype'=>'money')
  );

  $filters = [
    [ 'name'=>'inventoryid', 'operator'=>'=', 'value'=>$id ],
    [ 'name'=>'date', 'operator'=>'>=', 'value'=>$mutationdetailstartdate ],
    [ 'name'=>'date', 'operator'=>'<=', 'value'=>$mutationdetailenddate ],
  ];
  if(is_array($mutationdetailquickfilters))
    $filters = array_merge($filters, $mutationdetailquickfilters);

  $sorts = [
    [ 'name'=>'date', 'sorttype'=>'asc' ],
    [ 'name'=>'id', 'sorttype'=>'asc' ],
  ];

  $items = inventorymutation($columns, $sorts, $filters);

  $filepath = "usr/inventory_mutation_" . $id . "_" . date('YmdHis') . ".xls";
  array_to_excel($items, $filepath);
  echo uijs("ui('#downloader').href = '$filepath';ui('#downloader').click();");

}
function ui_inventorydetail_mutationdetail_columnresize($name, $width){

  global $module;
  $preset = $module['mutationdetailpresets'][$module['mutationdetailpresetidx']];
  for($i = 0 ; $i < count($preset['columns']) ; $i++){
    if($preset['columns'][$i]['name'] == $name){
      $preset['columns'][$i]['width'] = $width;
    }
  }
  $module['mutationdetailpresets'][$module['mutationdetailpresetidx']] = $preset;
  m_savestate($module);

}
function ui_inventorydetail_mutationdetail_columnapply($columns){

  return;

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

  return ui_inventorydetail_mutationdetail($module['mutationdetailid']);

}
function ui_inventorydetail_mutationdetail_sortapply($name){

  return;

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

  return ui_inventorydetail_mutationdetail($module['mutationdetailid']);

}
function ui_inventorydetail_mutationdetail_quickfilter($param0){

  $hint = $param0['hint'];
  $items[] = array('text'=>"Pelanggan: $hint", 'value'=>json_encode(array('name'=>'description', 'operator'=>'contains', 'value'=>$hint)));
  $items[] = array('text'=>"Gudang: $hint", 'value'=>json_encode(array('name'=>'warehousename', 'operator'=>'contains', 'value'=>$hint)));
  return $items;

}
function ui_inventorydetail_mutationdetail_quickfilterapply($exp){

  global $module;
  $quickfilters = array();
  $explodes = explode('|', $exp);
  for($i = 0 ; $i < count($explodes) ; $i++){
    $explode = $explodes[$i];
    $obj = objectToArray(json_decode($explode));
    if(is_array($obj)) $quickfilters[] = $obj;
  }
  $module['mutationdetailpresets'][$module['mutationdetailpresetidx']]['mutationdetailquickfilters'] = $quickfilters;
  m_savestate($module);

  return ui_inventorydetail_mutationdetail($module['mutationdetailid']);

}
function ui_inventorydetail_mutationdetail_datechanged($start_date, $end_date){

  global $module;
  $module['mutationdetailpresets'][$module['mutationdetailpresetidx']]['mutationdetailstartdate'] = $start_date;
  $module['mutationdetailpresets'][$module['mutationdetailpresetidx']]['mutationdetailenddate'] = $end_date;
  m_savestate($module);

  return ui_inventorydetail_mutationdetail($module['mutationdetailid']);

}

$ui_inventorydetail_costpricedetail_columns = [
  [ 'active'=>1, 'name'=>'ref', 'text'=>'Tipe', 'width'=>'40px' ],
  [ 'active'=>1, 'name'=>'date', 'text'=>'Tanggal', 'width'=>'70px' ],
  [ 'active'=>1, 'name'=>'code', 'text'=>'Kode', 'width'=>'100px' ],
  [ 'active'=>1, 'name'=>'description', 'text'=>'Deskripsi', 'width'=>'150px' ],
  [ 'active'=>1, 'name'=>'unitamount', 'text'=>'Harga Modal', 'type'=>'html', 'html'=>'ui_inventorydetail_costpricedetail_col0', 'width'=>'100px' ],
];

function ui_inventorydetail_costpricedetail($id){

  global $ui_inventorydetail_costpricedetail_columns;

  $columns = $ui_inventorydetail_costpricedetail_columns;
  $sorts = [];
  $filters = [
    [ 'name'=>'inventoryid', 'operator'=>'=', 'value'=>$id ],
  ];

  $c = "<element exp='#scrollable10'>";
  $c .= ui_grid2(array('id'=>'costpricegrid', 'columns'=>$columns, 'datasource'=>'inventorycostpricelist', 'filters'=>$filters,
    'sorts'=>$sorts, 'scrollel'=>'#scrollable10'));
  $c .= "</element>";
  $c .= uijs("inventorydetail_costpricedetail_resize();");

  global $module;
  $module['costpricedetailid'] = $id;
  m_savestate($module);

  echo $c;

}
function ui_inventorydetail_costpricelist(){



}
function ui_inventorydetail_costpricedetail_col0($obj){

  $unitamount = ov('unitamount', $obj, 0, 0);

  $html = [];
  $html[] = ui_textbox([ 'width'=>'100%', 'value'=>$unitamount ]);
  return implode('', $html);

}

?>