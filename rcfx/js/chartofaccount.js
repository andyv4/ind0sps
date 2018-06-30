function chartofaccountdetail_historyclick(){

  var modal = ui('.modal');
  var historytab = ui('.historytab', modal);




}

function chartofaccountdetail_tabclick(index, id){

  if(index == 1){
    ui.async('ui_chartofaccountdetail_mutationdetail', [ id ], {});
  }

}

function chartofaccountmutationdetail_resize(){

  ui('#scrollable9').style.height = ui('#scrollable8').clientHeight - ui('#static9').clientHeight - 15;

}