<?php
require_once(INC_DIR.'dbconn.php');
$subj = $db->getAll();
function prVal($c, $tx = '') {
	global $rec;
	$def = ' rows="';
	if($rec) {
		$val = htmlspecialchars($rec[$c]);
		if($tx) {
			$num = count(explode("\r\n", $val));
			$def .= $num > $tx ? $num : $tx;
			$val = $def.'">'.$val;
		} else $val = ' value="'.$val.'"';
	} else if($tx) {
		$val = $def.$tx.'">';
	} else $val = '';
	return ' name="'.$c.'"'.$val;
}
$isupdate = false;
if(!empty($_GET['page'])) {
	$isupdate = true;
	$rec = $db->getRow($_GET, TBL_CON);
	if($rec) {
//		array_unshift($prefix, 'Edit');
		$rec['arg'] = array_slice($rec,0,3);
		$_GET = array_slice($rec,0,4);
	}
} else {
	$rec = $db->getNext();
}
?>
		<div class="addabs">
			<form action="/<?=$isupdate ? 'archive/'.implode('/', $rec['arg']) : $path?>" name="newabs" method="post" enctype="multipart/form-data">
				<h3><?=$isupdate ? 'Edit existing' : 'Submit new' ?> report</h3>
				<div class="row">
				<div class="dbl">
					<div class="dbl"><span>Vol:</span><input type="text" maxlength="2" <?=prVal('vol')?>></div>
					<div class="dbl rht"><span>Issue:</span><input type="text" maxlength="2"<?=prVal('issue')?>></div>
				</div>
				<div class="dbl rht">
					<div class="dbl"><span>Start page:</span><input type="text" maxlength="4"<?=prVal('page')?>></div>
					<div class="dbl rht"><span>End page:</span><input type="text" maxlength="4"<?=prVal('end_page')?>></div>
				</div>
				</div>
				<div class="row">
					<div class="dbl">
					<span>Section:</span><select name="section">
						<option disabled selected>Please select...</option><?php
						foreach($subj as $k => $v) {
							$sel = $k == $rec['section'] ? ' selected' : '';
							echo '<option value="'.$k.'"'.$sel.'>'.$v.'</option>';
						}
						?>
						<!--<option>Add new section...</option>-->
					</select></div>
					<!--<div class="dbl rht"><span>DOI:</span><input type="text" maxlength="255"<?=prVal('doi')?>></div>-->
				</div>
				<div class="row"><span>Title:</span><input type="text" maxlength="255"<?=prVal('title')?>></div>
				<div class="row"><span>Author:</span><input type="text" maxlength="255"<?=prVal('author')?>></div>
				<div class="row"><span>Institute:</span><textarea<?=prVal('inst', 2)?></textarea></div>
				<div class="row"><span>Abstract:</span><textarea<?=prVal('abstract', 5)?></textarea></div>
				<div class="row"><span>Keywords:</span><input type="text" maxlength="255"<?=prVal('keywords')?>></div>
				<div class="row"><span>References:</span><textarea<?=prVal('refs', 2)?></textarea></div>
				<?php foreach($_GET as $k => $v)
				echo '<input type="hidden" name="'.preg_replace('/\W/','',$k).'" value="'.($v*1).'">';
				?>
<?php if (!$isupdate): ?>
				<div class="row">
					<span>Report (PDF):</span><input type="file" name="pdf_file" accept=".pdf">
				</div>
				<div class="row">
					<span>Image (JPG):</span><input type="file" name="img_file" accept=".jpg" optional>
				</div>
				<div class="row">
					<span>Source (ZIP):</span><input type="file" name="zip_file" accept=".zip">
				</div>
<?php endif; ?>
				<div class="norow">
					<!--<label class="box"><input type="checkbox" id="ignore"><span>Ignore warnings</span></label>-->
					<button class="btn btn-blu">Submit</button>
				</div>
			</form>
		</div>
