<?php
require_once 'rcfx/php/pdo.php';
require_once 'rcfx/php/util.php';
//require_once 'rcfx/php/component.php';
require_once 'rcfx/php/log.php';

function loadmodule(){

  $params = params();
  $savepath = $params['savepath'] . '/' . md5($params['title']);
  $module = file_exists($savepath) ? unserialize(file_get_contents($savepath)) : null;

  if(isset($_GET['reset']) || !$module || !isset($module['presets']) || count($module['presets']) == 0){

    $temp = null;
    $columns = function_exists('defaultcolumns') ? defaultcolumns() : array();

    // Check if ID column exists;
    $exists = false;
    for($i = 0 ; $i < count($columns) ; $i++)
      if($columns[$i]['name'] == 'id'){ $exists = true; break; }
    if(!$exists) $columns[] = array('name'=>'id', 'text'=>'id', 'width'=>50, 'active'=>0);

    // Presets
    $presets = null;
    $presetidx = 0;
    if(function_exists('defaultpresets')) $presets = defaultpresets($presetidx);
    if(!is_array($presets)){
      $presets = array(
        array(
          'text'=>'Default',
          'columns'=>$columns
        )
      );
    }
    for($i = 0 ; $i < count($presets) ; $i++)
      $presets[$i]['viewtype'] = 'list';

    $module = $params;
    $module['title'] = $params['title'];
    $module['columns'] = $columns;
    $module['presets'] = $presets;
    $module['presetidx'] = $presetidx;
    $module['viewtype'] = ov('viewtype', $params);

    if(function_exists('defaultmodule')) $module = defaultmodule();

    file_put_contents($savepath, serialize($module));
  }

  return $module;

}
function savemodule($module){

  $params = params();
  $savepath = $params['savepath'] . '/' . md5($params['title']);
  file_put_contents($savepath, serialize($module));

}

function ui_presets(){

  $module = loadmodule();
  $presets = $module['presets'];

  $items = array();
  for($i = 0 ; $i < count($presets) ; $i++){
    $preset = $presets[$i];
    $items[] = array(
      'text'=>$preset['text'],
      'value'=>$i
    );
  }
  $value = $module['presetidx'];

  $c = ui_dropdown(array('items'=>$items, 'value'=>$value, 'placeholder'=>'-No Preset-', 'width'=>150, 'onchange'=>"ui_list.presetselect(value, this)"));

  return $c;

}
function ui_presetselect($idx){

  $module = loadmodule();
  $module['presetidx'] = intval($idx);
  savemodule($module);
  return ui_load();

}

function ui_load(){

  $bypass_internal_process = false;

  log_bench_start('ui_load#data');
  $module = loadmodule();
  $preset = $module['presets'][$module['presetidx']];
  $columns = $preset['columns'];
  $sorts = isset($preset['sorts']) ? $preset['sorts'] : null;
  $filters = isset($preset['filters']) ? $preset['filters'] : null;
  if($preset['viewtype'] != 'group') $data = datasource($bypass_internal_process);
  $dataid = uniqid();
  $params = params();
  $entriable = isset($params['privileges']['entry']) && $params['privileges']['entry'] ? true : false;
  $exportable = isset($params['privileges']['export']) && $params['privileges']['export'] ? true : false;
  $refreshable = true;

  // Set default
  if(!isset($preset['viewtype'])) $preset['viewtype'] = 'list';

  if(!$bypass_internal_process){
    if(is_array($sorts))
      data_sort($data, $sorts);

    if(is_array($filters)){
      $filtered_data = array();
      for($i = 0 ; $i < count($data) ; $i++){
        $obj = $data[$i];
        $obj_match = data_filter($obj, $filters);

        if($obj_match) $filtered_data[] = $obj;
      }
      $data = $filtered_data;
    }

    if($preset['viewtype'] == 'group')
      $data = data_group($data, $preset['groups']);
  }

  // Quick filter value
  $quickfiltervalue = null;
  switch($preset['viewtype']){
    case 'group':
      if(isset($preset['groupquickfilters']) && is_array($preset['groupquickfilters'])){

        //throw new Exception(print_r($preset['groupquickfilters'], 1));
        $quickfiltervalue = array();
        for($i = 0 ; $i < count($preset['groupquickfilters']) ; $i++){
          $filter = $preset['groupquickfilters'][$i];
          $filtername = $filter['name'];
          $filtervalue = $filter['value'];

          switch($filtername){
            case 'all':
              $quickfiltervalue[] = array('text'=>'Search All: ' . $filtervalue, 'value'=>json_encode(array('name'=>'all', 'value'=>$filtervalue)));
              break;
            case 'group':
              $quickfiltervalue[] = array('text'=>'Search Group: ' . $filtervalue, 'value'=>json_encode(array('name'=>'group', 'value'=>$filtervalue)));
              break;
            default:
              throw new Exception('Unsupported group quickfilter filtername');
          }
        }

      }
      break;
    default:
      if(isset($preset['listquickfilters']) && is_array($preset['listquickfilters'])){

        //throw new Exception(print_r($preset['listquickfilters'], 1));
        $quickfiltervalue = array();
        $quickfilters = array();
        $columns_indexed = array_index($columns, array('name'), 1);
        for($i = 0 ; $i < count($preset['listquickfilters']) ; $i++){
          $filter = $preset['listquickfilters'][$i];
          $filtername = $filter['name'];
          $filtervalue = $filter['value'];

          $quickfilters[] = array('name'=>$filtername, 'operator'=>'contains', 'value'=>$filtervalue, 'type'=>'text');
          $quickfiltervalue[] = array('text'=>$columns_indexed[$filtername]['text'] . ' : ' . $filtervalue, 'value'=>json_encode(array('name'=>$filtername, 'value'=>$filtervalue)));
        }

        //throw new Exception(print_r($quickfilters, 1));

        // Filter data
        if(!$bypass_internal_process){
          $filtered_data = array();
          for($i = 0 ; $i < count($data) ; $i++){
            $obj = $data[$i];
            $obj_match = data_filter($obj, $quickfilters);

            if($obj_match) $filtered_data[] = $obj;
          }
          $data = $filtered_data;
        }
      }
      break;
  }

  log_bench_end();

  $c = '';
  $c .= "<element exp='.contentheadtoolbar'>";
  $c .= "
      <table class=\"\" cellspacing=\"10\">
          <tr>";

  if(function_exists('ui_header')){

    $c .= call_user_func_array('ui_header', array());

  }
  else{
    $c .= ($entriable ? "<td><button class='blue' onclick=\"ui.async('ui_detail', [ null, 'write' ], {})\"><span class='mdi mdi-2x mdi-plus'></span></button></td>" : '') . "
        " . ($refreshable ? "<td><button class='hollow' onclick=\"ui.async('ui_load', [], {})\"><span class='mdi mdi-2x mdi-refresh'></span></button></td>" : '') . "
        " . ($exportable ? "<td><button class=\"hollow\" onclick=\"ui.async('ui_export', [], {})\"><span class='mdi mdi-2x mdi-download'></span></button></td>" : '') . "
        <td style=\"width: 100%;\"><div>" . ui_multicomplete(array('width'=>'100%', 'name'=>'search', 'src'=>'ui_listquickfilter', 'placeholder'=>'Quick filter...', 'value'=>$quickfiltervalue, 'separator'=>'|', 'onchange'=>"ui.async('ui_listquickfilterapply', [ ui.multicomplete_value(this) ], {})")) . "</div></td>
        <td><span class='presetcont'>" . ui_presets() . "</span></td>
        <td><button class=\"hollow\" onclick=\"ui.async('ui_reportoption', [], { waitEl:this })\"><span class=\"fa fa-cog\"></span><label>" . lang('005') . "</label></button></td>
    ";
  }

  $c .= "</tr>
        </table>
    ";
  $c .= "</element>";

  if($preset['viewtype'] == 'group'){

    if(!$bypass_internal_process) $data = data_group($data, $preset['group']);

    $c .= "<element exp='.contenthead'>";
    $c .= ui_groupgridhead(array('columns'=>$columns, 'groups'=>$preset['groups']));
    $c .= "</element>";
    $c .= "<element exp='.contentbody'>";
    $c .= ui_groupgrid(array('id'=>'listgroupgrid1', 'columns'=>$columns, 'filters'=>ov('filters', $preset), 'groups'=>$preset['groups'],
      'value'=>$data, 'ondoubleclick_callback'=>'ui_onlistdblclickexp', 'groupcacheds'=>ov('groupcacheds', $params), 'cacheds'=>ov('cacheds', $params)));
    $c .= "</element>";
  }
  else{
    $c .= "<element exp='.contenthead'>";
    $c .= ui_gridhead(array('columns'=>$columns, 'oncolumnapply'=>'ui_columnapply', 'oncolumnclick'=>"ui.async('ui_sortapply', [ name ], {})", 'gridexp'=>'#listgrid', 'oncolumnresize'=>"ui.async('ui_columnresize', [ name, width ], {})"));
    $c .= "</element>";
    $c .= "<element exp='.contentbody'>";
    $c .= ui_grid(array('id'=>'listgrid', 'dataid'=>$dataid, 'maxitemperpage'=>100, 'columns'=>$columns,
      'value'=>$data, 'ondoubleclick_callback'=>'ui_onlistdblclickexp',
      'scrollel'=>'.contentbody', 'cacheds'=>ov('cacheds', $params), 'message_notassigned'=>lang('004'), 'message_novalue'=>lang('004')));
    $c .= "</element>";
  }

  $c .= uijs("ui_list.resize();");

  return $c;
}
function ui_onlistdblclickexp($obj){

  return "ui.async('ui_onlistdblclick', [ '$obj[id]' ], {})";

}
function ui_onlistdblclick($id){

  $c = '';
  if(function_exists('ui_detail'))
    $c = call_user_func_array('ui_detail', array($id, 'read'));
  echo $c;

}

