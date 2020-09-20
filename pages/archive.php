<?php
function cite($a) {
	return '<cite><abbr>'
	.J_ABBR.' </abbr><span>'.
	(J_YEAR + $a[0]).', '.
	$a[0].'('.$a[1].')'.
	(isset($a[3]) ? ': '.
	$a[2].'&ndash;'.$a[3]
	: '').'</span></cite>';
}

//function mkdoi($a, $b = DOI_ADDR) { return $a ? $b.$a : ''; }
function check($a, $d = 0) {
	return !empty($_GET[$a]) || $d ? ' checked' : '';
}
function plural($n, $a) {
	return '<div>'.$n.' '.$a.($n == 1 ? '' : 's').'</div>';
}
function linkarc($a) {
	return '/archive/'.implode('/', array_slice($a, 0, 3));
}
function linkedt($a, $c) {
	return linker('/newabs?vol='.$a[0].'&amp;issue='.$a[1].'&amp;page='.$a[2], 'Edit', $c);
}
function linkpdf($a) {
	return '/pdf/'.$a[0].'/'.$a[1].'/'.
	PDF_PREF.'_'.($a[0]+J_YEAR).'_'.$a[0].'_'.$a[1].
	(isset($a[2]) ? '_'.str_pad($a[2],3,0,STR_PAD_LEFT) : '').'.pdf';
}
function linkimg($a) {
	return '/pdf/'.$a[0].'/'.$a[1].'/'.
	PDF_PREF.'_'.($a[0]+J_YEAR).'_'.$a[0].'_'.$a[1].
	(isset($a[2]) ? '_'.str_pad($a[2],3,0,STR_PAD_LEFT) : '').'.jpg';
}
function linker($a, $n = '', $x = '') {
	if (is_array($a)) {
		if ($a[1] != 'http') {
			$n = $a[0];
			$a = 'http://'.$n;
		} else $a = $a[0];
	}
	if ($x) $x = ' class="'.$x.'"';
	else if (!$n) $n = $a;
	return $a ? '<a href="'.$a.'"'.$x.'>'.$n.'</a>' : '';
}
function humansize($bytes, $decimals = 2) {
	$sz = 'BKMGTP';
	$factor = floor((strlen($bytes) - 1) / 3);
	if (!(strlen($bytes)%2)) $decimals = 0;
	return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
}
function getval($a, $name = '', $int = false) {
	$q = '"';
	if (!empty($_GET[$a])) {
		$val = $_GET[$a];
		$num = (int) $val;
		if ($val === (string) $num || $int) {
			$val = $num;
			$q = '';
		}
		//if($name * 1) $name = $a;
		if ((int) $name) $name = $a;
		if ($name && $val) $val = $name.'='.$q.$val.$q;
	} else $val = '';
	return $val;
}
function mkquery($cls) {
	if (is_array($cls)) {
		foreach ($cls as $k => $v) {
			if ($v) $cmd[] = "$k $v";
		}
		$cls = implode(' ', $cmd);
	}
	return $cls;
}
function paginate($a, $pg, $adt = '') {
	$i = 0;
	$pgs = explode(',', $a[$pg.'s']);
	$path = '/archive/';
	do $path .= current($a).'/';
	while (next($a) && $pg != key($a));
	foreach ($pgs as $val) {
		if ($val == $a[$pg]) {
			$prev = isset($pgs[$i-1]) ? $pgs[$i-1] : '';
			$next = isset($pgs[$i+1]) ? $pgs[$i+1] : '';
			break;
		}http://127.0.0.1/archive/1/1/pdf/1/1/TCS_2020_1_1_003.jpg
		$i++;
	}
	return '<div class="ctrl"><ul class="pagination"><li>'.
	linker($prev ? $path.$prev : '#', '', 'btn').
	'</li><li><span>'.($i+1).' of '.count($pgs).'</span></li><li>'.
	linker($next ? $path.$next : '#', '', 'btn').'</li></ul>'.
	linker(substr($path, 0, -1), 'Up', 'btn lev').$adt.'</div>';
}

require_once(INC_DIR.'dbconn.php');

include 'newabs.php';

