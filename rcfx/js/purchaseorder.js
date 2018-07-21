function purchaseorder_supplierchange(value){

  ui.async('ui_purchaseorderdetail_suppliercompletion', [ value ], {});

}

function purchaseorder_rowtotal(tr){

  var obj = ui.container_value(tr);
  var qty = parseFloat(obj['qty']);
  var unitprice = parseFloat(obj['unitprice']);
  var total = qty * unitprice;
  var discount = obj['unitdiscount'];
  var discountamount = ui.discount_calc(discount, total);
  var total = total - discountamount;

  ui.label_setvalue(ui('%unittotal', tr), total);

  purchaseorder_total();
}

function purchaseorder_subtotal(){

  var subtotal = 0;
  var inventories = ui.grid_value(ui('%inventories', ui('.modal')));
  for(var i = 0 ; i < inventories.length ; i++){
    var unittotal = parseFloat(inventories[i]['unittotal']);
    if(isNaN(unittotal)) unittotal = 0;
    subtotal += unittotal;
  }

  ui.label_setvalue(ui('%subtotal', ui('.modal')), subtotal);
  return subtotal;

}

function purchaseorder_total(){

  var subtotal = purchaseorder_subtotal();
  var discountamount = parseFloat(ui.textbox_value(ui('%discountamount', ui('.modal'))));
  if(isNaN(discountamount)) discountamount = 0;
  var taxamount = parseFloat(ui.label_value(ui('%taxamount', ui('.modal'))));
  if(isNaN(taxamount)) taxamount = 0;
  var freightcharge = parseFloat(ui.textbox_value(ui('%freightcharge', ui('.modal'))));
  if(isNaN(freightcharge)) freightcharge = 0;
  var total = subtotal - discountamount + taxamount + freightcharge;

  ui.label_setvalue(ui('%total', ui('.modal')), total);

  return total;

}

function purchaseorder_discountchange(){

  var subtotal = purchaseorder_subtotal();
  var discount = ui.textbox_value(ui('%discount', ui('.modal')));
  var discountamount = ui.discount_calc(discount, subtotal);

  ui.textbox_setvalue(ui('%discountamount', ui('.modal')), discountamount);
  purchaseorder_taxchange();
  purchaseorder_total();

}

function purchaseorder_discountamountchange(){

  ui.textbox_setvalue(ui('%discount', ui('.modal')), '');
  purchaseorder_taxchange();
  purchaseorder_total();

}

function purchaseorder_taxchange(){

  var subtotal = purchaseorder_subtotal();
  var discountamount = parseFloat(ui.textbox_value(ui('%discountamount', ui('.modal'))));
  if(isNaN(discountamount)) discountamount = 0;
  var total = subtotal - discountamount;
  var taxable = ui.checkbox_value(ui('%taxable', ui('.modal')));
  var taxamount = taxable ? total * .1 : 0;

  ui.label_setvalue(ui('%taxamount', ui('.modal')), taxamount);
  purchaseorder_total();

}

function purchaseorder_ispaid(){

  var ispaid = ui.checkbox_value(ui('%ispaid', ui('.modal')));
  if(ispaid){
    purchaseorder_paymentamount();
    if($("*[data-name='paymentdate']").val() == '')
      $("*[data-name='paymentdate']").val(date('Ymd'));
  }
  else{
    $("*[data-name='paymentdate']").val('');
    $("*[data-name='paymentamount']").val(0);
  }

}

function purchaseorder_paymentamount(){

  var currencyrate = ui.control_value(ui('%currencyrate', ui('.modal')));
  var total = purchaseorder_total();
  total = total * currencyrate;
  var handlingfee = ui.control_value(ui('%handlingfeepaymentamount', ui('.modal')));
  if(isNaN(parseFloat(handlingfee)) || handlingfee <= 0) handlingfee = 0;
  total = total + handlingfee;
  ui.control_setvalue(ui('%paymentamount'), total);
  ui.control_setvalue(ui('%ispaid', ui('.modal'), 1));

}

function purchaseorder_onhandlingfeechange(){

  purchaseorder_paymentamount();

}

function purchaseorder_paymentamountchange(){

  var total = purchaseorder_total();
  var currencyrate = ui.control_value(ui('%currencyrate', ui('.modal')));
  total = total * currencyrate;
  var handlingfee = ui.control_value(ui('%handlingfeepaymentamount', ui('.modal')));
  if(isNaN(parseFloat(handlingfee)) || handlingfee <= 0) handlingfee = 0;
  total = total + handlingfee;

  var paymentamount = ui.control_value(ui('%paymentamount'), total);

  if(paymentamount > total){
    alert('Pelunasan tidak dapat lebih besar dari total order.');
    ui.control_setvalue(ui('%paymentamount'), total);
    paymentamount = total;
  }
  ui.control_setvalue(ui('%ispaid', ui('.modal')), paymentamount > 0 ? 1 : 0);

}

function purchaseorder_onremovecompleted(){

  ui.modal_close(ui('.modal'));

}