function ui_export(){

  $bypass = false;
  $module = loadmodule();
  $savepath = $module['savepath'];
  $data = datasource($bypass);

  $filepath = $savepath . '/export.xls';
  array_to_excel($data, $filepath);

  echo uijs("ui('#downloader').href = '$filepath';ui('#downloader').click();");

}

function ui_listquickfilter($param0){
  $hint = $param0['hint'];

  $module = loadmodule();
  $preset = $module['presets'][$module['presetidx']];

  // For group quick filter
  if($preset['viewtype'] == 'group'){

    $items = array(
      array('text'=>'Search All: ' . $hint, 'value'=>json_encode(array('name'=>'all', 'value'=>$hint))),
      array('text'=>'Search Group: ' . $hint, 'value'=>json_encode(array('name'=>'group', 'value'=>$hint))),
    );

  }
  // For list quick filter
  else{

    $columns = $preset['columns'];
    for($i = 0 ; $i < count($columns) ; $i++){
      $column = $columns[$i];

      if(empty($column['name']) || empty($column['text'])) continue;

      $columntext = $column['text'];
      $items[] = array('text'=>"$columntext : $hint", 'value'=>json_encode(array('name'=>$column['name'], 'value'=>$hint)));
    }

  }


  return $items;


}
function ui_listquickfilterapply($exp){

  $quickfilters = array();
  $explodes = explode('|', $exp);
  for($i = 0 ; $i < count($explodes) ; $i++){
    $explode = $explodes[$i];
    $obj = objectToArray(json_decode($explode));
    if(is_array($obj)) $quickfilters[] = $obj;
  }

  $module = loadmodule();
  $preset = $module['presets'][$module['presetidx']];

  if($preset['viewtype'] == 'group'){
    $preset['groupquickfilters'] = $quickfilters;
  }
  else{
    $preset['listquickfilters'] = $quickfilters;
  }

  $module['presets'][$module['presetidx']] = $preset;
  savemodule($module);

  return ui_load();

}

