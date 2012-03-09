var isDOM = (typeof(document.getElementById) != 'undefined' && typeof(document.createElement) != 'undefined') ? 1 : 0;
var isIE4 = (typeof(document.all) != 'undefined' && parseInt(navigator.appVersion) >= 4) ? 1 : 0;
var isNS4 = (typeof(document.layers) != 'undefined') ? 1 : 0;
var isDHTML = (isDOM || isIE4 || isNS4) ? 1 : 0;
var capable = (isDOM || isIE4 || isNS4) ? 1 : 0;

// Fix for Opera and Konqueror 2.2 which are half DOM compliant
if (capable) {
	if (typeof(window.opera) != 'undefined') {
		capable = 0;
	}
	else if (typeof(navigator.userAgent) != 'undefined') {
		var browserName = ' ' + navigator.userAgent.toLowerCase();
		if (browserName.indexOf('konqueror') > 0) {
			capable = 0;
		}
	} // end if... else if...
} // end if

//fetch objects of interest
function getObject(id){
  if(!isDHTML)
    return null;

  var which=null;
  if(isDOM)
    which=document.getElementById(id);
  else if (isIE4)
    which=document.all(id);
  else if (isNS4)
    which=document.layers[id];
  return which;
}

function init() {
	localdiff = getObject('localdiff');
	if(localdiff) localdiff.value = Math.round(new Date().getTime()/1000) - 60*(new Date().getTimezoneOffset());
	dlocaltime = getObject('dlocaltime');
	var TheLocalTime = new Date();
	if(dlocaltime) dlocaltime.firstChild.nodeValue = '['+gibzero(TheLocalTime.getHours())+':'+gibzero(TheLocalTime.getMinutes())+':'+gibzero(TheLocalTime.getSeconds())+']';
	sdt = getObject('sdt');
	update();
}

function gibzero(number){
	if(isNaN(number)) return '0';

	return ((number<10)?'0'+number:number);
}

function update() {
	localTime = new Date().getTime();
	var fserverTime = localTime + 60000*(new Date().getTimezoneOffset()) + offsetTime - 1000*GMTint + 1000*timediff;
	var tmptime = new Date(fserverTime);
	//var fserverTime_date = new Date(fserverTime).toString();
	//sdt.firstChild.nodeValue = fserverTime_date.substring(0,fserverTime_date.indexOf('GMT'));
	//var fserverTime_date = new Date(fserverTime).getYear();
	var fserverTime_date = tmptime.getFullYear()+'-'+gibzero(tmptime.getMonth()+1)+'-'+gibzero(tmptime.getDate())+', ['+gibzero(tmptime.getHours())+':'+gibzero(tmptime.getMinutes())+':'+gibzero(tmptime.getSeconds())+']';
	if(sdt) sdt.firstChild.nodeValue = fserverTime_date;
	setTimeout("update()", 1000);
}