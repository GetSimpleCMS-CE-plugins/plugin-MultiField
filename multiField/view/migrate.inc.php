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
$url = $SITEURL . $GSADMIN . '/load.php?id=multiField'; 
?>

<div class="title-bar">
<h3><?php echo i18n_r('multiField/MIGRATE');?> MultiFields</h3>
<a style="float:right" href="<?php echo $url; ?>"><?php echo i18n_r('multiField/BACKTOLIST'); ?> <img width="24px" src="<?php echo $SITEURL . 'plugins/multiField/img/back.svg'; ?>"></a>
</div>

<form action="#" method="post" class="form">
	<label for=""><?php echo i18n_r('multiField/OLDURL');?></label>
	<input type="text" name="oldurl" placeholder="https://youroldadress.com/">
	<label for=""><?php echo i18n_r('multiField/NEWURL');?></label>
	<input type="text" name="newurl" placeholder="https://yournewadress.com/">
	<input type="submit" class="btn" style="background:green;" name="changeURL" value="<?php echo i18n_r('multiField/CHANGEURL');?>">
</form>

 <?php

	if (isset($_POST['changeURL'])) {
		foreach (glob(GSDATAOTHERPATH . 'multiField/*.json') as $file) {
			$fileContent = file_get_contents($file);
			$oldurl = isset($_POST['oldurl']) ? str_replace('/', '\/', $_POST['oldurl']) : '';
			$newurl = isset($_POST['newurl']) ? str_replace('/', '\/', $_POST['newurl']) : '';
			$newContent = str_replace([$oldurl, $oldurl . '/'], [$newurl, $newurl . '/'], $fileContent);
			file_put_contents($file, $newContent);
		}
		echo '<div class="mf-alert">'.i18n_r('multiField/DONE').'!</div>';
	};; ?>