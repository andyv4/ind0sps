function ui_warehousetransferdetail_col0_prehint(){

  return [ ui.control_value(ui("%fromwarehouseid")), ui.control_value(ui('%date', ui('.modal'))) ];

}

function ui_warehousetransferdetail_col0_posthint(obj, el){

  var tr = el.parentNode.parentNode;
  var current_qty = ui("%current_qty", tr);
  var code = ui("%inventorycode", tr);
  ui.label_setvalue(current_qty, obj['current_qty'] == null ? 0 : obj['current_qty']);
  ui.label_setvalue(code, obj['code'] == null ? '' : obj['code']);

}

function ui_warehousetransferdetail_currentqty_click(el){

  var tr = el.parentNode.parentNode.parentNode;
  var qty = parseFloat(el.innerHTML);
  if(!isNaN(qty))
    ui.control_setvalue(ui('%qty', tr), el.innerHTML);

}