<?php

if(!isset($_SESSION['ui_control']) || isset($_GET['reset'])){
  $_SESSION['ui_control'] = 'textbox';
  $_SESSION['ui_control_properties']['textbox'] = defaultcontrol('textbox');
}

function defaultcontrol($type){

  $defaultcontrols = array(
    'textbox'=>array(
        'value'=>'Hello World',
        'width'=>'300px',
        'datatype'=>'text',
        'readonly'=>0
    ),
    'dropdown'=>array(
      'items'=>array(
        array('text'=>'Dropdown-1', 'value'=>'Dropdown-1'),
        array('text'=>'Dropdown-2', 'value'=>'Dropdown-2'),
        array('text'=>'Dropdown-3', 'value'=>'Dropdown-3')
      ),
      'value'=>'Dropdown-1',
      'width'=>300,
      'readonly'=>0
    )
  );
  return $defaultcontrols[$type];

}

$controls = array(
  'textbox'=>array(
    'width'=>array('datatype'=>'text'),
    'readonly'=>array('datatype'=>'bool'),
    'datatype'=>array('datatype'=>'enum', 'enums'=>'text,number'),
    'value'=>array('datatype'=>'text')
  ),
  'dropdown'=>array(
    'width'=>array('datatype'=>'text'),
    'value'=>array('datatype'=>'text'),
    'readonly'=>array('datatype'=>'bool')
  )
);
$controlitems = array(
  array('text'=>'Textbox', 'value'=>'textbox'),
  array('text'=>'Dropdown', 'value'=>'dropdown')
);

function ui_controlchange($type){

  if(!isset($_SESSION['ui_control_properties'][$type]))
    $_SESSION['ui_control_properties'][$type] = defaultcontrol($type);
  $_SESSION['ui_control'] = $type;

  return ui_control_reload();

}

function ui_control_setproperty($name, $value){

  $_SESSION['ui_control_properties'][$_SESSION['ui_control']][$name] = $value;

  return ui_control_reload();

}

function ui_control_render(){

  $properties = $_SESSION['ui_control_properties'][$_SESSION['ui_control']];
  console_log($properties);
  $c = call_user_func_array('ui_' . strtolower($_SESSION['ui_control']), array($properties));
  return "<element exp='#samplecont'>" . $c . "</element>";

}

function ui_controlproperty_render(){

  global $controls;
  $available_properties = $controls[$_SESSION['ui_control']];
  $properties = $_SESSION['ui_control_properties'][$_SESSION['ui_control']];

  $c = "<element exp='#propertycont'>";

  $c .= "<table class=\"form\">";
  foreach($properties as $name=>$value){

    if(isset($available_properties[$name])){

      switch($available_properties[$name]['datatype']){

        case 'text':
          $c .= "<tr><th><label>$name</label></th><td>" . ui_textbox(array('name'=>$name, 'width'=>100, 'onchange'=>"ui.async('ui_control_setproperty', [ name, value ], {})", 'value'=>$value)) . "</td></tr>";
          break;

        case 'enum':

          $enums = explode(',', $available_properties[$name]['enums']);
          $items = array();
          foreach($enums as $enum){
            $items[] = array('text'=>$enum, 'value'=>$enum);
          }

          $c .= "<tr><th><label>$name</label></th><td>" . ui_dropdown(array('name'=>$name, 'items'=>$items, 'width'=>100, 'onchange'=>"ui.async('ui_control_setproperty', [ name, value ], {})", 'value'=>$value)) . "</td></tr>";
          break;

        case 'bool':

          $c .= "<tr><th><label>$name</label></th><td>" . ui_checkbox(array('name'=>$name, 'onchange'=>"ui.async('ui_control_setproperty', [ name, value ], {})", 'value'=>$value)) . "</td></tr>";
          break;



      }


    }

  }
  $c .= "</table>";

  $c .= "</element>";
  return $c;

}

function ui_control_reload(){

  return ui_control_render() . ui_controlproperty_render();

}

ui_async();
?>


<div class="padding10">

  <h4>Controls</h4>
  <br />

  <?=ui_dropdown(array('items'=>$controlitems, 'value'=>$_SESSION['ui_control'], 'width'=>200, 'onchange'=>"ui.async('ui_controlchange', [ value ], { waitel:this })"))?>

  <br /><br />

  <h4>Textbox</h4>
  <br />

  <div id="samplecont"></div>

  <div class="height10"></div>

  <h4>Properties</h4>
  <br />
  <div id="propertycont"></div>

  <script type="text/javascript">

    ui.async('ui_control_reload');

  </script>

</div>