<?php

//
// Configuration механизма подписчиков
// 
//


defined( 'ABSPATH' ) || exit;

if ( mif_bpc_options( 'followers' ) ) {

    global $mif_bpc_followers;
    $mif_bpc_followers = new mif_bpc_followers();

}


class mif_bpc_followers {

    //
    // Механизм подписчиков - подписчиками считаются те, кто имеет статус неподтвержденной дружбы 
    //

    function __construct()
    {

        // Add записи читаемых пользователей в ленту активности
        add_filter( 'mif_bpc_activity_stream_friends', array( $this, 'activity_stream' ), 10, 2 );

        // Страницы подписчиков и читаемых
        add_action( 'bp_activity_setup_nav', array( $this, 'followers_nav' ) );
        add_action( 'bp_activity_setup_nav', array( $this, 'subscriptions_nav' ) );
        // add_action( 'bp_activity_setup_nav', array( $this, 'delete_requests_nav' ) );
        add_action( 'bp_init', array( $this, 'delete_requests_nav' ) );

        // Кнопки в списке пользователей
        add_action( 'bp_get_add_friend_button', array( $this, 'friend_button' ) );
        add_action( 'wp_print_scripts', array( $this, 'load_js_helper' ) );            				
        // add_action( 'wp_ajax_awaiting-response', array( $this, 'awaiting_response_ajax_helper' ) );
        add_action( 'wp_ajax_mif-bpc-friendship-actions', array( $this, 'ajax_helper' ) );
        add_action( 'bp_directory_members_actions', array( $this, 'add_custom_button' ), 20 );

        // Корректировка поведения стандартных кнопок
        add_action( 'friends_friendship_post_delete', array( $this, 'friendship_delete' ), 10, 2 );
        add_action( 'friends_friendship_accepted', array( $this, 'friendship_accepted' ), 10, 3 );
        add_filter( 'bp_get_friend_reject_request_link', array( $this, 'reject_request_link' ) );

        // Строка "Subscribe" в меню блокировки пользователя
        add_filter( 'mif_bpc_banned_user_button', array( $this, 'following_user_menu' ) );
        add_action( 'wp_ajax_following-user-button', array( $this, 'following_user_menu_ajax_helper' ) );

        // Корректировка ссылки в уведомлении
        add_filter( 'bp_friends_single_friendship_request_notification', array( $this, 'notification_link' ) );
        add_filter( 'bp_friends_multiple_friendship_request_notification', array( $this, 'notification_link' ) );

        
        // add_action( 'bp_get_add_friend_button', array( $this, 'remove_old_friend_button' ) );
    
    }


    //
    // Уточнить кнопси дружбы и подписки
    //

    function friend_button( $button )
    {

		switch ( $button['id'] ) {

			case 'not_friends' :
                $button['link_text'] = __( 'Add to friends', 'mif-bpc' );
                $button['wrapper_class'] = 'custom-friendship-button not_friends';
				break;

			case 'awaiting_response' :
                $button['link_text'] = __( 'Accept the friendship', 'mif-bpc' );
                $secondary = ( $this->get_not_now_status() ) ? ' secondary' : '';
                $button['wrapper_class'] = 'custom-friendship-button' . $secondary . ' awaiting_response_friend';
                $button['link_href'] = bp_get_friend_accept_request_link();
				break;

			case 'is_friend' :
                $button['link_text'] = __( 'Cancel the friendship', 'mif-bpc' );
                $button['wrapper_class'] = 'custom-friendship-button secondary is_friend';
				break;

			case 'pending' :
                $button['link_text'] = __( 'Unsubscribe', 'mif-bpc' );
                $button['wrapper_class'] = 'custom-friendship-button pending_friend';
				break;

        }
        // p($button['id']);
                // p($button);
        $button['block_self'] = false;

        return apply_filters( 'mif_bpc_followers_friend_button', $button );
    }


    //
    // Обработка нажатия кнопок дружбы и подписки
    //

