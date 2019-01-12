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

  var payments = ui.container_value(ui('.payment-section'));

  var total = ui.label_value(ui('%total'));
  var total_payment_idr = 0;
  var total_payment = 0;
  for(var i = 0 ; i < 5 ; i++){

    var n_paymentamount = payments['paymentamount-' + i];
    var n_paymentcurrencyrate = payments['paymentcurrencyrate-' + i];
    var n_paymentdate = payments['paymentdate-' + i];
    var n_paymentaccountid = payments['paymentaccountid-' + i];

    if(/^\d{8}$/.test(n_paymentdate) && parseInt(n_paymentaccountid) > 0){
      total_payment += n_paymentamount;
      total_payment_idr += n_paymentamount * n_paymentcurrencyrate;
    }

  }

  if(total_payment > total){
    alert("Pembayaran melebihi total.");
    total_payment_idr = 0;
  }
  else
    ui.checkbox_setvalue(ui('%ispaid'), (total_payment >= total ? 1 : 0));

  ui.textbox_setvalue(ui('%paymentamount'), total_payment_idr);

}

function purchaseorder_paymentadd(){

  var completed = false;
  $('tr', '.payment-section').each(function(){

    if(completed) return;
    if($(this).hasClass('off')){
      $(this).removeClass('off');
      completed = true;
    }

  })
  purchaseorder_paymentamountchange();

}

function purchaseorder_paymentremove(button){

  var tr = $(button).closest('tr')[0];
  ui.textbox_setvalue(ui('.paymentamount', tr), 0);
  ui.textbox_setvalue(ui('.paymentcurrencyrate', tr), 0);
  ui.textbox_setvalue(ui('.paymentdate', tr), '');
  ui.textbox_setvalue(ui('.paymentaccountid', tr), '');
  $(tr).addClass('off');
  purchaseorder_paymentamountchange();

}

function purchaseorder_onremovecompleted(){

  ui.modal_close(ui('.modal'));

}