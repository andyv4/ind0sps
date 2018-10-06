<?php

function m_load(){

  global $module;
  $c = m_grid(array('module'=>$module));
  $c .= uijs("m_resize();");
  return $c;

}

function m_init($reset = false){

  global $module;
  if($reset) $module = m_loadstate($reset);

  $c = '';
  $c .= m_head(array('module'=>$module));
  $c .= m_grid(array('module'=>$module));
  $c .= uijs("m_resize();");
  return $c;

}

function m_export(){

  global $module;
  $preset = $module['presets'][$module['presetidx']];
  $columns = $preset['columns'];
  $sorts = $preset['sorts'];
  $quickfilters = ov('quickfilters', $preset);
  $filters = $preset['filters'];
  $filters = m_quickfilter_to_filters($filters, $quickfilters);
  $viewtype = ov('viewtype', $preset);

  if($viewtype == 'group'){
    $groups = ov('groups', $preset);

    $datasourcecolumns = $columns;
    $groupcolumns = groupcolumns_from_groups($groups);
    for($i = 0 ; $i < count($datasourcecolumns) ; $i++)
      if(in_array($datasourcecolumns[$i]['name'], $groupcolumns)) $datasourcecolumns[$i]['active'] = 1;

    // Sort the group ascendingly
    $groupsorts = array();
    foreach($groups as $group){
      $groupname = $group['name'];
      $groupsorttype = 'asc';
      switch($group['aggregrate']){
        case 'monthly': $groupname = "MONTH($groupname)"; break;
        case 'yearly': $groupname = "YEAR($groupname)"; break;
      }
      $groupsorts[] = array('name'=>$groupname, 'sorttype'=>$groupsorttype);
    }
    foreach($sorts as $sort)
      $groupsorts[] = $sort;

    $data = datasource($datasourcecolumns, $groupsorts, $filters, null);
    $data = data_group($data, $groups);
  }
  else{
    $data = datasource($columns, $sorts, $filters, null);
  }

  $filepath = ui_export_xls($data);

  echo uijs("ui('#downloader').href = '$filepath';ui('#downloader').click()");

}

function ui_export_xls($data){

  $c = "<table>";
  foreach($data as $obj){
    $c .= "<tr>";
    foreach($obj as $key=>$value){
      if(strpos($key, '__') !== false) continue;
      $c .= "<td>$value</td>";
    }
    $c .= "</tr>";

    if(isset($obj['__groupitems'])){
      $c .= ui_export_xls_more($obj['__groupitems']);
    }
  }
  $c .= "</table>";

  global $cachedir;
  $filename = 'f' . uniqid() . '.xls';
  $filepath = $cachedir . '/' . $filename;
  file_put_contents($filepath, $c);
  return $filepath;

}

function ui_export_xls_more($arr, $level = 0){

  if($level > 0) return '';

  $c = '';
  foreach($arr as $obj){

    $c .= "<tr>";
    for($i = 0 ; $i <= $level ; $i++)
      $c.= "<td></td>";
    foreach($obj as $key=>$value){
      if(strpos($key, '__') !== false) continue;
      $c .= "<td>$value</td>";
    }
    $c .= "</tr>";

    if(isset($obj['__groupitems'])){
      $c .= ui_export_xls_more($obj['__groupitems'], $level + 1);
    }

  }
  return $c;

}

