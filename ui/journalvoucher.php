<?php

require_once 'api/journalvoucher.php';

function ui_journalvoucherdetail($id, $mode = 'read'){

  $obj = journalvoucherdetail(null, array('id'=>$id));

  if($mode != 'read' && $obj && !privilege_get('journalvoucher', 'modify')) $mode = 'read';
  if($mode == 'read' && !$obj) throw new Exception('Jurnal dengan nomor ini tidak ada.');
  $readonly = $mode == 'write' ? 0 : 1;
  $closable = $readonly ? 1 : 0;
  $is_new = !$readonly && !$obj ? true : false;
  if($is_new && !privilege_get('journalvoucher', 'new')) exc("Anda tidak dapat membuat jurnal.");
  $module = m_loadstate();
  $date = ov('date', $obj);
  $description = ov('description', $obj);

  $is_new = !$obj && $mode == 'write' ? true : false;
  $date = $is_new ? date('Ymd') : $date;

  $controls = array(
    'id'=>array('type'=>'hidden', 'name'=>'id', 'value'=>ov('id', $obj)),
    'date'=>array('type'=>'datepicker', 'name'=>'date', 'value'=>$date, 'width'=>100, 'readonly'=>$readonly),
    'description'=>array('type'=>'textarea', 'name'=>'description', 'value'=>$description, 'width'=>300, 'height'=>60, 'readonly'=>$readonly),
    'amount'=>array('type'=>'textbox', 'name'=>'amount', 'value'=>ov('amount', $obj), 'width'=>100, 'datatype'=>'money', 'align'=>'left', 'readonly'=>1),
  );

  // Action Controls
  $actions = array();
  if($readonly && $obj && privilege_get('journalvoucher', 'modify')) $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_journalvoucherdetail', [ $id, 'write' ], { waitel:this })\"><span class='fa fa-save'></span><label>" . lang('001') . "</label></button></td>";
  if(!$readonly && !$obj && privilege_get('journalvoucher', 'new')) $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_journalvouchersave', [ ui.container_value(ui('.modal')) ], { waitel:this })\"><span class='fa fa-edit'></span><label>" . lang('002') . "</label></button></td>";
  if(!$readonly && $obj && privilege_get('journalvoucher', 'modify')) $actions[] = "<td><button class='blue' onclick=\"ui.async('ui_journalvouchersave', [ ui.container_value(ui('.modal')) ], { waitel:this })\"><span class='fa fa-edit'></span><label>" . lang('002') . "</label></button></td>";
  $actions[] = "<td><button class='hollow' onclick=\"ui.modal_close(ui('.modal'))\"><span class='fa fa-times'></span><label>Close</label></button></td>";

  $c = "<element exp='.modal'>";
  $c .= "
    <div class='scrollable padding1020'>
      <table class='form'>
        " . ui_control($controls['id']) . "
        " . ui_formrow(lang('jv01'), ui_control($controls['date'])) . "
        " . ui_formrow(lang('jv02'), ui_control($controls['description'])) . "
        " . ui_formrow(lang('jv03'), ui_control($controls['amount'])) . "
      </table>
      <div>
        " . ui_gridhead(array('columns'=>$module['detailcolumns'])) . "
        " . ui_grid(array('columns'=>$module['detailcolumns'], 'name'=>'details', 'value'=>ov('details', $obj), 'mode'=>'write', 'readonly'=>$readonly, 'id'=>'jvdetails', 'nodittomark'=>1)) . "
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
		ui.loadscript('rcfx/js/journalvoucher.js', \"ui.modal_open(ui('.modal'), { closeable:$closable, width:800, autoheight:true });\");
	</script>
	";
  return $c;

}

function ui_journalvoucherdetail_columnresize($name, $width){

  $module = m_loadstate();
  $preset = $module['detailpresets'][$module['detailpresetidx']];

  for($i = 0 ; $i < count($preset['columns']) ; $i++){
    if($preset['columns'][$i]['name'] == $name){
      $preset['columns'][$i]['width'] = $width;
    }
  }

  $module['detailpresets'][$module['detailpresetidx']] = $preset;
  m_savestate($module);

}

function ui_journalvoucherdetail_col0($obj, $params){

  return ui_autocomplete(array(
    'name'=>'coaid', 
    'src'=>'ui_journalvoucherdetail_col0_completion', 
    'text'=>ov('coaname', $obj), 
    'value'=>ov('coaid', $obj),
    'readonly'=>$params['readonly'], 
    'width'=>'99%'
  ));

}

function ui_journalvoucherdetail_col0_completion($param){

  $hint = ov('hint', $param);
  $items = chartofaccountlist(null, null, array(array('name'=>'name', 'operator'=>'contains', 'value'=>$hint)));
  $items = array_cast($items, array('text'=>'name', 'value'=>'id'));
  return $items;

}

function ui_journalvoucherdetail_col1($obj, $params){

  return ui_textbox(array(
    'class'=>'block',
    'name'=>'debitamount',
    'value'=>ov('debitamount', $obj),
    'readonly'=>$params['readonly'],
    'onchange'=>'journalvoucherdetail_debitamountchange(value, this)',
    'datatype'=>'money'
  ));

}

function ui_journalvoucherdetail_col2($obj, $params){

  return ui_textbox(array(
    'class'=>'block',
    'name'=>'creditamount',
    'value'=>ov('creditamount', $obj),
    'readonly'=>$params['readonly'],
    'onchange'=>'journalvoucherdetail_creditamountchange(value, this)',
    'datatype'=>'money'
  ));

}

function ui_journalvoucherdetail_col3($obj, $params){

  if(!$params['readonly'])
    return "<div class='align-center'><span class='fa fa-times-circle color-red' onclick=\"ui.grid_remove(this.parentNode.parentNode.parentNode)\"></span></div>";
  return '';

}

function ui_journalvouchersave($obj){

  isset($obj['id']) && intval($obj['id']) > 0 ? journalvouchermodify($obj, true) : journalvoucherentry($obj, true);
  return m_load() . uijs("ui.modal_close(ui('.modal'))");

}

function ui_journalvoucherremove($id){

  journalvoucherremove(array('id'=>$id), true);
  return m_load();

}

function ui_journalvoucherexport(){

  global $module;
  $preset = $module['presets'][$module['presetidx']];
  $columns = $preset['columns'];
  $sorts = $preset['sorts'];
  $quickfilters = ov('quickfilters', $preset);
  $filters = $preset['filters'];
  $filters = m_quickfilter_to_filters($filters, $quickfilters);

  $journalvoucher_columnaliases = array(
    'id'=>'t1.id',
    'journaltype'=>'t1.type',
    'date'=>'t1.date',
    'description'=>'t1.description',
    'ref'=>'t1.ref',
    'refid'=>'t1.refid',
    'amount'=>'t1.amount',
    'coaid'=>'t2.coaid',
    'coaname'=>'t3.name',
    'debit'=>'t2.debit',
    'credit'=>'t2.credit',
    'type'=>'t2.type',
    'createdon'=>'t1.createdon'
  );

  $params = array();
  $columnquery = columnquery_from_columnaliases($columns, $journalvoucher_columnaliases);
  $wherequery = 'WHERE t1.id = t2.jvid AND t2.coaid = t3.id ' . str_replace('WHERE', 'AND', wherequery_from_filters($params, $filters, $journalvoucher_columnaliases));
  $sortquery = sortquery_from_sorts($sorts, $journalvoucher_columnaliases);

  if(strlen($columnquery) > 0) $columnquery = ', ' . $columnquery;

  $query = "SELECT 'journalvoucher' as `type`, t1.id $columnquery
    FROM journalvoucher t1, journalvoucherdetail t2, chartofaccount t3 $wherequery $sortquery";
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

  $filepath = 'usr/journal-voucher-' . date('j-M-Y') . '.xlsx';
  array_to_excel($items, $filepath);

  echo uijs("ui('#downloader').href = '$filepath';ui('#downloader').click();");

}

?>