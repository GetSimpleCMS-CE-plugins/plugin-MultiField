<?php
/*
Plugin Name: Hello World
Description: Echos "Hello World" in footer of theme
Version: 1.0
Author: Chris Cagle
Author URI: http://www.cagintranet.com/
*/

# get correct id for plugin
$thisfile = basename(__FILE__, ".php");

# add in this plugin's language file
i18n_merge('multiField') || i18n_merge('multiField', 'en_US');


# register plugin
register_plugin(
	$thisfile, //Plugin id
	'MultiField 🏖️', 	//Plugin name
	'1.0', 		//Plugin version
	'Multicolor',  //Plugin author
	'#', //author website
	i18n_r('multiField/DESCRIPTION'), //Plugin description
	'plugins', //page type - on which admin tab to display
	'multiField'  //main function (administration)
);


# add a link in the admin tab 'theme'
add_action('plugins-sidebar', 'createSideMenu', array($thisfile, i18n_r('multiField/SETTINGS').' 🏖️'));


function multiField()
{

	if (isset($_GET['creator'])) {
		include(GSPLUGINPATH . 'multiField/view/addNew.inc.php');
	}elseif (isset($_GET['migrate'])) {
		include(GSPLUGINPATH . 'multiField/view/migrate.inc.php');
	}else {
		include(GSPLUGINPATH . 'multiField/view/list.inc.php');
	}

	echo '<div id="paypal" style="margin-top:10px; background: #fafafa; border:solid 1px #ddd; padding: 10px;box-sizing: border-box; text-align: center;">
	<p style="margin-bottom:10px;">'.i18n_r('multiField/PAYPAL').' </p>
	<a href="https://www.paypal.com/donate/?hosted_button_id=TW6PXVCTM5A72"><img alt="" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" border="0"></a>
</div>';
}



add_action('edit-extras', 'extrasMultiField');

function extrasMultiField()
{
include(GSPLUGINPATH . 'multiField/view/extras.inc.php');
};



add_action('changedata-save', 'save');

function save()
{

	if (isset($_POST['submitted'])) {

		$file = file_get_contents(GSDATAOTHERPATH . 'multiField/' . $_POST["post-id"] . '.json');

		$multiFieldType = $_POST['type-multifield'];
		$multiFieldLabel = $_POST['label-multifield'];
		$multiField = $_POST['multifield'];

		$ars = array();

		foreach ($multiField as $key => $value) {
			$ars[$key] = ["label" => $multiFieldLabel[$key], "value" => htmlentities(htmlentities($value)), "type" => $multiFieldType[$key]];
		};


		$final = json_encode($ars, true);
		if (file_exists(GSDATAOTHERPATH . 'multiField/') == null) {
			mkdir(GSDATAOTHERPATH . 'multiField/', 0755);
		}
		file_put_contents(GSDATAOTHERPATH . 'multiField/' . $_POST["post-id"] . '.json', $final);
	};
};


//frontend

function multiFields($name)
{

	$file = GSDATAOTHERPATH . 'multiField/' . return_page_slug() . '.json';

	if (file_exists($file)) {

		$final = json_decode(file_get_contents($file), true);
		foreach ($final as $key => $value) {

			if ($final[$key]['label'] == $name) {
				echo html_entity_decode(html_entity_decode($final[$key]['value']));
			}
		}
	}
}


function r_multiFields($name)
{

	$file = GSDATAOTHERPATH . 'multiField/' . return_page_slug() . '.json';

	if (file_exists($file)) {

		$final = json_decode(file_get_contents($file), true);
		foreach ($final as $key => $value) {

			if ($final[$key]['label'] == $name) {
				return html_entity_decode(html_entity_decode($final[$key]['value']));
			}
		}
	}
}
