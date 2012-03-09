<?php
/********************************************************

 Copyright (C) 2012 Gandi <gandisuATgmailDOTcom>, Kageno <kagenoATfreeDOTfr>

 Permission is hereby granted, free of charge, to any person obtaining a copy of this software
 and associated documentation files (the "Software"), to deal in the Software without restriction,
 including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
 and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so,
 subject to the following conditions:

 The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

 THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
 IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*********************************************************/
require('config.inc.php');
$post=array();

//check if there is relevant POST data
if(count($_POST)>0){
	// Get postdata -- define key and value we need from $_POST
	$input=array('settime' => 0,
			'setffa' => 0,
			'ffa' => 0,
			'resettime' => 0,
			'id' => 0,
			'count' => 0,
			'newuser' => '',
			'note' => '',
			'newtime' => '');
	foreach($_POST as $key1 => $value1){	// scan all pairs and retrieve those we want (if they are present). If not they will have the default value
		if(array_key_exists($key1,$input))
			$input[$key1]=$value1;
	}
	// Now we validate the data in $input
	$validated=TRUE;	// if even one value is not validated, turn this bool to FALSE

	if(!is_numeric($input['id'])||$input['id']<=0){
		$validated=FALSE;
		$input['id']=0;
	}

	// no command? -- do nothing
	if(!$input['settime']&&!$input['resettime']&&!$input['setffa'])
		$validated=FALSE;


	//check if spawntime is free (multiguild mode)
	if($validated && $config['multiguild']) {
		$sql = sprintf("SELECT ffa,lastkill,spawntime,lastname FROM %s WHERE id='%d'",
			$config['table'],
			$input['id']);
		$sql_result = mysql_query($sql) or die("A critical MySQL error has occurred.<br />Your Query: " . $sql . "<br /> Error: (" . mysql_errno() . ") " . mysql_error());
		if($sql_result&&mysql_num_rows($sql_result)==1){
			$curinfo = mysql_fetch_array($sql_result);
			if(! (($curinfo['lastname']==$_SERVER['REMOTE_USER']) || ($curinfo['ffa']) || (($time_sec-$curinfo['lastkill'])>$curinfo['spawntime']*60*2)) )
				$validated=FALSE;
		} else
			$validated=FALSE;
	}

	if($validated && array_key_exists('settime',$_POST) && !($config['multiguild'] && array_key_exists('setffa',$_POST))){
		$input['count']=1;
		$input['settime']=1;
		$input['ffa']=0;
		if($input['newtime']!=""){
			$date_arr=getdate($time_sec-$timediff);
			if(preg_match("/^([01]?[0-9]|2?[0-3]):([0-5]?\d):?([0-5]?\d)?$/i",$input['newtime'],$match)){
				if($match[1]>=24) $validated=FALSE;
				if($match[2]>=60) $validated=FALSE;
				if(!array_key_exists(3,$match))
					$match[3]=0;
				elseif($match[3]>=60)
					$validated=FALSE;
			} elseif(is_numeric($input['newtime'])) {
				if(($input['newtime'] < 60) && ($input['newtime'] >= 0)) {
					$match = array();
					$match[1] = $date_arr['hours'];
					$match[2] = $input['newtime'];
					$match[3] = 0;
				} else $validated=FALSE;
			} else $validated=FALSE;
			if($validated) {
				$input['newtime']=mktime($match[1], $match[2], $match[3], $date_arr["mon"], $date_arr["mday"], $date_arr["year"]);
				$input['newtime'] -= $timediff;
				//1-day fix (e.g.: 2 hour mvp got killed at 23:00, if entering the time after midnight, the time would falsely be assumed to have happened one day later
				//time can be 5 minutes in the future at max, else assume the kill happened yesterday
				if(($input['newtime'] - $time_sec) > 60*5)
					$input['newtime'] -= 60*60*24;
			}
		} else $input['newtime']=$time_sec;
	}
	elseif($validated && array_key_exists('resettime',$_POST)){
		$input['count']=0;
		$input['resettime']=1;
		$input['newtime']=0;
		$input['note']="";
		$input['ffa']=0;
	}
	elseif($validated && array_key_exists('setffa',$_POST) && $config['multiguild']){
		if(($time_sec-$curinfo['lastkill'])<$curinfo['spawntime']*60*2.5) {
			$input['ffa'] = ($input['ffa']==1?0:1);
			$input['count']=0;
			$input['newtime'] = $curinfo['lastkill'];
		} else
			$validated=FALSE;
	}
	
	if($validated){ // We update the database
		// Get user infos
		$user=array('ip' => $_SERVER["REMOTE_ADDR"],
				'agent' => (isset($_SERVER["HTTP_USER_AGENT"])?$_SERVER["HTTP_USER_AGENT"]:""),
				'host' => @gethostbyaddr($_SERVER["REMOTE_ADDR"]));
		$input['newuser'] = $_SERVER['REMOTE_USER'];

		// get old infos
		$sql = sprintf("SELECT lastkill FROM %s WHERE id='%d'",
			$config['table'],
			$input['id']);
		$sql_result = mysql_query($sql) or die("A critical MySQL error has occurred.<br />Your Query: " . $sql . "<br /> Error: (" . mysql_errno() . ") " . mysql_error());
		if($sql_result&&mysql_num_rows($sql_result)==1){
			$old = mysql_fetch_array($sql_result);

			// insert update
			$sql = sprintf("UPDATE %s SET lastkill='%d', lastname='%s', note='%s', ffa='%d', count=count+'%d' WHERE id='%d'",
				$config['table'],
				$input['newtime'],
				($input['resettime']?'':mysql_real_escape_string_fixed($input['newuser'])),
				mysql_real_escape_string_fixed(mb_substr(htmlspecialchars_decode($input['note']),0,255)),
				$input['ffa'],
				$input['count'],
				$input['id']);
			$sql_result = mysql_query($sql) or die("A critical MySQL error has occurred.<br />Your Query: " . $sql . "<br /> Error: (" . mysql_errno() . ") " . mysql_error());

			// insert log
			$sql = sprintf("INSERT INTO %s
						(ip, host, agent, changetime, user, note, ffa, mvp_id, time_old, time_new)
					VALUES ('%s', '%s', '%s', '%d', '%s', '%s', '%d', '%d', '%d', '%d')",
					$config['table2'],
					$user['ip'],
					mysql_real_escape_string_fixed($user['host']),
					mysql_real_escape_string_fixed($user['agent']),
					$time_sec,
					mysql_real_escape_string_fixed($input['newuser']),
					mysql_real_escape_string_fixed(mb_substr(htmlspecialchars_decode($input['note']),0,255)),
					$input['ffa'],
					$input['id'],
					$old['lastkill'],
					$input['newtime']);
			$sql_result = mysql_query($sql) or die("A critical MySQL error has occurred.<br />Your Query: " . $sql . "<br /> Error: (" . mysql_errno() . ") " . mysql_error());
		}
	}
	header("Location: http://".$_SERVER["HTTP_HOST"] . $_SERVER["SCRIPT_NAME"]); // Clear the POSTDATA that browser stores when filling web forms, and then asks you to resend when hitting F5 to refresh page
}

