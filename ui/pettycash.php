<?php

require_once 'api/pettycash.php';

function ui_pettycashdetail($id, $mode = 'read'){

  $obj = pettycashdetail(null, array('id'=>$id));

  if($mode != 'read' && $obj && !privilege_get('pettycash', 'modify')) $mode = 'read';
  if($mode == 'read' && !$obj) throw new Exception('Kas kecil dengan nomor ini tidak ada.');
  $readonly = $mode == 'write' ? 0 : 1;
  $closable = $readonly ? 1 : 0;
  $is_new = !$readonly && !$obj ? true : false;
  if($is_new && !privilege_get('pettycash', 'new')) exc("Anda tidak dapat membuat kas kecil.");
  $module = m_loadstate();
  $date = ov('date', $obj);
  $code = ov('code', $obj);

  $is_new = !$obj && $mode == 'write' ? true : false;
  $date = $is_new ? date('Ymd') : $date;
  $code = $is_new ? pettycashcode() : $code;

  $controls = array(
    'id'=>array('type'=>'hidden', 'name'=>'id', 'value'=>ov('id', $obj)),
    'date'=>array('type'=>'datepicker', 'name'=>'date', 'value'=>$date, 'readonly'=>$readonly),
    'code'=>array('type'=>'textbox', 'name'=>'code', 'value'=>$code, 'width'=>100, 'readonly'=>$readonly),
    'description'=>array('type'=>'textarea', 'name'=>'description', 'value'=>ov('description', $obj), 'width'=>300, 'height'=>60, 'readonly'=>$readonly),
    'creditaccountid'=>array('type'=>'dropdown', 'name'=>'creditaccountid', 'value'=>ov('creditaccountid', $obj), 'width'=>150, 'items'=>array_cast(chartofaccountlist(), array('text'=>'name', 'value'=>'id')), 'readonly'=>$readonly),
    'total'=>array('type'=>'textbox', 'name'=>'total', 'value'=>ov('total', $obj), 'width'=>150, 'datatype'=>'money', 'align'=>'left', 'readonly'=>1),
  );

  // Action Controls
  $actions = array();
  if($readonly && $obj && privilege_get('pettycash', 'modify')) $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_pettycashdetail', [ $id, 'write' ], { waitel:this })\"><span class='fa fa-edit'></span><label>" . lang('001') . "</label></button></td>";
  if(!$readonly && !$obj && privilege_get('pettycash', 'new')) $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_pettycashsave', [ ui.container_value(ui('.modal')) ], { waitel:this })\"><span class='fa fa-edit'></span><label>" . lang('002') . "</label></button></td>";
  if(!$readonly && $obj && privilege_get('pettycash', 'modify')) $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_pettycashsave', [ ui.container_value(ui('.modal')) ], { waitel:this })\"><span class='fa fa-edit'></span><label>" . lang('002') . "</label></button></td>";
  $actions[] = "<td><button class='hollow' onclick=\"ui.modal_close(ui('.modal'))\"><span class='fa fa-times'></span><label>Close</label></button></td>";

  $c = "<element exp='.modal'>";
  $c .= "
    <div class='scrollable padding1020'>
      <table class='form'>
        " . ui_control($controls['id']) . "
        " . ui_formrow(lang('pe01'), ui_control($controls['code'])) . "
        " . ui_formrow(lang('pe05'), ui_control($controls['date'])) . "
        " . ui_formrow(lang('pe02'), ui_control($controls['description'])) . "
        " . ui_formrow(lang('pe03'), ui_control($controls['creditaccountid'])) . "
        " . ui_formrow(lang('pe04'), ui_control($controls['total'])) . "
      </table>
      <div>
        " . ui_gridhead(array('columns'=>$module['detailcolumns'], 'gridexp'=>'#pettycashdetails', 'oncolumnresize'=>"ui.async('ui_pettycashdetail_columnresize', [ name, width ], {})")) . "
        " . ui_grid(array('id'=>'pettycashdetails', 'name'=>'debitaccounts', 'class'=>'grid1', 'columns'=>$module['detailcolumns'], 'mode'=>'write', 'value'=>$obj['debitaccounts'], 'readonly'=>$readonly)) . "
      </div>
    </div>
    <div class='foot'>
      <table cellspacing='5'>
        <tr>
          <td style='width: 100%'></td>
          " . implode('', $actions) . "
        </tr>
      </table>
    </div>
	";
  $c .= "</element>";
  $c .= "
	<script>
		ui.modal_open(ui('.modal'), { closeable:$closable, width:800, autoheight:true });
	</script>
	";
  return $c;

}