function m_head($param){

  global $module;
  $presets = $module['presets'];
  if(!is_array($presets)) throw new Exception('Preset error, Reset the preset to solve the problem');
  $presetitems = array();
  foreach($presets as $preset)
    $presetitems[] = array('text'=>$preset['text'], 'value'=>$preset['text']);

  $presetidx = $module['presetidx'];
  $preset = $module['presets'][$presetidx];
  $quickfilter = ov('quickfilters', $preset);
  $quickfilters_operator = ov('quickfilters_operator', $preset, 0, 'and');

  if(function_exists('m_quickfilter_items_from_value_custom'))
    $quickfilteritems = call_user_func_array('m_quickfilter_items_from_value_custom', [ $quickfilter ]);
  else
    $quickfilteritems = m_quickfilter_items_from_value($quickfilter);
  //exc([ $quickfilter, $quickfilteritems ]);

  $c = "<element exp='#row0'>";
  $c .= "<table cellspacing='5'><tr>
    " . (function_exists('customheadcolumns') ? call_user_func('customheadcolumns', array()) : '') . "
    <td><button class='hollow' id='reloadbtn' onclick=\"m_load()\"><span class='mdi mdi-refresh'></span></button></td>
    <td style='width:100%'>
      <div style='display:flex'>
        <div>" . ui_dropdown([ 'id'=>'quickfilter_opt', 'class'=>'dropdown no-border-right', 'width'=>'66px', 'items'=>[ [ 'text'=>'AND', 'value'=>'and' ], [ 'text'=>'OR', 'value'=>'or' ] ], 'value'=>$quickfilters_operator, 'onchange'=>"ui.async('m_quickfilter_apply', [ ui.multicomplete_value(ui('#quickfilter')), ui.dropdown_value(ui('#quickfilter_opt')) ], { waitel:'#row2' })" ]) . "</div>
        <div style='flex: 1 1 auto'>" . ui_multicomplete(array('id'=>'quickfilter', 'src'=>'m_quickfilter', 'width'=>'100%', 'value'=>$quickfilteritems, 'onchange'=>"ui.async('m_quickfilter_apply', [ ui.multicomplete_value(ui('#quickfilter')), ui.dropdown_value(ui('#quickfilter_opt')) ], { waitel:'#row2' })")) . "</div>
      </div>
      </td>
    <td id='preset_dropdown_cont'>" . ui_dropdown(array('items'=>$presetitems, 'width'=>150, 'value'=>$preset['text'], 'onchange'=>"ui.async('m_presetchange', [ value ], { waitel:'#row2' })")) . "</td>
    <td><button class='hollow' onclick=\"ui.async('m_gridoption_open', [], { waitel:this })\"><span class='mdi mdi-settings color-gray'></span></button></td>
  </tr></table>";
  $c .= "</element>";

  return $c;

}

function m_grid($param){

  global $module;
  $preset = $module['presets'][$module['presetidx']];
  $onrowdoubleclick = ov('onrowdoubleclick', $module);
  $columns = $preset['columns'];
  $sorts = $preset['sorts'];
  $rows = ov('rows', $module);
  $quickfilters = ov('quickfilters', $preset);
  $quickfilters_operator = ov('quickfilters_operator', $preset, 0, 'and');
  $filters = $preset['filters'];
  $filters = m_quickfilter_to_filters($filters, $quickfilters, $quickfilters_operator);
  $m_customgridheadcolumns = function_exists('m_customgridheadcolumns') ? call_user_func_array('m_customgridheadcolumns', array()) : null;

  if(function_exists('m_griddoubleclick')) $onrowdoubleclick = m_griddoubleclick();

  $viewtype = ov('viewtype', $preset);

  $c = "<script>ui.grid_store = {};</script>"; // only allow 1 grid per dashboard
  if($viewtype == 'group'){
    $groups = ov('groups', $preset);

    $datasourcecolumns = $columns;
    $groupcolumns = groupcolumns_from_groups($groups);
    for($i = 0 ; $i < count($datasourcecolumns) ; $i++)
      if(in_array($datasourcecolumns[$i]['name'], $groupcolumns)) $datasourcecolumns[$i]['active'] = 1;

    // Sort the group ascendingly
    $groupsorts = array();
    foreach($groups as $group){
      $groupname = $group['name'];
      $groupsorttype = 'asc';
      switch($group['aggregrate']){
        case 'monthly': $groupname = "MONTH($groupname)"; break;
        case 'yearly': $groupname = "YEAR($groupname)"; break;
      }
      $groupsorts[] = array('name'=>$groupname, 'sorttype'=>$groupsorttype);
    }
    foreach($sorts as $sort)
      $groupsorts[] = $sort;

    // Auto enabled grouped columns
    $additional_columns = [];
    foreach($groups as $group){
      $group_columns = $group['columns'];
      foreach($group_columns as $group_column)
        $additional_columns[$group_column['name']] = 1;
    }
    $additional_columns = array_keys($additional_columns);
    foreach($columns as $index=>$column)
      if(in_array($column['name'], $additional_columns))
        $columns[$index]['active'] = 1;

    /*$value = datasource($datasourcecolumns, $groupsorts, $filters, null);
    $c .= "<element exp='#row1'>";
    $c .= ui_groupgridhead(array('columns'=>$columns, 'groups'=>$groups));
    $c .= "</element>";
    $c .= ui_render(ui_grid2(array('columns'=>$columns, 'id'=>'grid1', 'scrollel'=>'#row2', 'sorts'=>$sorts, 'filters'=>$filters, 'groups'=>$groups, 'value'=>$value, 'onrowdoubleclick'=>$onrowdoubleclick)), '#row2');*/

    $c .= "<element exp='#row1'>";
    $c .= ui_grid3head(array('columns'=>$columns, 'groups'=>$groups));
    $c .= "</element>";
    $c .= "<element exp='#row2'>";
    $c .= ui_render(ui_grid3(array('columns'=>$columns, 'id'=>'grid1', 'scrollel'=>'#row2', 'datasource'=>'datasource', 'sorts'=>$sorts, 'filters'=>$filters, 'groups'=>$groups, 'onrowdoubleclick'=>$onrowdoubleclick)), '#row2');
    $c .= "</element>";

  }
  else{
    $c .= ui_render(ui_grid2(array('columns'=>$columns, 'rows'=>$rows, 'datasource'=>'datasource', 'id'=>'grid1', 'gridhead'=>'#gridhead1',
        'scrollel'=>'#row2', 'sorts'=>$sorts, 'filters'=>$filters, 'onrowdoubleclick'=>$onrowdoubleclick)), '#row2');

    $c .= "<element exp='#row1'>";
    $c .= ui_gridhead(array('columns'=>$columns, 'gridexp'=>'#grid1', 'id'=>'gridhead1', 'oncolumnresize'=>'m_columnresize',
      'oncolumnclick'=>'m_columnclick', 'oncolumnapply'=>'m_columnchange', 'customgridheadcolumns'=>$m_customgridheadcolumns));
    $c .= "</element>";

  }

  return $c;

}

