<?php
/*
Section: Audio Player
Author: Aleksander Hansson
Author URI: http://ahansson.com
Description: Audio Player Section with custom post types and ability to play local MP3/OGG, Podcasts or SoundCloud tracks and playlists.
Workswith: main, templates
Class Name: AudioPlayer
Cloning: true
Demo: http://audioplayer.ahansson.com
V3: true
*/

class AudioPlayer extends PageLinesSection {

	var $ptID = 'audio-player';
	var $taxID = 'audio-player-playlists';
	const version = '1.0';

	function section_persistent(){

		add_filter( 'upload_mimes', array(&$this, 'custom_mimes' ) );

		$this->post_type_setup();
		$this->post_meta_setup();

	}

	function custom_mimes( $mimes ){
		$mimes['ogg'] = 'audio/ogg';
	    return $mimes;
	}

	function section_scripts() {

		wp_enqueue_script( 'jquery' );

		wp_enqueue_script( 'jquery-mousewheel', $this->base_url.'/js/jquery.mousewheel.min.js' );

		wp_enqueue_script( 'soundmanager2-nodebug', $this->base_url.'/js/soundmanager2-nodebug-jsmin.js' );

		wp_enqueue_script( 'jquery-html5audio', $this->base_url.'/js/jquery.html5audio.js' );

		wp_enqueue_script( 'jquery-apPlaylistManager', $this->base_url.'/js/jquery.apPlaylistManager.min.js' );

		wp_enqueue_script( 'jquery-apTextScroller', $this->base_url.'/js/jquery.apTextScroller.min.js' );

	}


