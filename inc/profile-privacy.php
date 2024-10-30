<?php

//
// Configuration системы приватности профиля
//
//


defined( 'ABSPATH' ) || exit;


if ( mif_bpc_options( 'profile-privacy' ) ) {

    global $mif_bpc_profile_privacy;
    $mif_bpc_profile_privacy = new mif_bpc_profile_privacy();

}


class mif_bpc_profile_privacy {

    //
    // Механизм ограничение доступа - у пользователя есть мета-поле "mif_bpc_privacy_level", значение которого анализируется при просмотре страницы. 
    // Если ваш статус не удовлетворяет уровню доступа, то страница не показывается.
    //
    // Уровни доступа:
    //
    // 0  - полный доступ
    // 10 - только авторизованные пользователи
    // 20 - только подписчики
    // 30 - только на кого подписался я
    // 40 - только друзья
    // 50 - только я
    // 
    //
    // Мета-поле "mif_bpc_privacy_mode" - режим ограничения доступа
    //
    // Возможные значения:
    //
    // activity_hidden - не показывать ленту активности и другие данные профиля
    // profile_hidden  - не показывать ничего на странице пользователя
    // profile_deleted - не показывать страницу профиля и не отображать пользователя в общем списке пользователей
    //

    //
    // Access level по умолчанию
    //
    
    public $default_privacy = 0;

    //
    // Режим ограничения доступа по умолчанию
    //
    
    public $default_mode = 'activity_hidden';
    // public $default_mode = 'profile_hidden';
    // public $default_mode = 'profile_deleted';

    //
    // Количество дней отсутствия активности пользователя, когда включать мягкий режим ограничений (profile_hidden)
    // Страница доступна только тем, с кем был какой-то контакт (более, чем просто авторизованный пользователь)
    //
    public $profile_hidden_time = 365;
    public $profile_hidden_privacy = 11;

    //
    // Количество дней отсутствия активности пользователя, когда включать жесткий режим ограничений (profile_delete)
    // Страница доступна только друзьям
    //
    
    public $profile_deleted_time = 1095; // 365 * 3
    public $profile_deleted_privacy = 11;


    function __construct()
    {

        // Страница настройки
        add_action( 'bp_activity_setup_nav', array( $this, 'profile_privacy_nav' ) );
        add_action( 'bp_init', array( $this, 'profile_privacy_helper' ) );

        // activity_hidden
        add_filter( 'bp_get_template_part', array( $this, 'hide_profile_template_part' ), 10, 3 );
        add_action( 'bp_before_member_home_content', array( $this, 'hide_member_home_content' ) );
        add_action( 'bp_get_activity_latest_update', array( $this, 'hide_activity_latest_update' ) );

        // profile_hidden
        add_action( 'bp_before_member_header', array( $this, 'hide_avatar_hook' ) );
        add_action( 'bp_before_member_header_meta', array( $this, 'hide_member_header' ) );
        add_action( 'body_class', array( $this, 'add_body_class' ) );

        // profile_deleted
        add_filter( 'bp_activity_template_my_activity', array( $this, 'show_deleted_profile' ) );

        // profile_deleted (members-loop)
        add_action( 'bp_before_members_loop', array( $this, 'members_exclude_start' ) );
        add_action( 'bp_after_members_loop', array( $this, 'members_exclude_stop' ) );
        add_filter( 'bp_core_get_active_member_count', array( $this, 'member_count' ) );
        add_action( 'bp_core_activated_user', array( $this, 'clear_member_count_caches' ) );
        add_action( 'bp_core_process_spammer_status', array( $this, 'clear_member_count_caches' ) );
        add_action( 'bp_core_deleted_account', array( $this, 'clear_member_count_caches' ) );
        add_action( 'bp_first_activity_for_member', array( $this, 'clear_member_count_caches' ) );
        add_action( 'deleted_user', array( $this, 'clear_member_count_caches' ) );

        // Корректировка опций
        $this->default_privacy = get_option( 'mif_bpc_default_privacy', $this->default_privacy );
        $this->default_mode = get_option( 'mif_bpc_default_mode', $this->default_mode );
        $this->profile_hidden_time = get_option( 'mif_bpc_hidden_time', $this->profile_hidden_time );
        $this->profile_hidden_privacy = get_option( 'mif_bpc_hidden_privacy', $this->profile_hidden_privacy );
        $this->profile_deleted_time = get_option( 'mif_bpc_deleted_time', $this->profile_deleted_time );
        $this->profile_deleted_privacy = get_option( 'mif_bpc_deleted_privacy', $this->profile_deleted_privacy );

        // Установка профиля по умолчанию при активации нового пользователя
        add_action( 'bp_core_activated_user', array( $this, 'set_default_level' ) );

        // Раскомментировать на один раз для конвертации данных
        // add_action( 'bp_init', array( $this, 'old_level_correct' ) );

    }



