<?php

$DB_NAME='cokoholici.sqlite';

/*
#!/bin/bash
sqlite cokoholici.sqlite

create table clenovia (
	cislo number(9),
	meno varchar(25),
	mesto varchar(25),
	ulica varchar(14),
	psc number(5));
*/


function pridaj_clena($meno, $mesto, $ulica, $psc) {
	$mid = 0;
	return $mid;
}

function my_query($q) {
	if ($db = sqlite_open($DB_NAME, 0666, $err)) {
		$res = sqlite_query($db, $q, SQLITE_ASSOC);
		if ($res === false) {
			die('zla query');
		}
		$rows = sqlite_fetch_all($res);
		sqlite_close($db);
		return $rows;
	}
	else {
		die($err);
	}

	return $ret;
}

function najdi_clena_podla_id($id) {
	$arr = my_query('select id from clenovia where id='.$id);
	if (count($arr) < 1) {
		return false;
	}
	return $arr[0]['id'];
}

function najdi_clena($meno) {
	$arr = my_query('select id from clenovia where meno='.sqlite_escape_string($meno));
	if (count($arr) < 1) {
		return false;
	}
	return $arr[0]['id'];
}

function zmen_clena($id, $meno, $mesto, $ulica, $psc) {
	$mid == najdi_clena_podla_id($id);
	if ($mid === false) {
		$mid = najdi_clena($meno);
	}
	if ($mid === false) {
		return false;
	}
	$_ = my_query('update clenovia set meno='.sqlite_escape_string($meno).', mesto='.sqlite_escape_string($mesto).', ulica='.sqlite_escape_string($ulica).', psc='.$psc.' where id='.$mid);

	return true;
}

?>
