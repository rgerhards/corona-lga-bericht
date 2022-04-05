#!/bin/bash
# run script via cron after ~15:00 UTC but NOT TO OFTEN! Keep server load on the
# target reasonable low. I usually run it every 4 minutes.
# NOTE: this script stops when a new report has been found. In very rare cases, the
# found report was incorrect, in which case the current file needs to be deleted
# manually (plus some DB cleanup) in order to carry on.
#
cd /home/otherweb/scripts/lga-bericht
source config.sh
query_url() {
	if wget -nv -O "data/lgabw-$today.pdf" "$1"; then
		echo Found LGA Bericht in $1
		(
		printf "URL:\n%s\n" $1

		# auto-add TBB
		DATALINE="$(pdftotext -f 1 -l 2 -layout data/lgabw-$today.pdf -|grep Main-Tauber-Kreis \
			|sed 's/\.//g'|sed 's/,/./g'|sed -e 's/   */,/g' -e s'/(+ //g' -e 's/^ //' -e 's/)//g')"
		php lga-tbb.php "$(date -I)" "$DATALINE"
		php ../update-neuinfekt.php "and entity = 'DE:BW:TBB'" 1 # 1=manuell!

		# auto-add BaWü Gesamtinzidenz
		DATALINE="$(pdftotext -f 1 -l 2 -layout data/lgabw-$today.pdf -|grep Gesamtergebnis \
			|sed 's/\.//g'|sed 's/,/./g'|sed -e 's/   */,/g' -e s'/+ //g' -e 's/^ //' -e 's/[()]//g')"
		echo DATALINE: $DATALINE
		php lga-gesamtinzidenz.php "$(date -I)" "$DATALINE"

		# Leider funktioniert das scraping gar nicht, da das LGA laufend das Format
		# ändert. Etwas generellere Regexe führen dann u.U. sogar dazu, dass komplett
		# falsche Daten übernommen werden. Daher lassen wir es lieber ganz.
		# BaWü ITS, Hi-Inz etc -- abhängig vom Wochentag!
		#pdftotext -f 1 -l 1 -layout data/lgabw-$today.pdf  -|grep "Tage Hospitalisierungsinzidenz"
		#found=$?
		#found=1 # aktuell haben wir keine Berichte am Wochende
		#if (( found == 0 )); then
		#	echo Wochend-Verarbeitung!
		#	DATALINE="$(pdftotext -f 1 -l 1 -layout data/lgabw-$today.pdf -\
		#		|grep "Tage Hospitalisierungsinzidenz" \
		#		|sed -e 's/^[^:]*: //' -e 's/COVID-.*: //' -e 's/(.*)//' -e 's/,/./' -e 's/  */,/')"
		#	echo DATALINE: $DATALINE
		#	php lga-its-weekend.php "$(date -I)" "$DATALINE"
		#else
		#	echo "Normalverarbeitung (Werktag)"
		#	DATALINE="$(pdftotext -raw -f 1 -l 1  data/lgabw-$today.pdf - \
		#		|sed -e 's/\.//g' -e 's/,/./g' -e 's/$/ /' -e 's/-/-/g' -e 's/[-±∆°“"%]//g' \
		#		|tr -d '\n' \
		#		|sed -e 's/Abkürzungen.*//' -e 's/\*//g' -e 's/ //g' \
		#			-e 's/([+-0123456789]*)/:/g' -e 's/Vorwoche*//g' )"
		#	php lga-its-werktag.php "$(date -I)" "$DATALINE"
		#fi
		
		# generate today's posting (but not yet publish it!)
		(cd ..; tbb/gen_chart.sh)

		# finally flush page so that new data is seen
		wp --path=/home/adisconweb/www/wordpress-mu  --url=https://www.rainer-gerhards.de \
			w3-total-cache flush all --post_id=4609 # /coronavirus page

		) |& mail -s "LGA Bericht $today" --attach "data/lgabw-$today.pdf" $CORONA_MAIL_NOTIFY_ADDR
		exit 0 # done
	fi
}