    //
    // Проверить, является ли профиль открытым для данного пользователя
    //
    
    function is_displayed( $current_user_id = NULL, $profile_user_id = NULL )
    {
        if ( $current_user_id == NULL ) $current_user_id = bp_loggedin_user_id();
        if ( $profile_user_id == NULL ) $profile_user_id = bp_displayed_user_id();

        $privacy_level = $this->get_privacy_level();

        // Свою страницу показываем всегда
        if ( $current_user_id == $profile_user_id ) return true;

        // Администратор имеет доступ ко всем
        if ( current_user_can( 'manage_options' ) ) return true;

        // Уровень 0 - открытый профиль показываем всегда
        if ( $privacy_level == 0 ) return true;

        // Уровень 10 - показываем только авторизованным пользователям
        if ( $privacy_level == 10 && is_user_logged_in() ) return true;

        $friendship_status = friends_check_friendship_status( $current_user_id, $profile_user_id );
        $friendship_sheme = array( 'awaiting_response' => 20, 'pending' => 30, 'is_friend' => 40 );

        // Уровень 20, 30 и 40 - показывать в зависимости от состояния дружбы
        if ( isset( $friendship_sheme[$friendship_status] ) && $privacy_level <= $friendship_sheme[$friendship_status] ) return true;

        return apply_filters( 'mif_bpc_profile_privacy_is_displayed', false, $privacy_level, $current_user_id, $profile_user_id );
    }


    //
    // Получить уровень приватности профиля пользователя
    //

    function get_privacy_level( $user_id = NULL )
    {
        $arr = $this->get_privacy_mode_and_level( $user_id );
        return apply_filters( 'mif_bpc_profile_privacy_get_privacy_level', $arr['privacy_level'], $user_id );
    }


    //
    // Получить режим приватности профиля пользователя
    //

    function get_privacy_mode( $user_id = NULL )
    {
        $arr = $this->get_privacy_mode_and_level( $user_id );
        return apply_filters( 'mif_bpc_profile_privacy_get_privacy_mode', $arr['privacy_mode'], $user_id );
    }


    //
    // Получить массив режима доступа и режима приватности
    //

