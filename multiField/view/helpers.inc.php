<?php
/**
 * MultiField - shared helpers (NEW FILE: multiField/view/helpers.inc.php)
 *
 * Position model
 * --------------
 * A field's placement is resolved top-down, first match wins:
 *
 *   1. the field's own "position"            (explicit exception)
 *   2. the page's position-<id>.txt          (page default)
 *   3. position-allmultifield.txt            (global default)
 *   4. 'up'                                  (built-in default)
 *
 * Nothing is ever copied down the tree when you save. A page/global default
 * is only stored once, at its own level, and children inherit it at read time.
 * An empty value ('') always means "inherit".
 *
 * PHP 7.4 compatible.
 */

if (!defined('MF_ALL')) {
	define('MF_ALL', 'allmultifield');
}

/** Translate with a fallback (so other language files without the new keys keep working). */
function mfT($key, $default)
{
	$v = i18n_r('multiField/' . $key);
	if (!is_string($v) || $v === '' || strpos($v, '{') === 0) {
		return $default;
	}
	return $v;
}

function mfDir()
{
	return GSDATAOTHERPATH . 'multiField/';
}

function mfEnsureDir()
{
	$dir = mfDir();
	if (!is_dir($dir)) {
		@mkdir($dir, 0755, true);
	}
	return $dir;
}

/** Strip anything that could escape the multiField data folder. */
function mfSanitizeId($id)
{
	$id = str_replace(array('/', '\\', "\0"), '', (string)$id);
	$id = ltrim($id, '.');
	return trim($id);
}

function mfFieldTypes()
{
	return array('text', 'textarea', 'wysywig', 'color', 'date', 'foto', 'link', 'checkbox', 'dropdown');
}

function mfTypeLabel($type)
{
	$map = array(
		'text'     => array('TEXT', 'Text'),
		'textarea' => array('TEXTAREA', 'Textarea'),
		'wysywig'  => array('WYSYWIG', 'Wysiwyg'),
		'color'    => array('COLOR', 'Color'),
		'date'     => array('DATE', 'Date'),
		'foto'     => array('FOTO', 'Image'),
		'link'     => array('LINK', 'Link'),
		'checkbox' => array(null, 'Checkbox'),
		'dropdown' => array(null, 'Dropdown'),
	);
	if (!isset($map[$type])) {
		return (string)$type;
	}
	return $map[$type][0] ? mfT($map[$type][0], $map[$type][1]) : $map[$type][1];
}

/** Human readable label ("my-field" => "my field", dropdown "x[a,b]" => "x"). */
function mfDisplayLabel($label, $type = '')
{
	$l = (string)$label;
	if ($type === 'dropdown') {
		$p = strpos($l, '[');
		if ($p !== false) {
			$l = substr($l, 0, $p);
		}
	}
	return str_replace('-', ' ', $l);
}

/** '' (inherit) | 'up' | 'bottom' */
function mfNormalizePosition($pos)
{
	$pos = is_string($pos) ? trim($pos) : '';
	return ($pos === 'up' || $pos === 'bottom') ? $pos : '';
}

/** Explicit position stored at page / All level. '' = nothing stored = inherit. */
function mfReadPosition($id)
{
	$id = mfSanitizeId($id);
	if ($id === '') {
		return '';
	}
	$file = mfDir() . 'position-' . $id . '.txt';
	if (!is_file($file)) {
		return '';
	}
	return mfNormalizePosition((string)file_get_contents($file));
}

/**
 * Store a page/All default. An empty value DELETES the file, so "inherit"
 * is never persisted as a hard-coded 'up' (that was what masked the parent).
 */
function mfWritePosition($id, $pos)
{
	$id = mfSanitizeId($id);
	if ($id === '') {
		return;
	}
	$pos  = mfNormalizePosition($pos);
	$file = mfDir() . 'position-' . $id . '.txt';

	if ($pos === '') {
		if (is_file($file)) {
			@unlink($file);
		}
		return;
	}
	mfEnsureDir();
	file_put_contents($file, $pos);
}

/**
 * Field definitions of one scope, normalised: label => [label,value,type,position].
 * Accepts the old format (no "position") and the "[]" that older saves wrote for empty lists.
 */
function mfReadSettings($id)
{
	$id = mfSanitizeId($id);
	if ($id === '') {
		return array();
	}
	$file = mfDir() . 'settings-' . $id . '.json';
	if (!is_file($file)) {
		return array();
	}
	$data = json_decode((string)file_get_contents($file), true);
	if (!is_array($data)) {
		return array();
	}

	$out = array();
	foreach ($data as $key => $f) {
		if (!is_array($f)) {
			continue;
		}
		$label = isset($f['label']) ? (string)$f['label'] : (is_string($key) ? $key : '');
		if ($label === '') {
			continue;
		}
		$out[$label] = array(
			'label'    => $label,
			'value'    => isset($f['value']) ? (string)$f['value'] : '',
			'type'     => isset($f['type']) ? (string)$f['type'] : 'text',
			'position' => mfNormalizePosition(isset($f['position']) ? $f['position'] : ''),
		);
	}
	return $out;
}

/**
 * Resolve where a field ends up.
 *
 * @param string $fieldPos explicit field position ('' = inherit)
 * @param string $scopeId  page id the field is shown on (or MF_ALL)
 * @return array [effective position, source]
 *         source: 'field' | 'scope' (page/All default) | 'all' | 'default'
 */
function mfResolvePosition($fieldPos, $scopeId)
{
	$fieldPos = mfNormalizePosition($fieldPos);
	if ($fieldPos !== '') {
		return array($fieldPos, 'field');
	}

	$scopeId = mfSanitizeId($scopeId);
	$scopePos = $scopeId !== '' ? mfReadPosition($scopeId) : '';
	if ($scopePos !== '') {
		return array($scopePos, 'scope');
	}

	if ($scopeId !== MF_ALL) {
		$allPos = mfReadPosition(MF_ALL);
		if ($allPos !== '') {
			return array($allPos, 'all');
		}
	}
	return array('up', 'default');
}

/**
 * Every field shown on a page: the page's own fields plus the global ones.
 * If a label exists in both, the page's definition wins (more specific scope).
 * Each entry gets 'origin' (page|all), 'effective' (up|bottom) and 'source'.
 */
function mfFieldsForPage($pageId)
{
	$pageId = mfSanitizeId($pageId);
	$own    = ($pageId !== '' && $pageId !== MF_ALL) ? mfReadSettings($pageId) : array();
	$all    = mfReadSettings(MF_ALL);

	$out = array();
	foreach ($own as $label => $f) {
		$f['origin'] = 'page';
		$out[$label] = $f;
	}
	foreach ($all as $label => $f) {
		if (!isset($out[$label])) {
			$f['origin'] = 'all';
			$out[$label] = $f;
		}
	}

	foreach ($out as $label => $f) {
		list($eff, $src) = mfResolvePosition($f['position'], $pageId);
		$out[$label]['effective'] = $eff;
		$out[$label]['source']    = $src;
	}
	return $out;
}

/** Remove everything stored for one scope (fields, values and position). */
function mfDeleteAll($id)
{
	$id = mfSanitizeId($id);
	if ($id === '') {
		return;
	}
	foreach (array(
		mfDir() . $id . '.json',
		mfDir() . 'settings-' . $id . '.json',
		mfDir() . 'position-' . $id . '.txt',
	) as $file) {
		if (is_file($file)) {
			@unlink($file);
		}
	}
}