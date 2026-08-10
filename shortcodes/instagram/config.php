<?php if ( ! defined( 'FW' ) ) { die( 'Forbidden' ); }

/**
 * Instagram feed — a UnysonPlus Shortcodes Library element (downloaded on demand,
 * NOT bundled in the base). Renders a responsive grid of a profile's recent media
 * via the Instagram Basic Display / Graph API (server-side fetch, transient-cached).
 */

$cfg = array();

$cfg['page_builder'] = array(
	'title'       => __( 'Instagram', 'fw' ),
	'description' => __( 'A responsive grid of your recent Instagram posts', 'fw' ),
	'tab'         => __( 'Social', 'fw' ),
	'popup_size'  => 'large',
	// Canvas preview: a branded chip with the handle (or "Instagram feed" when unset),
	// so the element reads as an Instagram block in the builder. Builder affordance only.
	'title_template' => '
		{{
			var u = ( o && o["username"] ) ? ( "" + o["username"] ).replace( /^@/, "" ).trim() : "";
			var cols = ( o && o["columns"] ) ? o["columns"] : "3";
			var n = Math.min( 6, parseInt( ( o && o["count"] ) ? o["count"] : "6", 10 ) || 6 );
			var tiles = "";
			for ( var i = 0; i < n; i++ ) { tiles += "<span style=\"width:14px;height:14px;border-radius:3px;background:rgba(255,255,255,.85);display:inline-block;\"></span>"; }
		}}
		<span style="display:inline-flex; align-items:center; gap:8px; margin-top:.5rem; padding:6px 12px; background:linear-gradient(45deg,#f09433,#dc2743,#bc1888); color:#fff; border-radius:8px; font-size:12px; font-weight:600; line-height:1; vertical-align:middle;">
			<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="#fff" stroke="none"/></svg>
			<span>{{- u ? ( "@" + u ) : "Instagram feed" }}</span>
			<span style="display:inline-flex; gap:3px; opacity:.9;">{{= tiles }}</span>
		</span>',
);
