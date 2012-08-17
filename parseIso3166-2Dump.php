<?php
/**
 * Process a CSV of iso-3166-2 into a php file holding an array of subdivision data
 * 
 * ISO-3166-2 Data dumped to csv from commondatahub.com
 * @author <arichards@wikimedia.org>
 */

error_reporting( E_ALL );
define( 'UPDATE_EN_NAMES', true );
$dir = dirname( __FILE__ );
require_once( $dir . "/Iso31662RowProcessor.php" );
require_once( $dir . "/IsoSubdivision.php" );
require_once( $dir . "/IsoSubdivisionL10n.php" );

// csv dump of iso-3166-2 data from commondatahub.com
$data_file = dirname( __FILE__ ) . "/iso3166-2-data.txt";
// where we will output iso-3166-2 data for english names
$out_file = dirname( __FILE__ ) . "/subdivisions/SubdivisionsEn.php";

// the minimum headers we expect to see in the CSV
$headers = array(
	'iso_code' => 'ISO 3166-2 SUB-DIVISION/STATE CODE',
	'name' => 'ISO 3166-2 SUBDIVISION/STATE NAME',
	'level_name' => 'ISO 3166-2 PRIMARY LEVEL NAME',
	'alternate_names' => 'SUBDIVISION/STATE ALTERNATE NAMES',
	'country_iso_code' => 'COUNTRY ISO CHAR 2 CODE',
);
// an array to map headers to field positions in the CSV
$header_map = array();

// open the data file, and process it
$fh_csv = fopen( $data_file, 'r' );
if ( !$fh_csv ) {
	fOpenError( $data_file );
}

// open output file for writing
$fh_out = fopen( $out_file, 'w+' );
if ( !$fh_out ) {
	fOpenError( $out_file );
}

// write the <?php at the top of the file
$php_head = "<?php" . PHP_EOL;
fwrite ( $fh_out, $php_head );

// go!
$row_count = 0;
while ( $row = fgetcsv( $fh_csv ) ) {
	if ( $row_count === 0 ) {
		// find the keys of specific data values we care about from the CSV headers
		foreach ( $headers as $k => $v ) {
			$header_map[ $k ] = getHeaderKey( $v, $row );
		}
		$row_count = 1;
		continue;
	}

	// load up a row processor object and parse the row
	$processor = new Iso31662RowProcessor( $row, $header_map );
	$processor->parseRow();

	// now that the row is parsed, get the subdivision data
	$subdivision = $processor->getSubdivision();

	$iso_code = $subdivision->getIsoCode();
	// there are unusual iso codes in the data that are unexplained,
	// which end with a "~". ignore those for now.
	if ( preg_match( "/(.*)~$/", $iso_code ) ) {
		continue;
	}

	$country_iso_code = $subdivision->getCountryIsoCode();
	$name = $subdivision->getName();
	$level_name = $subdivision->getLevelName();

	// check the EN version of the name against enwiki
	// this takes a long time, but makes more en-friendly
	// subdiv names. the other caveat here is that this might
	// have unexpected results. what it does is take a iso-3166-2
	// name, query the langlinks API where title = $name. If there
	// is a redirect to another page, we use the redirect as
	// the iso-3166-2 name.
	if ( UPDATE_EN_NAMES ) {
		$subDivL10n = new SubdivisionL10n;
		$subDivL10n->setTitles( $name );
		$subDivL10n->queryApi();
		// update the name if there's something different on enwiki
		if ( $redirects = $subDivL10n->getRedirects() ) {
			$name = $redirects[0]['to'];
		}
	}
	// do some final name cleanup if necessary
	$name = SubdivisionL10n::fixL10nName( $name );

	// format the record and write it to the file
	$l10nRecord = SubdivisionL10n::formatL10nRecord( $country_iso_code, $iso_code, $name, $level_name );
	fwrite( $fh_out, $l10nRecord );
	$row_count++;
}
fclose( $fh_csv );
fclose( $fh_out );

function getHeaderKey( $header_name, $headers ) {
	$header_key = array_search( $header_name, $headers );
	if ( $header_key === false ) {
		throw new Exception( sprintf( 'Header "%s" not found.', $header_name ) );
	}
	return $header_key;
}

function fOpenError( $file_name ) {
	throw new Exception( sprintf( 'Could not open file %s', $file_name ) );
}