    function get_privacy_mode_and_level( $user_id = NULL )
    {
        if ( $user_id == NULL ) $user_id = bp_displayed_user_id();

        if ( ! $arr = wp_cache_get( 'privacy_mode_and_level', $user_id ) ) {

            // Узнать уровень доступа or установить значение по умолчанию, если уровень не указан
            $privacy_level = get_user_meta(  $user_id, 'mif_bpc_privacy_level', true );
            if ( $privacy_level === '' ) $privacy_level = $this->default_privacy;

            // Узнать режим доступа or установить значение по умолчанию, если режим не указан
            $privacy_mode = get_user_meta(  $user_id, 'mif_bpc_privacy_mode', true );
            if ( $privacy_mode === '' ) $privacy_mode = $this->default_mode;

            // Скорректировать режим и уровень по критерию последней активности
            $delta_day = timestamp_to_now( mif_bpc_get_last_activity_timestamp( $user_id ), 'day' );

            if ( $delta_day > $this->profile_hidden_time ) {

                $privacy_level = max( $privacy_level, $this->profile_hidden_privacy );
                if ( $privacy_mode != 'profile_deleted' ) $privacy_mode = 'profile_hidden';

            }

            if ( $delta_day > $this->profile_deleted_time ) {

                $privacy_level = max( $privacy_level, $this->profile_deleted_privacy );
                $privacy_mode = 'profile_deleted';

            }

            $arr = array( 'privacy_mode' => $privacy_mode, 'privacy_level' => $privacy_level );
            $arr = apply_filters( 'mif_bpc_profile_privacy_get_privacy_mode_and_level', $arr, $user_id, $delta_day );

            wp_cache_set( 'privacy_mode_and_level', $arr, $user_id );

        }
        return $arr;
    }





    //
    // Реализация режима profile_deleted
    //

    //
    // Показывать, что страница не найдена
    //
    
    function show_deleted_profile( $tpl )
    {
        if ( $this->is_displayed() ) return $tpl;
        if ( $this->get_privacy_mode() != 'profile_deleted' ) return $tpl;
        return apply_filters( 'mif_bpc_profile_privacy_show_deleted_profile', array( 'members/single/deleted', '404' ) );
    }


    //
    // Реализация режима profile_hidden
    //

    //
    // Убрать все кнопки из заголовка страницы
    //
    
    function hide_member_header()
    {
        if ( $this->is_displayed() ) return;
        if ( $this->get_privacy_mode() != 'profile_hidden' ) return;
     
        remove_all_filters( 'bp_member_header_actions' );

    }

    //
    // Add класс, чтобы убрать рамку заголовка
    //
    
    function add_body_class( $data )
    {
        if ( $this->is_displayed() ) return $data;
        if ( $this->get_privacy_mode() != 'profile_hidden' ) return $data;
     
        $data[] = 'profile-hidden';

        return $data;
    }

    //
    // Скрыть аватар пользователя (скрывается для незарегистрированных пользователей и если профиль имеет статус profile_hidden')
    //
    
    function hide_avatar_hook()
    {
        if ( is_user_logged_in() ) return;
        if ( $this->is_displayed() ) return;
        if ( $this->get_privacy_mode() != 'profile_hidden' ) return;

        global $mif_bpc_profile_privacy;
        add_filter( 'bp_core_fetch_avatar', array( $mif_bpc_profile_privacy, 'hide_avatar' ) );
    }

    function hide_avatar( $img )
    {
        global $mif_bpc_profile_privacy;
        remove_filter( 'bp_core_fetch_avatar', array( $mif_bpc_profile_privacy, 'hide_avatar' ) );

        $hidden_avatar_url = plugins_url( 'buddypress/bp-core/images/mystery-man.jpg' );
        $hidden_avatar_url = apply_filters( 'mif_bpc_profile_privacy_hidden_avatar_url', $hidden_avatar_url );

        // $pattern = "/src=\"[^\"]+\"/";
        $pattern = '/src="[^"]+"/';
        $img = preg_replace( $pattern, 'src="' . $hidden_avatar_url . '"', $img ); 

        return $img;
    }


    //
    // Реализация режима activity_hidden
    //

    //
    // Delete лишние вкладки профиля
    //
    
    function hide_member_home_content()
    {
        if ( $this->is_displayed() ) return;

        $nav_items = apply_filters( 'mif_bpc_profile_privacy_nav_items', array( 'profile', 'friends', 'groups', 'docs', 'gallery' ) );
        foreach ( $nav_items as $nav_item ) bp_core_remove_nav_item( $nav_item );
    }

    //
    // Не показывать последнюю активность в заголовке
    //

