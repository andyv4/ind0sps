<?php

/*
 * Search via multicomplete option handler
 */
function modulelist_searchoption(){


}

/*
 * Load entire module, used for initiation and reload
 */
function modulelist_load($reset = false){

  $module = modulelist_loadmodule($reset);
  $preset = $module['presets'][$module['presetidx']];
  $presetcolumns = $preset['columns'];
  $datasource = $module['datasource'];

  $c = '';
  $c .= ui_render(ui_gridhead(array('columns'=>$presetcolumns, 'gridexp'=>'#grid1',
      'oncolumnresize'=>'modulelist_columnresize', 'oncolumnclick'=>'modulelist_columnclick',
      'oncolumnapply'=>'modulelist_columnchange')), '#row1');
  $c .= ui_render(ui_grid2(array('columns'=>$presetcolumns, 'id'=>'grid1', 'datasource'=>$datasource, 'scrollel'=>'#row2',
    'progresscallback'=>'modulelist_loadprogress')), '#row2');
  return $c;

}

function modulelist_loadprogress($params){

  $current = $params['current'];
  $total = $params['total'];

  echo uijs("console.warn('$current of $total')");

}

function modulelist_columnresize($name, $width){

  $module = modulelist_loadmodule();
  $presetidx = $module['presetidx'];
  for($i = 0 ; $i < count($module['presets'][$presetidx]['columns']) ; $i++){
    if($module['presets'][$presetidx]['columns'][$i]['name'] == $name){
      $module['presets'][$presetidx]['columns'][$i]['width'] = $width;
    }
  }
  modulelist_savemodule($module);

}

function modulelist_columnclick($name){

  $module = modulelist_loadmodule();
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
  modulelist_savemodule($module);
  return modulelist_load();

}

function modulelist_columnchange($columns){

  $columns = array_index($columns, array('name'), 1);
  $module = modulelist_loadmodule();
  $presetidx = $module['presetidx'];
  $preset = $module['presets'][$presetidx];
  for($i = 0 ; $i < count($preset['columns']) ; $i++)
    if(isset($columns[$preset['columns'][$i]['name']]))
      $preset['columns'][$i]['active'] = $columns[$preset['columns'][$i]['name']]['active'];
  $module['presets'][$presetidx] = $preset;
  modulelist_savemodule($module);
  return modulelist_load();

}

function modulelist_loadmodule($reset = false){

  global $modulename, $moduledatadir;
  if($reset || !file_exists($moduledatadir . '/' . md5($modulename))){
    $module = defaultmodule();
    modulelist_savemodule($module);
  }
  else
    $module = unserialize(file_get_contents($moduledatadir . '/' . md5($modulename)));
  return $module;

}

function modulelist_savemodule($module){

  global $modulename, $cachedir;
  file_put_contents($cachedir . '/c' . md5($modulename), serialize($module));

}

/*
 * Handler for grid option button click
 */
function modulelist_gridoption(){

  $module = modulelist_loadmodule();
  return ui_render(ui_gridoption(array('value'=>$module, 'onapply'=>'modulelist_gridoption_apply')), '.modal');

}

function modulelist_gridoption_apply($module){

  modulelist_savemodule($module);
  return modulelist_load();

}

ui_async();
?>
<div class="padding10">

  <div id="row0" class="padding10" style="">
    <button class="hollow"><span class="mdi mdi-plus"></span></button>
    <span class="spacer4"></span>
    <button class="hollow"><span class="mdi mdi-refresh"></span></button>
    <span class="spacer4"></span>
    <?=ui_multicomplete(array('src'=>'modulelist_searchoption', 'width'=>300))?>
    <span class="spacer4"></span>
    <button class="hollow" onclick="ui.async('modulelist_gridoption', [], { waitel:this })"><span class="mdi mdi-settings"></span></button>
  </div>

  <div id="row1">

  </div>

  <div id="row2" class="scrollable">

  </div>

  <script type="text/javascript">

    modulelist = {};
    qs = <?=json_encode($_GET);?>;

    modulelist.init = function(){
      ui.async('modulelist_load', [ qs['reset'] ? 1 : 0 ], { partial:1 });
      modulelist.resize();
    }
    modulelist.resize = function(){
      ui("#row2").style.height = window.innerHeight - (ui("#row0").clientHeight + ui("#row1").clientHeight + 60);
    }
    modulelist.init();

  </script>

</div>