function ui_reportoption(){

  $module = loadmodule();
  $viewtype = strtolower(ov('viewtype', $module, 0, 'list,group'));
  $group_enabled = strpos($viewtype, 'group') !== false ? true : false;

  $c = "<element exp='.modal'>";
  $c .= "
    <div class='reportoption'>
      <span class='presetlist'>
        <div class='head'>
          <button class='hollow'><span class='fa fa-plus'></span></button>
          <button class='hollow'><span class='fa fa-minus'></span></button>
          <button class='hollow'><span class='fa fa-caret-up'></span></button>
          <button class='hollow'><span class='fa fa-caret-down'></span></button>
        </div>
        <div class='body scrollable'>
          <div class='menulist'></div>
        </div>
      </span>
      <span class='presetdetail padding10'>
        <div class='align-center'>
          <div class='tabhead' data-tabbody='#reportoptiontabbody'>
            <div class='tabitem' onclick=\"ui.tabclick(event, this)\"><label>Name</label></div>
            <div class='tabitem active' onclick=\"ui.tabclick(event, this)\"><label>Columns</label></div>
            <div class='tabitem' onclick=\"ui.tabclick(event, this)\"><label>Sorts</label></div>
            <div class='tabitem' onclick=\"ui.tabclick(event, this)\"><label>Filters</label></div>
            " . ($group_enabled ? "<div class='tabitem' onclick=\"ui.tabclick(event, this);ui_list.presetoptiongroupresize()\"><label>Groups</label></div>" : '') . "
          </div>
        </div>
        <div id='reportoptiontabbody' class='tabbody scrollable'>
          <div class='tab tabname off'>
            " . ui_reportoptionname() . "
          </div>
          <div class='tab tabcolumns'>
            " . ui_reportoptioncolumns() . "
          </div>
          <div class='tab off'>
            " . ui_reportoptionsorts() . "
          </div>
          <div class='tab off'>
            " . ui_reportoptionfilters() . "
          </div>
          <div class='tab off tabgroups'>
            " . ui_reportoptiongroups() . "
          </div>
        </div>
      </span>
      <div class='toolbar'>
        <table cellspacing='5'>
          <tr>
            <td style='width: 100%'></td>
            <td><button id='presetsavebtn' class='red' onclick=\"ui_list.presetapply()\"><span class='fa fa-check'></span><label>Apply</label></button></td>
            <td><button class='hollow' onclick=\"ui.modal_close(ui('.modal'))\"><span class='fa fa-check'><span><label>Cancel</label></button></td>
          </tr>
        </table>
      </div>
    </div>
  ";
  $c .= "</element>";
  $c .= uijs("
    report = " . json_encode($module) . ";
    ui.modal_open(ui('.modal'), { width:800 });
    ui_list.presetoptionload(0);
  ");
  return $c;
}
function ui_reportoptionname(){

  $c = '';
  $c .= "<div class='scrollable'>";
  $c .= "<table class='form'>
    <tr><th><label>Preset Name</label></th><td>" . ui_textbox(array('name'=>'text', 'onchange'=>'ui_list.presetoptiontextchange(value)')) . "</td></tr>
  </table>";
  $c .= "</div>";

  return $c;
}
function ui_reportoptioncolumns(){

  $aligns = array(
    array('text'=>'Default', 'value'=>''),
    array('text'=>'Left', 'value'=>'left'),
    array('text'=>'Center', 'value'=>'center'),
    array('text'=>'Right', 'value'=>'right')
  );

  $datatypes = array(
    array('text'=>'Auto', 'value'=>''),
    array('text'=>'Text', 'value'=>'text'),
    array('text'=>'Number', 'value'=>'number'),
    array('text'=>'Money', 'value'=>'money'),
    array('text'=>'Date', 'value'=>'date'),
    array('text'=>'Datetime', 'value'=>'datetime')
  );

  $lettercases = array(
    array('text'=>'Default', 'value'=>''),
    array('text'=>'Capitalize', 'value'=>'capitalize'),
    array('text'=>'Lower Case', 'value'=>'lowercase'),
    array('text'=>'Upper Case', 'value'=>'uppercase')
  );

  $c = '';

  // Column ui_list
  $c .= "<span class='columnlist'>";
  // Toolbar
  $c .= "<div>
    <button class='hollow' onclick=\"ui_list.presetoptioncolumnmoveup()\"><span class='fa fa-caret-up'></span></button>
    <button class='hollow' onclick=\"ui_list.presetoptioncolumnmovedown()\"><span class='fa fa-caret-down'></span></button>
  </div>";
  $c .= "<div class='scrollable'>";
  $c .= "</div></span>";

  // Column detail
  $c .= "<span class='columndetail'>";
  $c .= "
    <table class='form' cellspacing='5'>
      <tr><th><label>Active</label></th><td>" . ui_checkbox(array('name'=>'active', 'onchange'=>"ui_list.presetoptioncolumndetailchange(name, value)")) . "</td></tr>
      <tr><th><label>Name</label></th><td>" . ui_label(array('name'=>'name')) . "</td></tr>
      <tr><th><label>Text</label></th><td>" . ui_textbox(array('name'=>'text', 'width'=>100, 'onchange'=>"ui_list.presetoptioncolumndetailchange(name, value)")) . "</td></tr>
      <tr><th><label>Width</label></th><td>" . ui_textbox(array('name'=>'width', 'width'=>100, 'onchange'=>"ui_list.presetoptioncolumndetailchange(name, value)")) . "</td></tr>
      <tr><th><label>Align</label></th><td>" . ui_dropdown(array('name'=>'align', 'width'=>100, 'items'=>$aligns, 'value'=>'', 'onchange'=>"ui_list.presetoptioncolumndetailchange(name, value)")) . "</td></tr>
      <tr><th><label>Type</label></th><td>" . ui_dropdown(array('name'=>'datatype', 'width'=>100, 'items'=>$datatypes, 'value'=>'', 'onchange'=>"ui_list.presetoptioncolumndetailchange(name, value)")) . "</td></tr>
      <tr><th><label>Letter Case</label></th><td>" . ui_dropdown(array('name'=>'lettercase', 'width'=>100, 'items'=>$lettercases, 'value'=>'', 'onchange'=>"ui_list.presetoptioncolumndetailchange(name, value)")) . "</td></tr>
    </table>
  ";
  $c .= "</span>";

  return $c;
}
function ui_reportoptionsorts(){

  $c = '';

  // Toolbar
  $c .= "<div>
    <button class='hollow' onclick=\"ui_list.presetoptionsortnew()\"><span class='fa fa-plus'></span></button>
    <button class='hollow' onclick=\"ui_list.presetoptionsortremove()\"><span class='fa fa-minus'></span></button>
    <button class='hollow' onclick=\"ui_list.presetoptionsortmoveup()\"><span class='fa fa-caret-up'></span></button>
    <button class='hollow' onclick=\"ui_list.presetoptionsortmovedown()\"><span class='fa fa-caret-down'></span></button>
  </div>";

  $c .= "<div class='sortlist'><div class='scrollable'></div></div>";

  return $c;

}
function ui_reportoptionfilters(){

  $c = '';

  // Toolbar
  $c .= "<div>
    <button class='hollow' onclick=\"ui_list.presetoptionfilternew()\"><span class='fa fa-plus'></span></button>
    <button class='hollow' onclick=\"ui_list.presetoptionfilterremove()\"><span class='fa fa-minus'></span></button>
    <button class='hollow' onclick=\"ui_list.presetoptionfiltermoveup()\"><span class='fa fa-caret-up'></span></button>
    <button class='hollow' onclick=\"ui_list.presetoptionfiltermovedown()\"><span class='fa fa-caret-down'></span></button>
  </div>";

  $c .= "<div class='filterlist'><div class='scrollable'></div></div>";

  return $c;

}
function ui_reportoptiongroups(){

  $c = '';

  $c .= "<table cellspacing='5'>
    <tr>
      <td colspan='6'>" . ui_checkbox(array('name'=>'active', 'text'=>'Active', 'onchange'=>"ui_list.reportoptiongrouptoggle(this)")) . "</td>
    </tr>
    <tr>
      <td><button class='hollow' onclick=\"ui_list.reportoptiongroupremove()\"><span class='fa fa-times'></span></button></td>
      <td><button class='hollow' onclick=\"ui_list.reportoptiongroupmoveup()\"><span class='fa fa-caret-up'></span></button></td>
      <td><button class='hollow' onclick=\"ui_list.reportoptiongroupmovedown()\"><span class='fa fa-caret-down'></span></button></td>
      <td><button class='hollow' onclick=\"ui_list.reportoptiongroupcolumnremove()\"><span class='fa fa-times'></span></button></td>
      <td><button class='hollow' onclick=\"ui_list.reportoptiongroupcolumnmoveup()\"><span class='fa fa-caret-up'></span></button></td>
      <td><button class='hollow' onclick=\"ui_list.reportoptiongroupcolumnmovedown()\"><span class='fa fa-caret-down'></span></button></td>
    </tr>
  </table><br />";


  $c .= "<span class='grouplist'>
    <div class='scrollable'></div>
  </span>";

  return $c;

}

