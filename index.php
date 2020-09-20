<?php

error_reporting(E_ALL);
ini_set('display_errors',1);
define('BASE_DIR', __DIR__);
define('INC_DIR', __DIR__.'/inc/');
define('PDF_DIR', __DIR__.'/pdf/');

function sethead($msg, $code = 404) { http_response_code($code); echo '<p class="bad-response">'.$msg.'</p>'; }
function buildMenu($items, $current = null) {
	foreach($items as $url => $name) {
		if(!$name) $name = ucfirst($url);
		$active = isset($current[$url]) ? ' class="active"' : '';
		echo '<li'.$active.'><a href="/'.$url.'">'.$name.'</a></li>'.PHP_EOL.str_repeat("\t",3);
	}
}
function showAlert($msg, $ok = null) {
	echo '<div class="alert alert-'.($ok ? 'success' : 'fail').'" role="alert">'.
	'<strong>'.($ok ? 'Well done!' : 'Whoa!').'</strong> '.$msg.
	'</div>'.PHP_EOL;
}

const J_NAME = 'The Cuban Scientist';
const J_ABBR = 'Cuban Sci.';
const J_LANG = 'Eng';
const J_YEAR = 2019;
const J_ISSN = '2673-494X';
const PDF_PREF = 'TCS';
$meta = 'Two-page Reports on Science Made by Cubans';

$doi = array(
        'addr' => '',
        'pref' => '',
        'name' => 'TCS'
);
define('DOI_ADDR', $doi['addr'].$doi['pref']);


$path = preg_match('/[\w\-.]+/',$_SERVER['REQUEST_URI'],$path) ? $path[0] : '';
$canonical = 'https://cubanscientist.org'.$_SERVER['REQUEST_URI'];
$page = array(
	'home' => '',
	'archive' => '',
	'authors' => 'For Authors',
	'editorial' => 'Editorial Board',
	'contacts' => 'Contact'
);
$assist = array(
//	'tools' => '',
	'login' => ''
);
$param = array(
	'q' => 'Search results for',
	'sec' => 'Articles',
	'vol' => '',
	'issue' => '',
	'page' => ''
);
$prefix = array();
$desc = '';
$i = 0;

ob_start();
include INC_DIR.'usermodule.php';
include INC_DIR.'postabs.php';

$desc = $meta;
$all = $page + $assist;
unset($all['home']);
if(isset($all[$path])) {
	foreach($param as $k => $val) {
		if(!$val) $val = ucfirst($k);
		else if($i++) {
			if(!$prefix && !empty($_GET[$k])) $prefix[] = $val;
			continue;
		}
		if(!empty($_GET[$k])) $prefix[] = $val.' '.$_GET[$k];
	}
	if(!$prefix) $prefix[] = $all[$path] ? $all[$path] : ucfirst($path);
} else if(!$path) {
	$path = 'home';
}
$google_scholar = '';
$og = array();
$current = array($path => true);
$template = './pages/'.$path;
if(is_file($template.'.html')) include $template.'.html';
elseif(is_file($template.'.php')) include $template.'.php';
else sethead('Page not found '.$template);
$output = ob_get_contents();
ob_end_clean();

if($prefix) $prefix[] = '- ';

if(!array_key_exists('title', $og)) $og['title'] = implode(' ', $prefix).J_NAME;
if(!array_key_exists('desc', $og)) $og['desc'] = $meta;
if(!array_key_exists('image', $og)) $og['image'] = 'https://cubanscientist.org/img/logo_og.png';
if(!array_key_exists('image_type', $og)) $og['image_type'] = 'image/png';

