<?php

//
// Кнопка "Нравится"
// 
//

defined( 'ABSPATH' ) || exit;

if ( mif_bpc_options( 'like-button' ) ) {

    global $mif_bpc_like_button;
    $mif_bpc_like_button = new mif_bpc_like_button();

}


class mif_bpc_like_button {

    //
    // Механизм "Нравится" - у элементов активности есть мета-поле 'mif_bpc_likes' со списком id пользовавтелей, 
    // нажавших "Нравится". Это используется при выводе кнопки "Нравится"
    //
    // Есть мета-поле 'mif_bpc_likes', где указывается время последнего нажатия "Нравится"
    //
    // "Нравится" для записей блога связывается с данными элемента ленты активности о публикации этой записи.
    //

    //
    // Ключ мета-поля
    //

    public $meta_key = 'mif_bpc_likes';
    
    // 
    // Количество аватар, выводимых в подсказке
    // 

    public $number = 5;

    //
    // Элементы активности, которые нельзя отмечать кнопкой "Нравится"
    //

    public $unlikes_activity = array( 'activity_update' );
    
    //
    // Размер аватар, выводимых в подсказке
    //
    
    public $avatar_size  = 30;


    function __construct()
    {

        add_action( 'bp_activity_entry_meta', array( $this, 'like_button' ), 10 );
        add_action( 'wp_print_scripts', array( $this, 'load_js_helper' ) );            				
        add_action( 'wp_ajax_like-button-press', array( $this, 'ajax_helper' ) );

        $this->number = apply_filters( 'mif_bpc_like_buttons_avatar_number', $this->number );
        $this->avatar_size  = apply_filters( 'mif_bpc_like_buttons_avatar_size', $this->avatar_size );

        // Раскомментируйте на один раз эту строку для конвертации старых данных
        // add_action( 'init', array( $this, 'likes_old_to_new_converted' ) );

    }


    //
    // Показать кнопку "Нравится"
    //

    function like_button()
    {
        $likes = $this->get_likes();
        $count = count( $likes );

        global $activities_template;
        $user_id = $activities_template->activity->user_id;

		$url = wp_nonce_url( home_url( bp_get_activity_root_slug() . '/like/' . bp_get_activity_id() . '/' ), 'mif_bpc_like_button_press' );

        $active = ( $this->is_liked() ) ? ' active' : '';

        $avatar_hint = $this->avatar_hint();

        $button = '<div class="like like-user-' . $user_id  . $active . '"><a href="' . $url . '" class="button bp-primary-action like"><i class="fa fa-heart" aria-hidden="true"></i> <span>' . $count . '</span></a>' . $avatar_hint . '</div>';

        // Здесь можно убрать кнопку "Нравится" для заблокированных пользователей

        $button = apply_filters( 'mif_bpc_like_button_like_button', $button );

        echo $button;
    }

    public function load_js_helper()
    {
        wp_register_script( 'mif_bpc_like-button', plugins_url( '../js/like-button.js', __FILE__ ) );  
        wp_enqueue_script( 'mif_bpc_like-button' );
    }

    public function ajax_helper()
    {
        check_ajax_referer( 'mif_bpc_like_button_press' );

        if ( ! mif_bpc_options( 'like-button' ) ) wp_die();

        $activity_id = (int) $_POST['activity_id'];
        $user_id = bp_loggedin_user_id();
        
        if ( $this->is_liked( $activity_id, $user_id ) ) {

            if ( $this->unliked( $activity_id, $user_id ) ) echo 'unliked';

        } else {

            if ( $this->liked( $activity_id, $user_id ) ) echo 'liked';

        }

        $this->get_cache_avatar_data( $activity_id, 'create_new_cache' );

        wp_die();
    }


    // 
    // Выводит аватары во всплывающей подсказке
    // 

