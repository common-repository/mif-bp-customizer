<?php

//
// Класс блокировки пользователей
// 
//

defined( 'ABSPATH' ) || exit;

if ( mif_bpc_options( 'banned-users' ) ) {

    global $mif_bpc_banned_users;
    $mif_bpc_banned_users = new mif_bpc_banned_users();

}


class mif_bpc_banned_users {

    //
    // Механизм блокировки - у пользователя есть мета-поле 'banned_users' со списком id заблокированных пользователей, 
    // это используется при выподе элементов активности на экран.
    // 
    // У пользователя есть мета-поле 'banned_users_timestamp' - время, когда его в последний раз заблокировали (для статистики и контроля)
    //

    //
    // Ключ мета-поля
    //

    public $meta_key = 'banned_users';
    
    //
    // Пользователи, которых нельзя блокировать
    //

    public $unbanned_users = array( 'admin' );
    

    function __construct()
    {
        // Включить блокировку пользователей
        // if ( ! mif_bpc_options( 'banned-users' ) ) return;
        
        add_action( 'bp_activity_setup_nav', array( $this, 'banned_users_nav' ) );

        add_action( 'bp_member_header_actions', array( $this, 'banned_user_button' ), 100 );
        add_action( 'wp_print_scripts', array( $this, 'load_js_helper' ) );            				
        add_action( 'wp_ajax_banned-user-button', array( $this, 'banned_user_button_ajax_helper' ) );

        add_action( 'bp_get_add_friend_button', array( $this, 'remove_friendship_button' ) );
        add_filter( 'bp_activity_can_comment', array( $this, 'remove_comment_button' ) );
        add_filter( 'bp_activity_can_comment_reply', array( $this, 'remove_comment_button' ) );
        add_filter( 'bp_get_friendship_requests', array( $this, 'remove_friendship_requests' ), 10, 2 );

        add_filter( 'bp_use_legacy_activity_query', array( $this, 'remove_comment_query' ), 10, 2 );
        add_filter( 'bp_activity_comments_user_join_filter', array( $this, 'remove_comment_sql' ), 10, 5 );

        add_filter( 'mif_bpc_like_button_get_likes', array( $this, 'remove_likes_item' ) );
        add_filter( 'mif_bpc_like_button_like_button', array( $this, 'remove_like_button' ) );
        add_filter( 'mif_bpc_repost_button_is_reposted_activity', array( $this, 'remove_repost_button' ) );

        // add_action( 'bp_before_activity_comment', array( $this, 'before_activity_comment' ) );
        // add_action( 'bp_after_activity_comment', array( $this, 'after_activity_comment' ) );

    }
    

    // 
    // Страница блокировки пользователей в профиле пользователя
    // 
    // 

    function banned_users_nav()
    {
        $args = array(
                    'name' => __( 'Blockings', 'mif-bpc' ),
                    'slug' => 'banned-members',
                    'position' => 60,
                    'title' => __( 'User blocking', 'mif-bpc' ),
                    'body_comment' => __( 'The list of users, for whom contacts with you are restricted. These users can’t offer friendship, leave comments and press "Like" for your posts. Their information is not shown in the activity feed of your page. You can change the blocking status here or on the pages of users themselves.', 'mif-bpc' ),
                    'can_edit' => true,
                    'members_usermeta' => $this->meta_key,
                    'exclude_users' => $this->get_unbanned_users(),
                );

        new mif_bpc_members_page( $args );
    }
 

    // 
    // Кнопка блокировки пользователей на странице пользователей
    // 
    // 

    function banned_user_button()
    {

        if ( ! is_user_logged_in() ) return;
        if ( bp_is_my_profile() ) return;

        $user_id = bp_displayed_user_id();
        $unbanned_users = $this->get_unbanned_users( $mode = 'ids_arr' );

        if ( in_array( $user_id, $unbanned_users ) ) return;


        global $bp;

        $banned_url = $bp->loggedin_user->domain . $bp->profile->slug . '/banned-members';
        $banned_url_request = wp_nonce_url( $banned_url . '/banned-members/requests/' . $user_id . '/', 'mif_bpc_banned_user_button' );

        $caption = $this->get_caption();

        $arr = array(
                    array( 'href' => $banned_url_request, 'descr' => $caption, 'class' => 'ajax', 'data' => array( 'userid' => $user_id ) ),
                    array( 'href' => $banned_url, 'descr' => __( 'Configuration', 'mif-bpc' ) ),
                );

        $arr = apply_filters( 'mif_bpc_banned_user_button', $arr );

        $none = ( $this->is_banned() ) ? '' : ' none';

        echo '<div class="right"><div class="right relative generic-button banned-users"><a href="" class="gray banned-users"><strong>&middot;&middot;&middot;</strong></a>' . mif_bpc_hint( $arr ) . '</div><i class="fa fa-ban fa-2x right banned-users icon' . $none . '"></i></div>';

    }
 

