<?php


function m_metricoption($id){

  // retrieve metric by id
  $module = m_loadstate();
  $presetidx = $module['presetidx'];
  $preset = $module['presets'][$presetidx];
  $metrics = ov('metrics', $preset);
  foreach($metrics as $metric)
    if($metric['id'] == $id) break;

  // immediately return if metric not found
  if(!$metric) return;

  // retrieve required parameters
  $columnitems = array(
      array('text'=>'Date', 'value'=>'date'),
      array('text'=>'Code', 'value'=>'code'),
      array('text'=>'Customer', 'value'=>'customerdescription'),
      array('text'=>'Total', 'value'=>'total')
  );
  $logicitems = array(
      array('text'=>'First', 'value'=>'first'),
      array('text'=>'Sum', 'value'=>'sum'),
      array('text'=>'Average', 'value'=>'avg'),
      array('text'=>'Minimum', 'value'=>'min'),
      array('text'=>'Maximum', 'value'=>'max')
  );
  $text = ov('text', $metric);
  $column = ov('column', $metric);
  $logic = ov('logic', $metric);

  // render metric option ui
  $c = "<element exp='.modal'>";
  $c .= "<div class='padding10'>";
  $c .= "<table class='form' id='metricoption'>
    <tr><td colspan='2'>" . ui_hidden(array('name'=>'id', 'value'=>$id)) . "</td></tr>
    <tr><th><label>Text</label></th><td>" . ui_textbox(array('name'=>'text', 'width'=>'200', 'value'=>$text)) . "</td></tr>
    <tr><th><label>Columns</label></th><td>" . ui_textbox(array('name'=>'column', 'value'=>$column, 'width'=>'120px')) . "</td></tr>
    <tr><th><label>Logic</label></th><td>" . ui_dropdown(array('name'=>'logic', 'items'=>$logicitems, 'value'=>$logic, 'width'=>'100px')) . "</td></tr>
    <tr><td></td><td>
      <div style='height:20px'></div>
      <button class='blue' onclick=\"ui.async('m_metricoptionsave', [ ui.container_value(ui('#metricoption')) ], { waitel:this })\"><span class='fa fa-save'></span><label>Save</label></button>
      <button class='hollow' onclick=\"ui.modal_close(ui('.modal'))\"><span class='fa fa-times'></span><label>Close</label></button>
    </td></tr>
  </table>";
  $c .= "</div>";
  $c .="</element>";
  $c .= uijs("ui.modal_open(ui('.modal'))");
  return $c;

}

function m_metricoptionsave($obj){

  // retrieve metric
  $id = $obj['id'];
  $module = m_loadstate();
  $presetidx = $module['presetidx'];
  $preset = $module['presets'][$presetidx];
  $metrics = ov('metrics', $preset);
  $metricidx = -1;
  for($i = 0 ; $i < count($metrics) ; $i++)
    if($metrics[$i]['id'] == $id){
      $metricidx = $i;
      break;
    }

  // if no metric with current id found, return with alert
  if($metricidx == -1) throw new Exception('Unable to save metric, metric not found.');

  // save metric and persist
  $module['presets'][$presetidx]['metrics'][$metricidx] = $obj;
  m_savestate($module);

  // repaint ui
  return m_load() . uijs("ui.modal_close(ui('.modal'))");

}

function m_loaddata(){

  // retrieve required parameters
  $module = m_loadstate();
  $presetidx = $module['presetidx'];
  $preset = $module['presets'][$presetidx];
  $grid = $preset['grid'];
  $groups = ov('groups', $grid);
  $filters = ov('filters', $grid);
  $quickfilters = ov('quickfilters', $preset);
  $filters = m_quickfiltersvalue_to_filters($filters, $quickfilters);

  // retrieve data
  $data =  call_user_func_array('datasource', array($module['columns'], null, $filters));

  // data processing, skippable using skipinternalprocessing parameter of module
  if(!ov('skipinternaldataprocessing', $module)){
    echo uijs("console.warn('data count before filter: " . count($data) . "')");
    if(is_array($filters)) data_filter($data, $filters);
    echo uijs("console.warn('data count after filter: " . count($data) . "')");
  }

  // perform group if group exists
  if(is_array($groups)) $data = data_group($data, $groups);

  //echo uijs("console.warn('m_loaddata')");
  //echo uijs("console.log(" . json_encode($filters) . ")");
  //echo uijs("console.log(" . json_encode($groups) . ")");
  //echo uijs("console.log(" . json_encode($data) . ")");

  return $data;

}

