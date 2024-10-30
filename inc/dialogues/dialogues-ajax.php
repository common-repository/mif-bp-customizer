<?php

//
// Dialogues (функции ajax-запросов)
// 
//


defined( 'ABSPATH' ) || exit;


class mif_bpc_dialogues_ajax extends mif_bpc_dialogues_screen {


    function __construct()
    {
        parent::__construct();       
        
        // Ajax-события

        add_action( 'wp_ajax_mif-bpc-dialogues-thread-items-more', array( $this, 'ajax_thread_more_helper' ) );
        add_action( 'wp_ajax_mif-bpc-dialogues-thread-search', array( $this, 'ajax_thread_search_helper' ) );
        add_action( 'wp_ajax_mif-bpc-dialogues-member-items-more', array( $this, 'ajax_member_more_helper' ) );
        add_action( 'wp_ajax_mif-bpc-dialogues-member-search', array( $this, 'ajax_member_search_helper' ) );
        add_action( 'wp_ajax_mif-bpc-dialogues-messages', array( $this, 'ajax_messages_helper' ) );
        add_action( 'wp_ajax_mif-bpc-dialogues-messages-items-more', array( $this, 'ajax_messages_more_helper' ) );
        add_action( 'wp_ajax_mif-bpc-dialogues-messages-send', array( $this, 'ajax_messages_send_helper' ) );
        add_action( 'wp_ajax_mif-bpc-dialogues-write-notification', array( $this, 'ajax_write_notification_helper' ) );
        add_action( 'wp_ajax_mif-bpc-dialogues-compose-send', array( $this, 'ajax_compose_send_helper' ) );
        add_action( 'wp_ajax_mif-bpc-dialogues-refresh', array( $this, 'ajax_dialogues_refresh' ) );
        add_action( 'wp_ajax_mif-bpc-dialogues-join', array( $this, 'ajax_dialogues_join' ) );
        add_action( 'wp_ajax_mif-bpc-dialogues-compose-form', array( $this, 'ajax_dialogues_compose_form' ) );
        add_action( 'wp_ajax_mif-bpc-message-remove', array( $this, 'ajax_message_remove' ) );
        add_action( 'wp_ajax_mif-bpc-thread-remove-window', array( $this, 'ajax_thread_remove_window' ) );
        add_action( 'wp_ajax_mif-bpc-thread-remove', array( $this, 'ajax_thread_remove' ) );

    }



    //
    // Загрузка продолжения списка диалогов
    //

    function ajax_thread_more_helper()
    {
        check_ajax_referer( 'mif-bpc-dialogues-thread-items-more-nonce' );

        $page = (int) $_POST['page'];
        
        $arr = array( 'threads_more' => $this->get_threads_items( $page ) );
        $arr = apply_filters( 'mif_bpc_dialogues_ajax_thread_more_helper', $arr, $page );

        echo json_encode( $arr );

        wp_die();
    }



    //
    // Search диалогов
    //

    function ajax_thread_search_helper()
    {
        check_ajax_referer( 'mif-bpc-dialogues-search-nonce' );

        $arr = array( 'threads_window_update' => $this->get_threads_items() );
        $arr = apply_filters( 'mif_bpc_dialogues_ajax_thread_search_helper', $arr );

        echo json_encode( $arr );

        wp_die();
    }



    //
    // Загрузка диалога
    //

    function ajax_messages_helper()
    {
        check_ajax_referer( 'mif-bpc-dialogues-thread-nonce' );

        $thread_id = (int) $_POST['thread_id'];
        $page = (int) $_POST['page'];

        $out = '';

        // $out .= '<div class="messages-scroller-wrap scroller-wrap"><div></div><div class="messages-scroller scroller"><div class="messages-scroller-container scroller-container">';
        // $out .= $this->get_messages_page( $thread_id, $page );
        // $out .= '</div><div class="messages-scroller__bar scroller__bar"></div></div></div>';

        $out .= $this->get_messages_page_wrapped( $thread_id, $page );

        $arr = array( 
                    'messages_page' => $out,
                    'messages_header' => $this->get_messages_header( $thread_id ),
                    'messages_form' => $this->get_messages_form( $thread_id ),
                    'threads_unread_count' => $this->get_unread_count(),
                    );
        $arr = apply_filters( 'mif_bpc_dialogues_ajax_messages_helper', $arr, $thread_id, $page );

        echo json_encode( $arr );


        wp_die();
    }



