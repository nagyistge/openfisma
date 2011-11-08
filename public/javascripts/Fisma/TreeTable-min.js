(function(){var a=function(c,b){this._baseUrl=c;this._numberColumns=b;this._buttons=[{label:"Expand All",fn:this.expandAllNodes,image:"/images/expand.png"},{label:"Collapse All",fn:this.collapseAllNodes,image:"/images/collapse.png"}];a.superclass.constructor.call(this)};a.NodeState={EXPANDED:"EXPANDED",COLLAPSED:"COLLAPSED",LEAF_NODE:"LEAF_NODE"};YAHOO.lang.extend(a,Object,{_buttons:null,_baseUrl:null,_defaultDisplayLevel:3,_filters:{},_loadingBar:null,_errorBar:null,_numberColumns:null,_table:null,_treeData:null,render:function(b){var f=document.createElement("div");f.className="searchBox";b.appendChild(f);this._renderButtons(f);this._renderFilters(f);var h=document.createElement("table");h.className="treeTable";b.appendChild(h);this._table=h;var i=this._getNumberHeaderRows();var e=Array();var g;while(i>0){g=h.insertRow(h.rows.length);e.push(g);i--}this._renderHeader(e);var d=h.insertRow(h.rows.length);this._renderLoadingBar(d);this._loadingBar=d;var c=h.insertRow(h.rows.length);this._renderErrorBar(c);this._errorBar=c;this.hideError();this._requestData()},reloadData:function(){this._showLoadingBar();this.hideError();while(this._table.rows.length>(this._getNumberHeaderRows()+2)){this._table.deleteRow(-1)}this._requestData()},toggleNode:function(c,b){switch(b.state){case a.NodeState.EXPANDED:this._setNodeState(b,a.NodeState.COLLAPSED);break;case a.NodeState.COLLAPSED:this._setNodeState(b,a.NodeState.EXPANDED);break;case a.NodeState.LEAF_NODE:throw"Cannot toggle a leaf node's state";break;default:throw"Unexpected node state ("+b.state+")"}},collapseSubtree:function(c){if(c.state==a.NodeState.EXPANDED){this._setNodeState(c,a.NodeState.COLLAPSED)}if(YAHOO.lang.isValue(c.children)&&c.children.length>0){for(var b in c.children){this.collapseSubtree(c.children[b])}}},collapseAllNodes:function(){for(var c in this._treeData){var b=this._treeData[c];this.collapseSubtree(b);this._hideChildren(b)}},expandSubtree:function(c){if(c.state==a.NodeState.COLLAPSED){this._setNodeState(c,a.NodeState.EXPANDED)}if(YAHOO.lang.isValue(c.children)&&c.children.length>0){for(var b in c.children){this.expandSubtree(c.children[b])}}},expandAllNodes:function(){for(var c in this._treeData){var b=this._treeData[c];this.expandSubtree(b)}},_renderButtons:function(b){var e,d;for(var c in this._buttons){var f=document.createElement("div");b.appendChild(f);f.className="treeTableButton";d=this._buttons[c];e=new YAHOO.widget.Button({type:"button",container:f,label:d.label,onclick:{fn:d.fn,scope:this}});d.image="https://"+window.location.host+d.image;e._button.style.background="url("+d.image+") 10% 50% no-repeat";e._button.style.paddingLeft="3em"}},_renderFilters:function(c){var h=this;for(var e in this._filters){var d=this._filters[e];var b=document.createElement("div");c.appendChild(b);b.className="treeTableFilter";var i=document.createElement("span");b.appendChild(i);i.appendChild(document.createTextNode(d.label));var j=document.createElement("select");b.appendChild(j);j.onchange=(function(n,m,l){return function(){h.disableFilters();n.call(window,m,l.options[l.selectedIndex].value);h.reloadData()}})(d.callback,e,j);for(var k in d.values){var g=new Option(d.values[k],k);if(k==d.defaultValue){g.selected=true}if(YAHOO.env.ua.ie==7){j.add(g,j.options[null])}else{j.add(g,null)}}d.select=j}var f=document.createElement("div");c.appendChild(f);f.className="clear";this.disableFilters()},_renderHeader:function(b){throw"Override the _renderHeader method!"},_renderCell:function(b,e,d,c){b.appendChild(document.createTextNode("Override the _renderCell method!"))},_renderLoadingBar:function(e){var c=document.createElement("th");c.colSpan=this._numberColumns;e.appendChild(c);var d=document.createElement("p");d.appendChild(document.createTextNode("Loading…"));c.appendChild(d);var b=document.createElement("img");b.src="/images/loading_bar.gif";c.appendChild(b)},_renderErrorBar:function(d){var b=document.createElement("th");b.colSpan=this._numberColumns;d.appendChild(b);var c=document.createElement("p");c.appendChild(document.createTextNode("An unexpected error has occurred…"));b.appendChild(c)},_getDataUrl:function(){var d=this._baseUrl;for(var c in this._filters){var e=this._filters[c];var b=e.select;d+="/"+c+"/"+b.options[b.selectedIndex].value}return d},showError:function(b){if(YAHOO.lang.isString(b)){this._errorBar.firstChild.firstChild.firstChild.nodeValue=b}this._errorBar.style.display=""},hideError:function(){this._errorBar.style.display="none"},_requestData:function(){YAHOO.util.Connect.asyncRequest("GET",this._getDataUrl(),{success:this._handleDataRefresh,failure:this._handleDataRefreshFailed,scope:this},null)},_handleDataRefresh:function(c){this._hideLoadingBar();try{var c=YAHOO.lang.JSON.parse(c.responseText)}catch(d){this.showError(d.message);return}if(!c.hasOwnProperty("rootNodes")){throw"The response does not contain the required 'rootNodes' object."}this._treeData=c.rootNodes;if(YAHOO.lang.isNull(this._treeData)){this.showError("No data available.")}else{for(var e=0;e<this._treeData.length;e++){var b=this._treeData[e];this._preprocessTreeData(b);this._renderNode(b,0);this._setInitialTreeState(b,0)}}this.enableFilters()},_handleDataRefreshFailed:function(b){this._hideLoadingBar();this.showError("Unable to load finding summary: "+b.statusText);this.enableFilters()},_preprocessTreeData:function(b){},_renderNode:function(c,e){if(YAHOO.lang.isValue(c.children)&&c.children.length>0){c.expandedRow=this._table.insertRow(this._table.rows.length);this._renderNodeState(c.expandedRow,c,e,a.NodeState.EXPANDED);c.collapsedRow=this._table.insertRow(this._table.rows.length);this._renderNodeState(c.collapsedRow,c,e,a.NodeState.COLLAPSED)}else{c.leafNodeRow=this._table.insertRow(this._table.rows.length);this._renderNodeState(c.leafNodeRow,c,e,a.NodeState.LEAF_NODE)}for(var d=0;d<c.children.length;d++){var b=c.children[d];this._renderNode(b,e+1)}},_renderNodeState:function(c,e,b,j){var k=document.createElement("td");c.appendChild(k);var g=document.createElement("div");g.className="treeTable"+b;k.appendChild(g);if(j!=a.NodeState.LEAF_NODE){g.className+=" link"}var d=document.createElement("img");d.className="control";var h=document.createElement("span");h.appendChild(d);switch(j){case a.NodeState.EXPANDED:YAHOO.util.Event.addListener(g,"click",this.toggleNode,e,this);d.src="/images/minus.png";break;case a.NodeState.COLLAPSED:YAHOO.util.Event.addListener(g,"click",this.toggleNode,e,this);d.src="/images/plus.png";break;case a.NodeState.LEAF_NODE:d.src="/images/leaf_node.png";break;default:throw"Unexpected nodeState ("+j+")"}g.appendChild(h);this._renderCell(g,e.nodeData,0,j);for(var f=1;f<this._numberColumns;f++){k=document.createElement("td");this._renderCell(k,e.nodeData,f,j);c.appendChild(k)}},_setInitialTreeState:function(c,e){if(YAHOO.lang.isValue(c.children)&&c.children.length>0){for(var b in c.children){var d=c.children[b];this._setInitialTreeState(d,e+1)}if(e<this._defaultDisplayLevel-1){this._setNodeState(c,a.NodeState.EXPANDED)}else{this._setNodeState(c,a.NodeState.COLLAPSED)}}else{this._setNodeState(c,a.NodeState.LEAF_NODE)}},_setNodeState:function(b,c){if(b.state!=a.NodeState.LEAF_NODE){switch(c){case a.NodeState.EXPANDED:b.collapsedRow.style.display="none";b.expandedRow.style.display="";this._showChildren(b);break;case a.NodeState.COLLAPSED:b.collapsedRow.style.display="";b.expandedRow.style.display="none";this._hideChildren(b);break;case a.NodeState.LEAF_NODE:break;default:throw"Unexpected node state ("+c+")"}b.state=c}else{throw"Cannot change state on a leaf node"}},_hideRow:function(b){if(YAHOO.lang.isValue(b.collapsedRow)){b.collapsedRow.style.display="none"}if(YAHOO.lang.isValue(b.expandedRow)){b.expandedRow.style.display="none"}if(YAHOO.lang.isValue(b.leafNodeRow)){b.leafNodeRow.style.display="none"}},_showRow:function(b){switch(b.state){case a.NodeState.EXPANDED:b.expandedRow.style.display="";break;case a.NodeState.COLLAPSED:b.collapsedRow.style.display="";break;case a.NodeState.LEAF_NODE:b.leafNodeRow.style.display="";break;default:throw"Unexpected node state ("+b.state+")"}},_hideChildren:function(c){if(YAHOO.lang.isValue(c.children)&&c.children.length>0){for(var b in c.children){var d=c.children[b];this._hideRow(d);this._hideChildren(d)}}},_showChildren:function(c){if(YAHOO.lang.isValue(c.children)&&c.children.length>0){for(var b in c.children){var d=c.children[b];this._showRow(d);if(d.state===a.NodeState.EXPANDED){this._showChildren(d)}}}},_hideLoadingBar:function(){this._loadingBar.style.display="none"},_showLoadingBar:function(){this._loadingBar.style.display=""},_getNumberHeaderRows:function(){return 1},addFilter:function(e,d,c,b,f){if(this._filters.hasOwnProperty(e)){throw"Cannot create filter ("+e+") because it already exists"}this._filters[e]={label:d,defaultValue:b,values:c,callback:f}},disableFilters:function(){for(var b in this._filters){var c=this._filters[b];c.select.disabled=true}},enableFilters:function(){for(var b in this._filters){var c=this._filters[b];c.select.disabled=false}}});Fisma.TreeTable=a})();