if($autorefresh>0){
	//fix for chrome breaking autorefresh
	if(strpos(@strtoupper($_SERVER["HTTP_USER_AGENT"]), 'CHROME'))
		header('Refresh: '.($autorefresh).'; url=http://'.$_SERVER["HTTP_HOST"] . $_SERVER["SCRIPT_NAME"]);
	else
		header('Refresh: '.($autorefresh).'; url='.$_SERVER["REQUEST_URI"]);
}

// the portion below seriously needs some kind of template system
?>
<!DOCTYPE html
PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Ragnarok Online MVP Timer - Main</title>
<link rel="stylesheet" href="css/style.css" type="text/css" />
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
</head>
<body onload="init()">
<h1>Ragnarok Online MVP Timer</h1>
<? if($_SERVER['REMOTE_USER']=='Guest')
	echo '<p>Notice: There doesn\'t appear to be .htaccess restriction in place, if you\'re looking for the proper value for your AuthUserFile it should be:<br />',preg_replace('/\/[^\/]*\z/','/.htpasswd',$_SERVER['SCRIPT_FILENAME']),'</p>';
?>
Server date and time is<? echo (isset($_COOKIE['timediff'])?" synced to":""); ?>: <b><span id="sdt">-</span></b> <? /*echo date("\G\M\T O"); */?> <? /*echo date("I")>0?"[Daylight Saving ON]":""; */?>
<?
	if(!isset($_COOKIE['timediff']))
		echo "<br/>\n(You can have your local time synced with the server if the time(zones) are different -- there's really no reason not to do this)\n<br/>";
?>
<form action="http://<? echo $_SERVER["HTTP_HOST"] . $_SERVER["SCRIPT_NAME"]; ?>" method="post" target="_self">
	<input type="submit" class="button" name="setdiff" value="Sync" />
	<input type="submit" class="button" name="resetdiff" value="Reset" />
	<input type="hidden" name="remotediff" value="<? echo ($time_sec-$gmtint); ?>" />
	<input id="localdiff" type="hidden" name="localdiff" value="" />
