ui = {};

ui = function(exp, parent, forceArray, dontincludechild){
  if(!exp || typeof exp != 'string'){
    //ui.warn("Invalid exp parameter. (" + exp + ")");
    return null;
  }
  if(typeof dontincludechild == 'undefined') dontincludechild = 0;
  if(exp instanceof HTMLElement) return '$' + ui.uiid(exp);
  if(exp.substr(0, 1) == '%') exp = "*[data-name='" + exp.substr(1) + "']"; // % tag for data-name
  if(exp.substr(0, 1) == '$') exp = "*[data-uiid='" + exp.substr(1) + "']"; // % tag for data-uiid
  if(!parent) var els = document.body.querySelectorAll(exp);
  else els = parent.querySelectorAll(exp);

  var arr = [];
  for(var i = 0 ; i < els.length ; i++){
    if(els[i].hasAttribute("data-ischild") && els[i].getAttribute("data-ischild") == 1){
      if(!dontincludechild)
        arr.push(els[i]);
    }
    else{
      arr.push(els[i]);
    }
  }
  els = arr;

  if(els.length == 1 && !forceArray) return els[0];
  else if(els.length == 0) return null;
  return els;
}
ui.uiid = function(el){
  if(!el.hasAttribute('data-uiid')){
    if(typeof ui.__uiid == 'undefined') ui.__uiid = 0;
    el.setAttribute('data-uiid', ui.__uiid++);
  }
  return el.getAttribute('data-uiid');
}
ui.hc = function(name, style, method, parent){
  var el = document.createElement(name);
  if(el){
    ui.hs(style, el);
    ui.hm(method, el);
    if(parent) parent.appendChild(el);
    return el;
  }
}
ui.hm = function(method, el){
  if(typeof method == 'object')
    for(var key in method)
      el[key] = method[key];
}
ui.hs = function(style, el){
  if(typeof style == 'object')
    for(var key in style)
      el.style[key] = style[key];
}
ui.fp = function(className, el){
  var cEl = el;
  do{
    cEl = cEl.parentNode;
    if(cEl != null && typeof cEl.classList != 'undefined' && cEl.classList.contains(className)) return cEl;
  }
  while(cEl != null);
}
ui.eventcall = function(exp, param, thisArg){

  var paramString = [];
  var params = [];
  if(param)
    for(var key in param){
      paramString.push("\"" + key + "\"");
      params.push(param[key]);
    }

  if(typeof exp == 'function'){
    return exp.apply(thisArg, params);
  }
  else{

    var f = null;
    try{ f = eval(exp); } catch(ex){ f = null; }
    if(typeof f == 'function'){
      return f.apply(thisArg, params);
    }
    else if($.type(exp) == 'string'){
      paramString.push("\"" + exp + "\"");
      var f = eval("new Function(" + paramString.join(", ") + ")");
      return f.apply(thisArg, params);
    }

  }

}
ui.ov = function(name, obj, required, defaultValue){
  var value = defaultValue ? defaultValue : '';
  if(typeof obj != 'undefined' && typeof obj[name] != 'undefined')
    value = obj[name];
  return value;
}
ui.striphtml = function(text) {
  var scripts = '';
  var styles = '';
  var elements = {};
  var elements2 = {};
  var html = text.replace(/<script[^>]*>([\s\S]*?)<\/script>/gi, function(){
    scripts += arguments[1] + '\n';
    return '';
  });
  html = html.replace(/<style[^>]*>([\s\S]*?)<\/style>/gi, function(){
    styles += arguments[1] + '\n';
    return '';
  });
  html = html.replace(/<element exp='([\.\#\$\w]+)'>([\s\S]*?)<\/element>/gi, function(){
    elements[arguments[1]] = arguments[2];
    return '';
  });
  html = html.replace(/<element_i exp='([\.\#\$\w]+)'>([\s\S]*?)<\/element_i>/gi, function(){
    elements2[arguments[1]] = arguments[2];
    return '';
  });
  html = html.replace(/<element exp="([\s\S]*?)">([\s\S]*?)<\/element>/gi, function(){
    var t = arguments[4];
    var exp = t.substr(t.indexOf("\"") + 1, t.indexOf("\"", t.indexOf("\"") + 1) - t.indexOf("\"") - 1);
    elements[exp] = arguments[2];
    return '';
  });
  html = html.replace(/<element_i exp="([\s\S]*?)">([\s\S]*?)<\/element_i>/gi, function(){
    var t = arguments[4];
    var exp = t.substr(t.indexOf("\"") + 1, t.indexOf("\"", t.indexOf("\"") + 1) - t.indexOf("\"") - 1);
    elements2[exp] = arguments[2];
    return '';
  });
  var obj = { html:html, script:scripts, style:styles, elements:elements, elements2:elements2 };
  return obj;
}
ui.loadscript = function(src, callback){

  src = src + "?nocache=" + new Date().getTime();

  // Check if script loaded
  var exists = false;
  var scripts = ui('script');
  if(scripts != null){
    for(var i = 0 ; i < scripts.length ; i++)
      if(scripts[i].src.indexOf(src) >= 0){
        exists = true;
        break;
      }
  }

  exists = false;

  if(!exists){
    var script = ui.hc('script', {}, { src:src }, document.body);
    script.addEventListener("load", new Function(callback));
  }
  else{
    new Function(callback).call();
  }

}
ui.warn = function(message){
  if(console) console.warn(message);
  //if(navigator.userAgent.match(/iPad/i) != null) alert(message);
}
ui.isemptyarr = function(obj){
  for(var key in obj){
    var value = obj[key];
    switch(typeof value){
      case 'string': if(value.length > 0) return false; break;
      case 'number': if(!isNaN(value) && value != 0) return false; break;
      default: console.warn('unhandled isemptyarr type, ' + typeof value + ' = ' + value); break;
    }
  }
  return true;
}
ui.discount_calc = function(discount, total){

  discount = parseFloat(discount);
  if(isNaN(discount)) discount = 0;
  var discountamount = total * discount / 100;
  console.log(discountamount);
  return discountamount;

}
ui.el_in_viewport = function(el){

  if(!el || el == null || !el instanceof HTMLElement) return false;
  if(el instanceof Array) return false;

  var rect = el.getBoundingClientRect();
  return (
      rect.top >= 0 &&
      rect.left >= 0 &&
      rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) //&& /*or $(window).height() */
      /*rect.right <= (window.innerWidth || document.documentElement.clientWidth)*/ /*or $(window).width() */
  );

}
ui.class_toggle = function(el, className){

  if(el.classList.contains(className)) el.classList.remove(className);
  else el.classList.add(className);

}
ui.get_caret_position = function(el){
  var caretPos = 0,
    sel, range;
  if (window.getSelection) {
    sel = window.getSelection();
    if (sel.rangeCount) {
      range = sel.getRangeAt(0);
      if (range.commonAncestorContainer.parentNode == el) {
        caretPos = range.endOffset;
      }
    }
  } else if (document.selection && document.selection.createRange) {
    range = document.selection.createRange();
    if (range.parentElement() == el) {
      var tempEl = document.createElement("span");
      el.insertBefore(tempEl, el.firstChild);
      var tempRange = range.duplicate();
      tempRange.moveToElementText(tempEl);
      tempRange.setEndPoint("EndToEnd", range);
      caretPos = tempRange.text.length;
    }
  }
  return caretPos;

}
ui.set_caret_position = function(el, caretPos){
  var textnode = el.firstChild;

  if(window.getSelection) {
    sel = window.getSelection();
    if (sel.rangeCount) {
      range = sel.getRangeAt(0);

      if (range.commonAncestorContainer == el) {
        range.setStart(textnode, caretPos);
        range.setEnd(textnode, caretPos);
      }
      sel.removeAllRanges();
      sel.addRange(range);
    }
  }

}
ui.textcontent = function(el){

  if(!el || !(el instanceof HTMLElement)) return false;

  var text = [];
  for(var i = 0 ; i < el.childNodes.length ; i++){
    switch(el.childNodes[i].nodeType){
      case 1:
        if(el.childNodes[i].nodeName == 'BR')
          text.push("\n");
        break;
      case 3:
        text.push(el.childNodes[i].textContent);
        break;
    }
  }
  return text.join("");

}
ui.togglecss = function(targetEl, style, value){

  value = value.split('|');
  targetEl.style[style] = targetEl.style[style] == value[0] ? value[1] : value[0];

}

ui.__loaded = 0;
ui.__exec_callback = [];

