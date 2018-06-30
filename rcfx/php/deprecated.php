<?php

function ui_groupgridsect($obj, $params, &$caches){

  $name = ov('__name', $obj);
  $value = ov('__value', $obj);
  $items = ov('__items', $obj);
  $aggregrate = ov('__aggregrate', $obj);
  $cacheds = ov('cacheds', $params);

  $c = '';
  if(is_array($items) && count($items) > 0){
    $cacheid = uniqid();

    if(isset($items[count($items) - 1]['__items'])){
      for($i = 0 ; $i < count($items) ; $i++){

        $item = $items[$i];
        $columns = $params['columns'];
        $columns_indexed = array_index($columns, array('name'), 1);
        $groupgridsectbody_state = isset($item['__items'][0]['__items']) ? '' : 'off';

        if(isset($item['__items'])){
          $c .= "<div class='groupgridsect'>";
          $c .= "<div class='groupgridsecthead' onclick='ui.groupgridsecthead_click(event, this)'>";
          $c .= "<table><tr>";

          $caret_state = $groupgridsectbody_state == 'off' ? 'fa-caret-right' : 'fa-caret-down';

          $c .= "<td style='width: 6px'><span class='fa $caret_state'></span></td>";
          foreach($item as $key=>$value){
            if(strpos($key, '__') !== false) continue;
            $key_explode = explode('|', $key);
            $columnname = $key_explode[0];

            if(isset($columns_indexed[$columnname])){
              $column = $columns_indexed[$columnname];
              $column['active'] = 1;
              //$column['dateformat'] = 'M Y';
              //$column['datatype'] = 'text';
              $c .= ui_gridcol($column, array($columnname=>$value), 1);
            }
          }
          $c .= "<td style='width:100%'></td>";

          $c .= "</tr></table>";

          $c .= "</div>";
          $c .= "<div class='groupgridsectbody $groupgridsectbody_state'>";

          $c .= ui_groupgridsect($item, $params, $caches);

          $c .= "</div>";
          $c .= "</div>";

        }

        //break;
      }
    }
    else{
      $currentitems = array_splice($items, 0, 10);

      $c .= "<table>";
      for($i = 0 ; $i < count($currentitems) ; $i++){
        $item = $currentitems[$i];

        $c .= ui_gridrow($item, $params, $i == 0 ? 1 : 0, $i % 2);

        //break;
      }

      if(count($items) > 0){
        if($cacheds){
          $c .= "<tr class='tr_loadmore' data-cacheid='$cacheid' onclick=\"ui.groupgrid_trloadmoreclick2(event, this, '" . addslashes($name) . "', '" . addslashes($value) . "')\"><td colspan='100'>Load more from database named $name valued $value</td></tr>";
        }
        else{
          $caches[$cacheid] = $items;
          $c .= "<tr class='tr_loadmore' data-cacheid='$cacheid' onclick='ui.groupgrid_trloadmoreclick(event, this)'><td colspan='100'>Load more</td></tr>";
        }
      }


      $c .= "</table>";

    }

  }

  return $c;

}
function ui_groupgrid_loadcache($id, $cacheid){

  $cache = cache_get($id);
  $params = $cache['params'];
  $rowcaches = $cache['rowcaches'];
  $rowcache = $rowcaches[$cacheid];

  $rows = array_splice($rowcache, 0, 10);

  $c = "<element exp='null'>";
  for($i = 0 ; $i < count($rows) ; $i++)
    $c .= ui_gridrow($rows[$i], $params, 1, $i % 2);
  $c .= "</element>";
  $c .= uijs("remaining = " . count($rowcache) . "; console.warn(remaining);");

  $cache['rowcaches'][$cacheid] = $rowcache;
  cache_set($id, $cache);

  return $c;

}
function ui_groupgrid_loadfromcacheds($id, $name, $value, $pageidx){

  $cache = cache_get($id);
  $params = $cache['params'];
  $cacheds = ov('cacheds', $params);

  //echo uijs("console.log(" . json_encode(get_defined_vars()) . ")");

  $hasmore = 1;
  $items = call_user_func_array($cacheds, array($name, $value, $pageidx));
  $currentitems = array_splice($items, 0, 10);

  $c = "<element exp='null'>";
  for($i = 0 ; $i < count($currentitems) ; $i++)
    $c .= ui_gridrow($currentitems[$i], $params, 1, $i % 2);
  $c .= "</element>";
  $c .= uijs("remaining = " . (count($items) > 0 ? '1' : '0') . "; console.warn(remaining);");

  return $c;

}
function ui_groupgrid_value_as_singlearray($groupdata, $level = 0){

  $flatarray = array();
  if(is_array($groupdata))
    for($i = 0 ; $i < count($groupdata) ; $i++){
      $obj = $groupdata[$i];
      $obj['__level'] = $level;
      if(isset($obj['__items'])){
        $flatarray[] = $obj;

        if(isset($obj['__items'])){
          $inner_flatarray = ui_groupgrid_value_as_singlearray($obj['__items'], $level + 1);
          if(is_array($inner_flatarray))
            $flatarray = array_merge($flatarray, $inner_flatarray);
        }
      }
    }
  return $flatarray;

}

