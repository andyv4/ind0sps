function purchaseorder_supplierchange(value){

  ui.async('ui_purchaseorderdetail_suppliercompletion', [ value ], {});

}

function purchaseorder_rowtotal(tr){

  var obj = ui.container_value(tr);
  var qty = parseFloat(obj['qty']);
  var unitprice = parseFloat(obj['unitprice']);
  var total = qty * unitprice;

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
  var discountamount = parseFloat($("*[data-name='discountamount']", '.modal').val());
  var taxamount = parseFloat($("*[data-name='taxamount']", '.modal').val());
  var freightcharge = parseFloat($("*[data-name='freightcharge']", '.modal').val());

  if(isNaN(freightcharge)) freightcharge = 0;

  var total = subtotal - discountamount + freightcharge;
  $("*[data-name='total']", '.modal').val(total);

  var total_unittax = 0;
  $('#inventories tr').each(function(){

    if(this.classList.contains('newrowopt')) return;

    var unittax = $("*[data-name='unittax']", this).val();
    total_unittax += unittax > 0 ? unittax : 0;

  });
  $("*[data-name='import_cost']", '.modal').val(total_unittax);

  return total;

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
  purchaseorder_total();

}

function purchaseorder_discountamountchange(){

  ui.textbox_setvalue(ui('%discount', ui('.modal')), '');
  purchaseorder_total();

}

function purchaseorder_ispaid(){

  var ispaid = ui.checkbox_value(ui('%ispaid', ui('.modal')));
  if(ispaid){

    if($("*[data-name='paymentdate']").val() == '') $("*[data-name='paymentdate']").val(date('Ymd'));
    if(parseFloat($("*[data-name='paymentamount']").val()) == 0) $("*[data-name='paymentamount']").val(purchaseorder_paymentamount());
    $('.row-baddebt').show();

  }
  else{
    $("*[data-name='isbaddebt']").val(0);
    $("*[data-name='baddebtaccountid']").val('');
    $("*[data-name='baddebtdate']").val('');
    $("*[data-name='baddebtamount']").val(0);
    $("*[data-name='paymentamount']").val(0)
    $('.row-baddebt').hide();
  }

}
function purchaseorder_isbaddebt(){

  var isbaddebt = ui.checkbox_value(ui('%isbaddebt', ui('.modal')));
  if(isbaddebt){
    $("*[data-name='baddebtamount']").val($("*[data-name='paymentamount']").val());
    $("*[data-name='baddebtaccountid']").val(1000);
  }
  else{
    $("*[data-name='baddebtamount']").val(0);
    $("*[data-name='baddebtaccountid']").val(null);
  }

}

function purchaseorder_paymentamount(){

  var currencyrate = ui.control_value(ui('%currencyrate', ui('.modal')));
  var total = purchaseorder_total();
  total = total * currencyrate;
  return total;

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

}

function purchaseorder_onremovecompleted(){

  ui.modal_close(ui('.modal'));

}