ui.async_x = null;
ui.async_x_idle = true;
ui.async = function(method, params, options){
  if(options == null) options = {  };
  options['wait'] = 1;
  options['method'] = method;
  if(!ui.async_x_idle && ui.async_x_options.wait) return;
  if(!ui.async_x_idle && ui.async_x) ui.async_x.abort();
  ui.async_x_idle = false;

  if(options.type == 'put'){
    var file = params;
    var url = "?_async&_asyncm=" + method;
    var params = options['params'];
    for(var key in params){
      if(key == "clone") continue;
      url += "&" + key + "=" + params[key];
    }

    ui.async_x = new XMLHttpRequest();
    ui.async_x.addEventListener('load', ui.async_load, true);
    ui.async_x.open("PUT", url , true);
    ui.async_x.send(file);
    ui.async_x_idle = false;
    ui.async_x_options = options;
  }
  else{

    var urlqs = qs;
    urlqs['_async'] = 1;
    urlqs['_asyncm'] = method;

    var url = "?" + http_build_query(urlqs);
    if(options.params)
      for(var key in options.params)
        url += "&" + key + "=" + options.params[key];

    ui.async_x = new XMLHttpRequest();
    ui.async_x.addEventListener('abort', ui.async_abort, true);
    ui.async_x_data = [];
    ui.async_x.onreadystatechange = function(e){
      if(this.readyState == 3 && false){
        var this_response, response = e.currentTarget.response;
        if(this.last_response_len === false){
          this_response = response;
          this.last_response_len = response.length;
        }
        else{
          this_response = response.substring(this.last_response_len);
          this.last_response_len = response.length;
        }
        ui.async_x_data.push(this_response);

        var stripObj = ui.striphtml(this_response);
        for(var exp in stripObj.elements){
          if(ui(exp)) ui(exp).innerHTML = stripObj.elements[exp];
        }
        if(stripObj.script) eval(stripObj.script);

      }
      else if(this.readyState == 4){
        ui.async_load.call(this, e);
      }
    }
    ui.async_x.open("POST", url, true);
    ui.async_x.send(JSON.stringify(params));
    ui.async_x_options = options;
  }

  if(typeof options.waitel != 'undefined'){
    if(options.waitel instanceof HTMLElement) options.waitel = '$' + ui.uiid(options.waitel);
    ui.async_waitel(options.waitel);
  }

}
ui.async_abort = function(){
  if(typeof ui.async_x_options != "undefined" && typeof ui.async_x_options.onabort != "undefined")
    ui.eventcall(ui.async_x_options.onabort, { }, ui.async_x);

  if(typeof ui.async_x_options != "undefined" && typeof ui.async_x_options.oncomplete != "undefined")
    ui.eventcall(ui.async_x_options.oncomplete, { }, ui.async_x);

  if(typeof ui.async_x_options != "undefined" && typeof ui.async_x_options.waitel != 'undefined')
    ui.async_unwaitel(ui.async_x_options.waitel);
}
ui.async_load = function(e){
  if(ui.async_x.readyState == 4){

    if(typeof ui.async_x_options != "undefined" && typeof ui.async_x_options.waitel != 'undefined')
      ui.async_unwaitel(ui.async_x_options.waitel);

    if(ui.async_x.status == 200){
      var this_response, response = e.currentTarget.response;

      /*
      if(this.last_response_len === false)
        this_response = this.response;
      else
        this_response = response.substring(this.last_response_len);
      */

      this_response = this.response;

      //console.log(ui.async_x_data.join('').length + "|" + this_response.length);

      if(ui.async_x_data.join('').length != this_response.length){

        if(this_response.indexOf("<b>Fatal error</b>") >= 0){
          alert(this_response);
          ui.async_x_idle = true;
          return;
        }
        else if(this_response.indexOf("<b>Warning</b>") >= 0){
          alert(this_response);
          ui.async_x_idle = true;
          return;
        }

        var stripObj = ui.striphtml(this_response);
        for(var exp in stripObj.elements){
          if(ui(exp)) ui(exp).innerHTML = stripObj.elements[exp];
        }
        for(var exp in stripObj.elements2){
          if(ui(exp)) ui(exp).insertAdjacentHTML('beforeend', stripObj.elements2[exp]);
        }
        if(stripObj.script) eval(stripObj.script);

      }

      ui.async_x_idle = true;

      if(typeof ui.async_x_options != "undefined" && typeof ui.async_x_options.onload != "undefined")
        ui.eventcall(ui.async_x_options.onload, { obj:stripObj, text:this.responseText }, ui.async_x);

    }
    else{
      if(typeof ui.async_x_options != "undefined" && typeof ui.async_x_options.onerror != "undefined")
        ui.eventcall(ui.async_x_options.onerror, { error:this.responseText, message:this.responseText });
      else
        alert(this.responseText);
      stripObj = null;
    }

    $.ui_init();

    if(typeof ui.async_x_options != "undefined" && typeof ui.async_x_options.callback != "undefined")
      ui.eventcall(ui.async_x_options.callback, { obj:stripObj, text:this.responseText }, ui.async_x);

  }
}
ui.async_waitel = function(exp){
  if(exp instanceof Array){
    // TODO
  }
  else{
    var el = ui(exp);
    if(el){
      if(el.classList.contains('fa')){
        el.setAttribute("data-class", el.className);
        var has2x = el.classList.contains('fa-2x') ? 1 : 0;
        el.className = "fa fa-spinner fa-spin" + (has2x ? ' fa-2x' : '');
      }
      else if(el instanceof HTMLButtonElement)
        ui.button_wait(el);
      else if(el.classList.contains('textbox'))
        ui.textbox_wait(el);
      else if(el.hasAttribute("data-loadprogresscallback")){
        var loadprogresscallback = el.getAttribute("data-loadprogresscallback");
        eval(loadprogresscallback + "()");
      }
    }
  }
}
ui.async_unwaitel = function(exp){
  if(exp instanceof Array){
    // TODO
  }
  else{
    var el = ui(exp);
    if(el){
      if(el.classList.contains('fa')){
        el.className = el.getAttribute("data-class");
      }
      else if(el instanceof HTMLButtonElement)
        ui.button_unwait(el);
      else if(el.classList.contains('textbox'))
        ui.textbox_unwait(el);
    }
  }
}

ui.autocomplete = function(params){

	var name = ui.ov('name', $params);
	var src = ui.ov('src', $params);
	var width = ui.ov('width', $params);
  var value = ui.ov('value', $params);
  var text = ui.ov('text', $params, 0, $value);
  var placeholder = ui.ov('placeholder', $params);
	var onchange = ui.ov('onchange', $params);

	var c = "<span class='autocomplete' data-type='autocomplete' style='width:" + width + ";' data-name='" + name + "' data-src='" + src + "' data-onchange=\"" + onchange + "\" data-value=\"" + value + "\">";
  c += "<input type='text' onkeyup='ui.autocomplete_keyup.call(this, event)' value=\"" + text + "\" placeholder=\"" + placeholder + "\"/>";
  c += "<span class='fa fa-search'></span>";
  c += "<div class='popup off animated'></div>";
  c += "</span>";
	
	return c;
}
ui.autocomplete_keyup = function(e){
  var el = this.parentNode;
  if(this.value.length == 0)
    el.setAttribute("data-value", "");
  else
    window.setTimeout("ui.autocomplete_hint(ui('$" + ui.uiid(el) + "'), \"" + this.value + "\")", 400);
}
ui.autocomplete_hint = function(el, hint){

  if(hint.length > 2 && el.firstElementChild.value == hint){

    var src = el.getAttribute("data-src");
    var popup = el.lastElementChild;
    var params = [ ui.uiid(popup), src, hint ];

    var prehint = el.getAttribute("data-prehint");
    prehint = ui.eventcall(prehint, null, el);
    if(prehint instanceof Array){
      for(var i = 0 ; i < prehint.length ; i++)
        params.push(prehint[i]);
    }

    ui.async('ui_autocompleteitems', params, { onload:"ui.autocomplete_hintex(ui('$" + ui.uiid(el) + "'))",
      waitel:'$' + ui.uiid(el.firstElementChild.nextElementSibling) });

  }

}
ui.autocomplete_hintex = function(el){
  var popup = el.lastElementChild;
  var menuitems = ui('.menuitem', popup, true);
  if(menuitems){
    for(var i = 0 ; i < menuitems.length ; i++)
      menuitems[i].addEventListener('click', ui.autocomplete_menuitemclick, true);
    ui.popupopen(popup, el);
  }
}
ui.autocomplete_menuitemclick = function(e){
  var popup = this.parentNode;
  var el = popup.parentNode;
  var value = this.getAttribute("data-value");
  if(value != ''){
    el.firstElementChild.value = html_entity_decode(this.innerHTML);
    el.setAttribute("data-value", value);
  }
  popup.classList.add('off');
  var obj = eval("(" + this.getAttribute("data-obj") + ")");
  if(el.hasAttribute('data-onchange')){
    var name = el.hasAttribute("data-name") ? el.getAttribute("data-name") : '';
  	ui.eventcall(el.getAttribute('data-onchange'), { name:name, obj:obj, value:this.getAttribute("data-value"), text:this.innerHTML }, el);
  }
  ui.popupclose(popup);
}
ui.autocomplete_value = function(el){

  var any_text = parseInt(el.getAttribute("data-any-text"));
  var value = el.getAttribute("data-value");
  if(value == null || value == ''){
    if(any_text) value = ui('input', el).value;
  }
  return value;

}
ui.autocomplete_setvalue = function(el, value){

  ui('input', el).value = value;

}

ui.button_wait = function(el){

  var text = ui('label', el);

  if(ui('.fa', el) != null){
    var icon = ui('.fa', el);
    el.setAttribute("data-iconclass", icon.className);
    icon.className = "fa fa-spin fa-spinner";
  }
  else if(ui('.mdi', el) != null){
    el.style.opacity = .5;
  }
  if(text){
    el.setAttribute("data-text", text.innerHTML);
    //text.innerHTML = 'Please wait...';
  }

  el.disabled = true;
}
ui.button_unwait = function(el){
  if(typeof el == "undefined" || el == null || !el instanceof HTMLButtonElement) return;
  if(el != null && el.disabled){
    var icon = ui('.fa', el);
    var text = ui('label', el);

    if(icon){
      icon.className = el.getAttribute("data-iconclass");
    }
    else if(ui('.mdi', el) != null){
      el.style.opacity = '';
    }
    if(text){
      //text.innerHTML = el.getAttribute("data-text");
    }

    el.disabled = false;
  }
}
ui.button_setprogress = function(el, percentage){

  if(typeof el == 'undefined' || !(el instanceof HTMLElement)) return;

  var overlay_el = el.querySelector(".overlay");
  if(overlay_el == null){
    overlay_el = document.createElement("span");
    overlay_el.className = "overlay";
    el.insertBefore(overlay_el, el.firstElementChild);
  }
  if(percentage > 0 && percentage <= 100){
    overlay_el.style.opacity = 1;
    overlay_el.style.width = percentage + "%";
  }
  else{
    overlay_el.style.opacity = 0;
    overlay_el.style.width = "0";
  }

}

ui.chart_circles = function(el, points, style){

  var ctx = el.getContext('2d');
  if(style['fill']) ctx.fillStyle = style['fill'];
  if(style['stroke']) ctx.strokeStyle = style['stroke'];
  ctx.lineWidth = 3;
  console.warn(points.length);
  for(var i = 0 ; i < points.length ; i++){
    var point = points[i];
    var p = point.split(',');
    var x = p[0];
    var y = p[1];
    console.warn("draw " + x + ", " + y);
    ctx.beginPath();
    ctx.arc(x, y, 3, 0, Math.PI * 2);
    ctx.stroke();
    ctx.fill();
  }

}

ui.checkbox = function(params){

	var id = ui.ov('id', params);
  var name = ui.ov('name', params);
  var items = ui.ov('items', params);
  var itemwidth = ui.ov('itemwidth', params);
  var uid = uniqid();
  var width = ui.ov('width', params);
  var text = ui.ov('text', params);
  var onchange = ui.ov('onchange', params);
  var className = ui.ov('class', params, 0, 'checkbox');

  var c = '';
  c += "<span id='" + id + "' class='" + className + "' data-name='" + name + "' data-onchange=\"" + onchange + "\" data-type='checkbox' style='width:" + width + ";white-space: pre-wrap'>";
  if(items instanceof Array)
    for(var i = 0 ; i < count(items) ; i++){
      var item = items[$i];
      var text = ui.ov('text', item, 0, 'No text');
      var value = ui.ov('value', item);
      var uuid = 'c' + uid + i;

      c += "<span class='item' style='width:" + itemwidth + ";'>";
      c += "<input id='" + uuid + "' type='checkbox' value=\"" + value + "\"/>";
      c += "<label for='" + uuid + "'>" + text + "</label>";
      c += "</span>";
    }
  else{
    c += "<span class='item' style='width:" + itemwidth + ";'>";
    c += "<input id='" + uid + "' type='checkbox' onchange=\"ui.checkbox_onchange(event, this)\"/>";
    if(text.length > 0) c += "<label for='" + uid + "'>" + text + "</label>";
    c += "</span>";
  }

  c += "</span>";

  return c;

}
ui.checkbox_value = function(el){
  if(el.children.length > 1){
    var value = [];
    for(var i = 0 ; i < el.children.length ; i++){
      if(el.children[i].firstElementChild.checked)
        value.push(el.children[i].firstElementChild.value);
    }
    return value.join(',');
  }
  else{
    return el.firstElementChild.firstElementChild.checked ? 1 : 0;
  }
}
ui.checkbox_setvalue = function(el, value){

  if(el.children.length > 1){
    ui.warn('Multi items value not implemented');
  }
  else{
    el.firstElementChild.firstElementChild.checked = value ? true : false;
  }

}
ui.checkbox_onchange = function(e, input){
  var el = input.parentNode.parentNode;

  if(el.children.length > 1){
    ui.warn('Multi items value not implemented');
  }
  else{
    if(el.hasAttribute("data-onchange")){
      var name = el.hasAttribute("data-name") ? el.getAttribute("data-name") : '';
      ui.eventcall(el.getAttribute("data-onchange"), { name:name, value:input.checked ? 1 : 0}, el);
    }
  }

}

