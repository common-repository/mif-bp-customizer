<?php

//
// Configuration уведомлений
// 
//


defined( 'ABSPATH' ) || exit;

if ( mif_bpc_options( 'notifications' ) ) {

    global $mif_bpc_notifications;
    $mif_bpc_notifications = new mif_bpc_notifications();

}


class mif_bpc_notifications {

    //
    // Стандартный механизм уведомлений, но с улучшенной интерфейсной частью
    //


    //
    // Сколько уведомлений показывать на странице
    //

    public $per_page = 15;



    function __construct()
    {
       
        // Новая страница уведомлений
        add_action( 'bp_activity_setup_nav', array( $this, 'notifications_nav' ) );
        add_action( 'bp_init', array( $this, 'delete_notifications_nav' ) );

        // Обработка кнопок в списке уведомлений
        add_action( 'wp_print_scripts', array( $this, 'load_js_helper' ) );            				
        add_action( 'wp_ajax_mif-bpc-notifications-load-more', array( $this, 'ajax_helper_load_more' ) );
        add_action( 'wp_ajax_mif-bpc-notification-delete', array( $this, 'ajax_helper_delete' ) );
        add_action( 'wp_ajax_mif-bpc-notification-to-new', array( $this, 'ajax_helper_to_new' ) );
        add_action( 'wp_ajax_mif-bpc-notification-to-not-new', array( $this, 'ajax_helper_to_not_new' ) );
        add_action( 'wp_ajax_mif-bpc-notification-bulk-not-is-new', array( $this, 'ajax_helper_bulk_not_is_new' ) );
        add_action( 'wp_ajax_mif-bpc-notification-bulk-delete', array( $this, 'ajax_helper_bulk_delete' ) );
        add_action( 'wp_ajax_mif-bpc-float-notification-update', array( $this, 'ajax_helper_float_update' ) );

        // Корректировка запроса
        add_filter( 'bp_notifications_get_where_conditions', array( $this, 'where_conditions' ) );
    
        add_action( 'bp_notification_before_update', array( $this, 'remove_cache' ) );
        add_action( 'bp_notification_before_delete', array( $this, 'remove_cache' ) );

        add_action( 'wp_footer', array( $this, 'float_notifications' ) );
    }




    //
    // Всплывающие уведомления
    //

    function float_notifications()
    {
        if ( ! is_main_site() ) return;
        if ( ! is_user_logged_in() ) return;

        echo '<div class="float-notification">';
        echo implode( '', $this->get_float_notifications() );
        echo '</div>';

        $audio = apply_filters( 'mif_bpc_notifications_audio', plugins_url( '../media/notify5.ogg', __FILE__ ) );

        echo '<audio id="notification_notify" src="' . $audio . '" type="audio/ogg"></audio>';

    }

    function get_float_notifications()
    {
        $arr = array();
        $user_id = bp_loggedin_user_id(); 
        
        if ( bp_has_notifications( array( 'user_id' => $user_id, 'per_page' => 5, 'is_new' => 1 ) ) ) {

            while ( bp_the_notifications() ) : bp_the_notification(); 

                $arr[bp_get_the_notification_id()] = $this->get_notification_item();

            endwhile;
        }

        return $arr;
    }

    function get_notification_item()
    {
        $out = '';

        $icons = array(
                        'messages' => '<span class="type"><i class="fa fa-envelope" aria-hidden="true"></i></span>',
                        'groups' => '<span class="type"><i class="fa fa-users" aria-hidden="true"></i></span>',
                        'friends' => '<span class="type"><i class="fa fa-user" aria-hidden="true"></i></span>',
                        'activity' => '<span class="type"><i class="fa fa-comments" aria-hidden="true"></i></span>',
                        'default' => '<span class="type"><i class="fa fa-user" aria-hidden="true"></i></span>',
                        );


        $type = bp_get_the_notification_component_name();
        $icon = ( isset( $icons[$type] ) ) ? $icons[$type] : $icons['default'];

        global $bp;
        $id = bp_get_the_notification_id();
        $url = wp_nonce_url( $bp->displayed_user->domain . $bp->messages->slug . '/notifications/is_new/?id=' . $id, 'mif_bpc_notification_is_new_status' );

        $out .= '<div id="div-notification-' . $id . '">';
        $out .= '<span class="hint">' . bp_get_the_notification_description() . '</span>';
        $out .= $this->notification_avatar( 40, false );
        $out .= '<span class="close"><a href="' . $url . '" class="notification-to-not-new" id="float-tonew-' . $id . '"><i class="fa fa-times-circle" aria-hidden="true"></i></a></span>';
        $out .= $icon;
        $out .= '</div>';

        return $out;
    }



