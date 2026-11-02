<?php
require_once GSPLUGINPATH . 'multiField/view/helpers.inc.php';

global $SITEURL;
global $GSADMIN;

$url = $SITEURL . $GSADMIN . '/load.php?id=multiField';

/* ---------------------------------------------------------------------
 * SAVE (runs before any output)
 *
 *  - settings-<id>.json : the field list, each field with its OWN position
 *                         ('' = inherit, 'up', 'bottom')
 *  - position-<id>.txt  : the page-level default. '' (inherit) removes the
 *                         file instead of writing a hard-coded 'up'.
 *
 * Saving never rewrites field positions when the page default changes -
 * children inherit at read time.
 * ------------------------------------------------------------------- */
if (isset($_POST['submit'])) {
	$targetId = mfSanitizeId(isset($_POST['filename']) ? $_POST['filename'] : '');

	if ($targetId !== '') {
		$labels    = isset($_POST['multi-field-label']) && is_array($_POST['multi-field-label']) ? $_POST['multi-field-label'] : array();
		$types     = isset($_POST['multi-field-type']) && is_array($_POST['multi-field-type']) ? $_POST['multi-field-type'] : array();
		$fieldPos  = isset($_POST['multi-field-position']) && is_array($_POST['multi-field-position']) ? $_POST['multi-field-position'] : array();
		$allowed   = mfFieldTypes();

		$ars = array();
		foreach ($labels as $key => $label) {
			$label = (string)$label;
			if ($label === '') {
				continue;
			}
			$type = isset($types[$key]) ? (string)$types[$key] : 'text';
			if (!in_array($type, $allowed, true)) {
				$type = 'text';
			}
			$ars[$label] = array(
				'label'    => $label,
				'value'    => '',
				'type'     => $type,
				'position' => mfNormalizePosition(isset($fieldPos[$key]) ? $fieldPos[$key] : ''),
			);
		}

		mfEnsureDir();
		file_put_contents(mfDir() . 'settings-' . $targetId . '.json', json_encode((object)$ars));
		mfWritePosition($targetId, isset($_POST['position']) ? $_POST['position'] : '');

		echo "<script> window.location.href = '" . $url . "&creator=" . rawurlencode($targetId) . "'</script>";
		return;
	}
}

/* ---------------------------------------------------------------------
 * Data for the form
 * ------------------------------------------------------------------- */
$creator = mfSanitizeId(isset($_GET['creator']) ? $_GET['creator'] : '');
$startId = $creator !== '' ? $creator : MF_ALL;

// Explicit page/All defaults (so the form can show what "inherit" resolves to).
$positions = array();
foreach ((glob(mfDir() . 'position-*.txt') ?: array()) as $file) {
	$pid = substr(basename($file, '.txt'), strlen('position-'));
	$p   = mfReadPosition($pid);
	if ($pid !== '' && $p !== '') {
		$positions[$pid] = $p;
	}
}

$inputList = $creator !== '' ? mfReadSettings($creator) : array();

$jsFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
$h = function ($s) {
	return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
};

$types = array();
foreach (mfFieldTypes() as $t) {
	$types[] = array($t, mfTypeLabel($t));
}

$img = $SITEURL . 'plugins/multiField/img/';

