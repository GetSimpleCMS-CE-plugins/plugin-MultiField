<?php

	include('../../../gsconfig.php');
	$admin = defined('GSADMIN') ? GSADMIN : 'admin';
	include("../../../".$admin."/inc/common.php");
	$loggedin = cookie_check();
	if (!$loggedin) die("Not logged in!");
	if (!defined('IN_GS')) {
		die('you cannot load this page directly.');
	}

	i18n_merge('i18n_gallery', substr($LANG, 0, 2));
	i18n_merge('i18n_gallery', 'en');

	if (isset($_GET['path'])) {
		$subPath = preg_replace('/\.+\//', '', $_GET['path']);
		if ($subPath) $subPath .= '/';
		$path = "../../../data/uploads/" . $subPath;
	} else {
		$subPath = "";
		$path = "../../../data/uploads/";
	}
	$path = tsl($path);

	$func = preg_replace('/[^\w]/', '', @$_GET['func'] ?? '');
	$w = (int) @$_GET['w'];
	$h = (int) @$_GET['h'];
	if (!$w && !$h) {
		$w = 160;
		$h = 120;
	}
	$autoclose = (string) (@$_GET['autoclose'] ?? '');

	// Which multiField input should receive the chosen image (its data-name, i.e. the field label).
	$fieldKey = isset($_GET['count']) ? (string) $_GET['count'] : '';

	// Query string shared by every navigation link, so "count" survives folder changes.
	$baseQuery = 'func=' . rawurlencode($func)
		. '&w=' . $w
		. '&h=' . $h
		. '&autoclose=' . rawurlencode($autoclose)
		. '&count=' . rawurlencode($fieldKey);
	$e = function ($s) {
		return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
	};

	global $LANG;
	$LANG_header = preg_replace('/(?:(?<=([a-z]{2}))).*/', '', $LANG);
	$totalsize = 0;
	$filesArray = [];
	$dirsArray = [];

	clearstatcache();
	$dir_handle = opendir($path) or die("Unable to open $path");
	while ($file = readdir($dir_handle)) {
		if ($file == "." || $file == ".." || $file == ".htaccess") {
			// not an upload file
		} elseif (is_dir($path . $file)) {
			$dirsArray[] = ['name' => $file];
		} else {
			$ext = @strtolower(substr($file, strrpos($file, '.') + 1));
			if ($ext == 'jpg' || $ext == 'jpeg' || $ext == 'gif' || $ext == 'png' || $ext == 'webp') {
				$bytes = (int) @filesize($path . $file);
				$dim = @getimagesize($path . $file);
				$filesArray[] = [
					'name'   => $file,
					'size'   => fSize($bytes),
					'bytes'  => $bytes,
					'width'  => $dim ? $dim[0] : 0,
					'height' => $dim ? $dim[1] : 0,
				];
				$totalsize += $bytes;
			}
		}
	}
	closedir($dir_handle);
	$filesSorted = (array) subval_sort($filesArray, 'name');
	$dirsSorted = (array) subval_sort($dirsArray, 'name');

	$pathParts = explode("/", $subPath);
	$urlPath = "";

