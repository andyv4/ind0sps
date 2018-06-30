<?php

require_once 'ui/category.php';

function ui_categorydetail($id = null, $mode = 'read'){

  $category = $id ? categorydetail(null, array('id'=>$id)) : array();
  if($mode != 'read' && $category && !privilege_get('category', 'modify')) $mode = 'read';
  $readonly = $mode == 'write' ? 0 : 1;
  $closable = $readonly ? 1 : 0;
  $is_new = !$readonly && !$category ? true : false;
  if($is_new && !privilege_get('category', 'new')) exc("Anda tidak dapat membuat kategori.");

  $controls = array(
    'id'=>array('type'=>'hidden', 'name'=>'id', 'value'=>ov('id', $category)),
    'name'=>array('type'=>'textbox', 'name'=>'name','width'=>'300px', 'value'=>ov('name', $category), 'readonly'=>$readonly),
    'frontend_active'=>array('type'=>'checkbox', 'name'=>'frontend_active', 'value'=>ov('frontend_active', $category), 'readonly'=>$readonly),
    'imageurl'=>array('type'=>'image', 'name'=>'imageurl', 'src'=>ov('imageurl', $category), 'width'=>120, 'height'=>120),
    'uploader'=>array('type'=>'upload', 'src'=>'ui_categorydetail_imageupload', 'id'=>'', 'text'=>'Pilih Gambar'),
  );

  $tabs = array();

  $actions = array();
  if(!$readonly && !$category) $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_categorysave', [ ui.container_value(ui('#categorydetail_scrollable')) ], { waitel:this })\"><span class='fa fa-check'></span><label>Simpan</label></button></td>";
  if(!$readonly && $category) $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_categorysave', [ ui.container_value(ui('#categorydetail_scrollable')) ], { waitel:this })\"><span class='fa fa-check'></span><label>Simpan</label></button></td>";
  if($readonly && $category) $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_categorydetail', [ $id, 'write' ], { waitel:this })\"><span class='fa fa-edit'></span><label>Ubah</label></button></td>";
  $actions[] = "<td><button class='hollow' onclick=\"ui.modal_close(ui('.modal'))\"><span class='fa fa-times'></span><label>Tutup</label></button></td>";

  $c = "<element exp='.modal'>";
  if(count($tabs) > 0)
    $c .= "
    <div class='padding10 align-center'>
      <div class='tabhead' data-tabbody='.tabbody'>" . implode('', $tabs) . "</div>
    </div>";
  $c .= "
    <div id='categorydetail_scrollable' class='padding10'>
      <div class='tabbody'>

        <!-- BEGIN TAB -->
        <div class='tab'>
          " . ui_control($controls['id']) . "
          <table class='form'>
            <tr><th><label>Nama Kategori</label></th><td>" . ui_control($controls['name']) . "</td></tr>
            <tr><td>&nbsp;</td></tr>
            <tr>
              <th><label>Gambar</label></th>
              <td>
                " . ui_control($controls['imageurl']) . "
                <div class='height10'></div>
                " . (!$readonly ? ui_control($controls['uploader']) . "<button class='hollow' onclick=\"ui.image_setvalue(ui('%imageurl'), '')\"><span class='fa fa-times'></span></button>" : '') . "
              </td>
            </tr>
          </table>
        </div>
        <!-- END TAB -->

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
  $c .= uijs("
		ui.loadscript('rcfx/js/category.js', \"categorydetail_resize();ui.modal_open(ui('.modal'), { closeable:$closable, width:540 });\");
  ");

  return $c;

}

function ui_categorydetail_imageupload($params){

  $url = "usr/img/" . $params['filename'];
  echo uijs("ui.control_setvalue(ui('%imageurl'), '$url')");

}

function ui_categorysave($category){

  if(isset($category['id']) && $category['id'] > 0)
    categorymodify($category);
  else
    categoryentry($category);

  return m_load() . uijs("ui.modal_close(ui('.modal'))");

}

function ui_categoryremove($id){

  categoryremove(array('id'=>$id));
  return m_load();

}

function ui_categoryexport(){

  global $module;
  $preset = $module['presets'][$module['presetidx']];
  $columns = $preset['columns'];
  $sorts = $preset['sorts'];
  $quickfilters = ov('quickfilters', $preset);
  $filters = $preset['filters'];
  $filters = m_quickfilter_to_filters($filters, $quickfilters);

  $pettycash_columnaliases = array(
    'id'=>'t1.id',
    'frontend_active'=>'t1.frontend_active',
    'name'=>'t1.name',
    'imageurl'=>'t1.imageurl'
  );

  $params = array();
  $columnquery = columnquery_from_columnaliases($columns, $pettycash_columnaliases);
  $wherequery = wherequery_from_filters($params, $filters, $pettycash_columnaliases);
  $sortquery = sortquery_from_sorts($sorts, $pettycash_columnaliases);;
  if(strlen($columnquery) > 0) $columnquery = ', ' . $columnquery;
  $query = "SELECT 'category' as `type`, t1.id $columnquery FROM category t1 $wherequery $sortquery";
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

  $filepath = 'usr/category-' . date('j-M-Y') . '.xlsx';
  array_to_excel($items, $filepath);

  echo uijs("ui('#downloader').href = '$filepath';ui('#downloader').click();");

}

?>