    //
    // Загрузка продолжения списка сообщений
    //

    function ajax_messages_more_helper()
    {
        check_ajax_referer( 'mif-bpc-dialogues-messages-items-more-nonce' );

        $thread_id = (int) $_POST['tid'];
        $page = (int) $_POST['page'];

        $arr = array( 'messages_more' => $this->get_messages_page( $thread_id, $page ) );
        $arr = apply_filters( 'mif_bpc_dialogues_ajax_messages_more_helper', $arr, $thread_id, $page );

        echo json_encode( $arr );

        wp_die();
    }



    //
    // Отправка сообщения (форма диалога)
    //

    function ajax_messages_send_helper()
    {
        check_ajax_referer( 'mif-bpc-dialogues-messages-send-nonce' );

        $thread_id = (int) $_POST['thread_id'];
        $last_message_id = (int) $_POST['last_message_id'];
        $threads_update_timestamp = (int) $_POST['threads_update_timestamp'];
        $message = esc_html( sanitize_textarea_field( $_POST['message'] ) );
        
        $attachments = array();
        $aid_arr = explode( '&', sanitize_text_field( $_POST['attachments'] ) );
        
        foreach ( (array) $aid_arr as $aid ) $attachments[] = (int) end( explode( '=', $aid ) );

        $message_id = $this->send( $message, $thread_id );

        if ( $message_id ) {

            foreach ( $attachments as $attachment ) bp_messages_add_meta( $message_id, $this->message_attachment_meta_key, $attachment );

            $messages = $this->get_messages_items( $thread_id, $last_message_id );

            $arr = array( 
                        'messages_header_update' => $this->get_messages_header( $thread_id ),
                        'messages_update' => $messages,
                        'threads_update' => $this->get_threads_update( $threads_update_timestamp ),
                        'threads_update_timestamp' => time(),
                        );
            $arr = apply_filters( 'mif_bpc_dialogues_ajax_messages_send_helper', $arr, $thread_id, $message, $last_message_id, $threads_update_timestamp );

            echo json_encode( $arr );

        }

        wp_die();
    }



    //
    // Отправка уведомления о том, что пользователь вводит сообщение
    //

    function ajax_write_notification_helper()
    {
        check_ajax_referer( 'mif-bpc-dialogues-write-notification-nonce' );

        $thread_id = (int) $_POST['thread_id'];
        $sender_id = bp_loggedin_user_id();
        $recipients = $this->get_recipients_of_thread( $thread_id, $sender_id );

        do_action( 'mif_bpc_dialogues_write_notification', $thread_id, $recipients, $sender_id );

        wp_die();
    }



    //
    // Отправка сообщения (форма нового сообщения)
    //