export today="$(date +%y%m%d)" # shortcut - we also use $(date -I)!
if [ ! -s "data/lgabw-$today.pdf" ]; then
	query_url https://gesundheitsamt-bw.de/fileadmin/LGA/_DocumentLibraries/SiteCollectionDocuments/05_Service/LageberichtCOVID19/`date +%Y`-`date +%m`-`date +%d`_LGA_COVID19-Tagesbericht.pdf
	query_url https://gesundheitsamt-bw.de/fileadmin/LGA/_DocumentLibraries/SiteCollectionDocuments/05_Service/LageberichtCOVID19/`date +%Y`-`date +%m`-`date +%d`_LGA_COVID19-Lagebericht.pdf
	query_url https://www.baden-wuerttemberg.de/fileadmin/redaktion/dateien/PDF/Coronainfos/Corona_2022/${today}_LGA_COVID19-Lagebericht.pdf
	query_url https://www.baden-wuerttemberg.de/fileadmin/redaktion/dateien/PDF/Coronainfos/${today}_COVID_Tagesbericht.pdf
	query_url https://www.baden-wuerttemberg.de/fileadmin/redaktion/dateien/PDF/Coronainfos/${today}_COVID_Tagesbericht_LGA.pdf
	query_url https://www.baden-wuerttemberg.de/fileadmin/redaktion/dateien/PDF/Coronainfos/${today}_COVID_Tagesbericht_LGA_01.pdf
	query_url https://www.baden-wuerttemberg.de/fileadmin/redaktion/dateien/PDF/Coronainfos/${today}_COVID_Tagesberich.pdf

	query_url https://www.baden-wuerttemberg.de/fileadmin/redaktion/dateien/PDF/Coronainfos/Corona_2022/${today}_COVID_Tagesbericht.pdf
	query_url https://www.baden-wuerttemberg.de/fileadmin/redaktion/dateien/PDF/Coronainfos/Corona_2022/${today}_COVID_Tagesbericht_LGA.pdf
	query_url https://www.baden-wuerttemberg.de/fileadmin/redaktion/dateien/PDF/Coronainfos/Corona_2022/${today}_COVID_Tagesbericht_LGA_01.pdf
	query_url https://www.baden-wuerttemberg.de/fileadmin/redaktion/dateien/PDF/Coronainfos/Corona_2022/${today}_COVID_Tagesberich.pdf

	query_url https://www.baden-wuerttemberg.de/fileadmin/redaktion/dateien/PDF/Coronainfos/${today}_COVID_Lagebericht.pdf
	query_url https://www.baden-wuerttemberg.de/fileadmin/redaktion/dateien/PDF/Coronainfos/${today}_COVID_Lagebericht_LGA.pdf
	query_url https://www.baden-wuerttemberg.de/fileadmin/redaktion/dateien/PDF/Coronainfos/${today}_COVID_Lagebericht_LGA_01.pdf
	query_url https://www.baden-wuerttemberg.de/fileadmin/redaktion/dateien/PDF/Coronainfos/${today}_COVID_Lageberich.pdf

	query_url https://www.baden-wuerttemberg.de/fileadmin/redaktion/dateien/PDF/Coronainfos/Corona_2022/${today}_COVID_Lagebericht.pdf
	query_url https://www.baden-wuerttemberg.de/fileadmin/redaktion/dateien/PDF/Coronainfos/Corona_2022/${today}_COVID_Lagebericht_LGA.pdf
	query_url https://www.baden-wuerttemberg.de/fileadmin/redaktion/dateien/PDF/Coronainfos/Corona_2022/${today}_COVID_Lagebericht_LGA_01.pdf
	query_url https://www.baden-wuerttemberg.de/fileadmin/redaktion/dateien/PDF/Coronainfos/Corona_2022/${today}_COVID_Lageberich.pdf

	query_url https://www.baden-wuerttemberg.de/fileadmin/redaktion/dateien/PDF/COVID_Lagebericht_LGA_${today}_7-Tage-Inzidenzen.pdf
	query_url https://www.gesundheitsamt-bw.de/fileadmin/LGA/_DocumentLibraries/SiteCollectionDocuments/05_Service/LageberichtCOVID19/COVID_Lagebericht_LGA_${today}_7-Tage-Inzidenzen.pdf
	query_url https://www.gesundheitsamt-bw.de/fileadmin/LGA/_DocumentLibraries/SiteCollectionDocuments/05_Service/LageberichtCOVID19/COVID_Lagebericht_LGA_${today}__7-Tage-Inzidenzen.pdf
	query_url https://sozialministerium.baden-wuerttemberg.de/fileadmin/redaktion/m-sm/intern/downloads/Downloads_Gesundheitsschutz/$(date -I)_LGA_COVID19-Tagesbericht.pdf

	if [[ $(date +%u) -gt 5 ]]; then
		# Wochenende - unnötige Server Queries unter der Woche vermeiden
		# Hinweis: Werktags-URL erscheinen auch am Wochenende, daher hier keine reduzierung möglich!
		query_url https://www.baden-wuerttemberg.de/fileadmin/redaktion/dateien/PDF/Coronainfos/Corona_2022/${today}_COVID_Inzidenzbericht_Wochenende_LGA.pdf

		query_url https://www.baden-wuerttemberg.de/fileadmin/redaktion/dateien/PDF/Coronainfos/${today}_COVID_Inzidenzbericht-Wochenende.pdf
		query_url https://www.baden-wuerttemberg.de/fileadmin/redaktion/dateien/PDF/Coronainfos/${today}_COVID_Inzidenzbericht_Wochenende.pdf
		query_url https://www.baden-wuerttemberg.de/fileadmin/redaktion/dateien/PDF/Coronainfos/${today}_COVID_Inzidenzbericht-Wochenende_LGA.pdf
		query_url https://www.baden-wuerttemberg.de/fileadmin/redaktion/dateien/PDF/Coronainfos/${today}_COVID_Inzidenzbericht_Wochenende_LGA.pdf
		query_url https://www.baden-wuerttemberg.de/fileadmin/redaktion/dateien/PDF/${today}_COVID_Inzidenzbericht_Wochenende.pdf
		query_url https://www.baden-wuerttemberg.de/fileadmin/redaktion/dateien/PDF/${today}_COVID_Inzidenzbericht-Wochenende.pdf
		query_url https://www.baden-wuerttemberg.de/fileadmin/redaktion/dateien/PDF/Coronainfos/${today}_COVID_Inzidenzbericht_Wochenende_LGA.pdf
		query_url https://www.gesundheitsamt-bw.de/fileadmin/LGA/_DocumentLibraries/SiteCollectionDocuments/05_Service/LageberichtCOVID19/COVID_Lagebericht_LGA_${today}_7-Tage-Inzidenzen.pdf
		query_url https://www.baden-wuerttemberg.de/fileadmin/redaktion/dateien/PDF/Coronainfos/Corona_2022/${today}_LGA_COVID19-Inzidenzbericht.pdf
	fi
fi
