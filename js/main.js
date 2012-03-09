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

for(i=0;i<totalRow;i++){
	bgColor[i] = '#333333';
	if(isNaN(time_arr[i])) time_arr[i]=0;
}

function restoreBgColor(id) { //v3.0
	if (isNaN(id)) return;
	var row = getObject('tr'+id);
	row.style.backgroundColor = bgColor[id];
	rowHover=-1;
}

function changeBgColor(id){
	if (isNaN(id)) return;
	var row = getObject('tr'+id);
	if(bgColor[id]=='red')
		row.style.backgroundColor = '#aa0000';
	else if(bgColor[id]=='orange')
		row.style.backgroundColor = '#bb5500';
	else if(bgColor[id]=='green')
		row.style.backgroundColor = '#004400';		
	else
		row.style.backgroundColor = 'black';
	rowHover=id;
}

function init() {
	var i,row;
	for(i=0;i<totalRow;i++){
		row = getObject('tr'+i);
		row.style.backgroundColor = bgColor[i];
	}

	localdiff = getObject('localdiff');
	if(localdiff) localdiff.value = Math.round(new Date().getTime()/1000) - 60*(new Date().getTimezoneOffset());
	dlocaltime = getObject('dlocaltime');
	var TheLocalTime = new Date();
	if(dlocaltime) dlocaltime.firstChild.nodeValue = '['+gibzero(TheLocalTime.getHours())+':'+gibzero(TheLocalTime.getMinutes())+':'+gibzero(TheLocalTime.getSeconds())+']';

	dlocaltime = getObject('dlocaltime');
	sdt = getObject('sdt');
	update();
}

function entier(n){
  return Math.floor(n);
}

function Second2HMS(second){
	if(isNaN(second)) return 'NaN';

	var negative = false;
	if(second<0){
		negative = true;
		second = Math.abs(second);
	}
	var time = new Array(second,0,0);
	var return_str = '0s';
	if(time[0] >= 60){
		time[1]=entier(time[0] / 60);
		time[0]=time[0] % 60;
		if(time[1] >= 60){
			time[2]=entier(time[1] / 60);
			time[1]=time[1] % 60;
		}
	}
	if(time[2]!=0&&time[0]!=0) return_str = time[2]+'h'+(time[1]!=0?time[1]+'m':'');
	else return_str = (time[2]!=0?time[2]+'h':'')+(time[1]!=0?time[1]+'m':'')+(time[0]!=0?time[0]+'s':'');
	if(negative) return_str = '-'+return_str;
	return return_str;
}


function gibzero(number){
	if(isNaN(number)) return '0';

	return ((number<10)?'0'+number:number);
}

function update() {
	var i, mvpRow, etaField, etaInt;
	localTime = new Date().getTime();
	serverTime = localTime + offsetTime;
	var serverTime_date = new Date(serverTime).toString();
	for(i=0;i<totalRow;i++){
		if(time_arr[i]>0){
			etaField = getObject('eta'+i);
			etaInt = time_arr[i]-Math.round(serverTime/1000);
			etaField.firstChild.nodeValue = Second2HMS(etaInt);
			if(etaInt<=0)
				bgColor[i]='red';
			else if(etaInt<=600)
				bgColor[i]='orange';
			else
				bgColor[i]='green';
			if(rowHover!=i){
				mvpRow = getObject('tr'+i);
				mvpRow.style.backgroundColor = bgColor[i];
			}
		}
	}
	var fserverTime = localTime + 60000*(new Date().getTimezoneOffset()) + offsetTime - 1000*GMTint + 1000*timediff;
	var tmptime = new Date(fserverTime);
	//var fserverTime_date = new Date(fserverTime).toString();
	//sdt.firstChild.nodeValue = fserverTime_date.substring(0,fserverTime_date.indexOf('GMT'));
	//var fserverTime_date = new Date(fserverTime).getYear();
	var fserverTime_date = tmptime.getFullYear()+'-'+gibzero(tmptime.getMonth()+1)+'-'+gibzero(tmptime.getDate())+', ['+gibzero(tmptime.getHours())+':'+gibzero(tmptime.getMinutes())+':'+gibzero(tmptime.getSeconds())+']';
	sdt.firstChild.nodeValue = fserverTime_date;
	setTimeout("update()", 1000);
}