function ui_columnresize($name, $width){
  $module = loadmodule();
  $presetidx = $module['presetidx'];

  for($i = 0 ; $i < count($module['presets'][$presetidx]['columns']) ; $i++){
    if($module['presets'][$presetidx]['columns'][$i]['name'] == $name){
      $module['presets'][$presetidx]['columns'][$i]['width'] = $width;
    }
  }

  savemodule($module);
}
function ui_columnapply($columns){

  $columns = array_index($columns, array('name'), 1);

  $module = loadmodule();
  $presetidx = $module['presetidx'];
  $preset = $module['presets'][$presetidx];
  for($i = 0 ; $i < count($preset['columns']) ; $i++){
    $name = $preset['columns'][$i]['name'];
    if(isset($columns[$name]))
      $preset['columns'][$i]['active'] = $columns[$name]['active'];
  }

  $module['presets'][$presetidx] = $preset;
  savemodule($module);

  return ui_load();

}
function ui_sortapply($name){
  $module = loadmodule();
  $presetidx = $module['presetidx'];
  $preset = $module['presets'][$presetidx];

  // If sort applied before is equal with this one, invert the sorttype
  if(count($preset['sorts']) == 1 && $preset['sorts'][0]['name'] == $name){
    $preset['sorts'][0]['sorttype'] = $preset['sorts'][0]['sorttype'] == 'desc' ? 'asc' : 'desc';
  }
  else{
    $preset['sorts'] = array();
    $preset['sorts'][] = array(
      'name'=>$name,
      'sorttype'=>'asc'
    );
  }
  $module['presets'][$presetidx] = $preset;

  savemodule($module);

  return ui_load();
}
function ui_presetapply($module){

  savemodule($module);
  return uijs("ui.modal_close(ui('.modal'));") . ui_load();

}
function ui_presetprint(){

  $module = loadmodule();
  $preset = $module['presets'][$module['presetidx']];
  echo uijs("console.log(" . json_encode($preset) . ")");

}
function ui_moduleprint(){

  $module = loadmodule();
  echo uijs("console.log(" . json_encode($module) . ")");

}

ui_async();
?>
<html>
<head>
  <title><?=function_exists('params') ? ov('title', params()) : 'Untitled Module'?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <link rel="stylesheet" href="rcfx/css/opensans.css" />
  <link rel="stylesheet" href="rcfx/css/materialdesignicons.css" />
  <link rel="stylesheet" href="rcfx/css/fontawesome.css" />
  <link rel="stylesheet" href="rcfx/css/animation.css" />
  <link rel="stylesheet" href="rcfx/css/component.css" />
  <link rel="stylesheet" href="rcfx/css/reconv.css" />
  <?php
  if(function_exists('styles')){
    $list_customstyles = styles();
    if(is_array($list_customstyles))
      for($i = 0 ; $i < count($list_customstyles) ; $i++)
        echo "<link rel='stylesheet' href='" . $list_customstyles[$i] . "' />";
  }
  ?>
  <script type="text/javascript" src="rcfx/js/php.js"></script>
  <script type="text/javascript" src="rcfx/js/mattkrusedate.js"></script>
  <script type="text/javascript" src="rcfx/js/component.min.js"></script>
  <?php
  if(function_exists('scripts')){
    $list_customscripts = scripts();
    if(is_array($list_customscripts))
      for($i = 0 ; $i < count($list_customscripts) ; $i++)
        echo "<script type='text/javascript' src='" . $list_customscripts[$i] . "'></script>";
  }
  ?>
</head>
<body onload="ui_list.init()">

<div class="screen animated">

  <?php include 'sidebar.php'; ?>

  <div class="content">
    <div class="head">
      <div class="contentheadtoolbar"></div>
      <div class="contenthead"></div>
    </div>
    <div class="body contentbody scrollable"></div>
  </div>

  <div class="modalbg off"></div>
  <div class="modal off animated"></div>

  <div class="dialogbg off"></div>
  <div class="dialog off animated"></div>

  <a class="off" id="downloader" download></a>

</div>
<script type="text/javascript">

ui_list = {};
report = <?=json_encode(loadmodule())?>;
qs = <?=json_encode($_GET);?>;

ui_list.init = function(){
  ui_list.resize();
  ui.async('ui_load', [], { params:typeof qs['reset'] != 'undefined' ? { reset:1 } : {} });
  ui_list.contentbody_loadingmode();
  window.addEventListener('resize', ui_list.resize, true);
}

ui_list.resize = function(){
  var content = ui('.content');
  var contenthead = ui('.head', content);
  var contentbody = ui('.body', content);
  var sidebar = ui('.sidebar');
  var sidebarhead = ui('.head', sidebar);

  contentbody.style.marginTop = contenthead.clientHeight + "px";
  contentbody.style.height = (window.innerHeight - contenthead.clientHeight) + "px";
}

ui_list.contentbody_loadingmode = function(){
  ui('.contentbody').innerHTML = "<div class='spinner' style='padding-top:20px'><div class='bounce1'></div><div class='bounce2'></div><div class='bounce3'></div></div>";
}

