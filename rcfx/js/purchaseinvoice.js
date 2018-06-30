function purchaseinvoice_supplierchange(value){

  ui.async('ui_purchaseinvoicedetail_suppliercompletion', [ value ], {});

}

function purchaseinvoice_rowtotal(tr){

  var qty = parseFloat(ui.textbox_value($("*[data-name='qty']", tr)[0]));
  var unitprice = ui.textbox_value($("*[data-name='unitprice']", tr)[0]);
  var total = qty * unitprice;
  //var discount = ui.textbox_value($("*[data-name='unitdiscount']", tr)[0]);
  //var discountamount = ui.discount_calc(discount, total);
  var total = total; // - discountamount;
  ui.label_setvalue(ui('%unittotal', tr), total);

  purchaseinvoice_discountchange();
  purchaseinvoice_taxchange();
  purchaseinvoice_total();
  if(!isNaN(total)) ui.grid_add(ui('#inventories'), 1);

}

function purchaseinvoice_subtotal(){

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

function purchaseinvoice_total(){

  var subtotal = purchaseinvoice_subtotal();
  var discountamount = parseFloat(ui.textbox_value(ui('%discountamount', ui('.modal'))));
  if(isNaN(discountamount)) discountamount = 0;
  var subtotal_after_discount = subtotal - discountamount;
  var taxamount = parseFloat(ui.control_value(ui('%taxamount', ui('.modal'))));
  if(isNaN(taxamount)) taxamount = 0;
  var freightcharge = parseFloat(ui.control_value(ui('%freightcharge', ui('.modal'))));
  if(isNaN(freightcharge)) freightcharge = 0;
  var pph = parseFloat(ui.control_value(ui('%pph', ui('.modal'))));
  if(isNaN(pph)) pph = 0;
  var kso = parseFloat(ui.control_value(ui('%kso', ui('.modal'))));
  if(isNaN(kso)) kso = 0;
  var ski = parseFloat(ui.control_value(ui('%ski', ui('.modal'))));
  if(isNaN(ski)) ski = 0;
  var clearance_fee = parseFloat(ui.control_value(ui('%clearance_fee', ui('.modal'))));
  if(isNaN(clearance_fee)) clearance_fee = 0;
  var total = subtotal - discountamount;

  ui.label_setvalue(ui('%total', ui('.modal')), total);

  var discount_percentage = discountamount / subtotal;
  var tax_percentage = (taxamount + freightcharge + pph + kso + ski + clearance_fee) / subtotal_after_discount;

  var currencyrate = ui.control_value(ui('%currencyrate', ui('.modal')));
  var total_unittax = 0;
  $('#inventories tr').each(function(){

    if(this.classList.contains('newrowopt')) return;

    var qty = ui.textbox_value(ui('%qty', this));
    var unittotal = ui.label_value(ui('%unittotal', this));
    var unittax = ui.textbox_value(ui('%unittax', this));
    var unitprice = unittotal / qty;
    var unitcostprice = unitprice - (discount_percentage * unitprice);
    unitcostprice = unitcostprice + (tax_percentage * unitcostprice);
    unitcostprice = isNaN(unitcostprice) ? 0 : unitcostprice;
    unitcostprice = Math.round(unitcostprice * currencyrate) + unittax;
    ui.textbox_setvalue(ui('%unitcostprice', this), unitcostprice);

    total_unittax += unittax;

  })

  ui.textbox_setvalue(ui('%import_cost'), total_unittax);

  return total;

}

function purchaseinvoice_discountchange(){

  var subtotal = purchaseinvoice_subtotal();
  var discount = ui.textbox_value(ui('%discount', ui('.modal')));
  if(discount > 0){
    var discountamount = ui.discount_calc(discount, subtotal);
    ui.textbox_setvalue(ui('%discountamount', ui('.modal')), discountamount);
  }
  purchaseinvoice_total();

}

function purchaseinvoice_discountamountchange(){

  ui.textbox_setvalue(ui('%discount', ui('.modal')), '');
  purchaseinvoice_total();

}

function purchaseinvoice_taxchange(){

  purchaseinvoice_total();

}

function purchaseinvoice_ispaid(){

  var ispaid = ui.checkbox_value(ui('%ispaid', ui('.modal')));
  if(ispaid)
    purchaseinvoice_paymentamount();
  else
    ui.textbox_setvalue(ui('%paymentamount'), 0);

}

function purchaseinvoice_paymentamount(){

  var currencyrate = ui.control_value(ui('%currencyrate', ui('.modal')));
  var total = purchaseinvoice_total();
  total = total * currencyrate;

  /*
  var handlingfee = ui.control_value(ui('%handlingfeepaymentamount', ui('.modal')));
  var purchaseorderhandlingpaymentamount = ui.control_value(ui('%purchaseorderhandlingpaymentamount', ui('.modal')));
  if(isNaN(parseFloat(handlingfee)) || handlingfee <= 0 || purchaseorderhandlingpaymentamount > 0) handlingfee = 0;
  */
  var handlingfee = 0;

  var downpaymentamount = ui.control_value(ui('%downpaymentamount', ui('.modal')));
  if(isNaN(parseFloat(downpaymentamount)) || downpaymentamount <= 0) downpaymentamount = 0;
  total = total + handlingfee - downpaymentamount;
  ui.control_setvalue(ui('%paymentamount'), total);
  ui.control_setvalue(ui('%ispaid', ui('.modal'), 1));
  return total;

}

function purchaseinvoicedetail_inventoryhistory(div){

  var tr = div.parentNode.parentNode;
  var inventoryid = ui.control_value(ui('%inventoryid', tr));
  if(parseInt(inventoryid) > 0)
    ui.async('ui_purchaseinvoicedetail_inventoryhistory', [ inventoryid ], { waitel:this });
  else
    ui.warn("[purchaseinvoicedetail_inventoryhistory] Undefined inventoryid.");
}

function purchaseinvoice_onhandlingfeechange(){

  //purchaseinvoice_paymentamount();

}

function purchaseinvoice_onremovecompleted(){

  ui.modal_close(ui('.modal'));

}

function purchaseinvoice_onpaymentamountchange(){

  var currencyrate = ui.control_value(ui('%currencyrate', ui('.modal')));
  var total = purchaseinvoice_total();
  total = total * currencyrate;
  var paymentamount = ui.control_value(ui('%paymentamount', ui('.modal')));

  /*
  var handlingfee = ui.control_value(ui('%handlingfeepaymentamount', ui('.modal')));
  var purchaseorderhandlingpaymentamount = ui.control_value(ui('%purchaseorderhandlingpaymentamount', ui('.modal')));
  if(isNaN(parseFloat(handlingfee)) || handlingfee <= 0 || purchaseorderhandlingpaymentamount > 0) handlingfee = 0;
  */
  var handlingfee = 0;

  var downpaymentamount = ui.control_value(ui('%downpaymentamount', ui('.modal')));
  if(isNaN(parseFloat(downpaymentamount)) || downpaymentamount <= 0) downpaymentamount = 0;

  if(paymentamount > 0 && paymentamount < handlingfee){
    alert('Pelunasan harus lebih besar dari handling fee.');
    ui.control_setvalue(ui('%paymentamount', ui('.modal')), handlingfee);
  }


}