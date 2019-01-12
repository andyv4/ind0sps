<?php
require_once dirname(__FILE__) . '/util.php';
require_once dirname(__FILE__) . '/cache.php';

$__UI_STORE = [];

function ui_autocompleteitems($popupuiid, $src, $hint, $param3 = null, $param4 = null, $param5 = null){

  // Auto include
  if(substr($src, 0, 3) == 'ui_'){
    $uifiles = glob(base_path() . '/ui/*.php');
    foreach($uifiles as $uifile){
      $ui = explode('.', basename($uifile))[0];
      if(strpos($src, $ui) !== false){
        require_once $uifile;
      }
    }
  }

  $c = "<element exp='$" . $popupuiid . "'>";
  if(function_exists($src)){
    $items = call_user_func_array($src, array(array('hint'=>$hint, 'param'=>$param3, 'param1'=>$param4, 'param2'=>$param5)));
    if(is_array($items) && count($items) > 0){
      for($i = 0 ; $i < count($items) ; $i++){
        $item = $items[$i];
        $text = $item['text'];
        $value = $item['value'];
        $c .= "<div class='menuitem' data-value=\"$value\" data-obj=\"" . htmlentities(json_encode($item)) . "\">$text</div>";
      }
    }
    else{
      $c .= "<div class='menuitem' data-value=\"\" data-obj=\"" . htmlentities(json_encode([ 'text'=>'', 'value'=>'' ])) . "\">Tidak ada data</div>";
    }
  }
  else
    throw new Exception("Function not exists ($src)");
  $c .= "</element>";
  return $c;

}
function ui_autocomplete($params){

	$id = ov('id', $params);
	$name = ov('name', $params);
	$src = ov('src', $params);
	$width = ov('width', $params);
  $value = ov('value', $params);
  $text = ov('text', $params, 0, $value);
  $placeholder = ov('placeholder', $params);
	$onchange = ov('onchange', $params);
  $readonly = ov('readonly', $params);
  $ischild = ov('ischild', $params, 0, 0);
  $prehint = ov('prehint', $params, 0, '');
  $any_text = ov('any_text', $params, 0, 0);
  $class = ov('class', $params, 0, 0);

  $readonly_exp = $readonly ? 'readonly' : '';

	$c = "	
  <span id=\"$id\" class='autocomplete $readonly_exp {$class}' data-ischild='$ischild' data-type='autocomplete' style='width:$width;' data-name='$name' data-src='$src'
    data-prehint=\"$prehint\" data-onchange=\"$onchange\" data-value=\"$value\" data-any-text='$any_text'>
    <input type='text' onkeyup='ui.autocomplete_keyup.call(this, event)' value=\"$text\" placeholder=\"$placeholder\" $readonly_exp/>
    <span class='fa fa-search'></span>
    <div class='popup off animated'></div>
  </span>
	";
	
	return $c;
}

function ui_chart($params){

  $dataset = ov('value', $params);

  /*

    $dataset = array();
    $count = rand(1, 5);
    for($i = 0 ; $i < $count ; $i++){
      $obj = array();
      for($j = 0 ; $j < 10 ; $j++){
        $obj[] = rand(0, 10);
      }
      $dataset[] = $obj;
    }

    $dataset = array(
        array(80, 10, 0, 10, 80, 10, 0, 10, 80),
        array(0, 80, 10, 80, 10, 80, 10, 80, 0)
    );

  }*/

  $height = intval(ov('height', $params));
  $width = intval(ov('width', $params));
  $uid = 'ct' . uniqid();
  $padding = 20;
  $contentwidth = $width - ($padding * 2);
  $contentheight = $height - ($padding * 2);

  $x_ratio = 1;
  foreach($dataset as $datarow){
    $x_ratio = round($contentwidth / (count($datarow) - 1), 2);
    break;
  }

  $maxvalue = 0;
  foreach($dataset as $datarow){
    foreach($datarow as $datacol)
      if($datacol > $maxvalue) $maxvalue = $datacol;
  }
  $y_ratio = $contentheight / $maxvalue;

  $colors = array(
    array('#FFF', '#4A89DC', 'rgba(74, 137, 220, .5)'),
    array('#FFF', '#37BC9B', 'rgba(55, 188, 155, .5)'),
    array('#FFF', '#8CC152', 'rgba(140, 193, 82, .5)'),
    array('#FFF', '#F6BB42', 'rgba(246, 187, 66, .5)'),
    array('#FFF', '#967ADC', 'rgba(150, 122, 220, .5)')
  );

  $c = "<span class='chart' style='height:$height;'>
  <svg id='$uid' width='$width' height='$height' style='width:$width;height:$height'>";

  // Legend
  for($x = $padding ; $x < $width ; $x+=$x_ratio)
    $c .= "<path d='M$x $padding L$x " . ($height - $padding) . "' fill='transparent' stroke='#F5F5F5' stroke-width='0.5'/>";
  for($y = $padding ; $y < $height ; $y+=$padding)
    $c .= "<path d='M$padding $y  L" . ($width - $padding) . " $y' fill='transparent' stroke='#F5F5F5' stroke-width='0.5'/>";


  // Value
  $points = array();
  for($i = 0 ; $i < count($dataset) ; $i++){
    $obj = $dataset[$i];

    // Line value
    $path_d = array();
    for($j = 0 ; $j < count($obj) ; $j++){
      $value = $obj[$j];
      $x = $padding + ($j * $x_ratio);
      $y = $height - $padding - ($value * $y_ratio);
      if($j == 0) $path_d[] = "M$x $y";
      else $path_d[] = "L$x $y";
    }
    $path_d[] = "L$x $height";
    $path_d[] = "L" . ($padding + (0 * $x_ratio)) . " $height";
    $path_d[] = "Z";
    //$c .= "<path d='" . implode(' ', $path_d) . "' fill='" . $colors[$i][2] . "' stroke='transparent' stroke-width='0'/>";
    $c .= "<path d='" . implode(' ', array_splice($path_d, 0, count($path_d) - 3)) . "' fill='transparent' stroke='" . $colors[$i][1] . "' stroke-width='2'/>";

    // Dot value
    for($j = 0 ; $j < count($obj) ; $j++){
      $value = $obj[$j];
      $x = $padding + ($j * $x_ratio);
      $y = $height - $padding - ($value * $y_ratio);
      // 200 - (10 * 2) - (80 * 2.25)
      $points[] = "$x,$y";

      $c .= "<circle class='chart-line-circle' cx='$x' cy='$y' r='3' fill='" . $colors[$i][1] . "' stroke='" . $colors[$i][1] . "' stroke-width='0' />";
    }
  }
  $c .= "</svg>
  </span>";

  $c .= uijs("console.warn('width:$width, height:$height')");
  $c .= uijs("console.warn('maxvalue:$maxvalue')");
  $c .= uijs("console.warn('contentwidth:$contentwidth, contentheight:$contentheight')");
  $c .= uijs("console.warn('x_ratio:$x_ratio, y_ratio:$y_ratio')");
  $c .= uijs("console.log(" . json_encode(ov('value', $params)) . ")");
  $c .= uijs("console.log('" . implode('  ', $points) . "')");

  return $c;
}

function ui_checkbox($params){

  $id = ov('id', $params);
  $name = ov('name', $params);
  $items = ov('items', $params);
  $itemwidth = ov('itemwidth', $params);
  $uid = uniqid();
  $width = ov('width', $params);
  $text = ov('text', $params);
  $value = ov('value', $params);
  $readonly = ov('readonly', $params);
  $onchange = ov('onchange', $params);
  $style = ov('style', $params, 0, '');
  $ischild = ov('ischild', $params, 0, 0);
  if($ischild) $ischild = 1;

  $c = '';
  $c .= "<span id='$id' class='checkbox' data-name='$name' data-ischild='$ischild' data-onchange=\"$onchange\" data-type='checkbox' style='width:$width;white-space: pre-wrap;$style'>";
  if(is_array($items))
    for($i = 0 ; $i < count($items) ; $i++){
      $item = $items[$i];
      $text = ov('text', $item, 0, 'No text');
      $value = ov('value', $item);
      $uuid = 'c' . $uid . $i;

      $c .= "<span class='item' style='width:$itemwidth;'>";
      $c .= "<input id='$uuid' type='checkbox' value=\"$value\"/>";
      $c .= "<label for='$uuid'>$text</label>";
      $c .= "</span>";
    }
  else{
    $checked_exp = $value ? 'checked' : '';
    $disabled_exp = $readonly ? 'disabled' : '';

    $c .= "<span class='item' style='width:$itemwidth;'>";
    $c .= "<input id='$uid' type='checkbox' onchange=\"ui.checkbox_onchange(event, this)\" $checked_exp $disabled_exp/>";
    if(strlen($text) > 0) $c .= "<label for='$uid'>$text</label>";
    $c .= "</span>";
  }

  $c .= "</span>";

  return $c;
}

function ui_control($params){
  $type = ov('type', $params);
  if(function_exists("ui_" . $type)){
    return call_user_func_array("ui_" . $type, array($params));
  }
  return "Undefined control ($type)";
}

function ui_codeeditor($params){

  $name = ov('name', $params);
  $width = ov('width', $params);
  $height = ov('height', $params);

  $c = '';
  $c .= "<pre class='codeeditor' data-type='codeeditor' data-name='$name' spellcheck='off' style='width:$width;height:$height' contenteditable='true' onkeydown='ui.codeeditor_keydown(event, this)' onkeyup='ui.codeeditor_keyup(event, this)'>
    return array('name'=>'asd');
  </pre>";
  return $c;

}

function ui_dayselector($params){

  $name = ov('name', $params);
  $id = ov('id', $params);
  $uid = uniqid();
  $days = array(
    array('text'=>'Sun', 'value'=>'sun'),
    array('text'=>'Mon', 'value'=>'mon'),
    array('text'=>'Tue', 'value'=>'tue'),
    array('text'=>'Wed', 'value'=>'wed'),
    array('text'=>'Thu', 'value'=>'thu'),
    array('text'=>'Fri', 'value'=>'fri'),
    array('text'=>'Sat', 'value'=>'sat')
  );
  $width = ov('width', $params);
  $value = ov('value', $params);

  $c = '';
  $c .= "<span class='dayselector' data-type='dayselector' data-name='$name' id='$id' style='width:$width;'>";
  for($i = 0 ; $i < count($days) ; $i++){
    $uuid = $uid . $i;
    $itemvalue = $days[$i]['value'];
    $checked = strpos($value, $itemvalue) !== false ? true : false;

    $c .= "<span class='day'>";
    $c .= "<input id='$uuid' type='checkbox' value='$itemvalue' " . ($checked ? ' checked' : '') . "/>";
    $c .= "<label for='$uuid'>" . $days[$i]['text'] . "</label>";
    $c .= "</span>";
  }
  $c .= "</span>";

  return $c;
}

