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

#Referat73:Gesundheitsschutz.InfektionsschutzundEpidemiologie1TagesberichtCOVID19Datenstand:Mittwoch.3032022.16:00COVID19KennwerteBadenWürttembergBestätigteFälle7TageInzidenzCOVID19FälleaktuellaufITS3030227:1638.8::261::Verstorbene7TageHospitalisierungsinzidenzAnteilCOVID19BelegungenGesamtzahlderbetreibbarenITSBetten15067:6.8::11.9%(0.3%)(11.4%)GeneseneGeschätzter7TagesRWertCOVID19FälleaktuellaufNormalstation2233467:0.80:2044::MindestenseinmalGeimpfteVollständigGeimpfteAuffrischimpfungen8211936:74.0%(+0.1%)"8231003:74.1%(+0%)"6322963:56.9%(+0.2%)

if(preg_match("/"
	. "zahlderbetreibbarenITSBetten"
	. "([0-9.]*):"
	. "([0-9.]*):"
	. "/" , $argv[2], $matches)
  == 0) {
	preg_match("/"
		. "zahlderbetreibbarenITSBetten"
		. "([0-9.]*):"
		. "([0-9.]*):"
		. "/" , $argv[2], $matches);
}
print_r($matches);
$hi_inz    = floatval($matches[1]);
$its_anteil= floatval($matches[2]);

preg_match("/"
	. "station"
	. "([0-9]*):"
	. "([0-9.]*):"
	. "([0-9]*):"
	. "/" , $argv[2], $matches);
print_r($matches);
$normalstation= intval($matches[3]);
$genesene= intval($matches[1]);
$r7 = floatval($matches[2]);

preg_match("/"
	. "COVID19FälleaktuellaufITS"
	. "[0-9]+:"
	. "[0-9.]+::"
	. "([0-9]*):"
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
die();
if ($conn->query($strSql) === FALSE) {
	echo "Warning: $strSql \na{$conn->error}\nNOT Aborting - maybe already manually entered?\n";
}
?>
