<?php
/*
	Plugin Name: Audio Player
	Demo: http://audioplayer.ahansson.com
	Description: Audio Player Section with custom post types and ability to play local MP3/OGG, Podcasts or SoundCloud tracks and playlists.
	Version: 100.7.1
	Author: Aleksander Hansson
	Author URI: http://ahansson.com
	v3: true
*/

class ah_AudioPlayer_Plugin {

	function __construct() {
		add_action( 'init', array( &$this, 'ah_updater_init' ) );
	}

	function ah_updater_init() {

		require_once( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'includes/plugin-updater.php' );

		$config = array(
			'base'      => plugin_basename( __FILE__ ), 
			'repo_uri'  => 'http://shop.ahansson.com',  
			'repo_slug' => 'audio-player',
		);

		new AH_AudioPlayer_Plugin_Updater( $config );
	}

}

new ah_AudioPlayer_Plugin; 