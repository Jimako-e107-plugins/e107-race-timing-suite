<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2013 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * e107 Blank Plugin
 *
*/
if (!defined('e107_INIT'))
{
    require_once(__DIR__ . '/../../class2.php');
}

// Front LAN domain for the manual passing-entry "keypad app" (relocated here
// from timetracker). NOTE: the keypad UI, header caption and operator notices
// below are intentionally LEFT HARDCODED (faithful 1:1 relocation — see
// NOTES.md); this loader makes racetiming's first front route LAN-ready and
// exposes the LAN_FRONT_RACETIMING_* set (languages/<Lang>/<Lang>_front.php).
// This is a FRONT page, so it loads ONLY racetiming's _front domain; _global is
// loaded automatically by the core (lan_global_list) and _admin is never used
// here (the only non-front constants referenced are core e107 strings).
e107::lan('racetiming', '', true);

/**
 * Converts a microtime float to an ISO 8601 datetime string.
 *
 * @param float $microtime The microtime float to convert.
 * @return string The ISO 8601 datetime string.
 */


// function microtimeToISO8601(float $microtime): string
// {
//     $seconds = floor($microtime);
//     $microseconds = $microtime - $seconds;
//     $miliseconds = round($microseconds * 1000, 0);
//     $datetime = new DateTime();

//     $iso8601 = $datetime->format('Y-m-d H:i:s');

//     $iso8601 .= rtrim(sprintf('.%03d', $miliseconds), '0');

//     return $iso8601;
// } 

// function getFormattedDateTime($milliseconds = 0)
// {
// 	// Get the current time in seconds
// 	$currentTime = time();

// 	// If milliseconds are provided, adjust the time
// 	if ($milliseconds > 0)
// 	{
// 		// Create a DateTime object from the current time
// 		$dateTime = new DateTime();
// 		// Set the DateTime object to the current time
// 		$dateTime->setTimestamp($currentTime);

// 		// Add the milliseconds to the DateTime object
// 		$dateTime->modify("+{$milliseconds} milliseconds");
// 	}
// 	else
// 	{
// 		// Just create a DateTime object for the current time
// 		$dateTime = new DateTime();
// 	}

// 	// Format the date and time including milliseconds
// 	return $dateTime->format('Y-m-d H:i:s.v'); // Use 'v' for milliseconds
// }

function getFormattedCurrentDateTime()
{
	// Create a DateTime object for the current time
	$dateTime = new DateTime();

	// Format the date and time including milliseconds
	return $dateTime->format('Y-m-d H:i:s.v'); // 'v' for milliseconds
}

define("e_IFRAME", true);
if(getperms(0)) {
	$displayedtime = getFormattedCurrentDateTime();
	echo $displayedtime; 
}
 


