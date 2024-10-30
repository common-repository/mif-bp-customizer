<?php

//
// Виджет "Groups"
//
//


defined( 'ABSPATH' ) || exit;


if ( mif_bpc_options( 'groups-widget' ) ) 
    add_action( 'widgets_init', 'mif_bpc_groups_widget_init' );

function mif_bpc_groups_widget_init() 
{
    register_widget( 'mif_bpc_groups_widget' );
}


class mif_bpc_groups_widget extends WP_Widget {

	public function __construct() 
    {
		$widget_options = apply_filters( 'mif_wpc_groups_widget_options', array(
			'classname'   =>    'groups_widget',
			'description' => __( 'Simple, smart and fast widget of social networks groups', 'mif-bpc' )
		) );

		parent::__construct( false, __( 'Groups', 'mif-bpc' ), $widget_options );
	}



	public static function register_widget() 
    {
		register_widget( 'mif_bpc_groups_widget' );
	}



	public function widget( $args, $data ) 
    {
        extract( $args );

        $out = '';
        
		// $out .= '<p>';
   		// $out .= get_num_queries() . ' - ';
		// $out .= timer_stop(1) . ' - ';
		// $out .= round(memory_get_usage()/1024/1024, 2);

        $out .= $before_widget;

		$title = apply_filters( 'mif_wpc_members_widget_title', $data['title'] );

		if ( ! empty( $title ) ) $out .= $before_title . $title . $after_title;

		$avatars = $this->get_avatars( $data );

        $out .= $avatars;

		$out .= $after_widget;

   		// $out .= get_num_queries() . ' - ';
		// $out .= timer_stop(1) . ' - ';
		// $out .= round(memory_get_usage()/1024/1024, 2);

        echo $out;

	}


	private function get_avatars( $data )
	{
		$out = '';

        $number = apply_filters( 'mif_bpc_groups_widget_number', $data['number'] );
        $groups_type = apply_filters( 'mif_bpc_groups_widget_members_type', $data['groups_type'] );
		$cache_expires = apply_filters( 'mif_bpc_groups_widget_cache_expires', $data['cache_expires'] );
		$avatar_size = apply_filters( 'mif_bpc_groups_widget_avatar_size', 50 );

		$user_data = array();

	    global $wpdb, $blog_id;

		$cache_group = $number . '-' . $groups_type . '-' . $cache_expires . '-' . $avatar_size;
        $cache_widget_avatars = get_option( 'cache_widget_group_avatars' );
		$expires = absint( $cache_widget_avatars[$cache_group]['expires'] );
		$now = time();

        $limit = $number * 3;

		if ( ! isset ( $cache_widget_avatars[$cache_group] ) || $now > $expires ) {
		
            $args = array(
                    'type' => $groups_type,
                    'slug' => false,
                    'user_id' => false,
                    'max' => $limit,
                    'per_page' => $limit,
            );

            if ( bp_has_groups( $args ) ) {

                while ( bp_groups() ) {

                    bp_the_group();

                    $group_data[] = array(
                                    'ID' => bp_get_group_id(),
                                    'url' => bp_get_group_permalink(),
                                    'name' => bp_get_group_name(),
                                    'avatar' => bp_core_fetch_avatar( array(
                                                                        'item_id' => bp_get_group_id(),
                                                                        // 'title' => $groups_template->group->name,
                                                                        'avatar_dir' => 'group-avatars',
                                                                        'object' => 'group',
                                                                        'type' => 'thumb',
                                                                        'width' => $avatar_size,
                                                                        'height' => $avatar_size,
                                                                    ) )

                                    );

                }

            }

            $avatar_dir = trailingslashit( bp_core_avatar_upload_path() ) . trailingslashit( 'group-avatars' ); 

            foreach ( (array) $group_data as $key => $item ) {
                if ( count( $group_data ) <= $number ) break;
                if ( ! file_exists( $avatar_dir . $item['ID'] ) ) unset( $group_data[$key] );
            }

			$group_avatars = array();

			foreach ( (array) $group_data as $item ) {
				
				$before = ( $item['url'] ) ? '<a href="' . $item['url'] . '">' : '';
				$after = ( $item['url'] ) ? '</a>' : '';

				$group_avatars[] = '<span class="avatar" title="' . $item['name'] . '">' . $before . $item['avatar'] . $after . '</span>';

			}

            foreach ( (array) $cache_widget_avatars as $key => $value ) 
                if ( $value['expires'] < $now ) unset( $cache_widget_avatars[$key] );

            $cache_widget_avatars[$cache_group] = array( 'expires' => time() + $cache_expires, 'group_avatars' => $group_avatars );

            update_option( 'cache_widget_group_avatars', $cache_widget_avatars, false );

		} else {

			$group_avatars = $cache_widget_avatars[$cache_group]['group_avatars'];

		}


		shuffle( $group_avatars );
		$out_arr = array_splice( $group_avatars, 0, $number );
		$out .= implode( '', $out_arr );

		return $out;
	}

