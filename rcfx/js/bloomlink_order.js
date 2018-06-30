function bloomlink_order_detail_init(){

  var modal = ui('.modal');
  ui('#closebtn', modal).addEventListener('click', function(){
    ui.modal_close(ui('.modal'));
  });
  ui('.scrollable', modal).style.width = '500px';
  ui('.scrollable', modal).style.height = (window.innerHeight * .6) + "px";

}

function bloomlink_order_detail_reject(id){

  var message = prompt("Please fill the reject reason: ");
  if(message.length > 0){
    ui.async('ui_reject', [ id, message ], { waitel:ui('#rejectbtn') });
  }
  else{
    alert('Message cannot be empty');
  }

}

function bloomlink_order_detail_canceldeny(id){

  var message = prompt("Please fill the deny reason: ");
  if(message.length > 0){
    ui.async('ui_canceldeny', [ id, message ], { waitel:ui('#canceldenybtn') });
  }
  else{
    alert('Message cannot be empty');
  }

}

function bloomlink_order_detail_cancelconfirm(id){

  var message = prompt("Please fill the confirm message: ");
  if(message.length > 0){
    ui.async('ui_cancelconfirm', [ id, message ], { waitel:ui('#cancelconfirmbtn') });
  }
  else{
    alert('Message cannot be empty');
  }

}

ui.bloomlink_order_productlist_value = function(el){

  var products = [];

  for(var i = 0 ; i < el.children.length ; i++){
    var iel = el.children[i];

    var obj = ui.container_value(iel);
    products.push(obj);
  }

  console.log(products);

  return products;

}