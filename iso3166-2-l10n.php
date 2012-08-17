<?php
/**
 * Builds translation mappings of ISO-3166-2 data
 *
 * Takes ISO-3166-2 data from subdivisions/SubdivisionsEn.php
 * and builds identically formatted files for other languages
 * to hold translated ISO-3166-2 data. ISO-3166-2 subdivision
 * names are translated by querying the interwiki language
 * links on enwiki.
 *
 * This script relies on a reduced scope to build these language
 * files, concerning itself only with countries and languages
 * available in the WLM API's monuments_all table.
 *
 * If refined, this could be used to build a larger corpus
 * of translated ISO-3166-2 data.
 *
 * @author <arichards@wikimedia.org>
 */

error_reporting( E_ALL );
$time_start = microtime( true );
$dir = dirname( __FILE__ );
require_once( $dir . "/Iso31662RowProcessor.php" );
require_once( $dir . "/IsoSubdivision.php" );
require_once( $dir . "/IsoSubdivisionL10n.php" );
require_once( $dir . "/subdivisions/SubdivisionsEn.php" );
// @TODO do something about this unconfigurable whack-ness
require_once( '/home/awjrichards/Dev/p_erfgoed/api/includes/Defaults.php' );
require_once( '/home/awjrichards/Dev/p_erfgoed/database.inc' );

// an array to hold subdivision names that we do not have translations for
$missing = array();

// hold all the file handlers we'll be opening
$fhs = array();

// make database connection
$db = new mysqli( $dbServer, $dbUser, $dbPassword, $dbDatabase );
if ( $db->connect_errno ) {
	die( "db connection failed: {$db->connect_error}\n" );
}

// get list of unique available countries
$countries = array();
$country_query = "SELECT DISTINCT `adm0` FROM `monuments_all`";
if ( $result = $db->query( $country_query ) ) {
	while ( $row = $result->fetch_object() ) {
		array_push( $countries, strtoupper( $row->adm0 ) );
	}
}

// get list of unique available languages
$langs = array();
$language_query = "SELECT DISTINCT `lang` FROM `monuments_all`";
if ( $result = $db->query( $language_query ) ) {
	while ( $row = $result->fetch_object() ) {
		array_push( $langs, $row->lang );
	}
}

// an array to hold names of subdivisions
$sub_names = array();

$i = 0;
$lang_names_added = 0;
foreach ( $subdivisions as $country_code => $subdivision ) {
	// don't bother processing if the country is not one in our list
	if ( !in_array( $country_code, $countries ) ) {
		continue;
	}

	foreach ( $subdivision as $iso_code => $sub_data ) {
		$i++;
		$sub_names[ $iso_code ] = $sub_data['name'];

		if ( $i % 50 != 0 && $i != count( $subdivision ) ) {
			continue;
		}
		// build a string list of titles for querying
		$titles = implode( '|', array_values( $sub_names ) );
		$names_to_codes = array_flip( $sub_names );
		// It seems you can only query langlinks for one language a time
		// so loop through our available langs
		foreach ( $langs as $lang ) {
			// empty array to hold redirect info
			// $redirects = array();

			$localizer = new SubdivisionL10n;
			$localizer->debug = true;
			$localizer->setTitles( $titles );
			$localizer->setLang( $lang );
			$localizer->queryApi();
			$result = $localizer->getApiResult();

			// make sure we have some pages to work with
			if ( !array_key_exists( 'pages', $result['query'] ) ) {
				continue;
			}

			// handle strange redirect scenarios
			// we need to track these for being able to properly map
			// and fetch subdivision iso codes by title
			if ( array_key_exists( 'redirects', $result['query'] ) ) {
				foreach ( $result['query']['redirects'] as $redirect ) {
					//$redirects[ $reidrect['to'] ] = $redirect['from'];
					// map the names-to-codes to the redirect as well as the original
					$names_to_codes[ $redirect['to'] ] = $names_to_codes[ $redirect['from'] ];
				}
				
			}

			// loop through results, write them to the appropriate file
			foreach ( $result['query']['pages'] as $page_id => $page ) {
				// $page_id < 0 if the title searched for doesn't exist
				// and langlinks won't be set if there are no langlinks
				if ( $page_id < 0 || !array_key_exists( 'langlinks', $page ) ) {
					continue;
				}
				
				$name = SubdivisionL10n::fixL10nName( $page['langlinks'][0]['*'] );
				$iso_subdiv = new ISOSubdivision;
				$iso_subdiv->setName( $name );
				$iso_subdiv->setCountryIsoCode( $country_code );
				$sub_iso_code = $names_to_codes[ $page['title'] ];
				$iso_subdiv->setIsoCode( $sub_iso_code );
				$iso_subdiv->setLevelName( $subdivisions[ $country_code ][ $sub_iso_code ]['level'] );
				if ( $localizer->debug) echo $iso_subdiv;

				// if we don't already have the subdivision language file open for writing, do it.
				if ( !array_key_exists( $lang, $fhs ) ) {
					$subdivision_lang_file = sprintf( '%s/subdivisions/Subdivisions%s.php', $dir, ucfirst( $lang ) );
					$fhs[$lang] = fopen( $subdivision_lang_file, 'w' );

					// start the file off with php opener
					$php_head = "<?php" . PHP_EOL;
					fwrite( $fhs[$lang], $php_head );
				}
				fwrite( $fhs[$lang], $iso_subdiv );
				$lang_names_added++;
			}
		}
		// empty the $sub_names array for the next batch
		$sub_names = array();
	}
	$i = 0;
}

// close all the open file handlers
array_map( 'fclose', $fhs );
$time_end = microtime( true );
$run_time = $time_end - $time_start;
echo "$lang_names_added localized language names added." . PHP_EOL;
echo "Processing done in $run_time seconds." . PHP_EOL;
