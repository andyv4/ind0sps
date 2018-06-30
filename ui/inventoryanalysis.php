<?php

function ui_inventoryanalysisgenerate(){

  inventoryanalysisgenerate();

  // UI HTML
  $c = "<element exp=''></element>";
  $c .= uijs("ui('#intro').style.display = 'none';m_load();");

  return $c;

}

function ui_inventoryanalysisexport(){

  global $module;
  $preset = $module['presets'][$module['presetidx']];
  $columns = $preset['columns'];
  $sorts = $preset['sorts'];
  $quickfilters = ov('quickfilters', $preset);
  $filters = $preset['filters'];
  $filters = m_quickfilter_to_filters($filters, $quickfilters);

  $items = inventoryanalysislist($columns, $sorts, $filters);

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

  $filepath = 'usr/inventory-analysis.xls';
  array_to_excel($items, $filepath);

  echo uijs("ui('#downloader').href = '$filepath';ui('#downloader').click();");

}

?>