ui.codeeditor_keydown = function(e, pre){
  if(e.keyCode == 9){
    e.preventDefault();

    var pos = ui.get_caret_position(pre);
    var text = ui.textcontent(pre);
    console.log(JSON.stringify(text));
    text = text.substr(0, pos) + "\t" + text.substr(pos);
    pre.textContent = text;
    ui.set_caret_position(pre, pos + 1);
  }
}
ui.codeeditor_keyup = function(e, pre){

}
ui.codeeditor_value = function(el){
  return el.textContent;
}
ui.codeeditor_setvalue = function(el, value){
  el.textContent = value;
}

ui.container_value = function(el, includechild){
  var obj = {};
  var els = el.querySelectorAll("*[data-name]");

  for(var i = 0 ; i < els.length ; i++){
    var el = els[i];
    var name = el.getAttribute("data-name");
    var ischild = el.getAttribute("data-ischild");
    if(ischild == 1 && !includechild) continue;

    if(el.classList.contains('textbox')) obj[name] = ui.textbox_value(el);
    else if(el.classList.contains('hidden')) obj[name] = el.value;
    else if(el.classList.contains('autocomplete')) obj[name] = ui.autocomplete_value(el);
    else if(el.classList.contains('multicomplete')) obj[name] = ui.multicomplete_value(el);
    else if(el.classList.contains('checkbox')) obj[name] = ui.checkbox_value(el);
    else if(el.classList.contains('radio')) obj[name] = ui.radio_value(el);
    else if(el.classList.contains('dayselector')) obj[name] = ui.dayselector_value(el);
    else if(el.classList.contains('dropdown')) obj[name] = ui.dropdown_value(el);
    else if(el.classList.contains('textarea')) obj[name] = ui.textarea_value(el);
    else if(el.classList.contains('datepicker')) obj[name] = ui.datepicker_value(el);
    else if(el.classList.contains('toggler')) obj[name] = ui.toggler_value(el);
    else if(el.getAttribute("data-type") == 'upload') obj[name] = ui.upload_value(el);
    else{

      if(el.hasAttribute("data-type")){
        var type = el.getAttribute("data-type");
        if(eval("typeof ui." + type + "_value") == 'function'){
          var f = eval("ui." + type + "_value");
          obj[name] = f(el);
        }
      }

    }
  }
  return obj;
}
ui.container_setvalue = function(el, obj, objtoel){

  if(objtoel){
    for(var name in obj){
      var iel = el.querySelector("*[data-name='" + name + "']");
      if(iel){
        if(!iel.hasAttribute('data-type')){ ui.warn("Control with no data-type property. (" + name + ")"); continue;}
        var type = iel.getAttribute("data-type");
        var ischild = el.getAttribute("data-ischild");
        if(ischild && !includechild) continue;

        if(eval("typeof ui." + type + "_setvalue") == 'function'){
          var f = eval("ui." + type + "_setvalue");
          f(iel, ui.ov(name, obj));
        }
        else{
          ui.warn('Function not exists, ' + type + "_setvalue");
        }
      }
    }
  }
  else{
    var iels = el.querySelectorAll("*[data-name]");

    for(var i = 0 ; i < iels.length ; i++){
      var iel = iels[i];
      if(!iel.hasAttribute('data-name')) continue;
      var name = iel.getAttribute("data-name");
      if(name.length == 0) continue;
      if(!iel.hasAttribute('data-type')){ ui.warn("Control with no data-type property. (" + name + ")"); continue;}

      var type = iel.getAttribute("data-type");
      if(eval("typeof ui." + type + "_setvalue") == 'function'){
        var f = eval("ui." + type + "_setvalue");
        f(iel, ui.ov(name, obj));
      }
      else{
        ui.warn('Function not exists, ' + type + "_setvalue");
      }
    }
  }

}

ui.control_value = function(el){
  if(el == null){ ui.warn('Invalid el parameter.'); return; }
  if(typeof el.hasAttribute == 'undefined'){ return; }
  if(!el.hasAttribute('data-type')){ ui.warn("Control with no data-type property. (" + name + ")"); return;}

  var type = el.getAttribute("data-type");
  if(eval("typeof ui." + type + "_value") == 'function'){
    var f = eval("ui." + type + "_value");
    return f(el);
  }
  else{
    ui.warn('Function not exists, ' + type + "_value");
  }
}
ui.control_setvalue = function(el, value){

  if(!el || !(el instanceof HTMLElement)){ ui.warn('[control_setvalue] Invalid el'); return; }
  if(!el.hasAttribute('data-type')){ ui.warn('[control_setvalue] El without data-type attribute.'); return; }

  var type = el.getAttribute("data-type");
  if(eval("typeof ui." + type + "_setvalue") == 'function'){
    var f = eval("ui." + type + "_setvalue");
    f(el, value);
  }
  else{
    ui.warn('[control_setvalue] ')
  }

}
ui.control_setproperty = function(el, name, value){
  if(!el.hasAttribute('data-type')){ ui.warn("Control with no data-type property. (" + name + ")"); return;}

  var type = el.getAttribute("data-type");
  var funcname = "ui." + type + "_set" + name;
  if(eval("typeof " + funcname) == 'function'){
    var f = eval(funcname);
    f(el, value);
  }
  else{
    ui.warn('Function not exists, ' + funcname);
  }
}
ui.control_properties = function(el){
  var params = el.dataset;
  var type = el.getAttribute("data-type");

  if(parseInt(el.style.height)) params['height'] = el.style.height;
  if(parseInt(el.style.width)) params['width'] = el.style.width;
  if(eval("typeof ui." + type + "_value") == 'function') params['value'] = eval("ui." + type + "_value")(el);
  if(eval("typeof ui." + type + "_placeholder") == 'function') params['placeholder'] = eval("ui." + type + "_placeholder")(el);

  return params;

  var type = el.getAttribute("data-type");
  var func = "ui." + type + "_properties";
  if(eval("typeof " + func) == 'function'){
    var f = eval(func);
    return f(el);
  }
  else
    ui.warn("Function not exists. (" + func + ")");
}
ui.control_getphptag = function(el){

  var params = ui.control_properties(el);

  var c = "<" + "?=" + "ui_" + params.type + "(array(";
  var c1 = [];
  for(var key in params){
    c1.push("\"" + key + "\"=>\"" + params[key] + "\"");
  }
  c += c1.join(", ");
  c += "))" + "?" + ">";
  return c.replace(/</gi, "&lt;").replace(/>/gi, "&gt");
}
ui.control_gethtmltag = function(el){
  if(el.parentNode) return el.parentNode.innerHTML.replace(/</gi, "&lt;").replace(/>/gi, "&gt");
  return '';
}

ui.datepicker = function(params){
  var idchild = ui.ov('ischild', params);
  var name = ui.ov('name', params);
  var readonly = ui.ov('readonly', params);
  var width = ui.ov('width', params);
  var value = ui.ov('value', params);
  var onchange = ui.ov('onchange', params);
  var hidden = ui.ov('hidden', params, 0, 0);

  var ischildExp = idchild ? "data-ischild='1'" : "";
  var readonlyExp = readonly ? 'readonly' : '';
  var valueExp = value.length > 0 ? formatDate(getDateFromFormat(value, 'yyyyMMdd'), 'd NNN yyyy') : formatDate(new Date(), 'd NNN yyyy'); //strtotime(value) ? date('j M Y', strtotime($value)) : date('j M Y');
  var hiddenExp = hidden ? 'off' : '';

  var c = "<span class='datepicker " + (readonlyExp + " " + hiddenExp) + "' data-type='datepicker' data-onchange=\"" + onchange + "\" data-name='" + name + "' onclick=\"ui.datepicker_openselector(event, this)\" " + ischildExp + ">";
  c += "<label type='text' style='width:" + width + ";' " + readonlyExp + ">" + valueExp + "</label>";
  c += "<span class='fa fa-calendar'></span>";
  c += "</span>";
  return c;
}
ui.datepicker_openselector = function(e, el){
  if(typeof ui.datepicker_selector == 'undefined')
    ui.datepicker_selector = ui.hc("div", null, { className:"datepicker popup off animated" }, ui('.screen'));
  if(el.classList.contains('readonly')) return;

  var d = getDateFromFormat(el.firstElementChild.innerHTML, 'd NNN yyyy');
  if(d == 0) d = new Date();
  ui.datepicker_selector_content(d, el, d);

  ui.popupopen(ui.datepicker_selector, el, { width:240 });
}
ui.datepicker_selector_content = function(d, el, current_d){

  var d1 = getStartDayOfMonth(d.getMonth(), d.getFullYear());
  var d2 = getEndDateOfMonth(d.getMonth(), d.getFullYear());
  var d3 = [ 'Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa' ];
  var d4 = 1;
  var d5 = ui.uiid(el);
  var d8 = new Date(d.getTime());
  var d9 = new Date(d.getTime());

  d8.setFullYear(d8.getFullYear(), d8.getMonth() - 1, 1);
  d9.setFullYear(d9.getFullYear(), d9.getMonth() + 1, 1);

  var c = "";

  c += "<table class='datenav' width='100%'><tr>";
  c += "<td><span class='fa fa-chevron-left' onclick=\"ui.datepicker_selector_content(new Date(" + d8.getTime() + "), ui('$" + ui.uiid(el) + "'), new Date(" + current_d.getTime() + "));event.preventDefault();event.stopPropagation()\"></span></td>";
  c += "<td style='width:100%;text-align: center'><h1>" + formatDate(d, 'MMM yyyy') + "</h1></td>";
  c += "<td><span class='fa fa-chevron-right' onclick=\"ui.datepicker_selector_content(new Date(" + d9.getTime() + "), ui('$" + ui.uiid(el) + "'), new Date(" + current_d.getTime() + "));event.preventDefault();event.stopPropagation()\"></span></td>";
  c += "</tr></table>";
  c += "<div class='height5'></div>";
  c += "<table class='datetable' cellspacing='0' width='100%'>";
  c += "<thead><tr>";
  for(var i = 0 ; i < d3.length ; i++)
    c += "<th>" + d3[i] + "</th>";
  c += "</tr><tr><th><div class='height5'></div></th></tr></thead>";
  c += "<tbody>";
  for(var i = 0 ; i < 6 ; i++){
    c += "<tr>";
    for(var j = 0 ; j < 7 ; j++){
      c += "<td>";
      if(d1 > 0) d1--;
      else if(d4 > d2);
      else{
        var d7 = new Date(d.getTime());
        d7.setDate(d4);
        var d6 = formatDate(d7, 'yyyyMMdd');
        var active = formatDate(current_d, 'yyyyMMdd') == d6 ? ' active' : '';
        var day_color = j == 0 ? ' red' : (j == 6 ? ' green' : '');

        c += "<label onclick=\"ui.datepicker_ondateselect(ui('$" + d5 + "'), '" + d6 + "');\" class='day" + active + day_color + "'>" + d4 + "</label>";
        d4++;
      }
      c += "</td>";
    }
    c += "</tr>";
  }
  c += "</tbody>";
  c += "<tfoot><tr><td><div class='height10'></div></td></tr><tr><td colspan='7' style='text-align:left'><label class='misc' onclick=\"ui.datepicker_ondateselect(ui('$" + d5 + "'), '" + (formatDate(new Date(), 'yyyyMMdd')) + "');\">Today</label></td></tr></tfoot>";
  c += "</table>";
  ui.datepicker_selector.innerHTML = c;
}
ui.datepicker_value = function(el){
  var d = getDateFromFormat(el.firstElementChild.innerHTML, 'd NNN yyyy');
  return d > 0 ? formatDate(getDateFromFormat(el.firstElementChild.innerHTML, 'd NNN yyyy'), 'yyyyMMdd') : '';
}
ui.datepicker_setvalue = function(el, value, options){

  var supportedformat = [ 'yyyyMMdd', 'yyyy-MM-dd' ];
  for(var i = 0 ; i < supportedformat.length ; i++){
    var d = getDateFromFormat(value, supportedformat[i]);
    if(d != 0){
      value = formatDate(d, 'yyyyMMdd');
      break;
    }
  }
  if(d == 0){
    if(ui.ov('suppresserror', options))
      throw '[datepicker_setvalue] Unsupported value (' + value + ')';
    else
      ui.warn('[datepicker_setvalue] Unsupported value (' + value + ')');
    return;
  }

  ui.datepicker_ondateselect(el, value);
}
ui.datepicker_ondateselect = function(el, date){
  var text = formatDate(getDateFromFormat(date, 'yyyyMMdd'), 'd NNN yyyy');
  el.firstElementChild.innerHTML = text;
  if(typeof ui.datepicker_selector != 'undefined') ui.popupclose(ui.datepicker_selector);
  var name = el.hasAttribute("data-name") ? el.getAttribute("data-name") : '';
  ui.eventcall(el.getAttribute("data-onchange"), { name:name, value:date, text:text }, el);
}