function m_presetchange($presettext){

  global $module;
  $presets = $module['presets'];
  for($presetidx = 0 ; $presetidx < count($presets) ; $presetidx++)
    if($presets[$presetidx]['text'] == $presettext)
      break;
  $module['presetidx'] = $presetidx;
  m_savestate($module);
  return m_load();

}

function m_columnresize($name, $width){

  global $module;
  $presetidx = $module['presetidx'];
  for($i = 0 ; $i < count($module['presets'][$presetidx]['columns']) ; $i++){
    if($module['presets'][$presetidx]['columns'][$i]['name'] == $name){
      $module['presets'][$presetidx]['columns'][$i]['width'] = $width;
    }
  }
  m_savestate($module);

}

function m_columnchange($columns){

  global $module;
  $columns = array_index($columns, array('name'), 1);
  $presetidx = $module['presetidx'];
  $preset = $module['presets'][$presetidx];
  for($i = 0 ; $i < count($preset['columns']) ; $i++)
    if(isset($columns[$preset['columns'][$i]['name']]))
      $preset['columns'][$i]['active'] = $columns[$preset['columns'][$i]['name']]['active'];
  $module['presets'][$presetidx] = $preset;
  m_savestate($module);
  return m_load();
}

function m_columnclick($name){

  global $module;
  $presetidx = $module['presetidx'];
  $preset = $module['presets'][$presetidx];

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
  m_savestate($module);
  return m_load();

}

