<style>
	.multifields {
		margin: 0 !important;
		padding: 0 !important;
	}

	.multifields div {
		margin: 10px 0;
	}

	.multifields label {
		margin-bottom: 5px;
	}

	div.multifields div input {
		width: 97%;
		padding: 7px;

		border-radius: 5px;
		max-height: 300px;
		border: solid 1px #ddd;
	}

	div.multifields div textarea {
		height: 200px;
		width: 97%;
	}

	div.multifields div button {
		height: 35px;
		margin: 5px 0 0 5px;
</style>

<?php

$file =  GSDATAOTHERPATH . 'multiField/settings-' . @$_GET['id'] . '.json';
$fileAll =  GSDATAOTHERPATH . 'multiField/settings-allmultifield.json';

$fileinput = GSDATAOTHERPATH . 'multiField/' . @$_GET['id'] . '.json';


$filer = '';

if (file_exists($file) || file_exists($fileAll)) {

	$filer .= @file_get_contents($file) ?? '';

	if (file_exists($file) && file_exists($fileAll)) {
		$filer = substr($filer, 0, -1) . ',';
		$filer .= substr(file_get_contents($fileAll), 1);
	};

	if (!file_exists($file) && file_exists($fileAll)) {
		$filer .= file_get_contents($fileAll);
	};
} else {
	$filer = '{}';
};




if (file_exists($fileinput)) {
	$fileData = file_get_contents($fileinput);
} else {
	$fileData = '{}';
};




?>

<?php

global $EDLANG;
global $EDTOOL;
global $toolbar;
global $EDOPTIONS;

if (isset($EDTOOL)) $EDTOOL = returnJsArray($EDTOOL);
if (isset($toolbar)) $toolbar = returnJsArray($toolbar); // handle plugins that corrupt this

else if (strpos(trim($EDTOOL), '[[') !== 0 && strpos(trim($EDTOOL), '[') === 0) {
	$EDTOOL = "[$EDTOOL]";
}

if (isset($toolbar) && strpos(trim($toolbar), '[[') !== 0 && strpos($toolbar, '[') === 0) {
	$toolbar = '[$toolbar]';
}
$toolbar = isset($EDTOOL) ? ",toolbar: " . trim($EDTOOL, ",") : '';
$options = isset($EDOPTIONS) ? ',' . trim($EDOPTIONS, ",") : '';


?>



<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

<div x-data='{counters:0,dates:<?php echo $filer; ?>,dater:<?php echo $fileData; ?>}' class="multifields">
	<template x-for="(data,index) in dates" :key="index">
		<div>
			<template x-if="data['type']=='wysywig'">
				<div>
					<label x-text="data['label'].replace(/-/g,' ')"></label>
					<input style="margin:5px 0;display:block;" type="hidden" :value="data['label']" name="label-multifield[]">
					<input style="margin:5px 0;display:block;" type="hidden" :value="data['type']" name="type-multifield[]">
					<textarea x-html="dater[data['label']]['value'] === undefined ? '' : dater[data['label']]['value'] " x-init="CKEDITOR.replace( $el, {
						skin : 'getsimple',
						forcePasteAsPlainText : true,
						language : '<?php global $EDLANG;
									echo $EDLANG; ?>',
					defaultLanguage : 'en',
						entities : false,
						// uiColor : '#FFFFFF',
						height: '250px',
						baseHref : '<?php global $SITEURL;
									echo $SITEURL; ?>',
						tabSpaces:10,
						filebrowserBrowseUrl : 'filebrowser.php?type=all',
						filebrowserImageBrowseUrl : 'filebrowser.php?type=images',
						filebrowserWindowWidth : '730',
						filebrowserWindowHeight : '500'
			<?php echo $toolbar; ?>
			<?php echo  str_replace('"', "'", $options); ?>											
						});
						" name="multifield[]">
						</textarea>
				</div>
			</template>

			<template x-if="data['type']=='textarea'">
				<div>

					<label x-text="data['label'].replace(/-/g,' ')"></label>
					<input style="margin:5px 0;display:block;" type="hidden" :value="data['label']" name="label-multifield[]">
					<input style="margin:5px 0;display:block;" type="hidden" :value="data['type']" name="type-multifield[]">
					<textarea :x-bind:x-html="dater[data['label']]['value']" style="width:100%;padding:10px;box-sizing:border-box;" name="multifield[]"></textarea>
				</div>
			</template>

			<template x-if="data['type']=='dropdown'">
				<div>



					<label x-text="data['label'].replace(/-/g,' ').split('[')[0]"></label>
					<input style="margin:5px 0;display:block;" type="hidden" :value="data['label']" name="label-multifield[]">
					<input style="margin:5px 0;display:block;" type="hidden" :value="data['type']" name="type-multifield[]">




					<select name="multifield[]" style="background:#fff;border:solid 1px #ddd;width:100%;padding:10px;border-radius:5px;">

						<template x-for="option in data['label'].substring(data['label'].indexOf('[') + 1, data['label'].indexOf(']')).split(',')">
							<option x-text="option.replace(/-/g, ' ')" :value="option" :selected="dater[data['label']]['value'] == option"></option>
						</template>

					</select>
				</div>
			</template>


			<template x-if="data['type']=='checkbox'">
				<div>
					<label x-text="data['label'].replace(/-/g,' ')"></label>
					<input style="margin:5px 0;display:block;" type="hidden" :value="data['label']" name="label-multifield[]">
					<input style="margin:5px 0;display:block;" type="hidden" :value="data['type']" name="type-multifield[]">
					<input value="on" x-bind:checked="dater[data['label']]['value'] == 'on'" style="all:revert;" type="checkbox" name="multifield[]">
				</div>
			</template>

			<template x-if=" data['type']=='text' || data['type']=='color' || data['type']=='date' || data['type']=='date'">
				<div>
					<label x-text="data['label'].replace(/-/g,' ')"></label>
					<input style="margin:5px 0;display:block;" type="hidden" :value="data['label']" name="label-multifield[]">
					<input style="margin:5px 0;display:block;" type="hidden" :value="data['type']" name="type-multifield[]">
					<input style="margin:5px 0;display:block;width:100%;padding:10px;box-sizing:border-box;" :type="data['type']" :value="dater[data['label']]['value']" name="multifield[]">
				</div>
			</template>

			<template x-if="data['type']=='foto'">
				<div>
					<label x-text="data['label'].replace(/-/g,' ')"></label>
					<input style="margin:5px 0;display:block;" type="hidden" :value="data['label']" name="label-multifield[]">
					<input style="margin:5px 0;display:block;" type="hidden" :value="data['type']" name="type-multifield[]">
					<div style="display:flex;gap:2px">
						<input style="margin:5px 0;display:inline-block; width:80%" :type="data['type']" :value="dater[data['label']]['value']" :data-name="data['label']" class="foto" name="multifield[]">
						<button style="background:#000;color:#fff;border:none;padding:3px;cursor:pointer;border-radius:2px;width:20%" @click.prevent="window.open(`<?php echo $SITEURL; ?>plugins/multiField/files/imagebrowser.php?&func=multifield[]&count=${index}`,'myWindow','tolbar=no,scrollbars=no,menubar=no,width=500,height=500')"><?php echo i18n_r('multiField/GETPHOTO'); ?></button>
					</div>
				</div>
			</template>

			<template x-if=" data['type']=='link'">
				<div>
					<label x-text="data['label'].replace(/-/g,' ')"></label>
					<input style="margin:5px 0;display:block;" type="hidden" :value="data['label']" name="label-multifield[]">
					<input style="margin:5px 0;display:block;" type="hidden" :value="dater[data]['type']" name="type-multifield[]">
					<select name="multifield[]" style="background:#fff;border:solid 1px #ddd;width:100%;padding:10px;border-radius:5px;">
						<?php foreach (glob(GSDATAPAGESPATH . '*.xml') as $file) {
							$xmlFile = simplexml_load_file($file);
							$filePure = pathinfo($file)['filename'];
							global $SITEURL;
							global $GSADMIN;

							$link = $SITEURL  . $filePure;
							echo '<option :selected="dater[data[`label`]][`value`] == $el.value" value="' . $link . '">' . $xmlFile->title . '</option>';
						}; ?>
					</select>
				</div>
			</template>
		</div>
	</template>
</div>


<?php

$posFile = GSDATAOTHERPATH . 'multiField/position-' . @$_GET['id'] . '.txt';
$posAllFile = GSDATAOTHERPATH . 'multiField/position-allmultifield.txt';



if (file_exists($posFile) && file_get_contents($posFile) == 'bottom') : ?>

	<script>
		window.addEventListener("load", () => {
			document.querySelector('.editing').prepend(document.querySelector('.multifields'));
		});
	</script>
<?php endif; ?>



<?php if (file_exists($posAllFile) && !file_exists($posFile) && file_get_contents($posAllFile) == 'bottom') : ?>

	<script>
		window.addEventListener("load", () => {
			document.querySelector('.editing').prepend(document.querySelector('.multifields'));
		});
	</script>
<?php endif; ?>