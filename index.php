<?php
error_reporting(-1);

session_start();
$DB_NAME='cokoholici.sqlite';

/*
#!/bin/bash
sqlite cokoholici.sqlite

create table clenovia (
	cisloClena number(9),
	meno varchar(25),
	mesto varchar(25),
	ulica varchar(14),
	psc number,
	typ char(1),
	email varchar2(25)
);

create table mesacnePoplatky (
	cisloClena number(9),
	zaplateny char(1),
	mesiac number,
	rok number
);

create table sluzby (
	nazovSluzby number(9),
	datumPoskytnutia date,
	cisloClena number(9),
	cisloPoskytovatela number(9)
);

*/

$hlavne_menu = array(
	'vyziadaj_hodnoty_pridaj_clena' => 'Pridavanie noveho clena',
	'vyziadaj_zoznam_clenov_na_zmenu' => 'Zmena udajov existujuceho clena',
	'vyziadaj_hodnoty_pridaj_poskytovatela' => 'Pridavanie noveho poskytovatela',
	'vyziadaj_zoznam_poskytovatelov' => 'Zmena udajov poskytovatela',
	'zadajCisloClena' => 'Overenie stavu clena',
	'zadajCisloClenaNavstevy' => 'Pridat navstevu clena',
	'zobrazTlacHlaseni' => 'Tlacit hlasenia',
	'Odhlasenie' => 'Odhlasenie'
);

function snull($v) {
	return ($v == '' ? 'NULL' : $v);
}

