Fisma.AutoComplete=function(){return{requestCount:0,resultsPopulated:false,init:function(c,g,f){var e=new YAHOO.widget.DS_XHR(f.xhr,f.schema);e.responseType=YAHOO.widget.DS_XHR.TYPE_JSON;e.maxCacheEntries=500;e.queryMatchContains=true;var d=new YAHOO.widget.AutoComplete(f.fieldId,f.containerId,e);d.maxResultsDisplayed=20;d.forceSelection=true;var b=document.getElementById(f.containerId+"Spinner");d.dataRequestEvent.subscribe(function(){b.style.visibility="visible";Fisma.AutoComplete.requestCount++});d.dataReturnEvent.subscribe(function(){Fisma.AutoComplete.requestCount--;if(0===Fisma.AutoComplete.requestCount){b.style.visibility="hidden"}});d.getInputEl().onclick=function(){if(Fisma.AutoComplete.resultsPopulated){d.expandContainer()}};d.containerPopulateEvent.subscribe(function(){Fisma.AutoComplete.resultsPopulated=true});d.generateRequest=function(h){return f.queryPrepend+h};d.formatResult=function(i,l,h){var k=(h)?PHP_JS().htmlspecialchars(h):"";var j=new RegExp("\\b("+l+")","i");h=h.replace(j,"<em>$1</em>");return h};d.itemSelectEvent.subscribe(Fisma.AutoComplete.updateHiddenField,f.hiddenFieldId);d.selectionEnforceEvent.subscribe(Fisma.AutoComplete.clearHiddenField,f.hiddenFieldId);if(f.hasOwnProperty("enterKeyEventHandler")){YAHOO.util.Event.on(d.getInputEl(),"keydown",function(i){if((i.which&&i.which==13)||(i.keyCode&&i.keyCode==13)){YAHOO.util.Event.preventDefault(i);if(!YAHOO.lang.isNull(f.enterKeyEventHandler)){var h=Fisma.Util.getObjectFromName(f.enterKeyEventHandler);if(h){h(d,f.enterKeyEventArgs)}}}})}if(YAHOO.lang.isValue(f.setupCallback)){var a=Fisma.Util.getObjectFromName(f.setupCallback);a(d,f)}},updateHiddenField:function(c,b,a){document.getElementById(a).value=b[2][1]["id"];$("#"+a).trigger("change")},clearHiddenField:function(c,b,a){document.getElementById(a).value=null;$("#"+a).trigger("change")}}}();