    function avatar_hint()
    {
        $out = '';

        $avatars = $this->get_avatars();
        $user_ids = $this->get_likes();

        if ( $avatars && ! bp_is_single_activity() ) {

            $out .= '<div class="mif-bpc-hint"><div>';
            $out .= $avatars;
            // if ( count( $user_ids ) > $this->number ) $out .= '<span class="avatar more"><a href="' . bp_get_activity_comment_permalink() . '"><span class="wrap"><i class="fa fa-arrow-right" aria-hidden="true"></i></span></a></span>';
            // if ( count( $user_ids ) > $this->number ) $out .= '<p>И еще 15 <a href="' . bp_get_activity_comment_permalink() . '">подробнее</a></p>';
            $out .= '</div></div>';

        }

        return $out;
    }


    // 
    // Получить аватарки тех, кто нажимал "Нравится"
    // 

    function get_avatars( $activity_id = NULL )
    {

        if ( $activity_id == NULL ) $activity_id = bp_get_activity_id();

        $user_avatars = $this->get_cache_avatar_data( $activity_id );

        $current_user_id = bp_loggedin_user_id();

        unset( $user_avatars[$current_user_id] );

        $user_ids = $this->get_likes( $activity_id );

        // Удалим тех, кто есть в кэше, но кого нет в лайках (заблокированные пользователи)

        foreach ( (array) $user_avatars as $key => $item ) {

            if ( ! in_array( $key, $user_ids ) ) unset( $user_avatars[$key] );

        }

        shuffle( $user_avatars );

        $friends_ids = friends_get_friend_user_ids( $current_user_id );

        $arr = array();

        // Сначала выбрать друзей с аватарками

        foreach ( (array) $user_avatars as $key => $item ) {

            if ( count( $arr ) >= $this->number ) break;

            if ( $item['type'] == 'default' ) continue;
            if ( ! in_array( $key, $friends_ids ) ) continue;

            $arr[] = $item;
            unset( $user_avatars[$key] );

        }

        // Далее, если не хватает, добавить не друзей с аватарками

        foreach ( (array) $user_avatars as $key => $item ) {

            if ( count( $arr ) >= $this->number ) break;

            if ( $item['type'] == 'default' ) continue;

            $arr[] = $item;
            unset( $user_avatars[$key] );

        }

        // Теперь, если опять не хватает, добавить друзей без аватарок

        foreach ( (array) $user_avatars as $key => $item ) {

            if ( count( $arr ) >= $this->number ) break;

            if ( ! in_array( $key, $friends_ids ) ) continue;

            $arr[] = $item;
            unset( $user_avatars[$key] );

        }

        // Ну и наконец, если надо, добавить всех остальных

        foreach ( (array) $user_avatars as $key => $item ) {

            if ( count( $arr ) >= $this->number ) break;

            $arr[] = $item;
            unset( $user_avatars[$key] );

        }

        if ( $arr ) {

            shuffle( $arr );

            $arr_html = array();

            $arr_html[] = $this->get_item_avatar( array( 'ID' => $current_user_id, 
                                                        'img' => $this->get_avatar( $current_user_id, $this->avatar_size ),
                                                        'url' => bp_loggedin_user_domain(),
                                                        'name' => bp_core_get_user_displayname( $current_user_id ) ), 'current_user' );

            foreach ( (array) $arr as $key => $item ) $arr_html[] = $this->get_item_avatar( $item, 'n' . $key );

            $out = implode( '', $arr_html );
            
            return apply_filters( 'mif_bpc_like_button_get_avatars', $out, $activity_id, $current_user_id );

        } else {

            return false;

        }

    }


    //
    // Выбирает данные аватарок из кэша or формирует этот кэш
    //

