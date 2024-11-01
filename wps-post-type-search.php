<?php

/*
Plugin Name: WPS Post Type Search
Plugin URI: http://www.wpsmith.net/wps-post-type-search
Description: Creates a search widget to allow you to search single/multiple post types
Version: 0.2
Author: Travis Smith
Author URI: http://www.wpsmith.net/
License: GPLv2

    Copyright 2012  Travis Smith  (email : http://wpsmith.net/contact)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


define( 'WPSPTS_DOMAIN' , 'wps-post-type-search' );

/* Prevent direct access to the plugin */
if ( !defined( 'ABSPATH' ) ) {
    wp_die( __( "Sorry, you are not allowed to access this page directly.", WPSPTS_DOMAIN ) );
}

/**
 * Register widgets for use in the Genesis theme.
 */
function wps_load_widgets() {
	register_widget( 'WPS_Widget_Search' );
}
add_action( 'widgets_init', 'wps_load_widgets' , 25 );

/**
 * Search widget class
 *
 * @since 2.8.0
 */
class WPS_Widget_Search extends WP_Widget {

	function __construct() {
		$widget_ops = array( 'classname' => 'widget_search_pt', 'description' => __( "A multiple post type search form for your site" , WPSPTS_DOMAIN ) );
		parent::__construct( 'pt-search', __( 'WPS Post Type Search' , WPSPTS_DOMAIN ), $widget_ops );
	}

	function widget( $args, $instance ) {
		extract($args);
		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );
		$pt_args = apply_filters( 'gcpta_pt_args' , array( 'public' => true ) );
		$pts = get_post_types( $pt_args , 'names', 'and' );
		$post_types = array();		
		foreach ( $pts  as $pt ) {
			if ( isset( $instance['post_type_' . $pt] ) )
				$post_types[] = $pt;
		}

		echo $before_widget;
		if ( $title )
			echo $before_title . $title . $after_title;

		?>
		<form role="search" class="searchform" method="get" id="searchform" action="<?php echo home_url( '/' ); ?>">
		<input type="text" name="s" id="s" class="s" <?php if(is_search()) { ?>value="<?php the_search_query(); ?>" <?php } else { ?>value="Enter keywords &hellip;" onfocus="if(this.value==this.defaultValue)this.value='';" onblur="if(this.value=='')this.value=this.defaultValue;"<?php } ?> />
		<input type="submit" id="searchsubmit" value="Search" />
		<br />
		<?php $query_types = get_query_var('post_type'); 
		foreach ( $post_types as $pt ) { 
			$post_type = get_post_type_object( $pt );
		?>
			<input type="checkbox" name="post_type[]" value="<?php echo $post_type->name ?>" <?php if ( in_array( $post_type->name, (array)$query_types ) ) { echo 'checked="checked"'; } ?> /><label><?php echo $post_type->label; ?></label>
		
		<?php
		}
		?>

		
		</form>

		<?php
		echo $after_widget;
	}

	function form( $instance ) {
		
		$instance = wp_parse_args( (array) $instance, array( 'title' => '') );
		
?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e( 'Title:' , WPSPTS_DOMAIN ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>" /></label></p>
<?php
		$pt_args = apply_filters( 'gcpta_pt_args' , array( 'public' => true ) );
		$pts = get_post_types( $pt_args , 'objects', 'and' );
			
		foreach ( $pts  as $pt ) { ?>
			<p>
				<input id="<?php echo $this->get_field_id( 'post_type_' . $pt->name ); ?>" type="checkbox" name="<?php echo $this->get_field_name( 'post_type_' . $pt->name ); ?>" value="1"<?php checked( $instance['post_type_' . $pt->name] ); ?> />
				<label for="<?php echo $this->get_field_id( 'post_type_' . $pt->name ); ?>"><?php _e( $pt->label ); ?></label>
			</p>
			
			<?php
		}
		
		return $instance;
	}

	function update( $new_instance, $old_instance ) {
		$new_instance['title']     = strip_tags( $new_instance['title'] );
		return $new_instance;
	}

}

add_filter( 'pre_get_posts', 'wps_cpt_search' );
/**
 * This function modifies the main WordPress query to include an array of post types instead of the default 'post' post type.
 *
 * @author Travis Smith
 * @param mixed $query The original query
 * @return $query The amended query
 *
 */
function wps_cpt_search( $query ) {
	
    if ( $query->is_main_query() && $query->is_search ) {
		$post_types = $_GET['post_type'];
		if ( $post_types )
			$query->set( 'post_type', $post_types );
		else
			$query->set( 'post_type', array( 'post' ) );
	}
    return $query;
};