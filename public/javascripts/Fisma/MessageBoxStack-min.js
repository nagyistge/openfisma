(function(){YAHOO.util.Event.onDOMReady(function(){var b=new Fisma.MessageBoxStack();Fisma.Registry.set("messageBoxStack",b);var c=document.getElementById("msgbar");if(c){var d=new Fisma.MessageBox(c);b.push(d)}});var a=function(b){this._messageBoxes=new Array()};a.prototype={_messageBoxes:null,peek:function(){return this._messageBoxes[this._messageBoxes.length-1]},push:function(b){this._messageBoxes.push(b)},pop:function(){return this._messageBoxes.pop()}};Fisma.MessageBoxStack=a})();