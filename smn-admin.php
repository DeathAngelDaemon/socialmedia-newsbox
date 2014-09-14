<?php

/* Sicherheitsabfrage */
if ( ! class_exists('SocialMediaNewsbox') ) {
	die();
}

/**
* GUI for the SMN Adminpage
*
* @since 0.9.8
*/
class SocialMediaNewsboxGUI extends SocialMediaNewsbox {

	/**
	* Saves all changes on the configuration
	*
	* @since 0.1
	*/
	public function saveSettings() {

		if ( empty($_POST) && !check_admin_referer( 'save_smn_options', '_wpnonce-socialmedianewsbox' ) ) {
			wp_die('No form successful transmitted.');
		}

		# Update Settings on Save
		if( $_POST['action'] == 'save_smn_options' ) {
			$options = get_option(parent::$optiontag);
			if (!empty($_POST['smn_useDefaults'])) {
				delete_option('smn_options');
				parent::check_options();
				$_POST['notice'] = __( 'The settings are set back to default.', 'SMNlanguage' );
			} elseif (!empty($_POST['smn_deleteCache'])) {
				file_put_contents(SMN_CACHE_FB_URL, '');
				file_put_contents(SMN_CACHE_TW_URL, '');
				$_POST['notice'] = __( 'The caches are now deleted.', 'SMNlanguage' );
			} else {
				foreach ($_POST['smn_general'] as $key => $value) {
					$options['general'][''.$key.''] = $value;
				}
				$options['smn_facebook_id'] = $_POST['smn_fb_feedid'];
				if($options['smn_fbpost_number'] !== $_POST['smn_fb_postno']) {
					$options['smn_fbpost_number'] = $_POST['smn_fb_postno'];
					file_put_contents(SMN_CACHE_FB_URL, '');
				}
				$options['smn_twitter_account'] = $_POST['smn_tw_account'];
				if($options['smn_tweet_number'] !== $_POST['smn_tw_tweetno']) {
					$options['smn_tweet_number'] = $_POST['smn_tw_tweetno'];
					file_put_contents(SMN_CACHE_TW_URL, '');
				}
				foreach ($_POST['smn_fb_auth'] as $key => $value) {
					$options['facebook_auth'][''.$key.''] = $value;
				}
				foreach ($_POST['smn_tw_auth'] as $key => $value) {
					$options['twitter_auth'][''.$key.''] = $value;
				}				
				update_option( parent::$optiontag, $options);
				$_POST['notice'] = __( 'Settings saved.', 'SMNlanguage' );
			}
		}
	}

