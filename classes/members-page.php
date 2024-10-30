<?php

//
// Класс для построения списков пользователей
// 
//

defined( 'ABSPATH' ) || exit;



class mif_bpc_members_page {
  
    private $args = array();

    
    function __construct( $args = array() )
    {
        global $bp;

        $default = array(
                'name' => 'Name',
                'slug' => 'slug',
                'parent_url' => $bp->loggedin_user->domain . $bp->profile->slug . '/',
                'parent_slug' => $bp->profile->slug,
                'position' => 10,
                'title' => 'Title',
                'body_comment' => 'Comment',
                'can_edit' => true,
                'members_usermeta' => '',
                'exclude_users' => '',
                'user_id' => bp_displayed_user_id(),
                'add_btn' => __( 'Add users', 'mif-bpc' ),
                'add_submit' => __( 'Save the changes', 'mif-bpc' ),
                'add_comment' => __( 'Specify usernames you want to add to the list.', 'mif-bpc' ),
            );

        $this->args = $default;
        foreach ( (array) $args as $key => $item ) $this->args[$key] = $item;

        $sub_nav = array(  
                'name' => $this->args['name'], 
                'slug' => $this->args['slug'], 
                'parent_url' => $this->args['parent_url'], 
                'parent_slug' => $this->args['parent_slug'], 
                'position' => $this->args['position'],
                'screen_function' => array( $this, 'screen' ), 
                'user_has_access'=>  $this->user_has_access()
            );

        bp_core_new_subnav_item( $sub_nav );

        if ( $this->args['can_edit'] ) {

            add_action( 'wp_print_scripts', array( $this, 'load_js_helper_script' ) );
            add_action( 'wp_ajax_members-page-submit-' . $this->args['slug'], array( $this, 'ajax_helper' ) );
            add_action( 'wp_ajax_members-page-add-remove-button', array( $this, 'ajax_helper_add_remove_button' ) );
            
        }
    }


    function load_js_helper_script()
    {
        wp_register_script( 'mif_bpc_members_page', plugins_url( '../js/members-page.js', __FILE__ ) );  
        wp_enqueue_script( 'mif_bpc_members_page' );
    }


    function user_has_access()
    {
        return apply_filters( 'mif_bpc_members_page_user_has_access_' . $this->args['slug'], true );
    }


    function screen()
    {
        global $bp;
        add_action( 'bp_template_title', array( $this, 'title' ) );
        add_action( 'bp_template_content', array( $this, 'body' ) );
        bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
    }


    function title()
    {
        echo $this->args['title'];
    }


    function body()
    {
		echo '<div class="members-page">';
        
        do_action( 'mif_bpc_members_page_before_body_comment' );

        echo '<p>' . $this->args['body_comment'] . '</p>';
        echo '<p>&nbsp;';

		do_action( 'mif_bpc_members_page_after_body_comment' );

        remove_action( 'bp_directory_members_actions', 'bp_member_add_friend_button' );
        add_action( 'bp_directory_members_actions', array( $this, 'member_button' ) );
        add_filter( 'bp_ajax_querystring', array( $this, 'members_param' ), 100, 2 );

        bp_get_template_part( 'members/members-loop' ) ;

        remove_filter( 'bp_ajax_querystring', array( $this, 'members_param' ) );
        add_action( 'bp_directory_members_actions', 'bp_member_add_friend_button' );
        remove_action( 'bp_directory_members_actions', array( $this, 'member_button' ) );
                    
		do_action( 'mif_bpc_members_page_after_body_members_loop' );

        if ( $this->args['can_edit'] ) echo $this->get_add_form();

		do_action( 'mif_bpc_members_page_after_body_add_member' );

		echo '</div>';
    }

        
    function get_add_form()
    {
        $out = '';

        $out .= '<div class="add-member">
        <div class="add-btn"><button>' . $this->args['add_btn'] . '</button></div>
        <div class="add-form"><form method="POST">
        ';

        if ( $this->args['add_comment'] ) $out .= '<p>' . $this->args['add_comment'] . '</p>';

        $out .= '<div class="response-box"></div>';

        $out .= '<div><textarea name="memberlist" placeholder="username"></textarea></div>
        <div class="submit-btn"><button>' . $this->args['add_submit'] . '</button></div>';
        
        $out .= '<input type="hidden" name="slug" value="' . $this->args['slug'] . '">'; 
        // $out .= '<input type="hidden" name="user_id" value="' . $this->args['user_id'] . '">'; 
        $out .= wp_nonce_field( "mif-bpc-member-page-add-member-nonce", "_wpnonce", true, false ); 

        $out .= '</form></div>
        </div>
        ';

        return $out;
    }



	function member_button() 
    {
        echo $this->get_member_button();
    }