ui_list.presetselect = function(index, el){
  ui.async('ui_presetselect', [ index ]);
  ui('.contentbody').innerHTML = "<div class='spinner' style='padding-top:20px'><div class='bounce1'></div><div class='bounce2'></div><div class='bounce3'></div></div>";
}
ui_list.presetoptionload = function(){
  ui_list.presetoptionpresetlistload();
  ui_list.presetoptionpresetdetailload();
}
ui_list.presetoptionpresetlistload = function(){
  var presets = report['presets'];
  var presetlist = ui('.presetlist');

  var menulist = ui('.menulist', presetlist);
  var c = "";
  for(var i = 0 ; i < presets.length ; i++){
    var preset = presets[i];
    var text = preset['text'];
    var active_class = i == report['presetidx'] ? ' active' : '';
    c += "<div class=\"menuitem" + active_class + "\"><span class=\"fa fa-calendar\"></span><label onclick=\"ui_list.presetoptionitemclick(this)\">" + text + "</label>";
    c += "<span class='sect2'>";
    c += "<span class='fa fa-copy' onclick=\"ui_list.presetoptioncopy(this)\"></span>";
    c += "<span class='fa fa-times' onclick=\"ui_list.presetoptionremove(this)\"></span>";
    c += "</span>";
    c += "</div>";
  }
  menulist.innerHTML = c;
}
ui_list.presetoptionitemclick = function(label){
  var menuitem = label.parentNode;
  var menulist = menuitem.parentNode;
  var idx = -1;
  for(var i = 0 ; i < menulist.children.length ; i++){
    if(menulist.children[i] == menuitem){
      idx = i;
      break;
    }
  }

  var active_menuitem = ui('.active', menulist);
  if(active_menuitem) active_menuitem.classList.remove('active');
  menuitem.classList.add('active');

  report['presetidx'] = idx;
  ui_list.presetoptionpresetdetailload();
}
ui_list.presetoptionpresetdetailload = function(){
  var presets = report['presets'];
  var presetidx = report['presetidx'];
  var preset = presets[presetidx];
  ui.textbox_setvalue(ui('%text', ui('.tabname')), preset['text']);
  ui_list.presetoptioncolumnload();
  ui_list.presetoptionsortload();
  ui_list.presetoptionfilterload();
  ui_list.presetoptiongroupload();
}
ui_list.presetoptioncopy = function(span){

  // Get index
  var menuitem = span.parentNode.parentNode;
  var menulist = menuitem.parentNode;
  var idx = -1;
  for(var i = 0 ; i < menulist.children.length ; i++){
    if(menulist.children[i] == menuitem){
      idx = i;
      break;
    }
  }

  // Copy preset
  var presets = report['presets'];
  var preset = presets[idx];
  var clonedpreset = {};
  for(var key in preset)
    clonedpreset[key] = preset[key];
  clonedpreset.text += " (Copy)";
  report['presets'].push(clonedpreset);

  report['presetidx'] = report['presets'].length - 1;
  ui_list.presetoptionpresetlistload();
  ui_list.presetoptionpresetdetailload();
}
ui_list.presetoptionremove = function(span){

  // Get index
  var menuitem = span.parentNode.parentNode;
  var menulist = menuitem.parentNode;
  var idx = -1;
  for(var i = 0 ; i < menulist.children.length ; i++){
    if(menulist.children[i] == menuitem){
      idx = i;
      break;
    }
  }

  report['presets'].splice(idx, 1);
  report['presetidx'] = report['presets'].length - 1;
  ui_list.presetoptionpresetlistload();
  ui_list.presetoptionpresetdetailload();

}
ui_list.presetoptiontextchange = function(text){

  // Update data
  var presetidx = report['presetidx'];
  var presets = report['presets'];
  report['presets'][presetidx]['text'] = text;

  // Update menulist ui
  var presetlist = ui('.presetlist');
  var menulist = ui('.menulist', presetlist);
  ui('label', menulist.children[presetidx]).innerHTML = text;

}

ui_list.presetoptioncolumnload = function(){
  var presetidx = report['presetidx'];
  var presets = report['presets'];
  var preset = presets[presetidx];
  var columns = preset['columns'];
  var columnlist = ui('.columnlist');
  var scrollable = ui('.scrollable', columnlist);

  var c = '';
  for(var i = 0 ; i < columns.length ; i++){
    var column = columns[i];
    var name = column['name'];
    var text = ui.ov('text', column);
    var active = column['active'];
    var selected = i == preset['columnidx'] ? 1 : 0;

    if(text.length == 0) text = "&nbsp;";

    c += "<div class='columnitem" + (selected ? ' active' : '') + "' onclick=\"ui_list.presetoptioncolumnclick(event, this)\">" +
      "<input type='checkbox' " + (active ? 'checked' : '') + " onchange=\"ui_list.presetoptioncolumndetailchange('active', this.checked);ui_list.presetoptioncolumndetailload(" + i + ");event.preventDefault();event.stopPropagation();return false;\" />" +
      "<label>" + text + "</label>" +
      "</div>";
  }
  scrollable.innerHTML = c;

  ui_list.presetoptioncolumndetailload(0);
  report['presetidx'] = presetidx;
}
ui_list.presetoptioncolumnclick = function(e, div){
  var columnlist = ui('.columnlist');
  var scrollable = ui('.scrollable', columnlist);
  var idx = -1;
  for(var i = 0 ; i < scrollable.children.length ; i++)
    if(scrollable.children[i] == div){
      idx = i;
      break;
    }
  ui_list.presetoptioncolumndetailload(idx);
}
ui_list.presetoptioncolumndetailload = function(idx){
  var presets = report['presets'];
  var preset = presets[report['presetidx']];
  var columns = preset['columns'];
  var column = columns[idx];

  var columndetail = ui('.columndetail');

  ui.container_setvalue(columndetail, column);
  presets[report['presetidx']]['columnidx'] = idx;

  // Mark as active
  var columnlist = ui('.columnlist');
  var scrollable = ui('.scrollable', columnlist);
  var active_columnitem = ui('.active', scrollable);
  if(active_columnitem) active_columnitem.classList.remove('active');
  scrollable.children[idx].classList.add('active');
}
ui_list.presetoptioncolumndetailchange = function(name, value){
  console.warn("ui_list.presetoptioncolumndetailchange, name: " + name + ", value: " + value);
  var presets = report['presets'];
  var preset = presets[report['presetidx']];
  var columnidx = preset['columnidx'];
  report['presets'][report['presetidx']]['columns'][columnidx][name] = value;
}
ui_list.presetoptioncolumnmovedown = function(){
  var presetidx = report['presetidx'];
  var presets = report['presets'];
  var preset = presets[presetidx];
  var columns = preset['columns'];
  var columnlist = ui('.columnlist');
  var scrollable = ui('.scrollable', columnlist);

  var idx = -1;
  for(var i = 0 ; i < scrollable.children.length ; i++)
    if(scrollable.children[i].classList.contains('active')){
      idx = i;
      break;
    }
  if(idx != -1 && idx < scrollable.children.length - 1){
    var temp = report['presets'][presetidx]['columns'][idx + 1];
    report['presets'][presetidx]['columns'][idx + 1] = report['presets'][presetidx]['columns'][idx];
    report['presets'][presetidx]['columns'][idx] = temp;
    scrollable.insertBefore(scrollable.children[idx + 1], scrollable.children[idx]);
  }
}
ui_list.presetoptioncolumnmoveup = function(){
  var presetidx = report['presetidx'];
  var presets = report['presets'];
  var preset = presets[presetidx];
  var columns = preset['columns'];
  var columnlist = ui('.columnlist');
  var scrollable = ui('.scrollable', columnlist);

  var idx = -1;
  for(var i = 0 ; i < scrollable.children.length ; i++)
    if(scrollable.children[i].classList.contains('active')){
      idx = i;
      break;
    }
  if(idx != -1 && idx > 0){
    var temp = report['presets'][presetidx]['columns'][idx - 1];
    report['presets'][presetidx]['columns'][idx - 1] = report['presets'][presetidx]['columns'][idx];
    report['presets'][presetidx]['columns'][idx] = temp;
    scrollable.insertBefore(scrollable.children[idx], scrollable.children[idx - 1]);
  }
}