	function section_head( ){

		$clone_id = $this->get_the_id();

		$prefix = ($clone_id != '') ? 'Clone'.$clone_id : '';

		$buttons_path = $this->base_url.'/img/';

		$soundcloud_api = ( ploption( 'ap_soundcloud_api', $this->oset ) ) ? ploption( 'ap_soundcloud_api', $this->oset ) : '';

			?>

				<script type="text/javascript">

				jQuery(document).ready(function($) {

					/* CALLBACKS */

					// SETTINGS

					var ap_settings = {
						/* playlistHolder: dom elements which holds list of playlists */
						playlistHolder: '#playlist_list<?php echo $prefix; ?>',
						/* activePlaylist: set active playlist that will be loaded on beginning.
						Leave empty for none like this: activePlaylist: '' */
						activePlaylist: '#playlist<?php echo $prefix; ?>',
						/* sound_id: unique string for soundmanager sound id (if multiple player instances were used, then strings need to be different) */
						sound_id: 'sound_id<?php echo $prefix; ?>',
						/* activeItem: active item to start with when playlist is loaded (0 = first, 1 = second, 2 = third... -1 = none) */
						activeItem: 0,
						/* soundcloudApiKey: If you want to use SoundCloud music, register you own api key here for free:
						'http://soundcloud.com/you/apps/new' and enter Client ID */
						soundcloudApiKey: '<?php echo $soundcloud_api; ?>',

						/*defaultVolume: 0-1 (Irrelevant on ios mobile) */
						defaultVolume:0.5,
						/*autoPlay: true/false (false on mobile by default) */
						autoPlay:false,
						/*autoLoad: true/false (auto start sound load) */
						autoLoad:true,
						/*randomPlay: true/false */
						randomPlay:false,
						/*loopingOn: true/false (loop on the end of the playlist) */
						loopingOn:true,

						/* autoSetSongTitle: true/false. Auto set song title in 'player_mediaName'. */
						autoSetSongTitle: true,

						/* useSongNameScroll: true/false. Use song name scrolling. */
						useSongNameScroll: true,
						/* scrollSpeed: speed of the scroll (number higher than zero). */
						scrollSpeed: 1,
						/* scrollSeparator: String to append between scrolling song name. */
						scrollSeparator: '&nbsp;&#42;&#42;&#42;&nbsp;',

						/* mediaTimeSeparator: String between current and total song time. */
						mediaTimeSeparator: '&nbsp;-&nbsp;',
						/* seekTooltipSeparator: String between current and total song position, for progress tooltip. */
						seekTooltipSeparator: '&nbsp;/&nbsp;',

						/* defaultArtistData: Default text for song media name. */
						defaultArtistData: 'Artist&nbsp;Name&nbsp;-&nbsp;Artist&nbsp;Title',

						/* buttonsUrl: url of the buttons for normal and rollover state */
						buttonsUrl: {prev: "<?php printf('%sbackwards.png', $buttons_path ); ?>", prevOn: "<?php printf('%sbackwardsOn.png', $buttons_path ); ?>",
									next: "<?php printf('%sforward.png', $buttons_path ); ?>", nextOn: "<?php printf('%sforwardOn.png', $buttons_path ); ?>",
									pause: "<?php printf('%spause.png', $buttons_path ); ?>", pauseOn: "<?php printf('%spauseOn.png', $buttons_path ); ?>",
									play: "<?php printf('%splay.png', $buttons_path ); ?>", playOn: "<?php printf('%splayOn.png', $buttons_path ); ?>",
									volume: "<?php printf('%svolume.png', $buttons_path ); ?>", volumeOn: "<?php printf('%svolumeOn.png', $buttons_path ); ?>",
									mute: "<?php printf('%smute.png', $buttons_path ); ?>", muteOn: "<?php printf('%smuteOn.png', $buttons_path ); ?>",
									loop: "<?php printf('%srepeat.png', $buttons_path ); ?>", loopOn: "<?php printf('%srepeatOn.png', $buttons_path ); ?>",
									shuffle: "<?php printf('%srandom.png', $buttons_path ); ?>", shuffleOn: "<?php printf('%srandomOn.png', $buttons_path ); ?>"},
						/* useAlertMessaging: Alert error messages to user (true / false). */
						useAlertMessaging: true,

						/* activatePlaylistScroll: true/false. activate jScrollPane. */
						activatePlaylistScroll: false
					};

					//sound manager settings (http://www.schillmania.com/projects/soundmanager2/)
					soundManager.setup({
						url: 'swf/', // path to SoundManager2 SWF files
						allowScriptAccess: 'always',
						debugMode: false,
						noSWFCache: true,
						useConsole: false,
						waitForWindowLoad: true,
						flashVersion: 9,
						useFlashBlock: true,
						preferFlash: false,
						useHTML5Audio: true
					});

					var audio = document.createElement('audio'), mp3Support, oggSupport;
					if (audio.canPlayType) {
						mp3Support = !!audio.canPlayType && "" != audio.canPlayType('audio/mpeg');//setting this will use html5 audio on all html5 audio capable browsers ('modern browsers'), flash on the rest ('older browsers')
					//mp3Support=true;//setting this will use html5 audio on modern browsers that support 'mp3', flash on the rest of modern browsers that support 'ogv' like firefox and opera, and of course flash on the rest ('older browsers') (USE THIS SETTING WHEN USING PODCAST AND SOUNDCLOUD!)
						oggSupport = !!audio.canPlayType && "" != audio.canPlayType('audio/ogg; codecs="vorbis"');
					}else{
						//for IE<9
						mp3Support = true;
						oggSupport = false;
					}
					//console.log('mp3Support = ', mp3Support, ' , oggSupport = ', oggSupport);

					soundManager.audioFormats = {
						'mp3': {
							'type': ['audio/mpeg; codecs="mp3"', 'audio/mpeg', 'audio/mp3', 'audio/MPA', 'audio/mpa-robust'],
							'required': mp3Support
						},
						'mp4': {
							'related': ['aac','m4a'],
							'type': ['audio/mp4; codecs="mp4a.40.2"', 'audio/aac', 'audio/x-m4a', 'audio/MP4A-LATM', 'audio/mpeg4-generic'],
							'required': false
						},
						'ogg': {
							'type': ['audio/ogg; codecs=vorbis'],
							'required': oggSupport
						},
						'wav': {
							'type': ['audio/wav; codecs="1"', 'audio/wav', 'audio/wave', 'audio/x-wav'],
							'required': false
						}
					};

					//player instances
					var player<?php echo $prefix; ?>;

					//init component
					player<?php echo $prefix; ?> = $('.componentWrapper<?php echo $prefix; ?>').html5audio(ap_settings);
					ap_settings = null;

				});

				</script>
			<?php
	}

