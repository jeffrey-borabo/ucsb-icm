<?php

require "phpQuery.php";
require "database.php";

$param_level = 'Undergraduate';
$param_qtr = false;
$db_table_name = false;

foreach($argv as $argn => $arg) {
	if($arg[0] == '-') {
		$arg = strtolower($arg);
		if($arg == '-u') {
			$param_level = 'Undergraduate';
		}
		else if($arg == '-g') {
			$param_level = 'Graduate';
		}
		else if(preg_match('/\-(.)([0-9]{2})/', $arg, $qtr)) {
			$param_qtr = (2000+$qtr[2]);
			switch($qtr[1]) {
				case 'w':
					$param_qtr .= '1'; break;
				case 's':
					$param_qtr .= '2'; break;
				case 'm':
					$param_qtr .= '3'; break;
				case 'f':
					$param_qtr .= '4'; break;
				default:
					die_input('quarter not recognized: "'.$qtr[1].'"');
			}
		}
		else if($arg == '--help') {
			die_input();
		}
		else {
			die_input('argument not recognized: "'.$arg.'"');
		}
	}
	else if($argn == $argc-1) {
		$db_table_name = $arg;
	}
	else if($argn != 0) {
		die_input('argument not recognized: "'.$arg.'"');
	}
}

if($param_qtr === false) {
	die_input('a quarter must be specified');
}
if($db_table_name === false || $db_table_name == '') {
	die_input('a table name must be specified');
}

function die_input($remarks=false) {
echo 'php '.$_SERVER['SCRIPT_FILENAME'].' [OPTION] -QUARTER TABLE_NAME
Concrete options:
  -u, [default]     undergraduate course levels only
  -g,               graduate course levels only
  
To select a quarter, the format of the argument is: `-qyy`, Where `yy` are the last two digits of the year `20yy` and `q` is one of the following:
  f,                fall
  w,                winter
  s,                spring
  m,                summer

eg:

php registrar.php -u -s12 uqtr
-- selects undergraduate courses from spring 2012 and stores them to table named `uqtr`

php registrar.php -g -m10 smr-10
-- selects graduate courses from summer 2010 and stores them to table named `smr-10`
  
';
if($remarks !== false) {
	echo 'ERROR: '.$remarks;
}
exit;
}



$regUrl = "http://my.sa.ucsb.edu/public/curriculum/coursesearch.aspx";

// setup an array for any cookies we will need for requests
$regCookie = array();

$curlOpts = array(
	CURLOPT_URL => $regUrl,
	CURLOPT_CONNECTTIMEOUT => 15,
	CURLOPT_RETURNTRANSFER => 1,
);



/* before we make requests, fetch any server-side token values (cookies & keys) */

// initialize a curl object
$initHandle = curl_init();

// extend any additional curl options to the default ones
$initOpts = $curlOpts + array(
	CURLOPT_HEADER => 1,
);
curl_setopt_array($initHandle, $initOpts);

// perform get request
$initResponse = curl_exec($initHandle);
curl_close($initHandle);
unset($initHandle);

// extract the cookies from the header for subsequent calls
if(!preg_match('/Set\-Cookie: ([^;]+)/', $initResponse, $matches)) {
	die('Could not resolve ASP.NET_SessionId cookie by regex');
}
$regCookie[] = $matches[1];

$initHtml = substr($initResponse, strpos($initResponse, '<!DOCTYPE'));

// parse the document
$doc = phpQuery::newDocument($initHtml);
phpQuery::selectDocument($doc);

// steal microsoft's stupid ASP token keys
$ASP_Tokens = array(
	'__VIEWSTATE'       => pq('#__VIEWSTATE')->val(),
	'__EVENTVALIDATION' => pq('#__EVENTVALIDATION')->val(),
);

// store all the department options
$courseOptions = array();

function saveCourseOption($option) {
	// php bug won't allow call by reference in user-defined functions, use global instead
	global $courseOptions;
	$courseOptions[] = pq($option)->val();
}

// fetch all of the course list option values
pq('select[id$="courseList"]')->find('option')->each('saveCourseOption', new CallbackParam);

// cleanup
unset($initPage);



if($limitOneDepartment) {
	$courseOptions = array($param_dept);
}


$db = new MySQL_Pointer($DATABASE['db_name']);

$db->table($db_table_name, array(
	'courseTitle'    => 'varchar(255)',
	'fullTitle'      => 'varchar(255)',
	'description'    => 'varchar(255)',
	'preReq'         => 'varchar(255)',
	'college'        => 'varchar(255)',
	'units'          => 'varchar(255)',
	'grading'        => 'varchar(255)',
	'primaryTitle'   => 'varchar(255)',
	'status'         => 'varchar(255)',
	'enrollCode'     => 'varchar(255)',
	'levelLimit'     => 'varchar(255)',
	'majorLimitPass' => 'varchar(255)',
	'majorLimit'     => 'varchar(255)',
	'messages'       => 'varchar(255)',
	'instructor'     => 'varchar(255)',
	'days'           => 'varchar(255)',
	'time'           => 'varchar(255)',
	'location'       => 'varchar(255)',
	'enrolled'       => 'varchar(255)',
));

$db->selectTable($db_table_name);



