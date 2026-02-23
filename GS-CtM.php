<?php

# plugin id from filename
$thisfile = basename(__FILE__, ".php");

# register plugin in Settings
register_plugin(
	$thisfile,
	'GS Click-to-Mail',
	'1.0',
	'risingisland',
	'https://www.getsimple-ce.ovh/',
	'Add mailto-powered contact buttons, forms, and a floating popup to your site.',
	'plugins',
	'gsctm_admin_page'
);

# add a link in Settings menu
add_action('plugins-sidebar','createSideMenu',array($thisfile,'GS Click-to-Mail
<svg xmlns="http://www.w3.org/2000/svg" style="vertical-align:middle;" width="1.2em" height="1.2em" viewBox="0 0 15 15"><rect width="15" height="15" fill="none"/><path fill="currentColor" d="M4 6a3 3 0 0 1 0-6h7a3 3 0 1 1 0 6H9V3.5a2.5 2.5 0 0 0-5 0z"/><path fill="currentColor" d="M6.5 2A1.5 1.5 0 0 0 5 3.5v4.55a2.5 2.5 0 0 0-2 2.45A4.5 4.5 0 0 0 7.5 15H8a5 5 0 0 0 5-5v-.853A2.147 2.147 0 0 0 10.853 7H8V3.5A1.5 1.5 0 0 0 6.5 2"/></svg>'));


# ----------------------------------------------------------
#  FILE PATH FOR STORAGE
# ----------------------------------------------------------
define('GSCTM_FILE', GSDATAOTHERPATH . 'gs-ctm.json');


# ----------------------------------------------------------
#  DEFAULT SETTINGS
#  These match the placeholder text shown in the admin form.
# ----------------------------------------------------------
function gsctm_defaults() {
	return array(
		// Global
		'recipient'			=> '',
		'theme_color'		=> '#1163fa',
		'popup_active'		=> '0',
		'popup_exclude'		=> '',

		// Form / Popup shared
		'form_header'		=> 'How can we help you?',
		'form_subheader'	=> "We're here to advise you and answer any questions you may have.",
		'form_subject'		=> 'Inquiry from your Website',
		'form_body'			=> '', // Hello, I am interested in 
		'form_placeholder'	=> 'Message',
		'form_send_text'	=> 'Send Mail',

		// Buttons
		'btn_text'			=> 'How can we help you?',
		'btn_subject'		=> 'Inquiry from your Website',
		'btn_body'			=> 'Hello, I am interested in ',

		// Composer chooser (mailtoui)
		'mc_title'			=> 'Compose message with...',
		'mc_gmail'			=> 'Gmail in browser',
		'mc_outlook'		=> 'Outlook in browser',
		'mc_yahoo'			=> 'Yahoo in browser',
		'mc_app'			=> 'Default email app',
		'mc_copy'			=> 'Copy',
	);
}


# ----------------------------------------------------------
#  LOAD SETTINGS  (merges saved data over defaults)
# ----------------------------------------------------------
function gsctm_load() {
	$defaults = gsctm_defaults();
	if (!file_exists(GSCTM_FILE)) return $defaults;
	$json = file_get_contents(GSCTM_FILE);
	$data = json_decode($json, true);
	if (!is_array($data)) return $defaults;
	return array_merge($defaults, $data);
}


# ----------------------------------------------------------
#  SAVE SETTINGS
# ----------------------------------------------------------
function gsctm_save($data) {
	$dir = dirname(GSCTM_FILE);
	if (!is_dir($dir)) @mkdir($dir, 0755, true);
	file_put_contents(GSCTM_FILE, json_encode($data, JSON_PRETTY_PRINT));
}


# ----------------------------------------------------------
#  HELPER: sanitize a plain-text setting value
# ----------------------------------------------------------
function gsctm_clean($val) {
	// Store plain text — strip tags and trim only.
	// htmlspecialchars() is applied at output time to avoid double-encoding.
	return strip_tags(trim($val));
}

// Variant that does NOT trim — used for fields that are allowed to be empty or
// contain only whitespace (form_body, btn_body).
function gsctm_clean_notrim($val) {
	return strip_tags($val);
}


# ----------------------------------------------------------
#  HELPER: build a mailto href from settings
# ----------------------------------------------------------
// For standalone buttons — subject and body are baked in as real values.
function gsctm_mailto($cfg, $use_btn = false) {
	$subject = rawurlencode($use_btn ? $cfg['btn_subject'] : $cfg['form_subject']);
	$body	= rawurlencode($use_btn ? $cfg['btn_body']	 : $cfg['form_body']);
	return 'mailto:' . $cfg['recipient']
		 . '?subject=' . $subject
		 . '&body='	. $body;
}

// For form/popup withinputmail links — uses the literal tokens subjectText and
// bodyText that gs-ctm.js regex-replaces with the textarea/input values on keyup.
function gsctm_mailto_tokens($cfg) {
	return 'mailto:' . $cfg['recipient']
		 . '?subject=subjectText'
		 . '&body=bodyText';
}


# ----------------------------------------------------------
#  HELPER: shared mail SVG icon
# ----------------------------------------------------------
function gsctm_mail_icon() {
	return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="24" height="24" fill="none"/><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m2.357 7.714l6.98 4.654c.963.641 1.444.962 1.964 1.087c.46.11.939.11 1.398 0c.52-.125 1.001-.446 1.964-1.087l6.98-4.654M7.157 19.5h9.686c1.68 0 2.52 0 3.162-.327a3 3 0 0 0 1.31-1.311c.328-.642.328-1.482.328-3.162V9.3c0-1.68 0-2.52-.327-3.162a3 3 0 0 0-1.311-1.311c-.642-.327-1.482-.327-3.162-.327H7.157c-1.68 0-2.52 0-3.162.327a3 3 0 0 0-1.31 1.311c-.328.642-.328 1.482-.328 3.162v5.4c0 1.68 0 2.52.327 3.162a3 3 0 0 0 1.311 1.311c.642.327 1.482.327 3.162.327"/></svg>';
}


# ----------------------------------------------------------
#  INJECT CSS  (theme-header)
#  Loads the plugin stylesheet AND overrides --color-primary
#  with whatever colour the admin has chosen.
# ----------------------------------------------------------
add_action('theme-header','gsctm_css');
function gsctm_css() {
	global $SITEURL;
	$cfg   = gsctm_load();
	$color = gsctm_clean($cfg['theme_color']);
	if (!$color) $color = '#1163fa';

	echo '<link href="' . $SITEURL . 'plugins/GS-CtM/assets/gs-ctm.css" rel="stylesheet">' . "\n";
	echo '<style>:root { --color-primary: ' . $color . '; --color-secondary: ' . gsctm_darken($color) . '; }</style>' . "\n";
}

# Simple helper: darken a hex colour by ~15 % for --color-secondary
function gsctm_darken($hex) {
	$hex = ltrim($hex, '#');
	if (strlen($hex) === 3) {
		$hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
	}
	$r = max(0, hexdec(substr($hex,0,2)) - 38);
	$g = max(0, hexdec(substr($hex,2,2)) - 38);
	$b = max(0, hexdec(substr($hex,4,2)) - 38);
	return sprintf('#%02x%02x%02x', $r, $g, $b);
}


# ----------------------------------------------------------
#  INJECT JS  (theme-footer)
#  Passes the Composer Chooser strings to mailtoui via the
#  data-options attribute that the library reads natively.
#  See: t.getOptionsFromScriptTag() in gs-ctm.js
# ----------------------------------------------------------
add_action('theme-footer','gsctm_js');
function gsctm_js() {
	global $SITEURL;
	$cfg = gsctm_load();

	$options = json_encode(array(
		'title'	   => $cfg['mc_title'],
		'buttonText1' => $cfg['mc_gmail'],
		'buttonText2' => $cfg['mc_outlook'],
		'buttonText3' => $cfg['mc_yahoo'],
		'buttonText4' => $cfg['mc_app'],
		'buttonTextCopy' => $cfg['mc_copy'],
	), JSON_HEX_APOS | JSON_HEX_TAG);

	echo '<script src="' . $SITEURL . 'plugins/GS-CtM/assets/gs-ctm.js" data-options=\'' . $options . '\'></script>' . "\n";
}


# ----------------------------------------------------------
#  SHORTCODE REGISTRATION
#  Hooks into GetSimple's content filter so that tags like
#  [CtM_btn1] are replaced when page content is rendered.
# ----------------------------------------------------------
add_filter('content', 'gsctm_process_shortcodes');
function gsctm_process_shortcodes($content) {
	$tags = array('CtM_btn1','CtM_btn2','CtM_btn3','CtM_btn4','CtM_btn5','CtM_form');
	foreach ($tags as $tag) {
		if (strpos($content, '[' . $tag . ']') !== false) {
			ob_start();
			call_user_func($tag);		  // call the output function
			$html = ob_get_clean();
			$content = str_replace('[' . $tag . ']', $html, $content);
		}
	}
	return $content;
}


# ----------------------------------------------------------
#  FLOATING POPUP  (only rendered when popup_active = 1)
# ----------------------------------------------------------
add_action('content-bottom','CtM_popup');
function CtM_popup() {
	$cfg = gsctm_load();
	if (empty($cfg['popup_active']) || $cfg['popup_active'] != '1') return;
	if (empty($cfg['recipient'])) return;  // nothing to send to

	// Check slug exclusion list
	if (!empty($cfg['popup_exclude'])) {
		// Use GetSimple's return_page_slug() if available, otherwise fall back to $id
		if (function_exists('return_page_slug')) {
			$current_slug = return_page_slug();
		} else {
			global $id;
			$current_slug = isset($id) ? $id : '';
		}
		$excluded = array_filter(array_map('trim', explode(',', $cfg['popup_exclude'])));
		if (in_array($current_slug, $excluded)) return;
	}

	$mailto_tokens = htmlspecialchars(gsctm_mailto_tokens($cfg), ENT_QUOTES, 'UTF-8');
	$header	 = htmlspecialchars($cfg['form_header'],	ENT_QUOTES, 'UTF-8');
	$subheader  = htmlspecialchars($cfg['form_subheader'], ENT_QUOTES, 'UTF-8');
	$body_text	= htmlspecialchars($cfg['form_body'],	  ENT_QUOTES, 'UTF-8');
	$form_subject = htmlspecialchars($cfg['form_subject'],   ENT_QUOTES, 'UTF-8');
	$placeholder  = htmlspecialchars($cfg['form_placeholder'],ENT_QUOTES,'UTF-8');
	$send_text	= htmlspecialchars($cfg['form_send_text'], ENT_QUOTES, 'UTF-8');
	$mail_icon  = gsctm_mail_icon();
	$close_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="24" height="24" fill="none"/><g fill="none" fill-rule="evenodd"><path d="m12.593 23.258l-.011.002l-.071.035l-.02.004l-.014-.004l-.071-.035q-.016-.005-.024.005l-.004.01l-.017.428l.005.02l.01.013l.104.074l.015.004l.012-.004l.104-.074l.012-.016l.004-.017l-.017-.427q-.004-.016-.017-.018m.265-.113l-.013.002l-.185.093l-.01.01l-.003.011l.018.43l.005.012l.008.007l.201.093q.019.005.029-.008l.004-.014l-.034-.614q-.005-.018-.02-.022m-.715.002a.02.02 0 0 0-.027.006l-.006.014l-.034.614q.001.018.017.024l.015-.002l.201-.093l.01-.008l.004-.011l.017-.43l-.003-.012l-.01-.01z"/><path fill="currentColor" d="m12 14.122l5.303 5.303a1.5 1.5 0 0 0 2.122-2.122L14.12 12l5.304-5.303a1.5 1.5 0 1 0-2.122-2.121L12 9.879L6.697 4.576a1.5 1.5 0 1 0-2.122 2.12L9.88 12l-5.304 5.304a1.5 1.5 0 1 0 2.122 2.12z"/></g></svg>';

	echo '
<div class="ctm" id="ctm-popup-buttons">
	<button class="ctm-bubble circle-bubble circle-animation-2">
		<span class="open-icon">' . $mail_icon . '</span>
		<span class="close-icon">' . $close_icon . '</span>
	</button>
	<div id="ctm-popup-form" class="ctm__popup animation4">
		<div class="ctm__popup--header header-center">
			<div class="info">
				<h4 class="info__title">' . $header . '</h4>
				<p class="info__sub_title">' . $subheader . '</p>
			</div>
		</div>
		<div class="ctm__popup--content">
			<div class="user-text">
				<input type="hidden" id="subject" value="' . $form_subject . '">
				<textarea id="messagePopup" rows="5" type="text" placeholder="' . $placeholder . '">' . $body_text . '</textarea>
			</div>
			<button class="ctm__send-message" target="_blank">
				' . $mail_icon . ' ' . $send_text . '
				<a class="mailtoui withinputmail" href="' . $mailto_tokens . '"></a>
			</button>
		</div>
	</div>
</div>
<script>
// Trigger gs-ctm.js updateHref once on load so the default textarea content
// is passed correctly without breaking subsequent keyup updates.
document.addEventListener("DOMContentLoaded", function() {
	var msg = document.querySelector("#ctm-popup-form textarea");
	if (msg) msg.dispatchEvent(new Event("keyup"));
});
</script>
';
}


# ----------------------------------------------------------
#  BUTTONS
# ----------------------------------------------------------

function gsctm_btn_html($extra_classes) {
	$cfg	   = gsctm_load();
	$mailto	= htmlspecialchars(gsctm_mailto($cfg, true), ENT_QUOTES, 'UTF-8');
	$btn_text  = htmlspecialchars($cfg['btn_text'], ENT_QUOTES, 'UTF-8');
	$mail_icon = gsctm_mail_icon();
	echo '<a href="' . $mailto . '" class="mailtoui ' . $extra_classes . '">'
		. $mail_icon . ' ' . $btn_text
		. '</a>' . "\n";
}

# Style 1 – clean / no border
function CtM_btn1() { gsctm_btn_html('ctm-button-2 ctm-btn-clean'); }

# Style 2 – rounded border
function CtM_btn2() { gsctm_btn_html('ctm-button-2 ctm-btn-rounded'); }

# Style 3 – filled, rounded, icon circle
function CtM_btn3() { gsctm_btn_html('ctm-button-3 ctm-btn-bg ctm-btn-rounded'); }

# Style 4 – standard border
function CtM_btn4() { gsctm_btn_html('ctm-button-2'); }

# Style 5 – filled, icon circle (no rounded)
function CtM_btn5() { gsctm_btn_html('ctm-button-3 ctm-btn-bg'); }


# ----------------------------------------------------------
#  EMBEDDED FORM
# ----------------------------------------------------------
function CtM_form() {
	$cfg		= gsctm_load();
	$mailto_tokens = htmlspecialchars(gsctm_mailto_tokens($cfg), ENT_QUOTES, 'UTF-8');
	$header	 = htmlspecialchars($cfg['form_header'],	 ENT_QUOTES, 'UTF-8');
	$subheader  = htmlspecialchars($cfg['form_subheader'],  ENT_QUOTES, 'UTF-8');
	$body_text	= htmlspecialchars($cfg['form_body'],	   ENT_QUOTES, 'UTF-8');
	$form_subject = htmlspecialchars($cfg['form_subject'],	ENT_QUOTES, 'UTF-8');
	$placeholder  = htmlspecialchars($cfg['form_placeholder'],ENT_QUOTES, 'UTF-8');
	$send_text	= htmlspecialchars($cfg['form_send_text'],  ENT_QUOTES, 'UTF-8');
	$mail_icon  = gsctm_mail_icon();

	echo '
<div id="ctm-form" class="ctm-form-embed">
	<div class="ctm__popup--header header-center">
		<div class="info">
			<h4 class="info__title">' . $header . '</h4>
			<p class="info__sub_title">' . $subheader . '</p>
		</div>
	</div>
	<div class="ctm__popup--content">
		<div class="user-text">
			<input type="hidden" id="subject" value="' . $form_subject . '">
			<textarea id="messageForm" rows="10" type="text" placeholder="' . $placeholder . '">' . $body_text . '</textarea>
		</div>
		<button class="ctm__send-message" target="_blank">
			' . $mail_icon . ' ' . $send_text . '
			<a class="mailtoui withinputmail" href="' . $mailto_tokens . '"></a>
		</button>
	</div>
</div>
<script>
// Trigger gs-ctm.js updateHref once on load so the default textarea content
// is passed correctly without breaking subsequent keyup updates.
document.addEventListener("DOMContentLoaded", function() {
	var msg = document.querySelector("#ctm-form textarea");
	if (msg) msg.dispatchEvent(new Event("keyup"));
});
</script>
';
}


# ----------------------------------------------------------
#  ADMIN PAGE
# ----------------------------------------------------------
function gsctm_admin_page() {

	$cfg = gsctm_load();

	// ---- Handle save ----
	if (isset($_POST['gsctm-save'])) {
		$new = array(
			'recipient'			=> gsctm_clean($_POST['recipient']	?? ''),
			'theme_color'		=> gsctm_clean($_POST['theme_color']  ?? '#1163fa'),
			'popup_active'		=> isset($_POST['popup_active']) ? '1' : '0',
			'popup_exclude'		=> gsctm_clean($_POST['popup_exclude'] ?? ''),

			'form_header'		=> gsctm_clean($_POST['form_header']	  ?? ''),
			'form_subheader'	=> gsctm_clean($_POST['form_subheader']   ?? ''),
			'form_subject'		=> gsctm_clean($_POST['form_subject']	  ?? ''),
			'form_body'			=> gsctm_clean_notrim($_POST['form_body']		 ?? ''),
			'form_placeholder'	=> gsctm_clean($_POST['form_placeholder']  ?? ''),
			'form_send_text'	=> gsctm_clean($_POST['form_send_text']	?? ''),

			'btn_text'			=> gsctm_clean($_POST['btn_text']	?? ''),
			'btn_subject'		=> gsctm_clean($_POST['btn_subject'] ?? ''),
			'btn_body'			=> gsctm_clean_notrim($_POST['btn_body']	?? ''),

			'mc_title'			=> gsctm_clean($_POST['mc_title']   ?? ''),
			'mc_gmail'			=> gsctm_clean($_POST['mc_gmail']   ?? ''),
			'mc_outlook'		=> gsctm_clean($_POST['mc_outlook'] ?? ''),
			'mc_yahoo'			=> gsctm_clean($_POST['mc_yahoo']   ?? ''),
			'mc_app'			=> gsctm_clean($_POST['mc_app']	 ?? ''),
			'mc_copy'			=> gsctm_clean($_POST['mc_copy']	?? ''),
		);
		// Fall back to defaults for any blank required fields
		$defaults = gsctm_defaults();
		foreach ($new as $k => $v) {
			if (in_array($k, array('recipient','popup_active','popup_exclude','form_body','btn_body'))) continue; // keep as-is (these are allowed to be empty)
			if ($v === '') $new[$k] = $defaults[$k];
		}
		gsctm_save($new);
		$cfg = $new;
		echo '<div class="updated">Settings saved.</div>';
	}

	// ---- Helper to output a text input ----
	$fi = function($name, $placeholder, $val) {
		$v   = htmlspecialchars($val,		 ENT_QUOTES, 'UTF-8');
		$ph  = htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8');
		echo '<input type="text" name="' . $name . '" value="' . $v . '" placeholder="' . $ph . '">' . "\n";
	};

	$defaults = gsctm_defaults();

	// ---- Shortcode reference table ----
	$shortcodes = array(
		'CtM_btn1' => 'Button – Clean / no border',
		'CtM_btn2' => 'Button – Rounded border',
		'CtM_btn3' => 'Button – Filled + rounded + icon circle',
		'CtM_btn4' => 'Button – Standard border',
		'CtM_btn5' => 'Button – Filled + icon circle',
		'CtM_form' => 'Embedded contact form',
	);

	?>
<style>
/* ---- admin scoped styles ---- */
#gsctm-wrap { font-family: inherit; }
#gsctm-wrap .gsctm-box {
	background: #f5f5f5;
	border: 1px solid #d0d0d0;
	border-radius: 5px;
	padding: 30px 40px;
	margin-bottom: 30px;
}
#gsctm-wrap h2 {
	font-size: 20px;
	font-weight:600;
	margin: 0 0 4px;
	color: var(--main-color, #333) !important;
}
#gsctm-wrap h2.gsctm-help {
	color:#0080ff !important;
}
#gsctm-wrap .gsctm-desc {
	color: #666;
	margin: 0 0 20px;
	font-size: 13px;
}
#gsctm-wrap hr {
	border: none;
	border-top: 1px dotted #bbb;
	margin: 24px 0;
}
#gsctm-wrap label {
	display: block;
	font-weight: bold;
	margin: 16px 0 4px;
	font-size: 13px;
}
#gsctm-wrap label .gsctm-hint {
	font-weight: normal;
	color: #888;
	font-size: 12px;
	margin-left: 6px;
}
#gsctm-wrap input[type="text"],
#gsctm-wrap input[type="email"],
#gsctm-wrap input[type="color"] {
	width: 100%;
	padding: 7px 9px;
	border: 1px solid #aaa;
	background: #fff;
	font-size: 14px;
	box-sizing: border-box;
	border-radius: 3px;
}
#gsctm-wrap input[type="color"] {
	width: 60px;
	height: 36px;
	padding: 2px 4px;
	cursor: pointer;
	vertical-align: middle;
}
#gsctm-wrap .gsctm-color-row {
	display: flex;
	gap: 10px;
	align-items: center;
}
#gsctm-wrap .gsctm-color-row input[type="text"] {
	flex: 1;
}
#gsctm-wrap input[type="checkbox"] {
	width: 18px;
	height: 18px;
	margin-top: 4px;
	cursor: pointer;
}
#gsctm-wrap .gsctm-shortcode-table {
	width: 100%;
	border-collapse: collapse;
	font-size: 13px;
	margin-top: 10px;
}
#gsctm-wrap .gsctm-shortcode-table th,
#gsctm-wrap .gsctm-shortcode-table td {
	text-align: left;
	padding: 7px 10px;
	border: 1px solid #ddd;
}
#gsctm-wrap .gsctm-shortcode-table th {
	background: #e8e8e8;
}
#gsctm-wrap .gsctm-shortcode-table code {
	background: #e0e0e0;
	padding: 1px 5px;
	border-radius: 3px;
	font-family: monospace;
}
#gsctm-wrap .gsctm-shortcode-table .gsctm-php {
	color: #555;
	font-family: monospace;
	font-size: 12px;
}
#gsctm-wrap .gsctm-save-bar {
	margin-top: 24px;
}
#gsctm-wrap .gsctm-save-bar input[type="submit"] {
	padding: 9px 28px;
	font-size: 15px;
	cursor: pointer;
}