    public function load_js_helper()
    {
        wp_register_script( 'mif_bpc_exclude_button', plugins_url( '../js/button-hint-helper.js', __FILE__ ) );  
        wp_enqueue_script( 'mif_bpc_exclude_button' );
    }


    public function banned_user_button_ajax_helper()
    {
        check_ajax_referer( 'mif_bpc_banned_user_button' );

        if ( ! mif_bpc_options( 'banned-users' ) ) wp_die();

        $user_id = (int) $_POST['userid'];
        $current_user_id = bp_loggedin_user_id();
        $banned_users = $this->get_banned_users( $current_user_id, 'arr' );
        
        if ( in_array( $user_id, $banned_users ) ) {

            $banned_users = array_diff( $banned_users, array( $user_id ) );

        } else {

            $banned_users[] = $user_id;
            sort( $banned_users );

            // Укажем время, когда последний раз заблокировали пользователя
            update_user_meta( $user_id, $this->meta_key . '_timestamp', time() );

        }

        // Обновим список блокировки
        update_user_meta( $current_user_id, $this->meta_key, implode( ',', $banned_users ) );
        wp_cache_delete( 'banned_users', $current_user_id );
        $caption = $this->get_caption();

        echo $caption;
     
        wp_die();
    }


    function get_caption()
    {
        $caption = ( $this->is_banned() ) ? __( 'Take off the restrictions', 'mif-bpc' ) : __( 'Restrict contacts', 'mif-bpc' );
        return $caption;
    }


    // 
    // Не показывать "Нравится" на своей старнице, если пользователь заблокирован
    // 

    public function remove_likes_item( $likes_arr )
    {
        if ( bp_is_my_profile() ) {
            $user_id = bp_loggedin_user_id();
            $banned_users = $this->get_banned_users( $user_id, 'arr' );
            $likes_arr = array_diff( $likes_arr, $banned_users );

        }

        return $likes_arr;
    }


    // 
    // Delete кнопку "Нравится" для заблокированных пользователей
    // 

    public function remove_like_button( $button )
    {
        $target_user_id = bp_get_activity_user_id();
        $user_id = bp_loggedin_user_id();

        if ( $this->is_banned( $target_user_id, $user_id ) ||
             $this->is_banned( $user_id, $target_user_id ) ) $button = '';

        // if ( $this->is_banned( $target_user_id, $user_id ) ) $button = array();

        return $button;    }


    // 
    // Delete кнопку "Add to friends", если пользователь тебя заблокировал
    // 

    public function remove_friendship_button( $button )
    {
        $target_user_id = bp_get_potential_friend_id();
        $user_id = bp_loggedin_user_id();

        if ( bp_is_friend() == 'is_friend' || bp_is_friend() == 'pending' ) return $button;

        if ( $this->is_banned( $target_user_id, $user_id ) ||
             $this->is_banned( $user_id, $target_user_id ) ) $button = array();

        // if ( $this->is_banned( $target_user_id, $user_id ) ) $button = array();

        return $button;
    }


    // 
    // Delete кнопку "Оставить комментарий", если пользователь тебя заблокировал
    // 

    public function remove_comment_button( $can_comment )
    {
        $target_user_id = ( bp_get_activity_comment_user_id() ) ? bp_get_activity_comment_user_id() : bp_get_activity_user_id();
        $user_id = bp_loggedin_user_id();

        if ( $this->is_banned( $target_user_id, $user_id ) ||
             $this->is_banned( $user_id, $target_user_id ) ) $can_comment = false;

        // if ( $this->is_banned( $target_user_id, $user_id ) ) $can_comment = false;

        return $can_comment;
    }


    // 
    // Delete кнопку "Репост", если пользователь тебя заблокировал
    // 

    public function remove_repost_button( $can_reposted )
    {
        $target_user_id = ( bp_get_activity_comment_user_id() ) ? bp_get_activity_comment_user_id() : bp_get_activity_user_id();
        $user_id = bp_loggedin_user_id();

        if ( $this->is_banned( $target_user_id, $user_id ) ||
             $this->is_banned( $user_id, $target_user_id ) ) $can_reposted = false;

        // if ( $this->is_banned( $target_user_id, $user_id ) ) $can_comment = false;

        return $can_reposted;
    }


    //
    // Не показывать комментарии заблокированного пользователя
    //
    
    function remove_comment_query( $ret, $method )
    {
        if ( $method == 'BP_Activity_Activity::get_activity_comments' ) {
            $ret = true;
        }

        return $ret;
    }


