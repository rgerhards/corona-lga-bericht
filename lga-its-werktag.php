<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$dbserver = getenv("CORNA_DB_SERVER");
$dbport = getenv("CORONA_DB_PORT");
$dbname = getenv("CORONA_DB_DBNAME");
$dbname2 = getenv("CORONA_DB_DBNAME2");
$dbuser = getenv("CORONA_DB_USER");
$dbpass = getenv("CORONA_DB_PASS");

$conn = new mysqli($dbserver . ":" . $dbport, $dbuser, $dbpass, $dbname);
if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error . "\n");
}

/* -------- end "plumbing" -------- */

if ($argv[1] == "" || $argv[2] == "")
	die("usage date lga-string");
$today = $conn->real_escape_string($argv[1]);
echo $argv[2];

#Referat 73: Gesundheitsschutz. Infektionsschutz und Epidemiologie  Tagesbericht COVID-19 Datenstand Mittwoch. 28022022. 16:00 Uhr COVID-19-Kennwerte Baden-Württemberg COVID-19-Fälle aktuell auf ITS 272 (+13) Vorwoche (288) Anteil COVID-19-Belegung an 7-Tage Gesamtzahl der betreibbaren ITSVerstorbene Hospitalisierungsinzidenz Betten 14278 (+32) 6.7 (-0.2 ) 12.3% (+0.5 %) Vorwoche (7.7) Vorwoche (13.0 %) COVID-19-Fälle aktuell auf Geschätzter Genesene Normalstation 7-Tages-R-Wert 1436563 (+939) 1612 (-46) 0.87 (0.82-0.92) Vorwoche (1619) Mindestens einmal Geimpfte Grundimmunisiert Auffrischimpfungen 8187901 (+2351) 8184224 (+7986) 6199014 (+14651) 73.7 % (Vorwoche: +0.0 %) 73.7 % (Vorwoche: +0.2 %) 55.8 % (Vorwoche: +0.4 %) Nach § 1 Absatz 2 und 3 der Corona-Verordnung des Landes gilt die Warnstufe Bestätigte Fälle 2107501 (+18438)  7-Tage-Inzidenz 1402.0 (-24.1) Vorwoche (1561.6)
#Referat 73: Gesundheitsschutz. Infektionsschutz und Epidemiologie Tagesbericht COVID-19 Datenstand Mittwoch. 23032022. 16:00 Uhr COVID-19-Kennwerte Baden-Württemberg Bestätigte Fälle 2831327 (+43750) 7-Tage-Inzidenz 1939.3 (+13.1) Vorwoche (1912.0) Verstorbene 14878 (+32) 7-Tage Hospitalisierungsinzidenz 7.9 (-0.2) Vorwoche (7.7) Genesene 2059703 (+33690) Geschätzter 7-Tages-R-Wert 0.86 (0.81 - 0.90) Mindestens einmal Geimpfte 8208219 (+1076) 73.9% (Vorwoche: +0.0 %) Grundimmunisiert 8223152 (+1736) 74.1% (Vorwoche: +0.1 %) COVID-19-Fälle aktuell auf ITS 249 (+8) Vorwoche (253) Anteil COVID-19-Belegung an Gesamtzahl der betreibbaren ITSBetten 11.4 % (+0.4 %) Vorwoche (11.5 %) COVID-19-Fälle aktuell auf Normalstation 2090 (+126) Vorwoche (1868) Auffrischimpfungen 6300607 (+4823) 56.7 % (Vorwoche: +0.2 %)

preg_match("/"
	. "7-Tage Hospitalisierungsinzidenz "
	. "([0-9.]*) \("
	. "/" , $argv[2], $matches);
print_r($matches);
$hi_inz    = floatval($matches[1]);

preg_match("/"
	. "ITSBetten "
	. "([0-9.]*) % \("
	. "/" , $argv[2], $matches);
print_r($matches);
$its_anteil= floatval($matches[1]);