    // 
    // Кнопки "Add" и "Delete" для пользователей в списке
    // 
	function get_member_button( $user_id = NULL ) 
    {
        if ( ! $this->args['can_edit'] ) return;

        if ( $user_id == NULL ) $user_id = bp_get_member_user_id();

        $block_self = ( bp_get_member_user_id() ) ? true : false;

        if ( $this->is_present( $user_id ) ) {

            $button = array(
                'id'                => 'remove',
                'component'         => 'activity',
                'must_be_logged_in' => true,
                'block_self'        => $block_self,
                'wrapper_class'     => 'banned-button',
                'wrapper_id'        => 'banned-button-' . $user_id,
                'link_href'         => wp_nonce_url( $this->args['parent_url'] . $this->args['slug'] . '/requests/remove/' . $user_id . '/', 'mif_bpc_members_page_add_remove_member' ),
                'link_text'         => __( 'Delete from the list', 'mif-bpc' ),
                'link_id'           => 'banned-' . $user_id,
                'link_rel'          => 'remove',
                'link_class'        => 'banned-button'
            );

        } else {

            $button = array(
                'id'                => 'add',
                'component'         => 'activity',
                'must_be_logged_in' => true,
                'block_self'        => $block_self,
                'wrapper_class'     => 'banned-button',
                'wrapper_id'        => 'banned-button-' . $user_id,
                'link_href'         => wp_nonce_url( $this->args['parent_url'] . $this->args['slug'] . '/requests/add/' . $user_id . '/', 'mif_bpc_members_page_add_remove_member' ),
                'link_text'         => __( 'Add to list', 'mif-bpc' ),
                'link_id'           => 'banned-' . $user_id,
                'link_rel'          => 'add',
                'link_class'        => 'banned-button'
            );

        }

		return bp_get_button( apply_filters( 'mif_bpc_members_page_member_button', $button ) );
	}    


    // 
    // Проверка наличия пользователя в списке
    // 
    function is_present( $user_id )
    {
        $memberlist = $this->get_memberlist();
        return in_array( $user_id, $memberlist );
    }



    //
    // Удаление or добавление пользователя через кнопку
    //
    function ajax_helper_add_remove_button()
    {
        check_ajax_referer( 'mif_bpc_members_page_add_remove_member' );

        $user_id = (int) $_POST['user_id'];
        $rel = sanitize_text_field( $_POST['rel'] );

        if ( $rel == 'remove' ) {

            $memberlist = $this->get_memberlist();
            $memberlist = array_diff( $memberlist, array( $user_id ) );
            $this->update_memberlist( $memberlist );

        } elseif ( $rel == 'add' ) {

            $memberlist = $this->get_memberlist();
            $memberlist[] = $user_id;
            $this->update_memberlist( $memberlist );

        }

        echo $this->get_member_button( $user_id );
        // echo $this->args['user_id'];
        
        wp_die();


        
        // if ( ! ( isset( $_POST['nonce'] ) && wp_verify_nonce( $_POST['nonce'], 'mif-bpc-member-page-add-member-nonce' ) ) ) wp_die();


    }


