function inventorydetail_mutationdetail_onresize(name, width){
  ui.async('ui_inventorydetail_mutationdetail_columnresize', [ name, width ], {});
}
function inventorydetail_mutationdetail_resize(){

  var height = $('#id_scrollable').outerHeight() - $('#mutationdetail_row0').outerHeight() - $('#mutationdetail_gridhead').outerHeight() - 15;
  $('#scrollable9').css({ height:height });

}
function inventorydetail_costpricedetail_resize(){

  var height = $('#id_scrollable').outerHeight() - $('#costpricedetail_row0').outerHeight() - $('#costpricedetail_gridhead').outerHeight() - 15;
  $('#scrollable10').css({ height:height });
  console.log([ 'inventorydetail_costpricedetail_resize', height ]);

}
function inventorydetail_taxable_changed(name, value){

  console.log([ "value", value ]);
}