	function section_template() {

		$clone_id = $this->get_the_id();

		$prefix = ($clone_id != '') ? 'Clone'.$clone_id : '';

		$buttons_path = $this->base_url.'/img/';

		$playlist_image = ( ploption( 'ap_playlist_image', $this->oset ) ) ? ploption( 'ap_playlist_image', $this->oset ) : null;

		$playlist_description =  do_shortcode( ploption( 'ap_playlist_description', $this->oset ) ) ? ploption( 'ap_playlist_description', $this->oset ) : null;

		$playerspan = '12';

		?>

			<div class="row">

				<?php

					if ($playlist_image || $playlist_description) {

						$playerspan = '9';

						?>

							<div class="span3 ap_playlist_details">

									<?php

										if ($playlist_image) {

											printf('<div class="ap_playlist_image_container"><div class="ap_playlist_image" style="background-image:url(%s);"></div></div>', $playlist_image);

										}
									?>

									<?php


										if ($playlist_description) {

											printf('<div class="ap_playlist_description_container"><div class="ap_playlist_description">%s</div></div>', $playlist_description);

										}

									?>

							</div>

						<?php
					}


				printf('<div id="componentWrapper" class="span%s componentWrapper%s">', $playerspan, $prefix);

				?>

					<div class="playerHolder">

						<div class="row zmb">
							<!-- song name -->
							<div class="player_mediaName_Mask span6 zmb">
								<div class="player_mediaName"></div>
							</div>

							<!-- song time -->
							<div class="player_mediaTime span2 offset1 zmb">
								<!-- current time and total time are separated so you can change the design if needed. -->
								<span class="player_mediaTime_current"></span><span class="player_mediaTime_total"></span>
							</div>
						</div>

						<div class="player_controls">
						<!-- previous -->
						<div class="controls_prev">
							<?php printf("<img src='%sbackwards.png' width='25' height='26' alt='controls_prev'/>", $buttons_path); ?>
						</div>
						<!-- pause/play -->
						<div class="controls_toggle">
							<?php printf("<img src='%splay.png' width='30' height='31' alt='controls_toggle'/>", $buttons_path); ?>
						</div>
						<!-- next -->
						<div class="controls_next">
							<?php printf("<img src='%sforward.png' width='25' height='26' alt='controls_next'/>", $buttons_path); ?>
						</div>

						<!-- volume -->
						<div class="player_volume">
							<?php printf("<img src='%svolume.png' width='25' height='26' alt='player_volume'/>", $buttons_path); ?>
						</div>
						<div class="volume_seekbar progress">
							<div class="volume_bg"></div>
							<div class="volume_level bar"></div>
						</div>

						<!-- loop -->
						<div class="player_loop">
							<?php printf("<img src='%srepeat.png' width='25' height='26' alt='player_loop'/>", $buttons_path); ?>
						</div>
						<!-- shuffle -->
						<div class="player_shuffle">
							<?php printf("<img src='%srandom.png' width='25' height='26' alt='player_shuffle'/>", $buttons_path); ?>
						</div>
					</div>

						<!-- progress -->
						<div class="player_progress progress center">
							<div class="progress_bg"></div>
							<div class="play_progress bar"></div>
						</div>

						<div class="player_bottom_space"></div>



						<!-- volume tooltip -->
						<div class="player_volume_tooltip"><p></p></div>

						<!-- progress tooltip -->
						<div class="player_progress_tooltip"><p></p></div>

					</div>

					<div class="playlistHolder">
						<div class="componentPlaylist">
							<div class="playlist_inner">
								<!-- playlist items are appended here! -->
							</div>
						</div>
						<!-- preloader -->
						<div class="preloader"></div>
					</div>
				</div>

			</div>

			<div id="playlist_list<?php echo $prefix; ?>" class="playlist_list">
				<ul id='playlist<?php echo $prefix; ?>'>

					<?php

						$playlist = ( ploption( 'ap_playlist_select', $this->oset ) ) ? ploption( 'ap_playlist_select', $this->oset ) : null;
//						$orderby = ( ploption( 'ap_playlist_orderby', $this->oset ) ) ? ploption( 'ap_playlist_orderby', $this->oset ) : 'menu_order';
//						$order = ( ploption( 'ap_playlist_order', $this->oset ) ) ? ploption( 'ap_playlist_order', $this->oset ) : 'ASC';

						$args = array(
							'post_type'	=> $this->ptID,
							'post_status'   => 'publish',
//							'orderby' => $orderby,
//							'order'=> $order,
							$this->taxID => $playlist,
						);

						$loop = new WP_Query( $args );

						while ( $loop->have_posts() ) : $loop->the_post();

							$this->draw_playlist();

						endwhile;

						wp_reset_query();

					?>

				</ul>

			</div>

		<?php

	}

