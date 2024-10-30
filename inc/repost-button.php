<?php

//
// Кнопка "Репост"
// 
//

defined( 'ABSPATH' ) || exit;

if ( mif_bpc_options( 'repost-button' ) ) {

    global $mif_bpc_repost_button;
    $mif_bpc_repost_button = new mif_bpc_repost_button();
   
}


class mif_bpc_repost_button {

    //
    // Механизм repost-записей - при нажатии кнопки создается элемент активности 
    //      'component' => 'activity',
    //      'type' => 'activity_repost',
    //      'item_id' => id repost-записи
    //      'secondary_item_id' => id автора repost-записи
    //      'content' => комментарий or <!-- none -->
    // При выводе такой записи вызывается шаблон, в который выводится нужная запись по item_id
    // Шаблон хранится в плагине, но можно определить и в теме. В шаблоне используются
    // специфические функции, определенные в repost-button-template.php
    // 
    // Repost - работает только для публичной активности.
    // 


    //
    // Элементы активности, которые нельзя репостить
    //

    public $unreposted_activity = array( 'last_activity' );
    


    function __construct()
    {

        // Кнопка репоста на записях
        add_action( 'bp_activity_entry_meta', array( $this, 'repost_button' ), 30 );

        // Форма репоста
        add_action( 'bp_activity_post_form_options', array( $this, 'repost_form' ) );
        add_filter( 'body_class', array( $this, 'repost_body_class' ), 30 );

        // JS и AJAX - помощники
        add_action( 'wp_print_scripts', array( $this, 'load_js_helper' ) );            				
        add_filter( 'bp_activity_custom_update', array( $this, 'ajax_helper' ), 10, 3 );

        // Отображение repost-записи в ленте активности
        add_filter( 'bp_get_activity_content_body', array( $this, 'activity_content' ), 10, 2 );
        add_filter( 'bp_get_activity_secondary_avatar', array( $this, 'secondary_avatar' ) );

        // Поправки для того, чтобы выводилась вторичная аватартка в форме репоста
        add_filter( 'bp_get_activity_secondary_avatar_item_id', array( $this, 'secondary_avatar_fix_item_id' ) );
        add_filter( 'bp_get_activity_secondary_avatar_object_groups', array( $this, 'secondary_avatar_fix_object' ) );
        add_filter( 'bp_get_activity_secondary_avatar_object_blogs', array( $this, 'secondary_avatar_fix_object' ) );
        add_filter( 'bp_get_activity_secondary_avatar_object_friends', array( $this, 'secondary_avatar_fix_object' ) );

    }




    //
    // Показать блок repost-записи (запись, для которой сделан репост, внутри новой записи)
    //

    function show_reposted_activity( $activity_id )
    {
        global $reposted_activity;
        
        $reposted_activity = $this->activity_get( $activity_id );

        if ( $reposted_activity ) {

            if ( $template = locate_template( 'repost-activity.php' ) ) {
                
                load_template( $template, false );

            } else {

                load_template( dirname( __FILE__ ) . '/../templates/repost-activity.php', false );

            }

        } else {

        }

    }

    //
    // Показать кнопку "Репост"
    //

    function repost_button()
    {
        if ( ! $this->is_reposted_activity() ) return;

        global $bp, $activities_template;
        $activity_id = ( $activities_template->activity->type == 'activity_repost' ) ? $activities_template->activity->item_id : $activities_template->activity->id;
        $user_id = $activities_template->activity->user_id;

        if ( $result = $this->is_activity_reposted( $activity_id ) ) {

            $url = bp_activity_get_permalink( $result );
            $active = ' active';

        } else {

            $url = $bp->loggedin_user->domain . '?repost=' . $activity_id . '&_wpnonce=' . wp_create_nonce( 'mif_bpc_repost_button_press' );
            $active = '';

        }


        $button = '<div class="repost repost-user-' . $user_id . $active . '"><a href="' . $url . '" class="button bp-primary-action repost"><i class="fa fa-share" aria-hidden="true"></i></a></div>';

        // Здесь можно изменить кнопку "Репост"

        $button = apply_filters( 'mif_bpc_repost_button_like_button', $button, $url );

        echo $button;
    }

