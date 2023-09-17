<style>
	.form input {
		width: 100%;
		padding: 10px;
		box-sizing: border-box !important;
	}

	.form ul {
		margin: 10px 5px !important;
		padding: 10px !important;
	}

	.form-input {
		display: grid;
		grid-template-columns: 1fr 1fr 50px;
		gap: 10px;
		padding: 10px;
		margin: 5px 0;
		background: #fafafa;
		border: solid 1px #ddd;
		align-items: center;
		border-radius: 5px;
	}

	.form-input label {
		grid-column: 1/2
	}

	.form-input code {
		background: #fafafa;
		border: solid 1px #ddd;
		padding: 5px;
		box-sizing: border-box;
		grid-column: 2/4;
		font-size: 11px;
		text-align: center;
	}

	.form-input :is(input, select, button) {
		padding: 10px;
		background: #fff;
		border: solid 1px #ddd;
		border-radius: 5px;
	}

	.form-input input {
		grid-column: 1/2;
	}

	.form-input select {
		grid-column: 2/3;
	}

	.form .submit,
	.form .order {
		width: 200px;
		border: none;
		border-radius: 0 !important;
		font-weight: inherit !important;
		background: #146C94 !important;
		color: #fff !important;
		border: none !important;
		border-radius: 5px !important;
		text-shadow: none !important;
		cursor: pointer;
	}

	.form .order {
		padding: 10px;
		display: inline-block;
		border: none !important;
		background: #000 !important;
		border-radius: 5px;
	}

	.form-input button {
		background: #D21312;
		color: #fff;
		grid-column: 3/4;
		width: 100%;
		border: none;
	}

	.add-input {
		background: #fafafa;
		border: solid 1px #ddd;
		width: 100%;
		box-sizing: border-box;
		padding: 10px;
		margin: 10px 0;
		border-radius: 5px !important;
	}

	.add-input input {
		width: 100%;
		border: solid 1px #ddd;
		background: #fff;
		border-radius: 5px;
	}

	.add-input select {
		width: 100%;
		border: solid 1px #ddd;
		background: #fff;
		margin: 10px 0 !important;
		border-radius: 5px;
	}

	.add-input button {
		padding: 10px;
		border: none;
		background: #146C94;
		border-radius: 5px;
		color: #fff;
		cursor: pointer;
		border-radius: 5px;
		display: flex;
		align-items: center;
	}

	.title-bar {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 30px;
		padding: 10px 0;
		border-bottom: solid 1px #ddd;
	}

	.title-bar h3 {
		margin: 0;
	}

	.title-bar a {
		margin: 0 !important;
		padding: 0 !important;
		background: #146C94;
		color: #fff !important;
		text-decoration: none !important;
		padding: 10px !important;
		border-radius: 5px;
		display: flex;
		align-items: center;
	}

	.title-bar a img {
		filter: invert(100%);
		width: 20px;
		margin-left: 10px;
	}
</style>

<?php
global $SITEURL;
global $GSADMIN;
$url = $SITEURL . $GSADMIN . '/load.php?id=multiField';; ?>

<div class="title-bar">
	<h3><?php echo i18n_r('multiField/MULTIFIELDCREATE'); ?></h3>
	<a href="<?php echo $url; ?>"><?php echo i18n_r('multiField/BACKTOLIST'); ?> <img src="<?php echo $SITEURL . 'plugins/multiField/img/back.svg'; ?>"></a>
</div>

<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

<form class="form" <?php
					global $SITEURL;
					global $GSADMIN;
					if (isset($_POST['creator'])) {
						echo " action ='" . $SITEURL . $GSADMIN . "/load.php?id=multiField&creator=" . $_POST['creator'] . "'";
					}; ?> method="post">





	<?php

	$posFile = GSDATAOTHERPATH . 'multiField/position-' . $_GET['creator'] . '.txt';

	if (file_exists($posFile)) {

		echo '<script>

	window.addEventListener("load",()=>{

		document.querySelector(".pos").value = "' . file_get_contents($posFile) . '"


	});

	</script>';
	};; ?>



	<div x-data="content">


		<h4><i x-text="positionname"></i></h4>
		<select name="position" class="pos" style="padding:10px;background:#fafafa;border:solid 1px #ddd;width:100%;margin:10px 0;border-radius:5px;">
			<option x-text="up" value="up"></option>
			<option x-text="bottom" value="bottom"></option>
		</select>

		<h4><i x-text="selectPage"></i></h4>

		<select name="filename" style="padding:10px;background:#fafafa;border:solid 1px #ddd;width:100%;margin:10px 0;border-radius:5px;">
			<option value="allmultifield">All</option>
			<?php foreach (glob(GSDATAPAGESPATH . '*.xml') as $file) {
				$xmlFile = simplexml_load_file($file);
				$filePure = pathinfo($file)['filename'];
				echo '<option ' . ($_GET['creator'] == $filePure ? 'selected="selected"' : '') . ' value="' . $filePure . '">' . $xmlFile->title . '</option>';
			}; ?>
		</select>




		<h4><i x-text="addNewInput"></i></h4>

		<div class="add-input">
			<input type="text" x-model="title">
			<br>
			<select x-model="select" style="width:100%;padding:10px;margin:5px;">
				<template x-for="(selector,index) in selectList">
					<option :value="selector" x-text="selectListLang[index]"></option>
				</template>
			</select>

			<br>
			<button @click.prevent="inputList[count]={'label':title,'value':'','type':select}, title='title' + count++, console.log(inputList)" x-html="buttonAdd"></button>
		</div>

		<br>

		<h4><i x-text="inputNameList"></i></h4>
		<hr>

		<div style="margin:10px 0;" id="sortable">
			<template x-for="(input,index) in inputList" :key="index">
				<div class="form-input">
					<label x-text="input.label.replace(/-/g,' ')"></label>
					<span class="shortcode tpl" style="text-align:center">&#60;?php multiFields('<span x-text="input.label.replace(/ /g,'-')"></span>') ;?&#62;</span>
					<input :value="input.label.replace(/-/g,' ')" required style="width:100%;" x-model="input.label">
					<input type="hidden" name="multi-field-label[]" :value="input.label.replace(/ /g,'-')" placeholder="label">
					<select name="multi-field-type[]">
						<template x-for="(selector,index) in selectList">
							<option :value="selector" :selected="input.type==selector" x-text="selectListLang[index]"></option>
						</template>
					</select>
					<button @click.prevent="if(confirm(sureQuestion)){delete inputList[index]}" x-html="remove"></button>
				</div>
			</template>
		</div>

		<input type="submit" :value="save" class="submit" name="submit">
		<button style="display:inline-flex;align-items:center;justify-content:center;" class="sortable order" x-html="sortName" @click.prevent="sortable"></button>

	</div>

