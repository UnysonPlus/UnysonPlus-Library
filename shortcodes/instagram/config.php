<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

$cfg = array();

$cfg['page_builder'] = array(
	'title'          => __( 'Instagram Feed', 'fw' ),
	'description'    => __( 'An Instagram profile header with a grid or slider of posts — live from a connected account, or images you choose.', 'fw' ),
	'tab'            => __( 'Media Elements', 'fw' ),
	'popup_size'     => 'large',
	// A small picture of the element on the canvas: the header line and a strip of tiles, so the
	// builder shows what the element is rather than only its name.
	'title_template' => '
		{{
			var src    = o && o.source && o.source.mode === "manual" ? "Chosen images" : "Connected account";
			var handle = o && o.profile_handle ? ( "" + o.profile_handle ).replace( /^@/, "" ) : "";
		}}
		<span class="fw-igf-chip" style="display:inline-flex;align-items:center;gap:8px">
			<span style="display:inline-grid;grid-template-columns:repeat(4,10px);gap:2px">
				<i style="width:10px;height:10px;background:#c9cdd6;border-radius:2px"></i><i style="width:10px;height:10px;background:#d8dbe2;border-radius:2px"></i><i style="width:10px;height:10px;background:#c9cdd6;border-radius:2px"></i><i style="width:10px;height:10px;background:#d8dbe2;border-radius:2px"></i>
			</span>
			<strong>Instagram Feed</strong>
			<span style="opacity:.7">{{- handle ? "@" + handle + " · " : "" }}{{- src }}</span>
		</span>',
);