$col = 'vol, issue';
$query = array(
	'SELECT' => $col,
	'FROM' => TBL_CON,
	'JOIN' => '',
	'WHERE' => '',
	'GROUP BY' => $col,
	'ORDER BY' => 'vol DESC'
);
$subsel = 'GROUP_CONCAT(%1$s CAST(%2$s AS CHAR)) AS %2$ss';
$ctncol = 'vol,issue,page,end_page,section,doi,title,author,pdf,date_format(date_in,"%e %b %Y") AS pub_date';
$totrow = 0;
$condic = array();
$subj = $db->getAll();
$cond = array();
$xtra = false;
$qval = getval('q', 'value');
$sec = getval('sec');
$qhelp = 'Minimum 4 letters per full word, or partial word ending with * symbol';

if ($val = getval('vol', 1, 1)) { // identical name, force int
	$query['WHERE'] = $val;
	if ($val = getval('issue', 1, 1)) {
		$query['GROUP BY'] = '';
		$query['SELECT'] = sprintf($subsel, 'DISTINCT', 'issue');
		$query['JOIN'] = '('.mkquery($query).') x';
		$query['SELECT'] = $ctncol.',issues';
		$query['WHERE'] .= ' AND '.$val;
		if ($val = getval('page', 1, 1)) {
			$query['SELECT'] = sprintf($subsel, '', 'page');
			$query['JOIN'] = '';
			$query['JOIN'] = '('.mkquery($query).') x';
			$query['SELECT'] = '*,date_format(date_in,"%e %b %Y") AS pub_date,date_format(date_in,"%Y/%m/%d") AS pub_date_gs';
			$query['WHERE'] .= ' AND '.$val;
		}
	}
} else {
	if ($qval || $sec) {
		$keywords = '';
		$idx = array(
			'abs' => 'title,author,inst,abstract,keywords',
			'refs' => 'refs'
		);
		if (isset($subj[$sec])) $prefix[] = 'in '.$subj[$sec];

		if ($qval) {
			$qval = ' '.$qval;
			$keywords = implode(' +', preg_split('/[^\w*]+/u', $_GET['q'], 0, PREG_SPLIT_NO_EMPTY));
		}

		if ((int) $sec) $cond[] = getval('sec', 'section', 1);
		if ($keywords) {
			foreach ($idx as $k => $val)
				if (isset($_GET[$k]))
					$cmd[$k] = $val;
		} else $cmd = array(0);
		if (!isset($cmd)) $cmd = $idx;

		foreach ($cmd as $k => $val) {
			if ($val) $cond['kw'] = "MATCH($val) AGAINST ('+$keywords' IN BOOLEAN MODE)";
			$cmd[$k] = "SELECT $ctncol FROM ".TBL_CON.($cond ? ' WHERE ' : '').implode(' AND ', $cond);
		}

		$query = implode(' UNION ',$cmd).' ORDER BY vol desc, issue, page';
		$xtra = true;
	} ?>
		<div class="search">
			<form action="archive" method="get">
				<div class="full">
					<input type="text" name="q" placeholder="Search for keywords..." title="<?=$qhelp?>"<?=$qval?>><?php
					$condic[] = ob_get_contents();
					ob_clean(); ?>

				</div>
				<div><label class="box" title="Includes: Title, Author, Institution, Abstract, Keywords">
					<input type="checkbox" name="abs"<?=check('abs',empty($_GET))?>><span>Content</span>
				</label></div>
				<div><label class="box">
					<input type="checkbox" name="refs"<?=check('refs')?>><span>References</span>
				</label></div>
				<div><select name="sec" id="ignore">
					<option selected>All sections</option>
					<?php foreach ($subj as $k => $v) {
						$sel = $sec && $sec === $k ? ' selected' : '';
						echo '<option value="'.$k.'"'.$sel.'>'.$v.'</option>';
					} ?>
				</select></div>
				<div><button class="btn btn-grn">Search</button></div>
			</form>
		</div>
<?php
	$condic[] = ob_get_contents();
	ob_clean();
}

