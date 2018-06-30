function inventoryadjustment_inventorychange(value, el){

  var tr = el.parentNode.parentNode;
  ui.async('ui_inventoryadjustmentdetail_col0_completion2', [ value, ui.uiid(tr) ], {});

}

function inventoryadjustmentdetail_rowcalculate(){




}