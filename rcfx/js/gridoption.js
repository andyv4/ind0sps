ui_list = {};

ui_list.init = function(){
  ui_list.resize();
  ui.async('ui_load', [], { params:typeof qs['reset'] != 'undefined' ? { reset:1 } : {} });
  ui_list.contentbody_loadingmode();
  window.addEventListener('resize', ui_list.resize, true);
}

ui_list.resize = function(){
  var content = ui('.content');
  var contenthead = ui('.head', content);
  var contentbody = ui('.body', content);
  var sidebar = ui('.sidebar');
  var sidebarhead = ui('.head', sidebar);

  contentbody.style.marginTop = contenthead.clientHeight + "px";
  contentbody.style.height = (window.innerHeight - contenthead.clientHeight) + "px";
}

ui_list.contentbody_loadingmode = function(){
  ui('.contentbody').innerHTML = "<div class='spinner' style='padding-top:20px'><div class='bounce1'></div><div class='bounce2'></div><div class='bounce3'></div></div>";
}

ui_list.presetselect = function(index, el){
  ui.async('ui_presetselect', [ index ]);
  ui('.contentbody').innerHTML = "<div class='spinner' style='padding-top:20px'><div class='bounce1'></div><div class='bounce2'></div><div class='bounce3'></div></div>";
}
ui_list.presetoptionload = function(){
  ui_list.presetoptionpresetlistload();
  ui_list.presetoptionpresetdetailload();
}
ui_list.presetoptionpresetlistload = function(){
  var presets = report['presets'];
  var presetlist = ui('.presetlist');

  var menulist = ui('.menulist', presetlist);
  var c = "";
  for(var i = 0 ; i < presets.length ; i++){
    var preset = presets[i];
    var text = preset['text'];
    var active_class = i == report['presetidx'] ? ' active' : '';
    c += "<div class=\"menuitem" + active_class + "\">";
    c += "<label onclick=\"ui_list.presetoptionitemclick(this)\">" + text + "</label>";
    c += "</div>";
  }
  menulist.innerHTML = c;
}
ui_list.presetoptionitemclick = function(label){
  var menuitem = label.parentNode;
  var menulist = menuitem.parentNode;
  var idx = -1;
  for(var i = 0 ; i < menulist.children.length ; i++){
    if(menulist.children[i] == menuitem){
      idx = i;
      break;
    }
  }

  var active_menuitem = ui('.active', menulist);
  if(active_menuitem) active_menuitem.classList.remove('active');
  menuitem.classList.add('active');

  report['presetidx'] = idx;
  ui_list.presetoptionpresetdetailload();
}
ui_list.presetoptionpresetdetailload = function(){
  var presets = report['presets'];
  var presetidx = report['presetidx'];
  var preset = presets[presetidx];
  ui.textbox_setvalue(ui('%text', ui('.tabname')), preset['text']);
  ui_list.presetoptioncolumnload();
  ui_list.presetoptionsortload();
  ui_list.presetoptionfilterload();
  ui_list.presetoptiongroupload();
}
ui_list.presetoptioncopy = function(){

  // Get index
  var menulist = ui('.menulist', ui('.modal'));
  var idx = -1;
  for(var i = 0 ; i < menulist.children.length ; i++){
    if(menulist.children[i].classList.contains('active')){
      idx = i;
      break;
    }
  }

  // Copy preset
  var presets = report['presets'];
  var preset = presets[idx];
  var clonedpreset = {};
  for(var key in preset)
    clonedpreset[key] = preset[key];
  clonedpreset.text += " (Copy)";
  report['presets'].push(clonedpreset);

  report['presetidx'] = report['presets'].length - 1;
  ui_list.presetoptionpresetlistload();
  ui_list.presetoptionpresetdetailload();
}
ui_list.presetoptionremove = function(span){

  // Get index
  var menulist = ui('.menulist', ui('.modal'));
  var idx = -1;
  for(var i = 0 ; i < menulist.children.length ; i++){
    if(menulist.children[i].classList.contains('active')){
      idx = i;
      break;
    }
  }

  report['presets'].splice(idx, 1);
  report['presetidx'] = report['presets'].length - 1;
  ui_list.presetoptionpresetlistload();
  ui_list.presetoptionpresetdetailload();

}
ui_list.presetoptionmoveup = function(){

  // Get index
  var menulist = ui('.menulist', ui('.modal'));
  var idx = -1;
  for(var i = 0 ; i < menulist.children.length ; i++){
    if(menulist.children[i].classList.contains('active')){
      idx = i;
      break;
    }
  }

  if(idx != -1 && idx > 0){
    var temp = report['presets'][idx - 1];
    report['presets'][idx - 1] = report['presets'][idx];
    report['presets'][idx] = temp;
    menulist.insertBefore(menulist.children[idx], menulist.children[idx - 1]);
    report['presetidx'] = idx - 1;
  }

}
ui_list.presetoptionmovedown = function(){

  // Get index
  var menulist = ui('.menulist', ui('.modal'));
  var idx = -1;
  for(var i = 0 ; i < menulist.children.length ; i++){
    if(menulist.children[i].classList.contains('active')){
      idx = i;
      break;
    }
  }

  if(idx != -1 && idx < menulist.children.length - 1){
    var temp = report['presets'][idx + 1];
    report['presets'][idx + 1] = report['presets'][idx];
    report['presets'][idx] = temp;
    menulist.insertBefore(menulist.children[idx + 1], menulist.children[idx]);
    report['presetidx'] = idx + 1;
  }

}
ui_list.presetdownload = function(){

  // Get index
  var menulist = ui('.menulist', ui('.modal'));
  var idx = -1;
  for(var i = 0 ; i < menulist.children.length ; i++){
    if(menulist.children[i].classList.contains('active')){
      idx = i;
      break;
    }
  }

  if(idx == -1) return;

  ui_list.presetoptionsortsave();
  ui_list.presetoptionfiltersave();
  ui_list.presetoptiongroupsave();
  ui.async(report_ondownload, [ report, idx ], {  });

}
ui_list.presetupload = function(){

  ui('#uploader').addEventListener('change', ui_list.presetuploadstart, true);
  ui('#uploader').click();

}
ui_list.presetuploadstart = function(e){

  if(this.files.length == 1){
    ui.async('m_gridoption_upload', this.files[0], { type:"put" });
  }
  else
    alert('Only 1 file at one time for upload supported.');

}
ui_list.presetoptiontextchange = function(text){

  // Update data
  var presetidx = report['presetidx'];
  var presets = report['presets'];
  report['presets'][presetidx]['text'] = text;

  // Update menulist ui
  var presetlist = ui('.presetlist');
  var menulist = ui('.menulist', presetlist);
  ui('label', menulist.children[presetidx]).innerHTML = text;

}

