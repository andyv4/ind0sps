<?php

function ui_render($obj){

  $actionname = ov('actionname', $obj);
  $actionparams = ov('actionparams', $obj, 0, array());
  $containername = ov('containername', $obj);
  $containerparams = ov('containerparams', $obj);

  $isfile = file_exists("ui/$actionname") ? true : false;
  $isfunction = function_exists($actionname) ? true : false;

  $c = "<element exp='.modal'>";
  if($isfile){
    ob_start();
    ob_clean();
    include "ui/$actionname";
    $c .= ob_get_clean();
  }
  else if($isfunction){
    $c .= call_user_func($actionname, $actionparams);
  }
  $c .= "</element>";

  $c .= "<script>";
  $c .= "ui.modal_open(ui('.modal'), { closeable:false });";
  $c .= "</script>";

  echo $c;
}

function ui_presets(){

  $module = bloom_module();
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

  $c = ui_dropdown(array('items'=>$items, 'value'=>$value, 'placeholder'=>'-No Preset-', 'width'=>150, 'onchange'=>"list.presetselect(value, this)"));

  return $c;

}
function ui_presetselect($idx){

  $module = bloom_module();
  $module['presetidx'] = intval($idx);
  bloom_savemodule($module);
  return bloom_orderlist();

}

function ui_reportoption(){

  $module = bloom_module();

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
      <span class='presetdetail'>
        <div class='tabhead' data-tabbody='#reportoptiontabbody'>
          <div class='tabitem' onclick=\"ui.tabclick(event, this)\"><label>Name</label></div>
          <div class='tabitem active' onclick=\"ui.tabclick(event, this)\"><label>Columns</label></div>
          <div class='tabitem' onclick=\"ui.tabclick(event, this)\"><label>Sorts</label></div>
          <div class='tabitem' onclick=\"ui.tabclick(event, this)\"><label>Filters</label></div>
          <div class='tabitem' onclick=\"ui.tabclick(event, this)\"><label>Groups</label></div>
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
            <td><button id='presetsavebtn' class='red' onclick=\"list.presetapply()\"><span class='fa fa-check'></span><label>Apply</label></button></td>
            <td><button class='hollow' onclick=\"ui.modal_close(ui('.modal'))\"><span class='fa fa-check'><span><label>Cancel</label></button></td>
          </tr>
        </table>
      </div>
    </div>
  ";
  $c .= "</element>";
  $c .= uijs("
    report = " . json_encode($module) . ";
    list.presetoptionload(0);
    ui.modal_open(ui('.modal'), { width:800 });
  ");
  return $c;
}
function ui_reportoptionname(){

  $c = '';
  $c .= "<div class='scrollable'>";
  $c .= "<table class='form'>
    <tr><th><label>Preset Name</label></th><td>" . ui_textbox(array('name'=>'text', 'onchange'=>'list.presetoptiontextchange(value)')) . "</td></tr>
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

  // Column list
  $c .= "<span class='columnlist'><div class='scrollable'>";
  $c .= "</div></span>";

  // Column detail
  $c .= "<span class='columndetail'>";
  $c .= "
    <table class='form' cellspacing='5'>
      <tr><th><label>Active</label></th><td>" . ui_checkbox(array('name'=>'active', 'onchange'=>"list.presetoptioncolumndetailchange(name, value)")) . "</td></tr>
      <tr><th><label>Name</label></th><td>" . ui_label(array('name'=>'name')) . "</td></tr>
      <tr><th><label>Text</label></th><td>" . ui_textbox(array('name'=>'text', 'width'=>100, 'onchange'=>"list.presetoptioncolumndetailchange(name, value)")) . "</td></tr>
      <tr><th><label>Width</label></th><td>" . ui_textbox(array('name'=>'width', 'width'=>100, 'onchange'=>"list.presetoptioncolumndetailchange(name, value)")) . "</td></tr>
      <tr><th><label>Align</label></th><td>" . ui_dropdown(array('name'=>'align', 'width'=>100, 'items'=>$aligns, 'value'=>'', 'onchange'=>"list.presetoptioncolumndetailchange(name, value)")) . "</td></tr>
      <tr><th><label>Type</label></th><td>" . ui_dropdown(array('name'=>'datatype', 'width'=>100, 'items'=>$datatypes, 'value'=>'', 'onchange'=>"list.presetoptioncolumndetailchange(name, value)")) . "</td></tr>
      <tr><th><label>Letter Case</label></th><td>" . ui_dropdown(array('name'=>'lettercase', 'width'=>100, 'items'=>$lettercases, 'value'=>'', 'onchange'=>"list.presetoptioncolumndetailchange(name, value)")) . "</td></tr>
    </table>
  ";
  $c .= "</span>";

  return $c;
}
function ui_reportoptionsorts(){

  $c = '';

  // Toolbar
  $c .= "<div>
    <button class='hollow' onclick=\"t.presetoptionsortnew()\"><span class='fa fa-plus'></span></button>
    <button class='hollow' onclick=\"t.presetoptionsortremove()\"><span class='fa fa-minus'></span></button>
    <button class='hollow' onclick=\"t.presetoptionsortmoveup()\"><span class='fa fa-caret-up'></span></button>
    <button class='hollow' onclick=\"t.presetoptionsortmovedown()\"><span class='fa fa-caret-down'></span></button>
  </div>";

  $c .= "<div class='sortlist'><div class='scrollable'></div></div>";

  return $c;

}
function ui_reportoptionfilters(){

  $c = '';

  // Toolbar
  $c .= "<div>
    <button class='hollow' onclick=\"list.presetoptionfilternew()\"><span class='fa fa-plus'></span></button>
    <button class='hollow' onclick=\"list.presetoptionfilterremove()\"><span class='fa fa-minus'></span></button>
    <button class='hollow' onclick=\"list.presetoptionfiltermoveup()\"><span class='fa fa-caret-up'></span></button>
    <button class='hollow' onclick=\"list.presetoptionfiltermovedown()\"><span class='fa fa-caret-down'></span></button>
  </div>";

  $c .= "<div class='filterlist'><div class='scrollable'></div></div>";

  return $c;

}
function ui_reportoptiongroups(){

  $c = '';

  $c .= "<table class='form'>
    <tr><th>Active</th><td>" . ui_checkbox(array('name'=>'active', 'onchange'=>"t.reportoptiongrouptoggle(this)")) . "</td></tr>
  </table>";


  $c .= "<span class='grouplist'>
  <div>
    <button class='hollow' onclick=\"t.presetoptiongroupnew()\"><span class='fa fa-plus'></span></button>
    <button class='hollow' onclick=\"t.presetoptiongroupremove()\"><span class='fa fa-minus'></span></button>
    <button class='hollow' onclick=\"t.presetoptiongroupmoveup()\"><span class='fa fa-caret-up'></span></button>
    <button class='hollow' onclick=\"t.presetoptiongroupmovedown()\"><span class='fa fa-caret-down'></span></button>
  </div>
  <div class='scrollable'></div>
  </span>";

  $c .= "<span class='groupdetail'>
  <div>
    <button class='hollow' onclick=\"t.presetoptiongroupdetailnew()\"><span class='fa fa-plus'></span></button>
    <button class='hollow' onclick=\"t.presetoptiongroupdetailremove()\"><span class='fa fa-minus'></span></button>
    <button class='hollow' onclick=\"t.presetoptiongroupdetailmoveup()\"><span class='fa fa-caret-up'></span></button>
    <button class='hollow' onclick=\"t.presetoptiongroupdetailmovedown()\"><span class='fa fa-caret-down'></span></button>
  </div>
  <div class='scrollable'></div>
  </span>";

  return $c;

}

function ui_columnresize($name, $width){
  $module = bloom_module();
  $presetidx = $module['presetidx'];

  for($i = 0 ; $i < count($module['presets'][$presetidx]['columns']) ; $i++){
    if($module['presets'][$presetidx]['columns'][$i]['name'] == $name){
      $module['presets'][$presetidx]['columns'][$i]['width'] = $width;
    }
  }

  bloom_savemodule($module);
}
function ui_columnapply($columns){

  $columns = array_index($columns, array('name'), 1);

  $module = bloom_module();
  $presetidx = $module['presetidx'];
  $preset = $module['presets'][$presetidx];
  for($i = 0 ; $i < count($preset['columns']) ; $i++){
    $name = $preset['columns'][$i]['name'];
    if(isset($columns[$name]))
      $preset['columns'][$i]['active'] = $columns[$name]['active'];
  }
  $module['presets'][$presetidx] = $preset;

  bloom_savemodule($module);

  return bloom_orderlist();

}
function ui_sortapply($name){
  $module = bloom_module();
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

  bloom_savemodule($module);

  return bloom_orderlist();
}
function ui_presetapply($module){

  bloom_savemodule($module);
  return uijs("ui.modal_close(ui('.modal'));") . bloom_orderlist();

}

?>