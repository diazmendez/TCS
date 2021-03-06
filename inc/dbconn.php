<?php
const TBL_USR = 'j_users';
const TBL_CON = 'j_content';
const TBL_SEC = 'j_section';

$mysqli = new mysqli("localhost", "", "", "");

/* check connection */
if ($mysqli->connect_errno) {
	printf("Connect failed: %s\n", $mysqli->connect_error);
	exit();
}
$mysqli->query('SET NAMES \'utf8\'');

class mydb
{
	private $db;

	function __construct($handle){
		$this->db = $handle;
		$this->prep = array(
			TBL_CON => array(
				'doi' => function(&$a) {
					$base = substr($a, 0, strlen(DOI_ADDR));
					if($base == DOI_ADDR) $a = substr($a, strlen(DOI_ADDR));
				},
				'pdf' => function(&$a) { if($a && !strcasecmp($a, J_LANG)) $a = ''; },
				'section' => function(&$a) {
					if(!intval($a)) {
						$this->insert(array('name' => $a), TBL_SEC);
						$a = $this->db->insert_id;
					}
				}
			)
		);
	}
	function prop($name = '') {
		return $name ? (bool) $this->ex[] = $name : array_shift($this->ex);
	}
	
	function escape($val, $q = '') {
		if(is_bool($val)) {
			$val = $this->prop();
			$q = '';
		}
		return $q.$this->db->real_escape_string($val).$q;
	}
	
	function param($data, $delim = false, $tabcol = 0) {
		$prm = '';
		if(!is_array($data)) $data = array($tabcol => $data);
		foreach($data as $key => $val) {
			if(isset($this->prep[$tabcol][$key]))
				$this->prep[$tabcol][$key]($val);
			if($delim) {
				if($prm) $prm .= " $delim ";
				$prm .= $this->escape($key)." = ".$this->escape($val, "'");
			} else {
				if(!empty($val)) $ins[$this->escape($key)] = $this->escape($val, "'");
			}
		}
		return isset($ins) ? $ins : $prm;
	}
	function insert($data, $tbl = TBL_USR) {
			$data = $this->param($data, false, $tbl);
			return $this->db->query(
				"INSERT INTO $tbl (".
				implode(",", array_keys($data)).") VALUES (".
				implode(",", $data).")"
			);
	}

	function update($data, $cond, $tbl = TBL_USR) {
		$params = $this->param($data, ',', $tbl);
		$cond = $this->param($cond, 'AND', 'id');
		$res = $this->db->query("UPDATE $tbl SET $params WHERE $cond LIMIT 1");
		return $this->db->affected_rows;
	}
	
	function getRow($cond, $tbl = TBL_USR, $expr = '') {
		if($expr) $expr = ','.$expr;
		$cond = $this->param($cond, 'AND', 'token');
		$res = $this->db->query("SELECT *$expr FROM $tbl WHERE $cond LIMIT 1");
		return $res->fetch_assoc();
	}
	
	function mkToken() {
		$tkn = genRand();
		while($this->getRow($tkn)) $tkn = genRand();
		return $tkn;
	}
	function getAll($tbl = TBL_SEC) {
		$tmp = array();
		$res = $this->db->query("SELECT * FROM $tbl");
		//if($res->field_count > 2)
		while ($row = $res->fetch_assoc()) {
			$tmp[$row['id']] = $row['name'];
		}
		return $tmp;
	}
	function getNext() {
		$res = $this->db->query("SELECT vol,issue,end_page+1 AS page,end_page+2 AS end_page,'' AS title,'' AS abstract,'' AS author,'' AS inst,'' AS keywords,'' AS refs FROM j_content ORDER BY vol DESC,issue DESC,page DESC LIMIT 1");
		//if($res->field_count > 2)
		return $res->fetch_assoc();
	}
}

$db = new mydb($mysqli);
