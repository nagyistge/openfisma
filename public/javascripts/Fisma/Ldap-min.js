Fisma.Ldap={validateLdapBusy:false,validateLdapConfiguration:function(){if(Fisma.Ldap.validateLdapBusy){return}Fisma.Ldap.validateLdapBusy=true;var c=document.location;var f=document.location.pathname.split("/");var b=null;for(pieceIndex in f){var d=f[pieceIndex];if("id"==d){b=f[parseInt(pieceIndex,10)+1];break}}var a=document.getElementById("validateLdap");a.className="yui-button yui-push-button yui-button-disabled";var g=new Fisma.Spinner(a.parentNode);g.show();var e=document.getElementById("ldapUpdate");YAHOO.util.Connect.setForm(e);YAHOO.util.Connect.asyncRequest("POST","/config/validate-ldap/format/json/id/"+b,{success:function(i){var h=YAHOO.lang.JSON.parse(i.responseText);Fisma.Util.message(h.msg,h.type,true);a.className="yui-button yui-push-button";Fisma.Ldap.validateLdapBusy=false;g.hide()},failure:function(h){Fisma.Util.message("Validation failed: "+h.statusText,"warning",true);g.hide()}})}};