function ui_groupgridsql_loadcache($type, $cacheid){

  $obj = cache_get($cacheid);

  $c = '';
  if($type == 'group'){

    $params = $obj[0];
    $level = $obj[1];
    $filters = $obj[2];
    $items = $obj[3];

    $viewitems = array_splice($items, 0, 24);
    cache_set($cacheid, array($params, $level, $filters, $items));

    $c = ui_groupgridsql($params, $level, $filters, $viewitems);

  }
  else{

    $params = $obj[0];
    $filters = $obj[1];
    $items = $obj[2];

    $viewitems = array_splice($items, 0, 24);
    cache_set($cacheid, array($params, $filters, $items));

    $c = ui_groupgridsql_item($params, $filters, $viewitems);

  }

  $result = json_encode(array('c'=>$c, 'more'=>count($items) > 0 ? 1 : 0));
  echo uijs("result = $result;");

}
function ui_groupgridsql($params, $level = 0, $filters = null, $items = null){

  $groups = $params['groups'];
  $group = $groups[$level];
  $groupcacheds = ov('groupcacheds', $params);
  $paramfilters = ov('filters', $params);

  if($filters == null) $filters = array();
  if(is_array($paramfilters)) $filters = array_merge($filters, $paramfilters);

  if(function_exists($groupcacheds)){

    // Retrieve data
    if($items == null) $items = call_user_func_array($groupcacheds, array($groups, $level, null, $filters));

    $groupcolumns = $group['columns'];
    $columns = ov('columns', $params);
    $columns_indexed = array_index($columns, array('name'), 1);
    $nexttype = $level + 1 < count($groups) ? 'group' : 'list';

    // Cache partitioning
    $viewitems = array_splice($items, 0, 24);
    $cacheid = cache_set(null, array($params, $level, $filters, $items));

    // UI
    $c = '';
    for($i = 0 ; $i < count($viewitems) ; $i++){
      $item = $viewitems[$i];

      $c .= "<div class='groupgridsect'>";
      $c .= "<div class='groupgridsecthead' onclick='ui.groupgridsecthead_click(event, this)'>";

      $c .= "<table><tr>";
      $c .= "<td style='width: 6px'><span class='fa" . ($nexttype == 'group' ? ' fa-caret-down' : ' fa-caret-right') . "'></span></td>";
      foreach($groupcolumns as $groupcolumn){
        if(!isset($columns_indexed[$groupcolumn['name']])) continue;
        $column = $columns_indexed[$groupcolumn['name']];
        $column['active'] = 1; // Bypass active parameter
        //if($groupcolumn['logic'] != 'first') $column['name'] = $column['name'] . '|' . $groupcolumn['logic'];

        //echo uijs("console.log(" . json_encode($column) . ")");
        //echo uijs("console.log(" . json_encode($item) . ")");

        //echo uijs("");

        $c .= ui_gridcol($column, $item, 1);
      }
      $c .= "<td style='width:100%'></td>";
      $c .= "</tr></table>";

      $c .= "</div>";
      $c .= "<div class='groupgridsectbody" . ($nexttype == 'list' ? ' off' : '') . "'>";

      $nextfilters = array();
      if(is_array($filters))  $nextfilters = array_merge($nextfilters, $filters);
      $nextfilters[] = array('name'=>$group['name'], 'operator'=>'equals', 'value'=>$item[$group['name']]);

      if($nexttype == 'group'){
        $c .= ui_groupgridsql($params, $level + 1, $nextfilters);
      }
      else{
        $c .= "<table>";
        $c .= ui_gridrow($item, $params, $i == 0 ? 1 : 0, $i % 2);
        $c .= "</table>";
      }

      $c .= "</div>";
      $c .= "</div>";

    }

    // Load more
    if(count($items) > 0) $c .= "<div class='align-center' onclick=\"ui.groupgridsql_loadcache(this, 'group', '$cacheid')\"><label>Load more from $cacheid</label></div>";

  }


  return $c;

}
function ui_groupgridsql_item($params, $filters = null, $items = null){

  $cacheds = ov('cacheds', $params);
  $c = '';
  if(function_exists(($cacheds))){

    $set_new_cache = $items == null ? 1 : 0;

    if($items == null) $items = call_user_func_array($cacheds, array(false, null, $filters, 0));

    $viewitems = array_splice($items, 0, 24);

    for($i = 0 ; $i < count($viewitems) ; $i++){
      $item = $viewitems[$i];

      $c .= ui_gridrow($item, $params, $i == 0 ? 1 : 0, $i % 2);
    }


    // Cache partitioning
    if(count($items) > 0 && $set_new_cache){
      $cacheid = cache_set(null, array($params, $filters, $items));

      $c .= "<tr class='tr_loadmore' onclick=\"ui.groupgridsql_loadcache(this, 'item', '$cacheid')\"><td colspan='100' align='center'>More</td></tr>";
    }

  }
  return $c;

}

