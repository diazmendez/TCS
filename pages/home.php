<?php
function cite($a) {
	return '<cite><abbr>'.
	J_ABBR.'</abbr> <span>'.
	(J_YEAR+$a[0]).', '.
	$a[0].'('.$a[1].')'.
	(isset($a[3]) ? ': '.
	$a[2].'&ndash;'.$a[3]
	: '').'</span></cite>';
}
function check($a, $d = 0) { return !empty($_GET[$a]) || $d ? ' checked' : ''; }
function plural($n, $a) { return '<div>'.$n.' '.$a.($n == 1 ? '' : 's').'</div>'; }
function linkarc($a) { return '/archive/'.implode('/', array_slice($a, 0, 3)); }
function linkedt($a, $c) { return linker('/newabs?vol='.$a[0].'&amp;issue='.$a[1].'&amp;page='.$a[2], 'Edit', $c); }
function linkpdf($a) {
	return '/pdf/'.$a[0].'/'.$a[1].'/'.
	PDF_PREF.'_'.($a[0]+J_YEAR).'_'.$a[0].'_'.$a[1].
	(isset($a[2]) ? '_'.str_pad($a[2],3,0,STR_PAD_LEFT) : '').'.pdf';
}
function linkimg($a) {
	return 'pdf/'.$a[0].'/'.$a[1].'/'.
	PDF_PREF.'_'.($a[0]+J_YEAR).'_'.$a[0].'_'.$a[1].
	(isset($a[2]) ? '_'.str_pad($a[2],3,0,STR_PAD_LEFT) : '').'.jpg';
}
function linker($a, $n = '', $x = '') {
	if(is_array($a)) {
		if($a[1] != 'http') {
			$n = $a[0];
			$a = 'https://'.$n;
		} else $a = $a[0];
	}
	if($x) $x = ' class="'.$x.'"';
	else if(!$n) $n = $a;
	return $a ? '<a href="'.$a.'"'.$x.'>'.$n.'</a>' : '';
}
function humansize($bytes, $decimals = 2) {
  $sz = 'BKMGTP';
  $factor = floor((strlen($bytes) - 1) / 3);
  if(!(strlen($bytes)%2)) $decimals = 0;
  return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
}
function getval($a, $name = '', $int = false) {
	$q = '"';
	if(!empty($_GET[$a])) {
		$val = $_GET[$a];
		$num = (int) $val;
		if($val === (string) $num || $int) {
			$val = $num;
			$q = '';
		}
		if((int) $name) $name = $a;
		if($name && $val) $val = $name.'='.$q.$val.$q;
	} else $val = '';
	return $val;
}
function mkquery($cls) {
	if(is_array($cls)) {
		foreach($cls as $k => $v) {
			if($v) $cmd[] = "$k $v";
		}
		$cls = implode(' ', $cmd);
	}
	return $cls;
}
require_once(INC_DIR.'dbconn.php');
$subj = $db->getAll();
?>

<div class="about">
<div class="cover">
	<a href="archive/1/1"><img src="/img/cover.png" alt="cover"></a>
</div>

<p class="slogan">
<em>“The Cuban Scientist” invites you to submit two-page summaries of your published work...</em>
</p>

<p>“The Cuban Scientist” (or TCS for short) is a <em>free online journal</em> where Cuban scientists from all branches of science and technology, working inside or outside Cuba, can share their research results with the community in the form of two-page reports, summarizing works already published in peer-reviewed journals.</p>

<h3>Our mission</h3>

<p>TCS’s mission is to establish and strengthen ties among Cuban scientists by contributing to their self-awareness as a community, fostering collaboration and facilitating knowledge-sharing. It aims to serve as a free information layer bypassing geographical and institutional
barriers. Our goal is that TCS one day becomes <em>the</em> reference point for ‘science made by Cubans’</p>

<h3>Who can publish?</h3>

<p>We interpret ‘Cuban’ in an inclusive way, i.e. comprising all scientists who identify themselves with this community, regardless of their place of birth or where they live.</p>