    function ajax_helper()
    {
        $target_user_id = (int) $_POST['user_id'];
        $current_user_id = bp_loggedin_user_id();

        $action_id = sanitize_text_field( $_POST['action_id'] );
        
        if ( empty( $target_user_id ) || empty( $current_user_id ) ) wp_die();

        $friendship_status = friends_check_friendship_status( $current_user_id, $target_user_id );
        $friendship_id = friends_get_friendship_id( $current_user_id, $target_user_id );

        if ( $action_id == 'friend' ) {

            switch ( $friendship_status ) {

                case 'not_friends' :
                    check_ajax_referer( 'friends_add_friend' );
                    friends_add_friend( $current_user_id, $target_user_id );
                    break;

                case 'awaiting_response' :
                    check_ajax_referer( 'friends_accept_friendship' );
                    friends_accept_friendship( $friendship_id );
                    break;

                case 'is_friend' :
                    check_ajax_referer( 'friends_remove_friend' );
                    friends_remove_friend( $current_user_id, $target_user_id );
                    break;

                case 'pending' :
                    check_ajax_referer( 'friends_withdraw_friendship' );
                    friends_withdraw_friendship( $current_user_id, $target_user_id );
                    break;

            }

            bp_add_friend_button( $target_user_id );

        } else {

            switch ( $action_id ) {

                case 'not_now' :
                    check_ajax_referer( 'friends_not_now' );
                    $this->add_not_now_status( $target_user_id, $current_user_id );

                    bp_notifications_mark_notifications_by_item_id( $current_user_id, $target_user_id, buddypress()->friends->id, 'friendship_request' );

                    break;

            }

        }

        wp_die();
    }


    function load_js_helper()
    {
        wp_register_script( 'mif_bpc_followers', plugins_url( '../js/followers.js', __FILE__ ) );  
        wp_enqueue_script( 'mif_bpc_followers' );
    }


    //
    // Дополнительные кнопки в списке пользователей
    //

    function add_custom_button()
    {
        $target_user_id = bp_get_member_user_id();

        if ( empty( $target_user_id ) ) return false;

        $is_friend = bp_is_friend( $target_user_id );
        $not_now_status = $this->get_not_now_status( $target_user_id );

        if ( $is_friend == 'awaiting_response' && ! $not_now_status ) {


            $args = array(
					'id'                => 'not_now',
					'component'         => 'friends',
					'must_be_logged_in' => true,
					'block_self'        => false,
					'wrapper_class'     => 'custom-friendship-button not_now',
					'wrapper_id'        => 'not-now-button-' . $target_user_id,
					'link_href'         => wp_nonce_url( bp_loggedin_user_domain() . bp_get_friends_slug() . '/not_now/' . $target_user_id . '/', 'friends_not_now' ),
					'link_text'         => __( 'Not now', 'mif-bpc' ),
					'link_id'           => 'not_now-' . $target_user_id,
					'link_rel'          => 'remove',
					'link_class'        => 'friendship-button not_now remove'
            );

            echo bp_get_button( $args );

        }

    }



    //
    // Проверить статус "не сейчас" (про добавление в друзья)
    //

    function get_not_now_status( $target_user_id = NULL, $current_user_id = NULL )
    {
        if ( $target_user_id == NULL ) $target_user_id = bp_get_member_user_id();
        if ( $current_user_id == NULL ) $current_user_id = bp_loggedin_user_id();
        
        if ( ! $arr = wp_cache_get( 'mif_bpc_not_now_ids', $current_user_id ) ) {

            $arr = $this->get_not_now_array( $current_user_id );
            wp_cache_set( 'mif_bpc_not_now_ids', $arr, $current_user_id );

        }

        $ret = ( in_array( $target_user_id, $arr ) ) ? true : false;

        return apply_filters( 'mif_bpc_get_not_now_status', $ret, $current_user_id, $target_user_id );
    }


    //
    // Add пользователя в список "не сейчас" (про добавление в друзья)
    //

    function add_not_now_status( $target_user_id, $current_user_id = NULL )
    {
        // if ( $target_user_id == NULL ) $target_user_id = bp_get_member_user_id();
        if ( $current_user_id == NULL ) $current_user_id = bp_loggedin_user_id();

        $arr = $this->get_not_now_array( $current_user_id );
        $arr[] = $target_user_id;

        $ret = $this->set_not_now_array( $current_user_id, $arr );

        return $ret;
    }

    //
    // Delete пользователя из списка "не сейчас" (про добавление в друзья)
    //

    function delete_not_now_status( $target_user_id, $current_user_id = NULL )
    {
        // if ( $target_user_id == NULL ) $target_user_id = bp_get_member_user_id();
        if ( $current_user_id == NULL ) $current_user_id = bp_loggedin_user_id();

        $arr = $this->get_not_now_array( $current_user_id );
        $arr = array_diff( $arr, array( $target_user_id ) );

        $ret = $this->set_not_now_array( $current_user_id, $arr );

        return $ret;
    }