    public function load_js_helper()
    {
        wp_register_script( 'mif_bpc_repost-button', plugins_url( '../js/repost-button.js', __FILE__ ) );  
        wp_enqueue_script( 'mif_bpc_repost-button' );

    }


    //
    // Add класс в body, если отображается форма repost-записи
    // Позволяет настроить вид формы (убрать стандарный submit и др.)
    //

    public function repost_body_class( $data )
    {
        if ( empty( $_GET['repost'] ) ) return $data;
        $data[] = 'repost-activity-form';
        return $data;
    }


    //
    // Вывести repost-запись в форму публикации записей
    //

    public function repost_form()
    {
        if ( empty( $_GET['repost'] ) ) return;
        
        $activity_id = (int) $_GET['repost'];
        
        echo '<div class="activity-list">';
        $this->show_reposted_activity( $activity_id );
        echo '</div>';

        echo '<input type="hidden" name="whats-new-post-object" id="whats-new-post-object" value="activity_repost" />';
        echo '<input type="hidden" name="whats-new-post-in" id="whats-new-post-in" value="' . $activity_id . '" />';

        echo '<div id="repost-submit-wrap">';
		echo '<input type="submit" name="repost-submit" id="repost-submit" value="' . __( 'Post Update', 'buddypress' ) . '" />';
		echo '</div>';

        echo '<script>
        window.onload = function() {
            document.getElementById( "whats-new" ).focus();
        }
        </script>';

        $this->repost_form_flag = true;

        // global $wp_filter;
        // p($wp_filter);



    }



    //
    // Вывести repost-запись в форму публикации записей
    //

    public function repost_form_width_js()
    {
        if ( empty( $_GET['repost'] ) ) return;
        if ( $this->repost_form_flag ) return;

        $this->repost_form();

        echo '<script>
        window.onload = function() {
            div = document.getElementById( "whats-new-options" );
            button = document.getElementById( "whats-new-submit" );
            div.appendChild( button );
        }
        </script>
        ';

    }



    public function ajax_helper( $object, $item_id, $content )
    {

        if ( ! mif_bpc_options( 'repost-button' ) ) return;
        if ( $object != 'activity_repost' ) return;

        // $reposted_activity_arr = bp_activity_get( array( 'in' => $item_id ) );
        // $reposted_activity = $reposted_activity_arr['activities'][0];
        if ( $reposted_activity = $this->activity_get( $item_id ) ) {

            $user_id = bp_loggedin_user_id();

            if ( empty( trim( $content ) ) ) $content = '<!-- none -->';

            $args = array(
                            // 'action' => '<a href="' . bp_core_get_user_domain( $user_id ) . '">' . bp_members_get_user_nicename( $user_id ) . '</a> ' . __( 'shared a post', 'mif-bpc' ),
                            'action' => bp_core_get_userlink( $user_id ) . ': ' . __( 'shared a post', 'mif-bpc' ),
                            'component' => 'activity',
                            'type' => 'activity_repost',
                            'item_id' => $item_id,
                            'secondary_item_id' => $reposted_activity->user_id,
                            'content' => $content,
                            'hide_sitewide' => false,

                    );

            return bp_activity_add( $args );

        }

        return false;
    }


    //
    // Получить элемент активности по id
    //

    function activity_get( $id = NULL )
    {
        if ( $id == NULL ) return false;

        $reposted_activity_arr = bp_activity_get( array( 'in' => $id ) );
        $reposted_activity = $reposted_activity_arr['activities'][0];

        return $reposted_activity;
    }



    //
    // Проверить, был ли сделан репост активности пользователем
    //

    function is_activity_reposted( $activity_id = NULL, $user_id = NULL )
    {
        if ( $activity_id == NULL ) $activity_id = bp_get_activity_id();
        if ( $user_id == NULL ) $user_id = bp_loggedin_user_id();

        global $bp, $wpdb;

        $sql = "SELECT id FROM {$bp->activity->table_name} WHERE component='activity' AND type='activity_repost' AND user_id={$user_id} AND item_id={$activity_id}";
        $result = $wpdb->get_var( $sql );

        // $ret = ( $result ) ? true : false;

        return apply_filters( 'mif_bpc_repost_button_is_activity_reposted', $result, $activity_id, $user_id );
    }