</form>

<script>
	const content = {
		buttonAdd: '<?php echo i18n_r('multiField/ADDNEWINPUT'); ?> <img style="margin-left:10px;width:20px;filter:invert(100%);" src="<?php echo $SITEURL . 'plugins/multiField/img/plus.svg'; ?>">',
		title: '<?php echo i18n_r('multiField/TITLE'); ?>',
		selectPage: '<?php echo i18n_r('multiField/SELECTPAGE'); ?>',
		count: 0,
		select: '',
		inputNameList: '<?php echo i18n_r('multiField/MULTIFIELDSLIST'); ?>',
		remove: '<img style="width:17px;filter:invert(100%);" src="<?php echo $SITEURL . "plugins/multiField/img/trash.svg"; ?>">',
		save: '<?php echo i18n_r('multiField/SAVE'); ?>',
		addNewInput: '<?php echo i18n_r('multiField/ADDNEWINPUT'); ?>',
		sureQuestion: '<?php echo i18n_r('multiField/REMOVEQUESTION'); ?>',

		selectList: ["text", "textarea", "wysywig", "color", "date", "foto", "link",'checkbox', 'dropdown'],
		selectListLang: ["<?php echo i18n_r('multiField/TEXT'); ?>", "<?php echo i18n_r('multiField/TEXTAREA'); ?>", "<?php echo i18n_r('multiField/WYSYWIG'); ?>", "<?php echo i18n_r('multiField/COLOR'); ?>",
			"<?php echo i18n_r('multiField/DATE'); ?>", "<?php echo i18n_r('multiField/FOTO'); ?>", "<?php echo i18n_r('multiField/LINK'); ?>", 'Checkbox', 'Dropdown'
		],
		inputList: <?php
					if ($_GET['creator'] !== '') {
						echo file_get_contents(GSDATAOTHERPATH . 'multiField/settings-' . $_GET['creator'] . '.json');
					} else {
						echo '[]';
					}; ?>,

		sortableCheck: false,

		sortName: '<?php echo i18n_r('multiField/ORDER'); ?> <img style="width:15px;filter:invert(100%);margin-left:5px;" src="<?php echo $SITEURL . "plugins/multiField/img/order.svg"; ?>">',
		sortBtn: '<?php echo i18n_r('multiField/ORDER'); ?> <img style="width:15px;filter:invert(100%);margin-left:5px;" src="<?php echo $SITEURL . "plugins/multiField/img/order.svg"; ?>">',
		sortBtnDone: '<?php echo i18n_r('multiField/DONE'); ?> <img style="width:15px;filter:invert(100%);margin-left:5px;" src="<?php echo $SITEURL . "plugins/multiField/img/done.svg"; ?>">',

		positionname: '<?php echo i18n_r('multiField/POSITION'); ?>',
		up: '<?php echo i18n_r('multiField/UP'); ?>',
		bottom: '<?php echo i18n_r('multiField/DOWN'); ?>',
		uper: 'up',
		bottomer: 'bottom',


		sortable: function() {
			if (this.sortableCheck == false) {
				this.sortableCheck = true;
				this.sortName = this.sortBtnDone;
				$("#sortable").sortable();
				$("#sortable").sortable("enable");
				document.querySelectorAll('.form-input').forEach(x => {
					x.style.cursor = 'ns-resize';
				});
			} else {
				this.sortableCheck = false;
				this.sortName = this.sortBtn;
				$("#sortable").sortable("disable");
				document.querySelectorAll('.form-input').forEach(x => {
					x.style.cursor = 'revert';
				});
			}
		},
	}
</script>



<?php

if (isset($_POST['submit'])) {
	$multiFieldType = $_POST['multi-field-type'];
	$multiFieldLabel = $_POST['multi-field-label'];

	$ars = array();

	foreach ($multiFieldLabel as $key => $value) {
		$ars[$multiFieldLabel[$key]] = ["label" => $multiFieldLabel[$key], "value" => "", "type" => $multiFieldType[$key]];
	};

	$final = json_encode($ars);

	if (file_exists(GSDATAOTHERPATH . 'multiField/') == null) {
		mkdir(GSDATAOTHERPATH . 'multiField/', 0755);
	}

	file_put_contents(GSDATAOTHERPATH . 'multiField/settings-' . $_POST['filename'] . '.json', $final);
	file_put_contents(GSDATAOTHERPATH . 'multiField/position-' . $_POST['filename'] . '.txt', $_POST['position']);

	global $SITEURL;
	global $GSADMIN;

	echo ("<meta http-equiv='refresh' content='0'>");
	echo "<script> window.location.href = '" . $SITEURL . $GSADMIN . "/load.php?id=multiField&creator=" . $_POST['filename'] . "'</script>";
}; ?>