function m_loadstate($reset = false){

  // System module mode
  if(isset($_GET['preset']) && function_exists('systemmodule')){

    $module = systemmodule($_GET['preset']);
    $module['_systemmodule'] = 1;

  }

  // Defined module mode
  else{

    global $cachedir, $modulename, $module;
    $path = $cachedir . '/' . md5($modulename);

    if($reset || !file_exists($path)){
      $module = function_exists('defaultmodule') ? defaultmodule() : array();

      if(!isset($module['columns'])){
        if(function_exists('datasource')){
          $data = call_user_func_array('datasource', array(null, null, null, array('offset'=>0, 'limit'=>1)));
          if(is_array($data) && count($data) == 1){
            $module['columns'] = array();
            $obj = $data[0];
            foreach($obj as $key=>$value){
              $module['columns'][] = array(
                'active'=>1,
                'text'=>$key,
                'datatype'=>'text',
                'width'=>50
              );
            }
          }

        }
        else{
          throw new Exception('Columns required. Auto column failed, no datasource defined');
        }
      }
      if(!isset($module['presetidx'])) $module['presetidx'] = 0;
      if(!isset($module['presets']) || !is_array($module['presets'])) $module['presets'] = array();
      if(count($module['presets']) == 0) $module['presets'] = array(
        array(
          'columns'=>$module['columns'],
          'viewtype'=>'list',
          'text'=>'Default'
        )
      );

      file_put_contents($path, serialize($module));
    }
    else
      $module = unserialize(file_get_contents($path));

    if(function_exists('m_loadstate_ex')) call_user_func_array('m_loadstate_ex', array($reset, $module));
    //if(function_exists('defaultcolumns')) m_updatestate($module, call_user_func_array('defaultcolumns', []));
    //if(function_exists('defaultpresets')) m_updatepreset($module, call_user_func_array('defaultpresets', []));

  }

  return $module;

}

function m_savestate($module){

  if(isset($module['_systemmodule'])) return; // Dont save system module
  global $cachedir, $modulename;
  $path = $cachedir . '/' . md5($modulename);
  file_put_contents($path, serialize($module));

}

function m_updatestate($module, $columns){

  $updated = false;
  if(isset($module['presets']) && is_array($module['presets'])){
    for($i = 0 ; $i < count($module['presets']) ; $i++){
      if(!isset($module['presets'][$i]['columns'])) continue;
      $preset_updated = m_module_column_update($module['presets'][$i]['columns'], $columns);
      if($preset_updated) $updated = $preset_updated;
    }
  }
  if($updated) m_savestate($module);

}

function m_updatepreset($module, $presets){

  $updated = false;

  if(isset($module['presets']) && is_array($module['presets']) && is_array($presets)){

    $module_presets_indexed = array_index($module['presets'], [ 'text' ], 1);

    foreach($presets as $preset){
      if(!isset($module_presets_indexed[$preset['text']])){
        $module['presets'][] = $preset;
        $updated = true;
      }
    }

  }

  if($updated) m_savestate($module);

}

function m_module_column_update(&$columns1, $columns2){

  if(!is_array($columns2)) return $columns1;

  $updated = false;
  $columns1_indexed = array_index($columns1, [ 'name' ], 1);
  $columns2_indexed = array_index($columns2, [ 'name' ]);

  // Check removed
  for($i = 0 ; $i < count($columns1) ; $i++){
    $column = $columns1[$i];
    $columnname = $column['name'];
    if(!isset($columns2_indexed[$columnname])){

      $columnindex = -1;
      for($j = 0 ; $j < count($columns1) ; $j++){
        if($columns1[$j]['name'] == $columnname){
          $columnindex = $j;
          break;
        }
      }

      if($columnindex > -1){
        array_splice($columns1, $columnindex, 1);
        $updated = true;
        console_log('remove ' . $columnname . ' at index ' . $columnindex);
      }

    }
  }

  // Check insert & update
  $updated_columns = [];
  for($i = 0 ; $i < count($columns2) ; $i++){
    $column = $columns2[$i];
    $columnname = $column['name'];

    // Check insert
    if(!isset($columns1_indexed[$columnname])){

      $prev_column = $i > 0 ? $columns2[$i - 1] : null;
      $next_column = $i + 1 < count($columns2) ? $columns2[$i + 1] : null;
      $prev_columnname = isset($prev_column['name']) ? $prev_column['name'] : '';
      $next_columnname = isset($next_column['name']) ? $next_column['name'] : '';

      $columnindex = -1;
      for($j = 0 ; $j < count($columns1) ; $j++){
        if($columns1[$j]['name'] == $prev_columnname){
          $columnindex = $j + 1;
          break;
        }
        else if($columns1[$j]['name'] == $next_columnname){
          $columnindex = $j;
          break;
        }
      }

      if($columnindex > 0){
        array_splice($columns1, $columnindex, null, [ $column ]);
        $updated = true;
      }

    }

    // Check update
    else{

      if(!isset($updated_columns[$column['name']]) &&
        (isset($columns1_indexed[$columnname]['type']) && $columns1_indexed[$columnname]['type'] != $column['type'])
      ){
        $updated_columns[$column['name']] = $column;
        console_log([ $i, 'updated', $column['name'] ]);
      }

    }

  }

  for($i = 0 ; $i < count($columns1) ; $i++){
    $columnname = $columns1[$i]['name'];
    if(isset($updated_columns[$columnname])){
      $columns1[$i] = $updated_columns[$columnname];
      console_log($columns1[$i]);
    }
  }


  return $updated;

}

