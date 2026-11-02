<?php
require_once GSPLUGINPATH . 'multiField/view/helpers.inc.php';

global $SITEURL;
global $GSADMIN;

$baseUrl = $SITEURL . $GSADMIN . '/load.php?id=multiField';

/* ---------------------------------------------------------------------
 * Delete first (before any output), then bounce back to the list.
 * Also removes the position-<id>.txt that used to be left behind.
 * ------------------------------------------------------------------- */
if (!empty($_GET['delete'])) {
	$deleteId = mfSanitizeId($_GET['delete']);
	if ($deleteId !== '') {
		mfDeleteAll($deleteId);
	}
	echo '<script>window.location.href = ' . json_encode($baseUrl) . ';</script>';
	return;
}

/* ---------------------------------------------------------------------
 * Rendering helpers
 * ------------------------------------------------------------------- */
if (!function_exists('mfEsc')) {
	function mfEsc($s)
	{
		return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
	}
}

if (!function_exists('mfListBadge')) {
	/**
	 * @param string $level 'page' (node default) or 'field'
	 */
	function mfListBadge($pos, $source, $level, $nodeId)
	{
		$pos      = ($pos === 'bottom') ? 'bottom' : 'up';
		$arrow    = ($pos === 'bottom') ? '&#9660;' : '&#9650;';
		$posName  = ($pos === 'bottom') ? i18n_r('multiField/DOWN') : i18n_r('multiField/UP');
		$explicit = false;

		if ($source === 'field' || ($source === 'scope' && $level === 'page')) {
			$note     = i18n_r('multiField/POS_OWN');
			$explicit = true;
		} elseif ($source === 'scope') {
			$note = ($nodeId === MF_ALL) ? i18n_r('multiField/POS_ALL_DEFAULT') : i18n_r('multiField/POS_PAGE_DEFAULT');
		} elseif ($source === 'all') {
			$note = i18n_r('multiField/POS_FROM_ALL');
		} else {
			$note = i18n_r('multiField/POS_DEFAULT');
		}

		return '<span class="mf-badge ' . ($explicit ? 'is-explicit' : 'is-inherited') . '">'
			. $arrow . ' ' . mfEsc($posName) . ' <em>' . mfEsc($note) . '</em></span>';
	}
}

if (!function_exists('mfComputeVisible')) {
	/** A page is shown if it has fields itself or any descendant does (keeps the path visible). */
	function mfComputeVisible($id, $children, $settingsIds, &$visible, &$seen)
	{
		if (isset($seen[$id])) {
			return !empty($visible[$id]);
		}
		$seen[$id] = true;
		$show = isset($settingsIds[$id]);
		if (isset($children[$id])) {
			foreach ($children[$id] as $childId) {
				if (mfComputeVisible($childId, $children, $settingsIds, $visible, $seen)) {
					$show = true;
				}
			}
		}
		$visible[$id] = $show;
		return $show;
	}
}