ui_list.presetoptionsortload = function(){

  var presetidx = report['presetidx'];
  var presets = report['presets'];
  var preset = presets[presetidx];
  var sorts = typeof preset['sorts'] != 'undefined' ? preset['sorts'] : null;
  var sortlist = ui('.sortlist');
  var scrollable = ui('.scrollable', sortlist);

  scrollable.innerHTML = '';
  if(sorts instanceof Array)
    for(var i = 0 ; i < sorts.length ; i++)
      ui_list.presetoptionsortnew(sorts[i]);
}
ui_list.presetoptionsortsave = function(){

  var presetidx = report['presetidx'];
  var presets = report['presets'];
  if(typeof presets[presetidx] == 'undefined') return;
  var preset = presets[presetidx];
  var sorts = typeof preset['sorts'] != 'undefined' ? preset['sorts'] : null;
  var sortlist = ui('.sortlist');
  var scrollable = ui('.scrollable', sortlist);

  var sortitems = ui('.sortitem', scrollable, 1);
  var sorts = [];
  if(sortitems)
    for(var i = 0 ; i< sortitems.length ; i++)
      sorts.push(ui.container_value(sortitems[i]));

  report['presets'][presetidx]['sorts'] = sorts;
}
ui_list.presetoptionsortnew = function(sort){

  var presetidx = report['presetidx'];
  var presets = report['presets'];
  if(typeof presets[presetidx] == 'undefined') return;
  var preset = presets[presetidx];
  var columns = preset['columns'];
  var sortlist = ui('.sortlist');
  var scrollable = ui('.scrollable', sortlist);

  var sortitems = [];
  for(var i = 0 ; i < columns.length ; i++)
    sortitems.push({ text:columns[i].text, value:columns[i].name });

  var sorttypes = [
    { text:'Ascending', value:'asc' },
    { text:'Descending', value:'desc' }
  ];

  var name = ui.ov('name', sort);
  var sorttype = ui.ov('sorttype', sort);

  var c = "<div class='sortitem' onclick='ui_list.presetoptionsortitemclick(event, this)'>";
  c += ui.dropdown({ name:'name', items:sortitems, value:name, width:240 });
  c += "&nbsp;";
  c += ui.dropdown({ name:'sorttype', items:sorttypes, value:sorttype, width:140 });
  c += "</div>";
  scrollable.insertAdjacentHTML('beforeend', c);

}
ui_list.presetoptionsortitemclick = function(e, div){
  var cont = div.parentNode;
  var active_div = ui('.active', cont);
  if(active_div) active_div.classList.remove('active');
  div.classList.add('active');
}
ui_list.presetoptionsortremove = function(){
  var presetidx = report['presetidx'];
  var presets = report['presets'];
  var preset = presets[presetidx];
  var columns = preset['columns'];
  var sortlist = ui('.sortlist');
  var scrollable = ui('.scrollable', sortlist);

  var idx = -1;
  for(var i = 0 ; i < scrollable.children.length ; i++)
    if(scrollable.children[i].classList.contains('active')){
      idx = i;
      break;
    }
  if(idx != -1){
    report['presets'][presetidx]['sorts'].splice(idx, 1);
    scrollable.removeChild(scrollable.children[idx]);
  }
}
ui_list.presetoptionsortmoveup = function(){
  var presetidx = report['presetidx'];
  var presets = report['presets'];
  var preset = presets[presetidx];
  var columns = preset['columns'];
  var sortlist = ui('.sortlist');
  var scrollable = ui('.scrollable', sortlist);

  var idx = -1;
  for(var i = 0 ; i < scrollable.children.length ; i++)
    if(scrollable.children[i].classList.contains('active')){
      idx = i;
      break;
    }
  if(idx != -1 && idx > 0){
    var temp = report['presets'][presetidx]['sorts'][idx - 1];
    report['presets'][presetidx]['sorts'][idx - 1] = report['presets'][presetidx]['sorts'][idx];
    report['presets'][presetidx]['sorts'][idx] = temp;
    scrollable.insertBefore(scrollable.children[idx], scrollable.children[idx - 1]);
  }
}
ui_list.presetoptionsortmovedown = function(){
  var presetidx = report['presetidx'];
  var presets = report['presets'];
  var preset = presets[presetidx];
  var columns = preset['columns'];
  var sortlist = ui('.sortlist');
  var scrollable = ui('.scrollable', sortlist);

  var idx = -1;
  for(var i = 0 ; i < scrollable.children.length ; i++)
    if(scrollable.children[i].classList.contains('active')){
      idx = i;
      break;
    }
  if(idx != -1 && idx < scrollable.children.length - 1){
    var temp = report['presets'][presetidx]['sorts'][idx + 1];
    report['presets'][presetidx]['sorts'][idx + 1] = report['presets'][presetidx]['sorts'][idx];
    report['presets'][presetidx]['sorts'][idx] = temp;
    scrollable.insertBefore(scrollable.children[idx + 1], scrollable.children[idx]);
  }
}