	public function random_user_query( $class ) 
	{
		if( 'rand' == $class->query_vars['orderby'] )
			$class->query_orderby = str_replace( 'user_login', 'RAND()', $class->query_orderby );

		return $class;
	}


	public function update( $new_data, $old_data ) 
    {
		$data = $old_data;
		$data['title'] = strip_tags( $new_data['title'] );
		$data['number'] = strip_tags( $new_data['number'] );
		$data['groups_type'] = strip_tags( $new_data['groups_type'] );
		$data['cache_expires'] = strip_tags( $new_data['cache_expires'] );

		return $data;
	}



	public function form( $data ) 
    {
		$title = isset( $data['title'] ) ? $data['title'] : '';
		$number = isset( $data['number'] ) ? absint( $data['number'] ) : 16;
        $groups_type = isset( $data['groups_type'] ) ? $data['groups_type'] : 'active';
		$cache_expires = isset( $data['cache_expires'] ) ? absint( $data['cache_expires'] ) : 300;

        $out = '';

        $out .= '<p><label for="' . $this->get_field_id( 'title' ) . '">' . __( 'Title:', 'mif-bpc' ) . '
                <input class="widefat" id="' . $this->get_field_id( 'title' ) . ' " name="' . $this->get_field_name( 'title' ) . '" type="text" value="' . $title . '" /></label>';
        $out .= '<p><label for="' . $this->get_field_id( 'number' ) . '">' . __( 'Number of avatars:', 'mif-bpc' ) . '
                <input class="tiny-text" id="' . $this->get_field_id( 'number' ) . ' " name="' . $this->get_field_name( 'number' ) . '" type="number" value="' . $number . '" /></label>';
		$out .= '<p><label for="' . $this->get_field_id( 'groups_type' ) . '">' . __( 'Selection options:', 'mif-bpc' ) . '</label>
			    <select name="' . $this->get_field_name( 'groups_type' ) . '" id="' . $this->get_field_id( 'groups_type' ) . '" class="widefat">
				<option value="active"' . selected( $groups_type, 'active', false ) . '>' . __( 'Active', 'mif-bpc' ) . '</option>
				<option value="popular"' . selected( $groups_type, 'popular', false ) . '>' . __( 'Popular', 'mif-bpc' ) . '</option>
				<option value="random"' . selected( $groups_type, 'random', false ) . '>' . __( 'Random', 'mif-bpc' ) . '</option></select>';
        $out .= '<p><label for="' . $this->get_field_id( 'cache_expires' ) . '">' . __( 'Cache lifetime:', 'mif-bpc' ) . '
                <input class="tiny-text" id="' . $this->get_field_id( 'cache_expires' ) . ' " name="' . $this->get_field_name( 'cache_expires' ) . '" type="text" value="' . $cache_expires . '" /> ' . __( 'sec', 'mif-bpc' ) . '</label>';

        echo $out;    
    }
}


// add_action( 'pre_user_query', 'my_random_user_query' );
// function my_random_user_query( $class ) 
// 	{
// 		p('sss');

// 		if( 'rand' == $class->query_vars['orderby'] )
// 			$class->query_orderby = str_replace( 'user_login', 'RAND()', $class->query_orderby );

// 		return $class;
// 	}


?>