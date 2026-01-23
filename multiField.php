<?php

	# get correct id for plugin
	$thisfile = basename(__FILE__, ".php");

	# add in this plugin's language file
	i18n_merge('multiField') || i18n_merge('multiField', 'en_US');

	# register plugin
	register_plugin(
		$thisfile, //Plugin id
		'MultiField 🏖️', 	//Plugin name
		'4.3', 		//Plugin version
		'Multicolor',  //Plugin author
		'#', //author website
		i18n_r('multiField/DESCRIPTION'), //Plugin description
		'plugins', //page type - on which admin tab to display
		'multiField'  //main function (administration)
	);

	# add a link in the admin tab 'theme'
	add_action('plugins-sidebar', 'createSideMenu', array($thisfile, i18n_r('multiField/SETTINGS').' 🏖️'));

	function multiField() {
		if (isset($_GET['creator'])) {
			include(GSPLUGINPATH . 'multiField/view/addNew.inc.php');
		}elseif (isset($_GET['migrate'])) {
			include(GSPLUGINPATH . 'multiField/view/migrate.inc.php');
		}elseif (isset($_GET['position'])) {
			include(GSPLUGINPATH . 'multiField/view/position.inc.php');
		}else {
			include(GSPLUGINPATH . 'multiField/view/list.inc.php');
		}

		echo '
		<div id="paypal" style="margin-top:10px; background: #fafafa; border:solid 1px #ddd; padding: 10px;box-sizing: border-box; text-align: center;">
		<p style="margin-bottom:10px;">'.i18n_r('multiField/PAYPAL').' </p>
		<a href="https://www.paypal.com/donate/?hosted_button_id=TW6PXVCTM5A72"><img alt="" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" border="0"></a>
		</div>';
	}

	add_action('edit-extras', 'extrasMultiField');

	function extrasMultiField() {
		include(GSPLUGINPATH . 'multiField/view/extras.inc.php');
	};

	add_action('changedata-save', 'save');

	function save() {
    global $url;

		if (isset($_POST['submitted'])) {
			
			$filePath = GSDATAOTHERPATH . 'multiField/' . $url . '.json';
			
			$multiFieldType = $_POST['type-multifield'] ?? [];
			$multiFieldLabel = $_POST['label-multifield'] ?? [];
			$multiField = $_POST['multifield'] ?? [];
			
			if (empty($multiField) || empty($url)) {
				return;
			}

			$ars = array();

			foreach ($multiField as $key => $value) {
				if (isset($multiFieldLabel[$key]) && isset($multiFieldType[$key])) {
					$ars[$multiFieldLabel[$key]] = [
						"label" => $multiFieldLabel[$key], 
						"value" => $value, 
						"type" => $multiFieldType[$key]
					];
				}
			}

			$final = json_encode($ars, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
			
			// Create directory if it doesn't exist
			$multiFieldDir = GSDATAOTHERPATH . 'multiField/';
			if (!file_exists($multiFieldDir)) {
				mkdir($multiFieldDir, 0755, true);
			}
			
			file_put_contents($filePath, $final);
		}
	}

//frontend
	function multiFields($name) {
		$file = GSDATAOTHERPATH . 'multiField/' . return_page_slug() . '.json';

		if (file_exists($file)) {
			$final = json_decode(file_get_contents($file), true);
			if (is_array($final)) {
				foreach ($final as $key => $value) {
					if (isset($final[$key]['label']) && $final[$key]['label'] == $name) {
						echo html_entity_decode($final[$key]['value'], ENT_QUOTES, 'UTF-8');
						return;
					}
				}
			}
		}
	}

	function r_multiFields($name) {
		$file = GSDATAOTHERPATH . 'multiField/' . return_page_slug() . '.json';

		if (file_exists($file)) {
			$final = json_decode(file_get_contents($file), true);
			if (is_array($final)) {
				foreach ($final as $key => $value) {
					if (isset($final[$key]['label']) && $final[$key]['label'] == $name) {
						return html_entity_decode($final[$key]['value'], ENT_QUOTES, 'UTF-8');
					}
				}
			}
		}
		return '';
	}