list = {};
list.contentbody_loadingmode = function(){
  ui('.contentbody').innerHTML = "<div class='spinner' style='padding-top:20px'><div class='bounce1'></div><div class='bounce2'></div><div class='bounce3'></div></div>";
}

list.presetselect = function(index, el){
  ui.async('ui_presetselect', [ index ]);
  ui('.contentbody').innerHTML = "<div class='spinner' style='padding-top:20px'><div class='bounce1'></div><div class='bounce2'></div><div class='bounce3'></div></div>";
}
list.presetoptionload = function(){
  list.presetoptionpresetlistload();
  list.presetoptionpresetdetailload();
}
list.presetoptionpresetlistload = function(){
  var presets = report['presets'];
  var presetlist = ui('.presetlist');

  var menulist = ui('.menulist', presetlist);
  var c = "";
  for(var i = 0 ; i < presets.length ; i++){
    var preset = presets[i];
    var text = preset['text'];
    var active_class = i == report['presetidx'] ? ' active' : '';
    c += "<div class=\"menuitem" + active_class + "\"><span class=\"fa fa-calendar\"></span><label onclick=\"list.presetoptionitemclick(this)\">" + text + "</label>";
    c += "<span class='sect2'>";
    c += "<span class='fa fa-copy' onclick=\"list.presetoptioncopy(this)\"></span>";
    c += "<span class='fa fa-times' onclick=\"list.presetoptionremove(this)\"></span>";
    c += "</span>";
    c += "</div>";
  }
  menulist.innerHTML = c;
}
list.presetoptionitemclick = function(label){
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
  list.presetoptionpresetdetailload();
}
list.presetoptionpresetdetailload = function(){
  var presets = report['presets'];
  var presetidx = report['presetidx'];
  var preset = presets[presetidx];
  ui.textbox_setvalue(ui('%text', ui('.tabname')), preset['text']);
  list.presetoptioncolumnload();
  list.presetoptionsortload();
  list.presetoptionfilterload();
  list.presetoptiongroupload();
}
list.presetoptioncopy = function(span){

  // Get index
  var menuitem = span.parentNode.parentNode;
  var menulist = menuitem.parentNode;
  var idx = -1;
  for(var i = 0 ; i < menulist.children.length ; i++){
    if(menulist.children[i] == menuitem){
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
  list.presetoptionpresetlistload();
  list.presetoptionpresetdetailload();
}
list.presetoptionremove = function(span){

  // Get index
  var menuitem = span.parentNode.parentNode;
  var menulist = menuitem.parentNode;
  var idx = -1;
  for(var i = 0 ; i < menulist.children.length ; i++){
    if(menulist.children[i] == menuitem){
      idx = i;
      break;
    }
  }

  report['presets'].splice(idx, 1);
  report['presetidx'] = report['presets'].length - 1;
  list.presetoptionpresetlistload();
  list.presetoptionpresetdetailload();

}
list.presetoptiontextchange = function(text){

  // Update data
  var presetidx = report['presetidx'];
  var presets = report['presets'];
  report['presets'][presetidx]['text'] = text;

  // Update menulist ui
  var presetlist = ui('.presetlist');
  var menulist = ui('.menulist', presetlist);
  ui('label', menulist.children[presetidx]).innerHTML = text;

}
list.presetoptioncolumnload = function(){

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
    var text = column['text'];
    var active = column['active'];
    var selected = i == preset['columnidx'] ? 1 : 0;

    c += "<div class='columnitem" + (selected ? ' active' : '') + "' onclick=\"list.presetoptioncolumndetailload(" + i + ")\">" +
      "<input type='checkbox' " + (active ? 'checked' : '') + " onchange=\"list.presetoptioncolumndetailchange('active', this.checked)\"/>" +
      "<label>" + text + "</label>" +
      "</div>";
  }
  scrollable.innerHTML = c;

  list.presetoptioncolumndetailload(0);
  report['presetidx'] = presetidx;
}
list.presetoptioncolumndetailload = function(idx){
  console.warn("list.presetoptioncolumndetailload, idx: " + idx);

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
  if(active_columnitem) active_columnitem.classList.remove('active');
  scrollable.children[idx].classList.add('active');
}
list.presetoptioncolumndetailchange = function(name, value){
  var presets = report['presets'];
  var preset = presets[report['presetidx']];
  var columnidx = preset['columnidx'];
  report['presets'][report['presetidx']]['columns'][columnidx][name] = value;
}

list.presetoptionsortload = function(){

  var presetidx = report['presetidx'];
  var presets = report['presets'];
  var preset = presets[presetidx];
  var sorts = typeof preset['sorts'] != 'undefined' ? preset['sorts'] : null;
  var sortlist = ui('.sortlist');
  var scrollable = ui('.scrollable', sortlist);

  scrollable.innerHTML = '';
  if(sorts instanceof Array)
    for(var i = 0 ; i < sorts.length ; i++)
      list.presetoptionsortnew(sorts[i]);
}
list.presetoptionsortsave = function(){

  var presetidx = report['presetidx'];
  var presets = report['presets'];
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
list.presetoptionsortnew = function(sort){

  var presetidx = report['presetidx'];
  var presets = report['presets'];
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

  var c = "<div class='sortitem' onclick='list.presetoptionsortitemclick(event, this)'>";
  c += ui.dropdown({ name:'name', items:sortitems, value:name, width:240 });
  c += "&nbsp;";
  c += ui.dropdown({ name:'sorttype', items:sorttypes, value:sorttype, width:140 });
  c += "</div>";
  scrollable.insertAdjacentHTML('beforeend', c);

}
list.presetoptionsortitemclick = function(e, div){
  var cont = div.parentNode;
  var active_div = ui('.active', cont);
  if(active_div) active_div.classList.remove('active');
  div.classList.add('active');
}
list.presetoptionsortremove = function(){
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
list.presetoptionsortmoveup = function(){
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
list.presetoptionsortmovedown = function(){
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

list.presetoptionfilterload = function(){

  var presetidx = report['presetidx'];
  var presets = report['presets'];
  var preset = presets[presetidx];
  var filters = typeof preset['filters'] != 'undefined' ? preset['filters'] : null;
  var filterlist = ui('.filterlist');
  var scrollable = ui('.scrollable', filterlist);

  scrollable.innerHTML = '';
  if(filters instanceof Array)
    for(var i = 0 ; i < filters.length ; i++)
      list.presetoptionfilternew(filters[i]);

}
list.presetoptionfiltersave = function(){

  var presetidx = report['presetidx'];
  var presets = report['presets'];
  var preset = presets[presetidx];
  var filterlist = ui('.filterlist');
  var scrollable = ui('.scrollable', filterlist);

  var filteritems = ui('.filteritem', scrollable, 1);
  var filters = [];
  if(filteritems)
    for(var i = 0 ; i< filteritems.length ; i++)
      filters.push(ui.container_value(filteritems[i]));
  console.log(filters);

  report['presets'][presetidx]['filters'] = filters;

}
list.presetoptionfilternew = function(filter){

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
    c += ui.dropdown({ name:'name', items:columnitems, width:120, value:columnname, onchange:"list.presetoptionfiltercolumnchange(value, this)" });
    c += "</span>";
    c += "<span class='sect-operator'>";
    c += list.presetoptionfiltercolumnui(columnname, filter['operator']);
    c += "</span>";
    c += "<span class='sect-value'>";
    c += list.presetoptionfiltervalueui(filter['operator'], filter); //ui.textbox({ name:"value", value:filter['value'], width: 100 });
    c += "</span>";
  }
  else{
    c += ui.checkbox({ name:'selected' });
    c += "<span>";
    c += ui.dropdown({ name:'name', items:columnitems, width:120, onchange:"list.presetoptionfiltercolumnchange(value, this)" });
    c += "</span>";
    c += "<span class='sect-operator'>";
    c += "</span>";
    c += "<span class='sect-value'>";
    c += "</span>";
  }
  c += "</div>";
  scrollable.insertAdjacentHTML('beforeend', c);

}
list.presetoptionfilterremove = function(){

  var filterlist = ui('.filterlist');
  var scrollable = ui('.scrollable', filterlist);
  for(var i = scrollable.children.length - 1 ; i >= 0 ; i--){
    var filteritem = scrollable.children[i];
    if(ui.checkbox_value(ui('%selected', filteritem)))
      scrollable.removeChild(scrollable.children[i]);
  }

}
list.presetoptionfiltercolumnchange = function(columnname, el){

  var filteritem = el.parentNode.parentNode;
  var sect_operator = ui('.sect-operator', filteritem);
  sect_operator.innerHTML = list.presetoptionfiltercolumnui(columnname);

}
list.presetoptionfilteroperatorchange = function(operator, el){

  var filteritem = el.parentNode.parentNode;
  var sect_value = ui('.sect-value', filteritem);
  sect_value.innerHTML = list.presetoptionfiltervalueui(operator);

}
list.presetoptionfiltervalueui = function(operator, obj){
  var value = ui.ov('value', obj);
  var value1 = ui.ov('value1', obj);

  // Construct operator control
  var c = '';
  switch(operator){
    case 'today': break;
    case 'thisweek': break;
    case 'thismonth': break;
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
list.presetoptionfiltercolumnui = function(columnname, operator){

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

  console.warn(columnname + ", " + datatype);

  // Construct operator control
  var c = '';
  switch(datatype){
    case 'date':
      var items = [
        { value:"today", text:"Today" },
        { value:"thisweek", text:"This Week" },
        { value:"thismonth", text:"This Month" },
        { value:"thisyear", text:"This Year" },
        { value:"on", text:"On" },
        { value:"between", text:"Between" },
        { value:"before", text:"Before" },
        { value:"after", text:"After" }
      ];
      c += ui.dropdown({ name:"operator", items:items, value:operator, width: 120, onchange:"list.presetoptionfilteroperatorchange(value, this)" });
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
      c += ui.dropdown({ name:"operator", items:items, value:operator, width: 120, onchange:"list.presetoptionfilteroperatorchange(value, this)" });
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
      c += ui.dropdown({ name:"operator", items:items, value:operator, width: 120, onchange:"list.presetoptionfilteroperatorchange(value, this)" });
      break;
    default :
      var items = [
        { value:"equals", text:"Equals" },
        { value:"contains", text:"Contains" }
      ];
      c += ui.dropdown({ name:"operator", items:items, value:operator, width: 100, onchange:"list.presetoptionfilteroperatorchange(value, this)" });
      break;
  }
  c += ui.hidden({ name:"type", value:datatype });

  return c;
}

list.reportoptiongrouptoggle = function(el){
  var active = ui.checkbox_value(el);
  report['presets'][report['presetidx']]['viewtype'] = active ? 'group' : 'list';
}
list.presetoptiongroupnew = function(obj){
  var name = ui.ov('name', obj);

  var tabgroup = ui('.tabgroups');
  var grouplist = ui('.grouplist', tabgroup);
  var grouplistscrollable = ui('.scrollable', grouplist);
  var columns = report['columns'];

  var sortitems = [];
  for(var i = 0 ; i < columns.length ; i++)
    sortitems.push({ text:columns[i].text, value:columns[i].name });

  var c = "<div class='groupitem' onclick=\"list.presetoptiongroupselect(this)\">";
  c += ui.dropdown({ name:'name', items:sortitems, value:name, width:120 });
  c += "</div>";
  grouplistscrollable.insertAdjacentHTML('beforeend', c);
}
list.presetoptiongroupselect = function(groupitem){
  var tabgroup = ui('.tabgroups');
  var grouplist = ui('.grouplist', tabgroup);
  var grouplistscrollable = ui('.scrollable', grouplist);
  var groupdetail = ui('.groupdetail', tabgroup);
  var groupdetailscrollable = ui('.scrollable', groupdetail);

  if(groupitem instanceof HTMLElement){
    var idx = -1;
    for(var i = 0 ; i < grouplistscrollable.children.length ; i++)
      if(grouplistscrollable.children[i] == groupitem){
        idx = i;
        break;
      }
  }
  else{
    idx = groupitem;
  }

  var active_groupitem = ui('.active', grouplistscrollable);
  if(active_groupitem) active_groupitem.classList.remove('active');
  grouplistscrollable.children[idx].classList.add('active');

  var groups = report['presets'][report['presetidx']]['groups'];
  groupdetailscrollable.innerHTML = '';
  if(idx >= 0 && idx < groups.length){
    var group = groups[idx];
    var groupcolumns = group['columns'];
    var groupdetail = ui('.groupdetail', tabgroup);
    var groupdetailscrollable = ui('.scrollable', groupdetail);

    for(var i = 0 ; i < groupcolumns.length ; i++){
      list.presetoptiongroupdetailnew(groupcolumns[i]);
    }
  }
  else{
    list.presetoptiongroupdetailnew({ name:name, logic:'first' });
  }

  console.warn('idx: ' + idx);

}
list.presetoptiongroupremove = function(){
  var tabgroup = ui('.tabgroups');
  var grouplist = ui('.grouplist', tabgroup);
  var grouplistscrollable = ui('.scrollable', grouplist);
  var idx = -1;
  for(var i = 0 ; i < grouplistscrollable.children.length ; i++)
    if(grouplistscrollable.children[i].classList.contains('active')){
      idx = i;
      break;
    }

  report['presets'][report['presetidx']]['groups'].splice(idx, 1);
  grouplistscrollable.removeChild(grouplistscrollable.children[idx]);

  var next_idx = report['presets'][report['presetidx']]['groups'].length - 1;
  list.presetoptiongroupselect(next_idx);
}
list.presetoptiongroupmoveup = function(){

}
list.presetoptiongroupmovedown = function(){

}
list.presetoptiongroupdetailnew = function(obj){

  var name = ui.ov('name', obj);
  var logic = ui.ov('logic', obj);

  var columns = report['columns'];
  var tabgroup = ui('.tabgroups');
  var groupdetail = ui('.groupdetail', tabgroup);
  var groupdetailscrollable = ui('.scrollable', groupdetail);

  var sortitems = [];
  for(var i = 0 ; i < columns.length ; i++)
    sortitems.push({ text:columns[i].text, value:columns[i].name });

  var logics = [
    { value:"first", text:"First" },
    { value:"sum", text:"Sum" },
    { value:"avg", text:"Average" },
    { value:"min", text:"Min" },
    { value:"max", text:"Max" }
  ];

  var c = "<div class='groupitem'>";
  c += ui.dropdown({ name:'name', items:sortitems, value:name, width:120, onchange:"list.presetoptiongroupdetailcolumnchange(value, this)" });
  c += "&nbsp;";
  c += ui.dropdown({ name:'logic', items:logics, value:logic, width:80, onchange:"list.presetoptiongroupsave()" });
  c += "</div>";
  groupdetailscrollable.insertAdjacentHTML('beforeend', c);

}
list.presetoptiongroupdetailcolumnchange = function(name, el){

  var groupitem = el.parentNode;
  var logicel = ui('%logic', groupitem);
  if(ui.dropdown_value(logicel) == '')
    ui.dropdown_setvalue(logicel, 'first');

  list.presetoptiongroupsave();

}
list.presetoptiongroupsave = function(){

  var tabgroup = ui('.tabgroups');
  var grouplist = ui('.grouplist', tabgroup);
  var grouplistscrollable = ui('.scrollable', grouplist);
  var groupdetail = ui('.groupdetail', tabgroup);
  var groupdetailscrollable = ui('.scrollable', groupdetail);

  var idx = -1;
  for(var i = 0 ; i < grouplistscrollable.children.length ; i++)
    if(grouplistscrollable.children[i].classList.contains('active')){
      idx = i;
      break;
    }

  if(typeof report['presets'][report['presetidx']]['groups'] == 'undefined')
    report['presets'][report['presetidx']]['groups'] = [];

  var groupitem = grouplistscrollable.children[idx];
  var name = ui.dropdown_value(ui('%name', groupitem));
  var columns = [];
  for(var i = 0 ; i < groupdetailscrollable.children.length ; i++){
    var groupdetailitem = groupdetailscrollable.children[i];
    var groupdetailname = ui.dropdown_value(ui('%name', groupdetailitem));
    var groupdetaillogic = ui.dropdown_value(ui('%logic', groupdetailitem));

    if(groupdetailname.length > 0 && groupdetaillogic.length > 0)
      columns.push({ name:groupdetailname, logic:groupdetaillogic });
  }

  if(name.length > 0 && columns.length > 0){
    var group = { name:name, columns:columns };
    report['presets'][report['presetidx']]['groups'][idx] = group;
  }

}
list.presetoptiongroupload = function(){
  var tabgroup = ui('.tabgroups');
  ui.checkbox_setvalue(ui('%active', tabgroup), report['presets'][report['presetidx']]['viewtype'] == 'group' ? true : false);

  if(typeof report['presets'][report['presetidx']]['groups'] != 'undefined' && report['presets'][report['presetidx']]['groups'].length > 0){
    var groups = report['presets'][report['presetidx']]['groups'];
    console.log(group);

    var tabgroup = ui('.tabgroups');
    var grouplist = ui('.grouplist', tabgroup);
    var grouplistscrollable = ui('.scrollable', grouplist);
    var groupdetail = ui('.groupdetail', tabgroup);
    var groupdetailscrollable = ui('.scrollable', groupdetail);

    for(var i = 0 ; i < groups.length ; i++){
      var group = groups[i];
      var groupname = group['name'];
      list.presetoptiongroupnew({ name:groupname });
    }
    list.presetoptiongroupselect(0);
  }
}

list.presetapply = function(){

  list.presetoptionsortsave();
  list.presetoptionfiltersave();
  ui.async('ui_presetapply', [ report ], { waitel:"#presetsavebtn" });

}