    function ajax_compose_send_helper()
    {
        check_ajax_referer( 'mif-bpc-dialogues-compose-send-nonce' );

        $email_status = (int) $_POST['email'];
        $message = esc_html( sanitize_textarea_field( $_POST['message'] ) );
        $subject = esc_html( sanitize_text_field( $_POST['subject'] ) );
        $recipient_ids = array_map( 'sanitize_key', $_POST['recipient_ids'] );

        // Получить чистый список получателей

        $recipient_clean_ids = array();
        foreach ( $recipient_ids as $recipient_id ) { 
            
            $recipient = get_user_by( 'ID', $recipient_id );
            if ( $recipient ) $recipient_clean_ids[] = $recipient->ID;
            
        }

        // Если получателей нет, то ничего и не делать
       
        if ( count( $recipient_clean_ids ) == 0 ) {

            $arr = array( 
                        'messages_header' => '<!-- empty -->',
                        'messages_page' => __( 'Error. The users you specified don’t exist', 'mif-bpc' ),
                        'threads_window' => $this->get_threads_items(),
                        );
            $arr = apply_filters( 'mif_bpc_dialogues_ajax_compose_send_helper_no_send', $arr, $message, $recipient_ids, $subject, $email_status );

            echo json_encode( $arr );

            wp_die();

        } 

        // Save сообщение

        $thread_id = $this->get_thread_id( $recipient_clean_ids );
        $res = $this->send( $message, $thread_id, NULL, $subject, $email_status );

        if ( $res ) {

            $out .= '<div class="messages-scroller-wrap scroller-wrap"><div></div><div class="messages-scroller scroller"><div class="messages-scroller-container scroller-container">';
            $out .= $this->get_messages_page( $thread_id );
            $out .= '</div><div class="messages-scroller__bar scroller__bar"></div></div></div>';

            $arr = array( 
                        'messages_page' => $out,
                        'messages_header' => $this->get_messages_header( $thread_id ),
                        'messages_form' => $this->get_messages_form( $thread_id ),
                        'threads_window' => $this->get_threads_items(),
                        );

            $arr = apply_filters( 'mif_bpc_dialogues_ajax_compose_send_helper', $arr, $thread_id, $message, $recipient_ids, $subject, $email_status );

            echo json_encode( $arr );

        }

        // Действие после отправки сообщения (учитывать, что есть аналогичное в send).

        do_action( 'mif_bpc_dialogues_after_compose_send', $message, $recipient_clean_ids, $subject, $email_status );

        wp_die();
    }



    //
    // Удаление сообщения
    //

    function ajax_message_remove()
    {
        check_ajax_referer( 'mif-bpc-dialogues-message-remove-nonce' );
        
        $message_id = (int) $_POST['message_id'];
        $threads_update_timestamp = (int) $_POST['threads_update_timestamp'];
        $user_id = bp_loggedin_user_id();

        if ( bp_messages_add_meta( $message_id, 'deleted', $user_id ) ) {

            $arr['threads_update'] = $this->get_threads_update( $threads_update_timestamp );
            $arr['threads_update_timestamp'] = time();

            $arr = apply_filters( 'mif_bpc_dialogues_ajax_message_remove', $arr, $message_id, $user_id, $threads_update_timestamp );

            echo json_encode( $arr );
        };

        wp_die();
    }



    //
    // Вывод окна удаления диалога
    //

    function ajax_thread_remove_window()
    {
        check_ajax_referer( 'mif-bpc-dialogues-thread-remove-window-nonce' );

        $thread_id = (int) $_POST['thread_id'];
        $url = $this->get_dialogues_url();

        $out = '';
        $out .= '<div class="remove-window">';
        $out .= '<i class="fa fa-5x  fa-exclamation-circle " aria-hidden="true"></i>';
        $out .= '<p>';
        $out .=  __( 'You want to <strong>delete all messages</strong> in this dialogue.', 'mif-bpc' );
        $out .= '<br />';
        $out .=  __( 'Be careful, this operation <strong>can not be undone</strong>.', 'mif-bpc' );
        $out .=  '<p><div class="generic-button"><a href="' . $url . '" class="thread-remove">' . __( 'Delete', 'mif-bpc' ) . '</a></div>';
        $out .=  '<div class="generic-button"><a href="' . $url . '" class="thread-no-remove">' . __( 'Don’t delete', 'mif-bpc' ) . '</a></div>';
        $out .= '</div>';

        $arr = array( 
                    'messages_window' => $out,
                    'messages_header' => $this->get_messages_header( $thread_id ),
                    'messages_form' => $this->get_messages_form( $thread_id ),
                    );
        $arr = apply_filters( 'mif_bpc_dialogues_ajax_thread_remove_window', $arr, $thread_id );

        echo json_encode( $arr );


        wp_die();
    }



    //
    // Удаление диалога
    //

    function ajax_thread_remove()
    {
        check_ajax_referer( 'mif-bpc-dialogues-thread-remove-nonce' );

        $thread_id = (int) $_POST['thread_id'];

        $this->delete_thread( $thread_id );

        $arr = array( 
                    'messages_window' => $this->get_dialogues_default_page( true ),
                    'messages_header' => '<!-- ajaxed -->',
                    'messages_form' => '<div class="form-empty"></div>',
                    );
        $arr = apply_filters( 'mif_bpc_dialogues_ajax_thread_remove', $arr, $thread_id );

        echo json_encode( $arr );

        wp_die();
    }



