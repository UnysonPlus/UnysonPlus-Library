<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

$igf_cols = array( '1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6' );

$options = array(
	'tab_content' => array(
		'title'   => __( 'Content', 'fw' ),
		'type'    => 'tab',
		'options' => array(
			'group_source' => array(
				'type'    => 'group',
				'options' => array(
					'source' => array(
						'type'         => 'multi-picker',
						'label'        => false,
						'desc'         => false,
						'show_borders' => false,
						'picker'       => array(
							'mode' => array(
								'label'   => __( 'Posts From', 'fw' ),
								'type'    => 'select',
								'value'   => 'account',
								'choices' => array(
									'account' => __( 'A connected Instagram account (updates itself)', 'fw' ),
									'manual'  => __( 'Images I choose', 'fw' ),
								),
								'desc'    => __( 'A connected account shows its latest posts and keeps them current. Chosen images work with any account and need no connection.', 'fw' ),
							),
						),
						'choices'      => array(
							'account' => array(
								'access_token' => array(
									'label' => __( 'Access Token', 'fw' ),
									'type'  => 'password',
									'value' => '',
									'desc'  => __( 'A long-lived Instagram access token for a Business or Creator account (Instagram API with Instagram Login). Personal accounts cannot share their posts. The token is refreshed automatically, so it keeps working after its first 60 days.', 'fw' ),
									'help'  => __( 'Create it in the Meta developer dashboard: add the Instagram product to an app, use "API setup with Instagram login", add the account and click "Generate token". Paste the token here. It is stored on your site only and is never shown on the page.', 'fw' ),
								),
								'cache_hours'  => array(
									'label'   => __( 'Check for New Posts', 'fw' ),
									'type'    => 'select',
									'value'   => '6',
									'choices' => array(
										'1'   => __( 'Every hour', 'fw' ),
										'3'   => __( 'Every 3 hours', 'fw' ),
										'6'   => __( 'Every 6 hours', 'fw' ),
										'12'  => __( 'Every 12 hours', 'fw' ),
										'24'  => __( 'Once a day', 'fw' ),
									),
									'desc'    => __( 'Posts and images are saved on your site and refreshed in the background, so the feed loads fast and still shows if Instagram is down.', 'fw' ),
								),
							),
							'manual'  => array(
								'posts' => array(
									'label'         => __( 'Images', 'fw' ),
									'type'          => 'addable-popup',
									'value'         => array(),
									'template'      => '{{- link || "Image" }}',
									'popup-options' => array(
										'image' => array(
											'label'       => __( 'Image', 'fw' ),
											'type'        => 'upload',
											'images_only' => true,
										),
										'link'  => array(
											'label' => __( 'Link', 'fw' ),
											'type'  => 'text',
											'value' => '',
											'desc'  => __( 'The Instagram post this tile opens. Leave empty to use the profile link.', 'fw' ),
										),
										'kind'  => array(
											'label'   => __( 'Post Type Icon', 'fw' ),
											'type'    => 'select',
											'value'   => 'image',
											'choices' => array(
												'image' => __( 'Photo (no icon)', 'fw' ),
												'video' => __( 'Reel / video', 'fw' ),
												'album' => __( 'Several photos', 'fw' ),
											),
										),
										'alt'   => array(
											'label' => __( 'Description', 'fw' ),
											'type'  => 'text',
											'value' => '',
											'desc'  => __( 'Alt text for screen readers. Leave empty to use the image\'s own alt text.', 'fw' ),
										),
									),
								),
							),
						),
					),
				),
			),
			'group_header' => array(
				'type'    => 'group',
				'options' => array(
					'show_header'    => array(
						'label'        => __( 'Profile Header', 'fw' ),
						'type'         => 'switch',
						'value'        => 'yes',
						'left-choice'  => array( 'value' => 'no', 'label' => __( 'Hide', 'fw' ) ),
						'right-choice' => array( 'value' => 'yes', 'label' => __( 'Show', 'fw' ) ),
						'desc'         => __( 'The profile picture, name, @handle and Follow button above the posts.', 'fw' ),
					),
					'profile_handle' => array(
						'label' => __( 'Instagram Handle', 'fw' ),
						'type'  => 'text',
						'value' => '',
						'desc'  => __( 'For example @yourvilla. Links the header and Follow button to the profile. A connected account fills this in by itself.', 'fw' ),
					),
					'profile_name'   => array(
						'label' => __( 'Display Name', 'fw' ),
						'type'  => 'text',
						'value' => '',
						'desc'  => __( 'Shown in bold beside the picture. Leave empty to use the account\'s own name.', 'fw' ),
					),
					'profile_avatar' => array(
						'label'       => __( 'Profile Picture', 'fw' ),
						'type'        => 'upload',
						'images_only' => true,
						'desc'        => __( 'Leave empty to use the account\'s own picture.', 'fw' ),
					),
					'follow_label'   => array(
						'label' => __( 'Button Text', 'fw' ),
						'type'  => 'text',
						'value' => __( 'Follow', 'fw' ),
					),
					'button_preset'  => array(
						'type'         => 'button-style-picker',
						'label'        => __( 'Button Preset', 'fw' ),
						'choices'      => function_exists( 'sc_get_button_style_choices' ) ? sc_get_button_style_choices() : array(),
						'value'        => '',
						'allow_none'   => true,
						'preview_text' => __( 'Follow', 'fw' ),
						'desc'         => __( 'None keeps the Instagram-blue Follow button. Pick a Button Preset (Theme Settings → General → Buttons) to match the rest of the site.', 'fw' ),
					),
				),
			),
		),
	),

	'tab_layout' => array(
		'title'   => __( 'Layout', 'fw' ),
		'type'    => 'tab',
		'options' => array(
			'group_layout' => array(
				'type'    => 'group',
				'options' => array(
					'layout'          => array(
						'label'   => __( 'Layout', 'fw' ),
						'type'    => 'select',
						'value'   => 'slider',
						'choices' => array(
							'grid'   => __( 'Grid (all posts at once)', 'fw' ),
							'slider' => __( 'Slider (pages with arrows)', 'fw' ),
						),
					),
					'count'           => array(
						'label'   => __( 'Number of Posts', 'fw' ),
						'type'    => 'select',
						'value'   => '16',
						'choices' => array( '4' => '4', '6' => '6', '8' => '8', '9' => '9', '12' => '12', '16' => '16', '20' => '20', '24' => '24' ),
						'desc'    => __( 'For a connected account, the latest posts. Chosen images show in their own order.', 'fw' ),
					),
					'rows'            => array(
						'label'   => __( 'Rows', 'fw' ),
						'type'    => 'select',
						'value'   => '2',
						'choices' => array( '1' => '1', '2' => '2', '3' => '3' ),
						'desc'    => __( 'Slider only: how many rows each page shows.', 'fw' ),
					),
					'columns'         => array(
						'label'   => __( 'Columns (Desktop)', 'fw' ),
						'type'    => 'select',
						'value'   => '4',
						'choices' => $igf_cols,
					),
					'columns_tablet'  => array(
						'label'   => __( 'Columns (Tablet)', 'fw' ),
						'type'    => 'select',
						'value'   => '3',
						'choices' => $igf_cols,
					),
					'columns_mobile'  => array(
						'label'   => __( 'Columns (Phone)', 'fw' ),
						'type'    => 'select',
						'value'   => '2',
						'choices' => $igf_cols,
					),
					'ratio'           => array(
						'label'   => __( 'Tile Shape', 'fw' ),
						'type'    => 'select',
						'value'   => '1-1',
						'choices' => array(
							'1-1' => __( 'Square', 'fw' ),
							'4-5' => __( 'Portrait 4:5 (Instagram feed)', 'fw' ),
							'9-16' => __( 'Tall 9:16 (Reels)', 'fw' ),
						),
					),
					'gap'             => array(
						'label'   => __( 'Gap Between Tiles', 'fw' ),
						'type'    => 'select',
						'value'   => '0',
						'choices' => array( '0' => __( 'None', 'fw' ), '2' => '2px', '6' => '6px', '12' => '12px', '20' => '20px' ),
					),
					'rounding'        => array(
						'label'   => __( 'Tile Corners', 'fw' ),
						'type'    => 'select',
						'value'   => 'none',
						'choices' => array( 'none' => __( 'Square', 'fw' ), 'small' => __( 'Slightly rounded', 'fw' ), 'medium' => __( 'Rounded', 'fw' ), 'large' => __( 'Very rounded', 'fw' ) ),
					),
					'max_width'       => array(
						'label' => __( 'Maximum Width', 'fw' ),
						'type'  => 'unit-input',
						'units' => array( 'px', '%', 'rem', 'vw' ),
						'value' => array( 'value' => '', 'unit' => 'px' ),
						'desc'  => __( 'Leave empty to fill the column.', 'fw' ),
					),
					'align'           => array(
						'label'   => __( 'Alignment', 'fw' ),
						'type'    => 'select',
						'value'   => 'center',
						'choices' => array( 'left' => __( 'Left', 'fw' ), 'center' => __( 'Center', 'fw' ), 'right' => __( 'Right', 'fw' ) ),
						'desc'    => __( 'Where the feed sits when a Maximum Width is set.', 'fw' ),
					),
					'show_type_icon'  => array(
						'label'        => __( 'Reel & Album Icons', 'fw' ),
						'type'         => 'switch',
						'value'        => 'yes',
						'left-choice'  => array( 'value' => 'no', 'label' => __( 'Hide', 'fw' ) ),
						'right-choice' => array( 'value' => 'yes', 'label' => __( 'Show', 'fw' ) ),
					),
					'hover'           => array(
						'label'   => __( 'Hover Effect', 'fw' ),
						'type'    => 'select',
						'value'   => 'zoom',
						'choices' => array(
							'zoom'    => __( 'Zoom and darken', 'fw' ),
							'darken'  => __( 'Darken only', 'fw' ),
							'none'    => __( 'None', 'fw' ),
						),
					),
					'new_tab'         => array(
						'label'        => __( 'Open Instagram in a New Tab', 'fw' ),
						'type'         => 'switch',
						'value'        => 'yes',
						'left-choice'  => array( 'value' => 'no', 'label' => __( 'No', 'fw' ) ),
						'right-choice' => array( 'value' => 'yes', 'label' => __( 'Yes', 'fw' ) ),
					),
					'frame'           => array(
						'label'        => __( 'White Frame', 'fw' ),
						'type'         => 'switch',
						'value'        => 'yes',
						'left-choice'  => array( 'value' => 'no', 'label' => __( 'No', 'fw' ) ),
						'right-choice' => array( 'value' => 'yes', 'label' => __( 'Yes', 'fw' ) ),
						'desc'         => __( 'A white panel behind the header, like an Instagram profile.', 'fw' ),
					),
				),
			),
		),
	),

	'tab_animation' => array(
		'title'   => __( 'Animations', 'fw' ),
		'type'    => 'tab',
		'options' => sc_get_animation_fields(),
	),
	'tab_advanced'  => array(
		'title'   => __( 'Advanced', 'fw' ),
		'type'    => 'tab',
		'options' => array(
			'advanced_settings' => array(
				'type'    => 'group',
				'options' => sc_get_advanced_tab(),
			),
		),
	),
);
