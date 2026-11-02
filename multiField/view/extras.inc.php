<style>
	.multifields {
		margin: 0 !important;
		padding: 0 !important;
	}

	.multifields div {
		margin: 0;
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
	}

	.mf-block {
		margin-bottom: 20px !important;
	}
</style>

<?php
	require_once GSPLUGINPATH . 'multiField/view/helpers.inc.php';

	global $SITEURL;
	global $EDLANG;
	global $EDTOOL;
	global $toolbar;
	global $EDOPTIONS;

	/* ---------------------------------------------------------------
	 * Fields for this page, each with its resolved position:
	 * field -> page default -> All default -> 'up'
	 * Page fields win over global fields with the same label.
	 * ------------------------------------------------------------- */
	$pageId = mfSanitizeId(isset($_GET['id']) ? $_GET['id'] : '');
	$fields = mfFieldsForPage($pageId);

	$stored   = array();
	$dataFile = mfDir() . $pageId . '.json';
	if ($pageId !== '' && is_file($dataFile)) {
		$decoded = json_decode((string)file_get_contents($dataFile), true);
		if (is_array($decoded)) {
			$stored = $decoded;
		}
	}

	$hasLink = false;
	foreach ($fields as $f) {
		if ($f['type'] === 'link') {
			$hasLink = true;
		}
	}

	/* ---------------------------------------------------------------
	 * CKEditor options (unchanged logic)
	 * ------------------------------------------------------------- */
	// Initialize variables to avoid null values
	$EDTOOL = $EDTOOL ?? '';
	$toolbar = $toolbar ?? '';
	$EDOPTIONS = $EDOPTIONS ?? '';

	if (isset($EDTOOL) && !empty($EDTOOL)) $EDTOOL = returnJsArray($EDTOOL);
	if (isset($toolbar) && !empty($toolbar)) $toolbar = returnJsArray($toolbar); // handle plugins that corrupt this

	else if (strpos(trim($EDTOOL ?? ''), '[[') !== 0 && strpos(trim($EDTOOL ?? ''), '[') === 0) {
		$EDTOOL = "[$EDTOOL]";
	}

	if (isset($toolbar) && strpos(trim($toolbar ?? ''), '[[') !== 0 && strpos(trim($toolbar ?? ''), '[') === 0) {
		$toolbar = '[$toolbar]';
	}

	$toolbar = isset($EDTOOL) && !empty(trim($EDTOOL ?? '')) ? ",toolbar: " . trim($EDTOOL, ",") : '';
	$options = isset($EDOPTIONS) && !empty(trim($EDOPTIONS ?? '')) ? ',' . trim($EDOPTIONS, ",") : '';

	/* ---------------------------------------------------------------
	 * Page list for "link" fields - built once, only when needed
	 * ------------------------------------------------------------- */
	$pagesList = array();
	if ($hasLink) {
		foreach ((glob(GSDATAPAGESPATH . '*.xml') ?: array()) as $file) {
			$xmlFile = @simplexml_load_file($file);
			if (!$xmlFile) {
				continue;
			}
			$slug = pathinfo($file, PATHINFO_FILENAME);

			$fullSlug = $slug;
			$parent   = (string)$xmlFile->parent;

			if (!empty($parent)) {
				$parentPath    = array();
				$currentParent = $parent;
				$guard         = 0;
				while (!empty($currentParent) && $guard++ < 20) {
					array_unshift($parentPath, $currentParent);

					$parentFile = GSDATAPAGESPATH . $currentParent . '.xml';
					if (file_exists($parentFile)) {
						$parentXml     = @simplexml_load_file($parentFile);
						$currentParent = $parentXml ? (string)$parentXml->parent : '';
					} else {
						break;
					}
				}
				if (!empty($parentPath)) {
					$fullSlug = implode('/', $parentPath) . '/' . $slug;
				}
			}

			$pagesList[] = array(
				'link'    => $SITEURL . $fullSlug,
				'display' => (!empty($parent) ? $parent . ' › ' : '') . (string)$xmlFile->title,
			);
		}

		usort($pagesList, function ($a, $b) {
			return strcasecmp($a['display'], $b['display']);
		});
	}

	$jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