	/**
	* Initialize certain admin functions and sets the checkboxes for the configuration page.
	*
	* @since 0.1
	*/
	public function smn_admin_init() {
		add_settings_section('smn_general_options', __('General Settings', 'SMNlanguage'),  array( 'SocialMediaNewsboxGUI', 'general_section_text' ), 'smn_section');
			add_settings_field(	'cache_interval', __('Interval of the cache renewal', 'SMNlanguage'), array( 'SocialMediaNewsboxGUI', 'cache_interval_callback'), 'smn_section', 'smn_facebook_options', array( 'label_for' => 'cache_interval' ) );

		add_settings_section('smn_facebook_options', __('Settings: Facebook', 'SMNlanguage'),  array( 'SocialMediaNewsboxGUI', 'facebook_section_text' ), 'smn_section');
			add_settings_field(	'link_facebook_feed', 'Facebook Page ID / Name', array( 'SocialMediaNewsboxGUI', 'link_check_facebook_callback'), 'smn_section', 'smn_facebook_options', array( 'label_for' => 'link_facebook_feed' ) );
			add_settings_field(	'fb_post_number', __('Post Number', 'SMNlanguage'), array( 'SocialMediaNewsboxGUI', 'check_fbnumber_callback'), 'smn_section', 'smn_facebook_options', array( 'label_for' => 'fb_post_number' ) );
			add_settings_field(	'fb_appid', 'Facebook App ID', array( 'SocialMediaNewsboxGUI', 'check_fbappid_callback'), 'smn_section', 'smn_facebook_options', array( 'label_for' => 'fb_appid' ) );
			add_settings_field(	'fb_appsecret', 'Facebook App Secret', array( 'SocialMediaNewsboxGUI', 'check_fbappsecret_callback'), 'smn_section', 'smn_facebook_options', array( 'label_for' => 'fb_appsecret' ) );

		add_settings_section('smn_twitter_options', __('Settings: Twitter', 'SMNlanguage'),  array( 'SocialMediaNewsboxGUI', 'twitter_section_text'), 'smn_section');
			add_settings_field(	'link_twitter_feed', 'Twitter Account Name', array( 'SocialMediaNewsboxGUI', 'check_twaccount_callback'), 'smn_section', 'smn_twitter_options', array( 'label_for' => 'link_twitter_feed' ) );
			add_settings_field(	'tw_tweet_number', __('Tweet Number', 'SMNlanguage'), array( 'SocialMediaNewsboxGUI', 'check_tweetnumber_callback'), 'smn_section', 'smn_twitter_options', array( 'label_for' => 'tw_tweet_number' ) );
			add_settings_field(	'tw_consumerkey', 'Consumer Key', array( 'SocialMediaNewsboxGUI', 'check_twconsumkey_callback'), 'smn_section', 'smn_twitter_options', array( 'label_for' => 'tw_consumerkey' ) );
			add_settings_field(	'tw_consumersecret', 'Consumer Secret', array( 'SocialMediaNewsboxGUI', 'check_twconsumsecret_callback'), 'smn_section', 'smn_twitter_options', array( 'label_for' => 'tw_consumersecret' ) );
			add_settings_field(	'tw_accesstoken', 'Access Token', array( 'SocialMediaNewsboxGUI', 'check_twaccesstoken_callback'), 'smn_section', 'smn_twitter_options', array( 'label_for' => 'tw_accesstoken' ) );
			add_settings_field(	'tw_accesssecret', 'Access Token Secret', array( 'SocialMediaNewsboxGUI', 'check_twaccesssecret_callback'), 'smn_section', 'smn_twitter_options', array( 'label_for' => 'tw_accesssecret' ) );

		register_setting( 'smn_section', 'smn_options');
	}

		/**
	   	* The Callbacks for the checkboxes and text.
	   	*
		* @since 0.1
	   	*/
	   	function general_section_text() {
			echo __('Make some general settings first.', 'SMNlanguage');
		}
		function cache_interval_callback() {
			$options = get_option( parent::$optiontag );
			echo '<input type="number" min="300" max="3600" step="60" id="cache_interval" name="smn_general[interval]" value="'.((!empty($options['general']['interval'])) ? $options['general']['interval'] : '').'" size="10" />';
			echo ' <em>Standard: 600 sec (= 10 min)</em>';
			echo '<br><small>'.__('<strong>Info:</strong> time in seconds for the cache renewal. You can go by steps of 60 sec (= 1 min). Maximum value can be 3600 sec (= 1 hour)', 'SMNlanguage').'</small>';
			echo '<br><small><strong>'.__('Important: The lower the value the more often the cache have to be reloaded, which increases shortly your site load time.', 'SMNlanguage').'</strong></small>';
		}

		function facebook_section_text() {
			echo __('Please insert the ID of your Facebook Page, you want to show the feeds from.', 'SMNlanguage');
		}
		function link_check_facebook_callback() {
			$options = get_option( parent::$optiontag );
			echo '<input type="text" id="link_facebook_feed" name="smn_fb_feedid" value="'.((!empty($options['smn_facebook_id'])) ? $options['smn_facebook_id'] : '').'" placeholder="'.__('Your Facebook Page ID', 'SMNlanguage').'" size="40" />';
		}
		function check_fbnumber_callback() {
			$options = get_option( parent::$optiontag );
			echo '<input type="number" id="fb_post_number" name="smn_fb_postno" value="'.((!empty($options['smn_fbpost_number'])) ? $options['smn_fbpost_number'] : '').'" placeholder="'.__('No. of posts', 'SMNlanguage').'" min="1" max="10" step="1" />';
			echo ' <em>Standard: 1</em>';
		}
		function check_fbappid_callback() {
			$options = get_option( parent::$optiontag );
			echo '<input type="text" id="fb_appid" name="smn_fb_auth[appid]" value="'.((!empty($options['facebook_auth']['appid'])) ? $options['facebook_auth']['appid'] : '').'" placeholder="'.__('App ID of your Facebook App', 'SMNlanguage').'" size="40" />';
		}
		function check_fbappsecret_callback() {
			$options = get_option( parent::$optiontag );
			echo '<input type="text" id="fb_appsecret" name="smn_fb_auth[appsecret]" value="'.((!empty($options['facebook_auth']['appsecret'])) ? $options['facebook_auth']['appsecret'] : '').'" placeholder="'.__('App Secret of your Facebook App', 'SMNlanguage').'" size="40" />';
		}

