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


// Category x track join now lives in racers itself (B3 de-coupling): the menu no
// longer reads timetracker_class::$racecats. Load the racers class the racers way
// (mirrors startlist.php) and pull the same (track x category) rows.
e107::getSingleton('plugin_racers_racers', e_PLUGIN . 'racers/includes/racers.php');


$text =
'<!--====== FEATURE ONE PART START ======-->
<section class="features-area features-one">
   <div class="container">
       
      <!-- row -->
      <div class="row justify-content-center">';

$category_data = plugin_racers_racers::getCategoriesWithTracks();
foreach ($category_data as $category) {
 
	$url = e107::url('racers', 'startlist', $category);
	$url2 = e107::url('racereports', 'finish', $category);
	
    $title = $category['race_name'] . " <br> " . $category['race_category_name'];
    $category_color = $category['race_category_color'];

    $text .= '<div class="col-lg-3 col-md-6 col-sm-6" >
                <div class="features-style-one text-center" style="background-color:' . $category_color . ';">
                
                <div class="features-content" >
                    <h4 class="features-title">'. $title. '</h4>
                     
                    <div class="features-btn rounded-buttons">
                        <a
                            class="btn btn-primary rounded-full"
                            href="'.$url. '"
                            >
                        Štart.listina
                        </a>
                    </div>
                   <div class="features-btn rounded-buttons">
                        <a
                            class="btn btn-secondary rounded-full"
                            href="' . $url2 . '"
                            >
                        Cieľ
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