?>

<!-- Two zones: 'up' stays here, 'bottom' is moved next to the editor by the script below. -->
<div class="multifields mf-zone mf-zone-up" id="mf-zone-up"></div>
<div class="multifields mf-zone mf-zone-bottom" id="mf-zone-bottom"></div>

<script>
	(function() {
		var fields = <?php echo json_encode(array_values($fields), $jsonFlags); ?>;
		var dater = <?php echo json_encode((object)$stored, $jsonFlags); ?>;
		var pages = <?php echo json_encode($pagesList, $jsonFlags); ?>;
		var SITEURL = <?php echo json_encode((string)$SITEURL, $jsonFlags | JSON_UNESCAPED_SLASHES); ?>;
		var GETPHOTO = <?php echo json_encode((string)i18n_r('multiField/GETPHOTO'), $jsonFlags); ?>;

		var editors = [];

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

		function val(label) {
			var d = dater[label];
			return (d && d.value !== undefined && d.value !== null) ? String(d.value) : '';
		}

		// Wrapper + the two hidden inputs every field posts (label and type).
		// Order matters: label[], type[] and multifield[] are parallel arrays on the server.
		function block(f, title) {
			var b = el('div', {
				className: 'mf-block'
			});
			b.appendChild(el('label', {
				textContent: title !== undefined ? title : f.label.replace(/-/g, ' ')
			}));
			b.appendChild(el('input', {
				type: 'hidden',
				name: 'label-multifield[]',
				value: f.label
			}));
			b.appendChild(el('input', {
				type: 'hidden',
				name: 'type-multifield[]',
				value: f.type
			}));
			return b;
		}

		var inputCss = 'margin:5px 0;display:block;width:100%;padding:10px;box-sizing:border-box;';
		var selectCss = 'background:#fff;border:solid 1px #ddd;width:100%;padding:10px;border-radius:5px;';

		function build(f) {
			var b;

			switch (f.type) {
				case 'wysywig':
					b = block(f);
					var ta = el('textarea', {
						name: 'multifield[]'
					});
					ta.value = val(f.label);
					b.appendChild(ta);
					editors.push(ta);
					break;

				case 'textarea':
					b = block(f);
					var t2 = el('textarea', {
						name: 'multifield[]'
					}, 'width:100%;padding:10px;box-sizing:border-box;');
					t2.value = val(f.label);
					b.appendChild(t2);
					break;

				case 'dropdown':
					b = block(f, f.label.replace(/-/g, ' ').split('[')[0]);
					var sel = el('select', {
						name: 'multifield[]'
					}, selectCss);
					var a = f.label.indexOf('['),
						z = f.label.indexOf(']');
					var opts = (a > -1 && z > a ? f.label.substring(a + 1, z) : '').split(',');
					opts.forEach(function(o) {
						sel.appendChild(el('option', {
							value: o,
							textContent: o.replace(/-/g, ' '),
							selected: val(f.label) === o
						}));
					});
					b.appendChild(sel);
					break;

				case 'checkbox':
					// An unchecked checkbox posts nothing, which would shift the parallel arrays.
					// So the posted value lives in a hidden input and the checkbox only drives it.
					b = block(f);
					var flag = el('input', {
						type: 'hidden',
						name: 'multifield[]',
						value: val(f.label) === 'on' ? 'on' : ''
					});
					var cb = el('input', {
						type: 'checkbox',
						checked: val(f.label) === 'on'
					}, 'all:revert;');
					cb.addEventListener('change', function() {
						flag.value = cb.checked ? 'on' : '';
					});
					b.appendChild(flag);
					b.appendChild(cb);
					break;

				case 'foto':
					b = block(f);
					var wrap = el('div', null, 'display:flex;gap:10px;align-items:center;');
					var prev = el('div', null, 'width:120px;height:80px;border:1px solid #ddd;border-radius:5px;overflow:hidden;flex-shrink:0;background:#f5f5f5;display:flex;align-items:center;justify-content:center;');
					var img = el('img', {
						alt: 'Preview'
					}, 'max-width:100%;max-height:100%;object-fit:cover;');
					var none = el('span', {
						textContent: 'No image'
					}, 'color:#999;font-size:12px;');
					prev.appendChild(img);
					prev.appendChild(none);

					var row = el('div', null, 'display:flex;gap:2px;flex:1;');
					var inp = el('input', {
						type: 'text',
						className: 'foto',
						name: 'multifield[]',
						value: val(f.label)
					}, 'margin:5px 0;display:inline-block;width:80%;margin-right:10px;');
					inp.setAttribute('data-name', f.label);

					var preview = function() {
						if (inp.value) {
							img.src = inp.value;
							img.style.display = '';
							none.style.display = 'none';
						} else {
							img.removeAttribute('src');
							img.style.display = 'none';
							none.style.display = '';
						}
					};
					inp.addEventListener('input', preview);
					preview();

					var btn = el('button', {
						type: 'button',
						textContent: GETPHOTO
					}, 'background:#FF9900;color:#fff;border:none;padding:3px;cursor:pointer;border-radius:5px;width:20%');
					btn.addEventListener('click', function(e) {
						e.preventDefault();
						window.open(SITEURL + 'plugins/multiField/files/imagebrowser.php?&func=multifield[]&count=' + encodeURIComponent(f.label),
							'myWindow', 'toolbar=no,scrollbars=yes,menubar=no,resizable=yes,width=780,height=620');
					});

					row.appendChild(inp);
					row.appendChild(btn);
					wrap.appendChild(prev);
					wrap.appendChild(row);
					b.appendChild(wrap);
					break;

				case 'link':
					b = block(f);
					var ls = el('select', {
						name: 'multifield[]'
					}, selectCss);
					ls.appendChild(el('option', {
						value: 'javascript:;',
						textContent: ' -- '
					}));
					pages.forEach(function(p) {
						ls.appendChild(el('option', {
							value: p.link,
							textContent: p.display,
							selected: val(f.label) === p.link
						}));
					});
					b.appendChild(ls);
					break;

				default: // text, color, date
					b = block(f);
					b.appendChild(el('input', {
						type: f.type,
						name: 'multifield[]',
						value: val(f.label)
					}, inputCss));
			}
			return b;
		}

		function render() {
			var up = document.getElementById('mf-zone-up');
			var bottom = document.getElementById('mf-zone-bottom');
			var editing = document.querySelector('.editing');

			var hasBottom = fields.some(function(f) {
				return f.effective === 'bottom';
			});
			// Move the zone first, so CKEditor is created where it will stay.
			if (hasBottom && editing && bottom) {
				editing.prepend(bottom);
			}

			fields.forEach(function(f) {
				(f.effective === 'bottom' ? bottom : up).appendChild(build(f));
			});

			editors.forEach(function(ta) {
				if (typeof CKEDITOR === 'undefined') {
					return; // falls back to a plain textarea
				}
				CKEDITOR.replace(ta, {
					skin: 'getsimple',
					forcePasteAsPlainText: true,
					language: <?php echo json_encode((string)$EDLANG, $jsonFlags); ?>,
					defaultLanguage: 'en',
					entities: false,
					// uiColor : '#FFFFFF',
					height: '250px',
					baseHref: SITEURL,
					tabSpaces: 10,
					filebrowserBrowseUrl: 'filebrowser.php?type=all',
					filebrowserImageBrowseUrl: 'filebrowser.php?type=images',
					filebrowserWindowWidth: '730',
					filebrowserWindowHeight: '500'
					<?php echo $toolbar; ?>
					<?php echo str_replace('"', "'", $options); ?>
				});
			});
		}

		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', render);
		} else {
			render();
		}
	})();
</script>
