<?php

function ui_chartofaccountdetail($id, $mode = 'read'){

  // Inventory Object
  $chartofaccount = chartofaccountdetail(null, array('id'=>$id));

  if($mode == 'write' && $chartofaccount && !privilege_get('chartofaccount', 'modify')) $mode = 'read';

  // Controls
  $readonly = $mode == 'write' ? 0 : 1;
  $closable = $readonly ? 1 : 0;
  $is_new = !$readonly && !$chartofaccount ? true : false;
  if($is_new && !privilege_get('chartofaccount', 'new')) exc("Anda tidak dapat membuat akun.");
  $controls = array(
    'id'=>array('type'=>'hidden', 'name'=>'id', 'value'=>$chartofaccount['id']),
    'code'=>array('type'=>'textbox', 'name'=>'code','width'=>'100px', 'value'=>ov('code', $chartofaccount), 'readonly'=>$readonly),
    'name'=>array('type'=>'textbox', 'name'=>'name','width'=>'300px', 'value'=>ov('name', $chartofaccount), 'readonly'=>$readonly),
    'type'=>array('type'=>'dropdown', 'name'=>'type','width'=>'150px', 'value'=>ov('type', $chartofaccount), 'items'=>chartofaccount_type(), 'readonly'=>$readonly),
    'currencyid'=>array('type'=>'dropdown', 'readonly'=>$readonly, 'name'=>'currencyid','width'=>'120px','items'=>array_cast(currencylist(), array('text'=>'name', 'value'=>'id')), 'width'=>'160px', 'value'=>ov('currencyid', $chartofaccount)),
    'accounttype'=>array('type'=>'dropdown', 'name'=>'accounttype','width'=>'120px','items'=>chartofaccount_accounttype(), 'width'=>'150px', 'value'=>ov('accounttype', $chartofaccount), 'readonly'=>$readonly),
    'amount'=>array('type'=>'textbox', 'name'=>'amount','width'=>'150px', 'align'=>'left', 'readonly'=>1, 'value'=>ov('amount', $chartofaccount), 'readonly'=>1),
  );

  global $module;
  $preset = $module['mutationdetailpresets'][$module['mutationdetailpresetidx']];
  $columns = $preset['columns'];
  foreach($columns as $index=>$column)
    if($columns[$index]['name'] == 'id'){
      $preset['columns'][$index]['active'] = 1;
      //$preset['columns'][$index]['width'] = 60;
    }
  $quickfiltervalue = array();
  $filters = array();
  $columns_indexed = array_index($columns, array('name'), 1);
  if(isset($preset['mutationdetailquickfilters']) && is_array($preset['mutationdetailquickfilters']))
    for($i = 0 ; $i < count($preset['mutationdetailquickfilters']) ; $i++){
      $filter = $preset['mutationdetailquickfilters'][$i];
      $filtername = $filter['name'];
      $filtervalue = $filter['value'];

      $filters[] = array('name'=>$filtername, 'operator'=>'contains', 'value'=>$filtervalue, 'type'=>'text');
      $quickfiltervalue[] = array('text'=>$columns_indexed[$filtername]['text'] . ' : ' . $filtervalue, 'value'=>json_encode(array('name'=>$filtername, 'value'=>$filtervalue)));
    }
  if(isset($preset['sorts']) && is_array($preset['sorts']) && count($preset['sorts']) > 0){
    $preset['sorts'][] = array('name'=>'id', 'sorttype'=>$preset['sorts'][count($preset['sorts']) - 1]['sorttype']); // Sort by id if same sort value exists
    data_sort($items, $preset['sorts']);
  }

  // Action Controls
  $actions = array();
  if($readonly && privilege_get('chartofaccount', 'modify')) $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_chartofaccountdetail', [ $id, 'write' ], { waitel:this })\"><span class='fa fa-edit'></span><label>Ubah</label></button></td>";
  if(!$readonly && $chartofaccount && privilege_get('chartofaccount', 'modify')) $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_chartofaccountdetail_save', [ ui.container_value(ui('.modal')) ], { waitel:this })\"><span class='fa fa-save'></span><label>Simpan</label></button></td>";
  if(!$readonly && !$chartofaccount && privilege_get('chartofaccount', 'new')) $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_chartofaccountdetail_save', [ ui.container_value(ui('.modal')) ], { waitel:this })\"><span class='fa fa-save'></span><label>Simpan</label></button></td>";

  // UI HTML
  $c = "<element exp='.modal'>";
  $c .= "
  <div class='padding10 align-center'>
    <div class='tabhead' data-tabbody='.tabbody'>
      <div class='tabitem active' onclick='ui.tabclick(event, this);chartofaccountdetail_tabclick(0, $id)'><label>Detil Akun</label></div>
      <div class='tabitem' onclick='ui.tabclick(event, this);chartofaccountdetail_tabclick(1, $id)'><label>Mutasi</label></div>
      <!--<div class='tabitem' onclick='ui.tabclick(event, this);chartofaccountdetail_tabclick(2, $id)'><label>Histori</label></div>-->
    </div>
  </div>
  <div id='scrollable8'' class='scrollable padding10'>
    <div class='tabbody'>
      <!-- Tab-1 -->
      <div class='tab'>
        " . ui_control($controls['id']) . "
        <table class='form' id='chartofaccountdetailform1'>
            " . ui_control($controls['id']) . "
            <tr><th><label>" . lang('coa06') . "</label></th><td>" . ui_control($controls['code']) . "</td></tr>
            <tr><th><label>" . lang('coa07') . "</label></th><td>" . ui_control($controls['name']) . "</td></tr>
            <tr><th><label>" . lang('coa08') . "</label></th><td>" . ui_control($controls['type']) . "</td></tr>
            <tr><th><label>" . lang('coa09') . "</label></th><td>" . ui_control($controls['accounttype']) . "</td></tr>
            <tr><th><label>" . lang('coa11') . "</label></th><td>" . ui_control($controls['currencyid']) . "</td></tr>
            <tr><th><label>" . lang('coa10') . "</label></th><td>" . ui_control($controls['amount']) . "</td></tr>
          </table>
      </div>
      <!-- Tab-2 -->
      <div class='tab off mutationdetail'>
        <div id='static9'>
          <table cellspacing='4' style='width: 100%'>
            <tr>
              <td><button class='hollow' onclick=\"ui . async('ui_chartofaccountdetail_mutationdetail_export', [$id])\"><span class='mdi mdi-download'></span><label>Download</label></button></td>
              <td>" . ui_datepicker(array('name'=>'startdate', 'value'=>$module['mutationdetailstartdate'], 'onchange'=>"ui.async('ui_chartofaccountdetail_mutationdetail_startdatechange', [ $id, value ], { waitel:this })")) . "</td>
              <td>" . ui_datepicker(array('name'=>'enddate', 'value'=>$module['mutationdetailenddate'], 'onchange'=>"ui.async('ui_chartofaccountdetail_mutationdetail_enddatechange', [ $id, value ], { waitel:this })")) . "</td>
              <td style='width:100%'>" . ui_multicomplete(array('width'=>'100%', 'name'=>'search', 'src'=>'ui_chartofaccountdetail_mutationdetail_quickfilter', 'placeholder'=>'Quick filter...', 'value'=>$quickfiltervalue, 'separator'=>'|', 'onchange'=>"ui.async('ui_chartofaccountdetail_mutationdetail_quickfilterapply', [ ui.multicomplete_value(this) ], {})")) . "</td>
            </tr>
          </table>
          " . ui_gridhead(array('columns'=>$preset['columns'], 'gridexp'=>'#mutationdetailgrid',
              'oncolumnresize'=>"ui_chartofaccountdetail_mutationdetail_columnresize",
              'oncolumnclick'=>"ui_chartofaccountdetail_mutationdetail_sortapply",
              'oncolumnapply'=>'ui_chartofaccountdetail_mutationdetail_columnapply')) . "
        </div>
        <div id='scrollable9' class='scrollable'>
        </div>
      </div>
      <!-- Tab-3 -->
      <div class='tab off'>

      </div>
    </div>
  </div>
  <div class='foot'>
    <table cellspacing='5'>
      <tr>
        <td style='width: 100%'></td>
        " . implode(', ', $actions) . "
        <td><button class='hollow' onclick=\"ui.modal_close(ui('.modal'))\"><span class='fa fa-times'></span><label>Tutup</label></button></td>
    </table>
  </div>
  ";
  $c .= "</element>";
  $c .= uijs("
    ui.loadscript('rcfx/js/chartofaccount.js', \"ui.modal_open(ui('.modal'), { closeable:$closable, width:980, autoheight:1 })\");
  ");
  return $c;

}

function p11p($obj1, $obj2){

  return $obj1['p2id'] > $obj2['p2id'] ? -1 : 1;

}

function ui_chartofaccountdetail_mutationdetail($id){

  global $module;
  $preset = $module['mutationdetailpresets'][$module['mutationdetailpresetidx']];
  $columns = $preset['columns'];

  foreach($columns as $index=>$column)
    if($columns[$index]['name'] == 'id'){
      $preset['columns'][$index]['active'] = 1;
      //$preset['columns'][$index]['width'] = 60;
    }

  $sorts = $preset['sorts'];
  if(!is_array($sorts) || count($sorts) <= 0)
    $sorts[] = [ 'name'=>'id', 'sorttype'=>'desc' ];

  // Quick filter value
  $filters = [
    [ 'name'=>'coaid', 'operator'=>'=', 'value'=>$id ],
    [ 'name'=>'date', 'operator'=>'>=', 'value'=>$module['mutationdetailstartdate'] ],
    [ 'name'=>'date', 'operator'=>'<=', 'value'=>$module['mutationdetailenddate'] ],
  ];
  if(isset($preset['mutationdetailquickfilters']) && is_array($preset['mutationdetailquickfilters']))
    for($i = 0 ; $i < count($preset['mutationdetailquickfilters']) ; $i++){
      $filter = $preset['mutationdetailquickfilters'][$i];
      $filtername = $filter['name'];
      $filtervalue = $filter['value'];

      $filters[] = array('name'=>$filtername, 'operator'=>'contains', 'value'=>$filtervalue, 'type'=>'text');
    }

  $items = chartofaccountmutation($filters, $sorts);

  $c = "<element exp='#scrollable9'>";
  $c .= ui_grid(array('id'=>'mutationdetailgrid', 'columns'=>$preset['columns'], 'value'=>$items, 'scrollel'=>'#scrollable9'));
  $c .= "</element>";
  $c .= uijs("
    chartofaccountmutationdetail_resize();
  ");

  $module['mutationdetailid'] = $id;
  m_savestate($module);

  echo $c;

}

function ui_chartofaccountdetail_mutationdetail_startdatechange($id, $startdate){

  global $module;
  $module['mutationdetailstartdate'] = $startdate;
  m_savestate($module);
  return ui_chartofaccountdetail_mutationdetail($id);

}

function ui_chartofaccountdetail_mutationdetail_enddatechange($id, $enddate){

  global $module;
  $module['mutationdetailenddate'] = $enddate;
  m_savestate($module);
  return ui_chartofaccountdetail_mutationdetail($id);

}

function ui_chartofaccountdetail_mutationdetail_columnresize($name, $width){

  global $module;
  $preset = $module['mutationdetailpresets'][$module['mutationdetailpresetidx']];

  for($i = 0 ; $i < count($preset['columns']) ; $i++){
    if($preset['columns'][$i]['name'] == $name){
      $preset['columns'][$i]['width'] = $width;
    }
  }

  $module['mutationdetailpresets'][$module['mutationdetailpresetidx']] = $preset;
  m_savestate($module);

}

function ui_chartofaccountdetail_mutationdetail_columnapply($columns){

  global $module;
  $columns = array_index($columns, array('name'), 1);
  $preset = $module['mutationdetailpresets'][$module['mutationdetailpresetidx']];

  for($i = 0 ; $i < count($preset['columns']) ; $i++){
    $name = $preset['columns'][$i]['name'];
    if(isset($columns[$name]))
      $preset['columns'][$i]['active'] = $columns[$name]['active'];
  }

  $module['mutationdetailpresets'][$module['mutationdetailpresetidx']] = $preset;
  m_savestate($module);

  return ui_chartofaccountdetail_mutationdetail($module['mutationdetailid']);

}

function ui_chartofaccountdetail_mutationdetail_sortapply($name){

  global $module;
  $preset = isset($module['mutationdetailpresets'][$module['mutationdetailpresetidx']]) ? $module['mutationdetailpresets'][$module['mutationdetailpresetidx']] : [];

  // If sort applied before is equal with this one, invert the sorttype
  if(isset($preset['sorts']) && count($preset['sorts']) >= 1 && $preset['sorts'][0]['name'] == $name){
    $preset['sorts'][0]['sorttype'] = $preset['sorts'][0]['sorttype'] == 'desc' ? 'asc' : 'desc';
  }
  else{
    $preset['sorts'] = [
      [
        'name'=>$name,
        'sorttype'=>'asc'
      ]
    ];
  }
  $module['mutationdetailpresets'][$module['mutationdetailpresetidx']] = $preset;

  return ui_chartofaccountdetail_mutationdetail($module['mutationdetailid']);

}

function ui_chartofaccountdetail_mutationdetail_quickfilter($param0){

  $hint = $param0['hint'];
  $module = m_loadstate();
  $presetname = $module['mutationdetailpresets'][$module['mutationdetailpresetidx']];
  $columns = $presetname['columns'];
  for($i = 0 ; $i < count($columns) ; $i++){
    $column = $columns[$i];

    if(empty($column['name']) || empty($column['text'])) continue;

    $columntext = $column['text'];
    $items[] = array('text'=>"$columntext : $hint", 'value'=>json_encode(array('name'=>$column['name'], 'value'=>$hint)));
  }
  return $items;

}

function ui_chartofaccountdetail_mutationdetail_quickfilterapply($exp){

  $quickfilters = array();
  $explodes = explode('|', $exp);
  for($i = 0 ; $i < count($explodes) ; $i++){
    $explode = $explodes[$i];
    $obj = objectToArray(json_decode($explode));
    if(is_array($obj)) $quickfilters[] = $obj;
  }

  global $module;
  $presetname = $module['mutationdetailpresets'][$module['mutationdetailpresetidx']];
  $presetname['mutationdetailquickfilters'] = $quickfilters;
  $module['mutationdetailpresets'][$module['mutationdetailpresetidx']] = $presetname;
  m_savestate($module);

  return ui_chartofaccountdetail_mutationdetail($module['mutationdetailid']);

}

function ui_chartofaccountdetail_mutationdetail_export($id){

  global $module;
  $preset = $module['mutationdetailpresets'][$module['mutationdetailpresetidx']];
  $columns = $preset['columns'];

  foreach($columns as $index=>$column)
    if($columns[$index]['name'] == 'id'){
      $preset['columns'][$index]['active'] = 1;
      //$preset['columns'][$index]['width'] = 60;
    }

  $sorts = $preset['sorts'];
  if(!is_array($sorts) || count($sorts) <= 0)
    $sorts[] = [ 'name'=>'id', 'sorttype'=>'desc' ];

  // Quick filter value
  $filters = [
    [ 'name'=>'coaid', 'operator'=>'=', 'value'=>$id ],
    [ 'name'=>'date', 'operator'=>'>=', 'value'=>$module['mutationdetailstartdate'] ],
    [ 'name'=>'date', 'operator'=>'<=', 'value'=>$module['mutationdetailenddate'] ],
  ];
  if(isset($preset['mutationdetailquickfilters']) && is_array($preset['mutationdetailquickfilters']))
    for($i = 0 ; $i < count($preset['mutationdetailquickfilters']) ; $i++){
      $filter = $preset['mutationdetailquickfilters'][$i];
      $filtername = $filter['name'];
      $filtervalue = $filter['value'];

      $filters[] = array('name'=>$filtername, 'operator'=>'contains', 'value'=>$filtervalue, 'type'=>'text');
    }

  $items = chartofaccountmutation($filters, $sorts);

  $filepath = 'usr/chartofaccountmutation.xls';
  array_to_excel($items, $filepath);

  echo uijs("ui('#downloader').href = '$filepath';ui('#downloader').click();");

}

function ui_chartofaccountdetail_save($obj){

  isset($obj['id']) && intval($obj['id']) > 0 ? chartofaccountmodify($obj) : chartofaccountentry($obj);
  return m_load() . uijs("ui.modal_close(ui('.modal'))");

}

function ui_chartofaccountdetail_remove($id){

  chartofaccountremove(array('id'=>$id));
  return m_load() . uijs("ui.modal_close(ui('.modal'))");

}

?>