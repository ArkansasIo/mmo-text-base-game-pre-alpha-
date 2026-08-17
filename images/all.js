	var auto;
	var queryString;   //will hold the POSTed data

	function sendData(burst){
	    var url = "s_pages/"+burst+".php";
	    httpRequest("GET",url,true);
	}

	function handleResponse(){
	    if(request.readyState == 4){
	        if(request.status >= 200 && request.status < 300){
	           var doc = request.responseText || "";
	           var target = document.getElementById("mainDisplay");
	           if (target) stylizeDiv(doc,target);
	        } else {
	           var target = document.getElementById("mainDisplay");
	           if (target) stylizeDiv('<div class="ajax-error">Command request failed. Please retry.</div>',target);
	        }
	    }
	}

	function initReq(reqType,url,bool){
	    request.onreadystatechange=handleResponse;
	    request.open(reqType,url,bool);
	    if (reqType.toUpperCase() === "POST") {
	        request.setRequestHeader("Content-Type","application/x-www-form-urlencoded; charset=UTF-8");
	    }
	    request.send(queryString || null);
	}

	function httpRequest(reqType,url,asynch){
	    if(window.XMLHttpRequest){
	        request = new XMLHttpRequest();
	    } else if (window.ActiveXObject){
	        try { request=new ActiveXObject("Msxml2.XMLHTTP"); } catch(e) {
	            try { request=new ActiveXObject("Microsoft.XMLHTTP"); } catch(ignored) { request=null; }
	        }
	    }
	    if(request) initReq(reqType,url,asynch);
	}

	function stylizeDiv(bdyTxt,div){
	    if (!div) return;
	    div.innerHTML = bdyTxt || "";
	}

	var autoBusy = false;
	var autoTimer = null;

	function autoLoad(){
	    if (autoBusy) return;
	    autoBusy = true;
	    autoRequest("GET","stats.php?time="+Date.now(),true);
	}

	function autoSchedule(){
	    if (autoTimer) window.clearTimeout(autoTimer);
	    autoTimer = window.setTimeout(autoLoad,15000);
	}

	function autoPut(id,value){
	    var el=document.getElementById(id);
	    if (el && value !== undefined && value !== null) stylizeDiv(String(value),el);
	}

	function autoHandle(){
	    if (!auto || auto.readyState !== 4) return;
	    autoBusy=false;
	    autoSchedule();
	    if (auto.status < 200 || auto.status >= 300) return;
	    var obj;
	    try { obj=JSON.parse(auto.responseText || "[]"); } catch(e) { return; }
	    if (!Array.isArray(obj)) return;
	    autoPut("next",obj[6]); autoPut("messages",obj[5]); autoPut("time",obj[4]);
	    autoPut("serverTime",obj[4]); autoPut("turns",obj[3]); autoPut("isRank",obj[2]);
	    autoPut("inBank",obj[1]); autoPut("inHand",obj[0]); autoPut("metal",obj[7]);
	    autoPut("crystal",obj[8]); autoPut("deuterium",obj[9]); autoPut("food",obj[10]);
	    autoPut("water",obj[11]); autoPut("population",obj[12]); autoPut("energy",obj[13]);
	}

	function autoReq(reqType,url,bool){
	    if (!auto) return;
	    auto.onreadystatechange=autoHandle;
	    auto.open(reqType,url,bool);
	    if (reqType.toUpperCase() === "POST") {
	        auto.setRequestHeader("Content-Type","application/x-www-form-urlencoded; charset=UTF-8");
	    }
	    auto.send(null);
	}

	function autoRequest(reqType,url,asynch){
	    if(window.XMLHttpRequest){
	        auto=new XMLHttpRequest();
	    } else if(window.ActiveXObject){
	        try { auto=new ActiveXObject("Msxml2.XMLHTTP"); } catch(e) {
	            try { auto=new ActiveXObject("Microsoft.XMLHTTP"); } catch(ignored) { auto=null; }
	        }
	    }
	    if(auto) autoReq(reqType,url,asynch);
	    else { autoBusy=false; autoSchedule(); }
	}

	function setQueryString(){
	    queryString="";
	    var frm=document.forms[0];
	    if (!frm) return;
	    for(var i=0;i<frm.elements.length;i++){
	        if(!frm.elements[i].name) continue;
	        if(queryString) queryString += "&";
	        queryString += encodeURIComponent(frm.elements[i].name)+"="+encodeURIComponent(frm.elements[i].value);
	    }
	}

	function autoDiv(bdyTxt,div){ stylizeDiv(bdyTxt,div); }