    function get_cache_avatar_data( $activity_id = NULL, $nocache = false )
    {

        $cache_data = bp_activity_get_meta( $activity_id, 'cache_liked_avatar', true );
        if ( empty( $cache_data ) || $cache_data['expires'] < time() || $nocache != false ) {

            $user_ids = $this->get_likes_raw( $activity_id );
            
            if ( $user_ids === array() ) return array();

            $args = array(
                    'max' => $this->number * 10,
                    'per_page' => $this->number * 10,
                    'include' => $user_ids,
                    // 'orderby' => 'rand',
                    'type' => 'random',
            );
            
            $user_data = array();

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

            if ( $user_data === array() ) return array();

            $avatar_dir = trailingslashit( bp_core_avatar_upload_path() ) . trailingslashit( 'avatars' ); 

            $user_data_clean = array();

            // Выберем сначала те аватарки, которые с картинками

            foreach ( (array) $user_data as $key => $item ) {

                if ( file_exists( $avatar_dir . $item['ID'] ) ) {

                    $item['type'] = 'img';
                    $user_data_clean[] = $item;
                    unset( $user_data[$key] );

                }
                
                if ( count( $user_data_clean ) >= $this->number * 5 ) break;

            }

            // Добавим и аватарки без картинок, если их не хватает

            foreach ( (array) $user_data as $key => $item ) {

                if ( count( $user_data ) >= $this->number * 5 ) break;
                $item['type'] = 'default';
                $user_data_clean[] = $item;

            }

            // Массив с HTML аватарок

            $user_avatars = array();

            foreach ( (array) $user_data_clean as $item ) {
                
                $item['img'] = $this->get_avatar( $item['ID'], $this->avatar_size );
                $user_avatars[$item['ID']] = $item;
                
            }
            
            // Здесь можно изменить время жизни аватарок в кеше. По умолчанию 1 час = 3600 секунд.
            // Кеш сбрасывается, когда обновляется список лайков
            
            $ttl = apply_filters( 'mif_bpc_like_buttons_avatar_cache_ttl', 3600 );

            $expires = time() + $ttl;

            bp_activity_update_meta( $activity_id, 'cache_liked_avatar', array( 'expires' => $expires, 'user_avatars' => $user_avatars ) );

        } else {

            $user_avatars = $cache_data['user_avatars'];

        }

        return apply_filters( 'mif_bpc_like_cache_avatar_data', $user_avatars );

    }


    //
    // Получить HTML-блок одной аватарки
    //

    function get_item_avatar( $item, $class = '' )
    {

        $before = ( $item['url'] ) ? '<a href="' . $item['url'] . '">' : '';
        $after = ( $item['url'] ) ? '</a>' : '';

        if ( $class ) $class = ' ' . $class;

        // $ret = '<span class="avatar' . $class . '" title="' . $item['name'] . '">' . $before . get_avatar( $item['ID'], $avatar_size ) . $after . '</span>';
        $ret = '<span class="avatar' . $class . '" title="' . $item['name'] . '">' . $before . $item['img'] . $after . '</span>';

        return $ret;

    }


    //
    // Стандартный get_avatar, но с кэшированием
    //

    function get_avatar( $user_id, $size )
    {

        if ( ! $avatar = wp_cache_get( 'user_avatar_' . $size, $user_id ) ) {

            $avatar = get_avatar( $user_id, $size );
            wp_cache_set( 'user_avatar_' . $size, $avatar, $user_id );

        }

        return $avatar;
    }


    //
    // Add new отметку "Нравится"
    //

    function liked( $activity_id = NULL, $user_id = NULL )
    {

        if ( $activity_id == NULL ) return;
        if ( $user_id == NULL ) return;

        $likes_ids = bp_activity_get_meta( $activity_id, $this->meta_key, true );
        $likes_arr = explode( ',', $likes_ids );
        $likes_arr[] = (int) $user_id;

        $likes_arr = array_unique( $likes_arr );
        $likes_arr = array_diff( $likes_arr, array( '' ) );

        $likes_ids = implode( ',', $likes_arr );

        $ret = bp_activity_update_meta( $activity_id, $this->meta_key, $likes_ids );
        bp_activity_update_meta( $activity_id, $this->meta_key . '_timestamp', time() );
        
        wp_cache_delete( 'likes_arr', $activity_id );

        // Здесь можно отслеживать добавление "Нравится". Например, отправлять уведомление пользователю

        if ( $ret ) do_action( 'mif_bpc_like_button_liked', $activity_id, $user_id );

        return $ret;

    }


    //
    // Убрать отметку "Нравится"
    //