function ui_datepicker($params){
  $id = ov('id', $params);
  $idchild = ov('ischild', $params);
  $name = ov('name', $params);
  $readonly = ov('readonly', $params);
  $width = ov('width', $params);
  $value = ov('value', $params);
  $onchange = ov('onchange', $params);
  $hidden = ov('hidden', $params, 0, 0);
  $align = ov('align', $params, 0, 'left');
  $placeholder = ov('placeholder', $params, 0, '[Pilih Tanggal]');

  $ischildExp = $idchild ? "data-ischild='1'" : "";
  $readonlyExp = $readonly ? 'readonly' : '';
  $valueExp = (strtotime($value) > 0 && strtotime($value) != strtotime('invalid')) ? date('j M Y', strtotime($value)) : $placeholder;
  $hiddenExp = $hidden ? 'off' : '';

  $c = "<span id='$id' class='datepicker $readonlyExp $hiddenExp' data-type='datepicker' data-onchange=\"$onchange\" data-name='$name'
    onclick=\"ui.datepicker_openselector(event, this)\" style=\"text-align:$align;\" $ischildExp>";
  $c .= "<label type='text' style='width:$width;' $readonlyExp>$valueExp</label>";
  $c .= "<span class='fa fa-calendar'></span>";
  $c .= "</span>";
  return $c;
}

function ui_datepicker1($params){

  $name = ov('name', $params);

  $c = "<span class='datepicker' data-name='$name'>";

  // Date
  $c .= "<select>";
  for($i = 1 ; $i <= 31 ; $i++)
    $c .= "<option value='$i'>$i</option>";
  $c .= "</select>";

  // Month
  $months = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
  $c .= "<select>";
  for($i = 0 ; $i < count($months) ; $i++)
    $c .= "<option value='$i+1'>$months[$i]</option>";
  $c .= "</select>";

  // Year
  $c .= "<select>";
  for($i = 2015 ; $i <= 2020 ; $i++)
    $c .= "<option value='$i'>$i</option>";
  $c .= "</select>";

  $c .= "</span>";

  return $c;
}

function ui_detailview($params){

  $obj = ov('value', $params);

  $c = "<table class='detailview'>";

  foreach($obj as $key=>$value){
    $c .= "<tr>";
    $c .= "<th>$key</th>";
    $c .= "<td>";
    $c .= $value;
    $c .= "</td>";
    $c .= "</tr>";
  }

  $c .= "</table>";

  return $c;
}

