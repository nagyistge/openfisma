Fisma.Module={handleSwitchButtonStateChange:function(a){a.setBusy(true);var b=a.state?"true":"false";var c="/config/set-module/id/"+a.payload.id+"/enabled/"+b+"/format/json/";YAHOO.util.Connect.asyncRequest("GET",c,{success:Fisma.Module.handleAsyncResponse,failure:Fisma.Module.handleAsyncResponse,argument:a},null)},handleAsyncResponse:function(b){try{var c=YAHOO.lang.JSON.parse(b.responseText)}catch(d){if(d instanceof SyntaxError){c=new Object();c.success=false;c.message="Invalid response from server."}else{throw d}}if(!c.success){alert("Error: Not able to change module status. Reason: "+c.message)}var a=b.argument;a.setBusy(false)}};