ui_list.presetoptionfilterload = function(){

  var presetidx = report['presetidx'];
  var presets = report['presets'];
  var preset = presets[presetidx];
  var filters = typeof preset['filters'] != 'undefined' ? preset['filters'] : null;
  var filterlist = ui('.filterlist');
  var scrollable = ui('.scrollable', filterlist);

  scrollable.innerHTML = '';
  if(filters instanceof Array)
    for(var i = 0 ; i < filters.length ; i++)
      ui_list.presetoptionfilternew(filters[i]);

}
ui_list.presetoptionfiltersave = function(){

  var presetidx = report['presetidx'];
  var presets = report['presets'];
  if(typeof presets[presetidx] == 'undefined') return;
  var preset = presets[presetidx];
  var filterlist = ui('.filterlist');
  var scrollable = ui('.scrollable', filterlist);

  var filteritems = ui('.filteritem', scrollable, 1);
  var filters = [];
  if(filteritems)
    for(var i = 0 ; i< filteritems.length ; i++)
      filters.push(ui.container_value(filteritems[i]));
  console.log(filters);

  report['presets'][presetidx]['filters'] = filters;

}
ui_list.presetoptionfilternew = function(filter){

  var presetidx = report['presetidx'];
  var presets = report['presets'];
  var preset = presets[presetidx];
  var columns = preset['columns'];
  var filterlist = ui('.filterlist');
  var scrollable = ui('.scrollable', filterlist);

  var columnitems = [];
  for(var i = 0 ; i < columns.length ; i++)
    columnitems.push({ text:columns[i].text, value:columns[i].name });

  var c = "<div class='filteritem'>";
  if(filter){
    var columnname = filter['name'];

    c += ui.checkbox({ name:'selected' });
    c += "<span>";
    c += ui.dropdown({ name:'name', items:columnitems, width:120, value:columnname, onchange:"ui_list.presetoptionfiltercolumnchange(value, this)" });
    c += "</span>";
    c += "<span class='sect-operator'>";
    c += ui_list.presetoptionfiltercolumnui(columnname, filter['operator']);
    c += "</span>";
    c += "<span class='sect-value'>";
    c += ui_list.presetoptionfiltervalueui(filter['operator'], filter); //ui.textbox({ name:"value", value:filter['value'], width: 100 });
    c += "</span>";
  }
  else{
    c += ui.checkbox({ name:'selected' });
    c += "<span>";
    c += ui.dropdown({ name:'name', items:columnitems, width:120, onchange:"ui_list.presetoptionfiltercolumnchange(value, this)" });
    c += "</span>";
    c += "<span class='sect-operator'>";
    c += "</span>";
    c += "<span class='sect-value'>";
    c += "</span>";
  }
  c += "</div>";
  scrollable.insertAdjacentHTML('beforeend', c);

}
ui_list.presetoptionfilterremove = function(){

  var filterlist = ui('.filterlist');
  var scrollable = ui('.scrollable', filterlist);
  for(var i = scrollable.children.length - 1 ; i >= 0 ; i--){
    var filteritem = scrollable.children[i];
    if(ui.checkbox_value(ui('%selected', filteritem)))
      scrollable.removeChild(scrollable.children[i]);
  }

}
ui_list.presetoptionfiltercolumnchange = function(columnname, el){

  var filteritem = el.parentNode.parentNode;
  var sect_operator = ui('.sect-operator', filteritem);
  sect_operator.innerHTML = ui_list.presetoptionfiltercolumnui(columnname);

}
ui_list.presetoptionfilteroperatorchange = function(operator, el){

  var filteritem = el.parentNode.parentNode;
  var sect_value = ui('.sect-value', filteritem);
  sect_value.innerHTML = ui_list.presetoptionfiltervalueui(operator);

}
ui_list.presetoptionfiltervalueui = function(operator, obj){
  var value = ui.ov('value', obj);
  var value1 = ui.ov('value1', obj);

  // Construct operator control
  var c = '';
  switch(operator){
    case 'today': break;
    case 'thisweek': break;
    case 'thismonth': break;
    case 'thisyear': break;
    case 'on':
      c += ui.datepicker({ name:"value", value:value });
      break;
    case 'between':
      c += ui.datepicker({ name:"value", value:value });
      c += ui.datepicker({ name:"value1", value:value1 });
      break;
    case 'before':
      c += ui.datepicker({ name:"value", value:value });
      break;
    case 'after':
      c += ui.datepicker({ name:"value", value:value });
      break;
    default :
      c += ui.textbox({ name:"value", width: 100, value:value });
      break;
  }
  return c;

}
ui_list.presetoptionfiltercolumnui = function(columnname, operator){

  // Get column datatype
  var presetidx = report['presetidx'];
  var presets = report['presets'];
  var preset = presets[presetidx];
  var columns = preset['columns'];
  var datatype = '';
  for(var i = 0 ; i < columns.length ; i++)
    if(columns[i].name == columnname){
      datatype = columns[i].datatype;
      break;
    }

  // Construct operator control
  var c = '';
  switch(datatype){
    case 'date':
      var items = [
        { value:"today", text:"Today" },
        { value:"thisweek", text:"This Week" },
        { value:"thismonth", text:"This Month" },
        { value:"thisyear", text:"This Year" },
        { value:"on", text:"On" },
        { value:"between", text:"Between" },
        { value:"before", text:"Before" },
        { value:"after", text:"After" }
      ];
      c += ui.dropdown({ name:"operator", items:items, value:operator, width: 120, onchange:"ui_list.presetoptionfilteroperatorchange(value, this)" });
      break;
    case 'number':
      var items = [
        { value:"<", text:"<" },
        { value:"<=", text:"<=" },
        { value:"=", text:"=" },
        { value:">", text:">" },
        { value:">=", text:">=" },
        { value:"between", text:"Between" }
      ];
      c += ui.dropdown({ name:"operator", items:items, value:operator, width: 120, onchange:"ui_list.presetoptionfilteroperatorchange(value, this)" });
      break;
    case 'money':
      var items = [
        { value:"<", text:"<" },
        { value:"<=", text:"<=" },
        { value:"=", text:"=" },
        { value:">", text:">" },
        { value:">=", text:">=" },
        { value:"between", text:"Between" }
      ];
      c += ui.dropdown({ name:"operator", items:items, value:operator, width: 120, onchange:"ui_list.presetoptionfilteroperatorchange(value, this)" });
      break;
    default :
      var items = [
        { value:"equals", text:"Equals" },
        { value:"contains", text:"Contains" },
        { value:"in", text:"In" }
      ];
      c += ui.dropdown({ name:"operator", items:items, value:operator, width: 100, onchange:"ui_list.presetoptionfilteroperatorchange(value, this)" });
      break;
  }
  c += ui.hidden({ name:"type", value:datatype });

  return c;
}