function m_loadstate($reset = false){

  global $cachedir, $modulename;
  $path = $cachedir . '/' . md5($modulename);

  if($reset || !file_exists($path)){
    $module = defaultmodule();
  }
  else{
    $module = unserialize(file_get_contents($path));
  }

  return $module;

}

function m_savestate($module){

  global $cachedir, $modulename;
  $path = $cachedir . '/' . md5($modulename);
  file_put_contents($path, serialize($module));

}

function m_metricload($param = null){

  $eltag = ov('eltag', $param, 0, 1);
  $module = m_loadstate();
  $presetidx = $module['presetidx'];
  $preset = $module['presets'][$presetidx];
  $metrics = ov('metrics', $preset);
  $data = ov('data', $param);

  $c = '';
  if($eltag) $c .= "<element exp='#row1'>";
  if(is_array($metrics)){
    foreach($metrics as $metric){
      $id = $metric['id'];
      $text = $metric['text'];
      $column = $metric['column'];
      $logic = $metric['logic'];

      $value = m_metricvalue($data, $column, $logic);

      $c .= "<div class=\"metric\" style=\"width:330px\">
            <div class=\"metric-head\"><label>$text</label><span class=\"fa fa-cog\" onclick=\"ui.async('m_metricoption', [ $id ], { waitel:this })\"></span></div>
            <div class=\"metric-body\"><label>" . number_format($value). "</label></div>
          </div>";
    }
  }
  if($eltag) $c .= "</element>";
  return $c;

}

function m_metricvalue($data, $column, $logic){

  $value = '';
  switch($logic){
    case 'sum':
      $sum = 0;
      foreach($data as $obj)
        $sum += $obj[$column];
      $value = $sum;
      break;
    case 'avg':
      $sum = 0;
      foreach($data as $obj)
        $sum += $obj[$column];
      $avg = $sum / count($data);
      $value = $avg;
      break;
    case 'min':
      $min = null;
      foreach($data as $obj){
        if($min == null || $obj[$column] < $min)
          $min = $obj[$column];
      }
      $value = $min;
      break;
    case 'max':
      $max = null;
      foreach($data as $obj){
        if($max == null || $obj[$column] > $max)
          $max = $obj[$column];
      }
      $value = $max;
      break;
    default:
      foreach($data as $obj){
        $value = $obj[$column];
        break;
      }
  }
  return $value;

}

function m_load(){

  while (@ob_end_flush());
  echo uijs("ui.showstatus('Loading data...', 'info')");
  ob_flush();
  flush();

  $data = m_loaddata();
  $c = m_head();
  $c .= m_metricload(array('data'=>$data));
  $c .= m_chartload(array('data'=>$data));
  $c .= m_gridload(array('data'=>$data));
  $c .= uijs("ui.hidestatus()");

  return $c;

}

function m_head($param = null){

  $eltag = ov('eltag', $param, 0, 1);
  $module = m_loadstate();
  $presetidx = $module['presetidx'];
  $presets = $module['presets'];

  $presetitems = array();
  foreach($presets as $preset)
    $presetitems[] = array('text'=>$preset['text'], 'value'=>$preset['id']);
  $preset = $presets[$presetidx];

  $quickfilters = ov('quickfilters', $preset);
  $quickfilters = m_quickfiltersvalue_to_quickfilters($quickfilters);

  $c = '';
  if($eltag) $c .= "<element exp='#row0'>";
  $c .= "<table cellspacing=\"5\"><tr>";
  $c .= "<td><button class=\"hollow\" onclick=\"ui.async('m_load', [], { waitel:this })\"><span class=\"mdi mdi-refresh\"></span></button></td>";
  $c .= "<td style=\"width:100%\">" . ui_multicomplete(array('width'=>'100%', 'value'=>$quickfilters, 'src'=>'m_quickfilters', 'onchange'=>"ui.async('m_quickfiltersapply', [ value ], { waitel:this })", 'placeholder'=>'Quick filter...')) . "</td>";
  $c .= "<td>" . ui_dropdown(array('items'=>$presetitems, 'text'=>$preset['text'], 'value'=>$preset['id'], 'width'=>'120px')) . "</td>";
  $c .= "<td><button class=\"hollow\"><span class=\"fa fa-cog\"></span><label>Customize...</label></button></td>";
  $c .= "</tr></table>";
  if($eltag) $c .= "</element>";
  return $c;

}