ui_list.presetoptioncolumnload = function(){
  var presetidx = report['presetidx'];
  var presets = report['presets'];
  var preset = presets[presetidx];
  var columns = preset['columns'];
  var columnlist = ui('.columnlist');
  var scrollable = ui('.scrollable', columnlist);

  var c = '';
  for(var i = 0 ; i < columns.length ; i++){
    var column = columns[i];
    var name = column['name'];
    var text = ui.ov('text', column);
    var active = column['active'];
    var selected = i == preset['columnidx'] ? 1 : 0;

    if(text.length == 0) text = "&nbsp;";

    c += "<div class='columnitem" + (selected ? ' active' : '') + "' onclick=\"ui_list.presetoptioncolumnclick(event, this)\">" +
        "<input type='checkbox' " + (active ? 'checked' : '') + " onchange=\"ui_list.presetoptioncolumndetailchange('active', this.checked);ui_list.presetoptioncolumndetailload(" + i + ");event.preventDefault();event.stopPropagation();return false;\" />" +
        "<label>" + text + "</label>" +
        "</div>";
  }
  scrollable.innerHTML = c;

  ui_list.presetoptioncolumndetailload(0);
  report['presetidx'] = presetidx;
}
ui_list.presetoptioncolumnclick = function(e, div){
  var columnlist = ui('.columnlist');
  var scrollable = ui('.scrollable', columnlist);
  var idx = -1;
  for(var i = 0 ; i < scrollable.children.length ; i++)
    if(scrollable.children[i] == div){
      idx = i;
      break;
    }
  ui_list.presetoptioncolumndetailload(idx);
}
ui_list.presetoptioncolumndetailload = function(idx){
  var presets = report['presets'];
  var preset = presets[report['presetidx']];
  var columns = preset['columns'];
  var column = columns[idx];

  var columndetail = ui('.columndetail');

  ui.container_setvalue(columndetail, column);
  presets[report['presetidx']]['columnidx'] = idx;

  // Mark as active
  var columnlist = ui('.columnlist');
  var scrollable = ui('.scrollable', columnlist);
  var active_columnitem = ui('.active', scrollable);
  if(active_columnitem != null) active_columnitem.classList.remove('active');
  scrollable.children[idx].classList.add('active');
}
ui_list.presetoptioncolumndetailchange = function(name, value){
  var presets = report['presets'];
  var preset = presets[report['presetidx']];
  var columnidx = preset['columnidx'];
  report['presets'][report['presetidx']]['columns'][columnidx][name] = value;
}
ui_list.presetoptioncolumnmovedown = function(){
  var presetidx = report['presetidx'];
  var presets = report['presets'];
  var preset = presets[presetidx];
  var columns = preset['columns'];
  var columnlist = ui('.columnlist');
  var scrollable = ui('.scrollable', columnlist);

  var idx = -1;
  for(var i = 0 ; i < scrollable.children.length ; i++)
    if(scrollable.children[i].classList.contains('active')){
      idx = i;
      break;
    }
  if(idx != -1 && idx < scrollable.children.length - 1){
    var temp = report['presets'][presetidx]['columns'][idx + 1];
    report['presets'][presetidx]['columns'][idx + 1] = report['presets'][presetidx]['columns'][idx];
    report['presets'][presetidx]['columns'][idx] = temp;
    scrollable.insertBefore(scrollable.children[idx + 1], scrollable.children[idx]);
  }
}
ui_list.presetoptioncolumnmoveup = function(){
  var presetidx = report['presetidx'];
  var presets = report['presets'];
  var preset = presets[presetidx];
  var columns = preset['columns'];
  var columnlist = ui('.columnlist');
  var scrollable = ui('.scrollable', columnlist);

  var idx = -1;
  for(var i = 0 ; i < scrollable.children.length ; i++)
    if(scrollable.children[i].classList.contains('active')){
      idx = i;
      break;
    }
  if(idx != -1 && idx > 0){
    var temp = report['presets'][presetidx]['columns'][idx - 1];
    report['presets'][presetidx]['columns'][idx - 1] = report['presets'][presetidx]['columns'][idx];
    report['presets'][presetidx]['columns'][idx] = temp;
    scrollable.insertBefore(scrollable.children[idx], scrollable.children[idx - 1]);
  }
}
ui_list.presetoptioncolumnadd = function(){

  var columnname = prompt("Column name:")
  if(columnname.length > 0){

    var presetidx = report['presetidx'];
    var presets = report['presets'];
    var columnlist = ui('.columnlist');
    var scrollable = ui('.scrollable', columnlist);

    var idx = -1;
    for(var i = 0 ; i < scrollable.children.length ; i++)
      if(scrollable.children[i].classList.contains('active')){
        idx = i;
        break;
      }

    var name = columnname;
    var text = columnname;
    var active = 1;
    var i = idx + 1;

    report['presets'][presetidx]['columns'].splice(idx + 1, 0, {
      active: active,
      datatype: "text",
      name: name,
      text: text,
      width: 100
    });

    scrollable.insertAdjacentHTML('beforeend', "<div class='columnitem' onclick=\"ui_list.presetoptioncolumnclick(event, this)\">" +
        "<input type='checkbox' " + (active ? 'checked' : '') + " onchange=\"ui_list.presetoptioncolumndetailchange('active', this.checked);ui_list.presetoptioncolumndetailload(" + i + ");event.preventDefault();event.stopPropagation();return false;\" />" +
        "<label>" + text + "</label>" +
        "</div>");
    scrollable.insertBefore(scrollable.lastElementChild, scrollable.children[i]);

    ui_list.presetoptioncolumnclick(null, scrollable.lastElementChild);
    ui_list.presetoptioncolumndetailload(i);

  }

}
ui_list.presetoptioncolumnremove = function(){

  var presetidx = report['presetidx'];
  var presets = report['presets'];
  var preset = presets[presetidx];
  var columns = preset['columns'];
  var columnlist = ui('.columnlist');
  var scrollable = ui('.scrollable', columnlist);

  var idx = -1;
  for(var i = 0 ; i < scrollable.children.length ; i++)
    if(scrollable.children[i].classList.contains('active')){
      idx = i;
      break;
    }
  if(idx != -1){

    report['presets'][presetidx]['columns'].splice(idx, 1);
    scrollable.removeChild(scrollable.children[idx]);

  }

}