isset($mysqli) && $mysqli->close();
?>
<!DOCTYPE html>
<html>
<head>
	<link rel="canonical" href="<?=$canonical?>"/>
	<script async src="https://www.googletagmanager.com/gtag/js?id=UA-166272202-1"></script>
	<script>
		window.dataLayer = window.dataLayer || [];
		function gtag(){dataLayer.push(arguments);}
		gtag('js', new Date());
		gtag('config', 'UA-166272202-1');
	</script>
	<meta charset="UTF-8">
	<title><?=$og['title']?></title>
	<link rel="stylesheet"
          href="https://fonts.googleapis.com/css?family=Fredericka+the+Great|Lato|Lato:i,b">
	<link href="/assets/style.css" rel="stylesheet">
	<meta name="viewport" content="width=device-width">
	<meta property="og:title" content="<?=$og['title']?>">
	<meta property="og:description" content="<?=$og['desc']?>">
	<meta property="og:type" content="website">
	<meta property="og:url" content="<?=$canonical?>">
	<meta property="og:image" content="<?=$og['image']?>">
	<meta property="og:image:type" content="<?=$og['image_type']?>" />
	<meta property="og:image:width" content="100" />
	<meta property="og:image:height" content="100" />
	<meta name="twitter:card" content="summary">
	<meta name="twitter:site" content="@CubanScientist">
	<meta name="twitter:title" content="<?=$og['title']?>">
	<meta name="twitter:description" content="<?=$og['desc']?>">
	<meta name="twitter:image" content="<?=$og['image']?>">
	<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js"></script>
	<meta name="description" content="<?=$desc?>">
	<?=$google_scholar?>
	<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/cookie-bar/cookiebar-latest.min.js?always=1&showNoConsent=1"></script>
</head>
<body>
	<div class="container color">
		<div class="header">
			<div class="logo"><a href="/"><img src="/img/logo.png" alt="logo"></a></div>
			<div class="issn">ISSN <?=J_ISSN?></div>
			<ul class="social">
				<li><a href="https://twitter.com/CubanScientist" target="_blank" class="twitter-follow-button" data-show-count="false">
					<img alt="Qries" src="/img/tw1.png">
				</a></li>
				<li><a href="https://www.facebook.com/TheCubanScientistJournal" target="_blank" class="twitter-follow-button" data-show-count="false">
					<img alt="Qries" src="/img/fb2.png">
				</a></li>
			</ul>
			<div class="title"> <span><?=J_NAME?></span></div>
		</div>		
		<ul class="nav">
			<?php
				buildMenu($page + $assist, $current);
			?>
		</ul>
	</div>
	<div class="container">
		<div class="page <?=$path?>">
			<?php
			echo $output;
			?>
		</div>
		<div class="footer">
			<div style="float: left; clear: right"> 
			  <span>&copy; 2020 <?=J_NAME?></span>
		    </div>
            <div style="float: right"><a href="#" onclick="document.cookie='cookiebar=;expires=Thu, 01 Jan 1970 00:00:01 GMT;path=/'; setupCookieBar(); return false;">Revoke Cookie consent</a> • Website operates on <a href="https://github.com/6o6o/sci-journal-mgr"; align="right">sci-j-mgr</a></div>
		</div>
	</div>
<?php if (preg_match('/\/home$/', $template)): ?>
	<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
	<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
	<script type="text/javascript">
	google.charts.load('current', {
		'packages':['geochart'],
	});
	google.charts.setOnLoadCallback(drawRegionsMap);
	function drawRegionsMap() {
<?php endif; ?>
<?php
if (preg_match('/\/home$/', $template)) {
	echo 'var data = new google.visualization.DataTable('.$geo_data.');';
}
?>
<?php if (preg_match('/\/home$/', $template)): ?>
	var chart = new google.visualization.GeoChart(document.getElementById('chart-geo'));
	chart.draw(data, {
		width:620,
//		height:350,
		region:'world',
		colorAxis: {colors: ['chartreuse', 'darkgreen']},
		legend:'none',
//		title:'Distribution by country',
		keepAspectRatio:true,
	});
	}
    </script>
<?php endif; ?>
<?php if (preg_match('/\/home$/', $template)): ?>
	<script type="text/javascript">

	google.charts.load('current', {'packages':['corechart']});
	google.charts.setOnLoadCallback(drawChart);

	function drawChart() {
<?php endif; ?>
<?php
if (preg_match('/\/home$/', $template)) {
	echo 'var data = new google.visualization.DataTable('.$chart_data.');';
}
?>
<?php if (preg_match('/\/home$/', $template)): ?>
	var chart = new google.visualization.PieChart(document.getElementById('chart-pie'));
	chart.draw(data, {
//		title:'Distribution by section',
		width:370,
//		height:400,
		chartArea:{left:10,top:10,width:'100%',height:'100%'},
		pieStartAngle:0,
		fontSize:12,
		pieSliceText:'percentage',
		legend:{position:'right',alignment:'start'},
		enableInteractivity:false,
	});
	}
	</script>
<?php endif; ?>
	<script type="text/javascript" src="/assets/script.js"></script>
</body>
</html>