</form>
<br/>
Auto-refresh the page every X seconds (10s min, 3600s max)
<br/>
<form action="http://<? echo $_SERVER["HTTP_HOST"] . $_SERVER["SCRIPT_NAME"]; ?>" method="post" target="_self">
	<input class="field2" name="refreshtime" type="text" maxlength="4" value="<? echo $autorefresh; ?>" />
	<input type="submit" class="button" name="refresh" value="Set" />
	<input type="submit" class="button" name="resetrefresh" value="Reset" />
</form>

<?
// get all MVP/miniboss infos
$sql="SELECT id,name,type,spawntime,spawnvariance,lastkill,note,lastname,ffa,count FROM ".$config['table']." ORDER BY name";
$sql_query = mysql_query($sql) or die("A critical MySQL error has occurred.<br />Your Query: " . $sql . "<br /> Error: (" . mysql_errno() . ") " . mysql_error());
while ($result=mysql_fetch_array($sql_query)) {
	$mvp_id=intval($result['id']);
	$mvp[$mvp_id]['type'] = intval($result['type']);
	$mvp[$mvp_id]['name'] = $result['name'];
	$mvp[$mvp_id]['spawntime'] = $result['spawntime'];
	$mvp[$mvp_id]['spawnvariance'] = $result['spawnvariance'];
	$mvp[$mvp_id]['lastkill'] = intval($result['lastkill']);
	$mvp[$mvp_id]['count'] = $result['count'];
	$mvp[$mvp_id]['lastname'] = $result['lastname'];
	$mvp[$mvp_id]['ffa'] = $result['ffa'];
	$mvp[$mvp_id]['note'] = $result['note'];
} ;

