(function(){var a=function(b){this._contentDiv=document.getElementById(b);if(YAHOO.lang.isNull(this._contentDiv)){throw"Invalid contentDivId"}this._storage=new Fisma.PersistentStorage("Poc.Tree")};a.prototype={_contentDiv:null,_treeView:null,_loadingContainer:null,_treeViewContainer:null,_savePanel:null,_storage:null,render:function(){var b=this;b._loadingContainer=document.createElement("div");b._renderLoading(b._loadingContainer);b._contentDiv.appendChild(b._loadingContainer);b._treeViewContainer=document.createElement("div");b._renderTreeView(b._treeViewContainer);b._contentDiv.appendChild(b._treeViewContainer)},_renderLoading:function(b){var c=document.createElement("img");c.src="/images/spinners/small.gif";b.style.display="none";b.appendChild(c)},_showLoadingImage:function(){this._loadingContainer.style.display="block"},_hideLoadingImage:function(){this._loadingContainer.style.display="none"},_renderTreeView:function(b){this._showLoadingImage();var c="/poc/tree-data/format/json";YAHOO.util.Connect.asyncRequest("GET",c,{success:function(d){var e=YAHOO.lang.JSON.parse(d.responseText);if(e.treeData.length>0){var g={dragFinished:{fn:this.handleDragDrop,context:this},testDragTargetDelegate:{fn:this.testDragTarget,context:this}};this._treeView=new YAHOO.widget.TreeView(this._treeViewContainer);this._buildTreeNodes(e.treeData,this._treeView.getRoot());Fisma.TreeNodeDragBehavior.makeTreeViewDraggable(this._treeView,g,this);var f=this._treeView.getNodesBy(function(h){return h.depth<2});$.each(f,function(h,i){i.expand()});this._treeView.draw()}this._hideLoadingImage()},failure:function(d){var e="Unable to load the organization tree: "+d.statusText;Fisma.Util.showAlertDialog(e)},scope:this},null)},_buildTreeNodes:function(c,e){for(var d in c){var f=c[d];var b;if(f.hasOwnProperty("id")){b=this._buildOrgNode(f,e)}else{b=this._buildPocNode(f,e)}if(YAHOO.lang.isArray(f.children)&&f.children.length>0){this._buildTreeNodes(f.children,b)}}},_buildOrgNode:function(e,d){var c="<b>"+PHP_JS().htmlspecialchars(e.label)+"</b> - <i>"+PHP_JS().htmlspecialchars(e.orgTypeLabel)+"</i>";var b=new YAHOO.widget.HTMLNode({html:c,organizationId:e.id,type:e.orgType,systemId:e.systemId},d,false);b.contentStyle=e.orgType;return b},_buildPocNode:function(e,d){var c="<b>"+e.p_nameFirst+" "+e.p_nameLast+" ("+e.p_username+")</b> - <i>Point of Contact</i>";var b=new YAHOO.widget.HTMLNode({html:c,pocId:e.p_id},d,false);b.contentStyle="poc";return b},expandAll:function(){this._treeView.getRoot().expandAll()},collapseAll:function(){this._treeView.getRoot().collapseAll()},handleDragDrop:function(c,e,f,g){if(YAHOO.lang.isNull(this._savePanel)){this._savePanel=new YAHOO.widget.Panel(YAHOO.util.Dom.generateId(),{width:"250px",fixedcenter:true,close:false,draggable:false,modal:true,visible:true});this._savePanel.setHeader("Saving...");this._savePanel.render(document.body)}this._savePanel.setBody('<img src="/images/loading_bar.gif">');this._savePanel.show();var b=(YAHOO.lang.isValue(f.data.pocId))?("/destPoc/"+f.data.pocId):("/destOrg/"+f.data.organizationId);var d="/poc/move-node/src/"+e.data.pocId+b+"/dragLocation/"+g;YAHOO.util.Connect.asyncRequest("GET",d,{success:function(i){var h=YAHOO.lang.JSON.parse(i.responseText);if(h.success){c.completeDragDrop(e,f,g);this._savePanel.hide()}else{this._displayDragDropError("Error: "+h.message)}},failure:function(h){this._displayDragDropError("Unable to reach the server to save your changes: "+h.statusText);this._savePanel.hide()},scope:this},null)},testDragTarget:function(b,c,d){if(!YAHOO.lang.isValue(b.data.pocId)){return false}if(YAHOO.lang.isValue(c.data.pocId)&&d===Fisma.TreeNodeDragBehavior.DRAG_LOCATION.ONTO){return false}return true},_displayDragDropError:function(e){var c=document.createElement("div");var g=document.createElement("p");g.appendChild(document.createTextNode(e));var f=document.createElement("p");var d=this;var b=new YAHOO.widget.Button({label:"OK",container:f,onclick:{fn:function(){d._savePanel.hide()}}});c.appendChild(g);c.appendChild(f);this._savePanel.setBody(c)}};Fisma.PocTreeView=a})();