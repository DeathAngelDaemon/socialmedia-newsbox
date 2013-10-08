<?php

/**
* Adds the SocialMedia News widget.
*
* @since 0.1
*/
class SocialMediaNewsboxWidget extends WP_Widget {

	/**
	 * Register the widget with WordPress.
	 */
	public function __construct() {
		$widget_ops = array('classname' => 'SocialMediaNewsboxWidget', 'description' => __('Shows the latest post from the configured social media networks.', 'SMNlanguage') );
		$this->WP_Widget('SocialMediaNewsboxWidget', 'SocialMedia Newsbox Widget', $widget_ops);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		global $options;
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'] );
		$newstext = $instance['newstext'];
		$newsdate = isset( $instance['newsdate'] ) ? $instance['newsdate'] : false;
		$smn_fb = isset( $instance['smn_fb'] ) ? $instance['smn_fb'] : false;
		$smn_tw = isset( $instance['smn_tw'] ) ? $instance['smn_tw'] : false;

		echo $before_widget;
		if ( !empty($title) )
			echo $before_title . $title . $after_title;
		if( !empty($newstext) ) {
			echo '<div class="smn_intronews">';
			if($newsdate)
				echo date('d.m.Y / H:i').' - ';
			echo $newstext;
			echo '</div>';
		}
		# output of the newslist
		echo SocialMediaNewsbox::show_newslist($smn_fb, $smn_tw);

		echo $after_widget;
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['newstext'] = strip_tags( $new_instance['newstext'] );
		$instance['newsdate'] = $new_instance['newsdate'] ? 1 : 0;
		$instance['smn_fb'] = $new_instance['smn_fb'] ? 1 : 0;
		$instance['smn_tw'] = $new_instance['smn_tw'] ? 1 : 0;

		return $instance;
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		} else {
			$title = __('SocialMedia News', 'SMNlanguage');
		}
		if ( isset( $instance[ 'newstext' ] ) ) {
			$newstext = $instance[ 'newstext' ];
		} else { $newstext = ''; }
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'SMNlanguage' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('newstext'); ?>"><?php _e( 'Set up your own news information.', 'SMNlanguage' ) ?></label>
			<textarea class="widefat" id="<?php echo $this->get_field_id('newstext'); ?>" name="<?php echo $this->get_field_name('newstext'); ?>" cols="10" rows="16"><?php echo esc_attr( $newstext ); ?></textarea>
			<br>
			<input class="checkbox" type="checkbox" id="<?php echo $this->get_field_id('newsdate'); ?>" name="<?php echo $this->get_field_name('newsdate'); ?>" <?php checked( $instance['newsdate'], 1 ); ?> />
			<label for="<?php echo $this->get_field_id('newsdate'); ?>"><?php _e( 'Show date and time prior to the Text.', 'SMNlanguage' ) ?></label>
		</p>
		<p>
			<?php _e( 'Choose which social network you want to show.', 'SMNlanguage' ) ?><br />
			<input class="checkbox" type="checkbox" id="<?php echo $this->get_field_id('smn_fb'); ?>" name="<?php echo $this->get_field_name('smn_fb'); ?>" <?php checked( $instance['smn_fb'], 1 ); ?> />
			<label for="<?php echo $this->get_field_id('smn_fb'); ?>"><?php _e( 'Facebook', 'SMNlanguage' ) ?></label>
			<br />
			<input class="checkbox" type="checkbox" id="<?php echo $this->get_field_id('smn_tw'); ?>" name="<?php echo $this->get_field_name('smn_tw'); ?>" <?php checked( $instance['smn_tw'], 1 ); ?> />
			<label for="<?php echo $this->get_field_id('smn_tw'); ?>"><?php _e( 'Twitter', 'SMNlanguage' ) ?></label>
		</p>
		<?php
	}

} ?>