    function remove_comment_sql( $sql, $activity_id, $left, $right, $spam_sql )
    {
        global $wpdb;
        $bp = buddypress();

        $top_level_parent_id = $activity_id;

        if ( bp_is_active( 'xprofile' ) ) {
            $fullname_select = ", pd.value as user_fullname";
            $fullname_from = ", {$bp->profile->table_name_data} pd ";
            $fullname_where = "AND pd.user_id = a.user_id AND pd.field_id = 1";
        } else {
            $fullname_select = $fullname_from = $fullname_where = '';
        }

        $banned_users = $this->get_banned_users();

        $sql = $wpdb->prepare( "SELECT a.*, u.user_email, u.user_nicename, u.user_login, u.display_name{$fullname_select} FROM {$bp->activity->table_name} a, {$wpdb->users} u{$fullname_from} WHERE u.ID = a.user_id {$fullname_where} AND a.type = 'activity_comment' {$spam_sql} AND a.item_id = %d AND a.mptt_left > %d AND a.mptt_left < %d AND a.user_id NOT IN (%d) ORDER BY a.date_recorded ASC", $top_level_parent_id, $left, $right, $banned_users );

        // p($sql);

        return $sql;
    }


    // 
    // Delete запросы в друзья для заблокироанных пользователей
    // 

    public function remove_friendship_requests( $requests, $user_id )
    {

        if ( ! mif_bpc_options( 'banned-users' ) ) return $requests;

        $requests = friends_get_friendship_request_user_ids( $user_id );

        $banned_users = $this->get_banned_users( $user_id, 'arr' );
        $requests = array_diff( $requests, $banned_users );

        if ( !empty( $requests ) ) {
            $requests = implode( ',', (array) $requests );
        } else {
            $requests = 0;
        }

        return $requests;
    }


    // 
    // Получить список заблокированных пользователей для пользователя
    // 

    public function get_banned_users( $user_id = NULL, $mode = 'str' )
    {
        // возвращает строку id через запятую

        if ( $user_id === NULL ) $user_id = bp_loggedin_user_id();

        if ( ! $ret_arr = wp_cache_get( 'banned_users', $user_id ) ) {

            $ret = get_user_meta( $user_id, $this->meta_key, true );

            $ret_arr = explode( ',', $ret );
            foreach ( (array) $ret_arr as $key => $item ) $ret_arr[$key] = (int) $item;

            $unbanned_users = $this->get_unbanned_users( 'ids_arr' );
            $ret_arr = array_diff( $ret_arr, $unbanned_users );

            // Здесь можно поменять id заблокированных пользователей в массиве
            $ret_arr = apply_filters( 'mif_bpc_activity_stream_get_banned_users_arr', $ret_arr, $user_id );

            wp_cache_set( 'banned_users', $ret_arr, $user_id );

        }

        if ( $mode == 'arr' ) return $ret_arr;

        $ret = implode( ',', $ret_arr );

        return apply_filters( 'mif_bpc_activity_stream_get_banned_users', $ret, $user_id );
    }


    // 
    // Проверяет, является ли user2 заблокироанным пользователем у пользователя user
    // 

    public function is_banned( $user_id = NULL, $user2_id = NULL )
    {
        if ( $user_id == NULL ) $user_id = bp_loggedin_user_id();
        if ( $user2_id == NULL ) $user2_id = bp_displayed_user_id();

        $banned_users = $this->get_banned_users( $user_id, 'arr' );

        $ret = ( in_array( $user2_id, $banned_users ) ) ? true : false;

        return apply_filters( 'mif_bpc_activity_stream_get_banned_users', $ret, $user_id, $user2_id );
    }


    // 
    // Получить пользователей, которых нельзя блокировать
    // 

    public function get_unbanned_users( $mode = 'ids' )
    {
        // возвращает массив or строку пользователей, которых нельзя блокировать
        
        // Здесь можно менять список неблокируемых пользователей (массив nicenames)
        $unbanned_users = apply_filters( 'mif_bpc_activity_stream_get_unbanned_users', $this->unbanned_users );
        $unbanned_users = array_unique( $unbanned_users ); // массив nicenames

        if ( $mode == 'ids' || $mode = 'ids_arr' ) {

            if ( ! $unbanned_users_ids_arr = wp_cache_get( 'unbanned_users' ) ) {

                $unbanned_users_ids_arr = array();

                foreach ( (array) $unbanned_users as $item ) {

                    if ( trim( $item ) == '' ) continue;

                    $user = get_user_by( 'slug', $item ); 
                    if ( is_object( $user ) ) $unbanned_users_ids_arr[] = $user->ID;

                }
            
                // Здесь можно менять список неблокируемых пользователей (массив id)
                $unbanned_users_ids_arr = apply_filters( 'mif_bpc_activity_stream_get_unbanned_users_ids', $unbanned_users_ids_arr );

                wp_cache_set( 'unbanned_users', $unbanned_users_ids_arr );

            }

            // вернуть id в массиве
            if ( $mode == 'ids_arr' ) return $unbanned_users_ids_arr;

            // вернуть id в строке через запятую
            if ( $mode == 'ids' ) return implode( ',', $unbanned_users_ids_arr );

        }

        // вернуть массив nicenames
        return $unbanned_users;
    }




}



?>