function ui_groupgrid_sqltype($params, $index = 0, $groupfilters = null){

  $columns = ov('columns', $params);
  $filters = ov('filters', $params);
  $columns_indexed = array_index($columns, array('name'), 1);

  $groups = ov('groups', $params);
  $group = $groups[$index];
  $groupname = $group['name'];
  $groupcolumns = $group['columns'];
  $groupds = ov('groupcacheds', $params);

  if(is_array($groupfilters) && count($groupfilters) > 0 && !is_array($filters)) $filters = array();
  if(is_array($groupfilters) && count($groupfilters) > 0) $filters = array_merge($filters, $groupfilters);

  $nextisgroup = $index + 1 < count($groups) ? 1 : 0;

  $arr = call_user_func_array($groupds, array($group, $filters));

  file_put_contents('usr/log.txt', "group query\n", FILE_APPEND);

  $c = '';
  for($i = 0 ; $i < count($arr) ; $i++){

    $obj = $arr[$i];

    $c .= "<div class='groupgridsect'>";
    $c .= "<div class='groupgridsecthead' onclick='ui.groupgridsecthead_click(event, this)'>";

    $c .= "<table><tr>";
    $c .= "<td style='width: 6px'><span class='fa" . ($nextisgroup  ? ' fa-caret-down' : ' fa-caret-right') . "'></span></td>";
    foreach($groupcolumns as $groupcolumn){
      if(!isset($columns_indexed[$groupcolumn['name']])) continue;

      $column = $columns_indexed[$groupcolumn['name']];
      $column['active'] = 1;
      $column['name'] = $groupcolumn['logic'] != 'first' ? $column['name'] . '.' . $groupcolumn['logic'] : $column['name'];

      $c .= ui_gridcol($column, $obj, 1);
    }
    $c .= "<td style='width:100%'></td>";
    $c .= "</tr></table>";

    $c .= "</div>";
    $c .= "<div class='groupgridsectbody" . (!$nextisgroup ? ' off' : '') . "'>";

    $nextfilters = array();
    if(is_array($filters)) $nextfilters = array_merge($nextfilters, $filters);
    $nextfilters[] = array('name'=>$groupname, 'operator'=>'equals', 'value'=>ov($groupname, $obj));

    if($nextisgroup){

      $groupfilters = array(array('name'=>$groupname, 'operator'=>'equals', 'value'=>ov($groupname, $obj)));
      $c .= ui_groupgrid_sqltype($params, $index + 1, $groupfilters);

    }
    else{
      $cacheds = ov('cacheds', $params);
      $bypass = true;
      $itemarr = call_user_func_array($cacheds, array($columns, $nextfilters));

      $c .= "<table>";
      for($j = 0 ; $j < count($itemarr) ; $j++){
        $itemobj = $itemarr[$j];

        $c .= ui_gridrow($itemobj, $params, $j == 0 ? 1 : 0, $j % 2);
      }
      //$c .= ui_groupgridsql_item($params, $nextfilters);
      $c .= "</table>";


    }


    $c .= "</div>";
    $c .= "</div>";

  }

  //echo uijs("console.log(" . json_encode($arr) . ")");

  return $c;

}

function ui_groupgrid($params){

  /*
   * Params:
   * - id
   * - columns
   * - filters
   * - groups
   * - value
   * - ondoubleclick_callback
   * - groupcacheds
   * - cacheds
   */
  //echo uijs("console.log(" . json_encode(get_defined_vars()) . ")");

  $id = ov('id', $params);
  $cacheds = ov('cacheds', $params);

  if(!empty($cacheds)){

    $c = "<div id='$id' class='grid'>";
    //$c .= ui_groupgridsql($params, 0);
    $c .= ui_groupgrid_sqltype($params);
    $c .= "</div>";

  }
  else{
    $arr = ov('value', $params);
    $rowcaches = array();

    $c = "<div id='$id' class='grid'>";
    if(count($arr) > 0) $c .= ui_groupgridsect($arr[0], $params, $rowcaches);
    $c .= "</div>";

    $cache = array(
        'params'=>$params,
        'rowcaches'=>$rowcaches
    );
    cache_set($id, $cache);
  }


  return $c;
}

?>