ui.dayselector = function(params){

  var name = ui.ov('name', params);
  var id = ui.ov('id', params);
  var uid = uniqid();
  var days = [
    { text:'Sun', value:'sun' },
    { text:'Mon', value:'mon' },
    { text:'Tue', value:'tue' },
    { text:'Wed', value:'wed' },
    { text:'Thu', value:'thu' },
    { text:'Fri', value:'fri' },
    { text:'Sat', value:'sat' }
  ];
  var width = ui.ov('width', params);
  var value = ui.ov('value', params);

  var c = '';
  c += "<span class='dayselector' data-type='dayselector' data-name='" + name + "' id='" + id + "' style='width:" + width + ";'>";
  for(var i = 0 ; i < days.length ; i++){
    var uuid = uid + i;
    var itemvalue = days[i]['value'];
    var checked = itemvalue.indexOf(value) >= 0 ? true : false;

    c += "<span class='day'>";
    c += "<input id='" + uuid + "' type='checkbox' value='" + itemvalue + "' " + (checked ? ' checked' : '') + "/>";
    c += "<label for='" + uuid + "'>" + days[i]['text'] + "</label>";
    c += "</span>";
  }
  c += "</span>";

  return c;
}

ui.dayselector_value = function(el){
  var value = [];
  for(var i = 0 ; i < el.children.length ; i++)
    if(el.children[i].firstElementChild.checked) value.push(el.children[i].firstElementChild.value);
  return value.join(',');
}
ui.dayselector_setvalue = function(el, value){
  value = value.split(',');
  for(var i = 0 ; i < el.children.length ; i++){
    var item = el.children[i];
    var val = item.firstElementChild.value;
    item.firstElementChild.checked = value.indexOf(val) >= 0 ? true : false;
  }
}

ui.dropdown = function(params){

  var id = ui.ov('id', params);
  var align = ui.ov('align', params);
  var name = ui.ov('name', params);
  var src = ui.ov('src', params);
  var items = ui.ov('items', params);
  var width = ui.ov('width', params);
  var value = ui.ov('value', params);
  var className = ui.ov('class', params, 0, 'dropdown');
  var placeholder = ui.ov('placeholder', params, 0, '-Select-');
  var onchange = ui.ov('onchange', params);

  var selecteditem = null;
  if(value.length > 0 && items instanceof Array){
    for(var i = 0 ; i < items.length ; i++){
      if(items[i]['value'] == value){
        selecteditem = items[i];
        break;
      }
    }
  }

  var c = "<span id='" + id + "' class='" + className + "' data-name='" + name + "' data-type='dropdown' data-src='" + src + "' data-onchange=\"" + onchange + "\" data-items=\"" + htmlentities(JSON.stringify(items)) + "\"";
  c += "style=\"width:" + width + ";\" data-value=\""  + (selecteditem != null ? htmlentities(selecteditem['value']) : '') + "\">";
  c += "<label onclick='ui.dropdown_open(this.parentNode, event)' style='text-align:" + align + "'>" + (selecteditem != null ? selecteditem['text'] : placeholder) +  "</label>";
  c += "<span onclick='ui.dropdown_open(this.parentNode, event);' class='fa fa-caret-down'></span>";
  c += "<div class='popup off animated'>";

  if(items instanceof Array)
    for(var i = 0 ; i < items.length ; i++){
      var item = items[i];
      var text = item['text'];
      var value = item['value'];
      c += "<div class='menuitem' data-value=\"" + value + "\">" + text + "</div>";
    }

  c += "</div></span>";
  return c;

}
ui.dropdown_open = function(el, e){
  if(el.classList.contains('readonly')) return;
  if(el.hasAttribute('data-src') && el.getAttribute('data-src').length > 0){
    var popup = el.querySelector(".popup");
    ui.async('ui_dropdownitems', [ ui.uiid(popup), el.getAttribute('data-src') ], { onload:"ui.dropdown_openex(ui('$" + ui.uiid(el) + "'))",
      waitel:'$' + ui.uiid(el.firstElementChild.nextElementSibling) });
  }
  else{
    ui.dropdown_openex(el);
  }
  e.preventDefault();
  e.stopPropagation();
  return false;
}
ui.dropdown_openex = function(el){
  var popup = el.lastElementChild;
  if(popup.classList.contains('off')){
    var menuitems = ui('.menuitem', popup, true);
    if(menuitems){
      for(var i = 0 ; i < menuitems.length ; i++)
        menuitems[i].addEventListener('click', ui.dropdown_menuitemclick, true);
      ui.popupopen(popup, el);
    }
  }
  else{

  }
}
ui.dropdown_menuitemclick = function(e){
  var popup = this.parentNode;
  var el = popup.parentNode;
  el.firstElementChild.innerHTML = this.innerHTML;
  el.setAttribute("data-value", this.getAttribute("data-value"));
  popup.classList.add('off');

  var name = el.hasAttribute("data-name") ? el.getAttribute("data-name") : '';
  ui.eventcall(el.getAttribute("data-onchange"), { name:name, value:el.getAttribute("data-value"), text:this.innerHTML }, el);
  ui.popupclose(popup);
}
ui.dropdown_value = function(el){
  return el.getAttribute("data-value");
}
ui.dropdown_setvalue = function(el, obj){
  if(obj == null){
    el.setAttribute("data-value", '');
    el.firstElementChild.innerHTML = '';
  }
  else{
    if(el.getAttribute("data-items").length > 0){
      var items = eval("(" + html_entity_decode(el.getAttribute("data-items")) + ")");
      var item = null;
      for(var i = 0 ; i < items.length ; i++)
        if(items[i]['value'] == obj){
          item = items[i];
          break;
        }
      if(item != null){
        el.setAttribute("data-value", item['value']);
        el.firstElementChild.innerHTML = item['text'];
      }
    }
    else{
      el.setAttribute("data-value", obj['value']);
      el.firstElementChild.innerHTML = obj['text'];
    }
  }
}
ui.dropdown_setwidth = function(el, width){
  el.style.width = width;
}
ui.dropdown_handlecursor = function(e, el){

  var popup = el.querySelector('.popup');
  var text = el.firstElementChild.nextElementSibling.innerHTML;
  var value = el.getAttribute("data-value");

  // Get current idx
  var items = el.hasAttribute("data-items") ? eval("(" + html_entity_decode(el.getAttribute("data-items")) + ")") : [];
  var idx = -1;
  for(var i = 0 ; i < items.length ; i++){
    if(items[i].text == text){
      idx = i;
      break;
    }
  }

  // Open popup
  if(popup.classList.contains('off'))
    ui.popupopen(popup, el);

  if(popup && idx != -1){
    switch(e.keyCode){
      case 40:
        idx = idx + 1 < items.length ? idx + 1 : 0;
        break;
      case 38:
        idx = idx - 1 >= 0 ? idx - 1 : items.length - 1;
        break;
    }
  }
  ui.dropdown_setvalue(el, items[idx].value);



}
ui.dropdown_onitemmouseover = function(e, menuitem){

}
ui.dropdown_onitemmouseout = function(e, menuitem){

}

