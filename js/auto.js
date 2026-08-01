var auto;
var autoString;   //will hold the POSTed data

function autoLoad(){
    var url="stats.php"; 
    autoRequest("GET",url,true);
    setTimeout(autoLoad,15000);
}

//event handler for XMLHttpRequest
function autoHandle(){
    if(auto.readyState == 4){
        if(auto.status == 200){
            var resp = auto.responseText;
            var obj = eval(resp);
			stylizeDiv(obj[6],document.getElementById("next"));
			stylizeDiv(obj[5],document.getElementById("messages"));
			stylizeDiv(obj[4],document.getElementById("time"));
            if (document.getElementById("serverTime")) { stylizeDiv(obj[4],document.getElementById("serverTime")); }
			stylizeDiv(obj[3],document.getElementById("turns"));
			stylizeDiv(obj[2],document.getElementById("isRank"));
			stylizeDiv(obj[1],document.getElementById("inBank"));
            stylizeDiv(obj[0],document.getElementById("inHand"));

            if (document.getElementById("metal")) { stylizeDiv(obj[7],document.getElementById("metal")); }
            if (document.getElementById("crystal")) { stylizeDiv(obj[8],document.getElementById("crystal")); }
            if (document.getElementById("deuterium")) { stylizeDiv(obj[9],document.getElementById("deuterium")); }
            if (document.getElementById("food")) { stylizeDiv(obj[10],document.getElementById("food")); }
            if (document.getElementById("water")) { stylizeDiv(obj[11],document.getElementById("water")); }
            if (document.getElementById("population")) { stylizeDiv(obj[12],document.getElementById("population")); }
            if (document.getElementById("energy")) { stylizeDiv(obj[13],document.getElementById("energy")); }
        } else {
            alert("A problem occurred with communicating between the XMLHttpRequest object and the server program .//Auto Problem");
        }
    }//end outer if
}

/* Initialize a Request object that is already constructed */
function autoReq(reqType,url,bool){
    /* Specify the function that will handle the HTTP response */
    auto.onreadystatechange=autoHandle;
    auto.open(reqType,url,bool);
    auto.setRequestHeader("Content-Type",
            "application/x-www-form-urlencoded; charset=UTF-8");
    auto.send(autoString);
}

/* Wrapper function for constructing a Request object.
 Parameters:
  reqType: The HTTP auto type such as GET or POST.
  url: The URL of the server program.
  asynch: Whether to send the auto asynchronously or not. */
function autoRequest(reqType,url,asynch){
    //Mozilla-based browsers
    if(window.XMLHttpRequest){
        auto = new XMLHttpRequest();
    } else if (window.ActiveXObject){
        auto=new ActiveXObject("Msxml2.XMLHTTP");
        if (! auto){
            auto=new ActiveXObject("Microsoft.XMLHTTP");
        }
     }
    //the auto could still be null if neither ActiveXObject
    //initializations succeeded
    if(auto){
       autoReq(reqType,url,asynch);
    }  else {
        alert("Your browser does not permit the use of all "+
        "of this application's features!");}
}