function error($msg) {
	die($msg);
}

function format($str) {
	return preg_replace(array('/^\s+/','/\s+$/','/\s+/'),array('','',' '),$str);
}

$records = array();

/** function responsible for extracting fields from each row **/
function extractRow($row) {
	global $records, $db;
	$array = &$records;
	
	// setup a reference to the child TD elements
	$tds = pq($row)->children('td');
	
	// course title [eg: GEOG 176C]
	$firstSetHtml = pq($row)->find('#CourseTitle')->html();
	if(!preg_match('/^\s*([a-z][^<]+)</i', $firstSetHtml, $match)) {
		error('failed to interpret course title regex');
	}
	$courseTitle = preg_replace('/ +/',' ',format($match[1]));
	
	/* deatiled information box */
	$masterCourseTable = pq($row)->find('.MasterCourseTable');
	$fullTitle   = $masterCourseTable->find('span[id$="labelTitle"]')->text();
	$description = $masterCourseTable->find('span[id$="labelDescription"]')->text();
	$preReq      = $masterCourseTable->find('span[id$="labelPreReqComment"]')->text();
	$college     = $masterCourseTable->find('span[id$="labelCollege"]')->text();
	$units       = $masterCourseTable->find('span[id$="labelUnits"]')->text();
	$grading     = $masterCourseTable->find('span[id$="labelQuarter"]')->text();
	
	// primary title, indicates that it is a lecture
	$primaryTitle = pq($row)->find('span[id$="HyperLinkPrimaryCourse"]')->text();
	
	// status: Closed, Full, Cancelled, or a blank string
	$status = preg_replace('/\s+/','', pq($row)->find('td.Status')->text());
	
	// encroll code
	$enrollCode = pq($row)->find('a.EnrollCodeLink')->text();
	
	/* restrictions information box */
	$restrictionsTable = pq($row)->find('.RestrictionsTable');
	$levelLimit     = $restrictionsTable->find('[id$="label2"]')->text();
	$majorLimitPass = $restrictionsTable->find('[id$="label4"]')->text();
	$majorLimit     = $restrictionsTable->find('[id$="label3"]')->text();
	$messages       = $restrictionsTable->find('[id$="lblMessages"]')->text();
	
	// instructor name
	$instructor = format($tds->eq(5)->text());
	
	// day codes
	$days = format($tds->eq(6)->text());
	
	// time of day codes
	$time = format($tds->eq(7)->text());
	
	// location
	$location = format($tds->eq(8)->text());
	if(preg_match('/^([A-Z]+)([0-9]+[A-Za-z]*)$/', $location, $locationFix)) {
		$location = $locationFix[1].' '.$locationFix[2];
	}
	
	// time of day codes
	$enrolled = format($tds->eq(9)->text());
	
	$insert = array(
		'courseTitle'    => $courseTitle,
		'fullTitle'      => $fullTitle,
		'description'    => $description,
		'preReq'         => $preReq,
		'college'        => $college,
		'units'          => $units,
		'grading'        => $grading,
		'primaryTitle'   => $primaryTitle,
		'status'         => $status,
		'enrollCode'     => $enrollCode,
		'levelLimit'     => $levelLimit,
		'majorLimitPass' => $majorLimitPass,
		'majorLimit'     => $majorLimit,
		'messages'       => $messages,
		'instructor'     => $instructor,
		'days'           => $days,
		'time'           => $time,
		'location'       => $location,
		'enrolled'       => $enrolled,
	);
	
	$db->insert($insert);
	$array[] = $insert;
}


ob_start();

/* iterate through every department and scrape their info */
foreach($courseOptions as $department) {
	
	// initialize a curl object
	$regHandle = curl_init();
	
	// prepare the post field data
	$regPostFieldsArray = $ASP_Tokens + array(
		'ctl00$pageContent$courseList'           => $department,
		'ctl00$pageContent$quarterList'          => $param_qtr,
		'ctl00$pageContent$dropDownCourseLevels' => $param_level,
		'ctl00$pageContent$searchButton.x'       => '22',
		'ctl00$pageContent$searchButton.y'       => '8'
	);
	
	
	// encode the post fields as a url component
	$regPostFieldsArrayEncoded = array();
	foreach($regPostFieldsArray as $key => $value) {
		$regPostFieldsArrayEncoded[] = urlencode($key).'='.urlencode($value);
	}
	$regPostFieldsString = implode('&', $regPostFieldsArrayEncoded);
	
	// extend any additional curl options to the default ones
	$regOpts = $curlOpts + array(
		CURLOPT_POST => true,
		CURLOPT_POSTFIELDS => $regPostFieldsString,
		
		CURLOPT_COOKIE => implode(';', $regCookie),
	);
	curl_setopt_array($regHandle, $regOpts);
	
	// perform get request
	$regHtml = curl_exec($regHandle);
	curl_close($regHandle);
	unset($regHandle);
	
	// parse the document
	$doc = phpQuery::newDocument($regHtml);
	phpQuery::selectDocument($doc);
	
	pq('.CourseInfoRow')->each('extractRow',new CallbackParam);
	
	// cleanup
	unset($regPage);
	
	echo $department."\n";
	ob_flush();
	flush();
}



?>