if (!function_exists('mfListNode')) {
	function mfListNode($id, $title, $ctx, $isRoot = false)
	{
		$ctx->rendered[$id] = true;

		$hasSettings = isset($ctx->settingsIds[$id]);
		$fields      = $hasSettings ? mfReadSettings($id) : array();
		list($pos, $src) = mfResolvePosition('', $id);

		$editUrl   = $ctx->base . '&creator=' . rawurlencode($id);
		$deleteUrl = $ctx->base . '&delete=' . rawurlencode($id);
		$confirm   = mfEsc(json_encode(i18n_r('multiField/REMOVEQUESTION')));

		$cls = 'mf-node' . ($isRoot ? ' mf-root' : '') . ($hasSettings ? '' : ' is-empty');
		$h   = '<li class="' . $cls . '">';

		/* node header */
		$h .= '<div class="mf-row">';
		$h .= '<div class="mf-title">';
		$h .= '<strong>' . mfEsc($title) . '</strong>';
		if (!$isRoot) {
			$h .= ' <code>' . mfEsc($id) . '</code>';
		}
		$h .= ' ' . mfListBadge($pos, $src, 'page', $id);
		$h .= '</div>';

		$h .= '<div class="btns">';
		$h .= '<a href="' . mfEsc($editUrl) . '" title="' . mfEsc($hasSettings ? i18n_r('multiField/EDIT') : i18n_r('multiField/ADDFIELDS')) . '">';
		$h .= '<img style="width:18px;" src="' . mfEsc($ctx->siteurl) . 'plugins/multiField/img/' . ($hasSettings ? 'edit' : 'plus') . '.svg">';
		$h .= '</a>';
		if ($hasSettings) {
			$h .= '<a class="del" href="' . mfEsc($deleteUrl) . '" onclick="return confirm(' . $confirm . ')" title="' . mfEsc(i18n_r('multiField/DELETE')) . '">';
			$h .= '<img style="width:18px;filter:invert(100%)" src="' . mfEsc($ctx->siteurl) . 'plugins/multiField/img/trash.svg">';
			$h .= '</a>';
		}
		$h .= '</div>';
		$h .= '</div>';

		/* fields of this node, each with its own resolved position */
		if ($hasSettings) {
			if ($fields) {
				$h .= '<ul class="mf-fields">';
				foreach ($fields as $f) {
					list($fp, $fs) = mfResolvePosition($f['position'], $id);
					$h .= '<li>';
					$h .= '<span class="mf-label">' . mfEsc(mfDisplayLabel($f['label'], $f['type'])) . '</span> ';
					$h .= '<span class="mf-type"><sup>(' . mfEsc(mfTypeLabel($f['type'])) . ')</sup></span> ';
					$h .= mfListBadge($fp, $fs, 'field', $id);
					$h .= '</li>';
				}
				$h .= '</ul>';
			} else {
				$h .= '<div class="mf-none">' . mfEsc(i18n_r('multiField/NOFIELDS')) . '</div>';
			}
		}

		/* child pages */
		$kids = array();
		if ($isRoot) {
			$kids = $ctx->roots;
		} elseif (isset($ctx->children[$id])) {
			$kids = $ctx->children[$id];
		}
		$visibleKids = array();
		foreach ($kids as $kid) {
			if (!empty($ctx->visible[$kid]) && empty($ctx->rendered[$kid])) {
				$visibleKids[] = $kid;
			}
		}
		if ($visibleKids) {
			$h .= '<ul class="mf-children">';
			foreach ($visibleKids as $kid) {
				$h .= mfListNode($kid, $ctx->pages[$kid]['title'], $ctx, false);
			}
			$h .= '</ul>';
		}

		$h .= '</li>';
		return $h;
	}
}

/* ---------------------------------------------------------------------
 * Gather data
 * ------------------------------------------------------------------- */
$settingsIds = array();
foreach ((glob(mfDir() . 'settings-*.json') ?: array()) as $file) {
	$sid = substr(basename($file, '.json'), strlen('settings-'));
	if ($sid !== '') {
		$settingsIds[$sid] = true;
	}
}

$pages = array();
foreach ((glob(GSDATAPAGESPATH . '*.xml') ?: array()) as $file) {
	$pid = pathinfo($file, PATHINFO_FILENAME);
	$xml = @simplexml_load_file($file);
	$pages[$pid] = array(
		'title'  => ($xml && isset($xml->title) && (string)$xml->title !== '') ? (string)$xml->title : $pid,
		'parent' => $xml ? trim((string)$xml->parent) : '',
	);
}

$children = array();
$roots    = array();
foreach ($pages as $pid => $p) {
	if ($p['parent'] !== '' && isset($pages[$p['parent']]) && $p['parent'] !== $pid) {
		$children[$p['parent']][] = $pid;
	} else {
		$roots[] = $pid;
	}
}

$visible = array();
$seen    = array();
foreach ($roots as $rid) {
	mfComputeVisible($rid, $children, $settingsIds, $visible, $seen);
}

$ctx              = new stdClass();
$ctx->base        = $baseUrl;
$ctx->siteurl     = $SITEURL;
$ctx->pages       = $pages;
$ctx->children    = $children;
$ctx->roots       = $roots;
$ctx->visible     = $visible;
$ctx->settingsIds = $settingsIds;
$ctx->rendered    = array();
?>
<h3><?php echo i18n_r('multiField/MULTIFIELDSLIST'); ?></h3>

