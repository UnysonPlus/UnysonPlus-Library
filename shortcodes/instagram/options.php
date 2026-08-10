<?php if ( ! defined( 'FW' ) ) { die( 'Forbidden' ); }

/**
 * Instagram feed options — Content / Layout / Styling / Advanced, the standard shortcode
 * shape. Reuses the shared framework presets + helpers: the Gap Scale (sc_get_gap_select_choices),
 * compact color fields (sc_color_field_compact), and the standard Advanced tab (sc_get_advanced_tab).
 * The Access Token is a CREDENTIAL — used only for the server-side API call, never printed.
 */

$ig_gap_choices = function_exists( 'sc_get_gap_select_choices' )
	? sc_get_gap_select_choices( __( 'None', 'fw' ) )
	: array( '' => __( 'None', 'fw' ), '4' => '4px', '8' => '8px', '16' => '16px' );

$ig_help = function ( $ctx ) { return function_exists( 'sc_styling_help_text' ) ? sc_styling_help_text( $ctx ) : ''; };

$options = array(

	/* ---------------------------------------------------------------- Content */
	'tab_content' => array(
		'title'   => __( 'Content', 'fw' ),
		'type'    => 'tab',
		'options' => array(
			'group_source' => array(
				'type'    => 'group',
				'options' => array(
					'username' => array(
						'label' => __( 'Username', 'fw' ),
						'desc'  => __( 'Your Instagram handle (without the @). Shown as the heading and used for the profile link.', 'fw' ),
						'type'  => 'text',
						'value' => '',
					),
					'access_token' => array(
						'label' => __( 'Access Token', 'fw' ),
						'desc'  => __( 'A long-lived Instagram Basic Display / Graph API token. Used only for the server-side feed request and never shown on the page. Leave empty to preview a placeholder grid.', 'fw' ),
						'help'  => __( 'Create a token in the Meta for Developers dashboard (Instagram Basic Display → User Token Generator). Long-lived tokens last ~60 days and can be refreshed.', 'fw' ),
						'type'  => 'text',
						'value' => '',
					),
					'count' => array(
						'label'   => __( 'Number of posts', 'fw' ),
						'type'    => 'select',
						'value'   => '6',
						'choices' => array( '3' => '3', '6' => '6', '9' => '9', '12' => '12', '15' => '15', '18' => '18' ),
					),
				),
			),
		),
	),

	/* ---------------------------------------------------------------- Layout */
	'tab_layout' => array(
		'title'   => __( 'Layout', 'fw' ),
		'type'    => 'tab',
		'options' => array(
			'group_grid' => array(
				'type'    => 'group',
				'options' => array(
					'columns' => array(
						'label'   => __( 'Columns', 'fw' ),
						'type'    => 'select',
						'value'   => '3',
						'choices' => array( '1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6' ),
						'desc'    => __( 'Tiles per row on desktop; tablet and phone step down automatically.', 'fw' ),
					),
					'gap' => array(
						'label'   => __( 'Gap', 'fw' ),
						'type'    => 'select',
						'value'   => '',
						'choices' => $ig_gap_choices,
						'desc'    => __( 'Spacing between tiles, from your Spacing → Gap Scale presets.', 'fw' ),
						'help'    => $ig_help( 'spacing' ),
					),
				),
			),
			'group_dimensions' => array(
				'type'    => 'group',
				'title'   => __( 'Dimensions', 'fw' ),
				'options' => array(
					// Overall feed WIDTH — constrain the grid narrower than its container. Empty = full width.
					'max_width' => array(
						'label' => __( 'Max width', 'fw' ),
						'type'  => 'unit-input',
						'units' => array( 'px', '%', 'rem', 'vw' ),
						'value' => array( 'value' => '', 'unit' => 'px' ),
						'min'   => 0,
						'desc'  => __( 'Maximum width of the whole feed. Leave empty to fill the column/section. Use the Alignment below to place a constrained feed.', 'fw' ),
					),
					'align' => array(
						'label'   => __( 'Alignment', 'fw' ),
						'type'    => 'select',
						'value'   => 'center',
						'choices' => array(
							'left'   => __( 'Left', 'fw' ),
							'center' => __( 'Center', 'fw' ),
							'right'  => __( 'Right', 'fw' ),
						),
						'desc'    => __( 'How a max-width-constrained feed sits in its container.', 'fw' ),
					),
					// Tile HEIGHT is driven by an aspect ratio so the grid stays responsive at any width.
					'aspect' => array(
						'type'    => 'multi-picker',
						'label'   => false,
						'desc'    => false,
						'value'   => array( 'ratio' => '1-1' ),
						'picker'  => array(
							'ratio' => array(
								'label'   => __( 'Tile shape (height)', 'fw' ),
								'type'    => 'select',
								'choices' => array(
									'1-1'  => __( 'Square (1:1)', 'fw' ),
									'4-5'  => __( 'Portrait (4:5)', 'fw' ),
									'3-4'  => __( 'Tall (3:4)', 'fw' ),
									'16-9' => __( 'Landscape (16:9)', 'fw' ),
									'auto' => __( 'Natural (original)', 'fw' ),
									'custom' => __( 'Custom…', 'fw' ),
								),
								'desc'    => __( 'Sets each tile\'s height relative to its width, so tiles stay uniform as the grid reflows.', 'fw' ),
							),
						),
						'choices' => array(
							'custom' => array(
								'aspect_custom' => array(
									'label' => __( 'Custom ratio', 'fw' ),
									'type'  => 'text',
									'value' => '4 / 3',
									'desc'  => __( 'A CSS aspect-ratio, e.g. <code>4 / 3</code> or <code>1.5</code>.', 'fw' ),
								),
							),
						),
						'show_borders' => false,
					),
				),
			),
		),
	),

	/* ---------------------------------------------------------------- Styling */
	'tab_styling' => array(
		'title'   => __( 'Styling', 'fw' ),
		'type'    => 'tab',
		'options' => array(
			'group_appearance' => array(
				'type'    => 'group',
				'options' => array(
					'rounding' => array(
						'label'   => __( 'Corner rounding', 'fw' ),
						'type'    => 'select',
						'value'   => 'small',
						'choices' => array(
							'none'   => __( 'None', 'fw' ),
							'small'  => __( 'Small', 'fw' ),
							'medium' => __( 'Medium', 'fw' ),
							'large'  => __( 'Large', 'fw' ),
							'circle' => __( 'Circle', 'fw' ),
						),
					),
					'hover_zoom' => array(
						'label'        => __( 'Zoom on hover', 'fw' ),
						'type'         => 'switch',
						'value'        => 'yes',
						'left-choice'  => array( 'value' => 'no',  'label' => __( 'No', 'fw' ) ),
						'right-choice' => array( 'value' => 'yes', 'label' => __( 'Yes', 'fw' ) ),
					),
					'show_caption' => array(
						'label'        => __( 'Caption on hover', 'fw' ),
						'desc'         => __( 'Fade in the post caption when hovering a tile.', 'fw' ),
						'type'         => 'switch',
						'value'        => 'no',
						'left-choice'  => array( 'value' => 'no',  'label' => __( 'No', 'fw' ) ),
						'right-choice' => array( 'value' => 'yes', 'label' => __( 'Yes', 'fw' ) ),
					),
				),
			),
			'group_colors' => array(
				'type'    => 'group',
				'options' => array(
					'handle_color' => function_exists( 'sc_color_field_compact' )
						? sc_color_field_compact( array( 'label' => __( 'Handle color', 'fw' ), 'desc' => __( 'Color of the @username heading. Defaults to the Instagram gradient.', 'fw' ), 'kind' => 'text' ) )
						: array( 'label' => __( 'Handle color', 'fw' ), 'type' => 'color-picker', 'value' => '' ),
					'overlay_color' => function_exists( 'sc_color_field_compact' )
						? sc_color_field_compact( array( 'label' => __( 'Caption overlay', 'fw' ), 'desc' => __( 'Tint behind the hover caption.', 'fw' ), 'kind' => 'bg' ) )
						: array( 'label' => __( 'Caption overlay', 'fw' ), 'type' => 'color-picker', 'value' => '' ),
				),
			),
		),
	),

	/* ---------------------------------------------------------------- Links */
	'tab_links' => array(
		'title'   => __( 'Links', 'fw' ),
		'type'    => 'tab',
		'options' => array(
			'group_links' => array(
				'type'    => 'group',
				'options' => array(
					'link_to_post' => array(
						'label'        => __( 'Link tiles to the post', 'fw' ),
						'type'         => 'switch',
						'value'        => 'yes',
						'left-choice'  => array( 'value' => 'no',  'label' => __( 'No', 'fw' ) ),
						'right-choice' => array( 'value' => 'yes', 'label' => __( 'Yes', 'fw' ) ),
					),
					'new_tab' => array(
						'label'        => __( 'Open links in a new tab', 'fw' ),
						'type'         => 'switch',
						'value'        => 'yes',
						'left-choice'  => array( 'value' => 'no',  'label' => __( 'No', 'fw' ) ),
						'right-choice' => array( 'value' => 'yes', 'label' => __( 'Yes', 'fw' ) ),
					),
				),
			),
		),
	),

	/* ---------------------------------------------------------------- Advanced */
	'tab_advanced' => array(
		'title'   => __( 'Advanced', 'fw' ),
		'type'    => 'tab',
		'options' => function_exists( 'sc_get_advanced_tab' ) ? sc_get_advanced_tab() : array(),
	),
);