    function hide_activity_latest_update( $latest_update )
    {
        if ( $this->is_displayed() ) return $latest_update;
        return '';
    }

    //
    // Не показывать содержимое страниц по вкладкам профиля
    //

    function hide_profile_template_part( $templates, $slug, $name )
    {
        $blocked_pages = array(
                            'members/single/activity',
                            'members/single/blogs',
                            'members/single/friends',
                            'members/single/groups',
                            'members/single/messages',
                            'members/single/profile',
                            'members/single/forums',
                            'members/single/notifications',
                            'members/single/settings',
                            'members/single/plugins',
                            'members/single/docs'
                        );

        if ( ! in_array( $slug, $blocked_pages ) ) return $templates;

        if ( $this->is_displayed() ) return $templates;

        $privacy_level = $this->get_privacy_level();
        $msg = ( $privacy_level == 10 ) ? __( 'Only authorized users can access the page', 'mif-bpc' ) : __( 'Access to the page is restricted.', 'mif-bpc' );

        echo '<div id="message" class="info">
		<p>' . $msg . '</p>
		</div>';

        return false;
    }


    // 
    // Не показывать пользователей с давним сроком активности в списке всех пользователей
    // 

    function members_exclude_start()
    {
        global $mif_bpc_profile_privacy;
        add_filter( 'bp_user_query_uid_clauses', array( $mif_bpc_profile_privacy, 'members_loop_param' ), 10, 2 );
    }

    function members_exclude_stop()
    {
        global $mif_bpc_profile_privacy;
        remove_filter( 'bp_user_query_uid_clauses', array( $mif_bpc_profile_privacy, 'members_loop_param' ), 10, 2 );
    }

    function members_loop_param( $arr, $obj )
    {
        if ( $obj->query_vars_raw['user_id'] ) return $arr;
        if ( $obj->query_vars_raw['include'] ) return $arr;

        $arr['where'][] = 'u.date_recorded >= DATE_SUB( UTC_TIMESTAMP(), Interval ' . $this->profile_deleted_time . ' DAY )';

        return $arr;
    }

    function member_count( $count )
    {
        global $wpdb, $bp;

        $count = get_transient( 'mif_bpc_active_member_count' );

        if ( $count === false ) {

            $count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(user_id) FROM {$bp->members->table_name_last_activity} WHERE component = %s AND type = 'last_activity' AND date_recorded >= DATE_SUB( UTC_TIMESTAMP(), Interval {$this->profile_deleted_time} DAY )", $bp->members->id ) );

    		set_transient( 'mif_bpc_active_member_count', $count, DAY_IN_SECONDS );

        }

        return $count;
    }

    function clear_member_count_caches()
    {
    	delete_transient( 'mif_bpc_active_member_count' );
    }



    // 
    // Страница настройки уровня приватности
    // 

    public function profile_privacy_nav()
    {
        global $bp;

        $parent_url = $bp->loggedin_user->domain . $bp->profile->slug . '/';
        $parent_slug = $bp->profile->slug;

        $sub_nav = array(  
                'name' => __( 'Privacy', 'mif-bpc' ), 
                'slug' => 'profile-privacy', 
                'parent_url' => $parent_url, 
                'parent_slug' => $parent_slug, 
                'screen_function' => array( $this, 'screen' ), 
                'position' => 50,
                'user_has_access'=>  bp_is_my_profile() 
            );

        bp_core_new_subnav_item( $sub_nav );
       
    }


    public function screen()
    {
        global $bp;
        add_action( 'bp_template_title', array( $this, 'title' ) );
        add_action( 'bp_template_content', array( $this, 'body' ) );
        bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
    }


    public function title()
    {
        echo __( 'Profile access options', 'mif-bpc' );
    }


