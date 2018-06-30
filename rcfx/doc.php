<?php

$columns = array(
  array('active'=>1, 'name'=>'name', 'width'=>100, 'align'=>'right'),
  array('active'=>1, 'name'=>'datatype', 'width'=>50, 'align'=>'center'),
  array('active'=>1, 'name'=>'required', 'width'=>30, 'align'=>'center', 'type'=>'html', 'html'=>'ui_gridrequired'),
  array('active'=>1, 'name'=>'samplevalue', 'width'=>200, 'type'=>'html', 'html'=>'ui_gridcreatenew'),
  array('active'=>1, 'name'=>'remark', 'width'=>'100%'),
);

data_sort($properties, array(array('name'=>'required', 'sorttype'=>'desc'), array('name'=>'name', 'sorttype'=>'asc')));

function ui_gridrequired($obj){

  return $obj['required'] ? "<span class='plat-green'>Yes</span>" : "<span class='plat-red'>No</span>";

}

function ui_gridcreatenew($obj){

  return ui_textbox(array('name'=>'samplevalue', 'value'=>$obj['samplevalue'])) .
    ui_hidden(array('name'=>'name', 'value'=>$obj['name']));

}

function ui_generate($properties){

  $c = ui_render(ui_sampleexp($properties), '#samplecont');

  global $name;
  $params = array();
  foreach($properties as $property)
    if(isset($property['samplevalue']) && !empty($property['samplevalue'])) $params[$property['name']] = $property['samplevalue'];
  $c .= uijs("ui.textarea_setvalue(ui('#tag'), \"ui_$name(" . array_to_phparray($params) . ")\")");


  return $c;

}

function ui_sampleexp($properties){

  global $name;
  $params = array();
  foreach($properties as $property)
    if(isset($property['samplevalue']) && !empty($property['samplevalue'])) $params[$property['name']] = $property['samplevalue'];
  $sampleexp = call_user_func_array("ui_" . $name, array($params));

  return $sampleexp;

}

ui_async();
?>
<div class="padding20">

  <h3><?=ucwords($name)?> Documentation</h3>
  <br /><br /><br />
  <h4>Properties</h4>
  <br />
  <?=ui_gridhead(array('columns'=>$columns, 'gridexp'=>'#grid1'))?>
  <?=ui_grid(array('columns'=>$columns, 'value'=>$properties, 'id'=>'grid1'))?>
  <br /><br />
  <h4>Sample</h4>
  <button class="hollow" onclick="ui.async('ui_generate', [ ui.grid_value(ui('#grid1')) ], {})"><label>Generate Control</label></button>
  <br /><br />
  <div id="samplecont"><?=ui_sampleexp($properties)?></div>
  <br /><br />
  <h4>Tag</h4>
  <br /><br />
  <?=ui_textarea(array('id'=>'tag', 'width'=>600, 'height'=>100))?>
  <div style="height:100px"></div>

</div>