<?php
/*echo '<div><table><tr><th>Section</th><th>Reports</th></tr>';
$query = "SELECT a.name AS sec,SUM(b.section IS NOT NULL) AS total FROM j_section a LEFT JOIN j_content b ON a.id=b.section GROUP BY a.name WITH ROLLUP";
$res = $mysqli->query(mkquery($query));
while ($row = $res->fetch_assoc()) {
	echo '<tr><td>'.$row['sec'].'</td><td style="text-align:right">'.$row['total'].'</td></tr>';
}
echo '</table></div>';*/
?>
</div>

<div class="clr"></div>

<div class="widgets">
<h3>Current distribution by country and section</h3>
<div id="chart-geo" class="chart geo"></div>
<div id="chart-pie" class="chart pie"></div>
</div>

<div class="clr"></div>

<div class="latest">
<h3>Latest reports</h3>

<?php
$query = "SELECT *,date_format(date_in,'%e %b %Y') AS pub_date FROM j_content ORDER BY vol DESC,issue DESC,page DESC LIMIT 6";
$res = $mysqli->query(mkquery($query));
while ($row = $res->fetch_assoc()) {
	echo '<div class="report">';
	$loc = array($row['vol'], $row['issue'], $row['page'], $row['end_page']);
	$img = linkimg($loc);
	$url = linkarc($loc);
	$pdf = linkpdf($loc);
	$edt = $user ? linkedt($loc, 'btn') : '';
	$kwd = array_filter(preg_split('/\ *(,|\r\n)\ */', $row['keywords']));
	foreach($kwd as &$w)
		$w = linker('/archive?abs=on&amp;q='.urlencode(strip_tags($w)), $w);
	if(file_exists($img)) echo '<img src="'.$img.'" width="200px"/>';
	echo '<div class="section">'.$subj[$row['section']].'</div>';
	echo '<div class="paper-title">'.linker($url, $row['title']).'</div>';
	echo '<div class="paper-author">'.$row['author'].'</div>';
	echo '<div>'.cite($loc).'</div>';
	echo '<div class="paper-date">Published '.$row['pub_date'].' '.linker($pdf, 'PDF ('.humansize(@filesize($_SERVER['DOCUMENT_ROOT'].$pdf)).')', 'pdf').'</div>';
	echo '<p class="paper-abstract">'.$row['abstract'].'</p>';
	echo '<p class="paper-keywords"><strong>Keywords:</strong> '.implode(', ', $kwd).'</p>';
	echo '</div>';
	echo '<div class="clr"></div>';
}
?>
</div>

<?php
$sql = "select a.name as sec,sum(b.section is not null) as total from j_section a left join j_content b on a.id=b.section group by sec order by a.id";
$result = $mysqli->query($sql);
$data = array('cols' => array(array('id' => 'sec', 'label' => 'section', 'type' => 'string'), array('id' => 'total', 'label' => 'total', 'type' => 'number')), 'rows' => array());
foreach($result as $row) {
        $data['rows'][] = array('c' => array(array('v' => $row['sec']), array('v' => 0 + $row['total'])));
}

$chart_data = json_encode($data);

$sql = "select inst from j_content";
$result = $mysqli->query($sql);
$data = array('cols' => array(array('id' => 'place', 'label' => 'Place', 'type' => 'string'), array('id' => 'total', 'label' => 'total', 'type' => 'number')), 'rows' => array());

$freq = array();
foreach($result as $row) {
	$one = array();
	foreach(preg_split('/\r\n/', $row['inst'], 0, PREG_SPLIT_NO_EMPTY) as $single) {
		$words = preg_split('/\,\ */', $single);
		$word = end($words);
		if($word == 'UK') { $word = 'United Kingdom'; };
		if($word == 'USA') { $word = 'United States'; };
		if($word == 'Independent Scholar') { $word = 'Cuba'; };
		$one[$word] = 1; # + (array_key_exists($word, $freq) ? $freq[$word] : 0);
	}
	foreach($one as $word => $value) {
		$freq[$word] = 1 + (array_key_exists($word, $freq) ? $freq[$word] : 0);		
	}
}
foreach($freq as $place => $total) {
        $data['rows'][] = array('c' => array(array('v' => $place), array('v' => 0 + $total)));
}
$geo_data = json_encode($data);
?>

<div class="clr"></div>