function ui_dialog($title = 'Error', $message, $params = null){

  $c = "<element exp='.dialog'>";
  $c .= "
      <div class='box-dialog' style='width:360px'>
        <div class='scrollable'>
          <label><b>$title</b></label>
          <div></div>
          <p>" . sanitize_text_output($message) . "</p>
        </div>
        <div style='height: 15px'></div>
        <button class='red' onclick=\"ui.dialog_close()\"><span class='fa fa-check'></span><label>Close</label></button>
      </div>
      ";
  $c .= "</element>";
  $c .= uijs("
        ui.dialog_open();
      ");
  return $c;

}

function ui_dropdown($params){

  $id = ov('id', $params);
  $align = ov('align', $params);
  $name = ov('name', $params);
  $src = ov('src', $params);
  $items = ov('items', $params);
  $width = ov('width', $params);
  $value = ov('value', $params);
  $class = ov('class', $params, 0);
  $placeholder = ov('placeholder', $params, 0, '-Select-');
  $onchange = ov('onchange', $params);
  $readonly = ov('readonly', $params);

  if(is_array($value)) $value = ov('value', $value);
  $selecteditem = null;
  if(is_array($items)){
    for($i = 0 ; $i < count($items) ; $i++){
      if($items[$i]['value'] == $value){
        $selecteditem = $items[$i];
        if(!isset($selecteditem['text']) || empty($selecteditem['text'])) $selecteditem['text'] = $selecteditem['value'];
        break;
      }
    }
  }

  $readonly_exp = $readonly ? 'readonly' : '';

  $c = "
  <span id='$id' class='dropdown $class $readonly_exp' data-name='$name' data-type='dropdown' data-src='$src' data-onchange=\"$onchange\"
    data-items=\"" . htmlentities(json_encode($items)) . "\"
    style=\"width:$width;text-align:$align\" data-value=\"" . ($selecteditem != null ? htmlentities($selecteditem['value']) : '') . "\">
    <label onclick='ui.dropdown_open(this.parentNode, event)'>" . ($selecteditem != null ? $selecteditem['text'] : $placeholder) . "</label>
    <span onclick='ui.dropdown_open(this.parentNode, event)' class='fa fa-caret-down'></span>
    <div class='popup off animated'>";
  if(is_array($items))
    for($i = 0 ; $i < count($items) ; $i++){
      $item = $items[$i];
      $itemvalue = $item['value'];
      $itemtext = ov('text', $item, 0, $itemvalue);
      $c .= "<div class='menuitem' data-value=\"$itemvalue\" onclick=\"ui.dropdown_menuitemclick.apply(this, [ event ]);\">$itemtext</div>";
    }
  $c .= "</div></span>";
  return $c;

}
function ui_dropdownitems($popupuiid, $src){

  $c = "<element exp='$" . $popupuiid . "'>";
  if(function_exists($src)){
    $items = call_user_func_array($src, array());
    if(is_array($items)){
      for($i = 0 ; $i < count($items) ; $i++){
        $item = $items[$i];
        $itemvalue = $item['value'];
        $itemtext = ov('text', $item, 0, $itemvalue);
        $c .= "<div class='menuitem' data-value=\"$itemvalue\" onclick=\"ui.dropdown_menuitemclick.apply(this, [ event ]);\">$itemtext</div>";
      }
    }
  }
  $c .= "</element>";
  return $c;
}

function ui_functionlog_params($source){

  return ui_dialog('Params', print_r(unserialize($source), 1));

}
function ui_functionlog_tree($datas, $parent = 0, $depth = 0, $skips = array()){

  if($depth > 1000) return ''; // Make sure not to have an endless recursion
  $tree = "<ul" . ($depth > 3 ? " style='display:none'" : '') . ">";
  for($i=0, $ni=count($datas); $i < $ni; $i++){
    if(in_array($datas[$i]['functionname'], $skips)) continue;
    if($datas[$i]['caller'] == $parent){
      $tree .= '<li>';
      $tree .= "<span>";
      $tree .= "<table cellspacing='2'><tr>";
      $tree .= "<td onclick=\"ui.togglecss(this.parentNode.parentNode.parentNode.parentNode.nextElementSibling, 'display', 'none|block')\">" . $datas[$i]['functionname'] . "</td>";
      $tree .= "<td onclick=\"ui.async('ui_functionlog_params', [ this.firstElementChild.getAttribute('data-value') ])\"><span style='max-width:100px;text-overflow:ellipsis;overflow:hidden;white-space:nowrap' data-value='" . $datas[$i]['params'] . "'>params</span></td>";
      $tree .= "<td>" . $datas[$i]['duration'] . "ms</td>";
      $tree .= "</tr></table>";
      $tree .= "</span>";
      $tree .= ui_functionlog_tree($datas, $datas[$i]['functionname'], $depth + 1, $skips);
      $tree .= '</li>';
    }
  }
  $tree .= '</ul>';
  return $tree;

}
function ui_functionlog($params){

  $arr = ov('value', $params);
  //$arr = array_splice($arr, 0, 10);



  $c = "<span class='' data-type='functionloglist'>";
  $c .= "<ul>";
  $c .= '<li>';
  $c .= "<span>";
  $c .= "<table cellspacing='2'><tr>";
  $c .= "<td onclick=\"ui.togglecss(this.parentNode.parentNode.parentNode.parentNode.nextElementSibling, 'display', 'none|block')\">" . $arr[0]['functionname'] . "</td>";
  $c .= "<td onclick=\"ui.async('ui_functionlog_params', [ this.firstElementChild.getAttribute('data-value') ])\"><span style='max-width:100px;text-overflow:ellipsis;overflow:hidden;white-space:nowrap' data-value='" . $arr[0]['params'] . "'>params</span></td>";
  $c .= "<td>" . $arr[0]['duration'] . "ms</td>";
  $c .= "</tr></table>";
  $c .= "</span>";
  $c .= ui_functionlog_tree($arr, $arr[0]['functionname'], 1);
  $c .= '</li>';
  $c .= '</ul>';
  $c .= "</span>";
  return $c;

}

function ui_formrow($label, $control){

  return "<tr><th><label>$label</label></th><td>$control</td></tr>";

}

function ui_formrow2($label, $control1, $control2){

  return "<tr><th><label>$label</label></th><td>$control1</td><td>$control2</td></tr>";

}

function ui_genericdetail($params){

  $value = ov('value', $params);

  $c = "<div class='genericdetail'>";
  if(is_array($value)){
    $c .= ui_genericdetail_object($value);
    $c .= ui_genericdetail_array($value);
  }
  else if(is_string($value))
    $c .= "<label>$value</label>";
  $c .= "</div>";
  return $c;

}
function ui_genericdetail_array($input){

  $c = '';
  if(is_array($input) && !is_assoc($input)){
    for($i = 0 ; $i < count($input) ; $i++){
      $value = $input[$i];
      if(is_array($value)){
        $c .= ui_genericdetail_object($value);
        $c .= ui_genericdetail_array($value);
      }
      else if(is_string($value))
        $c .= "<label>$value</label>";
    }
  }
  return $c;

}
function ui_genericdetail_object($input){

  $c = "<table cellspacing='0'>";;
  if(is_array($input) && is_assoc($input)){
    foreach($input as $key=>$value){
      $c .= "<tr>";
      $c .= "<th><label>$key</label></th>";
      $c .= "<td>";
      if(is_array($value)){
        $c .= ui_genericdetail_object($value);
        $c .= ui_genericdetail_array($value);
      }
      else if(is_string($value))
        $c .= "<p>$value</p>";
      $c .= "</td>";
      $c .= "</tr>";
    }
  }
  $c .= "</table>";
  return $c;

}

$__UIGRID_MAXITEMPERPAGE = 300;
$__UIGRID_PARTITION_SIZE = 30;
$__UIGRID_LASTROWOBJ = null;
function ui_gridhead($params){
  $id = ov('id', $params);
  $columns = ov('columns', $params);

  $gridid = ov('gridexp', $params);
  $oncolumnclick = ov('oncolumnclick', $params);
  $oncolumnresize = ov('oncolumnresize', $params);
  $oncolumnapply = ov('oncolumnapply', $params);
  $uid = uniqid();
  $class = ov('class', $params,  0);
  $customgridheadcolumns = ov('customgridheadcolumns', $params);

  // If no columns defined, try to get columns from grid
  if(!$columns){
    global $__UI_STORE;
    $grid_params = isset($__UI_STORE[$gridid]) ? $__UI_STORE[$gridid] : null;
    $columns = isset($grid_params['columns']) ? $grid_params['columns'] : $columns;
  }

  if(is_array($customgridheadcolumns)){

    for($i = 0 ; $i < count($columns) ; $i++){
      $column = $columns[$i];
      $text = $column['text'];
      if(preg_match_all('/%\w+%/', $text, $matches)){
        $match = $matches[0][0];
        if(isset($customgridheadcolumns[$match])){
          $columns[$i]['text'] = str_replace($match, $customgridheadcolumns[$match], $columns[$i]['text']);
        }
      }


    }

  }

  $c = "
  <div class='$class grid gridhead'>
    <table id='$id' data-gridid='$gridid' data-oncolumnclick=\"$oncolumnclick\" data-oncolumnapply=\"$oncolumnapply\" data-oncolumnresize=\"$oncolumnresize\">
      <thead>
        <tr>";
  if(is_array($columns)){
    for($i = 0 ; $i < count($columns) ; $i++){
      $column = $columns[$i];
      $name = ov('name', $column);
      $class = ov('class', $column);
      $type = ov('type', $column);
      $active = ov('active', $column);
      if(!$active) continue;
      $datatype = ov('datatype', $column, 0, 'text');
      $name = $column['name'];
      $text = isset($column['text']) ? $column['text'] : $name;
      $width = ov('width', $column, 0, '50px');
      $style = ov('style', $column);

      $align = ov('align', $column);
      if(!$align){ // Generate default alignment
        if($type == 'bool') $align = 'center';
      }

      switch($datatype){
        case 'money':
          if(empty($align)) $align = 'right';
          break;
        case 'date':
          if(empty($align)) $align = 'center';
          break;
        case 'datetime':
          if(empty($align)) $align = 'center';
          break;
        case 'number':
          if(empty($align)) $align = 'right';
          break;
      }

      //$c .= "<th style='text-align:$align;width:$width;background:" . randompastelcolors() . "' data-name='$name' oncontextmenu=\"return ui.gridhead_oncontextmenu(event, this)\" data-name='$name' onclick=\"ui.gridhead_oncolumnclick(event, this)\">$text</th>";
      $c .= "<th class='$class' style='text-align:$align;width:$width;$style' data-columnname='$name' oncontextmenu=\"return ui.gridhead_oncontextmenu(event, this)\" onclick=\"ui.gridhead_oncolumnclick(event, this)\">$text</th>";
      $c .= "<th class='separator' onmousedown=\"ui.gridhead_onresizestart(event, this)\"></th>";
    }
    $c .= "<th style='width:100%;'>";
    $c .= "<div id='theadpopup' class='popup animated off'>";
    for($i = 0 ; $i < count($columns) ; $i++){
      $column = $columns[$i];
      $name = $column['name'];
      $text = $column['text'];
      $uuid = 'c' . $uid . $i;

      $c .= "<div class='item'>";
      $c .= "<input type='checkbox' id='$uuid' value='$name'/>";
      $c .= "<label for='$uuid'>$text</label>";
      $c .= "</div>";
    }
    $c .= "<div class='padding5'><button class='red' onclick='ui.gridhead_oncolumnapply(event, this)'><span class='fa fa-check'></span><label>Apply</label></button></div>";
    $c .= "</div>";
    $c .= "</th>";
  }
  $c .= "</tr>
      </thead>
    </table>
  </div>
  ";

  return $c;
}
function ui_grid($params){
  global $__UIGRID_MAXITEMPERPAGE, $__UIGRID_PARTITION_SIZE;

  $id = ov('id', $params);
  if(empty($id)) throw new Exception('Parameter id required.');
  $columns = ov('columns', $params);
  $arr = ov('value', $params);
  unset($params['value']);
  $onremove = ov('onremove', $params);
  $dataid = ov('dataid', $params, 0, uniqid());
  if(empty($dataid)) $dataid = $id;
  $totaldata = count($arr);
  $scrollel = ov('scrollel', $params);
  $cacheds = ov('cacheds', $params);
  $write_no_add = ov('write_no_add', $params, 0, 0);
  $cacheds_exists = !empty($cacheds) && function_exists($cacheds) ? 1 : 0;
  $initialvalue = ov('initialvalue', $params, 0, 1);
  $maxitemperpage = ov('maxitemperpage', $params, false, $__UIGRID_MAXITEMPERPAGE);
  $mode = ov('mode', $params);
  $class = ov('class', $params,  0, $mode == 'write' ? 'grid writable' : 'grid');
  $readonly = ov('readonly', $params);
  $name = ov('name', $params);
  $message_notassigned = ov('message_notassigned', $params, 0, 'Data not assigned');
  $message_novalue = ov('message_novalue', $params, 0, 'No data available');

  $cache_partition_count = 0;

  $c = "
  <div id='$id' class='$class' data-type='grid' data-name='$name' data-onremove=\"$onremove\" onscroll=\"ui.grid_onscroll.apply(this, arguments)\">
    <table>
      <tbody id='tbody_$dataid'>";
  if(is_array($arr)){

    if(count($arr) > $maxitemperpage && !$cacheds_exists){
      if($initialvalue){
        $viewdata = array_splice($arr, 0, $maxitemperpage);

        $cache_partition_count = ceil(count($arr) / ($maxitemperpage * $__UIGRID_PARTITION_SIZE));
        log_write('cache data count: ' . $cache_partition_count);
        log_bench_start('saving cache');
        $cache_partition_count = $cache_partition_count > 10 ? 10 : $cache_partition_count;
        for($i = 0 ; $i < $cache_partition_count ; $i++){
          $cachedata = array_splice($arr, 0, $maxitemperpage * $__UIGRID_PARTITION_SIZE);
          cache_set($dataid . $i, array('params'=>$params, 'arr'=>$cachedata));
        }
      }
      else{
        $viewdata = array();
      }
    }
    else{
      $viewdata = $arr;
    }

    if(count($viewdata) > 0){

      $viewdata = data_calculate_logicalcolumn($viewdata, $columns);

      for($i = 0 ; $i < count($viewdata) ; $i++){
        $obj = $viewdata[$i];
        $c .= ui_gridrow($obj, $params, true, $i % 2 == 0 ? 0 : 1, array('prevobj'=>$i - 1 >= 0 ? $viewdata[$i - 1] : null));
       }
    }
    else{
      if($mode != 'write'){
        if(!isset($params['value'])) $c .= "<tr class='nodatainfo'><td colspan='100' align='center'><label class='message'>$message_notassigned</label></td></tr>";
        else $c .= "<tr class='nodatainfo'><td colspan='100'><label class='message' align='center'>$message_novalue</label></td></tr>";
      }
    }

  }
  else{
    if($mode != 'write'){
      if(!isset($params['value'])) $c .= "<tr class='nodatainfo'><td colspan='100' align='center'><label class='message'>$message_notassigned</label></td></tr>";
      else $c .= "<tr class='nodatainfo'><td colspan='100'><label class='message' align='center'>$message_novalue</label></td></tr>";
    }
  }

  if($mode == 'write' && !$readonly && !$write_no_add){
    $c .= ui_gridrow(array(), $params, 1, 0);
    $c .= "<tr class='newrowopt'><td colspan='100' align='center' onclick=\"ui.grid_add(ui('#$id'))\"><span class='fa fa-plus-circle color-green'></span><label>Tambah Baris Baru</label></td></tr>";

    // Generate template for new row
    $template = ui_gridrow(array(), $params, 1);

    // Store template in js
    $c .= uijs("
      ui.grid_store['$id'] = {
        'template':" . json_encode($template) . ",
        'columns':" . json_encode($columns) . "
      };
    ");
  }

      $c .= "</tbody>
    </table>
  </div>
  ";

  if($mode != 'write'){
    if($totaldata > $maxitemperpage && !empty($scrollel)){
      $scrollelexp = $scrollel == 'body' ? 'document.body' : "ui('$scrollel')"; // Special body tag
      $c .= uijs("
      ui.grid_store['$id'] = {
        'dataid':'$dataid',
        'pageidx':0,
        'cacheidx':0,
        'maxitemperpage':$maxitemperpage,
        'maxpagepercache':$__UIGRID_PARTITION_SIZE,
        'cachecount':$cache_partition_count,
        'totaldata':$totaldata,
        'scrollel':'$scrollel',
        'cacheds':'$cacheds',
        'params':" . json_encode($params) . "
      };
      $scrollelexp.addEventListener('scroll', ui.grid_more, true);
      $scrollelexp.setAttribute('data-gridid', '$id');
      //console.log(ui.grid_store['$id']);
    ");
    }
  }

  return $c;
}
function ui_grid_add($params){

  $name = ov('name', $params);
  $value = ov('value', $params);

  $trs = array();
  if(is_array($value)){
    foreach($value as $obj){
      $trs[] = ui_gridrow($obj, $params);
    }
  }

  $c = uijs("
    ui.grid_add_bytrs(ui('%$name'), " . json_encode($trs) . ");
  ");
  return $c;

}
function ui_gridrow($obj, $params, $startrow = false, $event_or_odd = 0, $others = null){

  global $__UIGRID_LASTROWOBJ;
  $columns = ov('columns', $params);
  $onclick = ov('onrowclick', $params);
  $ondblclick = ov('onrowdoubleclick', $params);
  $width100existed = false;
  $id = ov('id', $obj, 0, md5(json_encode($obj)));

  if(!is_array($others)) $others = array();
  $others['params'] = $params;

  $c = '';
  $c .= "<tr class='" . ($event_or_odd ? 'stripe' : '') . "' onclick=\"ui.grid_onrowclick(event, this)\" ondblclick=\"$ondblclick\" data-id='$id' data-onclick=\"$onclick\">";
  if(is_array($columns)){
    foreach($columns as $column){
      if(!$width100existed && isset($column['width']) && $column['width'] === '100%') $width100existed = true;
      $c .= ui_gridcol($column, $obj, $startrow, $others);
    }
  }

  if(!$width100existed) $c .= "<td style='width:100%'></td>";
  $c .= "</tr>";

  $__UIGRID_LASTROWOBJ = $obj;

  return $c;
}
function ui_gridcol($column, $obj, $defineWidth = true, $others = null){

  if(!ov('active', $column)) return;

  global $__UIGRID_LASTROWOBJ;
  $name = $column['name'];
  $type = ov('type', $column);
  $width = $column['width'];
  $classname = ov('class', $column, 0, '');
  $style = ov('style', $column, 0, '');
  $datatype = ov('datatype', $column, 0, 'text');
  $dateformat = ov('dateformat', $column, 0, $datatype == 'date' ? 'j M Y' : 'j M Y H:i:s');
  $lettercase = ov('lettercase', $column);
  $round_precision = ov('round_precision', $column, false, false);
  $prevobj = ov('prevobj', $others);
  $align = ov('align', $column);
  $value = ov($name, $obj);
  $prevvalue = ov($name, $prevobj);
  $decimals = ov('decimals', $column, 0, 2);
  $params = ov('params', $others);
  $col_norepeat = ov('col_norepeat', $column);
  switch($datatype){
    case 'money':
      $value = number_format_auto_money(floatval($value), $decimals);
      if(empty($align)) $align = 'right';
      break;
    case 'date':
    case 'datetime':
      if(strlen($value) == 4) $value .= '0101'; // 4 digit value will be treated as year
      $time = strtotime($value);
      $value = $time ? date($dateformat, is_double($value) ? $value : strtotime($value)) : '';
      if(empty($align)) $align = 'center';
      break;
    case 'number':
      $value = number_format_auto(floatval($value), $decimals);
      if(empty($align)) $align = 'right';
      break;
    case 'timestamp':
      $value = date('Y-m-d H:i:s', $value);
      break;
    case 'serializedobj':
      $value = unserialize($value);
      $value = ui_objecttree(array('value'=>$value));
      break;
  }
  switch($lettercase){
    case 'capitalize': $value = ucwords($value); break;
    case 'uppercase': $value = strtoupper($value); break;
    case 'lowercase': $value = strtolower($value); break;
  }

  $c = '';
  if($__UIGRID_LASTROWOBJ != null && $obj['id'] == $__UIGRID_LASTROWOBJ['id'] &&
      $obj[$name] == $__UIGRID_LASTROWOBJ[$name] && ov('nodittomark', $column) != 1 && ov('nodittomark', $params) != 1){
    $c .= "<td style='" . ($defineWidth ? "width:$width;" : '') . "text-align:$align;'></td>"; // &#8243;
  }
  else{
    if($type == 'html'){
      $html = ov('html', $column);
      if(function_exists($html)){

        $html_result = call_user_func_array($html, array($obj, $others['params'], $column));
        if(is_assoc($html_result)){
          $style = ov('style', $html_result, 0, '');
          $html = ov('html', $html_result, 0, '');
        }
        else{
          $html = $html_result;
        }

        $c .= "<td style='width:$width;$style' class='$classname'>";
        $c .= $html;
        $c .= "</td>";
      }
      else{
        $c .= "<td style='width:$width;'>Invalid callback</td>";
      }
    }
    else if($type == 'bool'){

      $c .= "<td style='width:$width;$style' class='$classname align-center'>";
      if($value)
        $c .= "<span class='fa fa-check-circle bg-green'></span>";
      else
        $c .= "<span class='fa fa-times-circle bg-red'></span>";
      $c .= "</td>";

    }
    else{
      if(empty($value)) $value = ' - ';
      if($col_norepeat && $value == $prevvalue) $value = "<i class='color-gray'>Same as above</i>";

      //$c .= "<td style='" . ($defineWidth ? "width:$width;" : '') . "text-align:$align;background:" . randompastelcolors() . "'>$value</td>";
      $c .= "<td style='" . ($defineWidth ? "width:$width;" : '') . "text-align:$align;$style' class='$classname'>$value</td>";
    }
  }
  $c .= "<td class='separator'></td>";

  return $c;

}
function ui_gridcol_empty($column, $obj, $defineWidth, $others = null){

  $width = ov('width', $column);

  $c = '';
  $c .= "<td style='" . ($defineWidth ? "width:$width;" : '') . "'>&nbsp;</td>";
  return $c;

}
function ui_gridmore($gridstore){
  $params = $gridstore['params'];
  $dataid = $gridstore['dataid'];
  $i = $gridstore['cacheidx'];
  $total = $gridstore['cachecount'];
  $cacheds = ov('cacheds', $gridstore);
  $cacheds_exists = !empty($cacheds) && function_exists($cacheds) ? 1 : 0;
  $scrollel = $gridstore['scrollel'];
  $maxitemperpage = $gridstore['maxitemperpage'];

  if(!$cacheds_exists){
    $obj = cache_get($dataid . $i);
    $arr = $obj['arr'];
    $params = $obj['params'];
    $columns = $params['columns'];

    $viewdata = array_splice($arr, 0, $maxitemperpage);
    cache_set($dataid . $i, array('params'=>$params, 'arr'=>$arr));

  }
  else{
    $viewdata = call_user_func_array($cacheds, array(null, null, null, $gridstore['pageidx']));
  }

  $viewdata = data_calculate_logicalcolumn($viewdata, $columns);

  $c = "<element_i exp='#tbody_$dataid'>";
  for($i = 0 ; $i < count($viewdata) ; $i++){
    $obj = $viewdata[$i];
    $c .= ui_gridrow($obj, $params, 0, $i % 2 == 0 ? 0 : 1);
  }
  $c .= "</element_i>";
  if(!$cacheds_exists && count($arr) == 0 && $i + 1 >= $total){
    $c .= uijs("console.warn('remove listener');delete ui.grid_moreid;ui('$scrollel').removeEventListener('scroll', ui.grid_more, true);");
  }
  else{
    if(count($viewdata) == 0)
      $c .= uijs("console.warn('remove listener');delete ui.grid_moreid;ui('$scrollel').removeEventListener('scroll', ui.grid_more, true);");
  }

  log_bench_end();
  return $c;
}

function ui_grid2($params){

  // Extract parameters
  $id = ov('id', $params, 0, 'c' . uniqid());
  $name = ov('name', $params);
  $class = ov('class', $params, 0, 'grid');
  $columns = ov('columns', $params);
  $sorts = ov('sorts', $params);
  $filters = ov('filters', $params);
  $groups = ov('groups', $params);
  $datasource = ov('datasource', $params);
  $value = ov('value', $params);
  $scrollel = ov('scrollel', $params);
  $progresscallback = ov('progresscallback', $params);
  $rowperpage = ov('rowperpage', $params, 0, 40); // Default: 40
  $onrowdoubleclick = ov('onrowdoubleclick', $params);
  $gridhead = ov('gridhead', $params, 0, '');

  $rows = ov('rows', $params);
  $rows_exists = function_exists($rows);

  global $cachedir, $ui_groupgrid_caches;
  $progresscallback_exists = function_exists($progresscallback) ? 1 : 0;
  if($progresscallback_exists) while (@ob_end_flush());
  $lastechotime = microtime(1);

  if(is_array($value) && count($value) > 100000){
    return uijs("alert('Data terlalu besar.')");
  }

  // Process
  $columns_indexed = array_index($columns, array('name'), 1);
  if(is_array($groups))
    $value = data_group($value, $groups);

  // Render
  // - Datasource mode
  $c = "
  <div id='$id' class='$class' data-type='grid2' data-name='$name' data-gridhead=\"$gridhead\" onscroll=\"ui.grid_onscroll.apply(this, arguments)\">";
  if(function_exists($datasource)){

    $c .= "<table><tbody id=''>";

    // Render first page
    $arr = call_user_func_array($datasource, array($columns, $sorts, $filters, array('offset'=>0, 'limit'=>$rowperpage)));

    if(is_array($arr) && count($arr) > 0){

      for($i = 0 ; $i < count($arr) ; $i++){
        $obj = $arr[$i];

        if(!$columns){
          $columns = [];
          foreach($obj as $obj_key=>$obj_value){
            $columns[] = [ 'active'=>1, 'name'=>$obj_key, 'text'=>$obj_key, 'width'=>strlen($obj_key) * 8 ];
          }
          $params['columns'] = $columns;
        }

        $rows_result = false;
        if($rows_exists)
          $rows_result = call_user_func_array($rows, [ $obj, $params, $i == 0 ? 1 : 0, $i % 2 ]);

        if(!$rows_result)
          $c .= ui_gridrow($obj, $params, $i == 0 ? 1 : 0, $i % 2);
        else
          $c .= $rows_result;

      }

      // Check if row exists
      $moredataexists = call_user_func_array($datasource, array($columns, $sorts, $filters, array('offset'=>40, 'limit'=>1)));
      if($moredataexists){
        $c .= "<tr class='tr-loadmore' onclick=\"ui.grid2_more('$id')\"><td colspan='100' align='left'><label>Load more</label></td></tr>";
      }

    }
    else{

      $c .= "<tr><td style='width:100%' class='align-center'>No data</td></tr>";

    }

    $c .= "</tbody></table>";

    $mode = 'grid';

  }
  // - Value mode
  else if(is_array($value) && count($value) > 0){

    if(isset($value[0]['__groupname'])){

      $mode = 'group';
      $c .= ui_groupgrid_groups($value, $columns_indexed, $params);
      if(is_array($ui_groupgrid_caches[$id]) && count($ui_groupgrid_caches) > 0) $moredataexists = 1;

    }
    else{

      $mode = 'grid';
      $c .= "<table><tbody id=''>";


      for($i = 0 ; $i < count($value) && $i < 100 ; $i++){
        $obj = $value[$i];

        $rows_result = false;
        if($rows_exists)
          $rows_result = call_user_func_array($rows, [ $obj, $params, $i == 0 ? 1 : 0, $i % 2 ]);

        if(!$rows_result)
          $c .= ui_gridrow($obj, $params, $i == 0 ? 1 : 0, $i % 2);
        else
          $c .= $rows_result;
      }

      $c .= "</tbody></table>";

    }

  }
  $c .= "</div>";
  // - More data link
  if($moredataexists){
    $grid2moredata = array(
      'id'=>$id,
      'datasource'=>$datasource,
      'offset'=>$rowperpage,
      'columns'=>$columns,
      'rowperpage'=>$rowperpage,
      'scrollel'=>$scrollel,
      'sorts'=>$sorts,
      'filters'=>$filters,
      'mode'=>$mode,
      'moredata'=>$moredataexists ? 1 : 0,
      'caches'=>$ui_groupgrid_caches[$id],
      'onrowdoubleclick'=>$onrowdoubleclick
    );
    $c .= uijs("ui.grid2moredata['$id'] = " . json_encode($grid2moredata) . ";");
  }
  $c .= uijs("ui.grid2init('$id')");

  global $__UI_STORE;
  $__UI_STORE['#' . $id] = $params;

  return $c;

}
function ui_grid2_more($params){

  $datasource = ov('datasource', $params);
  $rowperpage = ov('rowperpage', $params, 0, 40); // Default: 40
  $offset = ov('offset', $params);
  $columns = ov('columns', $params);
  $sorts = ov('sorts', $params);
  $filters = ov('filters', $params);
  $id = ov('id', $params);
  $mode = ov('mode', $params);
  global $ui_groupgrid_caches;
  $ui_groupgrid_caches[$params['id']] = $params['caches'];

  $c = '';
  if($mode == 'grid'){

    // Render next rows
    $arr = call_user_func_array($datasource, array($columns, $sorts, $filters, array('offset'=>$offset, 'limit'=>$rowperpage)));
    for($i = 0 ; $i < count($arr) ; $i++){
      $obj = $arr[$i];

      $c .= ui_gridrow($obj, $params, $i == 0 ? 1 : 0, $i % 2);
    }

    // Check if row exists
    $moredataexists = call_user_func_array($datasource, array($columns, $sorts, $filters, array('offset'=>$offset + $rowperpage, 'limit'=>1)));

    $params['moredata'] = $moredataexists ? 1 : 0;
    $params['offset'] = $offset + $rowperpage;
    $params['caches'] = $ui_groupgrid_caches[$params['id']];
    $c .= uijs("ui.grid2moredata['$id'] = " . json_encode($params) . ";");

  }
  else{

    $c .= uijs("ui.grid2moredata['$id'] = " . json_encode($params) . ";");

  }

  //sleep(1);

  return $c;

}

function ui_grid3($params){

  if(!isset($params['columns'])){
    if(isset($_SESSION['grid3_params'])){
      $session_params = $_SESSION['grid3_params'];
      $params = array_merge($session_params, $params);
    }
  }

  $target = ov('target', $params);
  $columns = ov('columns', $params);
  $columns_indexed = array_index($columns, [ 'name' ], true);
  $filters = ov('filters', $params);
  $groups = ov('groups', $params);
  $data = datasource($columns, null, $filters, null, $groups);

  $group = $groups[0];
  $group_name = ov('name', $group);
  $group_aggregrate = ov('aggregrate', $group);
  $group_columns = ov('columns', $group);

  $sub_groups = [];
  for($i = 1 ; $i < count($groups) ; $i++)
    $sub_groups[] = $groups[$i];

  // Render
  $c = [];
  $c[] = $target != '' ? "<element exp='$target'>" : "<div id=\"\" class=\"grid3\" data-type=\"grid3\" data-name=\"\">";
  if($group){
    if(is_array($data) && count($data) > 0) {
      foreach ($data as $obj) {

        $caret_sect_id = "c" . uniqid();
        $caret_body_id = "c" . uniqid();

        $group_name_key = '';
        foreach($group_columns as $index=>$group_column)
          if($group_column['name'] == $group_name){
            $group_name_key = 'col-' . $index;
            break;
          }

        $sub_filters = [];
        foreach ($filters as $filter)
          $sub_filters[] = $filter;
        switch ($group_aggregrate) {
          case 'monthly':
            $sub_filters[] = [
              'name' => "DATE_FORMAT(" . $group['name'] . ", '%b %Y')",
              'operator' => '=',
              'value' => date('M Y', strtotime($obj[$group_name_key]))
            ];
            break;
          case 'yearly':
            if(strlen($obj[$group_name_key]) == 4) $obj[$group_name_key] .= '0101';
            $sub_filters[] = [
              'name' => "DATE_FORMAT(" . $group['name'] . ", '%Y')",
              'operator' => '=',
              'value' => date('Y', strtotime($obj[$group_name_key]))
            ];
            break;
          default:
            $sub_filters[] = [
              'name' => $group['name'],
              'operator' => '=',
              'value' => $obj[$group_name_key]
            ];
            break;
        }

        $sub_params = [];
        $sub_params['filters'] = $sub_filters;
        $sub_params['groups'] = $sub_groups;
        $sub_params['target'] = "#$caret_body_id";

        $c[] = "<div class='grid3-sect'>";
        $c[] = "<div class='grid3-sect-head'>";
        $c[] = "<table><tbody><tr>";
        $c[] = "<td>";
        if($group){
          $c[] = "<span id='$caret_sect_id' class='expand-btn fa fa-caret-right' onclick=\"\"></span>";
          $c[] = "<script>
        $('#$caret_sect_id').click(function(){
          ui.grid3_expand.apply(this, [ " . json_encode($sub_params) . ", '#$caret_sect_id', '#$caret_body_id' ]);
        });
        </script>";
        }
        $c[] = "</td>";
        foreach($group_columns as $index=>$group_column){
          $key = $group_column['name'];
          $column = isset($columns_indexed[$key]) ? $columns_indexed[$key] : [];
          if(in_array($column['datatype'], [ 'date', 'datetime' ])){
            switch($group_aggregrate){
              case 'monthly': $column['dateformat'] = 'M Y'; break;
              case 'yearly': $column['dateformat'] = 'Y'; break;
            }
          }
          $c[] = ui_gridcol($column, [ $column['name']=>$obj['col-' . $index] ]);
        }
        /*foreach ($obj as $key => $value) {
          $column = isset($columns_indexed[$key]) ? $columns_indexed[$key] : [];
          if(in_array($column['datatype'], [ 'date', 'datetime' ]) && in_array($group_aggregrate, [ 'monthly', 'yearly' ])) $column['dateformat'] = 'M Y';
          $c[] = ui_gridcol($column, $obj);
        }*/
        $c[] = "<td style='width:100%'></td>";
        $c[] = "</tr></tbody></table>";
        $c[] = "</div>";
        $c[] = "<div id='$caret_body_id' class='grid3-sect-body'></div>";

      }
    }
  }
  else{
    $sorts = ov('sorts', $params);
    $onrowdoubleclick = ov('onrowdoubleclick', $params);
    $c[] = ui_grid2(array('columns'=>$columns, 'value'=>$data, 'sorts'=>$sorts, 'filters'=>$filters, 'onrowdoubleclick'=>$onrowdoubleclick));
  }
  $c[] = $target != '' ? "</element>" : "</div>";

  $_SESSION['grid3_params'] = $params;

  return implode('', $c);

}
function ui_grid3head($params){

  $columns = ov('columns', $params);
  $columns_indexed = array_index($columns, array('name'), 1);
  $groups = ov('groups', $params);

  // Analysing max group columns
  $max_group_columns = 0;
  foreach($groups as $group){
    if(count($group['columns']) > $max_group_columns) $max_group_columns = count($group['columns']);
  }

  $c = "<div class='grid3head'>";
  for($i = 0 ; $i < count($groups) ; $i++){
    $group = $groups[$i];
    if(!isset($group['name'])) continue;
    $groupcolumns = $group['columns'];

    $margin_left = $i * 10;

    $c .= "<table style='margin-left:$margin_left'><thead>";
    $c .= "<tr>";
    $c .= "<th style='width: 16px'></th>";
    for($j = 0 ; $j < $max_group_columns ; $j++){

      if(isset($groupcolumns[$j])){
        $groupcolumn = $groupcolumns[$j];
        $name = $groupcolumn['name'];
        $column = $columns_indexed[$name];
        $width = ov('width', $column);
        $datatype = ov('datatype', $column);
        $align = ui_gridcol_align($datatype, ov('align', $column));
        $text = ov('text', $columns_indexed[$name]) . ($groupcolumn['logic'] != 'first' ? ' (' . strtoupper($groupcolumn['logic']) . ')' : '');
        $c .= "<th style='text-align:$align;width:" . $width . "px;' data-name='$name' data-name='$name'>";
        $c .= $text;
        $c .= "</th>";
        $c .= "<th class='separator'></th>";
      }
      else{
        $c .= "<th></th><th class='separator'></th>";
      }
    }
    $c .= "<th style='width:100%'>";
    $c .= "</tr>";
    $c .= "</thead></table>";
  }
  $c .= "</div>";

  return $c;
}
function ui_gridcol_align($datatype, $default = ''){

  $align = '';
  switch($datatype){
    case 'money':
      if(empty($align)) $align = 'right';
      break;
    case 'date':
    case 'datetime':
      if(empty($align)) $align = 'center';
      break;
    case 'number':
      if(empty($align)) $align = 'right';
      break;
  }
  return $align;

}

function ui_gridoption($params){

  $obj = ov('value', $params);
  $onapply = ov('onapply', $params);
  $ondownload = ov('ondownload', $params);
  $onupload = ov('onupload', $params);
  $groupable = ov('groupable', $params, 0);

  /*
  <button class='hollow'><span class='fa fa-download'></span></button>
  <button class='hollow'><span class='fa fa-upload'></span></button>
  <button class='hollow'><span class='fa fa-ellipsis-h'></span></button>
   */

  $c = "";
  $c .= "
    <div class='reportoption'>
      <span class='presetlist'>
        <div class='head'>
          <button class='hollow' onclick=\"ui_list.presetoptionmoveup()\"><span class='fa fa-caret-up'></span></button>
          <button class='hollow' onclick=\"ui_list.presetoptionmovedown()\"><span class='fa fa-caret-down'></span></button>
          <span class='button hollow' style='float:right'>
            <span class='fa fa-ellipsis-h' onclick='ui.popupopen(this.nextElementSibling, this.parentNode)'></span>
            <div class='popup off'>
              <span class='menuitem' onclick=\"if(confirm('Hapus?')) ui_list.presetoptionremove();ui.popupclose(this.parentNode)\"><span class='fa fa-times'></span> Remove</span>
              <span class='menuitem' onclick=\"ui_list.presetoptioncopy();ui.popupclose(this.parentNode)\"><span class='fa fa-copy'></span> Copy</span>
              <span class='menuitem' onclick=\"ui_list.presetdownload();ui.popupclose(this.parentNode)\"><span class='fa fa-download'></span> Download</span>
              <span class='menuitem' onclick=\"ui_list.presetupload();ui.popupclose(this.parentNode)\"><span class='fa fa-upload'></span> Upload</span>
              <span class='menuitem' onclick=\"if(confirm('This will reset presets and remove all custom presets?')) window.location='?reset=1';ui.popupclose(this.parentNode)\"><span class='fa fa-minus-circle'></span> Reset</span>
            </div>
          </span>
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
            " . ($groupable ? "<div class='tabitem' onclick=\"ui . tabclick(event, this);ui_list . presetoptiongroupresize()\"><label>Groups</label></div>" : "") . "
          </div>
        </div>
        <div id='reportoptiontabbody' class='tabbody scrollable'>
          <div class='tab tabname off'>
            " . ui_gridoptionname() . "
          </div>
          <div class='tab tabcolumns'>
            " . ui_gridoptioncolumns() . "
          </div>
          <div class='tab off'>
            " . ui_gridoptionsorts() . "
          </div>
          <div class='tab off'>
            " . ui_gridoptionfilters() . "
          </div>
          <div class='tab off tabgroups'>
            " . ui_gridoptiongroups() . "
          </div>
        </div>
      </span>
      <div class='toolbar'>
        <table cellspacing='5'>
          <tr>
            <td style='width: 100%'></td>
            <td><button id='presetsavebtn' class='red' onclick=\"ui_list.presetapply()\"><span class='fa fa-check'></span><label>Apply</label></button></td>
            <td><button class='hollow' onclick=\"ui.modal_close(ui('.modal'))\"><span class='fa fa-times'><span><label>Cancel</label></button></td>
          </tr>
        </table>
      </div>
    </div>
  ";
  $c .= uijs("
    report = " . json_encode($obj) . ";
    report_onapply = \"$onapply\";
    report_ondownload = \"$ondownload\";
    report_onupload = \"$onupload\";
    ui.modal_open(ui('.modal'), { width:900 });
    ui_list.presetoptionload(0);
  ");
  return $c;
  
}
function ui_gridoptionname(){

  $c = '';
  $c .= "<div class='scrollable'>";
  $c .= "<table class='form'>
    <tr><th><label>Preset Name</label></th><td>" . ui_textbox(array('name'=>'text', 'width'=>'300px', 'onchange'=>'ui_list.presetoptiontextchange(value)')) . "</td></tr>
  </table>";
  $c .= "</div>";

  return $c;
}
function ui_gridoptioncolumns(){

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
    <button class='hollow' onclick=\"ui_list.presetoptioncolumnadd()\"><span class='fa fa-plus'></span></button>
    <button class='hollow' onclick=\"ui_list.presetoptioncolumnremove()\"><span class='fa fa-times'></span></button>
  </div>";
  $c .= "<div class='scrollable'>";
  $c .= "</div></span>";

  // Column detail
  $c .= "<span class='columndetail'>";
  $c .= "
    <table class='form' cellspacing='5'>
      <tr><th><label>Active</label></th><td>" . ui_checkbox(array('name'=>'active', 'onchange'=>"ui_list.presetoptioncolumndetailchange(name, value)")) . "</td></tr>
      <tr><th><label>Name</label></th><td>" . ui_textbox(array('name'=>'name', 'width'=>60, 'onchange'=>"ui_list.presetoptioncolumndetailchange(name, value)")) . "</td></tr>
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
function ui_gridoptionsorts(){

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
function ui_gridoptionfilters(){

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
function ui_gridoptiongroups(){

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

function ui_groupgridhead($params){

  $columns = ov('columns', $params);
  $columns_indexed = array_index($columns, array('name'), 1);
  $groups = ov('groups', $params);

  $c = "<div class='grid'>";
  $c .= "<table><thead>";
  for($i = 0 ; $i < count($groups) ; $i++){
    $group = $groups[$i];
    if(!isset($group['name'])) continue;
    $groupcolumns = $group['columns'];

    $c .= "<tr>";
    $c .= "<th style='width: 6px'></th>";
    for($j = 0 ; $j < count($groupcolumns) ; $j++){
      $groupcolumn = $groupcolumns[$j];
      $name = $groupcolumn['name'];
      $width = ov('width', $columns_indexed[$name]);
      $align = '';
      $text = ov('text', $columns_indexed[$name]) . ($groupcolumn['logic'] != 'first' ? ' (' . strtoupper($groupcolumn['logic']) . ')' : '');
      $c .= "<th style='text-align:$align;width:" . $width . "px;' data-name='$name' data-name='$name'>";
      $c .= $text;
      $c .= "</th>";
      $c .= "<th class='separator'></th>";
    }
    $c .= "<th style='width:100%'>";
    $c .= "</tr>";
  }
  $c .= "</thead></table>";
  $c .= "</div>";

  return $c;
}

function datasource_group($columns, $sorts, $filters, $groups, $depth = 0){

  if(count($groups) < 1) return [];

  $items = [];
  $data = datasource($columns, $sorts, $filters, null, $groups);

  $spliced_groups = array_splice($groups, 0, 1);
  $group = isset($spliced_groups[0]) ? $spliced_groups[0] : [];
  foreach($data as $index=>$obj){

    $items[] = $obj;

    // Check next depth
    if(count($groups) > 0){

      $sub_filters = [];
      foreach($filters as $filter)
        $sub_filters[] = $filter;

      $group_aggregrate = $group['aggregrate'];

      $group_name_key = '';
      foreach($group['columns'] as $index=>$group_column)
        if($group_column['name'] == $group['name']){
          $group_name_key = 'col-' . $index;
          break;
        }

      switch($group_aggregrate){
        case 'monthly':
          $sub_filter = [
            'name'=>"DATE_FORMAT(" . $group['name'] . ", '%b %Y')",
            'operator' => '=',
            'value' => date('M Y', strtotime($obj[$group_name_key]))
          ];
          break;
        case 'yearly':
          if(strlen($obj[$group_name_key]) == 4) $obj[$group_name_key] .= '0101';
          $sub_filters[] = [
            'name' => "DATE_FORMAT(" . $group['name'] . ", '%Y')",
            'operator' => '=',
            'value' => date('Y', strtotime($obj[$group_name_key]))
          ];
          break;
        default:
          $sub_filters[] = [
            'name' => $group['name'],
            'operator' => '=',
            'value' => $obj[$group_name_key]
          ];
          break;
      }
      $sub_filters[] = $sub_filter;

      $sub_items = datasource_group($columns, $sorts, $sub_filters, $groups, $depth + 1);
      $items = array_merge($items, $sub_items);

    }

  }

  return $items;

}

$ui_groupgrid_caches = [];
function ui_groupgrid($params){

  $starttime = microtime(1);
  $name = ov('name', $params);
  $cacheds = ov('cacheds', $params);
  $columns = ov('columns', $params);
  $columns_indexed = array_index($columns, array('name'), 1);
  $filters = ov('filters', $params);
  $groups = ov('groups', $params);

  if(!empty($cacheds) && function_exists($cacheds))
    $data = call_user_func_array($cacheds, array($columns, $filters));
  else
    $data = ov('value', $params);

  $datagroup = data_group($data, $groups);

  // 3. Render UI
  $c = '';
  $c .= "<div id='' class='grid' data-name='$name'>";
  $c .= ui_groupgrid_groups($datagroup, $columns_indexed, $params);
  $c .= "</div>";

  return $c;

}
function ui_groupgrid_groups($datagroup, $columns_indexed, $params, $level = 0){

  global $ui_groupgrid_caches;
  if(!isset($ui_groupgrid_caches[$params['id']])) $ui_groupgrid_caches[$params['id']] = array();

  // Show 30 row on first level of group and 10 for next level
  $viewdata = array_splice($datagroup, 0, $level == 0 ? 30 : 10);

  $c = '';
  for($i = 0 ; $i < count($viewdata) ; $i++){
    $c .= ui_groupgrid_group($viewdata[$i], $columns_indexed, $params);
  }

  if(count($datagroup) > 0){
    $cacheid = cache_set(md5($params['id'] . count($ui_groupgrid_caches[$params['id']])), array('datagroup'=>$datagroup, 'columns_indexed'=>$columns_indexed, 'params'=>$params, 'level'=>$level));
    $c .= "<div class='align-center tr-loadmore' onclick=\"ui.groupgrid_loadmore('" . $params['id'] . "', '$cacheid', 0)\"><div class='align-center'><label class='loadmore' data-cacheid='$cacheid'>Load more...</label></div></div>";
    $ui_groupgrid_caches[$params['id']][] = $cacheid;
  }
  return $c;

}
function ui_groupgrid_group($datagroup, $columns_indexed, $params, $level = 0){

  $groupcolumns = $datagroup['__groupcolumns'];
  $nextisgroup = 1;
  $obj = $datagroup;
  $items = $datagroup['__groupitems'];
  $aggregrate = $datagroup['__groupaggregrate'];

  $c = '';
  $c .= "<div class='groupgridsect'>";
  $c .= "<div class='groupgridsecthead' onclick='ui.groupgridsecthead_click(event, this)'>";

  $c .= "<table><tr>";
  $c .= "<td style='width: 20px'><span class='fa fa-caret-right'></span></td>";
  foreach($groupcolumns as $groupcolumn){
    if(!isset($columns_indexed[$groupcolumn['name']])) continue;

    $column = $columns_indexed[$groupcolumn['name']];
    $column['active'] = 1;
    $column['name'] = $groupcolumn['logic'] != 'first' ? $column['name'] . '.' . $groupcolumn['logic'] : $column['name'];

    switch($aggregrate){
      case 'monthly': $column['dateformat'] = 'M Y'; break;
    }
    $c .= ui_gridcol($column, $obj, 1);
  }
  $c .= "<td style='width:100%'></td>";
  $c .= "</tr></table>";

  $c .= "</div>";
  $c .= "<div class='groupgridsectbody off'>";

  if(is_array($items) && count($items) > 0){

    if(isset($items[0]['__groupname'])){

      $c .= ui_groupgrid_groups($items, $columns_indexed, $params, $level + 1);

    }
    else{

      $c .= ui_groupgrid_items($items, $columns_indexed, $params, $level + 1);

    }

  }

  $c .= "</div>";
  $c .= "</div>";

  return $c;

}
function ui_groupgrid_items($items, $columns_indexed, $params, $level = 0){

  $viewitems = array_splice($items, 0, 10);

  $c = "<table>";
  for($i = 0 ; $i < count($viewitems) ; $i++){
    $item = $viewitems[$i];

    $c .= ui_gridrow($item, $params, 1, $i % 2);
  }

  if(count($items) > 0){
    global $ui_groupgrid_caches;
    $cacheid = cache_set(md5($params['id'] . count($ui_groupgrid_caches[$params['id']])), array('items'=>$items, 'columns_indexed'=>$columns_indexed, 'params'=>$params, 'level'=>$level));
    $c .= "<tr onclick=\"ui.groupgrid_loadmore('" . $params['id'] . "', '$cacheid', 1)\" data-cacheid='$cacheid'><td colspan='20' align='center'><label class='loadmore'>Load more..</label></td></tr>";
    $ui_groupgrid_caches[$params['id']][] = $cacheid;
  }

  $c .= "</table>";
  return $c;

}
function ui_groupgrid_loadmore($id, $cacheid, $type, $caches){
  global $ui_groupgrid_caches;
  $ui_groupgrid_caches[$id] = $caches;

  if($type == 0){
    $cacheobj = cache_get($cacheid);
    $datagroup = $cacheobj['datagroup'];
    $columns_indexed = $cacheobj['columns_indexed'];
    $params = $cacheobj['params'];
    $level = $cacheobj['level'];
    $c = ui_groupgrid_groups($datagroup, $columns_indexed, $params, $level);
    $c .= uijs("ui.grid2moredata['$id']['caches'] = " . json_encode($ui_groupgrid_caches[$id]) . ";");
    return $c;
  }
  else{
    $cacheobj = cache_get($cacheid);
    $items = $cacheobj['items'];
    $columns_indexed = $cacheobj['columns_indexed'];
    $params = $cacheobj['params'];
    $level = $cacheobj['level'];
    $c = ui_groupgrid_items($items, $columns_indexed, $params, $level);
    $c .= uijs("ui.grid2moredata['$id']['caches'] = " . json_encode($ui_groupgrid_caches) . ";");
    return $c;
  }

}

function ui_hidden($params){

  $id = ov('id', $params);
  $name = ov('name', $params);
  $value = ov('value', $params);
  $ischild = ov('ischild', $params, 0, 0);
  if($ischild) $ischild = 1;

  $c = "<input class='hidden' type='hidden' id=\"$id\" data-name='$name' data-type='hidden' value=\"" . htmlentities($value) . "\" data-ischild=\"$ischild\"/>";
  return $c;
}

function ui_image($params){

  $width = ov('width', $params);
  $height = ov('height', $params);
  $id = ov('id', $params);
  $src = ov('src', $params);
  $name = ov('name', $params);

  $c = "<img id='$id' width='$width' data-type='image' data-name='$name' height ='$height' src='$src' data-value='$src' />";
  return $c;

}

function ui_imageupload($params){

	$c = '';
	
	return $c;
}

function ui_label($params){

  $id = ov('id', $params);
  $name = ov('name', $params);
  $value = ov('value', $params);
  $width = ov('width', $params);
  $datatype = ov('datatype', $params);
  $ischild = ov('ischild', $params, 0, 0);
  $onclick = ov('onclick', $params, 0, '');

  switch($datatype){
    case 'number':
      $value = floatval($value);
      $dec = $value - floor($value) > 0 ? 2 : 0;
      $value = number_format($value, $dec);
      if(empty($align)) $align = 'right';
      break;
    case 'money':
      $value = floatval($value);
      $dec = $value - floor($value) > 0 ? 2 : 0;
      $value = number_format($value, $dec);
      if(empty($align)) $align = 'right';
      break;
  }

  $c = "<label id='$id' data-name='$name' data-type='label' data-ischild='$ischild' style='text-align:$align;width:$width;' data-datatype='$datatype' onclick=\"$onclick\">$value</label>";
  return $c;

}

function ui_list($params){
  $columns = ov('columns', $params);
  $data = ov('data', $params);
  $width = ov('width', $params);

  $c = "<span class='list' style='width:$width;'>";
  if(is_array($columns)){
    $c .= "<table class='head'><thead><tr>";
    for($i = 0 ; $i < count($columns) ; $i++){
      $column = $columns[$i];
      $text = ov('text', $column);
      $width = ov('width', $column, 0, '100px');
			$align = ov('align', $column);
			
			$css = array();
			$css[] = !empty($align) ? "text-align:$align" : '';
			$css[] = "width:$width";

      if($i > 0 && $i < count($columns)) $c .= "<th class='separator'></th>";
      $c .= "<th" . (count($css) > 0 ? " style='" . implode(';', $css) . "'" : "") . ">$text</th>";
    }
    $c .= "<th class='spacer'></th>";
    $c .= "</tr></thead></table>";
  }
  $c .= "<div class='scrollable'>";
  if(is_array($data)){
    $c .= "<table class='body'>";
    foreach($data as $obj){
      $c .= "<tr onclick=\"ui.list_rowclick(event, this)\">";
      for($i = 0 ; $i < count($columns) ; $i++){
        $column = $columns[$i];
        $type = ov('type', $column);
        $width = ov('width', $column, 0, '100px');
				$align = ov('align', $column);
			
				$css = array();
				$css[] = !empty($align) ? "text-align:$align" : '';
				$css[] = "width:$width";

        if($i > 0 && $i < count($columns)) $c .= "<td class='separator'></td>";
        $c .= "<td" . (count($css) > 0 ? " style='" . implode(';', $css) . "'" : "") . ">";
        if($type == 'html'){
          $html = ov('html', $column);
          if(function_exists($html))
            $c .= call_user_func_array($html, array($obj));
          else
            $c .= "Invalid callback";
        }
        else{
          $name = $column['name'];
          $value = ov($name, $obj);

          $c .= $value;
        }
        $c .= "</td>";
      }
      $c .= "<td class='spacer'></td>";
      $c .= "</tr>";
    }
    $c .= "</table>";
  }
  $c .= "</div>";
  $c .= "</span>";

  return $c;
}

function ui_logarray($exp, $logs){

  if(is_array($logs) && count($logs) > 0){
    $log0 = $logs[0];
    $columns = array();
    foreach($log0 as $key=>$value)
      $columns[] = array('active'=>1, 'name'=>$key, 'width'=>50);

    $c = "<element exp='$exp'>";
    $c .= ui_gridhead(array('columns'=>$columns, 'gridexp'=>'#grid1'));
    $c .= ui_grid(array('columns'=>$columns, 'id'=>'grid1', 'value'=>$logs, 'maxitemperpage'=>200));
    $c .= "</element>";
    return $c;
  }

}

function ui_multicomplete($params){
	$id = ov('id', $params);
	$name = ov('name', $params);
	$width = ov('width', $params);
	$src = ov('src', $params);
	$value = ov('value', $params);
	$onchange = ov('onchange', $params);
  $separator = ov('separator', $params, 0, ',');
  $placeholder = ov('placeholder', $params, false, 'Search...');
  $readonly = ov('readonly', $params);
	
	if(empty($src)){ echo 'Parameter src required.'; return; }
	
	$c = '';
	$c .= "<span id='$id' class='multicomplete" . ($readonly ? ' readonly' : '') . "' data-type='multicomplete' data-name='$name' data-separator=\"$separator\" data-src='$src' data-onchange=\"$onchange\" style='width:$width;' onclick=\"ui.multicomplete_onclick(event, this)\">";
  if(gettype($value) == 'string'){
    $values = explode(',', trim($value));
    foreach($values as $value)
      if(!empty($value)){
        $c .= "<span class='item' data-value=\"" . htmlentities($value) . "\"><label>$value</label>";
        if(!$readonly) $c .= "<span class='fa fa-times' onclick=\"ui.multicomplete_onitemremove(event, this)\"></span>";
        $c .= "</span>";
      }
  }
  else if(is_array($value)){
    foreach($value as $obj){
      $text = ov('text', $obj);
      $value = ov('value', $obj);
      $c .= "<span class='item' data-value=\"" . htmlentities($value) . "\"><label>$text</label>";
      if(!$readonly) $c .= "<span class='fa fa-times' onclick=\"ui.multicomplete_onitemremove(event, this)\"></span>";
      $c .= "</span>";
    }
	}
	$c .= "<div class='popup off'></div>";
	$c .= "<input type='text' onkeyup=\"ui.multicomplete_onkeyup(event, this)\" placeholder=\"" . ($readonly ? '' : $placeholder) . "\"" . ($readonly ? ' readonly' : '') . "/>";
	$c .= "</span>";
	
	return $c;	
}
function ui_multicompleteitems($popupuiid, $src, $hint){

  $c = "<element exp='$" . $popupuiid . "'>";
  if(function_exists($src)){
    $items = call_user_func_array($src, array(array('hint'=>$hint)));
    if(is_array($items)){
      for($i = 0 ; $i < count($items) ; $i++){
        $item = $items[$i];
        $text = $item['text'];
        $value = $item['value'];
        $c .= "<div class='menuitem' data-value=\"" . htmlentities($value) . "\" data-obj=\"" . htmlentities(json_encode($item)) . "\">$text</div>";
      }
    }
  }
  $c .= "</element>";
  return $c;

}

function ui_objecttree($params){

  $value = ov('value', $params);
  $type = is_assoc($value) ? 'Object' : (is_array($value) ? 'Array' : 'Text');


  $c = "<div class='objecttree'>";
  $c .= "<div class='head' onclick=\"ui.class_toggle(this.nextElementSibling, 'off')\"><span class='fa fa-caret-right'></span>&nbsp;<span>$type</span></div>";
  $c .= "<div class='body off'>";
  if(is_assoc($value)){
    $c .= ui_objecttree_object($value);
  }
  else if(is_array($value)){
    $c .= ui_objecttree_array($value);
  }
  else{
    $c .= $value;
  }
  $c .= "</div>";
  $c .= "</div>";
  return $c;

}
function ui_objecttree_array($arr){

  $c = '';
  foreach($arr as $value){

    if(is_assoc($value)){
      $c .= ui_objecttree_object($value);
    }
    else if(is_array($value)){
      $c .= ui_objecttree_array($value);
    }
    else{
      $c .= "<div>" . $value . "</div>";
    }

  }
  return $c;

}
function ui_objecttree_object($obj){

  $c = '';
  foreach($obj as $key=>$value){

    if(is_assoc($value)){
      $c .= ui_objecttree_object($value);
    }
    else if(is_array($value)){
      $c .= ui_objecttree_array($value);
    }
    else{
      $c .= "<div>" . $key . '=' . $value . "</div>";
    }

  }
  return $c;

}

function ui_radio($params){

  $name = ov('name', $params);
  $items = ov('items', $params);
  $value = sanitize_text_output(ov('value', $params));
  $type = ov('type', $params);
  $groupid = uniqid();

  $c = "<span class='radio' data-type='radio' data-name=\"$name\">";
  if(is_array($items)){
    for($i = 0 ; $i < count($items) ; $i++){
      $item = $items[$i];
      $itemvalue = sanitize_text_output(ov('value', $item));
      $itemtext = sanitize_text_output(ov('text', $item));
      $radio_id = uniqid();
      $checked = !empty($value) && $value == $itemvalue ? 'checked' : '';

      $c .= $type == 'vertical' ? "<div>" : "<span>";
      $c .= "<input name='$groupid' id='$radio_id' type='radio' value=\"" . htmlentities($itemvalue) . "\" $checked/><label for='$radio_id'>$itemtext</label>";
      $c .= $type == 'vertical' ? "</div>" : "</span>";
    }
  }
  $c .= "</span>";

  return $c;
}

function ui_simpleprogressbar($params){

  $id = ov('id', $params);
  $width = ov('width', $params);

  $c = "<span id='$id' class='simpleprogressbar' style='width:$width;'><span class='bar'></span><span class='text'></span></span>";
  return $c;

}

function ui_upload($params){

	$id = ov('id', $params);
	$name = ov('name', $params);
  $src = ov('src', $params);
  $text = ov('text', $params, 0, '');

  $c = "
  <button id='$id' data-name='$name' data-type='upload' class='blue' data-type='upload' onclick=\"this.lastElementChild.click()\">
    <span class='fa fa-upload'></span>
    " . (!empty($text) ? "<label>$text</label>" : "") . "
    <input type='file' class='off' data-src=\"$src\" onchange=\"ui.upload_onchange(event, this)\"/>
  </button>
  ";

  return $c;
}

function ui_render($html, $target){

  $c = "<element exp='$target'>";
  $c .= $html;
  $c .= "</element>";
  return $c;

}

function ui_star($params){
  $id = ov('id', $params);
  $name = ov('name', $params);
  $onchange = ov('onchange', $params);
  $count = 5;
  $value = ov('value', $params);

  $c = '';
  $c .= "<span id='$id' class='star' data-name='$name' data-onchange=\"$onchange\">";
  for($i = 0 ; $i < $count ; $i++){
    $active = $i <= $value ? 'active' : '';

    $c .= "<span class='item $active' onclick=\"ui.star_onchange(event, this)\"></span>";
  }
  $c .= "</span>";

  return $c;
}

function ui_textarea($params){

	$name = ov('name', $params);
	$width = ov('width', $params);
	$height = ov('height', $params);
  $value = sanitize_text_output(ov('value', $params));
  $placeholder = ov('placeholder', $params);
  $readonly = ov('readonly', $params);
  $id = ov('id', $params);
  $ischild = ov('ischild', $params, 0, 0);

  $readonly_exp = $readonly ? 'readonly' : '';

  $c = "<span id='$id' class='textarea $readonly_exp' data-ischild='$ischild' data-name='$name' data-type='textarea'><textarea style='width:$width;height:$height;' placeholder=\"$placeholder\" spellcheck='false' $readonly_exp>$value</textarea></span>";
  return $c;
}

function ui_textbox($params){

  $id = ov('id', $params);
	$name = ov('name', $params);
	$class = ov('class', $params);
	$width = ov('width', $params);
  $value = sanitize_text_output(ov('value', $params));
  $type = ov('mode', $params, 0, 'text');
  $align = ov('align', $params);
  $placeholder = ov('placeholder', $params);
  $maxlength = ov('maxlength', $params, 0, 0);
  $onchange = ov('onchange', $params);
  $onsubmit = ov('onsubmit', $params);
  $readonly = ov('readonly', $params);
  $datatype = ov('datatype', $params);
  $ischild = ov('ischild', $params, 0, 0);

  $attributes = array();
  $readonly_exp = $readonly ? 'readonly' : '';
  switch($datatype){
    case 'number':
    case 'money':
      $precision = get_round_precision($value);
      $value = number_format(floatval($value), $precision);
      if(empty($align)) $align = 'right';
      $attributes['type'] = 'text';
      //$attributes['inputmode'] = 'numeric';
      //$attributes['pattern'] = '[0-9\,]*';
      break;
    default:
      $attributes['type'] = $type != 'text' ? $type : 'text';
  }
  if($maxlength > 0)
    $attributes['maxlength'] = $maxlength;

  $c = "<span id='$id' class='textbox $class $readonly_exp' data-ischild='$ischild' data-type='textbox' 
    data-datatype='$datatype' style='width:$width;' data-onsubmit=\"$onsubmit\" data-name='$name'
    data-onchange=\"$onchange\">
      <input " . htmlattr($attributes) . " autocomplete='off' 
      value=\"$value\" style=\"text-align:$align;\" placeholder=\"" . htmlentities($placeholder) . "\" 
      onchange=\"ui.textbox_onchange(event, this)\" onkeyup=\"ui.textbox_onkeyup(event, this)\" 
      onblur=\"ui.textbox_onblur(event, this)\" onfocus=\"ui.textbox_onfocus(event, this)\" 
      onclick=\"return ui.preventDefault(event)\"
      ondblclick=\"return ui.preventDefault(event)\"
      $readonly_exp />
    </span>";
  return $c;
}

function ui_toggler($params){

  $id = ov('id', $params);
  $name = ov('name', $params);
  $onchange = ov('onchange', $params);
  $value = ov('value', $params);
  $text = ov('text', $params, 0, 'Off,On');
  $readonly = ov('readonly', $params);

  $label0 = $label1 = '';
  if(strpos($text, ',') !== false){
    $text_explode = explode(',', $text);
    if(count($text_explode) == 2){
      $label0 = trim($text_explode[0]);
      $label1 = trim($text_explode[1]);
    }
  }

  $label = $value ? $label1 : $label0;
  $class_on = $value ? ' on' : '';
  $class_readonly = $readonly ? ' readonly' : '';

  $c = '';
  $c .= "<span id='$id' data-type='toggler' data-name=\"$name\" data-text=\"$text\" class='toggler{$class_on}{$class_readonly}' data-onchange=\"$onchange\" onclick=\"ui.toggler_ontoggle(event, this)\">";
  $c .= "<span class='item-cont'>";
  $c .= "<span class='itembg'></span>";
  $c .= "<span class='item'></span>";
  $c .= "</span>";
  $c .= "<label>$label</label>";
  $c .= "</span>";

  return $c;
}

function ui_tracedialog($obj){

  echo ui_dialog(ui_genericdetail(array('value'=>$obj)));

}

function ui_detailmodal($params){

  $controls = ov('controls', $params);
  $mode = ov('mode', $params, 0, 'read');
  $width = ov('width', $params);
  $height = ov('height', $params);
  $onmodify = ov('onmodify', $params);
  $onsave = ov('onsave', $params);
  $modalparams = array();
  $modalparams['closeable'] = $mode == 'read' ? 1 : 0;
  $script = ov('script', $params);
  if(!empty($width)) $modalparams['width'] = $width;

  if($mode != 'write') $mode = 'read';

  $hiddencontrol_c = array();
  $c = "<element exp='.modal'>";
  $c .= "<div class='body' style='height:$height;'>";
  $c .= "<table class='form' cellspacing='10'>";
  foreach($controls as $control){
    $label = ov('label', $control, 0, 'Unlabelled');
    $type = ov('type', $control);
    $control['readonly'] = $mode == 'read' ? 1 : 0;
    $c1 = ui_control($control);

    if($type == 'hidden') $hiddencontrol_c[] = $c1;
    else $c .= "<tr><th><label>$label</label></th><td>" . $c1 . "</td></tr>";
  }
  $c .= "</table>";
  $c .= implode('', $hiddencontrol_c);
  $c .=  "</div>";
  $c .= "<div class='foot'>";
  $c .= "<table cellspacing='5'><tr>";
  $c .= "<td style='width:100%'></td>";
  if($mode == 'read' && $onmodify) $c .= "<td><button class='blue' onclick=\"$onmodify\"><span class='fa fa-edit'></span><label>Ubah</label></button></td>";
  if($mode == 'write' && $onsave) $c .= "<td><button class='blue' onclick=\"$onsave\"><span class='fa fa-save'></span><label>Simpan</label></button></td>";
  $c .= "<td><button class='hollow' onclick=\"ui.modal_close(ui('.modal'))\"><span class='fa fa-times'></span><label>Tutup</label></button></td>";
  $c .= "</tr></table>";
  $c .= "</div>";
  $c .= "</element>";
  if(!empty($script)){
    $c .= uijs("
      ui.loadscript('$script', 'ui.modal_open(ui(\".modal\"), " . json_encode($modalparams) . ")');
    ");
  }
  else{
    $c .= uijs("
      ui.modal_open(ui('.modal'), " . json_encode($modalparams) . ");
    ");
  }

  return $c;

}

function ui_modalclose(){

  return uijs("ui.modal_close(ui('.modal'))");

}

function ui_timeaccesscontrol($params){

  $name = ov('name', $params);
  $value = ov('value', $params);
  $readonly = ov('readonly', $params);

  $c = array();

  $c[] = "<span data-type='timeaccesscontrol' data-name='$name' class='timeaccesscontrol'>";
  $c[] = "<table>";
  $c[] = "<tr><th></th><th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th></tr>";
  for($i = 0 ; $i < 24 ; $i++){
    $c[] = "<tr>";
    $c[] = "<th>" . str_pad($i, 2, '0', STR_PAD_LEFT) . ":00</th>";
    for($j = 0 ; $j < 7 ; $j++){

      $key = $j . str_pad($i, 2, '0', STR_PAD_LEFT);
      $checked = strpos($value, $key) !== false ? true : false;
      $c[] = "<td><input type='checkbox' value='$key' " . ($checked ? 'checked' : '') . " " . ($readonly ? 'disabled' : '') . " value='$key'/></td>";
    }
    $c[] = "</tr>";
  }
  $c[] = "</table>";
  $c[] = "</span>";

  return implode('', $c);

}

function ui_compare($params){

  $value = ov('value', $params);
  $col_count = count($value[0]['value']);
  $width = 100 / ($col_count);

  $c = [];
  $c[] = "<span class='compared'>";
  $c[] = "<table>";
  $c[] = "<tr>";
  $c[] = "<th>Column</th>";
  for($index = 1 ; $index <= $col_count ; $index++)
    $c[] = "<th>Data-{$index}</th>";
  $c[] = "</tr>";
  foreach($value as $obj){

    $key = $obj['key'];
    $datatype = $obj['datatype'];
    $matched = $obj['matched'];

    $c[] = "<tr class='" . ($matched ? '' : 'not-matched') . "'>";
    $c[] = "<td width='150px'>$key</td>";
    for($index = 0 ; $index < $col_count ; $index++){
      $c[] = "<td width='{$width}%'>";
      $c[] = $obj['value'][$index];
      $c[] = "</td>";
    }

    $c[] = "</tr>";
  }
  $c[] = "</table>";
  $c[] = "</span>";

  return implode('', $c);

}

?>