ui_list.reportoptiongrouptoggle = function(el){
  var active = ui.checkbox_value(el);
  report['presets'][report['presetidx']]['viewtype'] = active ? 'group' : 'list';
}
ui_list.presetoptiongroupsave = function(){

  var groups = [];
  var tabgroup = ui('.tabgroups');
  var grouplist = ui('.grouplist', tabgroup);
  var grouplistscrollable = ui('.scrollable', grouplist);
  for(var i = 0 ; i < grouplistscrollable.children.length - 1; i++){
    var grouptable = grouplistscrollable.children[i];
    var name = ui.dropdown_value(ui('%name', grouptable));
    var aggregrate = ui.dropdown_value(ui('%aggregrate', grouptable));
    var columns = [];
    var groupcolumns = ui('.groupcolumns', grouptable);
    for(var j =  0 ; j < groupcolumns.firstElementChild.children.length - 1 ; j++){
      var tr = groupcolumns.firstElementChild.children[j];
      var columnname = ui.dropdown_value(ui('%columnname', tr));
      var columnlogic = ui.dropdown_value(ui('%columnlogic', tr));
      columns.push({ name:columnname, logic:columnlogic });
    }

    if(name.length > 0 && columns.length > 0){
      groups.push({ name:name, aggregrate:aggregrate, columns:columns });
    }
  }

  report['presets'][report['presetidx']]['groups'] = groups;

}
ui_list.presetoptiongroupload = function(){
  var tabgroup = ui('.tabgroups');
  ui.checkbox_setvalue(ui('%active', tabgroup), report['presets'][report['presetidx']]['viewtype'] == 'group' ? true : false);

  if(typeof report['presets'][report['presetidx']]['groups'] != 'undefined' && report['presets'][report['presetidx']]['groups'].length > 0){
    var groups = report['presets'][report['presetidx']]['groups'];

    var tabgroup = ui('.tabgroups');
    var grouplist = ui('.grouplist', tabgroup);
    var grouplistscrollable = ui('.scrollable', grouplist);

    grouplistscrollable.innerHTML = "<div id='groupnewcont'><button class='hollow' onclick=\"ui_list.presetoptiongroupnew()\"><span class='fa fa-plus'></span><label>Add new group</label></button><div>";
    for(var i = 0 ; i < groups.length ; i++){
      var group = groups[i];
      ui_list.presetoptiongroupnew(group);
    }
  }
}
ui_list.presetoptiongroupresize = function(){

  // Resize scrollable height
  var tabgroup = ui('.tabgroups');
  var grouplist = ui('.grouplist', tabgroup);
  var grouplistscrollable = ui('.scrollable', grouplist);
  var presetdetail = ui('.presetdetail');
  var presetdetailcontentheight = presetdetail.clientHeight - presetdetail.firstElementChild.clientHeight - tabgroup.firstElementChild.clientHeight - 70;
  grouplistscrollable.style.height = presetdetailcontentheight + "px";
  grouplistscrollable.style.width = "560px";

}
ui_list.presetoptiongroupnew = function(obj){
  var name = ui.ov('name', obj);
  var aggregrate = ui.ov('aggregrate', obj);
  var groupcolumns = ui.ov('columns', obj);

  var tabgroup = ui('.tabgroups');
  var grouplist = ui('.grouplist', tabgroup);
  var grouplistscrollable = ui('.scrollable', grouplist);
  var columns = report['columns'];

  var columnitems = [];
  for(var i = 0 ; i < columns.length ; i++)
    columnitems.push({ text:columns[i].text, value:columns[i].name });

  var aggregrates = [
    { text:"First", value: "" },
    { text:"Monthly", value: "monthly" },
    { text:"Yearly", value: "yearly" }
  ];

  var logics = [
    { text:"First", value:"first" },
    { text:"Sum", value:"sum" },
    { text:"Count", value:"count" },
    { text:"Avg", value:"avg" },
    { text:"Min", value:"min" },
    { text:"Max", value:"max" }
  ];

  var c = "<table cellpadding='5' style='border-collapse:collapse'><tr>";
  c += "<td valign='top'><span class='padding5'><input type='checkbox' class='groupcheck'/></span></td>";
  c += "<td valign='top'>" + ui.dropdown({ name:'name', items:columnitems, value:name, width:120 }) + "</td>";
  c += "<td valign='top'>" + ui.dropdown({ name:'aggregrate', items:aggregrates, value:aggregrate, width:80 }) + "</td>";
  c += "<td valign='top' style='padding:0;margin:0'><table cellpadding='5' style='border-collapse:collapse' class='groupcolumns'>";
  if(groupcolumns){
    for(var i = 0 ; i < groupcolumns.length ; i++){
      var groupcolumn = groupcolumns[i];
      var groupcolumnname = groupcolumn['name'];
      var groupcolumnlogic = groupcolumn['logic'];

      c += "<tr>";
      c += "<td valign='middle'><input type='checkbox' class='groupcolumncheck'/></td>";
      c += "<td>" + ui.dropdown({ name:'columnname', items:columnitems, value:groupcolumnname, width:120 }) + "</td>";
      c += "<td>" + ui.dropdown({ name:'columnlogic', items:logics, value:groupcolumnlogic, width:80 }) + "</td>";
      c += "</tr>";

    }
    c += "<tr class='groupcolumnnewcont'><td colspan='3'><button class='hollow' onclick=\"ui_list.presetoptiongroupcolumnnew(this.parentNode.parentNode.parentNode.parentNode.parentNode)\"><span class='fa fa-plus'></span><label>Add new column</label></button></td></tr>";
  }
  else{

    c += "<tr>";
    c += "<td valign='middle'><input type='checkbox' class='groupcolumncheck'/></td>";
    c += "<td>" + ui.dropdown({ name:'columnname', items:columnitems, value:'', width:120 }) + "</td>";
    c += "<td>" + ui.dropdown({ name:'columnlogic', items:logics, value:'', width:80 }) + "</td>";
    c += "</tr>";
    c += "<tr class='groupcolumnnewcont'><td colspan='3'><button class='hollow' onclick=\"ui_list.presetoptiongroupcolumnnew(this.parentNode.parentNode.parentNode.parentNode.parentNode)\"><span class='fa fa-plus'></span><label>Add new column</label></button></td></tr>";

  }
  c += "</table></td>";
  c += "</tr></table>";
  grouplistscrollable.insertAdjacentHTML('beforeend', c);

  grouplistscrollable.appendChild(ui('#groupnewcont'));

}
ui_list.presetoptiongroupcolumnnew = function(td, obj){

  var groupcolumnname = ui.ov('name', obj);
  var groupcolumnlogic = ui.ov('logic', obj);
  var columns = report['columns'];

  var columnitems = [];
  for(var i = 0 ; i < columns.length ; i++)
    columnitems.push({ text:columns[i].text, value:columns[i].name });

  var logics = [
    { text:"First", value:"first" },
    { text:"Sum", value:"sum" },
    { text:"Count", value:"count" },
    { text:"Avg", value:"avg" },
    { text:"Min", value:"min" },
    { text:"Max", value:"max" }
  ];

  var c = "";
  c += "<tr>";
  c += "<td valign='middle'><input type='checkbox' class='groupcolumncheck'/></td>";
  c += "<td>" + ui.dropdown({ name:'columnname', items:columnitems, value:groupcolumnname, width:120 }) + "</td>";
  c += "<td>" + ui.dropdown({ name:'columnlogic', items:logics, value:groupcolumnlogic, width:80 }) + "</td>";
  c += "</tr>";

  var table = td.firstElementChild;
  var tbody = table.firstElementChild;
  tbody.insertAdjacentHTML('beforeend', c);
  tbody.appendChild(ui('.groupcolumnnewcont', tbody));

}
ui_list.reportoptiongroupcolumnremove = function(){

  var checkboxes = document.querySelectorAll(".groupcolumncheck:checked");
  for(var i = 0 ; i < checkboxes.length ; i++){
    var checkbox = checkboxes[i];
    var tr = checkbox.parentNode.parentNode;
    var tbody = tr.parentNode;
    tbody.removeChild(tr);
    if(tbody.children.length <= 1) ui_list.presetoptiongroupcolumnnew(tbody.parentNode.parentNode);
  }

}
ui_list.reportoptiongroupcolumnmoveup = function(){

  var checkboxes = document.querySelectorAll(".groupcolumncheck:checked");
  for(var i = 0 ; i < checkboxes.length ; i++){
    var checkbox = checkboxes[i];
    var tr = checkbox.parentNode.parentNode;
    if(tr.previousElementSibling) tr.parentNode.insertBefore(tr, tr.previousElementSibling);
  }

}
ui_list.reportoptiongroupcolumnmovedown = function(){

  var checkboxes = document.querySelectorAll(".groupcolumncheck:checked");
  for(var i = 0 ; i < checkboxes.length ; i++){
    var checkbox = checkboxes[i];
    var tr = checkbox.parentNode.parentNode;
    if(tr.nextElementSibling) tr.parentNode.insertBefore(tr.nextElementSibling, tr);
  }

}
ui_list.reportoptiongroupremove = function(){

  var checkboxes = document.querySelectorAll(".groupcheck:checked");
  for(var i = 0 ; i < checkboxes.length ; i++){
    var checkbox = checkboxes[i];
    var tr = checkbox.parentNode.parentNode.parentNode;
    var tbody = tr.parentNode;
    var table = tbody.parentNode;
    table.parentNode.removeChild(table);
  }

}

ui_list.presetapply = function(){

  ui_list.presetoptionsortsave();
  ui_list.presetoptionfiltersave();
  ui_list.presetoptiongroupsave();
  ui.async('ui_presetapply', [ report ], { waitel:"#presetsavebtn" });

}


</script>

</body>
</html>
