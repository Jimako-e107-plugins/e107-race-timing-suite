<?php

require_once("../../../class2.php");
include_once("admin_menu.php");


 

class translation_ui extends e_admin_ui
{
	// Plugin configurations
	protected $pluginTitle = LAN_RACEREG_PLUGIN;
	protected $pluginName = 'racereg';
	protected $table = ''; // No table defined
	protected $pid = '';
	protected $perPage = 10;
	protected $batchDelete = true;
	protected $batchExport = true;
	protected $batchCopy = true;

	// Define preferences
	protected $prefs = [
		'racereg_lans' => array(
			'title' => '',
			'tab' => 0,
			'type' => 'method',
			'data' => 'array',
			'width' => '38%',
			'help' => '',
			'readParms' => '',
			'writeParms' => array("nolabel" => 1),
			'class' => 'left',
			'thclass' => 'left'
		),

	];

	// Initialization method
	public function init()
	{
	}

	public function beforePrefsSave($new_data, $old_data)
	{

		// ===================================================================
		// SUPER SIMPLE MULTILAN MERGE (one-liner per section)
		// Keeps ALL old languages when editing in another language
		// Works with PHP 7.4 – 8.3 (no loops needed)
		// ===================================================================

 
		if (isset($new_data['racereg_lans']) && is_array($new_data['racereg_lans']))
		{
			$old = $old_data['racereg_lans'] ?? [];
			$new = $new_data['racereg_lans'];

			$new_data['racereg_lans'] = array_replace_recursive($old, $new);
		}

		return $new_data;
	}
 
}



 

class translation_form_ui extends e_admin_form_ui
{
	public function racereg_lans($curVal, $mode)
	{
		switch ($mode)
		{
			case 'read': // Edit Page
				return "Are you cheating?"; // Simplified return statement

			case 'write': // Edit Page
				$value   = $curVal;
				$lanList = self::contactLanList();

				$text = "<div class='e-container'>";

				$text .= '<div class="panel panel-default">';
				$text .= '<div class="panel-heading"><strong>Language Strings</strong> – Edit translations for all languages</div>';
				$text .= '<div class="panel-body">';

				$text .= '<table class="table table-striped table-bordered">';
				$text .= '<thead>';
				$text .= '<tr>';
				$text .= '<th style="width:45%">Constant / Original English</th>';
				$text .= '<th>Translation (Multi-language)</th>';
				$text .= '</tr>';
				$text .= '</thead>';
				$text .= '<tbody>';

				foreach ($lanList as $fieldKey => $originalEnglish)
				{
					// === FIXED: default = ACTUAL value of the constant in CURRENT language ===
					// When admin switches to Slovak → default becomes the Slovak translation
					// When admin switches to English → default becomes English
					// Uses e107 core function deftrue() (safe + respects e_LANGUAGE)
					$currentLanValue = deftrue($fieldKey, $originalEnglish);   // fallback to English if constant not defined yet

					$field = [
						'type'       => 'text',
						'multilan'   => true,
						'writeParms' => [
							'size'    => 'block-level',
							'default' => $currentLanValue          // ← this is what you asked for
						]
					];

					// Safe value (PHP 7.4–8.3 + works before first save)
					$actual_value = $value[$fieldKey] ?? [];

					$text .= "<tr>";
					$text .= "<td><small><strong>" . $fieldKey . "</strong></small><br>" .  htmlspecialchars($originalEnglish). "</td>";
					$text .= "<td>" . $this->renderElement("racereg_lans[{$fieldKey}]", $actual_value, $field) . "</td>";
					$text .= "</tr>";
				}

				$text .= '</tbody></table>';
				$text .= '</div>'; // panel-body
				$text .= '</div>'; // panel
				$text .= "</div>"; // e-container

				return $text;
		}

		return null;
	}

	// ===================================================================
	// EASY TO EXTEND – add new strings here only
	// ===================================================================
	public static function contactLanList()
	{
		//DO NOT TRANSLATE THIS - it is key and help in admin area. Always in English.
		//Advice: After manual translation, export econtact prefs via Database/Tools and save them as backup. If you save to xml folder, they will be imported with next install

		return [
			'LAN_RACEREG_STATE_STARTLIST'  => 'Ste potvrdený na štartovej listine.',
			'LAN_RACEREG_STATE_SUBSTITUTE' => 'Trať je plná — boli ste zaradený medzi náhradníkov. V prípade uvoľnenia miesta budete posunutý vyššie.',
			'LAN_RACEREG_STATE_PENDING'    => 'Vaša registrácia bola prijatá a čaká na schválenie organizátorom.',
		];
	}
}



// Create an instance of the admin area
new racereg_adminArea();

// Include authentication and footer files
require_once(e_ADMIN . "auth.php");
e107::getAdminUI()->runPage();
require_once(e_ADMIN . "footer.php");
exit;
