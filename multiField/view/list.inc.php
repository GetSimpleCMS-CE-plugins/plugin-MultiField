<h3><?php echo i18n_r('multiField/MULTIFIELDSLIST'); ?></h3>

<style>
    .multifield-list {
        list-style-type: none;
        margin: 0 !important;
        padding: 0 !important;
    }

    .multifield-list li {
        padding: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: solid 1px #ddd;
    }

    .multifield-list li:nth-child(2) {
        background: #fafafa;
    }

    .multifield-list li a {
        margin-left: 5px;
        background: `linear-gradient(to bottom, #146C94, #009FBD);
        padding: 5px;
        color: #fff !important;
        text-decoration: none !important;
        display: inline-block;
        border-radius: 5px;
    }

    .multifield-list li a img {
        padding: 0;
        margin: 0;
    }

    .multifield-list li a:nth-child(2) {
        background: #D21312;
    }

    .multifield-list li p {
        color: #000;
        font-weight: bold;
        margin: 0 !important;
        font-size: 14px;
        font-style: italic;
        margin: 0 !important;
        padding: 0 !important;
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
        display: inline-flex;
        align-items: center;
    }
</style>

<?php
global $SITEURL;
global $GSADMIN;

$url = $SITEURL . $GSADMIN . '/load.php?id=multiField';
?>

<a href="<?php echo $url . '&creator'; ?>" class="btn"><?php echo i18n_r('multiField/ADDNEW'); ?> <img style="width:20px;filter:invert(100%);margin-left:5px;" src="<?php echo $SITEURL . "plugins/multiField/img/plus.svg"; ?>"></a>
<a href="<?php echo $url . '&migrate'; ?>" class="btn"><?php echo i18n_r('multiField/MIGRATE'); ?> <img style="width:18px;filter:invert(100%);margin-left:5px;" src="<?php echo $SITEURL . "plugins/multiField/img/web.svg"; ?>"></a>
<hr>
<ul class="multifield-list">

<?php 
foreach (glob(GSDATAOTHERPATH . 'multiField/settings-*.json') as $file) {
    $pureFile = str_replace('settings-', '', pathinfo($file, PATHINFO_FILENAME));
    
    $pageFile = GSDATAPAGESPATH . $pureFile . '.xml';
    $title = 'Page no exist';
    
    if ($pureFile == 'allmultifield') {
        $title = 'All';
    } elseif (file_exists($pageFile)) {
        $xm = @simplexml_load_file($pageFile);
        if ($xm && isset($xm->title)) {
            $title = (string)$xm->title;
        }
    }
    
    echo '
    <li>
        <p>' . htmlspecialchars($title) . '</p>
        <div class="btns">
            <a href="' . $url . '&creator=' . urlencode($pureFile) . '"> 
                <img style="width:18px;margin-left:5px;" src="' . $SITEURL . 'plugins/multiField/img/edit.svg">
            </a>
            <a href="' . $url . '&delete=' . urlencode($pureFile) . '" onclick="return confirm(`Are you sure you want to delete this item?`)">
                <img style="width:18px;filter:invert(100%)" src="' . $SITEURL . 'plugins/multiField/img/trash.svg">
            </a>
        </div>
    </li>';
} 
?>

</ul>

<?php 
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $deleteFile = basename($_GET['delete']); // Sanitize input
    $dataFile = GSDATAOTHERPATH . 'multiField/' . $deleteFile . '.json';
    $settingsFile = GSDATAOTHERPATH . 'multiField/settings-' . $deleteFile . '.json';
    
    if (file_exists($dataFile)) unlink($dataFile);
    if (file_exists($settingsFile)) unlink($settingsFile);
    
    echo "<script> window.location.href = '" . $SITEURL . $GSADMIN . "/load.php?id=multiField'</script>";
}
?>