function my_query($q) {
	$db_name = $GLOBALS['DB_NAME'];
	//echo 'sqlcmd="'.htmlentities($q).'"<br>';
	if ($db = sqlite_open($db_name, 0666, $err)) {
		$res = sqlite_query($db, $q, SQLITE_ASSOC);
		if ($res === false) {
			echo vyrob_navrat_na_hlavne_menu();
			die('zla query '.htmlentities($q).'<br>'.sqlite_error_string(sqlite_last_error($db)));
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

function my_query_single($q) {
	$rows = my_query($q);
	foreach($rows[0] as $k => $v) {
		return $v;
	}
	return false;
}

function vyrob_zoznam_z_db($tab, $key_col, $val_col, $filter) {
	$rows = my_query('select '.$key_col.' as key, '.$val_col.' as val from '.$tab.' where '.$filter.';');
	$ret = array();
	foreach($rows as $row) {
		$ret[$row['key']] = $row['val'];
	}
	return $ret;
}


///////////////////
/////////////////// MEMBER INFO CLASS
function najdi_clena_podla_id($cisloClena) {
	if ($cisloClena > 0) {
		return my_query_single('select cisloClena from clenovia where cisloClena='.$cisloClena.';');
	}
	else {
		return false;
	}
}

function najdi_clena($meno) {
	return my_query_single('select cisloClena from clenovia where meno="'.sqlite_escape_string($meno).'";');
}

function zmen_alebo_pridaj_clena($cisloClena, $meno, $mesto, $ulica, $psc, $typ) {
	$mid = najdi_clena_podla_id($cisloClena);
	if ($mid === false) {
		$mid = najdi_clena($meno);
	}
	if ($mid === false) {
		$new_id = my_query_single('select IFNULL(max(cisloClena),0) + 1 from clenovia;');
		$sqlcmd = 'insert into clenovia (cisloClena, meno, mesto, ulica, psc, typ) values ('.
			$new_id.','.
			'"'.sqlite_escape_string($meno).'",'.
			'"'.sqlite_escape_string($mesto).'",'.
			'"'.sqlite_escape_string($ulica).'",'.
			''.snull($psc).','.
			'"'.sqlite_escape_string($typ).'"'.
			');';
		$_ = my_query($sqlcmd);
		return 'clen bol pridany';
	}
	else {
		$sqlcmd = 'update clenovia set meno="'.sqlite_escape_string($meno).'", mesto="'.sqlite_escape_string($mesto).'", ulica="'.sqlite_escape_string($ulica).'"'.
			', psc="'.$psc.'", typ="'.sqlite_escape_string($typ).'" where cisloClena='.$mid.';';
		$_ = my_query($sqlcmd);
		return 'clen bol zmeneny';
	}
}
/////////////////// MEMBER INFO CLASS
///////////////////

function vyziadaj_hodnoty($nazvy, $hodnoty, $submit) {
	$form = '<form method="POST"><table border=0>';
	foreach ($nazvy as $k => $v) {
		$form .= '<tr><td>'.htmlentities($v).'<td><input type=text name="'.htmlentities(htmlentities($k)).'" value="'.htmlentities(htmlentities($hodnoty[$k])).'"><br>';
	}
	$form .= '<tr><td colspan=2><input type=submit name=action value="'.htmlentities(htmlentities($submit)).'"></tr></table></form>';
	return $form;
}

function zobraz_menu_radio($menu, $action) {
	$form = '<form method="POST">';
	foreach ($menu as $k => $v) {
		$form .= '<input type=radio name="cisloClena" value="'.htmlentities($k).'">'.htmlentities($v).'</input><br>';
	}
	$form .= '<input type=submit name="action" value="'.htmlentities(htmlentities($action)).'"></form>';
	return $form;
}

function zobraz_menu($menu) {
	$form = '<form method="POST">';
	foreach ($menu as $k => $v) {
		$form .= '<button name="action" value="'.htmlentities($k).'">'.htmlentities($v).'</button><br>';
	}
	$form .= '</form>';
	return $form;
}

function vyrob_zoznam($nazvy, $hodnoty, $submit_action) {
	$ret = vyziadaj_hodnoty(
		$nazvy,
		$hodnoty,
		$submit_action
	);
	return $ret;
}

function parametre_osoby() {
	return array(
		'cisloClena'=>'Cislo clena',
		'meno'=>'Meno a priezvisko',
		'ulica'=>'Ulica',
		'mesto'=>'Mesto',
		'psc'=>'PSC'
	);
}

function parametre_poskytovatela() {
	$p = parametre_osoby();
	return array_merge($p, array('typ' => 'Typ (D - dietolog, I - internista, S - specialista)'));
}

function vyrob_navrat_na_hlavne_menu() {
	$form = '<form method=POST><input type=submit name="action" value="Hlavne menu"></form>';
	return $form;
}

function getStatus($nezaplatene, $celkom) {
	if ($celkom == 0) {
		return 'N';
	}
	if ($nezaplatene < $celkom) {
		return 'S';
	}

	return 'A';
}

function zobrazStatus($status) {
	switch ($status) {
		case 'A': return 'Aktivny';
		case 'N': return 'Neaktivny';
		case 'S': return 'Suspendovany';
		return 'Neznamy';
	}
}

function overZaplatenie($id) {
	$nezaplatene = my_query_single('select count(*) from mesacnePoplatky where zaplateny="N" and cisloClena='.$id.';');
	$celkom = my_query_single('select count(*) from mesacnePoplatky where cisloClena='.$id.';');
	return getStatus($nezaplatene, $celkom);
}

function poskytovatelJeAktivny($id) {
	return 1; //stub
}

if (isset($_POST) && count($_POST) > 0) {
	switch ($_POST['action']) {
		case 'vyziadaj_hodnoty_pridaj_clena':
		case 'vyziadaj_zoznam_clenov_na_zmenu':
		case 'vyziadaj_hodnoty_pridaj_poskytovatela':
		case 'vyziadaj_zoznam_poskytovatelov':
		case 'Vyber clena na zmenu':
		case 'Vyber clena na overenie':
		case 'Zobraz stav clena':
		case 'zadajCisloClena':
		case 'zadajCisloClenaNavstevy':
		case 'zobrazTlacHlaseni':
			$_SESSION['stav'] = $_POST['action'];
			break;

		case 'Odhlasenie':
			$_SESSION['stav'] = '';
			session_destroy();
			break;

		case 'Zadaj cislo clena':
			if ($_POST['cisloClena'] > 0) {
				$_SESSION['navsteva']['cisloClena'] = $_POST['cisloClena'];
				$_SESSION['stav'] = 'Zadaj cislo poskytovatela';
			}
			break;
		case 'Zadaj cislo poskytovatela':
			if ($_POST['cisloClena'] > 0 && poskytovatelJeAktivny($_POST['cisloClena'])) {
				$_SESSION['navsteva']['cisloPoskytovatela'] = $_POST['cisloClena'];
				$_SESSION['stav'] = 'Zadaj datum sluzby';
			}
			break;

		case 'Zadaj datum sluzby':
			$_SESSION['navsteva']['datumPoskytnutia'] = $_POST['datumPoskytnutia'];
			$_SESSION['stav'] = 'Zobraz dostupne sluzby';
			break;

		// 'vykonaj a zabudni' stavy
		case 'Pridaj clena':
		case 'Zmen clena':
		case 'Pridaj poskytovatela':
		case 'Zmen poskytovatela':
			$action_result = zmen_alebo_pridaj_clena(
				false,
				$_POST['meno'],
				$_POST['mesto'],
				$_POST['ulica'],
				$_POST['psc'],
				$_POST['typ']
			);
			//break; // toto nie, stav chceme zrusit
		default:
			$_SESSION['stav'] = '';
			break;
	}
}

switch ($_SESSION['stav']) {
	case 'vyziadaj_hodnoty_pridaj_poskytovatela':
		$title = 'Pridaj poskytovatela';
		$hodnoty = array();
		$submit_action = 'Pridaj poskytovatela';
		$nazvy = parametre_poskytovatela();

		$body = $title.'<br>';
		$body .= vyrob_zoznam($nazvy, $hodnoty, $submit_action);
		$body .= vyrob_navrat_na_hlavne_menu();
		break;
	case 'Vyber poskytovatela na zmenu':
		$rows = my_query('select cisloClena,meno,ulica,mesto,psc,typ from clenovia where cisloClena = '.$_POST['cisloClena'].';');

		$title = 'Zmen parametre poskytovatela';
		$hodnoty = $rows[0];
		$submit_action = 'Zmen poskytovatela';
		$nazvy = parametre_poskytovatela();
		$body = $title.'<br>';
		if (count($rows) > 0) {
			$body .= vyrob_zoznam($nazvy, $hodnoty, $submit_action);
		}
		$body .= vyrob_navrat_na_hlavne_menu();
		break;
	case 'Zadaj cislo clena na overenie':
		$status = my_query_single('select  from status where cisloClena = '.$_POST['cisloClena'].';');

		$title = 'Zmen parametre clena';
		$hodnoty = $rows[0];
		$submit_action = 'Zmen clena';
		$nazvy = parametre_osoby();
		$body = $title.'<br>';
		if (count($rows) > 0) {
			$body .= vyrob_zoznam($nazvy, $hodnoty, $submit_action);
		}
		$body .= vyrob_navrat_na_hlavne_menu();
		break;
	case 'Vyber clena na zmenu':
		$rows = my_query('select cisloClena,meno,ulica,mesto,psc from clenovia where cisloClena = '.$_POST['cisloClena'].';');

		$title = 'Zmen parametre clena';
		$hodnoty = $rows[0];
		$submit_action = 'Zmen clena';
		$nazvy = parametre_osoby();
		$body = $title.'<br>';
		if (count($rows) > 0) {
			$body .= vyrob_zoznam($nazvy, $hodnoty, $submit_action);
		}
		$body .= vyrob_navrat_na_hlavne_menu();
		break;
	case 'vyziadaj_hodnoty_pridaj_clena':
		$title = 'Zadaj parametre clena';
		$hodnoty = array();
		$submit_action = 'Pridaj clena';
		$nazvy = parametre_osoby();
		$body = $title.'<br>';
		$body .= vyrob_zoznam($nazvy, $hodnoty, $submit_action);
		$body .= vyrob_navrat_na_hlavne_menu();
		break;
	case 'vyziadaj_zoznam_poskytovatelov':
		$title = 'Vyber poskytovatela';
		$menu = vyrob_zoznam_z_db('clenovia', 'cisloClena', 'meno', 'typ is not null');
		$body = $title.'<br>';
		if (count($menu) > 0) {
			$body .= zobraz_menu_radio($menu, 'Vyber poskytovatela na zmenu');
		}
		$body .= vyrob_navrat_na_hlavne_menu();
		break;
	case 'vyziadaj_zoznam_clenov_na_zmenu':
		$title = 'Vyber clena na zmenu';
		$menu = vyrob_zoznam_z_db('clenovia', 'cisloClena', 'meno', 'typ is null');
		$body = $title.'<br>';
		if (count($menu) > 0) {
			$body .= zobraz_menu_radio($menu, 'Vyber clena na zmenu');
		}
		$body .= vyrob_navrat_na_hlavne_menu();
		break;
	case 'zadajCisloClena':
		$title = 'Zadaj cislo clena na overenie';
		$body = $title.'<br>';
		$body .= vyziadaj_hodnoty(array('cisloClena'=>'Cislo clena'), array(), 'Zobraz stav clena');
		$body .= vyrob_navrat_na_hlavne_menu();
		break;
	case 'Zobraz stav clena':
		$title = 'Stav clenstva clena cislo '.$_POST['cisloClena'];
		$body = $title.'<br>';
		$body .= zobrazStatus(overZaplatenie($_POST['cisloClena']));
		$body .= vyrob_navrat_na_hlavne_menu();
		break;
	case 'zadajCisloClenaNavstevy':
		$title = 'Zadaj cislo clena';
		$body = $title.'<br>';
		$body .= vyziadaj_hodnoty(array('cisloClena'=>'Cislo clena'), array(), 'Zadaj cislo clena');
		$body .= vyrob_navrat_na_hlavne_menu();
		break;
	case 'Zadaj cislo poskytovatela':
		$title = 'Zadaj cislo poskytovatela';
		$body = $title.'<br>';
		$body .= vyziadaj_hodnoty(array('cisloClena'=>'Cislo clena'), array(), 'Zadaj cislo poskytovatela');
		$body .= vyrob_navrat_na_hlavne_menu();
		break;
	case 'Zadaj datum sluzby':
		$title = 'Zadaj datum sluzby';
		$body = $title.'<br>';
		$body .= vyziadaj_hodnoty(array('datumPoskytnutia'=>'Datum sluzby'), array(), 'Zadaj datum sluzby');
		$body .= vyrob_navrat_na_hlavne_menu();
		break;
	case 'Zobraz dostupne sluzby':
		$body = 'Zobrazovanie dostupnych sluzieb nieje aktivne, nakolko nemame definovanu strukturu pre uchovavanie poskytovanych sluzieb.';
		$body .= vyrob_navrat_na_hlavne_menu();
		break;
	case 'zobrazTlacHlaseni': // stub
		$body = 'Zobrazovanie tlacenia hlaseni (o clenoch, poskytovateloch a manazerskych reportov) nieje dostupne.';
		$body .= vyrob_navrat_na_hlavne_menu();
		break;
	default:
		$body = 'Informacny system Anonymnych cokoholikov<br><br><br>';
		$body .= zobraz_menu($hlavne_menu);
}

?>
<html><head><title>Projekt z predmetu SWI2 - system anonymnych cokoholikov - Cokoholics Anonymous</title></head>
<body>
<?php
echo $body;
if (isset($action_result)) {
	echo '<br><font color=blue>'.htmlentities($action_result).'</font><br>';
}
?>
</body></html>
