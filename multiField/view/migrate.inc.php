<style>
	.form{
		background: #fafafa;
		border-radius: 5px;
		border:solid 1px #ddd;
		padding: 10px;
		box-sizing: border-box;
	}
	 .form :is(input) {
		 width: 100%;
		 padding: 10px;
		 border: solid 1px #ddd;
		 box-sizing: border-box;
		 border-radius: 5px;
	}
	 .form label {
		 margin: 10px 0;
	}
	 .btn {
		 background: #146C94;
		 color: #fff !important;
		 text-decoration: none !important;
		 margin: 0;
		 padding: 10px;
		 display: inline-block;
		 margin: 3px;
		 border-radius: 5px;
		 border: none;
		 margin-top: 10px;
		 width: 200px !important;
	}
	 .mf-alert{
		width: 100%;
		border-radius: 5px;
		background: #146C94;
		color:#fff;
		padding: 10px;
		box-sizing: border-box;
		margin-top: 20px;
	}
</style>

<h3><?php echo i18n_r('multiField/MIGRATE');?> MultiFields</h3>

<form action="#" method="post" class="form">
	<label for=""><?php echo i18n_r('multiField/OLDURL');?></label>
	<input type="text" name="oldurl" placeholder="https://youroldadress.com/">
	<label for=""><?php echo i18n_r('multiField/NEWURL');?></label>
	<input type="text" name="newurl" placeholder="https://yournewadress.com/">
	<input type="submit" class="btn" name="changeURL" value="<?php echo i18n_r('multiField/CHANGEURL');?>">
</form>

 <?php

	if (isset($_POST['changeURL'])) {
		foreach (glob(GSDATAOTHERPATH . 'multiField/*.json') as $file) {
			$fileContent = file_get_contents($file);
			$oldurl = str_replace('/', '\/', $_POST['oldurl']);
			$newurl = str_replace('/', '\/', $_POST['newurl']);
			$newContent = str_replace([$oldurl, $oldurl . '/'], [$newurl, $newurl . '/'], $fileContent);
			file_put_contents($file, $newContent);
		}
		echo '<div class="mf-alert">'.i18n_r('multiField/DONE').'!</div>';
	};; ?>