    //
    // Update блок всплывающих уведомлений
    //

    function ajax_helper_float_update()
    {
        $arr = $this->get_float_notifications();
        $arr[0] = mif_bpc_new_notifications_count();

        echo json_encode( $arr );

        wp_die();
    }


    //
    // Обработка действий кнопок в списке уведомлений
    //

    //
    // Delete отмеченные
    //

    function ajax_helper_bulk_delete()
    {
        check_ajax_referer( 'mif_bpc_notifications_bulk_delete' );

        $arr = array_map( 'sanitize_key', $_POST['arr'] );
        foreach ( $arr as $item ) bp_notifications_delete_notification( $item );
        echo mif_bpc_new_notifications_count();

        wp_die();
    }


    //
    // Отметить отмеченные как прочитанное
    //

    function ajax_helper_bulk_not_is_new()
    {
        check_ajax_referer( 'mif_bpc_notifications_bulk_not_is_new' );

        $arr = array_map( 'sanitize_key', $_POST['arr'] );
        foreach ( $arr as $item ) bp_notifications_mark_notification( $item, false );
        echo mif_bpc_new_notifications_count();

        wp_die();
    }


    //
    // Mark as read
    //

    function ajax_helper_to_not_new()
    {
        check_ajax_referer( 'mif_bpc_notification_is_new_status' );

        $id = (int) $_POST['id'];

        if ( isset( $id ) ) $ret = bp_notifications_mark_notification( $id, false );
        if ( $ret ) echo mif_bpc_new_notifications_count();

        wp_die();
    }


    //
    // Отметить как новое
    //

    function ajax_helper_to_new()
    {
        check_ajax_referer( 'mif_bpc_notification_is_new_status' );

        $id = (int) $_POST['id'];

        if ( isset( $id ) ) $ret = bp_notifications_mark_notification( $id, true );
        if ( $ret ) echo mif_bpc_new_notifications_count();

        wp_die();
    }


    //
    // Delete уведомление
    //

    function ajax_helper_delete()
    {
        check_ajax_referer( 'mif_bpc_notification_delete' );

        $id = (int) $_POST['id'];
        if ( isset( $id ) ) $ret = bp_notifications_delete_notification( $id );
        if ( $ret ) echo mif_bpc_new_notifications_count();

        wp_die();
    }


    //
    // Кнопка "читать далее"
    //

    function ajax_helper_load_more()
    {
        check_ajax_referer( 'mif_bpc_notifications_load_more' );

        $page = (int) $_POST['page'];
        $page = ( $page ) ? $page : 1;

        if ( bp_has_notifications( array( 'per_page' => $this->per_page, 'page' => $page, 'is_new' => 0 ) ) ) {

            $i == 0;
			while ( bp_the_notifications() ) : bp_the_notification(); 

                mif_bpc_the_notification_row();
                $i++;

            endwhile;

            if ( $i == $this->per_page ) {

                mif_bpc_the_notification_load_more( ++ $page );

            } else {

                echo '<tr><td colspan="6"></td></tr>';
            }

        } 

        wp_die();
    }


    function load_js_helper()
    {
        wp_register_script( 'mif_bpc_notifications', plugins_url( '../js/notifications.js', __FILE__ ) );  
        wp_enqueue_script( 'mif_bpc_notifications' );
    }



    // 
    // Страница уведомлений
    // 

    function notifications_nav()
    {
        global $bp;

        $parent_url = $bp->displayed_user->domain . $bp->messages->slug . '/';
        $parent_slug = $bp->messages->slug;

        $sub_nav = array(  
                'name' => __( 'Notifications', 'mif-bpc' ),
                'slug' => 'notifications', 
                'parent_url' => $parent_url, 
                'parent_slug' => $parent_slug, 
                'screen_function' => array( $this, 'screen' ), 
                'position' => 50,
                'user_has_access'=>  bp_is_my_profile() 
            );

        bp_core_new_subnav_item( $sub_nav );
       
    }



    //
    // Содержимое страниц
    //

    function screen()
    {
        global $bp;
        add_action( 'bp_template_content', array( $this, 'body' ) );
        bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
    }