ui_list.presetoptionsortload = function(){

  var presetidx = report['presetidx'];
  var presets = report['presets'];
  var preset = presets[presetidx];
  var sorts = typeof preset['sorts'] != 'undefined' ? preset['sorts'] : null;
  var sortlist = ui('.sortlist');
  var scrollable = ui('.scrollable', sortlist);

  scrollable.innerHTML = '';
  if(sorts instanceof Array)
    for(var i = 0 ; i < sorts.length ; i++)
      ui_list.presetoptionsortnew(sorts[i]);
}
ui_list.presetoptionsortsave = function(){

  var presetidx = report['presetidx'];
  var presets = report['presets'];
  if(typeof presets[presetidx] == 'undefined') return;
  var preset = presets[presetidx];
  var sorts = typeof preset['sorts'] != 'undefined' ? preset['sorts'] : null;
  var sortlist = ui('.sortlist');
  var scrollable = ui('.scrollable', sortlist);

  var sortitems = ui('.sortitem', scrollable, 1);
  var sorts = [];
  if(sortitems)
    for(var i = 0 ; i< sortitems.length ; i++)
      sorts.push(ui.container_value(sortitems[i]));

  report['presets'][presetidx]['sorts'] = sorts;
}
ui_list.presetoptionsortnew = function(sort){

  var presetidx = report['presetidx'];
  var presets = report['presets'];
  if(typeof presets[presetidx] == 'undefined') return;
  var preset = presets[presetidx];
  var columns = preset['columns'];
  var sortlist = ui('.sortlist');
  var scrollable = ui('.scrollable', sortlist);

  var sortitems = [];
  for(var i = 0 ; i < columns.length ; i++)
    sortitems.push({ text:columns[i].text, value:columns[i].name });

  var sorttypes = [
    { text:'Ascending', value:'asc' },
    { text:'Descending', value:'desc' }
  ];

  var name = ui.ov('name', sort);
  var sorttype = ui.ov('sorttype', sort);

  var c = "<div class='sortitem' onclick='ui_list.presetoptionsortitemclick(event, this)'>";
  c += ui.dropdown({ name:'name', items:sortitems, value:name, width:240 });
  c += "&nbsp;";
  c += ui.dropdown({ name:'sorttype', items:sorttypes, value:sorttype, width:140 });
  c += "</div>";
  scrollable.insertAdjacentHTML('beforeend', c);

}
ui_list.presetoptionsortitemclick = function(e, div){
  var cont = div.parentNode;
  var active_div = ui('.active', cont);
  if(active_div) active_div.classList.remove('active');
  div.classList.add('active');
}
ui_list.presetoptionsortremove = function(){
  var presetidx = report['presetidx'];
  var presets = report['presets'];
  var preset = presets[presetidx];
  var columns = preset['columns'];
  var sortlist = ui('.sortlist');
  var scrollable = ui('.scrollable', sortlist);

  var idx = -1;
  for(var i = 0 ; i < scrollable.children.length ; i++)
    if(scrollable.children[i].classList.contains('active')){
      idx = i;
      break;
    }
  if(idx != -1){
    report['presets'][presetidx]['sorts'].splice(idx, 1);
    scrollable.removeChild(scrollable.children[idx]);
  }
}
ui_list.presetoptionsortmoveup = function(){
  var presetidx = report['presetidx'];
  var presets = report['presets'];
  var preset = presets[presetidx];
  var columns = preset['columns'];
  var sortlist = ui('.sortlist');
  var scrollable = ui('.scrollable', sortlist);

  var idx = -1;
  for(var i = 0 ; i < scrollable.children.length ; i++)
    if(scrollable.children[i].classList.contains('active')){
      idx = i;
      break;
    }
  if(idx != -1 && idx > 0){
    var temp = report['presets'][presetidx]['sorts'][idx - 1];
    report['presets'][presetidx]['sorts'][idx - 1] = report['presets'][presetidx]['sorts'][idx];
    report['presets'][presetidx]['sorts'][idx] = temp;
    scrollable.insertBefore(scrollable.children[idx], scrollable.children[idx - 1]);
  }
}
ui_list.presetoptionsortmovedown = function(){
  var presetidx = report['presetidx'];
  var presets = report['presets'];
  var preset = presets[presetidx];
  var columns = preset['columns'];
  var sortlist = ui('.sortlist');
  var scrollable = ui('.scrollable', sortlist);

  var idx = -1;
  for(var i = 0 ; i < scrollable.children.length ; i++)
    if(scrollable.children[i].classList.contains('active')){
      idx = i;
      break;
    }
  if(idx != -1 && idx < scrollable.children.length - 1){
    var temp = report['presets'][presetidx]['sorts'][idx + 1];
    report['presets'][presetidx]['sorts'][idx + 1] = report['presets'][presetidx]['sorts'][idx];
    report['presets'][presetidx]['sorts'][idx] = temp;
    scrollable.insertBefore(scrollable.children[idx + 1], scrollable.children[idx]);
  }
}