    //
    // Получить массив пользователей со статусом "не сейчас" (про добавление в друзья)
    //

    function get_not_now_array( $current_user_id )
    {
        $arr = array();
        $data = get_user_meta( $current_user_id, 'mif_bpc_not_now_ids', true );
        if ( isset( $data ) ) $arr = explode( ',', $data );
        return $arr;
    }

    //
    // Update массив пользователей со статусом "не сейчас" (про добавление в друзья)
    //

    function set_not_now_array( $current_user_id, $arr )
    {
        $arr = array_unique( $arr );
        sort( $arr );
        $ret = update_user_meta( $current_user_id, 'mif_bpc_not_now_ids', implode( ',', $arr ) );
        wp_cache_delete( 'mif_bpc_not_now_ids', $current_user_id );
        return $ret;
    }


    //
    // При удалении дружбы - переводить пользователей в статус фолловеров
    //

    function friendship_delete( $initiator_userid, $friend_userid )
    {
        remove_action( 'friends_friendship_requested', 'bp_friends_friendship_requested_notification' );
        remove_action( 'friends_friendship_requested', 'friends_notification_new_request' );

        friends_add_friend( $friend_userid, $initiator_userid );
    }


    //
    // При добавлении дружбы - удалять статус "не сейчас"
    //

    function friendship_accepted( $friendship_id, $initiator_user_id, $friend_user_id )
    {
        $this->delete_not_now_status( $initiator_user_id );
    }


    //
    // Поломать кнопку отказа на запрос дружбы
    //

    function reject_request_link( $link )
    {
        return false;
    }


    //
    // Add строку "Subscribe" в меню сблокировки пользователей (если такой модуль активен)
    //

    function following_user_menu( $arr )
    {
        global $bp;

        $user_id = bp_displayed_user_id();
        $following_url = $bp->loggedin_user->domain . $bp->profile->slug;
        $following_url_request = wp_nonce_url( $following_url . '/following/requests/' . $user_id . '/', 'mif_bpc_following_user_button' );
        
        array_unshift( $arr, array( 
                                'href' => $following_url_request, 
                                'descr' => $this->get_caption(), 
                                'class' => 'following', 
                                'data' => array( 'userid' => $user_id ) 
                                ) );
        
        return $arr;
    }


    function following_user_menu_ajax_helper()
    {
        check_ajax_referer( 'mif_bpc_following_user_button' );

        $target_user_id = (int) $_POST['user_id'];
        $current_user_id = bp_loggedin_user_id();

        if ( empty( $target_user_id ) || empty( $current_user_id ) ) wp_die();

        $friendship_status = friends_check_friendship_status( $current_user_id, $target_user_id );
        $friendship_id = friends_get_friendship_id( $current_user_id, $target_user_id );

        switch ( $friendship_status ) {

            case 'not_friends' :
                $this->add_following( $target_user_id, $current_user_id );
                break;

            case 'awaiting_response' :
                friends_accept_friendship( $friendship_id );
                break;

            case 'is_friend' :
                friends_remove_friend( $current_user_id, $target_user_id );
                break;

            case 'pending' :
                friends_withdraw_friendship( $current_user_id, $target_user_id );
                break;

        }

        echo $this->get_caption();

        wp_die();
    }


    function get_caption()
    {
        $user_id = bp_displayed_user_id();
        $is_friend = bp_is_friend( $user_id );

    	switch ( $is_friend ) {

			case 'awaiting_response' :
                $caption = __( 'Accept the friendship', 'mif-bpc' );
				break;

			case 'is_friend' :
                $caption = __( 'Cancel the friendship', 'mif-bpc' );
				break;

			case 'pending' :
                $caption = __( 'Unsubscribe', 'mif-bpc' );
				break;

			default :
                $caption = __( 'Subscribe', 'mif-bpc' );
				break;

        }

        return $caption;
    }


    // 
    // Subscribe на пользователя
    // 

    function add_following( $target_user_id, $current_user_id )
    {
        if ( $target_user_id == NULL ) $target_user_id = bp_get_member_user_id();
        if ( $current_user_id == NULL ) $current_user_id = bp_loggedin_user_id();

        remove_action( 'friends_friendship_requested', 'bp_friends_friendship_requested_notification' );
        remove_action( 'friends_friendship_requested', 'friends_notification_new_request' );

        friends_add_friend( $current_user_id, $target_user_id );

        $this->add_not_now_status( $current_user_id, $target_user_id );
    }


