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
$log=array();
?>
<!DOCTYPE html
PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Ragnarok Online MVP Timer - Log</title>
<link rel="stylesheet" href="css/style.css" type="text/css" />
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
</head>
<body onload="init()">
<h1>Ragnarok Online MVP Timer</h1>
<?
	echo "Server date and time is".($timediff?" synced to":"").": <b><span id=\"sdt\">-</span></b>";
	if(!$timediff)
		echo "<br/>\n(You can sync with the server if your time is off!)\n<br/>";
?>
<form action="http://<? echo $_SERVER["HTTP_HOST"] . $_SERVER["SCRIPT_NAME"]; ?>" method="post" target="_self">
	<input type="submit" class="button" name="setdiff" value="Sync" />
	<input type="submit" class="button" name="resetdiff" value="Reset" />
	<input type="hidden" name="remotediff" value="<? echo ($time_sec-$gmtint); ?>" />
	<input id="localdiff" type="hidden" name="localdiff" value="" />
</form>
<br />
<?

$sql = sprintf("SELECT COUNT(%s.id) FROM %s,%s",
	$config['table2'],
	$config['table2'],
	$config['table']);
$where = sprintf(" WHERE %s.id=%s.mvp_id",
		$config['table'],
		$config['table2']);

if($config['multiguild'])
	$where .= sprintf(" AND (user='%s' OR %s.ffa=1)",
		mysql_real_escape_string_fixed($_SERVER['REMOTE_USER']),
		$config['table2']);
		

if(isset($_GET['id']) && is_numeric($_GET['id']))
	$where .= sprintf(" AND mvp_id='%d'",
		$_GET['id']);

$sql_query = mysql_query($sql.$where) or die("A critical MySQL error has occurred.<br />Your Query: " . $sql . "<br /> Error: (" . mysql_errno() . ") " . mysql_error());

$logcount = mysql_result($sql_query,0,0);

$pagecount = ceil($logcount/50);

if(isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page']>0) {
	$_GET['page'] = (int) $_GET['page'];
	if($_GET['page']<$pagecount)
		$limit = max(($_GET['page']-1)*50,0);
	else
		$limit = max(($pagecount-1)*50,0);
} else
	$limit = 0;

$page = ($limit/50)+1;

echo "<table width=\"".(($config['multiguild'])?"890":"860")."\" border=\"0\" align=\"center\" cellpadding=\"0\" cellspacing=\"0\">\n";
echo "	<caption>\n\t\t<a href=\"index.php\">Main page</a><br/>\n";

