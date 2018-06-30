<?php

require_once 'api/currency.php';

function ui_currencydetail($id = null, $mode = 'read'){

  $currency = currencydetail(null, array('id'=>$id));

  if($mode != 'read' && $currency && !privilege_get('currency', 'modify')) $mode = 'read';
  if($mode == 'read' && !$currency) throw new Exception('Mata uang dengan nomor ini tidak ada.');

  $readonly = $mode == 'write' ? 0 : 1;
  $is_new = !$readonly && !$currency ? true : false;
  if($is_new && !privilege_get('currency', 'new')) exc("Anda tidak dapat membuat mata uang.");

  $modal = array(
      'controls'=>array(
          array('type'=>'hidden', 'name'=>'id', 'value'=>ov('id', $currency)),
          array('type'=>'textbox', 'name'=>'code', 'label'=>'Kode', 'width'=>100, 'value'=>ov('code', $currency), 'maxlength'=>3),
          array('type'=>'textbox', 'name'=>'name', 'label'=>'Nama Mata Uang', 'width'=>300, 'value'=>ov('name', $currency))
      ),
      'mode'=>$mode,
      'height'=>300,
      'script'=>'rcfx/js/currency.js'
  );

  if(privilege_get('currency', 'new'))
    $modal['onsave'] = "ui.async('ui_currencysave', [ ui.container_value(ui('.modal')) ])";

  if(privilege_get('currency', 'modify'))
    $modal['onmodify'] = "ui.async('ui_currencydetail', [ $id, 'write' ])";

  return ui_detailmodal($modal);

}

function ui_currencysave($obj){

  if(intval($obj['id']) > 0) currencymodify($obj);
  else currencyentry($obj);
  return m_load() . ui_modalclose();

}

function ui_currencyremove($id){

  currencyremove(array('id'=>$id));
  return m_load();

}

?>