ui.grid_store = {};
ui.grid_more_nextfetch = 0;
ui.grid_onrowclick = function(e, tr){
  var tbody = ui.fp('grid', tr);
  var active_tr = ui('.active', tbody);
  if(active_tr != null && typeof active_tr.classList != 'undefined') active_tr.classList.remove('active');
  tr.classList.add('active');

  if(tr.hasAttribute("data-onclick"))
    ui.eventcall(tr.getAttribute("data-onclick"), { id:ui.ov('id', tr.dataset), event:e }, tr);
}
ui.gridloadmorecomplete = function(tbodyid){
  var tbody = ui('#' + tbodyid);
  var loadmores = ui('.loadmore', tbody);
  if(loadmores.length > 1){
    for(var i = 0 ; i < loadmores.length - 1 ; i++)
      tbody.removeChild(loadmores[i]);
  }
}
ui.grid_more = function(e){
  var target = e.target;
  var gridid = target.getAttribute("data-gridid");
  if(typeof ui.grid_store[gridid] != 'undefined' && ui.grid_store[gridid]['fetch'] != 1 &&
    new Date().getTime() > ui.grid_more_nextfetch){
    window.setTimeout("ui.grid_moreex('" + gridid + "')", 300);
    ui.grid_store[gridid]['fetch'] = 1
    ui.grid_more_nextfetch = new Date().getTime() + 1000;
  }
  //ui.warn('ui.grid_more, target: ' + target + ', gridid: ' + gridid);
}
ui.grid_moreex = function(gridid){
  var grid_partitionsize = ui.grid_store[gridid]['maxpagepercache'];
  ui.grid_store[gridid]['pageidx']++;

  ui.grid_store[gridid]['cacheidx'] = Math.floor(ui.grid_store[gridid]['pageidx'] / grid_partitionsize);

  //console.warn('pageidx: ' + ui.grid_store[gridid]['pageidx'] + ', cacheidx: ' + ui.grid_store[gridid]['cacheidx']);

  ui.async('ui_gridmore', [ ui.grid_store[gridid] ], { wait:true });
  ui.grid_store[gridid]['fetch'] = 0;
}
ui.grid_add = function(el, singleEmptyRow){
  var template = ui.grid_store[el['id']]['template'];
  var tbody = el.firstElementChild.lastElementChild;
  if(singleEmptyRow){
    var lasttr = tbody.lastElementChild;
    if(lasttr.className == 'newrowopt') lasttr = lasttr.previousElementSibling;
    if(lasttr){
      var obj = ui.container_value(lasttr);
      if(!ui.isemptyarr(obj))
        tbody.insertAdjacentHTML('beforeend', template);
    }
  }
  else{
    tbody.insertAdjacentHTML('beforeend', template);
  }

  var newrowopt = ui('.newrowopt', tbody);
  if(newrowopt)
    tbody.insertBefore(newrowopt, null);
}
ui.grid_add_bytrs = function(el, trs){

  var tbody = ui('tbody', el);

  var nodatainfo = ui('.nodatainfo', tbody);
  if(nodatainfo != null) tbody.removeChild(nodatainfo);

  tbody.insertAdjacentHTML('beforeend', trs.join(''));

  var newrowopt = ui('.newrowopt', tbody);
  if(newrowopt) tbody.appendChild(newrowopt);

}
ui.grid_value = function(el){

  var tbody = el.querySelector('tbody');
  var arr = [];
  for(var i = 0 ; i < tbody.children.length ; i++){
    var tr = tbody.children[i];
    var obj = ui.container_value(tr, 1);
    if(ui.isemptyarr(obj)) continue;
    arr.push(obj);
  }
  return arr;

}
ui.grid_remove = function(tr){

  var onremove = $(tr).closest('.grid').attr('data-onremove');
  $(tr).remove();
  if(onremove != null){
    var f = new Function(onremove);
    f();
  }

}
ui.grid_setvalue = function(el, items){

  var tbody = el.firstElementChild.lastElementChild;
  tbody.innerHTML = '';
  for(var i = 0 ; i < items.length ; i++){

  }

}
ui.grid_selectedid = function(el){

  var tbody = el.querySelector('tbody');
  var active_tr = tbody.querySelector("tr.active");
  return active_tr != null ? active_tr.getAttribute("data-id") : null;

}

ui.gridhead_onresizestart = function(e, th){
  ui.gridhead_resize_th = th;
  ui.gridhead_resize_x = e.clientX;
  window.addEventListener('mousemove', ui.gridhead_onresize);
  window.addEventListener('mouseup', ui.gridhead_onresizeend);
}
ui.gridhead_onresize = function(e){
  var distance_x = e.clientX - ui.gridhead_resize_x;
  ui.gridhead_resize_th.previousElementSibling.style.width = parseInt(ui.gridhead_resize_th.previousElementSibling.style.width) + distance_x;
  ui.gridhead_resize_x = e.clientX;
}
ui.gridhead_onresizeend = function(e){
  var el = ui.gridhead_resize_th.parentNode.parentNode.parentNode;
  var gridid = el.getAttribute("data-gridid");
  var grid = ui(gridid);

  var theadtr = ui.gridhead_resize_th.parentNode;
  var idx = -1;
  for(var i = 0 ; i < theadtr.children.length ; i++)
    if(theadtr.children[i] == ui.gridhead_resize_th){
      idx = i - 1;
      break;
    }

  if(grid){
    if(grid.firstElementChild.firstElementChild.children.length > 0){
      if(idx != -1){
        var tbody = grid.firstElementChild.firstElementChild;
        var tr = tbody.firstElementChild;
        tr.children[idx].style.width = theadtr.children[idx].style.width;
      }
    }
  }

  if(el.hasAttribute('data-oncolumnresize') && el.getAttribute('data-oncolumnresize').length > 0){
    var name = theadtr.children[idx].getAttribute("data-columnname");
    var width = parseInt(theadtr.children[idx].style.width);
    ui.async(el.getAttribute('data-oncolumnresize'), [ name, width ], {});
  }

  delete(ui.gridhead_resize_th);
  delete(ui.gridhead_resize_x = e.clientX);
  window.removeEventListener('mousemove', ui.gridhead_onresize);
  window.removeEventListener('mouseup', ui.gridhead_onresizeend);
}
ui.gridhead_oncolumnclick = function(e, th){
  var name = th.getAttribute("data-columnname");
  if(name.length > 0){
    var el = th.parentNode.parentNode.parentNode;
    if(el.hasAttribute("data-oncolumnclick") && el.getAttribute("data-oncolumnclick").length > 0){
      ui.async(el.getAttribute("data-oncolumnclick"), [ name ], {});
    }
  }
}
ui.gridhead_oncontextmenu = function(e, th){
  var el = th.parentNode.parentNode.parentNode;
  if(el.hasAttribute("data-oncolumnapply") && el.getAttribute("data-oncolumnapply").length > 0){
    var name = th.getAttribute("data-columnname");

    var thead = th.parentNode;
    var popup = ui('.popup', thead);

    var columns = [];
    var cols = ui('*[data-columnname]', thead);
    for(var i = 0 ; i < cols.length ; i++)
      columns.push(cols[i].getAttribute("data-columnname"));

    var checkboxes = ui('input', popup);
    for(var i = 0 ; i < checkboxes.length ; i++){
      var checkbox = checkboxes[i];
      var name = checkbox.value;
      checkbox.checked = in_array(name, columns) ? true : false;
    }

    ui.popupopen(popup, th);

    e.stopPropagation();
    e.preventDefault();
    return false;
  }
}
ui.gridhead_oncolumnapply = function(e, button){
  var popup = button.parentNode.parentNode;
  var el = popup.parentNode.parentNode.parentNode.parentNode;

  if(el.hasAttribute("data-oncolumnapply") && el.getAttribute("data-oncolumnapply").length > 0){
    var columns = [];
    var checkboxes = ui('input', popup);
    for(var i = 0 ; i < checkboxes.length ; i++){
      var checkbox = checkboxes[i];
      var name = checkbox.value;
      columns.push({ name:name, active:checkbox.checked ? 1 : 0 });
    }
    ui.async(el.getAttribute("data-oncolumnapply"), [ columns ], { waitel:button });
  }

}

ui.grid_onscroll = function(e){

  console.log([ this.getAttribute("data-gridhead"), $(this.getAttribute("data-gridhead")).length ]);
  $(this.getAttribute("data-gridhead")).parent().scrollLeft($(this).scrollLeft());

}

ui.grid2moredata = {};
ui.grid2_more = function(id, scrollel){

  if(typeof ui.grid2moredata[id] != 'undefined'){

    // Scroll down only
    //if(typeof ui.grid2moredata[id]['scrolltop'] == 'undefined') ui.grid2moredata[id]['scrolltop'] = 0;
    //if(scrollel.scrollTop > ui.grid2moredata[id]['scrolltop']){

      //ui.grid2moredata[id]['scrolltop'] = scrollel.scrollTop;
      if(!ui.grid2moredata[id]['onfetch'] && ui.grid2moredata[id]['moredata'] > 0){
        if(ui.grid2moredata[id]['mode'] == 'group'){
          // TODO
        }
        else{
          ui.async('ui_grid2_more', [ ui.grid2moredata[id] ], { onload:"ui.grid2_moreresult('" + id + "', text)" });
          ui.grid2moredata[id]['lastfetch'] = new Date().getTime();
          ui.grid2moredata[id]['onfetch'] = 1;
        }
      }

    //}

  }

}
ui.grid2_moreresult = function(id, response){

  if(ui.grid2moredata[id]['mode'] == 'group'){

  }
  else{
    var el = ui('#' + id);
    if(el){
      var tbody = el.querySelector('tbody');
      tbody.insertAdjacentHTML('beforeend', response);
      tbody.appendChild(tbody.querySelector('.tr-loadmore'));
      tbody.querySelector('.tr-loadmore').firstElementChild.firstElementChild.innerHTML = 'Load more';

      if(ui.grid2moredata[id]['moredata'] == 0)
        tbody.removeChild(tbody.querySelector('.tr-loadmore'));
    }
  }

}
ui.grid2_more2 = function(id){
  var tr = ui.grid2moredataex[id];
  if(ui.el_in_viewport(tr)){
    tr.firstElementChild.firstElementChild.innerHTML = 'Loading...';
    tr.click();
  }

}
ui.grid2init = function(id){

  if(typeof ui.grid2moredata[id] != 'undefined'){

    var griddata = ui.grid2moredata[id];
    var el = ui('#' + id);
    if(typeof ui.grid2moredataex == 'undefined') ui.grid2moredataex = {};
    ui.grid2moredataex[id] = ui('.tr-loadmore', el);

    // Initialize scrollel
    var scrollel = ui(griddata['scrollel']);
    $(scrollel).on('scroll', function(){
      ui.grid2_more2(id);
      //ui.grid2_more(griddata['id'], scrollel);
    });

    ui.grid2moredata[id]['lastfetch'] = new Date().getTime();

  }

}

ui.grid3_expand = function(subparams, caret_sect_id, caret_body_id){

  if(this.classList.contains('fa-caret-right')){
    ui.async('ui_grid3', [ subparams ], { onload:function(){
      $(caret_sect_id).removeClass('fa-caret-right fa-spinner fa-spin').addClass('fa-caret-down');
      $(caret_body_id).show();
    }});
    $(this).removeClass('fa-caret-right').addClass('fa fa-spinner fa-spin');
  }
  else{
    $(caret_sect_id).toggleClass('fa-caret-right', 'fa-caret-down');
    $(caret_body_id).hide();
  }

}

