<?php

//
// Виджет "Пользователи"
//
//


defined( 'ABSPATH' ) || exit;


if ( mif_bpc_options( 'members-widget' ) ) 
    add_action( 'widgets_init', 'mif_bpc_members_widget_init' );

function mif_bpc_members_widget_init() 
{
    register_widget( 'mif_bpc_members_widget' );
}


class mif_bpc_members_widget extends WP_Widget {

	public function __construct() 
    {
		$widget_options = apply_filters( 'mif_bpc_members_widget_options', array(
			'classname'   =>    'members_widget',
			'description' => __( 'Simple, smart and fast site members widget', 'mif-bpc' )
		) );

		parent::__construct( false, __( 'Site members', 'mif-bpc' ), $widget_options );
	}



	public static function register_widget() 
    {
		register_widget( 'mif_bpc_members_widget' );
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

		$title = apply_filters( 'mif_bpc_members_widget_title', $data['title'] );
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

        $number = apply_filters( 'mif_bpc_members_widget_number', $data['number'] );
        $members_type = apply_filters( 'mif_bpc_members_widget_members_type', $data['members_type'] );
		$cache_expires = apply_filters( 'mif_bpc_members_widget_cache_expires', $data['cache_expires'] );
		$avatar_size = apply_filters( 'mif_bpc_members_widget_avatar_size', 50 );

		$user_data = array();

	    global $wpdb, $blog_id;

        $cache_group = $number . '-' . $members_type . '-' . $cache_expires . '-' . $avatar_size;
		$cache_widget_avatars = get_option( 'cache_widget_user_avatars' );

		$expires = absint( $cache_widget_avatars[$cache_group]['expires'] );
		$now = time();
		if ( ! isset( $cache_widget_avatars[$cache_group] ) || $now > $expires || $cache_widget_avatars[$cache_group]['user_avatars'] == array() ) {
		
			if ( is_active_buddypress() ) {
				// Если есть buddypress

                add_filter( 'bp_is_current_component', 'no_friends_page', 10, 2 );
		
        		$limit = $number * 4;

				$args = array(
						'type' => $members_type,
						'max' => $limit,
						'per_page' => $limit,
						// 'meta_key' => $wpdb->base_prefix . $blog_id . "_capabilities"
				);
				
                if ( ! is_main_site( $blog_id ) ) $args['meta_key'] = $wpdb->base_prefix . $blog_id . "_capabilities";

				if ( bp_has_members( $args ) ) {

					while ( bp_members() ) {

						bp_the_member(); 

						$user_data[] = array(
										'ID' => bp_get_member_user_id(),
										'url' => bp_get_member_link(),
										'name' => bp_get_member_name(),
									);

					}; 

				}

				$avatar_dir = trailingslashit( bp_core_avatar_upload_path() ) . trailingslashit( 'avatars' ); 

				foreach ( (array) $user_data as $key => $item ) {

					if ( count( $user_data ) <= $number ) break;
					if ( ! file_exists( $avatar_dir . $item['ID'] ) ) unset( $user_data[$key] );
					
				}


			} else {

				// Если buddypress нет

				$limit = $number * 2;

				add_action( 'pre_user_query', array( $this, 'random_user_query' ) );

				$users = get_users( array(
									'blog_id' => $blog_id,
									'number' => $limit,
									'orderby' => 'rand',
								));

				foreach ( (array) $users as $user ) 
					$user_data[] = array(
									'ID' => $user->ID,
									'url' => $user->user_url,
									'name' => $user->user_nicename,
								);
				
			}

			$user_avatars = array();

			foreach ( (array) $user_data as $item ) {
				
				$before = ( $item['url'] ) ? '<a href="' . $item['url'] . '">' : '';
				$after = ( $item['url'] ) ? '</a>' : '';

				$user_avatars[] = '<span class="avatar" title="' . $item['name'] . '">' . $before . get_avatar( $item['ID'], $avatar_size ) . $after . '</span>';

			}

            foreach ( (array) $cache_widget_avatars as $key => $value ) 
                if ( $value['expires'] < $now ) unset( $cache_widget_avatars[$key] );

            $cache_widget_avatars[$cache_group] = array( 'expires' => time() + $cache_expires, 'user_avatars' => $user_avatars );
			update_option( 'cache_widget_user_avatars', $cache_widget_avatars, false );

		} else {

			$user_avatars = $cache_widget_avatars[$cache_group]['user_avatars'];

		}


		shuffle( $user_avatars );
		$out_arr = array_splice( $user_avatars, 0, $number );
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
		$data['members_type'] = strip_tags( $new_data['members_type'] );
		$data['cache_expires'] = strip_tags( $new_data['cache_expires'] );

		return $data;
	}



	public function form( $data ) 
    {
		// $title = ( ! empty( $data['title'] ) ) ? esc_attr( $data['title'] ) : __( 'Site members', 'mif-bpc' );
		$title = isset( $data['title'] ) ? $data['title'] : '';
		$number = isset( $data['number'] ) ? absint( $data['number'] ) : 16;
        $members_type = isset( $data['members_type'] ) ? $data['members_type'] : 'active';
		$cache_expires = isset( $data['cache_expires'] ) ? absint( $data['cache_expires'] ) : 300;

        $out = '';

        $out .= '<p><label for="' . $this->get_field_id( 'title' ) . '">' . __( 'Title:', 'mif-bpc' ) . '
                <input class="widefat" id="' . $this->get_field_id( 'title' ) . ' " name="' . $this->get_field_name( 'title' ) . '" type="text" value="' . $title . '" /></label>';
        $out .= '<p><label for="' . $this->get_field_id( 'number' ) . '">' . __( 'Number of avatars:', 'mif-bpc' ) . '
                <input class="tiny-text" id="' . $this->get_field_id( 'number' ) . ' " name="' . $this->get_field_name( 'number' ) . '" type="number" value="' . $number . '" /></label>';
        // $out .= '<p><label for="' . $this->get_field_id( 'members_type' ) . '">' . __( 'Selection options:', 'mif-bpc' ) . '
        //         <input class="widefat" id="' . $this->get_field_id( 'members_type' ) . ' " name="' . $this->get_field_name( 'members_type' ) . '" type="text" value="' . $members_type . '" /></label>';
		$out .= '<p><label for="' . $this->get_field_id( 'members_type' ) . '">' . __( 'Selection options:', 'mif-bpc' ) . '</label>
			    <select name="' . $this->get_field_name( 'members_type' ) . '" id="' . $this->get_field_id( 'members_type' ) . '" class="widefat">
				<option value="active"' . selected( $members_type, 'active', false ) . '>' . __( 'Active', 'mif-bpc' ) . '</option>
				<option value="popular"' . selected( $members_type, 'popular', false ) . '>' . __( 'Popular', 'mif-bpc' ) . '</option>
				<option value="random"' . selected( $members_type, 'random', false ) . '>' . __( 'Random', 'mif-bpc' ) . '</option></select>';
        $out .= '<p><label for="' . $this->get_field_id( 'cache_expires' ) . '">' . __( 'Cache lifetime:', 'mif-bpc' ) . '
                <input class="tiny-text" id="' . $this->get_field_id( 'cache_expires' ) . ' " name="' . $this->get_field_name( 'cache_expires' ) . '" type="text" value="' . $cache_expires . '" /> ' . __( 'sec', 'mif-bpc' ) . '</label>';

        echo $out;    
    }
}


?>