?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_header; ?>">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<title><?php echo i18n_r('FILE_BROWSER'); ?></title>
		<link rel="shortcut icon" href="../../../<?php echo $admin; ?>/favicon.png" type="image/x-icon" />
		<style>
			:root {
				--bg: #f6f7f9;
				--panel: #ffffff;
				--text: #1f2933;
				--muted: #7b8794;
				--border: #e1e5ea;
				--accent: #146C94;
				--accent-soft: #e6f2f8;
				--shadow: 0 1px 2px rgba(0, 0, 0, .06), 0 4px 12px rgba(0, 0, 0, .06);
				--check-a: #eceff3;
				--check-b: #ffffff;
			}

			@media (prefers-color-scheme: dark) {
				:root {
					--bg: #15181c;
					--panel: #1e2328;
					--text: #e6e9ec;
					--muted: #8b96a1;
					--border: #2c333a;
					--accent: #4fb3d9;
					--accent-soft: #1f3340;
					--shadow: 0 1px 2px rgba(0, 0, 0, .4), 0 4px 12px rgba(0, 0, 0, .35);
					--check-a: #262c33;
					--check-b: #1e2328;
				}
			}

			* {
				box-sizing: border-box;
			}

			html,
			body {
				margin: 0;
				background: var(--bg);
				color: var(--text);
				font: 14px/1.4 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
			}

			header {
				position: sticky;
				top: 0;
				z-index: 2;
				background: var(--bg);
				padding: 14px 16px 10px;
				border-bottom: 1px solid var(--border);
			}

			h1 {
				margin: 0 0 8px;
				font-size: 16px;
				font-weight: 600;
			}

			h1 small {
				font-weight: 400;
				color: var(--muted);
				margin-left: 6px;
			}

			.bar {
				display: flex;
				gap: 12px;
				align-items: center;
				flex-wrap: wrap;
			}

			.crumbs {
				flex: 1 1 auto;
				min-width: 0;
				color: var(--muted);
				white-space: nowrap;
				overflow: hidden;
				text-overflow: ellipsis;
			}

			.crumbs a {
				color: var(--accent);
				text-decoration: none;
			}

			.crumbs a:hover {
				text-decoration: underline;
			}

			#filter {
				flex: 0 1 200px;
				padding: 7px 10px;
				border: 1px solid var(--border);
				border-radius: 8px;
				background: var(--panel);
				color: var(--text);
				font: inherit;
			}

			#filter:focus {
				outline: 2px solid var(--accent);
				outline-offset: -1px;
			}

			main {
				padding: 16px;
			}

			.grid {
				display: grid;
				grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
				gap: 12px;
			}

			.card {
				display: flex;
				flex-direction: column;
				padding: 0;
				margin: 0;
				text-align: left;
				font: inherit;
				color: inherit;
				background: var(--panel);
				border: 1px solid var(--border);
				border-radius: 10px;
				overflow: hidden;
				cursor: pointer;
				text-decoration: none;
				transition: transform .12s ease, box-shadow .12s ease, border-color .12s ease;
			}

			.card:hover,
			.card:focus-visible {
				transform: translateY(-2px);
				box-shadow: var(--shadow);
				border-color: var(--accent);
				outline: none;
			}

			.thumb {
				display: block;
				aspect-ratio: 4 / 3;
				background: repeating-conic-gradient(var(--check-a) 0% 25%, var(--check-b) 0% 50%) 50% / 16px 16px;
			}

			.thumb img {
				display: block;
				width: 100%;
				height: 100%;
				object-fit: cover;
			}

			.meta {
				display: block;
				padding: 8px 10px 9px;
				min-width: 0;
			}

			.name {
				display: block;
				font-weight: 500;
				white-space: nowrap;
				overflow: hidden;
				text-overflow: ellipsis;
			}

			.info {
				display: block;
				margin-top: 1px;
				font-size: 12px;
				color: var(--muted);
				white-space: nowrap;
				overflow: hidden;
				text-overflow: ellipsis;
			}

			.card.folder {
				flex-direction: row;
				align-items: center;
				gap: 10px;
				padding: 12px;
				background: var(--accent-soft);
				border-color: transparent;
			}

			.card.folder svg {
				flex: 0 0 auto;
				width: 28px;
				height: 28px;
				fill: var(--accent);
			}

			.card.folder .meta {
				padding: 0;
			}

			.folders {
				margin-bottom: 14px;
			}

			.empty {
				padding: 40px 0;
				text-align: center;
				color: var(--muted);
			}

			[hidden] {
				display: none !important;
			}
		</style>
	</head>

	<body id="imagebrowser">
		<header>
			<h1><?php i18n('UPLOADED_FILES'); ?>
				<small><span id="shown"><?php echo count($filesSorted); ?></span> / <?php echo count($filesSorted); ?> <?php i18n('TOTAL_FILES'); ?> (<?php echo fSize($totalsize); ?>)</small>
			</h1>
			<div class="bar">
				<div class="crumbs">/ <a href="?<?php echo $e($baseQuery); ?>">uploads</a> /
					<?php
					foreach ($pathParts as $pathPart) {
						if ($pathPart != '') {
							$urlPath .= $pathPart;
							?>
							<a href="?path=<?php echo $e(rawurlencode($urlPath)); ?>&amp;<?php echo $e($baseQuery); ?>"><?php echo $e($pathPart); ?></a> /
							<?php
							$urlPath .= '/';
						}
					}
					?>
				</div>
				<input type="search" id="filter" placeholder="Filter…" autocomplete="off">
			</div>
		</header>

		<main>
			<?php if (count($dirsSorted)) { ?>
			<div class="grid folders">
				<?php foreach ($dirsSorted as $upload) {
					$p = $subPath . $upload['name']; ?>
				<a class="card folder" data-name="<?php echo $e(strtolower($upload['name'])); ?>" href="imagebrowser.php?path=<?php echo $e(rawurlencode($p)); ?>&amp;<?php echo $e($baseQuery); ?>" title="<?php echo $e($upload['name']); ?>">
					<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-8l-2-2z"/></svg>
					<span class="meta"><span class="name"><?php echo $e($upload['name']); ?></span></span>
				</a>
				<?php } ?>
			</div>
			<?php } ?>

			<div class="grid" id="images">
				<?php foreach ($filesSorted as $upload) {
					$url = $SITEURL . 'data/uploads/' . $subPath . $upload['name'];
					$dims = $upload['width'] ? $upload['width'] . ' × ' . $upload['height'] . ' · ' : '';
				?>
				<button type="button" class="card" data-url="<?php echo $e($url); ?>" data-name="<?php echo $e(strtolower($upload['name'])); ?>" title="<?php echo $e(i18n_r('SELECT_FILE') . ': ' . $upload['name']); ?>">
					<span class="thumb"><img loading="lazy" decoding="async" alt="" src="<?php echo $e($url); ?>"></span>
					<span class="meta">
						<span class="name"><?php echo $e($upload['name']); ?></span>
						<span class="info"><?php echo $e($dims . $upload['size']); ?></span>
					</span>
				</button>
				<?php } ?>
			</div>

			<div class="empty" id="empty" <?php echo (count($filesSorted) || count($dirsSorted)) ? 'hidden' : ''; ?>>—</div>
		</main>

		<script>
			var targetKey = <?php echo json_encode($fieldKey, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

			function pick(linker) {
				if (!window.opener) {
					window.close();
					return;
				}

				// Match on data-name with a plain comparison, so labels with
				// quotes or brackets can't break a CSS selector.
				var inputs = window.opener.document.querySelectorAll('[name="multifield[]"]');
				for (var i = 0; i < inputs.length; i++) {
					if (inputs[i].getAttribute('data-name') === targetKey) {
						inputs[i].value = linker;
						// programmatic changes fire no event; the editor's preview listens for "input"
						inputs[i].dispatchEvent(new window.opener.Event('input', { bubbles: true }));
						break;
					}
				}
				window.close();
			}

			document.getElementById('images').addEventListener('click', function(ev) {
				var card = ev.target.closest('.card[data-url]');
				if (card) {
					pick(card.getAttribute('data-url'));
				}
			});

			document.addEventListener('keydown', function(ev) {
				if (ev.key === 'Escape') {
					window.close();
				}
			});

			// Live filter by file / folder name
			var filter = document.getElementById('filter');
			var cards = document.querySelectorAll('.card[data-name]');
			var shown = document.getElementById('shown');
			var empty = document.getElementById('empty');

			filter.addEventListener('input', function() {
				var q = filter.value.trim().toLowerCase();
				var visibleImages = 0;
				var visibleAny = 0;
				for (var i = 0; i < cards.length; i++) {
					var match = !q || cards[i].getAttribute('data-name').indexOf(q) !== -1;
					cards[i].hidden = !match;
					if (match) {
						visibleAny++;
						if (cards[i].hasAttribute('data-url')) {
							visibleImages++;
						}
					}
				}
				shown.textContent = visibleImages;
				empty.hidden = visibleAny > 0;
			});

			filter.focus();
		</script>
	</body>

</html>
