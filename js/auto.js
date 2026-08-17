var auto = null;
var autoString = null;
var autoTimer = null;
var autoBusy = false;

function autoLoad(){
    if (autoBusy) return;
    autoBusy = true;
    autoRequest("GET", "stats.php?time=" + Date.now(), true);
}

function autoSchedule(){
    if (autoTimer) window.clearTimeout(autoTimer);
    autoTimer = window.setTimeout(autoLoad, 15000);
}

function autoPut(id, value){
    var el = document.getElementById(id);
    if (el && typeof value !== "undefined" && value !== null) {
        if (typeof stylizeDiv === "function") stylizeDiv(String(value), el);
        else el.innerHTML = String(value);
    }
}

function autoHandle(){
    if (!auto || auto.readyState !== 4) return;
    autoBusy = false;
    autoSchedule();
    if (auto.status < 200 || auto.status >= 300) return;

    var obj;
    try { obj = JSON.parse(auto.responseText || "[]"); } catch (e) { return; }
    if (!Array.isArray(obj)) return;

    autoPut("next", obj[6]);
    autoPut("messages", obj[5]);
    autoPut("time", obj[4]);
    autoPut("serverTime", obj[4]);
    autoPut("turns", obj[3]);
    autoPut("isRank", obj[2]);
    autoPut("inBank", obj[1]);
    autoPut("inHand", obj[0]);
    autoPut("metal", obj[7]);
    autoPut("crystal", obj[8]);
    autoPut("deuterium", obj[9]);
    autoPut("food", obj[10]);
    autoPut("water", obj[11]);
    autoPut("population", obj[12]);
    autoPut("energy", obj[13]);
}

function autoReq(reqType, url, bool){
    if (!auto) return;
    auto.onreadystatechange = autoHandle;
    auto.open(reqType, url, bool);
    if (reqType.toUpperCase() === "POST") {
        auto.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8");
    }
    auto.send(autoString || null);
}

function autoRequest(reqType, url, asynch){
    if (window.XMLHttpRequest) {
        auto = new XMLHttpRequest();
    } else if (window.ActiveXObject) {
        try { auto = new ActiveXObject("Msxml2.XMLHTTP"); } catch (e) {
            try { auto = new ActiveXObject("Microsoft.XMLHTTP"); } catch (ignored) { auto = null; }
        }
    }
    if (auto) autoReq(reqType, url, asynch);
    else { autoBusy = false; autoSchedule(); }
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", autoLoad);
} else {
    autoLoad();
}