ui_list.presetoptionfilterload = function(){

  var presetidx = report['presetidx'];
  var presets = report['presets'];
  var preset = presets[presetidx];
  var filters = typeof preset['filters'] != 'undefined' ? preset['filters'] : null;
  var filterlist = ui('.filterlist');
  var scrollable = ui('.scrollable', filterlist);

  scrollable.innerHTML = '';
  if(filters instanceof Array)
    for(var i = 0 ; i < filters.length ; i++)
      ui_list.presetoptionfilternew(filters[i]);

}
ui_list.presetoptionfiltersave = function(){

  var presetidx = report['presetidx'];
  var presets = report['presets'];
  if(typeof presets[presetidx] == 'undefined') return;
  var preset = presets[presetidx];
  var filterlist = ui('.filterlist');
  var scrollable = ui('.scrollable', filterlist);

  var filteritems = ui('.filteritem', scrollable, 1);
  var filters = [];
  if(filteritems)
    for(var i = 0 ; i< filteritems.length ; i++)
      filters.push(ui.container_value(filteritems[i]));

  report['presets'][presetidx]['filters'] = filters;

}
ui_list.presetoptionfilternew = function(filter){

  var presetidx = report['presetidx'];
  var presets = report['presets'];
  var preset = presets[presetidx];
  var columns = preset['columns'];
  var filterlist = ui('.filterlist');
  var scrollable = ui('.scrollable', filterlist);

  var columnitems = [];
  for(var i = 0 ; i < columns.length ; i++)
    columnitems.push({ text:columns[i].text, value:columns[i].name });

  var c = "<div class='filteritem'>";
  if(filter){
    var columnname = filter['name'];

    c += ui.checkbox({ name:'selected' });
    c += "<span>";
    c += ui.dropdown({ name:'name', items:columnitems, width:120, value:columnname, onchange:"ui_list.presetoptionfiltercolumnchange(value, this)" });
    c += "</span>";
    c += "<span class='sect-operator'>";
    c += ui_list.presetoptionfiltercolumnui(columnname, filter['operator']);
    c += "</span>";
    c += "<span class='sect-value'>";
    c += ui_list.presetoptionfiltervalueui(filter['operator'], filter); //ui.textbox({ name:"value", value:filter['value'], width: 100 });
    c += "</span>";
  }
  else{
    c += ui.checkbox({ name:'selected' });
    c += "<span>";
    c += ui.dropdown({ name:'name', items:columnitems, width:120, onchange:"ui_list.presetoptionfiltercolumnchange(value, this)" });
    c += "</span>";
    c += "<span class='sect-operator'>";
    c += "</span>";
    c += "<span class='sect-value'>";
    c += "</span>";
  }
  c += "</div>";
  scrollable.insertAdjacentHTML('beforeend', c);

}
ui_list.presetoptionfilterremove = function(){

  var filterlist = ui('.filterlist');
  var scrollable = ui('.scrollable', filterlist);
  for(var i = scrollable.children.length - 1 ; i >= 0 ; i--){
    var filteritem = scrollable.children[i];
    if(ui.checkbox_value(ui('%selected', filteritem)))
      scrollable.removeChild(scrollable.children[i]);
  }

}
ui_list.presetoptionfiltercolumnchange = function(columnname, el){

  var filteritem = el.parentNode.parentNode;
  var sect_operator = ui('.sect-operator', filteritem);
  sect_operator.innerHTML = ui_list.presetoptionfiltercolumnui(columnname);

}
ui_list.presetoptionfilteroperatorchange = function(operator, el){

  var filteritem = el.parentNode.parentNode;
  var sect_value = ui('.sect-value', filteritem);
  sect_value.innerHTML = ui_list.presetoptionfiltervalueui(operator);

}
ui_list.presetoptionfiltervalueui = function(operator, obj){
  var value = ui.ov('value', obj);
  var value1 = ui.ov('value1', obj);

  // Construct operator control
  var c = '';
  switch(operator){
    case 'today': break;
    case 'thisweek': break;
    case 'thismonth': break;
    case 'prevmonth': break;
    case 'thisyear': break;
    case 'on':
      c += ui.datepicker({ name:"value", value:value });
      break;
    case 'between':
      c += ui.datepicker({ name:"value", value:value });
      c += ui.datepicker({ name:"value1", value:value1 });
      break;
    case 'before':
      c += ui.datepicker({ name:"value", value:value });
      break;
    case 'after':
      c += ui.datepicker({ name:"value", value:value });
      break;
    default :
      c += ui.textbox({ name:"value", width: 100, value:value });
      break;
  }
  return c;

}
ui_list.presetoptionfiltercolumnui = function(columnname, operator){

  // Get column datatype
  var presetidx = report['presetidx'];
  var presets = report['presets'];
  var preset = presets[presetidx];
  var columns = preset['columns'];
  var datatype = '';
  for(var i = 0 ; i < columns.length ; i++)
    if(columns[i].name == columnname){
      datatype = columns[i].datatype;
      break;
    }

  // Construct operator control
  var c = '';
  switch(datatype){
    case 'date':
      var items = [
        { value:"today", text:"Today" },
        { value:"thisweek", text:"This Week" },
        { value:"thismonth", text:"This Month" },
        { value:"prevmonth", text:"Previous Month" },
        { value:"lastyear", text:"Last Year" },
        { value:"thisyear", text:"This Year" },
        { value:"on", text:"On" },
        { value:"between", text:"Between" },
        { value:"before", text:"Before" },
        { value:"after", text:"After" }
      ];
      c += ui.dropdown({ name:"operator", items:items, value:operator, width: 120, onchange:"ui_list.presetoptionfilteroperatorchange(value, this)" });
      break;
    case 'number':
      var items = [
        { value:"<", text:"<" },
        { value:"<=", text:"<=" },
        { value:"=", text:"=" },
        { value:">", text:">" },
        { value:">=", text:">=" },
        { value:"between", text:"Between" }
      ];
      c += ui.dropdown({ name:"operator", items:items, value:operator, width: 120, onchange:"ui_list.presetoptionfilteroperatorchange(value, this)" });
      break;
    case 'money':
      var items = [
        { value:"<", text:"<" },
        { value:"<=", text:"<=" },
        { value:"=", text:"=" },
        { value:">", text:">" },
        { value:">=", text:">=" },
        { value:"between", text:"Between" }
      ];
      c += ui.dropdown({ name:"operator", items:items, value:operator, width: 120, onchange:"ui_list.presetoptionfilteroperatorchange(value, this)" });
      break;
    default :
      var items = [
        { value:"equals", text:"Equals" },
        { value:"contains", text:"Contains" },
        { value:"in", text:"In" }
      ];
      c += ui.dropdown({ name:"operator", items:items, value:operator, width: 100, onchange:"ui_list.presetoptionfilteroperatorchange(value, this)" });
      break;
  }
  c += ui.hidden({ name:"type", value:datatype });

  return c;
}

