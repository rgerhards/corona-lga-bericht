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
echo $argv[2] . "\n\n";

#Referat73:Gesundheitsschutz.InfektionsschutzundEpidemiologie1TagesberichtCOVID19Datenstand:Freitag.01042022.16:00COVID19KennwerteBadenWürttembergBestätigteFälle7TageInzidenzCOVID19FälleaktuellaufITS3085574:1523.6::269::Verstorbene7TageHospitalisierungsinzidenzAnteilCOVID19BelegungenGesamtzahlderbetreibbarenITSBetten15141:7.4::12.3::GeneseneGeschätzter7TagesRWertCOVID19FälleaktuellaufNormalstation2298755:0.87:1996::MindestenseinmalGeimpfteVollständigGeimpfteAuffrischimpfungen8213240:74.0(+0.1)8233840:74.2(+0.1)6332323:57.0(+0.2)

if(preg_match("/"
	. "7TageHospitalisierungsinzidenz"
	. "([0-9.]*):"
	. ".*zahlderbetreibbarenITSBetten"
	. "([0-9.]*):"
	. "/" , $argv[2], $matches)
  == 0) if(preg_match("/"
		. "zahlderbetreibbarenITSBetten"
		. "([0-9.]*):"
		. "([0-9.]*):"
		. "/" , $argv[2], $matches)
  == 0)
	die("ERROR: Regex klappt nicht bei hi_inz etc\n");
print_r($matches);
$hi_inz    = floatval($matches[1]);
$its_anteil= floatval($matches[2]);

if(preg_match("/"
	. "Genesene"
	. "([0-9]*):"
	. ".*RWert"
	. "([0-9.]*):"
	. ".*station"
	. "([0-9]*):"
	. "/" , $argv[2], $matches)
  == 1) {
	print_r($matches);
	$genesene= intval($matches[1]);
	$r7 = floatval($matches[2]);
	$normalstation= intval($matches[3]);
} else if(preg_match("/"
	. "station"
	. "([0-9]*):"
	. "([0-9.]*):"
	. "([0-9]*):"
	. "/" , $argv[2], $matches)
  == 1) {
	print_r($matches);
	$genesene= intval($matches[1]);
	$r7 = floatval($matches[2]);
	$normalstation= intval($matches[3]);
} else {
	die("ERROR: Regex klappt nicht bei normalstation etc\n");
}

if (preg_match("/"
	. "COVID19FälleaktuellaufITS"
	. "([0-9]*):"
	. "/" , $argv[2], $matches) == 1) {
} else if (preg_match("/"
	. "COVID19FälleaktuellaufITS"
	. "[0-9]+:"
	. "[0-9.]+::"
	. "([0-9]*):"
	. "/" , $argv[2], $matches) == 1) {
} else {
	die("ERROR: Regex klappt nicht bei its_nbr etc\n");
}
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

if ($stufe != 0) {
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
}

$strSql = "UPDATE bw_mutationen
		set hi_inz7 = '$hi_inz',
                    its_nbr = '$its_nbr',
		    its_anteil = '$its_anteil',
		    genesen = '$genesene',
		    r7 = '$r7',
		    bw_stufe = '$stufe'
                where datum = '$today'
	  ";
echo "LGA Step 2/Wochentag: $strSql\n";
if ($conn->query($strSql) === FALSE) {
	echo "Warning: $strSql \na{$conn->error}\nNOT Aborting - maybe already manually entered?\n";
}
?>
