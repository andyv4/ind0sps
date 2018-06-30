function pettycashdetail_total(){

  var grid = ui('#pettycashdetails');
  var tbody = grid.querySelector('tbody');
  var subtotal = 0;
  for(var i = 0 ; i < tbody.children.length ; i++){
    var tr = tbody.children[i];
    var obj = ui.container_value(tr);
    var amount = parseFloat(ui.ov('amount', obj));

    if(!isNaN(amount)) subtotal += amount;
  }

  ui.textbox_setvalue(ui('%total', ui('.modal')), subtotal);

}