preg_match("/"
	. "Genesene ([0-9]*) \("
	. "/" , $argv[2], $matches);
print_r($matches);
$genesene= floatval($matches[1]);

preg_match("/"
	. "Normalstation ([0-9]*) \("
	. "/" , $argv[2], $matches);
print_r($matches);
$normalstation= floatval($matches[1]);

preg_match("/7-Tages-R-Wert ([0-9.]*) \(/" , $argv[2], $matches);
print_r($matches);
$hi_inz    = floatval($matches[1]);

preg_match("/"
	. "COVID-19-Fälle aktuell auf ITS "
	. "([0-9]*) \("
	. "/" , $argv[2], $matches);
print_r($matches);
$its_nbr   = intval($matches[1]);

/*
preg_match("/"
	. "(Basisstufe|Warnstufe|Alarmstufe)"
	. "/" , $argv[2], $matches);
print_r($matches);
$stufe     = $matches[1];
*/
$stufe = 0;
die();

#$neuinfekt = intval($matches[1]);
#$tote      = intval($matches[2]);
#$inz       = floatval($matches[4]);
if ($stufe == "Alarmstufe II")
	$stufe = 4;
else if ($stufe == "Alarmstufe" or $stufe == "Alarmstufe I")
	$stufe = 3;
else if ($stufe == "Warnstufe")
	$stufe = 2;
else if ($stufe == "Basisstufe")
	$stufe = 2;
else {// gibt's eigentlich nicht!
	echo "\nWARNUNG: unbekannte Stufe '$stufe'!!!\n\n";
	$stufe = 0;
}

$strSql = "UPDATE bw_mutationen
		set hi_inz7 = $hi_inz,
                    its_nbr = $its_nbr,
		    its_anteil = $its_anteil,
		    genesen = $genesene,
		    r7 = $r7,
		    bw_stufe = $stufe
                where datum = '$today'
	  ";
echo "LGA Step 2/Wochentag: $strSql\n";
if ($conn->query($strSql) === FALSE) {
	echo "Warning: $strSql \na{$conn->error}\nNOT Aborting - maybe already manually entered?\n";
}


/* HISTORY
preg_match("/Bestätigte Fälle [0-9]* \(([^)]*)\) "
	. "Verstorbene ([0-9]*) "
	. ".* Genesene ([0-9]*) "
	. ".*7-Tage-Inzidenz[^0-9]*([0-9.]*) "
	. ".*7-Tage Hospitalisierungsinzidenz[^0-9]*([0-9.]*) "
	. ".*Geschätzter 7-Tages-R-Wert[^0-9]*([0-9.]*) "
	. ".*COVID-19-Fälle aktuell auf ITS[^0-9]*([0-9.]*) "
	. ".*Anteil COVID-19-Belegung an Gesamtzahl der betreibbaren .*Betten*[^0-9]*([0-9.]*) "
	. ".*Corona-Verordnung des Landes gilt die ([a-zA-Z]* [^ ]*) "
	. "/" , $argv[2], $matches);
print_r($matches);

#$neuinfekt = intval($matches[1]);
#$tote      = intval($matches[2]);
#$inz       = floatval($matches[4]);
$genesene  = intval($matches[3]);
$hi_inz    = floatval($matches[5]);
$r7        = floatval($matches[6]);
$its_nbr   = intval($matches[7]);
$its_anteil= floatval($matches[8]);
$stufe     = $matches[9];
if ($stufe == "Alarmstufe II")
	$stufe = 4;
else if ($stufe == "Alarmstufe I")
	$stufe = 3;
else if ($stufe == "Warnstufe")
	$stufe = 2;
else if ($stufe == "Basisstufe")
	$stufe = 2;
else {// gibt's eigentlich nicht!
	echo "\nWARNUNG: unbekannte Stufe '$stufe'!!!\n\n";
	$stufe = 0;
}

*/
?>