    public function body()
    {
        $out = '';

        $out .= '<p>' . __( 'Specify who can view your profile page.', 'mif-bpc' ) . '</p>';
        $out .= '<p>&nbsp;';
        $out .= '<form class="nav-settings-form" method="POST">';

        $arr = $this->get_levels_data();
        $privacy_level = $this->get_privacy_level();

        $out .= '<table>';

        $alt = 1;
        foreach ( $arr as $key => $item ) {

            $checked = ( $key == $privacy_level ) ? ' checked' : '';
            $class_alt = ( $alt ) ? ' class="alt"' : '';

            $out .= '<tr' . $class_alt . '><td class="radio"><input type="radio" name="privacy_level" value="' . $key . '"' . $checked . ' id="privacy_level_' . $key . '"/></td>
             <td><label for="privacy_level_' . $key . '"><strong>' . $item['descr'] . '</strong>
             <p>' . $item['comment'] . '</p></label></td></tr>';

             $alt = 1 - $alt;
        }

        $out .= '</table>';

        $out .= wp_nonce_field( 'mif-bp-customizer-profile-privacy', '_wpnonce', true, false );
        $out .= '&nbsp;<p><input type="submit" value="' . __( 'Save the changes', 'mif-bpc' ) . '">';
        $out .= '</form>';

        echo $out;
    }


    public function profile_privacy_helper()
    {
        if ( ! ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'mif-bp-customizer-profile-privacy' ) ) ) return;

        $privacy_level = (int) $_POST['privacy_level'];

        update_user_meta( bp_displayed_user_id(), 'mif_bpc_privacy_level', $privacy_level );
    }



    // 
    // Возвращает массив уровней доступа
    // 

    function get_levels_data()
    {
        $arr = array(
            0 =>  array( 'descr' => __( 'All users', 'mif-bpc' ), 'comment' => __( 'The page will be available either to authorized or not authorized users. Your posts and documents will be publically available on the Internet', 'mif-bpc' ) ),
            10 => array( 'descr' => __( 'Authorized users', 'mif-bpc' ), 'comment' => __( 'Only users, who work on site, having specified their login and password can access the page', 'mif-bpc' ) ),
            20 => array( 'descr' => __( 'My subscribers', 'mif-bpc' ), 'comment' => __( 'Users, who subscribed to me, who I read and my friends can access the page', 'mif-bpc' ) ),
            30 => array( 'descr' => __( 'Those, who I follow', 'mif-bpc' ), 'comment' => __( 'Users, who I follow and my friends can access the page', 'mif-bpc' ) ),
            40 => array( 'descr' => __( 'My friends', 'mif-bpc' ), 'comment' => __( 'Only my friends can access the page', 'mif-bpc' ) ),
            50 => array( 'descr' => __( 'Only me', 'mif-bpc' ), 'comment' => __( 'No one can access the page. Only I can view my posts and documents', 'mif-bpc' ) ),
        );

        return apply_filters( 'mif_bpc_profile_privacy_privacy_levels', $arr );
    }



    // 
    // Установка мета-поля вновь создаваемым пользователям
    // 

    function set_default_level( $user )
    {
        if ( empty( $user ) ) return false;

        $user_id = ( is_array( $user ) ) ? $user['user_id'] : $user;
        if ( empty( $user_id ) ) return false;

        update_user_meta( $user_id, 'mif_bpc_privacy_level', $this->default_privacy );

    }



    // 
    // Установка мета-поля старым пользователям, чтобы учесть их публичный уровень доступа
    // 

    function old_level_correct()
    {
 
        global $bp, $wpdb;

        $arr = $wpdb->get_col( $wpdb->prepare( "SELECT user_id FROM {$bp->members->table_name_last_activity} WHERE component = %s AND type = 'last_activity'", $bp->members->id ) );

        $i=0;
        foreach ( (array) $arr as $user_id )
            if ( update_user_meta( $user_id, 'mif_bpc_privacy_level', 0 ) ) $i++;

        p( 'Changed levels of ' . $i . ' members' );

    }


}