$cfg = array(
	'allId'     => MF_ALL,
	'filename'  => $startId,
	'position'  => isset($positions[$startId]) ? $positions[$startId] : '',
	'positions' => (object)$positions,   // explicit page/All defaults only
	'fields'    => array_values($inputList),
	'types'     => $types,
	'confirm'   => i18n_r('multiField/REMOVEQUESTION'),
	'i18n'      => array(
		'up'            => i18n_r('multiField/UP'),
		'down'          => i18n_r('multiField/DOWN'),
		'inherit'       => i18n_r('multiField/INHERIT'),
		'inheritAll'    => i18n_r('multiField/INHERIT_ALL'),
		'def'           => i18n_r('multiField/DEFAULT'),
		'fieldPosition' => i18n_r('multiField/FIELDPOSITION'),
	),
	'html'      => array(
		'remove'   => '<img style="width:17px;filter:invert(100%);" src="' . $img . 'trash.svg">',
		'dragTip'  => i18n_r('multiField/DRAGTIP'),
	),
);
?>
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
		position: relative;
		padding: 10px 10px 10px 38px;
		margin: 5px 0;
		background: #fafafa;
		border: solid 1px #ddd;
		align-items: center;
		border-radius: 5px;
	}

	.form-input label {
		grid-column: 1/2
	}

	.form-input .shortcode {
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

	/* per-field position row */
	.form-input .mf-pos-row {
		grid-column: 1/4;
		display: flex;
		align-items: center;
		gap: 10px;
		font-size: 12px;
		color: #555;
	}

	.form-input .mf-pos-row select {
		grid-column: auto;
		flex: 0 0 auto;
		min-width: 120px;
		padding: 6px;
	}

	.form-input .mf-pos-row .mf-hint {
		font-size: 11px;
		color: #888;
	}

	.form-input .mf-pos-row.is-explicit select {
		border-color: #146C94;
		box-shadow: 0 0 0 1px #146C94;
	}

	.form-input .mf-pos-row.is-explicit>span:first-child {
		color: #146C94;
		font-weight: bold;
	}

	.form .submit {
		width: 200px;
		border: none;
		border-radius: 0 !important;
		font-weight: inherit !important;
		background: green !important;
		color: #fff !important;
		border: none !important;
		border-radius: 5px !important;
		text-shadow: none !important;
		cursor: pointer;
	}

	/* drag handle */
	.form-input .mf-handle {
		position: absolute;
		left: 0;
		top: 0;
		bottom: 0;
		width: 30px;
		display: flex;
		align-items: center;
		justify-content: center;
		cursor: grab;
		color: #999;
		font-size: 16px;
		letter-spacing: -2px;
		user-select: none;
		touch-action: none;
		border-radius: 5px 0 0 5px;
	}

	.form-input .mf-handle:hover {
		background: #eef6fa;
		color: #146C94;
	}

	.form-input.is-dragging {
		opacity: .85;
		background: #eef6fa;
		border-color: #146C94;
		box-shadow: 0 4px 14px rgba(0, 0, 0, .18);
		position: relative;
		z-index: 5;
	}

	.form-input.is-dragging .mf-handle {
		cursor: grabbing;
	}

	.form-input button {
		background: #D21312;
		color: #fff;
		grid-column: 3/4;
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
		display: flex;
		align-items: center;
	}

	.mf-help {
		margin: 0 0 10px;
		font-size: 12px;
		color: #666;
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

<div class="title-bar">
	<h3><?php echo i18n_r('multiField/MULTIFIELDCREATE'); ?></h3>
	<a href="<?php echo $url; ?>"><?php echo i18n_r('multiField/BACKTOLIST'); ?> <img src="<?php echo $img . 'back.svg'; ?>"></a>
</div>

<form class="form" method="post" id="mf-form">

	<h4><i><?php echo $h(i18n_r('multiField/SELECTPAGE')); ?></i></h4>

	<select name="filename" id="mf-filename" style="padding:10px;background:#fafafa;border:solid 1px #ddd;width:98%;margin:10px 0;border-radius:5px;">
		<option value="<?php echo MF_ALL; ?>"><?php echo $h(i18n_r('multiField/ALL')); ?></option>
		<?php foreach ((glob(GSDATAPAGESPATH . '*.xml') ?: array()) as $file) {
			$xmlFile  = @simplexml_load_file($file);
			$filePure = pathinfo($file, PATHINFO_FILENAME);
			$pTitle   = ($xmlFile && isset($xmlFile->title)) ? (string)$xmlFile->title : $filePure;
			echo '<option value="' . $h($filePure) . '">' . $h($pTitle) . '</option>';
		} ?>
	</select>

	<h4><i><?php echo $h(i18n_r('multiField/POSITION')); ?></i></h4>
	<p class="mf-help"><?php echo $h(i18n_r('multiField/POSITION_HELP')); ?></p>
	<select name="position" id="mf-position" style="padding:10px;background:#fafafa;border:solid 1px #ddd;width:98%;margin:10px 0;border-radius:5px;">
		<option value=""></option><!-- text filled in by JS: Default (...) / Inherit from All (...) -->
		<option value="up"><?php echo $h(i18n_r('multiField/UP')); ?></option>
		<option value="bottom"><?php echo $h(i18n_r('multiField/DOWN')); ?></option>
	</select>

	<h4><i><?php echo $h(i18n_r('multiField/ADDNEWINPUT')); ?></i></h4>

	<div class="add-input">
		<input type="text" id="mf-title" value="<?php echo $h(i18n_r('multiField/TITLE')); ?>">
		<br>
		<select id="mf-type" style="width:100%;padding:10px;margin:5px;">
			<?php foreach ($types as $t) {
				echo '<option value="' . $h($t[0]) . '">' . $h($t[1]) . '</option>';
			} ?>
		</select>
		<br>
		<button type="button" id="mf-add"><?php echo $h(i18n_r('multiField/ADDNEWINPUT')); ?> <img style="margin-left:10px;width:20px;filter:invert(100%);" src="<?php echo $img . 'plus.svg'; ?>"></button>
	</div>

	<br>

	<h4><i><?php echo $h(i18n_r('multiField/MULTIFIELDSLIST')); ?></i></h4>
	<hr>

	<div style="margin:10px 0;" id="sortable"></div>

	<input type="submit" value="<?php echo $h(i18n_r('multiField/SAVE')); ?>" class="submit" name="submit">

</form>

<script>
	(function() {
		var C = <?php echo json_encode($cfg, $jsFlags); ?>;

		var list = document.getElementById('sortable');
		var fn = document.getElementById('mf-filename');
		var pagePos = document.getElementById('mf-position');
		var titleIn = document.getElementById('mf-title');
		var typeSel = document.getElementById('mf-type');
		var counter = 0;

		function el(tag, props, css) {
			var e = document.createElement(tag);
			if (props) {
				for (var k in props) {
					e[k] = props[k];
				}
			}
			if (css) {
				e.style.cssText = css;
			}
			return e;
		}

		function posName(p) {
			return p === 'bottom' ? C.i18n.down : C.i18n.up;
		}

		// What "inherit" resolves to at page level: All's default, or 'up' for All itself.
		function pageInherited() {
			return fn.value === C.allId ? 'up' : (C.positions[C.allId] || 'up');
		}

		// What a field that inherits will end up with right now (uses the unsaved page choice).
		function pageDefault() {
			return pagePos.value || pageInherited();
		}

		function refreshPageOption() {
			var label = fn.value === C.allId ? C.i18n.def : C.i18n.inheritAll;
			pagePos.options[0].textContent = label + ' (' + posName(pageInherited()) + ')';
		}

		// The per-field selects themselves are never touched here - only the grey hint
		// next to them. A field's stored value changes only when you change ITS select.
		function refreshHints() {
			var rows = list.querySelectorAll('.mf-pos-row');
			for (var i = 0; i < rows.length; i++) {
				var sel = rows[i].querySelector('.mf-pos');
				var hint = rows[i].querySelector('.mf-hint');
				hint.textContent = sel.value ? '' : '\u2192 ' + posName(pageDefault());
				rows[i].classList.toggle('is-explicit', !!sel.value);
			}
		}

		function makeSortable(handle, row) {
			handle.addEventListener('pointerdown', function(e) {
				if (e.pointerType === 'mouse' && e.button !== 0) {
					return;
				}
				e.preventDefault();
				handle.setPointerCapture(e.pointerId);
				row.classList.add('is-dragging');

				function move(ev) {
					// edge auto-scroll
					if (ev.clientY < 60) {
						window.scrollBy(0, -12);
					} else if (ev.clientY > window.innerHeight - 60) {
						window.scrollBy(0, 12);
					}

					var others = Array.prototype.filter.call(list.children, function(r) {
						return r !== row;
					});
					for (var i = 0; i < others.length; i++) {
						var rect = others[i].getBoundingClientRect();
						if (ev.clientY < rect.top + rect.height / 2) {
							if (row.nextElementSibling !== others[i]) {
								list.insertBefore(row, others[i]);
							}
							return;
						}
					}
					if (list.lastElementChild !== row) {
						list.appendChild(row);
					}
				}

				function end() {
					row.classList.remove('is-dragging');
					handle.removeEventListener('pointermove', move);
					handle.removeEventListener('pointerup', end);
					handle.removeEventListener('pointercancel', end);
				}

				handle.addEventListener('pointermove', move);
				handle.addEventListener('pointerup', end);
				handle.addEventListener('pointercancel', end);
			});
		}

		function addRow(f) {
			var row = el('div', {
				className: 'form-input'
			});
			var handle = el('div', {
				className: 'mf-handle',
				textContent: '\u22EE\u22EE',
				title: C.html.dragTip
			});
			var lab = el('label');
			var code = el('span', {
				className: 'shortcode tpl'
			});
			var txt = el('input', {
				type: 'text',
				required: true
			}, 'width:100%;');
			var hid = el('input', {
				type: 'hidden',
				name: 'multi-field-label[]'
			});

			var type = el('select', {
				name: 'multi-field-type[]'
			});
			C.types.forEach(function(t) {
				var o = el('option', {
					value: t[0],
					textContent: t[1]
				});
				if (t[0] === f.type) {
					o.selected = true;
				}
				type.appendChild(o);
			});

			var del = el('button', {
				type: 'button'
			});
			del.innerHTML = C.html.remove;

			var pr = el('div', {
				className: 'mf-pos-row'
			});
			var pl = el('span', {
				textContent: C.i18n.fieldPosition
			});
			var ps = el('select', {
				name: 'multi-field-position[]',
				className: 'mf-pos'
			});
			[
				['', C.i18n.inherit],
				['up', C.i18n.up],
				['bottom', C.i18n.down]
			].forEach(function(o) {
				ps.appendChild(el('option', {
					value: o[0],
					textContent: o[1]
				}));
			});
			ps.value = f.position || '';
			var hint = el('span', {
				className: 'mf-hint'
			});
			pr.appendChild(pl);
			pr.appendChild(ps);
			pr.appendChild(hint);

			function sync() {
				var v = txt.value;
				lab.textContent = v;
				hid.value = v.replace(/ /g, '-');
				// split so PHP never sees an opening tag inside this script
				code.textContent = '<' + '?php multiFields(\'' + hid.value + '\') ;?' + '>';
			}

			txt.addEventListener('input', sync);
			ps.addEventListener('change', refreshHints);
			del.addEventListener('click', function(e) {
				e.preventDefault();
				if (confirm(C.confirm)) {
					row.parentNode.removeChild(row);
				}
			});

			row.appendChild(handle);
			row.appendChild(lab);
			row.appendChild(code);
			row.appendChild(txt);
			row.appendChild(hid);
			row.appendChild(type);
			row.appendChild(del);
			row.appendChild(pr);
			list.appendChild(row);
			makeSortable(handle, row);

			txt.value = String(f.label).replace(/-/g, ' ');
			sync();
		}

		document.getElementById('mf-add').addEventListener('click', function(e) {
			e.preventDefault();
			if (!titleIn.value.replace(/\s/g, '')) {
				return;
			}
			addRow({
				label: titleIn.value,
				type: typeSel.value,
				position: ''
			});
			titleIn.value = 'title' + counter++;
			refreshHints();
		});

		// Switching target page: show that page's stored default, not the previous page's.
		fn.addEventListener('change', function() {
			pagePos.value = C.positions[fn.value] || '';
			refreshPageOption();
			refreshHints();
		});
		pagePos.addEventListener('change', refreshHints);

		fn.value = C.filename;
		pagePos.value = C.position;
		C.fields.forEach(addRow);
		refreshPageOption();
		refreshHints();
	})();
</script>
