<?php
/**
 * Plugin Name: SocialMedia Newsbox
 * Plugin URI: 
 * Description: Showing the last X news from SocialMedia Accounts, like Facebook or Twitter.
 *
 * Version: 0.1
 *
 * Author: Anna Fischer
 * Author URI: http://hdroblog.anna-fischer.info/
 *
 * License: GNU General Public License v2.0
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

require_once('facebook-sdk/facebook.php');
require_once('twitteroauth/twitteroauth.php');

require_once('smn-widget.php');
require_once('smn-admin.php');

class SocialMediaNewsbox {

	public static
		$optiontag = 'smn_options';

	private static
		$arySettings = array(
			'smn_tweet_number' => 1,
			'smn_fbpost_number' => 1,
			'shortcode' => true
		);

	/**
	* Constructor
	*
	* @since 0.1
	**/
	function __construct() {
		self::constants();

		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_uninstall_hook( __FILE__, array( $this, 'uninstall' ) );

		// Add different admin settings and admin menu
		add_action( 'admin_init', array( 'SocialMediaNewsboxGUI', 'smn_admin_init') );
		add_action( 'admin_menu', array( $this, 'buildAdminMenu' ) );

		// Settings changed?
		if (isset($_POST['action']) && $_POST['action'] == 'save_smn_options')
			SocialMediaNewsboxGUI::saveSettings();

		// Add meta links to plugin details
		add_filter( 'plugin_action_links', array( $this, 'set_plugin_meta' ), 10, 2 );

		// Enable shortcodes if enabled
		if (self::$arySettings['shortcode'])
			add_shortcode( 'socialmedianews', array( $this, 'smn_shortcode') );

		// Register style sheet.
		add_action( 'wp_enqueue_scripts', array( $this, 'register_plugin_styles' ) );

		// Load language file
		load_plugin_textdomain( 'SMNlanguage', false, SMN_LANG_URL );
	}

	/**
    * Defines constants used by the plugin
    *
    * @since 0.1
    */
    function constants() {
        define( 'SMN_VERSION', '0.1' );
        define( 'SMN_PLUGIN_URL', trailingslashit( plugin_dir_url( __FILE__ ) ) );
        define( 'SMN_PLUGIN_NAME', plugin_basename( __FILE__ ) );
        define( 'SMN_PLUGIN_DIR', dirname( plugin_basename( __FILE__ ) ) );
        define( 'SMN_IMAGES_URL', trailingslashit( SMN_PLUGIN_URL . 'img' ) );
        define( 'SMN_LANG_URL', trailingslashit( SMN_PLUGIN_DIR . '/languages' ) );
    }

    /**
    * Checks the optiontag and possibly set the default options
    *
    * @since 0.1
    */
    function check_options() {
    	#check to see if option already present
		if(!get_option(self::$optiontag)) {
			#Adds an option for saving the settings
			add_option( self::$optiontag, self::$arySettings, '', 'no' );
		} else {
			#option is already in the database
			#get the stored value, merge it with default and update
			$old_op = get_option(self::$optiontag);
			$new_op = wp_parse_args($old_op, self::$arySettings);
			update_option(self::$optiontag, $new_op);
		}
    }

	/**
	* Activation of the plugin
	*
	* @since 0.1
	**/
	function activate() {
		global $wp_version;
		if (version_compare(PHP_VERSION, '5.3', '<') && version_compare($wp_version, '3.4', '<')) {
			deactivate_plugins(SMN_PLUGIN_NAME); // Deactivate ourself
			wp_die(__('Sorry, but you can\'t run this plugin, it requires PHP 5.3 or higher and Wordpress version 3.4.2 or higher.'));
			return;
		}

		self::check_options();
	}

	/**
	* Uninstallation
	*
	* @global wpdb get access to the wordpress-database and to clean it after uninstallation
	* @since 0.1
	**/
	function uninstall() {
		global $wpdb;
		# delete the options
		delete_option(self::$optiontag);
		#clean up the database
		$wpdb->query("OPTIMIZE TABLE `" .$wpdb->options. "`");
	}

	/**
	* Send a message after installing/updating
	*
	* @since 0.1
	*/
	function updateMessage() {
		# success message after installation
		$strText = 'SocialMediaNewsbox '.SMN_VERSION.' '.__('installed','SMNlanguage').'.';

		$strSettings = __('Please update your configuration!','SMNlanguage');
		$strLink = sprintf('<a href="options-general.php?page=%s">%s</a>', SMN_PLUGIN_DIR, __('Settings', 'SMNlanguage'));
		
		# display information message for setting up the servers
		echo '<div class="updated"><p>'.$strText.' <strong>'.__('Important', 'SMNlanguage').':</strong> '.$strSettings.': '.$strLink.'.</p></div>';
	}

	/**
	* Add plugin meta links to plugin details
	*
	* @see http://wpengineer.com/1295/meta-links-for-wordpress-plugins/
	* @since 0.1
	*/
	function set_plugin_meta($links, $file) {
	
		/* create link */
		if ( $file == SMN_PLUGIN_NAME ) {
			array_unshift(
				$links,
				sprintf( '<a href="options-general.php?page=%s">%s</a>', SMN_PLUGIN_DIR, __('Settings', 'SMNlanguage') )
			);
		}
		
		return $links;
	}

	/**
	* Adds an option page for configuration
	*
	* @since 0.1
	**/
	function buildAdminMenu() {
		$intOptionsPage = add_options_page( __('Settings: SocialMedia Newsbox', 'SMNlanguage'), __('SocialMedia Newsbox', 'SMNlanguage'), 'manage_options', 'socialmedia-newsbox', array( 'SocialMediaNewsboxGUI', 'showAdminPage' ) );
	}

	/**
	* Register and enqueue style sheet.
	*
	* @since 0.1
	*/
	function register_plugin_styles() {
		wp_register_style( 'smn-style', plugins_url( 'socialmedia-newsbox/smn-style.css' ) );
		wp_enqueue_style( 'smn-style' );
	}

	/**
	* helperfunction to check if a domain is online/available
	*
	* @see http://www.selfphp.de/code_snippets/code_snippet.php?id=11
	* @return true/false if domain is available or not
	* @since 0.1
	**/
	static function domainAvailable ( $strDomain ) {
		$rCurlHandle = curl_init ( $strDomain );

		curl_setopt ( $rCurlHandle, CURLOPT_CONNECTTIMEOUT, 10 );
		curl_setopt ( $rCurlHandle, CURLOPT_HEADER, TRUE );
		curl_setopt ( $rCurlHandle, CURLOPT_NOBODY, TRUE );
		curl_setopt ( $rCurlHandle, CURLOPT_RETURNTRANSFER, TRUE );

		$strResponse = curl_exec ( $rCurlHandle );

		curl_close ( $rCurlHandle );

		if ( !$strResponse )
		{
			return FALSE;
		}

		return TRUE;
	}

	/**
	* helperfunction to get the latest tweet(s) of the given Account
	* 
	* @see http://shoogledesigns.com/blog/blog/2013/07/22/embedded-timelines-api-v1-1-oauth/
	* @return get the JSON output from the latest tweet(s)
	* @since 0.1
	*/
	function getTwitterAuth($consumer_key, $consumer_secret, $access_token, $access_token_secret, $tweet_number=1, $incl_RTs=false) {
		$connectionTW = new TwitterOAuth($consumer_key,$consumer_secret,$access_token,$access_token_secret);
		$tweets = $connectionTW->get('statuses/user_timeline', array('count' => $tweet_number, 'include_rts' => $incl_RTs));
		return $tweets;
	}

	/**
	* helperfunction to connect to facebook and access the page timeline of the given Account
	* 
	* @see https://github.com/facebook/facebook-php-sdk
	* @return get the JSON output from the latest tweet(s)
	* @since 0.1
	*/
	function getFacebookAuth($fbAppId, $fbAppSecret, $fbAccount) {
		$connectionFB = new Facebook( array('appId' => $fbAppId, 'secret' => $fbAppSecret) );
		$accountapi = $connectionFB->api('/'.$fbAccount);
		$access_token = $connectionFB->getAccessToken();
		$fbAuth = array('account' => $accountapi, 'token' => $access_token);
		return $fbAuth;
	}

	/**
	* Function to call and show the newslist.
	*
	* @return gives back the latest status post and the name of the social network
	* @since 0.1
	**/
	static function show_newslist($showFB, $showTW) {

		$options = get_option( self::$optiontag );

		if( isset( $options[0] ) && empty( $options[0] ) ) {
			unset( $options[0] );
		}

		if($showTW && isset($options['twitter_auth']) && !empty($options['twitter_auth'])) {
			$tweets = @self::getTwitterAuth($options['twitter_auth']['consumerkey'], 
										$options['twitter_auth']['consumersecret'],
										$options['twitter_auth']['accesstoken'],
										$options['twitter_auth']['accesssecret'],
										$options['smn_tweet_number']);
			foreach ($tweets as $key => $tweet) {
				//Links, Hashtags (#) und Verbindungen (@) verlinken
        		$tweettext = ereg_replace("[[:alpha:]]+://[^<>[:space:]]+[[:alnum:]/]","<a target='_blank' href=\"\\0\">\\0</a>",  $tweet->text);
        		$tweettext = preg_replace('/(^|\s)#(\w*[a-zA-Z_]+\w*)/', '\1<a target="_blank" href="https://twitter.com/search?q=%23\2&src=hash">#\2</a>', $tweettext);
        		$tweettext = preg_replace('/[@]+([A-Za-z0-9-_]+)/', '<a target="_blank" href="http://twitter.com/$1" target="_blank">@$1</a>', $tweettext );
        		$tweetarray[$key] = array('text' => $tweettext, 'date' => $tweet->created_at);
        	}
		}

		if($showFB && isset($options['facebook_auth']) && !empty($options['facebook_auth'])) {
			$fbAuth = @self::getFacebookAuth($options['facebook_auth']['appid'], $options['facebook_auth']['appsecret'], $options['smn_facebook_id']);
			# check the facebook graph url
			$jsonurl = 'https://graph.facebook.com/'.$fbAuth['account']['username'].'/feed?access_token='.$fbAuth['token'];
			if( self::domainAvailable($jsonurl) ) {
				$json = file_get_contents($jsonurl,0,null,null);
				$json_output = json_decode($json);
				for ($i=0; $i < $options['smn_fbpost_number']; $i++) { 
					//Links und Hashtags verlinken
	        		$fbpost = ereg_replace("[[:alpha:]]+://[^<>[:space:]]+[[:alnum:]/]","<a target='_blank' href=\"\\0\">\\0</a>",  $json_output->data[$i]->message);
	        		$fbpost = preg_replace('/(^|\s)#(\w*[a-zA-Z_]+\w*)/', '\1<a target="_blank" href="https://www.facebook.com/hashtag/\2">#\2</a>', $fbpost);
	        		$postarray[$i] = array('text' => $fbpost, 'date' => $json_output->data[$i]->created_time);
				}				
			}
		}

		if($showFB || $showTW) {
			$listoutput = '<div class="smn_container">';
			if(isset($postarray)) {
				$listoutput .= '<div class="smn_facebook">';
				foreach ($postarray as $post) {
					$listoutput .= '<p>';
					$listoutput .= date('d.m.Y', strtotime(strstr($post['date'], 'T', true))).' / ';
					$listoutput .= date('H:i', strtotime(strstr($post['date'], 'T', false)));
					$listoutput .= ' - '.$post['text'];
					$listoutput .= '</p>';
				}
				$listoutput .= '</div>';
			}
			if(isset($tweetarray)) {
				$listoutput .= '<div class="smn_twitter">';
				foreach ($tweetarray as $tweet) {
					$listoutput .= '<p>'.date('d.m.Y / H:i', strtotime($tweet['date'])).' - '.$tweet['text'].'</p>';
				}
				$listoutput .= '</div>';
			}
			$listoutput .= '</div>';
		} else {
			$listoutput = '';
		}

		return $listoutput;
	}

	/**
	* the shortcode (UNDER DEVELOPMENT - not yet implemented)
	*/
	function smn_shortcode($atts) {

		# extract the attributes into variables
		extract(shortcode_atts(array(
			'loc' => 'all'
		), $atts));

	   return self::show_newslist($loc);

	}

}

/**
* Function to register the Widget
*
* @since 0.9.8
*/
function smn_register_widgets() {
	register_widget( 'SocialMediaNewsboxWidget' );
}
add_action( 'widgets_init', 'smn_register_widgets' );

/* start the plugin */
new SocialMediaNewsbox;