    //
    // Форма создания нового сообщения
    //

    function ajax_dialogues_compose_form()
    {
        check_ajax_referer( 'mif-bpc-dialogues-compose-form-nonce' );

        $arr = array( 
                    'compose_members' => $this->get_members_items(),
                    'compose_form' => $this->get_compose_form(),
                    'messages_header' => $this->get_compose_header(),
                    'messages_form' => '<div class="form-empty"></div>',
                    );
        $arr = apply_filters( 'mif_bpc_dialogues_ajax_dialogues_compose_form', $arr );

        echo json_encode( $arr );

        wp_die();
    }



    //
    // Загрузка продолжения списка пользователей
    //

    function ajax_member_more_helper()
    {
        check_ajax_referer( 'mif-bpc-dialogues-member-items-more-nonce' );

        $page = (int) $_POST['page'];

        $arr = array( 'threads_more' => $this->get_members_items( $page ) );
        $arr = apply_filters( 'mif_bpc_dialogues_ajax_member_more_helper', $arr, $page );

        echo json_encode( $arr );

        wp_die();
    }



    //
    // Search пользователей 
    //

    function ajax_member_search_helper()
    {
        check_ajax_referer( 'mif-bpc-dialogues-search-nonce' );

        $arr = array( 'compose_members_update' => $this->get_members_items() );
        $arr = apply_filters( 'mif_bpc_dialogues_ajax_member_search_helper', $arr );

        echo json_encode( $arr );

        wp_die();
    }



    //
    // Ajax-помощник группировки диалогов
    //

    function ajax_dialogues_join()
    {
        check_ajax_referer( 'mif-bpc-dialogues-join-nonce' );


        if ( $msg = $this->threads_joining() ) {

            $arr = array();
            $arr['threads_update'] = $this->get_threads_update();
            $arr['threads_update_timestamp'] = time();
            $arr['messages_window'] = $this->dialogues_join_success_page( $msg );

            $arr = apply_filters( 'mif_bpc_dialogues_ajax_dialogues_join', $arr );

            echo json_encode( $arr );
    
        }

        wp_die();
    }



    //
    // Обновление страницы диалогов
    //

    function ajax_dialogues_refresh()
    {
        check_ajax_referer( 'mif-bpc-dialogues-refresh-nonce' );
        $thread_id = (int) $_POST['thread_id'];
        $last_message_id = (int) $_POST['last_message_id'];
        $threads_update_timestamp = (int) $_POST['threads_update_timestamp'];
        $threads_mode = sanitize_key( $_POST['threads_mode'] );

        $arr = array();

        // Что показывается - диалоги or пользователи?

        if ( $threads_mode == 'threads' ) {

            $threads_update = $this->get_threads_update( $threads_update_timestamp );
            if ( $threads_update ) $arr['threads_update'] = $threads_update;
            $arr['threads_update_timestamp'] = time();

        } elseif ( $threads_mode == 'compose' ) {

            $arr['threads_window'] = $this->get_threads_items();

        }

        $messages_update = $this->get_messages_items( $thread_id, $last_message_id );

        if ( $messages_update ) { 
            
            $arr['messages_update'] = $messages_update; 
            $messages_header_update = $this->get_messages_header( $thread_id );
            if ( $messages_header_update ) $arr['messages_header_update'] = $messages_header_update;
            
        } else {

            $arr['messages_header'] = '<!-- empty -->'; 
            $arr['messages_window'] = $this->get_dialogues_default_page( true ); 

        }

        $arr['threads_unread_count'] = $this->get_unread_count();

        $arr = apply_filters( 'mif_bpc_dialogues_ajax_dialogues_refresh', $arr, $thread_id, $last_message_id, $threads_update_timestamp, $threads_mode );

        echo json_encode( $arr );

        wp_die();
    }

}


?>