function m_gridoption_open(){

  global $module, $groupable;
  $c = "<element exp='.modal'>";
  $c .= ui_gridoption(array(
    'value'=>$module,
    'onapply'=>'m_gridoption_save',
    'ondownload'=>'m_gridoption_download',
    'onupload'=>'m_gridoption_upload',
    'groupable'=>isset($groupable) ? $groupable : false
  ));
  $c .= "</element>";
  return $c;

}

function m_gridoption_save($obj){

  global $module;
  $module = $obj;

  m_savestate($obj);

  $presets = $module['presets'];
  if(!is_array($presets)) throw new Exception('Preset error, Reset the preset to solve the problem');
  $presetitems = array();
  foreach($presets as $preset)
    $presetitems[] = array('text'=>$preset['text'], 'value'=>$preset['text']);
  $presetidx = $module['presetidx'];
  $preset = $module['presets'][$presetidx];

  $c = [];
  $c[] = "<element exp='#preset_dropdown_cont'>";
  $c[] = ui_dropdown(array('items'=>$presetitems, 'width'=>150, 'value'=>$preset['text'], 'onchange'=>"ui.async('m_presetchange', [ value ], { waitel:'#row2' })"));
  $c[] = "</element>";
  $c[] = m_load();
  return implode('', $c);

}