    function unliked( $activity_id = NULL, $user_id = NULL )
    {

        if ( $activity_id == NULL ) return;
        if ( $user_id == NULL ) return;

        $likes_ids = bp_activity_get_meta( $activity_id, $this->meta_key, true );
        $likes_arr = explode( ',', $likes_ids );
        $likes_arr = array_diff( $likes_arr, array( $user_id ) );

        $likes_arr = array_unique( $likes_arr );
        $likes_arr = array_diff( $likes_arr, array( '' ) );

        $likes_ids = implode( ',', $likes_arr );

        $ret = bp_activity_update_meta( $activity_id, $this->meta_key, $likes_ids );
        
        wp_cache_delete( 'likes_arr', $activity_id );

        // Здесь можно отслеживать снятие "Нравится"

        if ( $ret ) do_action( 'mif_bpc_like_button_unliked', $activity_id, $user_id );

        return $ret;

    }


    //
    // Проверить, есть ли пользователь в числе тех, кому понравилось
    //

    function is_liked( $activity_id = NULL, $user_id = NULL )
    {

        if ( $activity_id == NULL ) $activity_id = bp_get_activity_id();
        if ( $user_id == NULL ) $user_id = bp_loggedin_user_id();

        $likes_arr = $this->get_likes( $activity_id );

        $ret = ( isset( $likes_arr ) && in_array( $user_id, $likes_arr ) ) ? true : false;

        return apply_filters( 'mif_bpc_like_button_is_likes', $ret, $activity_id, $user_id );

    }


    //
    // Получить массив ID пользователей, которым нравится элемент активности
    //

    function get_likes( $activity_id = NULL )
    {

        if ( $activity_id == NULL ) $activity_id = bp_get_activity_id();

        if ( ! $likes_arr = wp_cache_get( 'likes_arr', $activity_id ) ) {

            $likes_arr = $this->get_likes_raw( $activity_id );

            // Здесь можно уточнить список пользователей. Например, удалить тех, кто заблокирован
            
            $likes_arr = apply_filters( 'mif_bpc_like_button_get_likes', $likes_arr, $activity_id );

            wp_cache_set( 'likes_arr', $likes_arr, $activity_id );

        }

        return $likes_arr;

    }


    //
    // Получить массив ID пользователей, которым нравится элемент активности
    // Bез кэша и фильтров
    //

    function get_likes_raw( $activity_id = NULL )
    {

        if ( $activity_id == NULL ) $activity_id = bp_get_activity_id();

        $likes_ids = bp_activity_get_meta( $activity_id, $this->meta_key, true );
        $likes_arr = ( $likes_ids ) ? explode( ',', $likes_ids ) : NULL;
        $likes_arr = array_unique( (array) $likes_arr );
        $likes_arr = array_diff( (array) $likes_arr, array( '' ) );

        return $likes_arr;

    }


    //
    // Получить список типов активности, котрая не может нравиться
    //

    function get_unlikes_activity()
    {
        return apply_filters( 'mif_bpc_like_button_get_unlikes_activity', $this->unlikes_activity );
    }



    //
    // Конвертация данных (от плагина BuddyPress Like)
    //

    function likes_old_to_new_converted( $activity_id = NULL )
    {
        global $wpdb;

        $table = _get_meta_table( 'activity' );
        $arr = $wpdb->get_results( "SELECT activity_id, meta_value FROM $table WHERE meta_key='liked_count'", ARRAY_A );

        foreach ( (array) $arr as $item ) {
            $likes_ids = implode( ',', array_keys( unserialize( $item['meta_value'] ) ) );

		    bp_activity_update_meta( $item['activity_id'], $this->meta_key, $likes_ids );
            bp_activity_delete_meta( $item['activity_id'], 'liked_count' );

        }

        $table = _get_meta_table( 'user' );
        $arr = $wpdb->get_results( "SELECT user_id FROM $table WHERE meta_key='bp_liked_activities'", ARRAY_A );
        
        foreach ( (array) $arr as $item ) {

            delete_user_meta( $item['user_id'], 'bp_liked_activities' );

        }

    }





}



?>