		function twitter_section_text() {
			echo __('The needed information of your Twitter Account can be inserted here.', 'SMNlanguage');
		}
		function check_twaccount_callback() {
			$options = get_option( parent::$optiontag );
			echo '<input type="text" id="link_twitter_feed" name="smn_tw_account" value="'.((!empty($options['smn_twitter_account'])) ? $options['smn_twitter_account'] : '').'" placeholder="'.__('Your Twitter Account Name', 'SMNlanguage').'" size="40" />';
		}
		function check_tweetnumber_callback() {
			$options = get_option( parent::$optiontag );
			echo '<input type="number" id="tw_tweet_number" name="smn_tw_tweetno" value="'.((!empty($options['smn_tweet_number'])) ? $options['smn_tweet_number'] : '').'" placeholder="'.__('No. of tweets', 'SMNlanguage').'" min="1" max="10" step="1" />';
			echo ' <em>Standard: 1</em>';
		}
		function check_twconsumkey_callback() {
			$options = get_option( parent::$optiontag );
			echo '<input type="text" id="tw_consumerkey" name="smn_tw_auth[consumerkey]" value="'.((!empty($options['twitter_auth']['consumerkey'])) ? $options['twitter_auth']['consumerkey'] : '').'" placeholder="'.__('Consumer Key of your Twitter App', 'SMNlanguage').'" size="40" />';
		}
		function check_twconsumsecret_callback() {
			$options = get_option( parent::$optiontag );
			echo '<input type="text" id="tw_consumerkey" name="smn_tw_auth[consumersecret]" value="'.((!empty($options['twitter_auth']['consumersecret'])) ? $options['twitter_auth']['consumersecret'] : '').'" placeholder="'.__('Consumer Secret of your Twitter App', 'SMNlanguage').'" size="40" />';
		}
		function check_twaccesstoken_callback() {
			$options = get_option( parent::$optiontag );
			echo '<input type="text" id="tw_accesstoken" name="smn_tw_auth[accesstoken]" value="'.((!empty($options['twitter_auth']['accesstoken'])) ? $options['twitter_auth']['accesstoken'] : '').'" placeholder="'.__('Access Token of your Twitter App', 'SMNlanguage').'" size="40" />';
		}
		function check_twaccesssecret_callback() {
			$options = get_option( parent::$optiontag );
			echo '<input type="text" id="tw_accesssecret" name="smn_tw_auth[accesssecret]" value="'.((!empty($options['twitter_auth']['accesssecret'])) ? $options['twitter_auth']['accesssecret'] : '').'" placeholder="'.__('Access Token of your Twitter App', 'SMNlanguage').'" size="40" />';
		}

	/**
	* Load the HTML for the admin page
	*
	* @since 0.1
	**/
	public function showAdminPage() {
		$options = get_option( parent::$optiontag );
?>
<div class="wrap">
		<?php
		if( $_POST['notice'] )
			echo '<div id="message" class="updated"><p><strong>' . $_POST['notice'] . '</strong></p></div>';
		?>
	<h2><?php echo __( 'SocialMedia Newsbox: Settings', 'SMNlanguage' ); ?></h2>
	<?php settings_errors(); ?>
	<?php echo __( 'Take your settings and choose which social media network you want to use.', 'SMNlanguage' ); ?>
	<form method="post" action="">
		<?php wp_nonce_field( 'save_smn_options', '_wpnonce-socialmedianewsbox' ); ?>
		<?php settings_fields('smn_section'); ?>
	    <?php do_settings_sections('smn_section'); ?>
	    <br />
		<p>
	      <label>
	        <input type="checkbox" name="smn_useDefaults" />
	        <?php _e( 'Set settings to default', 'SMNlanguage' ); ?>
	      </label>
	    </p>
	    <p>
	      <label>
	        <input type="checkbox" name="smn_deleteCache" />
	        <?php _e( 'Delete the cached news', 'SMNlanguage' ); ?>
	      </label>
	    </p>
	    <?php submit_button(NULL,'primary','submit-smn-options'); ?>
	    <input name="action" value="save_smn_options" type="hidden" />
    </form>
</div>
<?php
	}

}
