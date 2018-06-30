function customerdetail_resize(){

  var customerdetailpane = ui('#customerdetailpane');
  if(customerdetailpane){
    var scrollable1 = ui('#customerdetailscrollable1', customerdetailpane);
    var scrollable2 = ui('#customerdetailscrollable2', customerdetailpane);
    var scrollable3 = ui('#customerdetailscrollable3', customerdetailpane);

    scrollable1.style.height = (window.innerHeight * .6) + "px";
    scrollable2.style.height = ((window.innerHeight * .6) - 84) + "px";
    scrollable3.style.height = ((window.innerHeight * .6) - 84) + "px";
  }

}

function customerdetail_pricetabclick(id, div, mode){

  ui.async('ui_customerdetail_pricedetail', [ id, mode ], {});

}

function customerdetail_salestabclick(id, div){

  ui.async('ui_customerdetail_salesdetail', [ id ], {});

}