function m_gridoption_download($obj, $idx){

  global $module, $cachedir, $modulename;
  $module = $obj;
  m_savestate($obj);

  $preset = $module['presets'][$idx];
  $path = $cachedir . '/preset-' . $modulename . '-' . preg_replace('/\s+/', '', strtolower($preset['text'])) . ".txt";
  file_put_contents($path, serialize($preset));

  return uijs("
    ui('#downloader').href = '$path';
    ui('#downloader').click();
  ");

}

function m_gridoption_upload($params){

  $fileurl = $params['fileurl'];
  $content = file_get_contents($fileurl);
  $preset = unserialize($content);

  global $module, $cachedir, $modulename;
  $module['presets'][] = $preset;
  m_savestate($module);

  return m_gridoption_open();

}

function m_quickfilter($param){

  if(function_exists('m_quickfilter_custom')){
    return call_user_func_array('m_quickfilter_custom', [ $param ]);
  }
  else{
    $hint = ov('hint', $param);
    global $module;
    $quickfilterscolumns = ov('quickfilterscolumns', $module);
    if(is_array($quickfilterscolumns)){
      $items = array();
      foreach($quickfilterscolumns as $quickfilterscolumn){
        $text = $quickfilterscolumn['text'];
        $value = $quickfilterscolumn['value'];

        $items[] = array('text'=>$text . $hint, 'value'=>$value . $hint);
      }
    }
    else
      throw new Exception('Parameter quickfilterscolumns not set');
    return $items;
  }



}

function m_quickfilter_apply($value, $operator = 'and'){

  if(function_exists('m_quickfilter_apply_custom')){
    return call_user_func_array('m_quickfilter_apply_custom', [ $value, $operator ]);
  }
  else{
    global $module;
    $presetidx = $module['presetidx'];
    $module['presets'][$presetidx]['quickfilters'] = $value;
    $module['presets'][$presetidx]['quickfilters_operator'] = $operator;
    m_savestate($module);
    return m_load();
  }

}

function m_quickfilter_items_from_value($value){

  if(strpos($value, '&') !== false){

    global $module;
    $quickfilterscolumns = ov('quickfilterscolumns', $module);

    $results = array();
    $arr = explode(',', $value);
    foreach($arr as $obj){
      $text = explode('&', $obj);
      $text = $text[2];
      foreach($quickfilterscolumns as $quickfilterscolumn)
        if(strpos($obj, $quickfilterscolumn['value']) !== false){
          $text = $quickfilterscolumn['text'] . $text;
          break;
        }
      $results[] = array('text'=>$text, 'value'=>$obj);
    }
    return $results;
  }

}

function m_quickfilter_to_filters($filters, $value, $opt = 'and'){

  if(strpos($value, '&')){

    global $module;
    if(!is_array($filters)) $filters = array();

    $arr = explode(',', $value);
    if(count($filters) > 0)
      $filters[] = array('type'=>'and');
    $filters[] = array('type'=>'(');
    for($i = 0 ; $i < count($arr) ; $i++){
      $obj = $arr[$i];
      $obj = explode('&', $obj);
      $name = $obj[0];
      $operator = $obj[1];
      $value = $obj[2];

      if($operator == 'bool'){
        $operator = '=';
        $value = $value == true ? 1 : 0;
      }

      if($name == 'all' && isset($module['quickfilterscolumns']))
        $name = $module['quickfilterscolumns'];

      if($i > 0 && $opt == 'or') $filters[] = array('type'=>'or');
      $filters[] = array('name'=>$name, 'operator'=>$operator, 'value'=>$value);
    }
    $filters[] = array('type'=>')');
  }
  return $filters;

}

function ui_moduleopen($ref, $refid){

  switch($ref){
    case 'PI':
      require_once 'ui/purchaseinvoice.php';
      return ui_purchaseinvoiceopen($refid);
      break;
    case 'IA':
      require_once 'ui/inventoryadjustment.php';
      return ui_inventoryadjustmentdetail($refid);
      break;
    case 'WT':
      require_once 'ui/warehousetransfer.php';
      return ui_warehousetransferdetail($refid);
      break;
    case 'SI':
      require_once 'ui/salesinvoice.php';
      return ui_salesinvoiceopen($refid);
      break;
    case 'SJS':
      require_once 'ui/sampleinvoice.php';
      return ui_sampleinvoicedetail($refid);
      break;
    default:
      exc("Tidak dapat membuka detil ini. [ref: {$ref}, refid:{$refid}]");
  }

}

$module = m_loadstate();
if(function_exists('m_patchstate')) m_patchstate();

ui_async();
?>
<div class="padding10">

  <div id="row0"></div>

  <div id="row1"></div>

  <div id="row2" class='scrollable' data-loadprogresscallback="m_loadingprogress"></div>

  <script type="text/javascript">

    function m_load(){

      ui.async('m_load', [], { waitel:'#row2', onload:'m_load_completed' });

    }
    function m_load_completed(){

      if(typeof m_onload == 'function') m_onload();

    }

    function m_resize(){

      ui('#row2').style.height = (window.innerHeight - ui('#row0').clientHeight - ui('#row1').clientHeight) - 24 + "px";

    }

    function m_loadingprogress(){
      //ui('#row2').innerHTML = "<div class='spinner-type0'>Loading...</div>";
      ui('#row2').innerHTML = '<div class="sk-cube-grid"><div class="sk-cube sk-cube1"></div><div class="sk-cube sk-cube2"></div><div class="sk-cube sk-cube3"></div><div class="sk-cube sk-cube4"></div><div class="sk-cube sk-cube5"></div><div class="sk-cube sk-cube6"></div><div class="sk-cube sk-cube7"></div><div class="sk-cube sk-cube8"></div><div class="sk-cube sk-cube9"></div></div>';
    }

    function m_init(input){

      var reset = <?=isset($mod_reset) && $mod_reset > 0 ? 1 : 0?>;
      reset = typeof qs['reset'] != 'undefined' ? 1 : reset;

      var params = [ reset ];
      if(typeof input == 'object')
        for(var i = 0 ; i < input.length ; i++)
          params.push(input[i]);
      params.push(qs);

      ui.async('m_init', params, { waitel:'#row2', onload:"m_load_completed" });
      $(window).on('resize', function(){ m_resize(); });

    }

    <?php if(!isset($autoload) || (isset($autoload) && $autoload)){ ?>m_init();<?php } ?>

  </script>

</div>
<?php
$title = ov('title', $module);
?>