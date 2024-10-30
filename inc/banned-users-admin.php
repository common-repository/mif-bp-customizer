<?php

//
// Страница настроек плагина
//
//


defined( 'ABSPATH' ) || exit;


class mif_bpc_admin_banned_members {
    
    function __construct() 
    {
        // add_action( bp_core_admin_hook(), array( $this, 'register_menu_page' ) );
        add_action( 'admin_menu', array( $this, 'register_menu_page' ) );
        // add_filter( 'users_list_table_query_args', array( $this, 'set_args' ) );

    }

    function register_menu_page()
    {
        add_users_page( __( 'User blockings', 'mif-bpc' ), __( 'User blockings', 'mif-bpc' ), 'manage_options', 'banned-members', array( $this, 'page' ) );
        // add_submenu_page( 'users.php', __( 'User blockings', 'mif-bpc' ), __( 'User blockings', 'mif-bpc' ), 'manage_options', 'banned-members', array( $this, 'page' ) );
        wp_register_style( 'mif-bpc-styles', plugins_url( '../mif-bpc-styles.css', __FILE__ ) );
        wp_enqueue_style( 'mif-bpc-styles' );
    }

    function page()
    {
        $out = '<h1>' . __( 'User blockings', 'mif-bpc' ) . '</h1>';
        $out .= '<p>' . __( 'This page displays information about user blockings in BuddyPress network.', 'mif-bpc' );
        $out .= '<p>&nbsp;';


        $banned_users_ids = $this->get_all_banned_users();
        $banned_users_index = $this->get_all_banned_users( 'index' );

        $args = apply_filters( 'mif_bpc_admin_banned_members_args', array( 'include' => $banned_users_ids ), $banned_users_index );

        $user_data = array();
        $tmp_arr = array();

        if ( bp_has_members( $args ) ) {

            while ( bp_members() ) {

                bp_the_member(); 

                $count = count( $banned_users_index[bp_get_member_user_id()] );
                $time = get_user_meta( bp_get_member_user_id(), 'banned_users_timestamp', true );

                $user_data[] = array(
                                'ID' => bp_get_member_user_id(),
                                'url' => bp_get_member_link(),
                                'name' => bp_get_member_name(),
                                'count' => $count,
                                'time' => $time,
                            );
                $count_arr[] = $count;
                $time_arr[] = $time;

            }; 

        }

        array_multisort( $time_arr, SORT_DESC, SORT_NUMERIC, $user_data );

        $out .= '<table width="100%">';

        foreach ( (array) $user_data as $user ) {

            $arr = array();
            foreach ( (array) $banned_users_index[$user['ID']] as $item ) $arr[] = '<a href="' . bp_core_get_user_domain( $item ) . '"> ' . get_avatar( $item, 30 ) . '</a>';

            $out .= '<tr>
            <td width="1%"><a href="' . $user['url'] . '"> ' . get_avatar( $user['ID'], 30 ) . '</a></td>
            <td><a href="' . $user['url'] . '"> ' . $user['name'] . '</a></td>
            <td>' . bp_core_time_since( $user['time'] ) . '</td>
            <td>' . $user['count'] . '</td>
            <td>' . implode( "\n", $arr ) . '</td>
            </tr>';

        }

        $out .= '</table>';
        
        
        // p($banned_users);




        echo $out;




    }


    // 
    // Возвращает список заблокированных пользователей
    // (массив id, or индекс с номерами тех, кто заблокировал)
    // 

    function get_all_banned_users( $mode = "ids" )
    {

        global $wpdb;

        if ( ! $arr_out = wp_cache_get( "all_banned_users") ) {
            
            $table = _get_meta_table( 'user' );
            $arr = $wpdb->get_results( "SELECT user_id, meta_value FROM $table WHERE meta_key='banned_users'", ARRAY_A );

            $arr_out = array();

            foreach ( (array) $arr as $item ) {

                $banned_users = explode( ',', $item['meta_value'] );

                foreach ( (array) $banned_users as $banned_user_id )                     
                    if ( (int) $banned_user_id ) $arr_out[(int) $banned_user_id][] = (int) $item['user_id'];

            }

            wp_cache_add( $arr_out, "all_banned_users");
        }

        if ( $mode == 'ids' )  return array_keys( $arr_out );

        return $arr_out;

    }


}

if ( mif_bpc_options( 'banned-users' ) ) {

    new mif_bpc_admin_banned_members();

}


?>