    //
    // Добавление пользователей через форму
    //
    function ajax_helper()
    {
        // if ( ! ( isset( $_POST['nonce'] ) && wp_verify_nonce( $_POST['nonce'], 'mif-bpc-member-page-add-member-nonce' ) ) ) wp_die();

        check_ajax_referer( 'mif-bpc-member-page-add-member-nonce' );

        $memberlist_nicenames = sanitize_text_field( $_POST['memberlist'] );
        $memberlist_nicenames = preg_replace( '/\@/', ' ', $memberlist_nicenames );
        $memberlist_nicenames = preg_replace( '/\s/', ',', $memberlist_nicenames );
        $memberlist_nicenames = preg_replace( '/;/', ',', $memberlist_nicenames );
        $memberlist_nicenames_arr = array_diff( array_unique( explode( ",", $memberlist_nicenames ) ), array( '' ) );

// p($memberlist_nicenames_arr);

        // $user_id = (int) $_POST['user_id'];
        // p($user_id);
        // p($this->args['user_id']);
        // $memberlist_ids_old = get_user_meta( $user_id, $this->args['members_usermeta'], true );
        // $memberlist_ids_arr_old = ( $memberlist_ids_old ) ? explode( ",", $memberlist_ids_old ) : array();

        $memberlist_ids_arr_old = $this->get_memberlist();

        $response = array();
        $memberlist_ids_arr_new = $memberlist_ids_arr_old;

        foreach ( (array) $memberlist_nicenames_arr as $user_nicename ) {
            
            if ( $user_nicename == '' ) continue;

            $user = get_user_by( 'slug', $user_nicename ); 
            
            if ( ! is_object( $user ) ) {

                $response[1][] = $user_nicename; // Пользователь не существует
                continue;

            } else {

                if ( in_array( $user->ID, $memberlist_ids_arr_old ) ) {

                    $response[2][] = $user_nicename; // Пользователь уже есть в списке
                    continue;

                }

                if ( $user->ID == $this->args['user_id'] ) {

                    if ( apply_filters( 'mif_bpc_members_page_self_adduser_' . $this->args['slug'], true, $this->args ) ) {

                        $response[3][] = $user_nicename; // Нельзя добавить самого себя
                        continue;

                    }

                }

                $exclude_users = $this->get_exclude_users_arr();

                if ( in_array( $user->ID, $exclude_users ) ) {

                    $response[4][] = $user_nicename; // Пользователь в списке тех, кого нельзя добавлять в список
                    continue;

                }

                if ( apply_filters( 'mif_bpc_members_page_check_adduser_' . $this->args['slug'], false, $this->args ) ) {

                    $response[5][] = $user_nicename; // Прочая причина невозможности добавления
                    continue;

                }

                $memberlist_ids_arr_new[] = $user->ID;
                $response[0][] = $user_nicename; // Пользователь добавлен

            }

        }

        $res = false;

        if ( isset( $response[0] ) ) {
                
            // $memberlist_ids_arr_new = array_unique( $memberlist_ids_arr_new );
            // sort( $memberlist_ids_arr_new );
            // $memberlist_ids_new = implode( ',', $memberlist_ids_arr_new );

            // $res = update_user_meta( $this->args['user_id'], $this->args['members_usermeta'], $memberlist_ids_new );
            // wp_cache_delete( 'memberlist_arr', $this->args['user_id'] );

            $res = $this->update_memberlist( $memberlist_ids_arr_new );

        }

        echo '<div id="message" class="bp-template-notice updated">';

        if ( count( $memberlist_nicenames_arr ) == 0 ) echo '<p>' . __( 'Specify usernames.', 'mif-bpc' ) . '</p>';
        if ( $res ) echo '<p>' . __( 'Users were added successfully:', 'mif-bpc' ) . ' <strong>' . implode( ', ', $response[0] ) . '</strong></p>';
        if ( $response[1] ) echo '<p>' . __( 'Users were not found:', 'mif-bpc' ) . ' <strong>' . implode( ', ', $response[1] ) . '</strong></p>';
        if ( $response[2] ) echo '<p>' . __( 'Users are already in the list:', 'mif-bpc' ) . ' <strong>' . implode( ', ', $response[2] ) . '</strong></p>';
        if ( $response[3] ) echo '<p>' . __( 'Can’t add yourself:', 'mif-bpc' ) . ' <strong>' . $response[3][0] . '</strong></p>';
        if ( $response[4] ) echo '<p>' . __( 'User can’t be added to the list:', 'mif-bpc' ) . ' <strong>' . implode( ', ', $response[4] ) . '</strong></p>';
        if ( $response[5] ) echo '<p>' . apply_filters( 'mif_bpc_members_page_check_adduser_comment' . $this->args['slug'],       
                                    __( 'Can’t be added:', 'mif-bpc' ), $this->args ) . ' <strong>' . implode( ', ', $response[5] ) . '</strong></p>';
        if ( $response[0] && ! $res ) echo '<p>' . __( 'Error occurred while adding the users:', 'mif-bpc' ) . ' <strong>' . implode( ', ', $response[0] ) . '</strong></p>';

        echo '</div>';

        wp_die();
    }
    
    
    // 
    // Save новый список пользователей
    // 
    function update_memberlist( $memberlist = array() )
    {
        if ( ! $this->args['can_edit'] ) return;
        
        $memberlist = array_unique( $memberlist );
        sort( $memberlist );
        $memberlist_ids = implode( ',', $memberlist );

        $res = update_user_meta( $this->args['user_id'], $this->args['members_usermeta'], $memberlist_ids );
        
        foreach ( (array) $memberlist as $member_id )
            update_user_meta( (int) $member_id, $this->args['members_usermeta'] . '_timestamp', time() );

        wp_cache_delete( 'memberlist', $this->args['user_id'] );

        return $res;
    }
    

    // 
    // Получить массив id пользователей своего списка
    // 
    function get_memberlist()
    {
        if ( ! $memberlist_arr = wp_cache_get( 'memberlist', $this->args['user_id'] ) ) {

            $memberlist = get_user_meta( $this->args['user_id'], $this->args['members_usermeta'], true );
            $exclude_users = $this->get_exclude_users_arr();
            $memberlist_arr = array_diff( array_unique( explode( ",", $memberlist ) ), $exclude_users, array( '' ) );
        
            wp_cache_set( 'memberlist', $memberlist_arr, $this->args['user_id'] );

        }

        return apply_filters( 'mif_bpc_members_page_get_memberlist', $memberlist_arr ) ;
    }


    // 
    // Options для запроса списка пользователей
    // 
    function members_param( $members_param, $object )
    {
        if ( $object == 'members' ) {
            $members_param_old = $members_param;
            if ( $this->args['members_usermeta'] ) $members_param = array( 'include' => implode( ',', $this->get_memberlist() ) );
        }

        return apply_filters( 'mif_bpc_members_page_members_param', $members_param, $members_param_old, $this->args, $object ) ;
    }    


    // 
    // Возвращает массив пользователей, которые не должны быть в списке
    // 
    function get_exclude_users_arr()
    {
        $exclude_users = $this->args['exclude_users'];
        $exclude_users = ( is_array( $exclude_users ) ) ? $exclude_users : explode( ',', $exclude_users );

        foreach ( $exclude_users as $key => $item ) $exclude_users[$key] = (int) $item;
        $exclude_users = array_diff( $exclude_users, array( '' ) );

        return apply_filters( 'mif_bpc_members_page_get_exclude_users_arr', $exclude_users ) ;
    }
    
}



?>