	function draw_playlist() {

		global $post;

		$cover = ( get_post_meta( $post->ID, 'single_ap_image', $this->oset ) );

		$title = ( get_the_title( $post->ID ) ) ? get_the_title( $post->ID ) : 'Audio Track has not title' ;

		$mp3 = ( get_post_meta( $post->ID,'single_ap_mp3', $this->oset ) );

		$ogg = ( get_post_meta( $post->ID,'single_ap_ogg', $this->oset ) );

		$link = ( get_post_meta( $post->ID,'single_ap_button_link', $this->oset ) );

		$link_text = ( get_post_meta( $post->ID,'single_ap_button_text', $this->oset ) );

		$soundcloud = ( get_post_meta( $post->ID,'single_ap_soundcloud', $this->oset ) );

		$podcast = ( get_post_meta( $post->ID,'single_ap_podcast', $this->oset ) );

		$type = ( get_post_meta( $post->ID,'single_ap_type', $this->oset ) ) ? get_post_meta( $post->ID,'single_ap_type', $this->oset ) : 'local' ;

		?>

			<li class= "playlistItem"

				<?php

					printf('data-type="%s"', $type);

					if ( $type == 'local' ) {
						if ($mp3) {
						printf( 'data-mp3Path="%s"', $mp3 );
						}
						if ($ogg) {
							printf( 'data-oggPath="%s"', $ogg );
						}
					} elseif ( $type == 'soundcloud' ) {
						printf( 'data-path="%s"', $soundcloud );
					} elseif ( $type == 'podcast' ) {
						printf( 'data-path="%s"', $podcast );
					}

				?>
			>
				<?php

					if ( $type == 'local' ) {
						printf( '<a class="playlistNonSelected" href="#">%s</a>', $title );
					}

					if ($link && $link_text) {
						printf( '<a class="btn btn-primary" href="%s" target="_blank">%s</a>', $link, $link_text );
					}
				?>

			</li>

		<?php

	}