.donate {
	margin:20px 0; padding:15px; border:solid 1px #ddd; background:#fafafa; border-radius:5px; margin:0!important;
}
.donate p {
	margin:0;
}
.donateButton {
	box-shadow: 0px 1px 0px 0px #fff6af; background:linear-gradient(to bottom, #ffec64 5%, #ffab23 100%); background-color:#ffec64; border-radius:8px; border:1px solid #ffaa22; display:inline-block; cursor:pointer; color:#333333; font-family:Arial; font-size:1.2em; font-weight:normal!important; padding:5px 10px; text-decoration:none!important; text-shadow:0px 1px 0px #ffee66; margin-left:20px;
}
.donateButton:hover {
	background:linear-gradient(to bottom, #ffab23 5%, #ffec64 100%); background-color:#ffab23;
}
.donateButton:active {
	position:relative; top:1px;
}
</style>

<div id="gsctm-wrap">

<div class="w3-parent">
	<header class="w3-container w3-border-bottom w3-margin-bottom">
		<h3>
			<svg xmlns="http://www.w3.org/2000/svg" style="vertical-align:middle;" width="1.2em" height="1.2em" viewBox="0 0 15 15"><rect width="15" height="15" fill="none"/><path fill="currentColor" d="M4 6a3 3 0 0 1 0-6h7a3 3 0 1 1 0 6H9V3.5a2.5 2.5 0 0 0-5 0z"/><path fill="currentColor" d="M6.5 2A1.5 1.5 0 0 0 5 3.5v4.55a2.5 2.5 0 0 0-2 2.45A4.5 4.5 0 0 0 7.5 15H8a5 5 0 0 0 5-5v-.853A2.147 2.147 0 0 0 10.853 7H8V3.5A1.5 1.5 0 0 0 6.5 2"/></svg>
			GS Click-to-Mail
		</h3>
		<p>A fully configurable contact system using mailto links. Offers a floating popup, embeddable forms, and styled buttons — with a built-in email client chooser for Gmail, Outlook, Yahoo, and more.</p>
	</header>
</div>

<form method="post" action="">
<input type="hidden" name="gsctm-save" value="1">

<!-- ===== GLOBAL ===== -->
<div class="gsctm-box">
	<h2>Global Settings</h2>
	<p class="gsctm-desc">These settings apply to all buttons, the floating popup, and the embedded form.</p>

	<label>Recipient Address <span class="gsctm-hint">required – the email address that will receive messages</span></label>
	<?php $fi('recipient', 'you@yourdomain.com', $cfg['recipient']); ?>

	<label>Theme Color</label>
	<div class="gsctm-color-row">
		<input type="color" id="gsctm-color-picker" value="<?php echo htmlspecialchars($cfg['theme_color'], ENT_QUOTES); ?>"
			   oninput="document.getElementById('gsctm-color-text').value=this.value">
		<input type="text"  id="gsctm-color-text"   name="theme_color"
			   value="<?php echo htmlspecialchars($cfg['theme_color'], ENT_QUOTES); ?>"
			   placeholder="#1163fa"
			   oninput="document.getElementById('gsctm-color-picker').value=this.value">
	</div>

	<label>Activate Floating Popup Button?
		<span class="gsctm-hint">When checked, a bubble button appears on every page</span>
	</label>
	<input type="checkbox" name="popup_active" value="1" <?php echo ($cfg['popup_active'] === '1') ? 'checked' : ''; ?>>

	<label>Exclude from Pages
		<span class="gsctm-hint">comma-separated page slugs where the popup should not appear, e.g. <code>contact, about</code></span>
	</label>
	<input type="text" name="popup_exclude" value="<?php echo htmlspecialchars($cfg['popup_exclude'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="contact, about">

</div>

<!-- ===== FORM / POPUP ===== -->
<div class="gsctm-box">
	<h2>Form &amp; Popup Settings</h2>
	<p class="gsctm-desc">Controls the text inside the floating popup and the embedded <code>[CtM_form]</code> form.</p>

	<label>Header</label>
	<?php $fi('form_header', $defaults['form_header'], $cfg['form_header']); ?>

	<label>Sub-header</label>
	<?php $fi('form_subheader', $defaults['form_subheader'], $cfg['form_subheader']); ?>

	<label>Email Subject <span class="gsctm-hint">pre-filled subject line of the sent email</span></label>
	<?php $fi('form_subject', $defaults['form_subject'], $cfg['form_subject']); ?>

	<label>Default Body Text <span class="gsctm-hint">pre-filled body of the sent email</span></label>
	<?php $fi('form_body', $defaults['form_body'], $cfg['form_body']); ?>

	<label>Message Textarea Placeholder</label>
	<?php $fi('form_placeholder', $defaults['form_placeholder'], $cfg['form_placeholder']); ?>

	<label>Send Button Text</label>
	<?php $fi('form_send_text', $defaults['form_send_text'], $cfg['form_send_text']); ?>
</div>

<!-- ===== BUTTONS ===== -->
<div class="gsctm-box">
	<h2>Button Settings</h2>
	<p class="gsctm-desc">Controls the text and email content for all button styles (CtM_btn1 – CtM_btn5).</p>

	<label>Button Label Text</label>
	<?php $fi('btn_text', $defaults['btn_text'], $cfg['btn_text']); ?>

	<label>Button Email Subject</label>
	<?php $fi('btn_subject', $defaults['btn_subject'], $cfg['btn_subject']); ?>

	<label>Button Email Body Text <span class="gsctm-hint">pre-filled body when clicking a button</span></label>
	<?php $fi('btn_body', $defaults['btn_body'], $cfg['btn_body']); ?>
</div>

<!-- ===== COMPOSER CHOOSER ===== -->
<div class="gsctm-box">
	<h2>Email Client Chooser Texts</h2>
	<p class="gsctm-desc">
		When a visitor clicks Send (or any button), a popup lets them pick their email client.
		These labels are injected into the <code>gs-ctm.js</code> library via its built-in
		<code>data-options</code> configuration attribute.
	</p>

	<label>Dialog Header</label>
	<?php $fi('mc_title', $defaults['mc_title'], $cfg['mc_title']); ?>

	<label>Gmail Option</label>
	<?php $fi('mc_gmail', $defaults['mc_gmail'], $cfg['mc_gmail']); ?>

	<label>Outlook Option</label>
	<?php $fi('mc_outlook', $defaults['mc_outlook'], $cfg['mc_outlook']); ?>

	<label>Yahoo Option</label>
	<?php $fi('mc_yahoo', $defaults['mc_yahoo'], $cfg['mc_yahoo']); ?>

	<label>Default Email App Option</label>
	<?php $fi('mc_app', $defaults['mc_app'], $cfg['mc_app']); ?>

	<label>Copy Address Button</label>
	<?php $fi('mc_copy', $defaults['mc_copy'], $cfg['mc_copy']); ?>
</div>

<!-- ===== SAVE ===== -->
<div class="gsctm-save-bar">
	<input type="submit" class="submit" value="Save Settings">
</div>

</form>

<!-- ===== SHORTCODE REFERENCE ===== -->
<div class="gsctm-box" style="margin-top:30px; border:#0080ff solid 1px;">
	<h2 class="gsctm-help">Shortcode &amp; Template Reference</h2>
	<p class="gsctm-desc">
		Use these anywhere in page content (shortcode) or directly in your theme template files (PHP tag).
	</p>
	<table class="gsctm-shortcode-table">
		<thead>
			<tr>
				<th>Description</th>
				<th>Content Shortcode</th>
				<th>PHP Template Tag</th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ($shortcodes as $tag => $desc): ?>
			<tr>
				<td><?php echo htmlspecialchars($desc); ?></td>
				<td><code>[<?php echo $tag; ?>]</code></td>
				<td class="gsctm-php">&lt;?php <?php echo $tag; ?>(); ?&gt;</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<p style="margin-top:14px; color:#666; font-size:13px;">
		The floating popup is controlled by the <strong>Activate Floating Popup Button</strong> checkbox above
		and requires no shortcode — it is injected automatically on every page.
	</p>
</div>

<footer class="donate">
	<p>Is this plugin useful to you? Help support a developer.
		<a href="https://getsimple-ce.ovh/donate" target="_blank" class="donateButton">
			<b>Buy Us A Coffee </b>
			<svg xmlns="http://www.w3.org/2000/svg" style="vertical-align:middle" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" fill-opacity="0" d="M17 14v4c0 1.66 -1.34 3 -3 3h-6c-1.66 0 -3 -1.34 -3 -3v-4Z"><animate fill="freeze" attributeName="fill-opacity" begin="0.8s" dur="0.5s" values="0;1"></animate></path><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path stroke-dasharray="48" stroke-dashoffset="48" d="M17 9v9c0 1.66 -1.34 3 -3 3h-6c-1.66 0 -3 -1.34 -3 -3v-9Z"><animate fill="freeze" attributeName="stroke-dashoffset" dur="0.6s" values="48;0"></animate></path><path stroke-dasharray="14" stroke-dashoffset="14" d="M17 9h3c0.55 0 1 0.45 1 1v3c0 0.55 -0.45 1 -1 1h-3"><animate fill="freeze" attributeName="stroke-dashoffset" begin="0.6s" dur="0.2s" values="14;0"></animate></path><mask id="lineMdCoffeeHalfEmptyFilledLoop0"><path stroke="#fff" d="M8 0c0 2-2 2-2 4s2 2 2 4-2 2-2 4 2 2 2 4M12 0c0 2-2 2-2 4s2 2 2 4-2 2-2 4 2 2 2 4M16 0c0 2-2 2-2 4s2 2 2 4-2 2-2 4 2 2 2 4"><animateMotion calcMode="linear" dur="3s" path="M0 0v-8" repeatCount="indefinite"></animateMotion></path></mask><rect width="24" height="0" y="7" fill="currentColor" mask="url(#lineMdCoffeeHalfEmptyFilledLoop0)"><animate fill="freeze" attributeName="y" begin="0.8s" dur="0.6s" values="7;2"></animate><animate fill="freeze" attributeName="height" begin="0.8s" dur="0.6s" values="0;5"></animate></rect></g></svg>
		</a>
	</p>
</footer>

</div><!-- #gsctm-wrap -->
<?php
}

?>