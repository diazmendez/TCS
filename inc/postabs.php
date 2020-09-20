<?php
if(!empty($_POST['vol']) && count($_POST) > 2) {
	require_once(INC_DIR.'dbconn.php');
	if($user && $user['priv'] > 1) {
		$aid = $_POST['vol'].'('.$_POST['issue'].'): '.$_POST['page'].'-'.$_POST['end_page'];
		if(isset($_POST['update'])) {
			$res = $db->update(
				$_POST['update'],
				array_intersect_key($_POST, array_flip(array('vol', 'issue', 'page'))),
				TBL_CON
			);
			showAlert(
				'Record <i>'.implode(', ',array_keys($_POST['update'])).'</i> in '.
				$aid.($res ? ' successfully updated' : ' failed updating'),
				$res
			);
		} else {
			$target_dir = PDF_DIR.$_POST['vol'].'/'.$_POST['issue'];
			$target_base = PDF_PREF.'_'.(J_YEAR+$_POST['vol']).'_'.$_POST['vol'].'_'.$_POST['issue'].'_'.sprintf('%03d', $_POST['page']);
			$target_pdf_file = $target_dir.'/'.$target_base.'.pdf';
			$target_zip_file = $target_dir.'/'.$target_base.'.zip';
			$target_img_file = $target_dir.'/'.$target_base.'.jpg';
			if ((is_dir($target_dir) or mkdir($target_dir, 0755, true))
				and rename($_FILES['pdf_file']['tmp_name'], $target_pdf_file) and chmod($target_pdf_file, 0644)
				and rename($_FILES['zip_file']['tmp_name'], $target_zip_file) and chmod($target_zip_file, 0600)
				and (($_FILES['img_file']['name'] === "") or (rename($_FILES['img_file']['tmp_name'], $target_img_file)
				and chmod($target_img_file, 0644)))
			) {				
				$_POST['doi'] = $target_base;
				$res = $db->insert($_POST, TBL_CON);
				showAlert($res ? 'Article '.$aid.' added successfully.' : 'Failed adding article '.$aid.'. Duplicate entry?', $res);
			} else {
				showAlert('Problem uploading file');
			}
		}
	} else showAlert('You do not have enough privileges to perform the action');
}