    function body()
    {
        if ( $template = locate_template( 'notifications-loop.php' ) ) {
            
            load_template( $template, false );

        } else {

            load_template( dirname( __FILE__ ) . '/../templates/notifications-loop.php', false );

        }
    }


    //
    // Уточнить запрос уведомлений - показывать как новые, так и старые в одном списке
    //

    function where_conditions( $where_conditions )
    {
        if ( isset( $where_conditions['is_new'] ) && $where_conditions['is_new'] == 'is_new = 0' ) unset( $where_conditions['is_new'] );
        return $where_conditions;
    }


    // 
    // Получить аватар уведомления
    // 

    function notification_avatar( $width = 25, $is_title = true )
    {
        $type = bp_get_the_notification_component_name();

        switch ( $type ) {

            case 'activity' :
            case 'messages' :
                    $object = 'user';
                    $item_id = bp_get_the_notification_secondary_item_id();
                break;

            case 'friends' :
                    $object = 'user';
                    $item_id = bp_get_the_notification_item_id();
                break;

            case 'groups' :
                    $object = 'group';
                    $item_id = bp_get_the_notification_item_id();
                break;

            default :
                    $item_id = apply_filters( 'mif_bpc_get_notification_avatar_' . $type . '_item_id', bp_get_the_notification_item_id() );
                    $object = apply_filters( 'mif_bpc_get_notification_avatar_' . $type . '_object', 'user' );
                break;

        }
        
        switch ( $object ) {

            case 'user' :
                    $url = bp_core_get_user_domain( $item_id );
                    $title = bp_core_get_user_displayname( $item_id );
                break;

            case 'group' :
                    $url = bp_get_group_permalink( groups_get_group( array( 'group_id' => $item_id ) ) );
                    $title = bp_get_group_name( groups_get_group( array( 'group_id' => $item_id ) ) );
                break;

            default :
                    $url = apply_filters( 'mif_bpc_get_notification_avatar_' . $object . '_url', bp_core_get_user_domain( $item_id ) );
                break;

        }

        $title = ( $is_title ) ? $title : false;

        $out = '';
        $out .= '<a href="' . $url . '">';
        $out .= bp_core_fetch_avatar( array( 'item_id' => $item_id, 'object' => $object, 'title' => $title, 'width' => $width ) );
        $out .= '</a>';

        return $out;
    }


    //
    // Delete стандартную страницу уведомлений
    //

    function delete_notifications_nav()
    {
        bp_core_remove_nav_item( 'notifications' );
    }


    //
    // Delete кэш-данные о количестве новых уведомлений
    //

    function remove_cache()
    {
        $user_id = bp_loggedin_user_id(); 
        wp_cache_delete( 'mif_bpc_new_notifications_count', $user_id );
    }


}


//
// Функции шаблона
//

//
// Выводит аватар уведомления
//

function mif_bpc_the_notification_avatar()
{
    global $mif_bpc_notifications;
    echo $mif_bpc_notifications->notification_avatar();
}

// 
// Проверить статус уведомления (прочитано or нет)
//

function mif_bpc_is_new_notification()
{
    $bp = buddypress();
    $res = ( $bp->notifications->query_loop->notification->is_new ) ? true : false;
    return $res;
}

// 
// Кнопка "прочитано"
//

function mif_bpc_notification_is_new_button()
{
    $id = bp_get_the_notification_id();

    global $bp;
    $url = wp_nonce_url( $bp->displayed_user->domain . $bp->messages->slug . '/notifications/is_new/?id=' . $id, 'mif_bpc_notification_is_new_status' );
    
    $button1 = '<div class="custom-button"><a href="' . $url . '" class="notification-to-not-new" id="notification-tonotnew-' . $id . '" title="' . __( 'Mark as read', 'mif-bpc' ) . '"><i class="fa fa-circle" aria-hidden="true"></i></a></div>';

    echo $button1;

    $button2 = '<div class="custom-button"><a href="' . $url . '" class="notification-to-new" id="notification-tonew-' . $id . '" title="' . __( 'Mark as unread', 'mif-bpc' ) . '"><i class="fa fa-circle-thin" aria-hidden="true"></i></a></div>';

    echo $button2;
}

// 
// Кнопка "удалить"
//

