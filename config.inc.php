<?php

ERROR_REPORTING(E_ALL);

//////////////////////
// general config
//////////////////////
// name of the DB tables, set up a prefix (e.g. a number) if you want to set up multiple instances
$prefix = '';
$config['table'] = 'ro_mvp_info';
$config['table2'] = 'ro_mvp_log';

$config['table'] = $prefix.$config['table'];
$config['table2'] = $prefix.$config['table2'];

// at what remaining ETA (in minutes) should the row begin displaying in orange?
$config['critical'] = 10;

// when multi-guild mode is enabled, spawntimes will only be visible to the user that entered them unless 'FFA' is set
// experimental!
$config['multiguild'] = 0;

//////////////////////
// monster types
//////////////////////
// the list of monster types can easily be expandanded,
// e.g. adding $config['types'][3]['name'] = "Random" and $config['types'][3]['active']=1 will display
// a new category with every monster inside the DB that has its type set to 3

//name to display for categories and their current state
// regular MVPs
$config['types'][0]['name'] = 'MVP';
$config['types'][0]['active'] = 1; // set to 0 to disable

// guild dungeon MVPs
$config['types'][1]['name'] = 'GD MVP';
$config['types'][1]['active'] = 1; // set to 0 to disable

// minibosses
$config['types'][2]['name'] = 'Miniboss';
$config['types'][2]['active'] = 1; // set to 0 to disable


//////////////////////
// MySQL login data
//////////////////////
$sql_connect = mysql_connect('HOSTNAME', 'USERNAME', 'PASSWORD') OR Die("Could not connect to MySQL server");
$sql_selectdb = mysql_select_db('DATABASE') OR Die("Could not select database");


//////////////////////
// only change the following parts if you know what you're doing
function getmicrotime()
{
	list($usec,$sec)=explode(" ",microtime());
	return((float)$usec+(float)$sec);
}

mb_internal_encoding('UTF-8');
$basetime = getmicrotime();
$time_sec=floor($basetime); //time in seconds
$linkappend = mt_rand(1, 9999); //random numbers to properly refresh even with #anchors set in URL -- probably not the best way to achieve that
if(!isset($_SERVER['REMOTE_USER']) && isset($_SERVER['REDIRECT_REMOTE_USER']))
	$_SERVER['REMOTE_USER'] = $_SERVER['REDIRECT_REMOTE_USER'];
if(!isset($_SERVER['REMOTE_USER']))
	$_SERVER['REMOTE_USER'] = 'Guest';

$gmtstring = date("O");
$gmtint = (int) substr($gmtstring,1,2) * 60;
$gmtint += (int) substr($gmtstring,3,2);
$gmtint *= (strcmp($gmtstring,"-")>0)?60:-60; //server's GMT offset in minutes


//set/load/reset cookie for server sync
$timediff = 0;
if(array_key_exists('resetdiff',$_POST)){
	//delete cookie
	setcookie('timediff','',time()-90000);
} elseif(array_key_exists('setdiff',$_POST)){
	//localdiff, remotediff already had their proper timezone added to them
	$timediff = intval($_POST['localdiff']) - intval($_POST['remotediff']);
	$expiretime = $time_sec+60*60*24*365*2;
	setcookie('timediff', $timediff, $expiretime);
} elseif(isset($_COOKIE['timediff'])) {
	// limit maximum possible timezone difference to 25 hours
	$timediff = (($_COOKIE['timediff']*$_COOKIE['timediff'])<(60*60*25*60*60*25))?$_COOKIE['timediff']:0;
}

//set/load/reset autorefresh cookies
$autorefresh = '';
if(array_key_exists('resetrefresh',$_POST)){
	//delete cookie
	setcookie('refresh','',time()-90000);
} elseif(array_key_exists('refresh',$_POST)){
	//only allow values between 10s - 60min
	if(($_POST['refreshtime'] >= 10) && ($_POST['refreshtime'] <= 3600))
		setcookie('refresh',((int) $_POST['refreshtime']),($time_sec+60*60*24*365*2));
} elseif(isset($_COOKIE['refresh'])){
	//only allow values between 10s - 60min
	if(($_COOKIE['refresh'] >= 10) && ($_COOKIE['refresh'] <= 3600))
		$autorefresh = (int) $_COOKIE['refresh'];
}

//not everyone is running php >= 5.1.0
if (!function_exists("htmlspecialchars_decode")) {
    function htmlspecialchars_decode($string, $quote_style = ENT_COMPAT) {
        return strtr($string, array_flip(get_html_translation_table(HTML_SPECIALCHARS, $quote_style)));
    }
}

//fix mysql_real_escape_string for people having magic quotes turned on
function mysql_real_escape_string_fixed($input) {
	if(get_magic_quotes_gpc()) {
		return(mysql_real_escape_string(stripslashes($input)));
	} else {
		return(mysql_real_escape_string($input));
	}
}


$sql = "SET NAMES 'utf8'";
mysql_query($sql);

?>