function m_quickfilters($param){

  $hint = ov('hint', $param);
  $module = m_loadstate();
  $quickfilterscolumns = ov('quickfilterscolumns', $module);
  if(is_array($quickfilterscolumns)){
    $items = array();
    foreach($quickfilterscolumns as $quickfilterscolumn){
      $text = $quickfilterscolumn['text'];
      $value = $quickfilterscolumn['value'];

      $items[] = array('text'=>$text . $hint, 'value'=>$value . $hint);
    }
  }

  return $items;

}

function m_quickfiltersapply($value){

  $module = m_loadstate();
  $presetidx = $module['presetidx'];
  $module['presets'][$presetidx]['quickfilters'] = $value;
  m_savestate($module);
  return m_load();

}

function m_quickfiltersvalue_to_quickfilters($value){

  if(strpos($value, '&') !== false){

    $module = m_loadstate();
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

function m_quickfiltersvalue_to_filters($filters, $value){

  if(strpos($value, '&')){

    $module = m_loadstate();
    if(!is_array($filters)) $filters = array();

    $arr = explode(',', $value);
    foreach($arr as $obj){
      $obj = explode('&', $obj);
      $name = $obj[0];
      $operator = $obj[1];
      $value = $obj[2];

      if($name == 'all' && isset($module['quickfilterscolumns']))
        $name = $module['quickfilterscolumns'];

      $filters[] = array('name'=>$name, 'operator'=>$operator, 'value'=>$value);
    }
  }
  return $filters;

}

function m_gridload($param = null){

  $eltag = ov('eltag', $param, 0, 1);
  $module = m_loadstate();
  $data = ov('data', $param);

  $c = '';
  if($eltag) $c .= "<element exp='#row3'>";
  $c .= ui_gridhead(array('columns'=>$module['columns'], 'gridexp'=>'#grid'));
  if($eltag) $c .= "</element>";
  if($eltag) $c .= "<element exp='#row4'>";
  $c .= ui_grid2(array('columns'=>$module['columns'], 'id'=>'grid', 'value'=>$data));
  if($eltag) $c .= "</element>";
  $c .= uijs("resize()");
  return $c;

}

function m_chartload($param){

  echo uijs("console.warn('m_chartload')");

  $eltag = ov('eltag', $param, 0, 1);
  $data = ov('data', $param);
  $module = m_loadstate();
  $presetidx = $module['presetidx'];
  $preset = $module['presets'][$presetidx];
  $charts = ov('charts', $preset);

  $xaxis = ov('x-axis', $charts);
  $yaxis = ov('y-axis', $charts);

  echo uijs("console.warn('x-axis: $xaxis, y-axis: $yaxis')");

  $chartdata = array();
  $chartobj = array();
  foreach($data as $obj){
    $chartobj[] = $obj[$yaxis];
  }
  $chartdata[] = $chartobj;

  //echo uijs("console.log(" . json_encode($chartobj) . ")");

  /*
  for($i = 0 ; $i < 5 ; $i++){
    $obj = array();
    for($j = 0 ; $j < 12 ; $j++)
      $obj[] = rand(10, 100);
    $chartdata[] = $obj;
  }
  */

  $c = '';
  if($eltag) $c .= "<element exp='#row2'>";
  $c .= "
  <table cellspacing=\"5\">
      <tr>
        <td>
          " . ui_chart(array('width'=>'1020px', 'height'=>'180px', 'value'=>$chartdata)) . "
        </td>
      </tr>
  </table>
  ";
  if($eltag) $c .= "</element>";
  return $c;

}

ui_async();
?>
<div class="padding10">

  <div id="row0" class="row"></div>

  <div id="row1" class="row"></div>

  <div id="row2" class="row"></div>

  <div id="row3" class="row"></div>

  <div id="row4" class="row scrollable"></div>

  <script type="text/javascript">

    function resize(){

      ui('#row4').style.height = window.innerHeight - (ui('#row0').clientHeight + ui('#row1').clientHeight + ui('#row2').clientHeight + ui('#row3').clientHeight + 20);

    }

    function reload(srcEl){
      ui.async('m_load', [], { waitel:srcEl, partial:1 });
    }

    window.addEventListener('resize', resize);
    resize();
    reload();

  </script>

</div>