<style>
	.btn {
		background: #146C94;
		color: #fff !important;
		text-decoration: none !important;
		margin: 3px;
		padding: 10px;
		border-radius: 5px;
		display: inline-flex;
		align-items: center;
	}

	.mf-help {
		margin: 10px 0;
		padding: 8px 10px;
		background: #fafafa;
		border: solid 1px #ddd;
		border-radius: 5px;
		font-size: 12px;
		color: #555;
	}

	.mf-tree,
	.mf-tree ul {
		list-style: none;
		margin: 0 !important;
		padding: 0 !important;
	}

	.mf-tree ul.mf-children {
		margin-left: 14px !important;
		padding-left: 14px !important;
		border-left: 2px solid #d5dde2;
	}

	.mf-node {
		position: relative;
		margin: 6px 0;
	}

	ul.mf-children>.mf-node::before {
		content: "";
		position: absolute;
		left: -14px;
		top: 18px;
		width: 12px;
		border-top: 2px solid #d5dde2;
	}

	.mf-row {
		display: flex;
		justify-content: space-between;
		align-items: center;
		gap: 10px;
		padding: 8px 10px;
		background: #fff;
		border: solid 1px #ddd;
		border-radius: 5px;
	}

	.mf-root>.mf-row {
		background: #eef6fa;
		border-color: #b9d8e6;
	}

	.mf-node.is-empty>.mf-row {
		background: #fafafa;
		color: #888;
		border-style: dashed;
	}

	.mf-title code {
		font-size: 11px;
		color: #777;
		background: #f1f1f1;
		padding: 1px 4px;
		border-radius: 3px;
	}

	.mf-row .btns {
		white-space: nowrap;
	}

	.mf-row .btns a {
		margin-left: 5px;
		background: #146C94;
		padding: 5px;
		display: inline-block;
		border-radius: 5px;
		text-decoration: none !important;
	}

	.mf-row .btns a.del {
		background: #D21312;
	}

	.mf-row .btns a img {
		padding: 0;
		margin: 0;
		display: block;
	}

	ul.mf-fields {
		margin: 4px 0 4px 18px !important;
		padding: 0 !important;
	}

	ul.mf-fields li {
		font-size: 12px;
		padding: 3px 0;
		border-bottom: dotted 1px #e5e5e5;
	}

	.mf-type {
		display: inline-block;
		min-width: 62px;
		font-size: 10px;
		text-transform: uppercase;
		color: #777;
	}

	.mf-none {
		margin: 4px 0 4px 18px;
		font-size: 12px;
		color: #999;
		font-style: italic;
	}

	.mf-badge {
		display: inline-block;
		font-size: 11px;
		padding: 1px 6px;
		border-radius: 10px;
		border: solid 1px #ccc;
		background: #fff;
		color: #555;
		white-space: nowrap;
	}

	.mf-badge em {
		font-style: normal;
		opacity: .75;
		margin-left: 2px;
	}

	.mf-badge.is-explicit {
		background: #146C94;
		border-color: #146C94;
		color: #fff;
		font-weight: bold;
	}

	.mf-missing h4 {
		margin: 20px 0 5px;
	}
</style>

<a href="<?php echo $baseUrl . '&creator'; ?>" class="btn"><?php echo i18n_r('multiField/ADDNEW'); ?> <img style="width:20px;filter:invert(100%);margin-left:5px;" src="<?php echo $SITEURL . 'plugins/multiField/img/plus.svg'; ?>"></a>
<a href="<?php echo $baseUrl . '&migrate'; ?>" class="btn"><?php echo i18n_r('multiField/MIGRATE'); ?> <img style="width:18px;filter:invert(100%);margin-left:5px;" src="<?php echo $SITEURL . 'plugins/multiField/img/web.svg'; ?>"></a>
<hr>

<div class="mf-help">
	<?php echo mfEsc(i18n_r('multiField/HIERARCHY_HELP')); ?>
</div>

<ul class="mf-tree">
	<?php echo mfListNode(MF_ALL, i18n_r('multiField/ALLPAGES'), $ctx, true); ?>
</ul>

<?php
/* Settings whose page is gone (or that sit in an unreachable parent loop) */
$leftover = array();
foreach ($settingsIds as $sid => $_) {
	if ($sid !== MF_ALL && empty($ctx->rendered[$sid])) {
		$leftover[] = $sid;
	}
}
if ($leftover) {
	echo '<div class="mf-missing"><h4>' . mfEsc(i18n_r('multiField/MISSINGPAGES')) . '</h4><ul class="mf-tree">';
	foreach ($leftover as $sid) {
		$t = isset($pages[$sid]) ? $pages[$sid]['title'] : i18n_r('multiField/PAGEMISSING');
		echo mfListNode($sid, $t, $ctx, false);
	}
	echo '</ul></div>';
}