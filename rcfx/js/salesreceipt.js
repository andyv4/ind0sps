function salesreceipt_invoicegrouphint(value, el){

  // Retrieve exception
  var grid = ui('#items');
  var tbody = ui('tbody', grid);
  for(var i = 0 ; i < tbody.children.length ; i++){
    var tr = tbody.children[i];
    var obj = ui.container_value(tr, 1);
    if(ui.isemptyarr(obj)) continue;
  }

  ui.async('ui_salesreceiptdetail_groupinvoice_addbycustomerdescription', [ value ], { waitel:el });
  active_tr = el.parentNode.parentNode;

}

function salesinvoicereceipt_setitemrow(item){

  ui.container_setvalue(active_tr, item);
  salesreceipttotal();

}

function salesreceipttotal(){

  var grid = ui('%items');
  var tbody = ui('tbody', grid);
  var total = 0;
  for(var i = 0 ; i < tbody.children.length ; i++){
    var tr = tbody.children[i];
    var obj = ui.container_value(tr, 1);
    if(ui.isemptyarr(obj)) continue;
    total += parseFloat(obj['total']);
  }

  var modal = ui('.modal');
  ui.label_setvalue(ui('%total', modal, 0, 1), total);

}

function salesreceiptpaymentamount(){

  var grid = ui('%items');
  var tbody = ui('tbody', grid);
  var paymentamount = 0;
  for(var i = 0 ; i < tbody.children.length ; i++){
    var tr = tbody.children[i];
    var obj = ui.container_value(tr, 1);
    if(ui.isemptyarr(obj)) continue;
    paymentamount += parseFloat(obj['paymentamount']);
  }

  ui.control_setvalue(ui('%paymentamount', ui('.modal'), 0, 1), paymentamount);

}

function salesreceiptitem_paidstatuschange(checked, el){

  var tr = el.parentNode.parentNode.parentNode;
  ui.control_setvalue(ui('%paymentamount', tr), checked ? ui.control_value(ui('%total', tr)) : 0);
  salesreceiptpaymentamount();

}

function salesreceiptdetail_onpaidchange(checked, el){

  var grid = ui('%items');
  var tbody = ui('tbody', grid);
  var paymentamount = 0;
  for(var i = 0 ; i < tbody.children.length ; i++){
    var tr = tbody.children[i];
    var obj = ui.container_value(tr, 1);
    if(ui.isemptyarr(obj)) continue;

    ui.control_setvalue(ui('%ispaid', tr), checked);
    salesreceiptitem_paidstatuschange(checked, ui('%ispaid', tr));
  }
  salesreceiptpaymentamount

}

function salesreceipt_selectorapply(){

  var selector1 = ui('#selector1');
  if(!selector1) return;
  var arr = ui.grid_value(ui('#selector1'));
  var checked_ids = [];
  if(arr instanceof Array)
    for(var i = 0 ; i < arr.length ; i++){
      var obj = arr[i];
      if(obj['checked']) checked_ids.push(obj['id']);
    }
  if(checked_ids.length > 0)
    ui.async('ui_salesreceipt_salesinvoicegroupselector_apply', [ checked_ids ], { callback:"ui.dialog_close()" });

}

function salesreceipt_open(param){

  ui.modal_open(ui('.modal'), param);
  salesreceipttotal();
  salesreceiptpaymentamount();

}