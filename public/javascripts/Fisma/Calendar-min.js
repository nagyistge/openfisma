(function(){var a={addCalendarPopupToTextField:function(e){var c=document.createElement("div");c.style.position="absolute";c.style.zIndex=1000;e.parentNode.appendChild(c);var d=YAHOO.util.Dom.getRegion(e);var g=[d.left,d.bottom+5];YAHOO.util.Dom.setXY(c,g);var f=new YAHOO.widget.Calendar(c,{close:true,title:"Pick A Date"});f.hide();setTimeout(function(){f.render()},0);e.onfocus=function(){f.show()};var b=function(m,j,o){var l=j[0][0];var k=l[0],n=l[1].toString(),i=l[2].toString();if(1===n.length){n="0"+n}if(1===i.length){i="0"+i}var h=k+"-"+n+"-"+i;if("finding[currentEcd]"==e.name&&!Fisma.Finding.validateEcd(h)){Fisma.Util.showAlertDialog("Warning: You entered an ECD date in the past.")}else{e.value=h}f.hide()};f.selectEvent.subscribe(b,f,true)}};Fisma.Calendar=a})();