    // 
    // Add записи читаемых пользователей в ленту активности
    // 

    function activity_stream( $friends, $user_id )
    {
        $followers = $this->get_subscriptions_ids( $user_id );
        $friends = array_merge( $friends, $followers );

        return apply_filters( 'mif_bpc_followers_activity_stream', $friends, $user_id );
    }


    // 
    // Получить массив ID подписчиков
    // 

    function get_subscriptions_ids( $user_id = NULL )
    {
        if ( $user_id == NULL ) $user_id = bp_displayed_user_id();

        $subscriptions = (array) BP_Friends_Friendship::get_friendships( $user_id, array( 'initiator_user_id' => $user_id, 'is_confirmed' => 0 ) );

        $arr = array();
        foreach ( $subscriptions as $item ) $arr[] = $item->friend_user_id;

        return $arr;
    }


    // 
    // Получить массив ID фолловеров
    // 

    function get_followers_ids( $user_id = NULL )
    {
        if ( $user_id == NULL ) $user_id = bp_displayed_user_id();

        $followers = (array) BP_Friends_Friendship::get_friendships( $user_id, array( 'friend_user_id' => $user_id, 'is_confirmed' => 0 ) );

        $arr = array();
        foreach ( $followers as $follower ) $arr[] = $follower->initiator_user_id;

        return $arr;
    }



    // 
    // Страница подписчиков
    // 

    function followers_nav()
    {
        global $bp;

        $parent_url = $bp->displayed_user->domain . $bp->friends->slug . '/';
        $parent_slug = $bp->friends->slug;

        $sub_nav = array(  
                'name' => __( 'Subscribers', 'mif-bpc' ), 
                'slug' => 'followers', 
                'parent_url' => $parent_url, 
                'parent_slug' => $parent_slug, 
                'screen_function' => array( $this, 'screen' ), 
                'position' => 50,
                // 'user_has_access'=>  bp_is_my_profile() 
            );

        bp_core_new_subnav_item( $sub_nav );
       
    }



    // 
    // Страница тех, на кого подписан
    // 

    function subscriptions_nav()
    {
        global $bp;

        $parent_url = $bp->displayed_user->domain . $bp->friends->slug . '/';
        $parent_slug = $bp->friends->slug;

        $sub_nav = array(  
                'name' => __( 'Reading', 'mif-bpc' ), 
                'slug' => 'subscriptions', 
                'parent_url' => $parent_url, 
                'parent_slug' => $parent_slug, 
                'screen_function' => array( $this, 'screen' ), 
                'position' => 60,
                // 'user_has_access'=>  bp_is_my_profile() 
            );

        bp_core_new_subnav_item( $sub_nav );
       
    }

    //
    // Содержимое страниц
    //

    function screen()
    {
        global $bp;
        // add_action( 'bp_template_title', array( $this, 'title' ) );
        add_action( 'bp_template_content', array( $this, 'body' ) );
        bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
    }

    function body()
    {
        // $out = '';
        global $mif_bpc_followers;

        add_filter( 'bp_ajax_querystring', array( $mif_bpc_followers, 'members_param' ), 100, 2 );
        bp_get_template_part( 'members/members-loop' ) ;
        remove_filter( 'bp_ajax_querystring', array( $mif_bpc_followers, 'members_param' ) );
    }

    function members_param( $members_param, $object )
    {
        global $bp;

        if ( $bp->current_action == 'subscriptions' ) {

            $members_param = array( 'include' => implode( ',', $this->get_subscriptions_ids() ) );

        } elseif ( $bp->current_action == 'followers' ) {

            $members_param = array( 'include' => implode( ',', $this->get_followers_ids() ) );

        }

        add_filter( 'bp_is_current_component', 'no_friends_page', 10, 2 );

        return apply_filters( 'mif_bpc_followers_members_param', $members_param, $bp->current_action ) ;
    }    



    //
    // Delete стандартную страницу запросов
    //

    function notification_link( $notification )
    {
        global $bp;

        $new_url = $bp->displayed_user->domain . $bp->friends->slug . '/followers/';
        $pattern = '/href="[^"]+"/';
        $notification = preg_replace( $pattern, 'href="' . $new_url . '"', $notification ); 
        
        return $notification;
    }



    //
    // Delete стандартную страницу запросов
    //

    function delete_requests_nav()
    {
        bp_core_remove_subnav_item( 'friends', 'requests' );
    }


}

?>