function mif_bpc_notification_delete_button()
{
    $id = bp_get_the_notification_id();
    
    global $bp;
    $url = wp_nonce_url( $bp->displayed_user->domain . $bp->messages->slug . '/notifications/delete/?id=' . $id, 'mif_bpc_notification_delete' );

    $button = '<div class="custom-button"><a href="' . $url . '" class="button notification-delete" id="notification-delete-' . $id . '" title="' . __( 'Delete', 'mif-bpc' ) . '"><i class="fa fa-trash-o" aria-hidden="true"></i></a></div>';
    
    echo $button;
}


// 
// Строка уведомления
//

function mif_bpc_the_notification_row()
{
?>
    <tr<?php if ( mif_bpc_is_new_notification() ) echo ' class="new"'; ?> id="tr-notification-<?php bp_the_notification_id(); ?>">
        <td class="cn-check"><input id="chk-<?php bp_the_notification_id(); ?>" type="checkbox" name="notifications_ids[]" value="<?php bp_the_notification_id(); ?>" class="cn-check"></td>
        <td class="cn-avatar"><?php mif_bpc_the_notification_avatar() ?></td>
        <td class="cn-is-new"><?php mif_bpc_notification_is_new_button() ?></td>
        <td class="cn-description"><?php bp_the_notification_description(); ?></td>
        <td class="cn-since"><span class="time-since"><?php bp_the_notification_time_since(); ?></span></td>
        <td class="cn-delete"><?php mif_bpc_notification_delete_button(); ?></td>
    </tr>
<?php
}


// 
// Кнопка "Read more"
//

function mif_bpc_the_notification_load_more( $page = 2, $none = false )
{
    global $bp;
    
    $url = wp_nonce_url( $bp->displayed_user->domain . $bp->messages->slug . '/notifications/load-more/?page=' . $page, 'mif_bpc_notifications_load_more' );

    $total_count = buddypress()->notifications->query_loop->total_notification_count;

    if ( $none || $total_count <= mif_bpc_get_notifications_per_page() ) {

        $out = '<tr><td colspan="6"></td></tr>';
    
    } else {

        $out = '<tr class="load-more" id="load-more-' . $page . '"><td class="load-more" colspan="6"><div class="loader"><i class="fa fa-spinner fa-spin fa-3x fa-fw"></i></div><div class="generic-button"><a href="' . $url . '" class="button load-more">' . __( 'Read more', 'mif-bpc' ) . '</a></div></td></tr>';
    }

    echo $out;
}



//
// Bлок о количестве уведомлений
//

function mif_bpc_the_notifications_info()
{
    echo mif_bpc_get_notifications_info();
}

function mif_bpc_get_notifications_info()
{
    if ( ! mif_bpc_options( 'notifications' ) ) return;

    global $bp;

    $count = mif_bpc_new_notifications_count();
    $url = $bp->displayed_user->domain . $bp->messages->slug . '/notifications/';
    $none = ( $count > 0 ) ? '' : ' style="display: none;"';

    $out = '<div class="notifications-info"' . $none . '><a href="' . $url . '">' . __( 'New notifications', 'mif-bpc' ) . ': <span>' . $count . '</span></a></div>';

    return apply_filters( 'mif_bpc_get_notifications_info', $out, $count );
}


//
// Количество новых уведомлений
//

function mif_bpc_new_notifications_count( $user_id = NULL )
{
    global $bp, $wpdb;

    if ( $user_id == NULL ) $user_id = bp_loggedin_user_id(); 
    if ( empty( $user_id ) ) return 0;

    if ( ! $count = wp_cache_get( 'mif_bpc_new_notifications_count', $user_id ) ) {

        $count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(user_id) FROM {$bp->notifications->table_name} WHERE user_id = %s AND is_new = '1'", $user_id ) );
        $count = apply_filters( 'mif_bpc_mif_bpc_new_notifications_count', $count, $user_id );

        wp_cache_set( 'mif_bpc_new_notifications_count', $count, $user_id );

    }
 
    return $count;
}


//
// Сколько уведомлений на странице
//

function mif_bpc_get_notifications_per_page()
{
    global $mif_bpc_notifications;
    return $mif_bpc_notifications->per_page;
}


//
// Получить url для кнопок групповой обработки уведомлений
//

function mif_bpc_the_notification_bulk_url( $mode = 'delete' )
{
    global $bp;
    echo wp_nonce_url( $bp->displayed_user->domain . $bp->messages->slug . '/notifications/' . $mode, 'mif_bpc_notifications_bulk_' . $mode );
}





?>