    //
    // Проверить, допускается ли репост этой записи
    //

    function is_reposted_activity()
    {
		global $activities_template;
        $activity = $activities_template->activity;

        if ( $activity->hide_sitewide ) return false;

        $unreposted_activity = $this->get_unreposted_activity();
        if ( in_array( $activity->type, $unreposted_activity ) ) return false;

        // Здесь можно уточнить вопрос о возможности репоста 
        // (например, запретить это для заблокированных пользователей)

        return apply_filters( 'mif_bpc_repost_button_is_reposted_activity', true, $activity );
    }


    //
    // Получить список типов активности, котрую нельзя репостить
    //

    function get_unreposted_activity()
    {
        return apply_filters( 'mif_bpc_like_button_get_unlikes_activity', $this->unreposted_activity );
    }


    //
    // Вывести запись repost-активности в ленту активности
    //

    function activity_content( $content, $activity = NULL )
    {
        if ( $activity == NULL ) return $content;
        if ( $activity->type != 'activity_repost' ) return $content;

        echo $content;
        $this->show_reposted_activity( $activity->item_id );

    }


    //
    // Поправить вторичную аватарку
    //

    function secondary_avatar( $avatar )
    {
        global $activities_template;

        if ( isset( $activities_template ) && $activities_template->activity->type != 'activity_repost' ) return $avatar;

        if ( isset( $activities_template->activity->item_id ) ) {

            $id = $activities_template->activity->item_id;

        } elseif ( isset( $_GET['repost'] ) ) {

            $id = (int) $_GET['repost'];

        } else {

            return false;
        }

        // $reposted_activity_arr = bp_activity_get( array( 'in' => $id ) );
        // $reposted_activity = $reposted_activity_arr['activities'][0];
        $reposted_activity = $this->activity_get( $id );

		switch ( $reposted_activity->component ) {
			case 'groups' :
				if ( bp_disable_group_avatar_uploads() ) {
					return false;
				}

				$object  = 'group';
				$item_id = $reposted_activity->item_id;
				$link    = '';
				$name    = '';

				if ( bp_is_active( 'groups' ) ) {
					$group = groups_get_group( $item_id );
					$link  = bp_get_group_permalink( $group );
					$name  = $group->name;
				}

				break;
			case 'blogs' :
				$object  = 'blog';
				$item_id = $reposted_activity->item_id;
				$link    = home_url();

				break;
			case 'friends' :
				$object  = 'user';
				$item_id = $reposted_activity->secondary_item_id;
				$link    = bp_core_get_userlink( $item_id, false, true );

				break;
			default :
				$object  = 'user';
				$item_id = $reposted_activity->user_id;
				$email   = $reposted_activity->user_email;
				$link    = bp_core_get_userlink( $item_id, false, true );

				break;
		}

		// $object  = apply_filters( 'bp_get_activity_secondary_avatar_object_' . $reposted_activity->component, $object );
		// $item_id = apply_filters( 'bp_get_activity_secondary_avatar_item_id', $item_id );
        $link = apply_filters( 'bp_get_activity_secondary_avatar_link', $link, $reposted_activity->component );

        $avatar = bp_core_fetch_avatar( array(
			'item_id' => $item_id,
			'object'  => $object,
			'type'    => 'thumb',
			'class'   => 'avatar',
			'width'   => 20,
			'height'  => 20,
			'email'   => $email
		) );

        return sprintf( '<a href="%s" class="%s">%s</a>', $link, '', $avatar );

    }

    
    //
    // Поправить пустой item_id
    //

    function secondary_avatar_fix_item_id( $item_id )
    {
        if ( isset( $item_id ) ) return $item_id;
        global $activities_template;
        $ret = ( empty( $activities_template ) && isset( $_GET['repost'] ) ) ? (int) $_GET['repost'] : false;
        return $ret;
    }


    //
    // Поправить пустой object
    //

    function secondary_avatar_fix_object( $object )
    {
        if ( isset( $object ) ) return $object;
        global $activities_template;
        $ret = ( empty( $activities_template ) && isset( $_GET['repost'] ) ) ? (int) $_GET['repost'] : false;
        return $ret;
    }






    //
    // Служебные переменные
    //
    
    private $repost_form_flag = false;

}



?>