if($logcount >0) {
	$sql = sprintf("SELECT %s.id,%s.mvp_id,%s.note,%s.ffa,changetime,time_new,time_old,user,name,type,agent,ip,host
			FROM %s,%s
			%s
			ORDER BY changetime DESC
			LIMIT %s, 50",
		$config['table2'],
		$config['table2'],
		$config['table2'],
		$config['table2'],
		$config['table2'],
		$config['table'],
		$where,
		$limit);
	$sql_query = mysql_query($sql) or die("A critical MySQL error has occurred.<br />Your Query: " . $sql . "<br /> Error: (" . mysql_errno() . ") " . mysql_error());

	while ($result = mysql_fetch_array($sql_query)) {
		$log[$result['id']]['id'] = $result['id'];
		$log[$result['id']]['mvp_id'] = $result['mvp_id'];
		$log[$result['id']]['ffa'] = $result['ffa'];
		$log[$result['id']]['changetime'] = $result['changetime'];
		$log[$result['id']]['time_new'] = $result['time_new'];
		$log[$result['id']]['time_old'] = $result['time_old'];
		$log[$result['id']]['user'] = $result['user'];
		$log[$result['id']]['mvp_name'] = $result['name'];
		$log[$result['id']]['mvp_type'] = $result['type'];
		$log[$result['id']]['agent'] = $result['agent'];
		$log[$result['id']]['note'] = $result['note'];
		$log[$result['id']]['ip'] = $result['ip'];
		$log[$result['id']]['host'] = $result['host'];
	} ;

	if($pagecount>1) {
		for($i=1;$i<=$pagecount;$i++){
			if($i==2 && $page>8){
				echo "\t\t[..]\n";
				$i = $page - 6;
			}
			elseif($i == $page)
				echo "\t\t[".$page."]\n";
			elseif($pagecount > ($page+7) && $i > ($page+5)){
				echo "\t\t[..]\n";
				echo "\t\t<a class=\"loglink\" href=\"log.php?page=".$pagecount.(isset($_GET['id'])?"&amp;id=".$_GET['id']:"")."\">[$pagecount]</a>\n";
				break;
			}
			else
				echo "\t\t<a class=\"loglink\" href=\"log.php?page=".$i.(isset($_GET['id'])?"&amp;id=".$_GET['id']:"")."\">[$i]</a>\n";
		}
		echo "\t\t<br />\n";
		//double arrow <<
		echo "\t\t".(($pagecount>2&&$page>2)?"<a class=\"loglink\" href=\"log.php?page=1".(isset($_GET['id'])?"&amp;id=".$_GET['id']:"")."\">&lt;&lt;</a>\n":"&lt;&lt;\n");
		//single arrow <
		echo "\t\t".(($pagecount>1&&$page>1)?"<a class=\"loglink\" href=\"log.php?page=".($page-1).(isset($_GET['id'])?"&amp;id=".$_GET['id']:"")."\">&lt;</a>":"&lt;");
		echo "----";
		//single arrow >
		echo "".(($pagecount>1&&$page<$pagecount)?"<a class=\"loglink\" href=\"log.php?page=".($page+1).(isset($_GET['id'])?"&amp;id=".$_GET['id']:"")."\">&gt;</a>\n":"&gt;\n");
		//double arrow >>
		echo "\t\t".(($pagecount>2&&($page+1)<$pagecount)?"<a class=\"loglink\" href=\"log.php?page=".($pagecount).(isset($_GET['id'])?"&amp;id=".$_GET['id']:"")."\">&gt;&gt;</a>\n":"&gt;&gt;\n");
	}
	echo "\t</caption>\n";
?>
	<tr>
		<th class="log-border" width="140">Log Time</th>
		<th class="log-border" width="190">Name</th>
		<th class="log-border" width="100">Type</th>
		<th class="log-border" width="70">Kill Time</th>
<?		if($config['multiguild']) echo "		<th class=\"log-border\" width=\"30\">FFA</th>"; ?>
		<th class="log-border" width="130">User</th>
		<th class="log-border" width="120">Note</th>
		<th class="log-bottom" width="110">IP</th>
	</tr>
<?php
		foreach($log AS $key => $value) {
			if($value['note']=='')
				$value['note'] = "-";
			if(!$config['showip'] || ($config['multiguild'] && $_SERVER['REMOTE_USER'] != $value['user'])) {
				$value['host'] = '***';
				$value['ip'] = 'xx.xx.xx.xx';
			}
			// table output
			echo "	<tr onmouseover=\"this.style.background='#000000'\" onmouseout=\"this.style.background='#333333'\">\n";
			echo "		<td class=\"log-dborder\"><span>" . date("Y-m-d, H:i:s", $value['changetime']+$timediff) ."</span></td>\n";
			echo "		<td class=\"log-dborder\">". (!isset($_GET['id'])?"<a class=\"logname\" href=\"log.php?id=".$value['mvp_id']."\">".htmlspecialchars($value['mvp_name'])."</a>":"<span class=\"logname\">".htmlspecialchars($value['mvp_name'])."</span>"). "</td>\n";
			echo "		<td class=\"log-dborder\"><span>" . ((isset($config['types'][$value['mvp_type']]['name']))?htmlspecialchars($config['types'][$value['mvp_type']]['name']):"Unknown")."</span></td>\n";
			echo "		<td class=\"log-dborder\" title=\"Prior Time: ".(($value['time_old'])?date("H:i:s", $value['time_old']+$timediff):"--:--:--")."\"><span>" . (($value['time_new'])?date("H:i:s", $value['time_new']+$timediff):"[Reset]")."</span></td>\n";
			if($config['multiguild']) echo "		<td class=\"log-dborder\"><span>" .($value['ffa']?"Yes":"-")."</span></td>\n";
			echo "		<td class=\"log-dborder\" title=\"Browser: ".(($value['agent'])?htmlspecialchars($value['agent']):"-")."\"><span>" . (($value['user'])?htmlspecialchars($value['user']):"-") . "</span></td>\n";
			echo "		<td class=\"log-dborder\"".((mb_strlen($value['note'])>15)?" title=\"".htmlspecialchars($value['note'])."\"":"")."><span>" . ((mb_strlen($value['note'])>15)?htmlspecialchars(mb_substr($value['note'],0,12))."..":htmlspecialchars($value['note'])) . "</span></td>\n";
			echo "		<td".(($value['host'])?" title=\"Host: ".htmlspecialchars($value['host'])."\"":"")."><span>" . $value['ip'] . "</span></td>\n";
			echo "	</tr>\n";
		} ;
	} else
		echo "\t</caption>\n\t<tr>\n\t\t<td>No Logs found!</td>\n\t</tr>\n";
	echo "</table>\n";


?>
<br/>
<br/>This script is freely available at <a href="https://github.com/dangerH/RO-MVP-Timer">github.com</a>!
<script type="text/javascript" language="javascript">
<!--
var serverTime = <? echo floor(1000*getmicrotime()); ?>;
var localTime = new Date().getTime();
var offsetTime = serverTime - localTime;
var timediff = <?php echo $timediff; ?>;
var GMTint = <?php echo $gmtint; ?>;
var localdiff, dlocaltime, sdt;
-->
</script>
<script src="js/log.js" type="text/javascript" language="javascript"></script>
</body>
</html>
