Fisma.User={userInfoPanelList:{},generatePasswordBusy:false,checkAccountBusy:false,commentTable:null,commentCallback:function(e,b){var d=this;var c={timestamp:e.createdTs,username:e.username,comment:e.comment};if(YAHOO.lang.isObject(Fisma.Util.yuiDataTable)){this.commentTable=Fisma.Util.yuiDataTable}this.commentTable.addRow(c);this.commentTable.sortColumn(this.commentTable.getColumn(0),YAHOO.widget.DataTable.CLASS_DESC);var a=new Fisma.Blinker(100,6,function(){d.commentTable.highlightRow(0)},function(){d.commentTable.unhighlightRow(0)});a.start();b.hide();b.destroy()},displayUserInfo:function(c,b){var a;if(typeof Fisma.User.userInfoPanelList[b]=="undefined"){a=Fisma.User.createUserInfoPanel(c,b);Fisma.User.userInfoPanelList[b]=a;a.show()}else{a=Fisma.User.userInfoPanelList[b];if(a.cfg.getProperty("visible")){a.hide()}else{a.bringToTop();a.show()}}},createUserInfoPanel:function(e,d){var b=350;var c,a;c=d+"InfoPanel";a=new YAHOO.widget.Panel(c,{width:b+"px",modal:false,close:true,constraintoviewport:true});a.setHeader("User Profile");a.setBody("Loading user profile for <em>"+d+"</em>...");a.render(document.body);Fisma.Util.positionPanelRelativeToElement(a,e);YAHOO.util.Connect.asyncRequest("GET","/user/info/username/"+escape(d),{success:function(f){a.setBody(f.responseText);Fisma.Util.positionPanelRelativeToElement(a,e)},failure:function(f){a.setBody("User information cannot be loaded.");Fisma.Util.positionPanelRelativeToElement(a,e)}},null);return a},generatePassword:function(){if(Fisma.User.generatePasswordBusy){return true}Fisma.User.generatePasswordBusy=true;var a=document.getElementById("generate_password");a.className="yui-button yui-push-button yui-button-disabled";var b=new Fisma.Spinner(a.parentNode);b.show();YAHOO.util.Connect.asyncRequest("GET","/user/generate-password/format/html",{success:function(c){document.getElementById("password").value=c.responseText;document.getElementById("confirmPassword").value=c.responseText;Fisma.User.generatePasswordBusy=false;a.className="yui-button yui-push-button";b.hide()},failure:function(d){b.hide();var c="Failed to generate password: "+d.statusText;Fisma.Util.showAlertDialog(c)}},null);return false},checkAccount:function(){if(Fisma.User.checkAccountBusy){return}Fisma.User.checkAccountBusy=true;var c=document.getElementById("username").value;var a="/user/check-account/format/json/account/"+encodeURIComponent(c);var b=document.getElementById("checkAccount");b.className="yui-button yui-push-button yui-button-disabled";var d=new Fisma.Spinner(b.parentNode);d.show();YAHOO.util.Connect.asyncRequest("GET",a,{success:function(k){var h=YAHOO.lang.JSON.parse(k.responseText);message(h.msg,h.type,true);var e=new Array("nameFirst","nameLast","phoneOffice","phoneMobile","email","title");var f=new Array("givenname","sn","telephonenumber","mobile","mail","title");if(h.accountInfo.length>0){for(var g in f){if(!f.hasOwnProperty(g)){continue}var j=h.accountInfo[f[g]];if(!YAHOO.lang.isUndefined(j)){document.getElementById(e[g]).value=j}else{document.getElementById(e[g]).value=""}}}Fisma.User.checkAccountBusy=false;b.className="yui-button yui-push-button";d.hide()},failure:function(f){Fisma.User.checkAccountBusy=false;b.className="yui-button yui-push-button";d.hide();var e="Failed to check account password: "+f.statusText;Fisma.Util.showAlertDialog(e)}},null)},showCommentPanel:function(){var i=YAHOO.util.Dom.get("locked");if(i===null||parseInt(i.value,10)===0){YAHOO.util.Dom.getAncestorByTagName("save-button","form").submit();return false}var f=document.createElement("div");var c=document.createElement("span");var h=document.createTextNode("Please add a comment explaining why you are locking this user's account.");c.appendChild(h);f.appendChild(c);var d=document.createElement("p");var g=document.createTextNode("Comments (OPTIONAL):");d.appendChild(g);f.appendChild(d);var a=document.createElement("textarea");a.id="commentTextArea";a.name="commentTextArea";a.rows=5;a.cols=60;f.appendChild(a);var b=document.createElement("div");b.style.height="10px";f.appendChild(b);var j=document.createElement("span");var e=new YAHOO.widget.Button({type:"button",label:"Save",container:j});f.appendChild(j);Fisma.HtmlPanel.showPanel("Add Comment",f);e.on("click",Fisma.User.submitUserForm);return true},submitUserForm:function(){var a=YAHOO.util.Dom.get("commentTextArea").value;YAHOO.util.Dom.get("comment").value=a;var b=YAHOO.util.Dom.getAncestorByTagName("save-button","form");b.submit()}};