function ui_pettycashdetailaccount_col0($obj, $params){

  return ui_dropdown(array(
      'name'=>'debitaccountid',
      'width'=>'99%',
      'datatype'=>'money',
      'readonly'=>$params['readonly'],
      'items'=>array_cast(chartofaccountlist(null, null, array(array('name'=>'accounttype', 'operator'=>'contains', 'value'=>'Expense'))), array('text'=>'name', 'value'=>'id')),
      'value'=>ov('debitaccountid', $obj),
      'onchange'=>"pettycashdetail_total()"
  ));

}

function ui_pettycashdetailaccount_col1($obj, $params){

  return ui_textbox(array(
      'name'=>'amount',
      'width'=>'100%',
      'datatype'=>'money',
      'value'=>ov('amount', $obj),
      'readonly'=>$params['readonly'],
      'onchange'=>"pettycashdetail_total()"
  ));

}

function ui_pettycashdetailaccount_col2($obj, $params){

  return ui_textbox(array('name'=>'remark', 'width'=>'100%', 'value'=>ov('remark', $obj), 'readonly'=>$params['readonly']));

}

function ui_pettycashdetailaccount_col3($obj, $params){

  if(!$params['readonly'])
    return "<div class='align-center'><span class='fa fa-times-circle color-red' onclick=\"ui.grid_remove(this.parentNode.parentNode.parentNode);pettycashdetail_total()\"></span></div>";
  return '';

}

function ui_pettycashdetail_columnresize($name, $width){

  $module = m_loadstate();
  $preset = $module['presets'][$module['presetidx']];

  for($i = 0 ; $i < count($preset['detailcolumns']) ; $i++){
    if($preset['detailcolumns'][$i]['name'] == $name){
      $preset['detailcolumns'][$i]['width'] = $width;
    }
  }

  $module['detailpresets'][$module['detailpresetidx']] = $preset;
  m_savestate($module);

}

function ui_pettycashsave($obj){

  isset($obj['id']) && intval($obj['id']) > 0 ? pettycashmodify($obj) : pettycashentry($obj);
  return m_load() . uijs("ui.modal_close(ui('.modal'))");

}

function ui_pettycashremove($id){

  pettycashremove(array('id'=>$id));
  return m_load();

}

function ui_pettycashexport(){

  global $module;
  $preset = $module['presets'][$module['presetidx']];
  $columns = $preset['columns'];
  $sorts = $preset['sorts'];
  $quickfilters = ov('quickfilters', $preset);
  $filters = $preset['filters'];
  $filters = m_quickfilter_to_filters($filters, $quickfilters);

  $pettycash_columnaliases = array(
    'id'=>'t1.id',
    'code'=>'t1.code',
    'date'=>'t1.date',
    'description'=>'t1.description',
    'total'=>'t1.total',
    'creditaccountname'=>'t3.name as creditaccountname',
    'debitaccountname'=>'t4.name as debitaccountname',
    'debitamount'=>'t2.amount as debitamount',
    'createdon'=>'t1.createdon'
  );

  $params = array();
  $columnquery = columnquery_from_columnaliases($columns, $pettycash_columnaliases);
  $wherequery = 'WHERE t1.id = t2.pettycashid and t1.creditaccountid = t3.id and t2.debitaccountid = t4.id' .
    str_replace('WHERE', 'AND', wherequery_from_filters($params, $filters, $pettycash_columnaliases));
  $sortquery = sortquery_from_sorts($sorts, $pettycash_columnaliases);
  if(strlen($columnquery) > 0) $columnquery = ', ' . $columnquery;
  $query = "SELECT 'pettycash' as `type`, t1.id $columnquery FROM pettycash t1, pettycashdebitaccount t2, chartofaccount t3, chartofaccount t4 $wherequery $sortquery";

  $items = pmrs($query, $params);

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

  $filepath = 'usr/petty-cash-' . date('j-M-Y') . '.xlsx';
  array_to_excel($items, $filepath);

  echo uijs("ui('#downloader').href = '$filepath';ui('#downloader').click();");

}

?>