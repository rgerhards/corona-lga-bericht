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

#Referat73:Gesundheitsschutz.InfektionsschutzundEpidemiologieTagesberichtCOVID-19Datenstand:Dienstag.2932022.16:00COVID-19-KennwerteBaden-WürttembergBestätigteFälle7-TageInzidenzCOVID-19-FälleaktuellaufITS1695.8(-50.4)267(-6)2993643(+34862)Vorwoche(1926.2)Vorwoche(241)AnteilCOVID-19-BelegungenGesamt-Verstorbene7-TageHospitalisierungsinzidenzzahlderbetreibbarenITS-Betten6.5(0.0)12.2%(-0.3%)15048(+43)Vorwoche(8.1)Vorwoche(11%)COVID-19-FälleaktuellaufNormal-GeneseneGeschätzter7-TagesR-Wertstation1996(-4)2202307(+22738)0.74(0.69–0.78)Vorwoche(1964)MindestenseinmalGeimpfteVollständigGeimpfteAuffrischimpfungen8211395(+356)8229952(+688)6319459(+2260)74.0%(Vorwoche+0.1%)"74.1%(Vorwoche+0.1%)"56.9%(Vorwoche+0.2%)

preg_match("/"
	. "7-TageHospitalisierungsinzidenzzahlderbetreibbarenITS-Betten"
	. "([0-9.]*)\([0-9.]*\)"
	. "([0-9.]*)%"
	. "/" , $argv[2], $matches);
print_r($matches);
$hi_inz    = floatval($matches[1]);
$its_anteil= floatval($matches[2]);

preg_match("/"
	. "GeneseneGeschätzter7-TagesR-Wertstation"
	. "([0-9.]*)\([+-]*[0-9.]*\)"
	. "([0-9]*)\([+-]*[0-9.]*\)"
	. "([0-9.]*)\("
	. "/" , $argv[2], $matches);
print_r($matches);
$normalstation= intval($matches[1]);
$genesene= intval($matches[2]);
$r7 = floatval($matches[3]);

preg_match("/"
	. "7-TageInzidenzCOVID-19-FälleaktuellaufITS"
	. "[0-9.]+\([+-]*[0-9.]+\)"
	. "([0-9]*)\("
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