$rownum=0;
$time_arr=array();
foreach($config['types'] as $key1 => $value1){
	if ($value1['active']){
?>
<br/>
<table width="<? echo (($config['multiguild'])?"840":"800"); ?>" border="0" align="center" cellpadding="0" cellspacing="0">
	<caption>
		<?php echo htmlspecialchars($value1['name'])." Timer";?><br/>
		<a name="type<? echo $key1; ?>" href="http://<? echo $_SERVER["HTTP_HOST"] . $_SERVER["SCRIPT_NAME"] . "?" . $linkappend . "#type" . $key1; ?>">Reload page</a>
	</caption>
	<tr>
		<th width="180" class="name">Name</th>
		<th width="60" class="eta">ETA</th>
		<th width="100" class="updater">User</th>
		<th width="<? echo (($config['multiguild'])?"270":"240"); ?>" align="left" class="time"><span class="fill"></span><span class="note">Note</span>Time</th>
		<th width="70" class="spawntime">Spawntime</th>
		<th width="70" class="interval">Interval</th>
	</tr>
<?php
		foreach($mvp as $key => $value){
			if($value['type']==$key1){

				// calculate hour/minutes spawntime string
				$spawntitle = "";
				$spawntitle2 = "";
				$spawntitle_tmp = floor($value['spawntime'] / 60);
				if($spawntitle_tmp > 0)
					$spawntitle .= ($spawntitle_tmp!=1)?$spawntitle_tmp." Hours":$spawntitle_tmp." Hour";
				$spawntitle_tmp = $value['spawntime'] % 60;
				if($spawntitle_tmp >0){
					if($spawntitle != "")
						$spawntitle .= " ";
					$spawntitle .= ($spawntitle_tmp!=1)?" ".$spawntitle_tmp." Minutes":" ".$spawntitle_tmp." Minute";
				}
				// calculate spawnvariance string if necessary
				if($value['spawnvariance']) {
					$spawntitle2 .= " ~ ";
					$spawntitle_tmp = floor(($value['spawntime']+$value['spawnvariance']) / 60);
					if($spawntitle_tmp > 0)
						$spawntitle2 .= ($spawntitle_tmp!=1)?$spawntitle_tmp." Hours":$spawntitle_tmp." Hour";
					$spawntitle_tmp = ($value['spawntime']+$value['spawnvariance']) % 60;
					if($spawntitle_tmp >0){
						if($spawntitle2 != " ~ ")
							$spawntitle2 .= " ";
						$spawntitle2 .= ($spawntitle_tmp!=1)?$spawntitle_tmp." Minutes":$spawntitle_tmp." Minute";
					}
				}

				// calculate next spawntime
				$time_arr[$rownum]=0;
				$is_private = 0;
				$is_ffa = 0;
				if($value['lastkill']){
					$time_arr[$rownum]=$value['lastkill']+$value['spawntime']*60;
					// don't display next spawn time when it is outdated by more than 1.5 x spawninterval
					// OR when in multimode, not ffa, not by the current user and not outdated by less than 1x spawninterval
					if($time_sec-$time_arr[$rownum]>$value['spawntime']*60*1.5) {
						$time_arr[$rownum]=0;
						$value['lastname']="";
					}
					elseif($config['multiguild']) {
						if(($_SERVER['REMOTE_USER'] != $value['lastname']) && ($time_sec-$time_arr[$rownum]<$value['spawntime']*60)){
							if(!$value['ffa']) {
								$time_arr[$rownum]=0;
								$is_private = 1;
							} else
								$is_ffa = 1;
						} elseif($value['ffa'])
							$is_ffa = 1;
					}
				} else
					$value['lastname']="";
				// table output
				echo "	<tr id=\"tr$rownum\" onmouseover=\"changeBgColor($rownum)\" onmouseout=\"restoreBgColor($rownum)\">\n";
				echo "		<td class=\"name\" align=\"left\" title=\"". (($value['count']==1)?"Kill: 1":"Kills: ".$value['count']). "\"><a href=\"log.php?id=$key\" class=\"mvplink\">".htmlspecialchars($value['name'])."</a></td>\n";
				echo "		<td class=\"eta\"><span class=\"spaneta\" id=\"eta$rownum\">-</span></td>\n";
				echo "		<td class=\"updater\"".((mb_strlen($value['lastname'])>12)?" title=\"".htmlspecialchars($value['lastname'])."\"":"")."><span>".($is_ffa?"<strike>":"").($value['lastname']!=""?htmlspecialchars(((mb_strlen($value['lastname'])>12)?mb_substr($value['lastname'],0,10)."..":$value['lastname'])):"-").($is_ffa?"</strike>":"")."</span></td>\n";
				echo "		<td class=\"time\">\n";
				if($is_private)
					echo "		<span class=\"privmsg\">Drats! This spawntime is private!</span>\n";
				else {
					echo "		<form action=\"#type$key1\" method=\"post\" target=\"_self\" name=\"mvp$rownum\">\n";
					echo "			<input ".(($value['note'])?"title=\"".htmlspecialchars($value['note'])."\" ":"")."class=\"notefield\" name=\"note\" type=\"text\" maxlength=\"255\" value=\"".htmlspecialchars($value['note'])."\"/>\n";
					echo "			<input class=\"field\" name=\"newtime\" type=\"text\" maxlength=\"8\" title=\"Time format: HH:MM:SS, HH:MM, MM, or leave the field blank for current time\"/>\n";
					echo "			<input class=\"button\" type=\"submit\" name=\"settime\" value=\"Set\" />\n";
					echo "			<input class=\"button\" type=\"submit\" name=\"resettime\" value=\"Reset\" />\n";
					if($config['multiguild']) {
						echo "			<input class=\"ffabutton\" type=\"submit\" name=\"setffa\" value=\"FFA\" />\n";
						echo "			<input class=\"hideme\" type=\"hidden\" name=\"ffa\" value=\"".$value['ffa']."\" />\n";
					}
					echo "			<input class=\"hideme\" name=\"id\" type=\"hidden\" value=\"" . $key . "\" />\n";
					echo "		</form>\n";
				}
				echo "		</td>\n";
				echo "		<td class=\"spawntime\"><span>".($time_arr[$rownum]>0?date("H:i:s",$time_arr[$rownum]+$timediff):"--:--:--")."</span></td>\n";
				echo "		<td class=\"interval\" title=\"".$spawntitle.$spawntitle2."\"><span>".$value['spawntime'].(($value['spawnvariance'])?"~".($value['spawntime']+$value['spawnvariance']):"")."</span></td>\n";
				echo "	</tr>\n";
				$rownum++;
			}
		} 
?>
</table>
<?
	}
}
?>
<br/><a href="log.php">View log</a>
<br/>
<br/>
<br/>This script is freely available at <a href="https://github.com/dangerH/RO-MVP-Timer">github.com</a>!
<script type="text/javascript" language="javascript">
<!--
var bgColor = new Array();
var time_arr = new Array();
var i, totalRow = <? echo $rownum; ?>;
var rowHover = -1; //row number where mouse is hovering
var sdt;
var localdiff, dlocaltime;
var GMTint = <? echo $gmtint; ?>;
var serverTime = <? echo floor(1000*getmicrotime()); ?>;
var localTime = new Date().getTime();
var offsetTime = serverTime - localTime;
var timediff = <? echo $timediff; ?>;
<?php
foreach($time_arr as $key => $value){
	if($value>0) echo "time_arr[$key] = $value;\n";
}
?>
-->
</script>
<script src="js/main.js" type="text/javascript" language="javascript"></script>
</body>
</html>
