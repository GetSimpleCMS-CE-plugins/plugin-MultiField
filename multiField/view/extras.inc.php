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
		border:solid 1px #ddd;
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

$file =  GSDATAOTHERPATH . 'multiField/settings-' . $_GET['id'] . '.json';
$fileinput = GSDATAOTHERPATH . 'multiField/' . $_GET['id'] . '.json';

if (file_exists($fileinput)) {
	$fileData = file_get_contents($fileinput);
} else {
	$fileData = '{}';
};


?>

<?php if (file_exists($file)) : ?>



	<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

	<div x-data='{counters:0,dates:<?php echo file_get_contents($file); ?>,dater:<?php echo $fileData; ?>}' class="multifields">
		<template x-for="(data,index) in dates" :key="index">
			<div>
				<template x-if="data['type']=='wysywig'">
					<div>
						<label x-text="data['label'].replace(/-/g,' ')"></label>
						<input style="margin:5px 0;display:block;" type="hidden" :value="data['label']" name="label-multifield[]">
						<input style="margin:5px 0;display:block;" type="hidden" :value="data['type']" name="type-multifield[]">
						<textarea x-html="dater[data['label']]['value']" x-init="CKEDITOR.replace( $el, {
						skin : 'getsimple',
						forcePasteAsPlainText : true,
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
						,toolbar: 'advanced'										
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
						<textarea x-html="dater[data['label']]['value']" style="width:100%;padding:10px;box-sizing:border-box;" name="multifield[]"></textarea>
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
							<input style="margin:5px 0;display:inline-block; width:80%" :type="data['type']" :value="dater[data['label']]['value']" :data-name="dater[data['label']]['label']" class="foto" name="multifield[]">
							<button style="background:#000;color:#fff;border:none;padding:3px;cursor:pointer;border-radius:2px;width:20%" @click.prevent="window.open(`<?php echo $SITEURL; ?>plugins/multiField/files/imagebrowser.php?&func=multifield[]&count=${index}`,'myWindow','tolbar=no,scrollbars=no,menubar=no,width=500,height=500')"><?php echo i18n_r('multiField/GETPHOTO'); ?></button>
						</div>
					</div>
				</template>

				<template x-if=" data['type']=='link'">
					<div>
						<label x-text="data['label'].replace(/-/g,' ')"></label>
						<input style="margin:5px 0;display:block;" type="hidden" :value="data['label']" name="label-multifield[]">
						<input style="margin:5px 0;display:block;" type="hidden" :value="dater[data]['type']" name="type-multifield[]">
						<select name="multifield[]">
							<?php foreach (glob(GSDATAPAGESPATH . '*.xml') as $file) {
								$xmlFile = simplexml_load_file($file);
								$filePure = pathinfo($file)['filename'];
								global $SITEURL;
								global $GSADMIN;

								$link = $SITEURL . $GSADMIN . '/' . $filePure;
								echo '<option :selected="dater[data[`label`]][`value`] == $el.value" value="' . $link . '">' . $xmlFile->title . '</option>';
							}; ?>
						</select>
					</div>
				</template>
			</div>
		</template>
	</div>

<?php endif; ?>


<?php

$posFile = GSDATAOTHERPATH . 'multiField/position-' . $_GET['id'] . '.txt';



if (file_exists($posFile) && file_get_contents($posFile) == 'Bottom') : ?>

	<script>
		window.addEventListener("load", () => {
			document.querySelector('.editing').prepend(document.querySelector('.multifields'));
		});
	</script>
<?php endif; ?>