e107::css('inline',

	'
* {
	box-sizing: border-box;
}

.tracker {
	height: 100vh;
} 
.numpad {
	width: 100%;
	height: 60vh;
	
	display: flex;
	flex-flow: row wrap;
}
.button {
	display: flex;
	align-items: center;
	justify-content: center;
	flex: 1 0 33.3333%;
	font-size: 50px;
     
	background-color: #7892c2;
	color: white;
	border: 1px solid rgba(255, 255, 255, .25);
	 
 
	 
	&:hover {
		background-color: #082a54;
	}
}

.textbox {
    border: 2px dotted blue;
    outline: 0;
    height: 70px;
    width: 95%;
    font-family: arial;
    font-size: 65px;
    text-align: center;
    text-decoration: none;
    color: green;
}


// Playground stuff - Not related to the demo
.playground {
	height: 80vh;
	display: flex;
	align-items: center;
	justify-content: center;
	background-color: #f1f1f1;
}


// General stuff - Not related to the playground
.general {
	position: fixed;
	left: 0;
	right: 0;
	
	display: flex;
	height: 40px;
	padding: 0 20px;
	
	background-color: white;
	
	a {
		height: 100%;
		display: flex;
		align-items: center;
		flex: 1;
		
		font-family: "source-code-pro";
		font-size: .8rem;
		text-decoration: none;
		
		color: #c9c9c9;
		transition: color 300ms ease;
		
		&:hover {
			color: $active;
		}
	}
	a:last-of-type {
		justify-content: flex-end;
	}
}
 
 
');

require_once(HEADERF);                     // render the header (everything before the main content area)

$frm = e107::getForm();

/*
ak existuje záznam a snaží sa poslať SEND - záznam s časom, vypíše, že už existuje 
ak existuje záznam a snaží sa poslať DNF, DSQ - záznam uloží, pôvodný čas neprepíše a pridá údaj DNF, DSQ 
ak už má akýkoľvek záznam s DNQ, DNS, ..  , tak vypíše že už skončil a záznam nepridá
*/
 

if($_POST['submit']) {
 
    $racer_number = trim($_POST['number']);
    $race_point_sef = trim($_POST['race_time_point']);
 
    $ended = '';
    if ($_POST['submit'] == "endeddnf") $ended = "DNF";
	if ($_POST['submit'] == "endeddsq") $ended = "DSQ";

    $query = "SELECT *  FROM " . MPREFIX . "race_time WHERE race_time_point LIKE '{$race_point_sef}' 
        AND race_time_racer_number LIKE '{$racer_number}' LIMIT 1";
 
    $exist = e107::getDb()->retrieve($query);

	if($exist) {
		$race_time_id = $exist['race_time_id'];
	 
	}
 
	if ($exist && $ended == "")
	{

		$message = "<span class='bg-danger text-white'>Pretekár č. {$racer_number} je už na {$race_point_sef} zapísaný</span>";
	}
    else {

		/* skontroluj ci predcasne neskoncil */
		$query2 = "SELECT *  FROM " . MPREFIX . "race_time WHERE race_time_racer_number LIKE '{$racer_number}'  AND race_time_ended != '' LIMIT 1";
 
		$exist2 = e107::getDb()->retrieve($query2);

		if($exist2) {

			$message = "<span class='bg-danger text-white'>Pretekár {$racer_number} už predčasne skončil</span>";
		}
		else {


			// $current_time = microtime(true);
				$displayedtime = getFormattedCurrentDateTime();
				echo $displayedtime; 
				$racer_number = sprintf("%04d", $racer_number);


				if($race_time_id) {
					/*
					UPDATE table_name
						SET column1 = value1, column2 = value2, ...
					WHERE condition;
					*/
					if($ended) {
						$sql = "UPDATE " . MPREFIX . "race_time  SET race_time_point = '" . $race_point_sef . "', race_time_racer_number = '" . $racer_number . "', 
						 race_time_ended = '" . $ended . "', race_time_updated = " . time() .
							" WHERE race_time_id = " . $race_time_id;
					}
					else {
						$sql = "UPDATE " . MPREFIX . "race_time  SET race_time_point = '" . $race_point_sef . "', race_time_racer_number = '" . $racer_number . "', 
						race_time_time = '" . $displayedtime . "', race_time_ended = '" . $ended . "', race_time_updated = " . time() .
							" WHERE race_time_id = " . $race_time_id;
					}
					if (e107::getDb()->gen($sql) === false)
					{
						e107::getMessage()->addError(LAN_UPDATED_FAILED . ': ' . ADLAN_78);
						$error = e107::getDb()->getLastErrorText();
						e107::getMessage()->addDebug($error);
						e107::getMessage()->addDebug(print_a($sql, true));
					}
		
				}
				else {
				
					$insert =
						[
							'race_time_point' => $race_point_sef,
							'race_time_racer_number' =>  $racer_number,
							'race_time_time' => $displayedtime,
							'race_time_ended' => $ended,
							'race_time_created' => time()
						];
					
					$result = e107::getDb()->insert('race_time', $insert);
				
				}
		}
        
    }
 
}

$race_point_sef = $_GET['k'];

$query = "SELECT * FROM " . MPREFIX . "race_point AS rp
            WHERE race_point_code = '{$race_point_sef}'";

$point_data = e107::getDb()->retrieve($query );


$where = " race_time_point = '". $race_point_sef."' "; 
$query = "SELECT * FROM  " . MPREFIX . "race_time AS rt WHERE race_time_point = '" . $race_point_sef . "' ORDER BY `race_time_id` DESC ";
$time_data = e107::getDb()->retrieve($query, true);

$count = count($time_data);
$last_number = $time_data[0]['race_time_racer_number'];

$caption = 
"<b>".$point_data['race_point_name'] . ": poradie {$count} - predošlé číslo: {$last_number} </b>". $message;
 
/*
CREATE TABLE `race_time` (
`race_time_id` int NOT NULL AUTO_INCREMENT,
`race_time_racer_number` VARCHAR(11) NOT NULL,
`race_time_point` VARCHAR(11) NOT NULL ,
`race_time_time` VARCHAR(100) NOT NULL ,
`race_time_ended` VARCHAR(3) NOT NULL DEFAULT '',
`race_time_created` int NOT NULL,
`race_time_updated` int NOT NULL,
PRIMARY KEY (`race_time_id`)
) ENGINE=MyISAM;
*/

$start = '<div class="tracker mb-3" > '; 
$end = '</div>';


 
$txt = $start. $caption;
$txt .= '<div class="playground">';
$txt .= $frm->open('point', 'POST', e_SELF);
$txt .=  $frm->text('number', "", 80, ['required' => 1, 'class'=> 'textbox']) ;
$txt .= '
 
	<div class="numpad">
		<div class="button" onclick="a = document.forms[0].number.value;
document.forms[0].number.value =  a + &quot;1&quot;" >1</div>
  		<div class="button" onclick="a = document.forms[0].number.value;
document.forms[0].number.value =  a + &quot;2&quot;" >2</div>
		<div class="button" onclick="a = document.forms[0].number.value;
document.forms[0].number.value =  a + &quot;3&quot;" >3</div>
	    <div class="button" onclick="a = document.forms[0].number.value;
document.forms[0].number.value =  a + &quot;4&quot;" >4</div>
    	<div class="button" onclick="a = document.forms[0].number.value;
document.forms[0].number.value =  a + &quot;5&quot;" >5</div>
		<div class="button" onclick="a = document.forms[0].number.value;
document.forms[0].number.value =  a + &quot;6&quot;" >6</div>
		<div class="button" onclick="a = document.forms[0].number.value;
document.forms[0].number.value =  a + &quot;7&quot;" >7</div>
		<div class="button" onclick="a = document.forms[0].number.value;
document.forms[0].number.value =  a + &quot;8&quot;" >8</div>
		<div class="button" onclick="a = document.forms[0].number.value;
document.forms[0].number.value =  a + &quot;9&quot;" >9</div>
		<div class="button" onclick="a = document.forms[0].number.value;
document.forms[0].number.value =  a + &quot;0&quot;" >0</div>
 	
		<button class="button" type="submit" name="submit" value="endeddnf" id="submit">DNF</button>
		<button class="button" type="submit" name="submit" value="endeddsq" id="submit">DSQ</button>
		<div class="button" onclick="document.forms[0].number.value =  &quot;&quot;">CLEAR</div>
 
		<button class="button" style="background-color: #003b00; "  type="submit" name="submit" value="point" id="submit">SEND</button>
	</div>
	
</div>
 
 ';

 
 
//$txt .= $frm->button('submit', 'point', 'submit', 'Odoslať', ['class'=> 'myButton']) . $end;
$txt .= $frm->hidden('race_time_point', $race_point_sef);
$txt .= $frm->close();
$text .= $end;

e107::getRender()->tablerender("", $txt);

require_once(FOOTERF);					// render the footer (everything after the main content area)