ui.groupgridsecthead_click = function(e, groupgridsecthead){
  var groupgridsectbody = groupgridsecthead.nextElementSibling;
  var groupgridsectcont = groupgridsecthead.parentNode;

  if(e.metaKey || e.ctrlKey || e.altKey){
    var groupgridsectcontcont = groupgridsectcont.parentNode;
    var state = groupgridsectbody.classList.contains('off') ? 1 : 0;
    for(var i = 0 ; i < groupgridsectcontcont.children.length ; i++)
      ui.groupgridsectcont_toggle(groupgridsectcontcont.children[i], state);
  }
  else{
    ui.groupgridsectcont_toggle(groupgridsecthead.parentNode, groupgridsectbody.classList.contains('off') ? 1 : 0)
  }
}
ui.groupgrid_trloadmoreclick = function(e, tr){
  var el = ui.fp('grid', tr);
  var id = el.id;
  var cacheid = tr.getAttribute("data-cacheid");

  ui.async('ui_groupgrid_loadcache', [ id, cacheid ], { onload:"ui.groupgrid_trloadmore('" + ui.uiid(tr) + "', obj, this)" });
}
ui.groupgrid_trloadmoreclick2 = function(e, tr, name, value){

  var el = ui.fp('grid', tr);
  var id = el.id;
  var tbody = tr.parentNode;
  var pageindex = tbody.children.length - 1;

  ui.async('ui_groupgrid_loadfromcacheds', [ id, name, value, pageindex ], { onload:"ui.groupgrid_trloadmore('" + ui.uiid(tr) + "', obj, this)" });


}
ui.groupgrid_trloadmore = function(cacheid, obj, el){
  var tr = ui("$" + cacheid);
  var tbody = tr.parentNode;
  tbody.insertAdjacentHTML('beforeend', obj.elements['null']);

  if(remaining > 0) tbody.appendChild(tr);
  else tbody.removeChild(tr);
}
ui.groupgridsectcont_toggle = function(cont, state){
  var groupgridsecthead = cont.firstElementChild;
  var groupgridsectbody = cont.lastElementChild;

  if(state){
    groupgridsectbody.classList.remove('off');
  }
  else{
    groupgridsectbody.classList.add('off');
  }

  groupgridsecthead.firstElementChild.firstElementChild.firstElementChild.firstElementChild.firstElementChild.className = groupgridsectbody.classList.contains('off') ? 'fa fa-caret-right' : 'fa fa-caret-down';
}
ui.groupgridrowclick = function(e, tr){

  var el = tr;
  do{
    el = el.parentNode;
    if(el.classList.contains('groupgrid')) break;
  }
  while(true);
  console.log(el);

  var active_tr = ui('.active', el);
  if(active_tr) active_tr.classList.remove('active');
  tr.classList.add('active');
}
ui.groupgridsql_loadcache = function(div, type, cacheid){

  ui.async('ui_groupgridsql_loadcache', [ type, cacheid ], { callback:"ui.groupgridsql_loadcache_ex('" + type + "', ui('$" + ui.uiid(div) + "'))" });

}
ui.groupgridsql_loadcache_ex = function(type, div){

  var c = result.c;
  var cont = div.parentNode;
  cont.insertAdjacentHTML('beforeend', c);
  if(result.more > 0)
    cont.insertBefore(div, null);
  else
    cont.removeChild(div);

}
ui.groupgrid_loadmore = function(id, cacheid, type){
  ui.async('ui_groupgrid_loadmore', [ id, cacheid, type, ui.grid2moredata[id]['caches'] ], { callback:"ui.groupgrid_loadmore_response('" + id + "', '" + cacheid + "', text)" });
}
ui.groupgrid_loadmore_response = function(id, cacheid, text){

  var el_loadmore = ui("*[data-cacheid='" + cacheid + "']");
  var cont = el_loadmore.parentNode.parentNode.parentNode;
  var tr_loadmore = cont.lastElementChild;
  if(tr_loadmore.tagName.toLowerCase() == 'script') tr_loadmore = tr_loadmore.previousElementSibling;
  if(tr_loadmore && tr_loadmore.classList.contains('tr-loadmore')){
    cont.removeChild(tr_loadmore);
    cont.insertAdjacentHTML('beforeend', text);
    var tr_loadmore = cont.lastElementChild;
    if(tr_loadmore.tagName.toLowerCase() == 'script') tr_loadmore = tr_loadmore.previousElementSibling;
    if(tr_loadmore && tr_loadmore.classList.contains('tr-loadmore'))
      ui.grid2moredataex[id] = tr_loadmore;
    else
      delete ui.grid2moredataex[id];
  }

}

ui.hidden = function (params){

  var name = ui.ov('name', params);
  var value = ui.ov('value', params);

  var c = "<input class='hidden' type='hidden' data-name='" + name + "' value=\"" + value + "\" />";
  return c;

}
ui.hidden_value = function(el){
  return el.value;
}
ui.hidden_setvalue = function(el, value){
  el.value = value;
}

ui.label_value = function(el){
  var datatype = el.getAttribute("data-datatype");
  var value = el.innerHTML;
  switch(datatype){
    case 'number':
    case 'money':
      value = parseFloat(value.replace(/,/gi, ''));
      break;
  }
  return value;

}
ui.label_setvalue = function(el, value){

  var datatype = el.getAttribute("data-datatype");
  switch(datatype){
    case 'number':
    case 'money':
      var dec = value - (Math.floor(value)) > 0 ? 2 : 0;
      value = number_format(value, dec);
      break;
  }
  el.innerHTML = value;

}

ui.list_rowclick = function(e, tr){
  var tbody = tr.parentNode;
  var active_tr = tbody.querySelector(".active");
  if(active_tr) active_tr.classList.remove('active');
  tr.classList.add('active');
}

ui.menuitemclick = function(e, div){
  var cont = div.parentNode;
  var active_div = ui('.active', cont);
  if(active_div) active_div.classList.remove('active');
  div.classList.add('active');
}

ui.modal_closeable = true;
ui.modal_open = function(el, params){
  if(!el || !(el instanceof HTMLElement) || !el.classList.contains('modal')){ ui.warn('Unable to open modal, invalid parameter.'); return; }
  if(!params) params = {};

  var autoheight = ui.ov('autoheight', params, 0, 0);

  if(el.classList.contains('off')){
    el.classList.remove('off');
    el.classList.add('animated');
    el.classList.add('modal-slidedown');
  }

  var closeable = ui.ov('closeable', params);
  ui.modal_closeable = closeable ? true : false;

  var scrollable = ui('.scrollable', el);
  if(autoheight){

    var scrollable_height = window.innerHeight *.75;
    var scrollable_count = 0;
    $('.modal').children().each(function(){

      if(!this.classList.contains('scrollable'))
        scrollable_height -= $(this).outerHeight();
      else
        scrollable_count++;

    });
    scrollable_height = Math.floor(scrollable_height / scrollable_count);
    $('.scrollable', el).css({ height:scrollable_height });

  }

  var height = typeof params.height != 'undefined' ? params.height : '';
  var width = typeof params.width != 'undefined' ? params.width : '';
  ui.hs({ width:width, height:height }, el);

  if(params.refEl){
    var offset = params.refEl.getBoundingClientRect();
    var left = Math.round(offset.left);
    var top = (Math.round(offset.top) + params.refEl.clientHeight + 10);
  }
  else{
    var left = Math.round((window.innerWidth - el.clientWidth) / 2);
    var top = Math.round((window.innerHeight - el.clientHeight) / 2);
  }

  ui.hs({ left:left + "px", top:top + "px" }, el);
  if(ui('.modalbg')){
    ui('.modalbg').classList.remove('off');
    ui('.modalbg').addEventListener('click', function(){ if(ui.modal_closeable)ui.modal_close(ui('.modal')); }, true);
  }
  $(el).on('animationend webkitAnimationEnd oAnimationEnd', function(){
    ui.modal_close_ex(el);
  })
}
ui.modal_close = function(el){
  if(!el || !(el instanceof HTMLElement) || !el.classList.contains('modal')){ ui.warn('Unable to close modal, invalid parameter.'); return; }
  if(el.classList.contains('off')) return;

  el.classList.add('modal-slideup');
  if(ui('.modalbg')) ui('.modalbg').classList.add('off');
}
ui.modal_close_ex = function(el){
  if(el.classList.contains('modal-slideup')){
    el.classList.remove('modal-popout');
    el.classList.remove('modal-slidedown');
    el.classList.remove('modal-slideup');
    el.classList.add('off');
  }
  else{
  }
  el.classList.remove('animated');
}

ui.dialog_open = function(params){
  if(!params) params = {};
  var el = ui('.dialog');
  el.classList.remove('off');
  el.classList.add('dialog-slidedown');

  var height = typeof params.height != 'undefined' ? params.height : '';
  var width = typeof params.width != 'undefined' ? params.width : '';
  ui.hs({ width:width, height:height }, el);

  if(params.refEl){
    var offset = params.refEl.getBoundingClientRect();
    var left = Math.round(offset.left);
    var top = (Math.round(offset.top) + params.refEl.clientHeight + 10);
  }
  else{
    var left = Math.round((window.innerWidth - el.clientWidth) / 2);
    var top = Math.round((window.innerHeight - el.clientHeight) / 2);
  }

  ui.hs({ left:left + "px", top:top + "px" }, el);
  if(ui('.dialogbg')){
    ui('.dialogbg').classList.remove('off');
  }

  el.addEventListener('animationend', function(){ ui.dialog_close_ex(el); });
  el.addEventListener('webkitAnimationEnd', function(){ ui.dialog_close_ex(el); });
  el.addEventListener('oAnimationEnd', function(){ ui.dialog_close_ex(el); });
}
ui.dialog_close = function(el){
  var el = ui('.dialog');
  el.classList.add('dialog-slideup');
  if(ui('.dialogbg')) ui('.dialogbg').classList.add('off');
}
ui.dialog_close_ex = function(el){
  if(el.classList.contains('dialog-slideup')){
    // Off state
    el.classList.remove('dialog-popout');
    el.classList.remove('dialog-slidedown');
    el.classList.remove('dialog-slideup');
    el.classList.add('off');
  }
  else{
    // On state
  }
}

ui.image_setvalue = function(el, value){

  el.src = value;
  el.setAttribute('data-value', value);

}
ui.image_value = function(el){

  var value = el.getAttribute('data-value');
  return value == null ? '' : value;

}

ui.showstatus = function(message, type){

  var el = ui('.statusbar');
  if(el){
    if(typeof type == 'undefined' || type == null) type = 'info';
    el.innerHTML = "<label class='" + type + "'>" + message + "</label>";
    el.classList.remove('off');
  }

}
ui.hidestatus = function(){

  var el = ui('.statusbar');
  if(el){
    el.classList.add('off');
  }

}