	function section_optionator($settings) {
		$settings = wp_parse_args($settings, $this->optionator_default);

		$tab = array(

			'ap_playlist_select' => array(
				'default'		=> '',
				'type' 			=> 'select_taxonomy',
				'taxonomy_id'	=> $this->taxID,
				'inputlabel'	=> __( 'Playlist to show', 'AudioPlayer' ),
			),

//			'ap_playlist_orderby' => array(
//				'inputlabel' => 'Order According To (Default: Menu Order)',
//				'type' => 'select',
//				'selectvalues' => array(
//					'title'   => array( 'name' => "Using Audio Track Title" ),
//					'post_date'  => array( 'name' => "Using Post Date" ),
//					'rand'   => array( 'name' => "Random Selection" ),
//					'ID'   => array( 'name' => "Audio Track ID" ),
//				)
//			),

//			'ap_playlist_order'  => array(
//				'inputlabel' => 'Order Type (Default: ASC)',
//				'type' => 'select',
//				'selectvalues' => array(
//					'ASC'   => array( 'name' => "Ascending Order" ),
//					'DESC'   => array( 'name' => "Descending Order" ),
//				)
//			),

			'ap_playlist_image'  => array(
				'inputlabel'  => __( 'Playlist Image', 'AudioPlayer' ),
				'type'   => 'image_upload',
				'title'   => __( 'Playlist Image', 'AudioPlayer' ),
				'shortexp'   => __( 'Upload a playlist image... </br>Recommended image size: 160x160</br>Images will scale to match the size of the image area, not crop.', 'AudioPlayer' )
			),

			'ap_playlist_description'  => array(
				'inputlabel'  => __( 'Playlist Description', 'AudioPlayer' ),
				'type'   => 'textarea',
				'title'   => __( 'Playlist Description', 'AudioPlayer' ),
				'shortexp'   => __( 'Type in your description of your playlist...', 'AudioPlayer' )
			),

			'ap_soundcloud_api'  => array(
				'inputlabel'  => __( 'SoundCloud API Key', 'AudioPlayer' ),
				'type'   => 'text',
				'title'   => __( 'SoundCloud API Key', 'AudioPlayer' ),
				'shortexp'   => __( 'If you want to use SoundCloud music, register your own api key <a href="http://soundcloud.com/you/apps/new" target="_blank">here for free</a> and enter Client ID', 'AudioPlayer' )
			),

//			'ap_directions'	=> array(
//				'type'		=> '',
//				'title'	=> __('<strong style="display:block;font-size:16px;color:#eaeaea;text-shadow:0 1px 0 black;padding:7px 7px 5px;background:#333;margin-top:5px;border-radius:3px;border:1px solid white;letter-spacing:0.1em;box-shadow:inset 0 0 3px black;">HOW TO USE:</strong>', 'AudioPlayer'),
//				'shortexp'   => __('', 'AudioPlayer'),
//			),

		);

		$tab_settings = array(
			'id'		=> 'audioplayer_meta',
			'name'	=> 'Audio Player',
			'icon'	=> $this->icon,
			'clone_id'  => $settings['clone_id'],
			'active'	=> $settings['active']
		);

		register_metatab( $tab_settings, $tab);
	}

	function post_type_setup(){

		$args = array(
			'label'			=> __('Audio Tracks', 'AudioPlayer'),
			'singular_label'	=> __('Audio Track', 'AudioPlayer'),
			'description'	=> __('For creating Audio Tracks', 'AudioPlayer'),
			'menu_icon'		=> $this->icon,
			'supports'		=> array('title'),
		);
		$taxonomies = array(
			$this->taxID => array(
				"label" => __('Playlists', 'AudioPlayer'),
				"singular_label" => __('Playlist', 'AudioPlayer'),
			)
		);

		$columns = array(
			"cb"			=> "<input type=\"checkbox\" />",
			"title"		=> __('Title', 'AudioPlayer'),
			"description"   => __('Text', 'AudioPlayer'),
			"event-categories"	=> __('Playlists', 'AudioPlayer'),
		);

		$this->post_type = new PageLinesPostType( $this->ptID, $args, $taxonomies,$columns,array(&$this, 'column_display'));

	}


