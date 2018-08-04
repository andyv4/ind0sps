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

  var currencyrate = parseFloat($("*[data-name='currencyrate']", '.modal').val());
  var subtotal = purchaseinvoice_subtotal();
  var discountamount = parseFloat($("*[data-name='discountamount']", '.modal').val());
  var taxamount = parseFloat($("*[data-name='taxamount']", '.modal').val());
  var freightcharge = parseFloat($("*[data-name='freightcharge']", '.modal').val());
  var pph = parseFloat($("*[data-name='pph']", '.modal').val());
  var kso = parseFloat($("*[data-name='kso']", '.modal').val());
  var ski = parseFloat($("*[data-name='ski']", '.modal').val());
  var clearance_fee = parseFloat($("*[data-name='clearance_fee']", '.modal').val());
  var handlingfeepaymentamount = parseFloat($("*[data-name='handlingfeepaymentamount']", '.modal').val());

  if(isNaN(currencyrate)) currencyrate = 1;
  if(isNaN(taxamount)) taxamount = 0;
  if(isNaN(freightcharge)) freightcharge = 0;
  if(isNaN(pph)) pph = 0;
  if(isNaN(kso)) kso = 0;
  if(isNaN(ski)) ski = 0;
  if(isNaN(clearance_fee)) clearance_fee = 0;
  if(isNaN(handlingfeepaymentamount)) handlingfeepaymentamount = 0;

  var total = subtotal - discountamount + freightcharge;
  $("*[data-name='total']", '.modal').val(total);

  subtotal = subtotal * currencyrate;
  discountamount = discountamount * currencyrate;
  freightcharge = freightcharge * currencyrate;
  total = subtotal - discountamount + freightcharge;

  var subtotal_after_discount = subtotal - discountamount;

  var discount_percentage = discountamount / subtotal;
  var tax_percentage = (freightcharge + taxamount + pph + kso + ski + clearance_fee + handlingfeepaymentamount) / subtotal_after_discount;

  /*console.log([
    taxamount,
    freightcharge,
    pph,
    kso,
    ski,
    clearance_fee,
    handlingfeepaymentamount,
    subtotal_after_discount,
    tax_percentage,
    discount_percentage
  ]);*/

  var total_unittax = 0;
  $('#inventories tr').each(function(){

    if(this.classList.contains('newrowopt')) return;

    var qty = $("*[data-name='qty']", this).val();
    var unittotal = $("*[data-name='unittotal']", this).val();
    var unittax = $("*[data-name='unittax']", this).val();
    var unitcostpriceflag = $("*[data-name='unitcostpriceflag']", this).val();

    if(unitcostpriceflag) return; // Skip manual cost price row

    if(isNaN(qty)) qty = 0;
    if(isNaN(unittotal)) unittotal = 0;
    if(isNaN(unittax)) unittax = 0;

    unittax_per_unit = unittax / qty;
    var unitprice = unittotal / qty;
    var unitcostprice = unitprice - (discount_percentage * unitprice);
    unitcostprice = unitcostprice + (tax_percentage * unitcostprice);
    unitcostprice = isNaN(unitcostprice) ? 0 : unitcostprice;
    unitcostprice = Math.round(unitcostprice * currencyrate) + unittax_per_unit;
    $("*[data-name='unitcostprice']", this).val(unitcostprice);

    total_unittax += unittax > 0 ? unittax : 0;

  });
  $("*[data-name='import_cost']", '.modal').val(total_unittax);

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

function purchaseinvoice_unitcostprice_changed(textbox){

  ui.checkbox_setvalue(textbox.previousElementSibling, true);

}

function purchaseinvoice_unitcostpriceflag_changed(checkbox){

  if(ui.checkbox_value(checkbox) > 0){
    ui.textbox_setvalue(checkbox.nextElementSibling, '');
    $("input", checkbox.nextElementSibling).select();
  }
  else
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

  var total = purchaseinvoice_total();

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