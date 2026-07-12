<?php

/**
 * e107 website system
 *
 * Copyright (C) 2008-2016 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 */


// news item rewrite for v2.x


if (!defined('e107_INIT'))
{
    require_once(__DIR__ . '/../../class2.php');
}


$caption = '';

$text = '';

// The single event is owned by the raceevent plugin (prefs) now (#49). Read the
// event description from raceevent and escape it on output: it is HTML from a
// textarea, so it is rendered via $tp->toHTML() (never echoed raw).
$text = e107::getParser()->toHTML(e107::getPlugConfig('raceevent')->get('event_description'), true, 'BODY');

// Caption can be sourced from raceevent's event_name (escape via toAttribute());
// left disabled to preserve the existing menu behaviour.
//$caption = e107::getParser()->toAttribute(e107::getPlugConfig('raceevent')->get('event_name'));


e107::lan('racetrack', '', true);


 


$text =
'<!--====== FEATURE ONE PART START ======-->
<section class="features-area features-one">
   <div class="container">
       
      <!-- row -->
      <div class="row justify-content-center">';

$query = "SELECT * FROM  #race AS r,  #race_point AS rc 
        WHERE FIND_IN_SET(r.race_id, rc.race_point_race) ORDER BY race_point_order";
 

$points_data = e107::getDb()->retrieve($query, true); 
 
foreach ($points_data as $point) {
    extract($point);
 
    /*
    [race_point_id] => 7
    [race_point_race] => 1
    [race_point_code] => start
    [race_point_name] => Štart
    [race_point_password] => a746f9d49809687527bc0081997738b0
    [race_point_order] => 1
    */
    if($race_point_code == 'start') continue;
    if ($race_point_code == 'finish') continue;

 
    $point['race_point_sef'] = $point['race_point_code'];
    $url = e107::url('racereports', 'point', $point);

    $title =   $point['race_point_name'];
    $trat =   $race_name;
 
    $text .= '<div class="col-md-4 col-12" >
                <div class="features-style-one text-center" style="background-color:' . $point_color . ';">
                
                <div class="features-content" >
                   <h4 class="features-title">'. $trat .'</h4>
                     
                    <div class="features-btn rounded-buttons">
                        <a
                            class="btn btn-primary rounded-full"
                            href="'.$url. '"
                            >
                       '.$title.'
                        </a>
                    </div>
                    

                </div>
                </div>
                <!-- single features -->
            </div>';
}         
          
$text .= ' </div>
      <!-- row -->
   </div>
   <!-- container -->
</section>
<!--====== FEATURE ONE PART ENDS ======-->';


e107::getRender()->tablerender('', $start . $text . $end, 'wmessage');