	function post_meta_setup(){

		$type_meta_array = array(

			'single_ap_type'  => array(
				'default'       => '',
				'type'           => 'select',
				'selectvalues'     => array(
					'local' => array( 'name' => __( 'Local'   , 'AudioPlayer' )),
					'soundcloud' => array( 'name' => __( 'SoundCloud'   , 'AudioPlayer' )),
					'podcast' => array( 'name' => __( 'Podcast'   , 'AudioPlayer' ))
				),
				'inputlabel'  =>  __('Choose Audio Type', 'AudioPlayer'),
				'title'      => __( 'Audio Type (Required)', 'AudioPlayer' ),
				'shortexp'      => __( 'You can choose from Local, SoundCloud and Podcast.', 'AudioPlayer' ),
				'exp'      => __( 'Mixed sources is not recommended but is possible!', 'AudioPlayer' )
			),

			'single_ap_local_options' => array(
				'type' => 'multi_option',
				'title' => __('Local Audio Track', 'AudioPlayer'),
				'shortexp' => __('Details for Local Audio Track goes here.', 'AudioPlayer'),
				'selectvalues' => array(

					'single_ap_mp3'  => array(
						'inputlabel'  => __( 'MP3 file', 'AudioPlayer' ),
						'type'   => 'text',
						'title'   => __( 'Link to local files', 'AudioPlayer' ),
						'shortexp'   => __( 'Use the Wordpress Media Uploader and copy links to these fields.', 'AudioPlayer' )
					),

					'single_ap_ogg'  => array(
						'inputlabel'  => __( 'OGG file', 'AudioPlayer' ),
						'type'   => 'text',
					),

					'single_ap_button_link'  => array(
						'title' => __('Button options', 'AudioPlayer'),
						'shortexp' => __('Details for the button goes here', 'AudioPlayer'),
						'inputlabel'  => __( 'Button links to...', 'AudioPlayer' ),
						'type'   => 'text',
					),

					'single_ap_button_text'  => array(
						'inputlabel'  => __( 'Button text...', 'AudioPlayer' ),
						'type'   => 'text',
					),
				),
			),

			'single_ap_soundcloud_options' => array(
				'type' => 'multi_option',
				'title' => __('SoundCloud Audio', 'AudioPlayer'),
				'shortexp' => __('Details for SoundCloud Audio', 'AudioPlayer'),
				'selectvalues' => array(
					'single_ap_soundcloud'  => array(
						'inputlabel'  => __( 'Link to SoundCloud...', 'AudioPlayer' ),
						'type'   => 'text',
						'title'   => __( 'SoundCloud link', 'AudioPlayer' ),
						'shortexp'   => __( 'Details for SoundCloud Audio goes here. (Remember to input your SoundCloud API key in the Section Options)', 'AudioPlayer' )
					),
				),
			),

			'single_ap_podcast_options' => array(
				'type' => 'multi_option',
				'title' => __('Podcast Audio', 'AudioPlayer'),
				'shortexp' => __('Details for Podcast Audio', 'AudioPlayer'),
				'selectvalues' => array(
					'single_ap_podcast'  => array(
						'inputlabel'  => __( 'Link to Podcast...', 'AudioPlayer' ),
						'type'   => 'text',
						'title'   => __( 'Podcast link', 'AudioPlayer' ),
						'shortexp'   => __( 'Details for Podcast Audio goes here.</br>If your playlist appear blank and the player does not play anything, then you did not type in a valid podcast link. A podcast is NOT a MP3 file, but for example a .xml file like this: "http://feeds.feedburner.com/dumbassguide?format=xml"', 'AudioPlayer' )
					),
				),
			),

//			'single_ap_directions'	=> array(
//				'type'		=> '',
//				'title'	=> __('<strong style="display:block;font-size:16px;color:#eaeaea;text-shadow:0 1px 0 black;padding:7px 7px 5px;background:#333;margin-top:5px;border-radius:3px;border:1px solid white;letter-spacing:0.1em;box-shadow:inset 0 0 3px black;">HOW TO USE:</strong>', 'AudioPlayer'),
//				'shortexp'   => __('', 'AudioPlayer'),
//			),
		);

		$post_types = array($this->id);

		$type_metapanel_settings = array(
			'id'		=> 'audioplayer-metapanel',
			'name'	=> 'Single Audio Track Options',
			'posttype'  => $post_types,
		);

		global $p_meta_panel;

		$p_meta_panel =  new PageLinesMetaPanel( $type_metapanel_settings );

		$type_metatab_settings = array(
			'id'		=> 'audioplayer-type-metatab',
			'name'	=> 'Single Audio Track Options',
			'icon'	=> $this->icon
		);

		$p_meta_panel->register_tab( $type_metatab_settings, $type_meta_array );

	}

	function column_display($column){
        global $post;

        switch ($column){
            case "description":
                the_excerpt();
                break;
            case "event-categories":
                $this->get_tags();
                break;
        }
    }

    // fetch the tags for the columns in admin
    function get_tags() {
        global $post;

        $terms = wp_get_object_terms($post->ID, $this->taxID);
        $terms = array_values($terms);

        for($term_count=0; $term_count<count($terms); $term_count++) {

            echo $terms[$term_count]->slug;

            if ($term_count<count($terms)-1){
                echo ', ';
            }
        }
    }

}