ui.multicomplete_onkeyup = function(e, input){
  if(input.value.length == 0)
    input.parentNode.setAttribute("data-value", "");
  else
	  window.setTimeout("ui.multicomplete_begininvoke(ui('$" + ui.uiid(input.parentNode) + "'), \"" + addslashes(input.value) + "\")", 300);
}
ui.multicomplete_begininvoke = function(el, hint){
	var input = el.lastElementChild;
	if(input.value == hint){
		var src = el.getAttribute("data-src");
    var popup = el.lastElementChild.previousElementSibling;
		ui.async('ui_multicompleteitems', [ ui.uiid(popup), src, hint ], { onload:"ui.multicomplete_endinvoke(ui('$" + ui.uiid(el) + "'))" });
  }
}
ui.multicomplete_endinvoke = function(el){
  var popup = el.lastElementChild.previousElementSibling;
  var menuitems = ui('.menuitem', popup, true);
  if(menuitems){
    for(var i = 0 ; i < menuitems.length ; i++)
      menuitems[i].addEventListener('click', ui.multicomplete_onmenuitemclick, true);
    ui.popupopen(popup, el);
  }
}
ui.multicomplete_onmenuitemclick = function(e){
  var popup = this.parentNode;
  var el = popup.parentNode;

  var text = this.innerHTML;
  var value = this.getAttribute("data-value");

  var item = ui.hc("span", null, { className:"item", innerHTML:"<label>" + text + "</label><span onclick='ui.multicomplete_onitemremove(event, this)' class='fa fa-times'></span>" });
  item.setAttribute("data-value", value);
  el.insertBefore(item, popup);

  el.lastElementChild.value = '';
  ui.popupclose(popup);

  var name = el.hasAttribute("data-name") ? el.getAttribute("data-name") : '';
  if(el.hasAttribute("data-onchange")) ui.eventcall(el.getAttribute("data-onchange"), { name:name, value:ui.multicomplete_value(el) }, el);
}
ui.multicomplete_onitemremove = function(e, span){
	var item = span.parentNode;
	var el = item.parentNode;
	el.removeChild(item);
  if(el.hasAttribute("data-onchange")) ui.eventcall(el.getAttribute("data-onchange"), { value:ui.multicomplete_value(el) }, el);
}
ui.multicomplete_value = function(el){
  var separator = el.getAttribute("data-separator");
  var items = [];
  for(var i = 0 ; i < el.children.length ; i++){
    if(el.children[i].classList.contains('popup')) break;
    items.push(el.children[i].getAttribute("data-value"));
  }
  return items.join(separator);
}
ui.multicomplete_onclick = function(e, el){
  el.lastElementChild.select();
}
ui.multicomplete_placeholder = function(el){
  return el.lastElementChild.placeholder;
}
ui.multicomplete_setplaceholder = function(el, placeholder){
  el.lastElementChild.placeholder = placeholder;
}
ui.multicomplete_clear = function(el){
  for(var i = el.children.length - 3 ; i >= 0 ; i--)
    el.removeChild(el.children[i]);
}

ui.navigation_onitemclick = function(e, span){
  var body = span.parentNode;
  var active_span = ui('.active', body);
  if(active_span) active_span.classList.remove('active');

  span.classList.add('active');

  var el = body.parentNode;
  if(el.hasAttribute("data-onchange"))
    ui.eventcall(el.getAttribute("data-onchange"), { text:ui('label', span).innerHTML }, el);
}

ui.popupopen = function(popup, refEl, options){

  if(!options) options = { nopositioning:0 };

  // ui('.screen').appendChild(popup);
  // popup.setAttribute('data-refid', ui.uiid(refEl));

  popup.classList.remove('off');
  popup.style.width = ui.ov('width', options);

  window.addEventListener('click', ui.popupcloseall, true);
  //window.addEventListener('scroll', ui.popupcloseall, true);

  var offset = refEl.getBoundingClientRect();

  var left = Math.round(offset.left);
  var top = Math.round(offset.top);

  var changed_properties = {};
  changed_properties['minWidth'] = refEl.clientWidth + "px";

  var maxHeight = window.innerHeight - top;
  var availablespace_bottom = window.innerHeight - top;
  var availablespace_top = top - refEl.clientHeight;
  var availablespace_left = left;
  var availablespace_right = window.innerWidth - (left);

  if(popup.clientHeight > availablespace_bottom && popup.clientHeight > availablespace_top){
    var height = availablespace_bottom > availablespace_top ? availablespace_bottom : availablespace_top;
    popup.style.height = (height - 40) + "px";
  }

  if(popup.clientHeight <= availablespace_bottom){
    changed_properties['top'] = (top + refEl.clientHeight + 5) + "px";
  }
  else{
    changed_properties['top'] = (top - popup.clientHeight - 5) + "px";
  }

  if(popup.clientWidth > availablespace_right){
    changed_properties['left'] = (left + refEl.clientWidth - popup.clientWidth);
  }
  else{
    changed_properties['left'] = left + "px";
  }

  ui.hs(changed_properties, popup);
}
ui.popupclose = function(popup){
  popup.classList.add('off');
  popup.classList.remove('slidedown');
  // var refEl = ui('$' + popup.getAttribute('data-refid'));
  // refEl.appendChild(popup);
}
ui.popupcloseall = function(e){
  //console.warn('ui.popupcloseall, which: ' + e.which);
  if(e.which == 3) return;
  var target = e.target;
  var popups = ui('.popup', null, 1);
  for(var i = 0 ; i < popups.length ; i++)
    if(!popups[i].classList.contains('off')){
      // Check if target contained in the popup
      var popupelement = false;
      var iel = target;
      while((iel = iel.parentNode) != null && !(iel instanceof HTMLBodyElement)){
        if(typeof iel.classList != 'undefined' && iel.classList.contains('popup')){
          popupelement = true;
          break;
        }
      }
      if(!popupelement)
        ui.popupclose(popups[i]);
    }
}

ui.popoutclose = function(el){
  el.className = 'popout animated';
  el.innerHTML = "&nbsp;";
}

ui.radio_value = function(el){
  var radios = el.querySelectorAll("input[type='radio']");
  for(var i = 0 ; i < radios.length ; i++)
    if(radios[i].checked) return radios[i].value;
  return null;
}

ui.star_onchange = function(e, item){
  var el = item.parentNode;

  var inactive = false;
  var value = 0;
  for(var i = 0 ; i < el.children.length ; i++){
    el.children[i].className = inactive ? 'item' : 'item active';
    if(el.children[i] == item){
      value = i;
      inactive = true;
    }
  }

  if(el.hasAttributes("data-onchange"))
    ui.eventcall(el.getAttribute("data-onchange"), { value:value }, el);
}

ui.simpleprogressbar_setvalue = function(el, current, max){

  var bar = el.firstElementChild;
  var text = el.lastElementChild;
  var width = el.clientWidth;
  var barwidth = current / max * width;
  bar.style.width = Math.floor(barwidth) + "px";
  text.innerHTML = current + " of " + max;
  el.setAttribute("data-value", current + "," + max);

}

ui.tabclick = function(e, div){
  var tabhead = div.parentNode;
  var active_div = tabhead.querySelector('.active');
  if(active_div) active_div.classList.remove('active');
  div.classList.add('active');

  var index = -1;
  for(var i = 0 ; i < tabhead.children.length ; i++)
    if(tabhead.children[i] == div){
      index = i;
      break;
    }

  if(tabhead.hasAttribute('data-tabbody') && ui(tabhead.getAttribute('data-tabbody'))){
    var tabbody = ui(tabhead.getAttribute('data-tabbody'));
    for(var i = 0 ; i < tabbody.children.length ; i++){
      if(i == index) tabbody.children[i].classList.remove('off');
      else tabbody.children[i].classList.add('off');
    }
  }
}
ui.tabselect = function(tabhead, index){

  var active_div = tabhead.querySelector('.active');
  if(active_div) active_div.classList.remove('active');
  tabhead.children[index].classList.add('active');


  if(tabhead.hasAttribute('data-tabbody') && ui(tabhead.getAttribute('data-tabbody'))){
    var tabbody = ui(tabhead.getAttribute('data-tabbody'));
    for(var i = 0 ; i < tabbody.children.length ; i++){
      if(i == index) tabbody.children[i].classList.remove('off');
      else tabbody.children[i].classList.add('off');
    }
  }

}
ui.tab_value = function(tabhead){

  var index = -1;
  var counter = 0;
  $(tabhead).children().each(function(){
    if(this.classList.contains('active'))
      index = counter;
    counter++;
  });
  return index;

}

ui.textarea_value = function(el){
  if(!el || !(el instanceof HTMLElement) || !el.classList.contains('textarea')){ ui.warn('Invalid el.'); return; }
  return el.firstElementChild.value;
}
ui.textarea_setvalue = function(el, value){
  if(!el || !(el instanceof HTMLElement) || !el.classList.contains('textarea')){ ui.warn('Invalid el.'); return; }
  el.firstElementChild.value = value;
}
ui.textarea_onkeyup = function(e, textarea){
  var el = textarea.parentNode;
  var name = el.hasAttribute("data-name") ? el.getAttribute("data-name") : '';
  if(el.hasAttribute('data-onsubmit') && e.keyCode == 13)
    ui.eventcall(el.getAttribute("data-onsubmit"), { name:name, value:textarea.value }, el);
}