$res = $mysqli->query(mkquery($query));
while ($row = $res->fetch_assoc()) {
	$arc[$row['vol']][$row['issue']][] = $row;
	$totrow++;
}
/*echo '<pre>';
print_r($arc);
echo '</pre>';*/
echo implode($xtra ? plural($totrow, 'result') : '', $condic);
if (isset($arc)) {
$cursec = '';
foreach ($arc as $vol => $issue) {
	$year = J_YEAR + $vol;
	$cur = current($issue);
	$abs = $cur[0];
	if (isset($abs['abstract'])) {
		$opn = '<i>';
		$cls = '</i>';
		$bgn = explode($opn, $abs['keywords']);
		foreach ($bgn as $val) {
			$end = explode($cls, $val);
			if (count($end) > 1)
				$end[0] = $opn.preg_replace('/,\s*/', $cls.', '.$opn, $end[0]).$cls;
			$kwd[] = implode($end);
		}
		$kwd = preg_split('/,\s*/', rtrim(implode($kwd), '.'));
		foreach ($kwd as &$w)
			//$w = linker('/archive?abs=on&amp;q='.urlencode(strip_tags($w)), $w);
			$w = linker('/archive?abs=on&amp;q='.urlencode(strip_tags($w)), $w);
		$loc = array_values(array_slice($abs,0,4));
		$pdf = linkpdf($loc);
		$edt = $user ? linkedt($loc, 'btn') : '';
		echo paginate($abs, 'page', $edt);
		echo '<div>'.cite($loc).'</div>';
		echo '<div class="paper-date">Published '.$abs['pub_date'].' '.linker($pdf, 'PDF ('.humansize(@filesize($_SERVER['DOCUMENT_ROOT'].$pdf)).')', 'pdf').'</div>';
		$img = linkimg($loc);
		echo '<div class="section">'.$subj[$abs['section']].'</div>';
		echo '<div class="paper-title">'.$abs['title'].'</div>';
		echo '<div class="paper-author">'.$abs['author'].'</div>';
		echo '<ul><li>'.implode("</li><li>",array_filter(explode("\r\n",$abs['inst']))).'</li></ul>';
		echo '<div class="panel"><div class="h">Abstract</div><div class="paper-abstract">'.$abs['abstract'].'</div></div>';
		echo '<p><strong>Keywords:</strong> '.implode(', ', $kwd).'</p>';
		if (file_exists($_SERVER['DOCUMENT_ROOT'].$img)) {
			echo '<div class="paper-img"><img src="'.$img.'" width="200px"></div>';
			$og['image'] = 'https://cubanscientist.org'.$img;
			$og['image_type'] = 'image/jpg';
		}
		if (strlen($abs['refs'])) {
			echo '<div class="panel"><div class="h">References</div>';
			echo '<div><ol class="ref"><li>'
				.implode("</li><li>",explode("\r\n",preg_replace_callback('/\b(http|www)([^\s<>"&]|&(?![lg]t;))+\b\/?/','linker',$abs['refs'])))
				.'</li></ol></div>';
		}
		$google_scholar = '<meta name="citation_title" content="'.htmlspecialchars($abs['title']).'"></meta>';
		foreach (preg_split('/\,\ *(and\ *)?/', $abs['author']) as $author) {
			$google_scholar .= '<meta name="citation_author" content="'.htmlspecialchars($author).'"></meta>';
		}
		$google_scholar .= '<meta name="citation_publication_date" content="'.$abs['pub_date_gs'].'"></meta>';
		$google_scholar .= '<meta name="citation_issn" content="'.htmlspecialchars(J_ISSN).'"></meta>';
		$google_scholar .= '<meta name="citation_journal_title" content="'.htmlspecialchars(J_NAME).'"></meta>';
		$google_scholar .= '<meta name="citation_journal_abbrev" content="'.htmlspecialchars(J_ABBR).'"></meta>';
		$google_scholar .= '<meta name="citation_volume" content="'.htmlspecialchars($abs['vol']).'"></meta>';
		$google_scholar .= '<meta name="citation_issue" content="'.htmlspecialchars($abs['issue']).'"></meta>';
		$google_scholar .= '<meta name="citation_firstpage" content="'.htmlspecialchars($abs['page']).'"></meta>';
		$google_scholar .= '<meta name="citation_lastpage" content="'.htmlspecialchars($abs['end_page']).'"></meta>';
		$google_scholar .= '<meta name="citation_pdf_url" content="https://cubanscientist.org'.$pdf.'"></meta>';
//		$og['title'] = J_ABBR.' '.(J_YEAR + $abs['vol']).' '.$abs['vol'].'('.$abs['issue'].') '.$abs['page'].'-'.$abs['end_page'].': “'.$abs['title'].'”, '.$abs['author'];
		$og['title'] = J_ABBR.' '.(J_YEAR + $abs['vol']).' '.$abs['vol'].'('.$abs['issue'].') '.$abs['page'].'-'.$abs['end_page'];
		$og['desc'] = '“'.$abs['title'].'”, '.$abs['author'];
//		$og['desc'] = '“'.$abs['title'].'”, '.$abs['author'];
//		$og['desc'] = $abs['abstract'];
//		echo '<iframe src="http://docs.google.com/gview?url=http://cubanscientist.org'.$pdf.'&embedded=true" style="width:500px; height:700px;" frameborder="0"></iframe>';
	} elseif (isset($abs['title'])) {
		foreach ($issue as $cur) {
			$abs = $cur[0];
			$num = $abs['issue'];
			$loc = array($vol, $num);
			$pdf = linkpdf($loc);
			$img = '<img src="/img/cover_'.$year.'_'.$vol.'_'.$num.'.png" alt="cover">';
			$fullpdf = '';
			if (file_exists('pdf/'.$vol.'/'.$num.'/TCS_'.$year.'_'.$vol.'_'.$num.'.pdf')) {
				$fullpdf = linker(linkpdf($loc), 'Full PDF ('.humansize(@filesize($_SERVER['DOCUMENT_ROOT'].$pdf)).')', 'pdf fullpdf');
			}
			$det = isset($abs['issues']) ? $img.paginate($abs, 'issue').
				'<div>'.cite($loc).'</div>'
				: linker(linkarc($loc), $img.cite($loc));
			echo '<div class="content">';
			echo '<div class="sticky rht">'.$det.'</div>';
			echo '<div class="primary"><h2>'.J_ABBR." $year, Vol. $vol, Issue $num</h2>";
			echo $fullpdf;
			echo plural(count($cur), 'article');
			foreach ($cur as $abs) {
				echo '<div class="entry">';
				echo '<div class="section">'.$subj[$abs['section']].'</div>';
				$loc = array_values(array_slice($abs,0,4));
				$url = linkarc($loc);
				$pdf = linkpdf($loc);
				$img = linkimg($loc);
				$edt = $user ? linkedt($loc, '') : '';
				if (file_exists($_SERVER['DOCUMENT_ROOT'].$img)) echo '<img src="'.$img.'">';
				echo '<div class="paper-title">'.linker($url, $abs['title']).'</div>';
				echo '<div class="paper-author">'.$abs['author'].'</div>';
				echo cite($loc).'  '.$edt;
				echo '<div class="paper-date">Published '.$abs['pub_date'].' '.linker($pdf, 'PDF ('.humansize(@filesize($_SERVER['DOCUMENT_ROOT'].$pdf)).')', 'pdf').'</div>';
				echo '<div class="clr"></div>';
				echo '</div>';
			}
			$og['image'] = 'https://cubanscientist.org/img/cover_'.$year.'_'.$vol.'_'.$num.'.png';
			$og['image_type'] = 'image/png';
		}
	} else {
		$latest = isset($latest) ? false : true;
		echo '<div class="panel">';
		echo '<h3 class="h">'."$year, Volume $vol".'</h3>';
		echo '<div>';
		foreach (array_keys($issue) as $num) {
			$src = '/img/cover_'.$year.'_'.$vol.'_'.$num.'.png';
			$datasrc = '';
			if (!$latest) {
				$datasrc = ' data-src="'.$src.'"';
				$src = 'data:,';
			}
			echo '<a href="/archive/'.$vol.'/'.$num.'" class="issue box">'.
			'<img src="'.$src.'"'.$datasrc.' alt="cover">'.
			'<span class="box">Issue '.$num.'</span>'.
			'</a>'.PHP_EOL;
		}
		echo '</div></div>';
	}
}} else sethead('No records found', $xtra ? 200 : 404);
?>