ui_list.reportoptiongrouptoggle = function(el){
  var active = ui.checkbox_value(el);
  report['presets'][report['presetidx']]['viewtype'] = active ? 'group' : 'list';
}
ui_list.presetoptiongroupsave = function(){

  var groups = [];
  var tabgroup = ui('.tabgroups');
  var grouplist = ui('.grouplist', tabgroup);
  var grouplistscrollable = ui('.scrollable', grouplist);
  for(var i = 0 ; i < grouplistscrollable.children.length - 1; i++){
    var grouptable = grouplistscrollable.children[i];
    var name = ui.dropdown_value(ui('%name', grouptable));
    var aggregrate = ui.dropdown_value(ui('%aggregrate', grouptable));
    var columns = [];
    var groupcolumns = ui('.groupcolumns', grouptable);
    for(var j =  0 ; j < groupcolumns.firstElementChild.children.length - 1 ; j++){
      var tr = groupcolumns.firstElementChild.children[j];
      var columnname = ui.dropdown_value(ui('%columnname', tr));
      var columnlogic = ui.dropdown_value(ui('%columnlogic', tr));
      columns.push({ name:columnname, logic:columnlogic });
    }

    if(name.length > 0 && columns.length > 0){
      groups.push({ name:name, aggregrate:aggregrate, columns:columns });
    }
  }

  report['presets'][report['presetidx']]['groups'] = groups;

}
ui_list.presetoptiongroupload = function(){
  var tabgroup = ui('.tabgroups');
  ui.checkbox_setvalue(ui('%active', tabgroup), report['presets'][report['presetidx']]['viewtype'] == 'group' ? true : false);

  var tabgroup = ui('.tabgroups');
  var grouplist = ui('.grouplist', tabgroup);
  var grouplistscrollable = ui('.scrollable', grouplist);

  if(typeof report['presets'][report['presetidx']]['groups'] != 'undefined' && report['presets'][report['presetidx']]['groups'].length > 0){
    var groups = report['presets'][report['presetidx']]['groups'];

    grouplistscrollable.innerHTML = "<div id='groupnewcont'><button class='hollow' onclick=\"ui_list.presetoptiongroupnew()\"><span class='fa fa-plus'></span><label>Add new group</label></button><div>";
    for(var i = 0 ; i < groups.length ; i++){
      var group = groups[i];
      ui_list.presetoptiongroupnew(group);
    }
  }
  else
    grouplistscrollable.innerHTML = "<div id='groupnewcont'><button class='hollow' onclick=\"ui_list.presetoptiongroupnew()\"><span class='fa fa-plus'></span><label>Add new group</label></button><div>";
}
ui_list.presetoptiongroupresize = function(){

  // Resize scrollable height
  var tabgroup = ui('.tabgroups');
  var grouplist = ui('.grouplist', tabgroup);
  var grouplistscrollable = ui('.scrollable', grouplist);
  var presetdetail = ui('.presetdetail');
  var presetdetailcontentheight = presetdetail.clientHeight - presetdetail.firstElementChild.clientHeight - tabgroup.firstElementChild.clientHeight - 70;
  grouplistscrollable.style.height = presetdetailcontentheight + "px";
  grouplistscrollable.style.width = "560px";

}
ui_list.presetoptiongroupnew = function(obj){
  var name = ui.ov('name', obj);
  var aggregrate = ui.ov('aggregrate', obj);
  var groupcolumns = ui.ov('columns', obj);

  var tabgroup = ui('.tabgroups');
  var grouplist = ui('.grouplist', tabgroup);
  var grouplistscrollable = ui('.scrollable', grouplist);
  var columns = report['columns'];

  var columnitems = [];
  for(var i = 0 ; i < columns.length ; i++)
    columnitems.push({ text:columns[i].text, value:columns[i].name });

  var aggregrates = [
    { text:"First", value: "" },
    { text:"Monthly", value: "monthly" },
    { text:"Yearly", value: "yearly" }
  ];

  var logics = [
    { text:"First", value:"first" },
    { text:"Sum", value:"sum" },
    { text:"Count", value:"count" },
    { text:"Avg", value:"avg" },
    { text:"Min", value:"min" },
    { text:"Max", value:"max" }
  ];

  var c = "<table cellpadding='5' style='border-collapse:collapse'><tr>";
  c += "<td valign='top'><span class='padding5'><input type='checkbox' class='groupcheck'/></span></td>";
  c += "<td valign='top'>" + ui.dropdown({ name:'name', items:columnitems, value:name, width:120 }) + "</td>";
  c += "<td valign='top'>" + ui.dropdown({ name:'aggregrate', items:aggregrates, value:aggregrate, width:80 }) + "</td>";
  c += "<td valign='top' style='padding:0;margin:0'><table cellpadding='5' style='border-collapse:collapse' class='groupcolumns'>";
  if(groupcolumns){
    for(var i = 0 ; i < groupcolumns.length ; i++){
      var groupcolumn = groupcolumns[i];
      var groupcolumnname = groupcolumn['name'];
      var groupcolumnlogic = groupcolumn['logic'];

      c += "<tr>";
      c += "<td valign='middle'><input type='checkbox' class='groupcolumncheck'/></td>";
      c += "<td>" + ui.dropdown({ name:'columnname', items:columnitems, value:groupcolumnname, width:120 }) + "</td>";
      c += "<td>" + ui.dropdown({ name:'columnlogic', items:logics, value:groupcolumnlogic, width:80 }) + "</td>";
      c += "</tr>";

    }
    c += "<tr class='groupcolumnnewcont'><td colspan='3'><button class='hollow' onclick=\"ui_list.presetoptiongroupcolumnnew(this.parentNode.parentNode.parentNode.parentNode.parentNode)\"><span class='fa fa-plus'></span><label>Add new column</label></button></td></tr>";
  }
  else{

    c += "<tr>";
    c += "<td valign='middle'><input type='checkbox' class='groupcolumncheck'/></td>";
    c += "<td>" + ui.dropdown({ name:'columnname', items:columnitems, value:'', width:120 }) + "</td>";
    c += "<td>" + ui.dropdown({ name:'columnlogic', items:logics, value:'first', width:80 }) + "</td>";
    c += "</tr>";
    c += "<tr class='groupcolumnnewcont'><td colspan='3'><button class='hollow' onclick=\"ui_list.presetoptiongroupcolumnnew(this.parentNode.parentNode.parentNode.parentNode.parentNode)\"><span class='fa fa-plus'></span><label>Add new column</label></button></td></tr>";

  }
  c += "</table></td>";
  c += "</tr></table>";
  grouplistscrollable.insertAdjacentHTML('beforeend', c);

  grouplistscrollable.appendChild(ui('#groupnewcont'));

}
ui_list.presetoptiongroupcolumnnew = function(td, obj){

  var groupcolumnname = ui.ov('name', obj);
  var groupcolumnlogic = ui.ov('logic', obj, 0, 'first');
  var columns = report['columns'];

  var columnitems = [];
  for(var i = 0 ; i < columns.length ; i++)
    columnitems.push({ text:columns[i].text, value:columns[i].name });

  var logics = [
    { text:"First", value:"first" },
    { text:"Sum", value:"sum" },
    { text:"Count", value:"count" },
    { text:"Avg", value:"avg" },
    { text:"Min", value:"min" },
    { text:"Max", value:"max" }
  ];

  var c = "";
  c += "<tr>";
  c += "<td valign='middle'><input type='checkbox' class='groupcolumncheck'/></td>";
  c += "<td>" + ui.dropdown({ name:'columnname', items:columnitems, value:groupcolumnname, width:120 }) + "</td>";
  c += "<td>" + ui.dropdown({ name:'columnlogic', items:logics, value:groupcolumnlogic, width:80 }) + "</td>";
  c += "</tr>";

  var table = td.firstElementChild;
  var tbody = table.firstElementChild;
  tbody.insertAdjacentHTML('beforeend', c);
  tbody.appendChild(ui('.groupcolumnnewcont', tbody));

}
ui_list.reportoptiongroupcolumnremove = function(){

  var checkboxes = document.querySelectorAll(".groupcolumncheck:checked");
  for(var i = 0 ; i < checkboxes.length ; i++){
    var checkbox = checkboxes[i];
    var tr = checkbox.parentNode.parentNode;
    var tbody = tr.parentNode;
    tbody.removeChild(tr);
    if(tbody.children.length <= 1) ui_list.presetoptiongroupcolumnnew(tbody.parentNode.parentNode);
  }

}
ui_list.reportoptiongroupcolumnmoveup = function(){

  var checkboxes = document.querySelectorAll(".groupcolumncheck:checked");
  for(var i = 0 ; i < checkboxes.length ; i++){
    var checkbox = checkboxes[i];
    var tr = checkbox.parentNode.parentNode;
    if(tr.previousElementSibling) tr.parentNode.insertBefore(tr, tr.previousElementSibling);
  }

}
ui_list.reportoptiongroupcolumnmovedown = function(){

  var checkboxes = document.querySelectorAll(".groupcolumncheck:checked");
  for(var i = 0 ; i < checkboxes.length ; i++){
    var checkbox = checkboxes[i];
    var tr = checkbox.parentNode.parentNode;
    if(tr.nextElementSibling) tr.parentNode.insertBefore(tr.nextElementSibling, tr);
  }

}
ui_list.reportoptiongroupremove = function(){

  var checkboxes = document.querySelectorAll(".groupcheck:checked");
  for(var i = 0 ; i < checkboxes.length ; i++){
    var checkbox = checkboxes[i];
    var tr = checkbox.parentNode.parentNode.parentNode;
    var tbody = tr.parentNode;
    var table = tbody.parentNode;
    table.parentNode.removeChild(table);
  }

}
ui_list.reportoptiongroupmoveup = function(){

  var checkboxes = document.querySelectorAll(".groupcheck:checked");
  for(var i = 0 ; i < checkboxes.length ; i++){
    var checkbox = checkboxes[i];

    var tr = checkbox.parentNode.parentNode.parentNode;
    var tbody = tr.parentNode;
    var table = tbody.parentNode;

    if(table.previousElementSibling)
      table.parentNode.insertBefore(table, table.previousElementSibling);
  }

}

ui_list.presetapply = function(){

  ui_list.presetoptionsortsave();
  ui_list.presetoptionfiltersave();
  ui_list.presetoptiongroupsave();
  ui.async(report_onapply, [ report ], { waitel:"#presetsavebtn", callback:"ui.modal_close(ui('.modal'))" });

}