ui.textbox = function(params){
  var name = ui.ov('name', params);
  var width = ui.ov('width', params);
  var value = ui.ov('value', params);
  var type = ui.ov('type', params, 0, 'text');
  var placeholder = ui.ov('placeholder', params);
  var onchange = ui.ov('onchange', params);

  var c = "<span class='textbox' data-type='textbox' style='width:" + width + ";' data-name='" + name + "' data-onchange=\"" + onchange + "\">";
  c += "<input autocomplete='off' type='" + type + "' value=\"" + value + "\" placeholder=\"" + placeholder + "\" onchange=\"ui.textbox_onchange(event, this)\"/>";
  c += "</span>";
  return c;
}
ui.textbox_placeholder = function(el){
  return el.firstElementChild.placeholder;
}
ui.textbox_value = function(el){
	if(!el || !(el instanceof HTMLElement) || !el.classList.contains('textbox')){ ui.warn('Invalid el.'); return; }

  var value = el.firstElementChild.value;
  var datatype = el.getAttribute("data-datatype");
  switch(datatype){
    case 'number':
    case 'money':
      value = parseFloat(value.replace(/,/gi, ''));
      break;
  }
  return value;

}
ui.textbox_setplaceholder = function(el, text){
  el.firstElementChild.placeholder = text;
}
ui.textbox_setvalue = function(el, value){
	if(!el || !(el instanceof HTMLElement) || !el.classList.contains('textbox')){ ui.warn('Invalid el.'); return; }

  var datatype = el.getAttribute("data-datatype");
  switch(datatype){
    case 'number':
    case 'money':
      var precision = Math.abs(value - Math.round(value)) > 0.01 ? 2 : 0;
      value = number_format(value, precision);
      break;
  }
	el.firstElementChild.value = value;

}
ui.textbox_setwidth = function(el, width){
  el.style.width = parseInt(width) + "px";
}
ui.textbox_onchange = function(e, input){
  var el = input.parentNode;
  var name = el.hasAttribute("data-name") ? el.getAttribute("data-name") : '';
  var value = ui.textbox_value(el);
  if(el.hasAttribute("data-onchange"))
    ui.eventcall(el.getAttribute("data-onchange"), { name:name, value:input.value }, el);
}
ui.textbox_onblur = function(e, input){

  var el = input.parentNode;
  var value = input.value;
  var datatype = el.getAttribute("data-datatype");
  switch(datatype){
    case 'number':
    case 'money':
      value = parseFloat(value);
      value = isNaN(value) ? 0 : value;
      var precision = get_round_precision(value);
      value = number_format(value, precision);
      break;
  }
  input.value = value;

}
ui.textbox_onfocus = function(e, input){

  var el = input.parentNode;
  //if(el.classList.contains('readonly')) return;
  var value = input.value;
  var datatype = el.getAttribute("data-datatype");
  switch(datatype){
    case 'number':
    case 'money':
      value = parseFloat(value.replace(/,/gi, ''));
      break;
  }
  input.value = value;

}
ui.textbox_onkeyup = function(e, input){
  var el = input.parentNode;
  var name = el.hasAttribute("data-name") ? el.getAttribute("data-name") : '';
  if(el.hasAttribute('data-onsubmit') && e.keyCode == 13)
    ui.eventcall(el.getAttribute("data-onsubmit"), { name:name, value:input.value }, el);
}

ui.tooltip = function(el, refEl){

  ui.tooltip_closeall();
  el.classList.add('on');
  if(ui('.arrow', el) != null) el.removeChild(ui('.arrow', el));

  var offset = refEl.getBoundingClientRect();
  var left = Math.round(offset.left);
  left -= (el.clientWidth - refEl.clientWidth) / 2;
  var top = Math.round(offset.top);
  top += refEl.clientHeight + 5;
  if(top + el.clientHeight > window.innerHeight){
    top = offset.top - el.clientHeight - 10;
    ui.hc('span', {}, { className:"arrow arrow-down" }, el);
  }
  else
    ui.hc('span', {}, { className:"arrow arrow-up" }, el);

  ui.hs({
    left:left,
    top:top
  }, el);

}
ui.tooltip_closeall = function(){

  var tooltips = document.querySelectorAll('.tooltip');
  for(var i = 0 ; i < tooltips.length ; i++)
    tooltips[i].classList.remove('on');

}

ui.toggler_onitemclick = function(e, span){
  var el = span.parentNode;
  var value = -1;
  if(span.classList.contains('left')){
    span.className = 'item right';
    value = 1;
    el.classList.add('on');
  }
  else{
    span.className = 'item left';
    value = 0;
    el.classList.remove('on');
  }

  if(el.hasAttributes("data-onchange"))
    ui.eventcall(el.getAttribute("data-onchange"), { value:value }, el);
}
ui.toggler_setvalue = function(el, value){
  if(value) el.classList.add('on');
  else el.classList.remove('on');
}
ui.toggler_value = function(el){

  return el.classList.contains('on') ? 1 : 0;

}
ui.toggler_ontoggle = function(e, el){

  if(el.classList.contains('readonly')) return;
  el.classList.toggle('on');

  var text = el.getAttribute("data-text");
  if(text != null && text.indexOf(',') >= 0){
    text = text.split(',');
    var label = el.classList.contains('on') ? text[1] : text[0];
    $('label', el).html(label);
  }

  if(el.hasAttributes("data-onchange")){
    var name = el.hasAttribute("data-name") ? el.getAttribute("data-name") : '';
    ui.eventcall(el.getAttribute("data-onchange"), { name:name, value:el.classList.contains('on') ? 1 : 0 }, el);
  }
}

ui.upload_onchange = function(event, input){
  if(input.files.length == 1){
    var el = input.parentNode;
    ui.async(input.getAttribute("data-src"), input.files[0], { type:'put', callback:"ui.upload_onuploadcompleted(ui('$" + ui.uiid(el) + "'), text)" });
    el.firstElementChild.className = "fa fa-spinner fa-spin";
    el.disabled = true;
  }
}
ui.upload_onuploadcompleted = function(el, text){
  //var obj = eval("(" + text + ")");
  ///el.setAttribute("data-value", obj.url);
  el.firstElementChild.className = "fa fa-upload";
  el.disabled = false;
}
ui.upload_value = function(el){
  return el.getAttribute("data-value");
}

ui.sidebartoggler = function(){
  var el = ui('.sidebar-toggler');
  var sidebar = ui('.sidebar');
  if(el && sidebar) sidebar.classList.contains('on') ? sidebar.classList.remove('on') : sidebar.classList.add('on');
  var content = ui('.content');
  if(content) content.classList.contains('sidebar-on') ? content.classList.remove('sidebar-on') : content.classList.add('sidebar-on');

  ui.async('sidebar_state', [ content.classList.contains('sidebar-on') ? 1 : 0 ], {});
}

ui.chartjs_update = function(datasets, data){
  console.log(datasets);
  console.log(data);
  return datasets;

}

ui.timeaccesscontrol_value = function(el){

  var value = [];
  var checkboxes = el.querySelectorAll("input[type='checkbox']:checked");
  for(var i = 0 ; i < checkboxes.length ; i++){
    var checkbox = checkboxes[i];
    var checkbox_value = checkbox.value;
    value.push(checkbox_value);
  }
  return value.join(' ');

}

ui.invoke_callback = function(callback, params, thisArg){

  if(typeof thisArg == 'undefined') thisArg = null; // Parameter 3 is optional, default: null
  if(typeof callback == 'string')
    callback = eval(callback);
  if(typeof callback == 'function')
    return callback.apply(thisArg, params);

}

ui.preventDefault = function(event){

  event.stopPropagation();
  event.preventDefault();
  return false;

}

ui.exec = function(callback){

  if(ui.__loaded == 0){
    ui.__exec_callback.push(callback);
  }
  else
    ui.invoke_callback(callback);

}

function urlencode (str) {
  str = (str + '')
  return encodeURIComponent(str)
    .replace(/!/g, '%21')
    .replace(/'/g, '%27')
    .replace(/\(/g, '%28')
    .replace(/\)/g, '%29')
    .replace(/\*/g, '%2A')
    .replace(/%20/g, '+')
}
function http_build_query(formdata, numericPrefix, argSeparator){

  var value
  var key
  var tmp = []
  var _httpBuildQueryHelper = function (key, val, argSeparator) {
    var k
    var tmp = []
    if (val === true) {
      val = '1'
    } else if (val === false) {
      val = '0'
    }
    if (val !== null) {
      if (typeof val === 'object') {
        for (k in val) {
          if (val[k] !== null) {
            tmp.push(_httpBuildQueryHelper(key + '[' + k + ']', val[k], argSeparator))
          }
        }
        return tmp.join(argSeparator)
      } else if (typeof val !== 'function') {
        return urlencode(key) + '=' + urlencode(val)
      } else {
        throw new Error('There was an error processing for http_build_query().')
      }
    } else {
      return ''
    }
  }
  if (!argSeparator) {
    argSeparator = '&'
  }
  for (key in formdata) {
    value = formdata[key]
    if (numericPrefix && !isNaN(key)) {
      key = String(numericPrefix) + key
    }
    var query = _httpBuildQueryHelper(key, value, argSeparator)
    if (query !== '') {
      tmp.push(query)
    }
  }
  return tmp.join(argSeparator);

}

qs = (function(a) {
  if (a == "") return {};
  var b = {};
  for (var i = 0; i < a.length; ++i)
  {
    var p=a[i].split('=', 2);
    if (p.length == 1)
      b[p[0]] = "";
    else
      b[p[0]] = decodeURIComponent(p[1].replace(/\+/g, " "));
  }
  return b;
})(window.location.search.substr(1).split('&'));

/*window.onerror = function(msg, url, line, col) {
  alert(msg + "\n\n\n" + url + ":" + line);
  return true;
};*/

document.addEventListener("DOMContentLoaded", function(event) {

  ui.__loaded = 1;
  for(var i = 0 ; i < ui.__exec_callback.length ; i++)
    ui.invoke_callback(ui.__exec_callback[i]);

  document.body.addEventListener("click", function(){
    ui.tooltip_closeall();
  });

  window.addEventListener('scroll', function(){
    ui.tooltip_closeall();
  })

  var scrollable = document.querySelectorAll(".scrollable");
  if(scrollable != null){
    for(var i = 0 ; i < scrollable.length ; i++){
      scrollable[i].addEventListener('scroll', function(){
        ui.tooltip_closeall();
      })
    }
  }

});

$.extend({

  ui_init:function(cont){

    $("*[data-toggle]", cont).on('click.toggle', function(){

      var toggleid = this.getAttribute("data-toggle");
      $(toggleid).toggleClass('toggle-off');

    });

    $("*[data-tooltip]", cont).on('mouseover.tooltip', function(){

      $.tooltip_show(this.getAttribute('data-tooltip'), this);

    });

    $("*[data-tooltip]", cont).on('mouseout.tooltip', function(){

      $.tooltip_hide();

    });

  }



});

(function($){

  var jqVal = $.fn.val;
  $.fn.val = function(value){

    var jqInstance = this;
    var type = $(this).attr('data-type');
    if(typeof type != 'undefined' && type != null){

      // Setter
      if(typeof value != 'undefined'){

        var funcName = "ui." + type + "_setvalue";
        if(eval("typeof " + funcName) == 'function'){
          $(this).each(function(){
            eval(funcName).apply(this, [ this, value ]);
          });
        }
        else{
          ui.warn(funcName + ' not exists.');
        }

      }

      // Getter
      else{

        var funcName = "ui." + type + "_value";
        if(eval("typeof " + funcName) == 'function'){
          var value = [];
          $(this).each(function(){
            value.push(eval(funcName).call(this, this));
          });
          return value.length == 1 ? value[0] : (value.length > 1 ? value : '');
        }
        else{
          ui.warn(funcName + ' not exists.');
        }

      }

    }
    else
      return jqVal.call(jqInstance);

  }

})(jQuery);

$.extend({

  tooltip_show:function(html, refEl){

    $('#tooltip').addClass('on').html(html);

    var offset = $(refEl).offset();
    offset['top'] += $(refEl).outerHeight() + 5;

    $('#tooltip').css(offset);

  },

  tooltip_hide:function(){

    $('#tooltip').removeClass('on');

  }

})

$(function(){

  $.ui_init();

})