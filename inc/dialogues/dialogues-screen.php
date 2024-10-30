<?php

//
// Dialogues (экранные функции)
// 
//


defined( 'ABSPATH' ) || exit;


class mif_bpc_dialogues_screen extends mif_bpc_dialogues_core {

    //
    // Размер аватарки
    //

    public $avatar_thread_size = 50;
    public $avatar_message_size = 40;
    public $avatar_member_size = 25;


    function __construct()
    {
        
        add_action( 'wp_enqueue_scripts', array( $this, 'load_js_helper' ) );   
        
    }



    // 
    // JS-помощник
    // 

    function load_js_helper()
    {
        // Плагин красивого скроллинга
        wp_enqueue_script( 'mif_bpc_baron_core', plugins_url( '../../js/mif-bpc-baron.js', __FILE__ ) );

        // Плагин авторесайза формы
        wp_enqueue_script( 'mif_bpc_autosize', plugins_url( '../../js/autosize/autosize.js', __FILE__ ) );

        wp_enqueue_script( 'mif_bpc_dialogues_helper', plugins_url( '../../js/dialogues.js', __FILE__ ) );
    }



    //
    // Получить аватар отправителя
    //

    function get_sender_avatar( $thread, $avatar_size = 0 )
    {
        $user_id = ( count( $thread['user_ids'] ) == 1 ) ? $thread['user_ids'][0] : $thread['sender_id'];

        if ( $avatar_size == 0 ) $avatar_size = apply_filters( 'mif_bpc_dialogues_avatar_thread_size', $this->avatar_thread_size );
        $avatar = get_avatar( $user_id, $avatar_size );

        return apply_filters( 'mif_bpc_dialogues_get_sender_avatar', $avatar, $sender_id, $avatar_size );
    }



    // 
    // Выводит элемент списка диалогов
    // 

    function thread_item( $thread = NULL )
    {
        if ( $thread == NULL ) return;

        $avatar = $this->get_sender_avatar( $thread );
        $title = $this->get_thread_title( $thread );
        $time_since = apply_filters( 'mif_bpc_dialogues_thread_item_time_since', $this->time_since( $thread['date_sent'] ) );
        $message_excerpt = $this->get_message_excerpt( $thread['message'] );
        $unread_count = $thread['unread_count'];
        $unread = ( $unread_count ) ? ' unread' : '';
        $current = ( bp_is_current_action( 'view' ) && $thread['thread_id'] == (int) bp_action_variable( 0 ) ) ? ' current' : '';

        $out = '';

        $out .= '<div class="thread-item' . $unread . $current . '" id="thread-item-' . $thread['thread_id'] . '" data-thread-id="' . $thread['thread_id'] . '">';
        $out .= '<div>';
        $out .= '<span class="avatar">' . $avatar . '</span>';
        $out .= '<span class="content">';
        if ( $unread_count ) $out .= '<span class="unread_count">' . $unread_count . '</span>';
        $out .= '<div class="remove"><div class="custom-button"><a href="' . $this->get_dialogues_url() . '" class="button thread-remove" title="' . __( 'Delete', 'mif-bpc' ) . '"><i class="fa fa-times" aria-hidden="true"></i></a></div></div>';
        $out .= '<span class="title">' . $title . '</span>';
        $out .= '<div><span class="time-since">' . $time_since . '</span></div>';
        $out .= '<div><span class="message-excerpt">' . $message_excerpt . '</span></div>';
        $out .= '</span>';
        $out .= '</div>';
        $out .= '</div>';

        return apply_filters( 'mif_bpc_dialogues_thread_item', $out, $thread );
    }



    //
    // Получить элементы списка диалогов
    //

    function get_threads_items( $page = 0, $user_id = NULL )
    {
        if ( $user_id == NULL ) $user_id = bp_loggedin_user_id();

        $threads = $this->get_threads_data( $page, $user_id );

        $arr = array();
        foreach ( $threads as $thread ) $arr[] = $this->thread_item( $thread );

        $page ++;
        $nonce = wp_create_nonce( 'mif-bpc-dialogues-thread-items-more-nonce' );
        $arr[] = '<div class="thread-item loader ajax-ready" data-mode="threads" data-page="' . $page . '" data-nonce="' . $nonce . '"></div>';

        return apply_filters( 'mif_bpc_dialogues_get_threads_items', implode( "\n", $arr ), $arr, $page, $user_id );
    }



    //
    // Получить новые элементы списка диалогов
    //

    function get_threads_update( $last_updated = NULL, $user_id = NULL )
    {
        if ( $user_id == NULL ) $user_id = bp_loggedin_user_id();

        $threads = $this->get_threads_data( 0, $user_id, $last_updated );

        $arr = array();
        foreach ( $threads as $key => $thread ) $arr[] = array( 'id' => $key, 'item' => $this->thread_item( $thread ) );

        $arr = array_reverse( $arr );

        return apply_filters( 'mif_bpc_dialogues_get_threads_update', $arr, $user_id );
    }
    


    //
    // Получить HTML-блок сообщения
    //

    function message_item( $message = NULL )
    {
        if ( $message == NULL ) return;

        $url = bp_core_get_user_domain( $message->sender_id );

        $avatar_size = apply_filters( 'mif_bpc_dialogues_avatar_message_size', $this->avatar_message_size );
        $avatar = '<a href="' . $url . '" target="blank">' . get_avatar( $message->sender_id, $avatar_size ) . '</a>';
        $title = '<a href="' . $url . '" target="blank">' . $this->get_username( $message->sender_id ) . '</a>';
        $time_since = apply_filters( 'mif_bpc_dialogues_message_item_time_since', $this->time_since( $message->date_sent ) );
        $message_message = apply_filters( 'mif_bpc_dialogues_message_item_message', $message->message );
        $new = ( $message->new ) ? ' new' : '';
        $attachments = bp_messages_get_meta( $message->id, $this->message_attachment_meta_key, false );

        $out = '';

        $out .= '<div class="message-item' . $new . '" id="message-' . $message->id . '" data-message-id="' . $message->id . '" data-sent="' . $message->date_sent . '">';
        $out .= '<div class="avatar">' . $avatar . '</div>';
        $out .= '<div class="remove"><div class="custom-button"><a href="' . $this->get_dialogues_url() . '" class="button message-remove" title="' . __( 'Delete', 'mif-bpc' ) . '"><i class="fa fa-times" aria-hidden="true"></i></a></div></div>';
        $out .= '<div class="content">';
        $out .= '<span class="title">' . $title . '</span> ';
        $out .= '<span class="time-since">' . $time_since . '</span>';
        $out .= '<span class="message">' . $message_message . '</span>';
        $out .= $this->attachments( $attachments );
        $out .= '</div>';
        $out .= '</div>';

        return apply_filters( 'mif_bpc_dialogues_message_item', $out, $message );
    }



    //
    // Сформировать ссылку на прикрепленный файл
    //

    function attachments( $attachments )
    {
        if ( empty( $attachments ) ) return;

        $out = '';

        foreach ( (array) $attachments as $attach ) {

            if ( is_numeric( $attach ) ) {

                // Найден номер стандартной системы документов

                $attach_data = apply_filters( 'mif_bpc_get_attachments_data', array(), $attach );

                $name = $attach_data['name'];
                $type = $attach_data['type'];
                $icon = $attach_data['icon'];
                $url = trailingslashit( $attach_data['url'] ) . 'download/';

            } else {

                // Хранится просто адрес документа

                $name = array_pop( explode( '/', $attach ) );
                $type = array_pop( explode( '.', $attach ) );
                $icon = mif_bpc_get_file_icon( $type );
                $url = $attach;

            }
            
            if ( ! empty( $name ) ) {

                $out .= '<span class="clearfix attach ' .  $type . '">';
                $out .= '<a href="' . $url . '" target="blank"><span class="icon">' . $icon . '</span><span class="name">' . $name . '</span></a>';
                $out .= '</span>';

            }
            
        }

        return apply_filters( 'mif_bpc_dialogues_attachments', $out, $attachments );
    }



    //
    // Получить страницу cообщений из диалога
    //

    function get_messages_page( $thread_id = NULL, $page = 0 )
    {
        global $bp, $wpdb;

        if ( $thread_id == NULL ) return false;

        // Проверка прав пользователя на просмотр этих сообщений

        if ( ! $this->is_access( $thread_id ) ) return false;

        // Получить нужную страницу сообщений

        $messages = $this->get_messages_data( $thread_id, $page );
        if ( $page === 0 ) $this->mark_as_read( $thread_id );

        if ( empty( $messages ) ) return false;

        // Оформить сообщения в виде HTML-блоков 

        $arr = array();
        foreach ( (array) $messages as $message ) $arr[] = $this->message_item( $message );

        $page ++;
        $nonce = wp_create_nonce( 'mif-bpc-dialogues-messages-items-more-nonce' );
        // $arr[] = '<div class="message-item loader ajax-ready" data-page="' . $page . '" data-nonce="' . $nonce . '" data-tid="' . $thread_id . '"><i class="fa fa-spinner fa-spin fa-fw"></i></div>';
        $arr[] = '<div class="message-item loader ajax-ready" data-page="' . $page . '" data-nonce="' . $nonce . '" data-tid="' . $thread_id . '"></div>';

        $arr = array_reverse( $arr );

        if ( $msg = $this->is_alone( $thread_id ) ) $arr[] = '<div class="message-item alone"><span>' . $msg . '</span></div>';

        return apply_filters( 'mif_bpc_dialogues_get_messages_page', implode( "\n", $arr ), $arr, $page, $thread_id );
    }



    // 
    // Выводит форму написания сообщения
    // 

    function get_messages_form( $thread_id )
    {
        $last_message_id = $this->get_last_message_id( $thread_id );
        $url = $this->get_dialogues_url();

        $form_attachment = apply_filters( 'mif_bpc_dialogues_get_messages_form_attachment', '', $thread_id );
        $clip = ( $form_attachment ) ? '<td class="clip"><a href="' . $url . '" class="clip"><i class="fa fa-2x fa-paperclip" aria-hidden="true"></i></a></td>' : '<td class="message">&nbsp;</td>';

        $out = '';
        $out .= '<form>';
        $out .= '<table><tr>';
        $out .= $clip;
        $out .= '<td class="message"><textarea name="message" id="message" placeholder="' . __( 'Type a message…', 'mif-bpc' ) . '" rows="1"></textarea></td>';
        $out .= '<td class="send"><div class="custom-button"><a href="' . $url . '" class="send button"><i class="fa fa-chevron-right" aria-hidden="true"></i></a></div></td>';
        $out .= '</tr>';
        $out .= $form_attachment;
        $out .= '</table>';
        $out .= wp_nonce_field( 'mif-bpc-dialogues-messages-send-nonce', 'nonce', true, false );
        $out .= '<input type="hidden" name="thread_id" id="thread_id" value="' . $thread_id . '">';
        $out .= '<input type="hidden" name="last_message_id" id="last_message_id" value="' . $last_message_id . '">';
        $out .= '</form>';

        return apply_filters( 'mif_bpc_dialogues_get_messages_form', $out, $thread_id );
    }



    // 
    // Выводит форму создания нового сообщения
    // 

    function get_compose_form( $recipient_ids = array() )
    {
        $recipients = '';

        if ( ! empty( $recipient_ids ) ) {

            $recipient_items = array();
            foreach ( (array) $recipient_ids as $recipient_id ) $recipient_items[] = $this->member_item( $recipient_id );

            $recipients = implode( "\n", $recipient_items );

        }

        $out = '';
        $out .= '<div>';
        $out .= '<div class="compose-wrap">';
        $out .= '<form>';
        $out .= '<div>' . __( 'To:', 'mif-bpc' ) . '</div>';
        $out .= '<div class="recipients">' . $recipients . '</div>';
        $out .= '<div>' . __( 'Message:', 'mif-bpc' ) . '</div>';
        $out .= '<div class="textarea"><textarea name="message" id="message"></textarea></div>';
        $out .= '<div><label><input type="checkbox" value="on" name="email" id="email"> ' . __( 'Notify by email', 'mif-bpc' ) . '</label></div>';
        $out .= '<div><input type="submit" value="' . __( 'Send', 'mif-bpc' ) . '"></div>';
        $out .= '<input type="hidden" name="nonce" id="nonce" value="' . wp_create_nonce( 'mif-bpc-dialogues-compose-send-nonce' ) . '">';
        $out .= '</form>';
        $out .= '</div>';
        $out .= '</div>';

        return apply_filters( 'mif_bpc_dialogues_get_compose_form', $out );
    }



    //
    // Страница диалога в обертке
    //

    function get_messages_page_wrapped( $thread_id, $page = 0 )
    {
        $before = '<div class="messages-scroller-wrap scroller-wrap"><div></div><div class="messages-scroller scroller"><div class="messages-scroller-container scroller-container">';
        $after = '</div><div class="messages-scroller__bar scroller__bar"></div></div></div>';
        $page = $this->get_messages_page( $thread_id, $page );

        $recipients = $this->get_recipients_of_thread( $thread_id, bp_loggedin_user_id() );

        $writing = '';
        foreach ( (array) $recipients as $recipient_id ) {

            $name = mif_bpc_get_member_name( $recipient_id );
            $writing .= '<div class="writing thread-' . $thread_id . ' user-' . $recipient_id . '"><span class="s1"></span><span class="s2"></span><span class="s3"></span> ' . $name . ' ' . __( 'is typing', 'mif-bpc' ) . '</div>';

        }

        $out = $before . $page . $after . $writing;

        return apply_filters( 'mif_bpc_dialogues_get_messages_page_wrapped', $out, $page, $before, $after );
    }



    //
    // Заголовок формы нового сообщения
    //

    function get_compose_header()
    {
        $out = '';

        $out .= '<div class="custom-button"><a href="' . $this->get_dialogues_url() . '" class="button dialogues-refresh" title="' . __( 'Cancel', 'mif-bpc' ) . '"><i class="fa fa-times" aria-hidden="true"></i></a></div>';
        $out .= '<span class="title">' . __( 'New message', 'mif-bpc' ) . '</span>';

        return apply_filters( 'mif_bpc_dialogues_get_compose_header', $out );
    }



    //
    // Получить список пользователей
    //

    function get_members_items( $page = 1 )
    {

        $user_id = bp_loggedin_user_id();

        $args = array(
                'per_page' => $this->members_on_page,
                'page' => $page,
                'exclude' => $user_id,
        );

        $args = apply_filters( 'mif_bpc_dialogues_get_members_list_args', $args );

        $arr = array();

        if ( bp_is_current_action( 'compose' ) ) {

            $recipient_id = bp_core_get_userid_from_nicename( bp_action_variable( 0 ) );
            if ( $item = $this->member_item( $recipient_id, true ) ) {

                if ( $page == 1 ) $arr[] = $item;
                $args['exclude'] = array( $user_id, $recipient_id );
            }

        }

        if ( bp_has_members( $args ) ) {

            while ( bp_members() ) {

                bp_the_member(); 
                // $arr[] = $this->member_item( bp_get_member_user_id(), bp_get_member_link(), bp_get_member_name() );
                $arr[] = $this->member_item( bp_get_member_user_id() );

            }; 

        }

        $page ++;
        $nonce = wp_create_nonce( 'mif-bpc-dialogues-member-items-more-nonce' );
        $arr[] = '<div class="member-item loader ajax-ready" data-mode="compose" data-page="' . $page . '" data-nonce="' . $nonce . '"></div>';

        return apply_filters( 'mif_bpc_dialogues_get_members_items', implode( "\n", $arr ), $arr, $page );
    }



    //
    // Получить блок пользователя
    //

    function member_item( $user_id, $checked = false )
    {
        if ( empty( $user_id ) ) return;

        $user_url = bp_core_get_user_domain( $user_id );
        $name = mif_bpc_get_member_name( $user_id );

        $out = '';

        $avatar_size = apply_filters( 'mif_bpc_dialogues_avatar_member_size', $this->avatar_member_size );
        $avatar = get_avatar( $user_id, $avatar_size );
        $url = $this->get_dialogues_url();

        $checked_class = ( $checked ) ? ' checked' : '';

        $out .= '<div class="member-item member-' . $user_id . $checked_class . '" data-uid="' . $user_id . '">';
        $out .= '<div class="m-check checked"><a href="' . $url . '" class="member-add" title="' . __( 'Add', 'mif-bpc' ) . '"><i class="fa fa-circle" aria-hidden="true"></i></a></div>';
        $out .= '<div class="m-check unchecked"><a href="' . $url . '" class="member-add" title="' . __( 'Add', 'mif-bpc' ) . '"><i class="fa fa-circle-thin" aria-hidden="true"></i></a></div>';
        $out .= '<span class="avatar"><a href="' . $user_url . '" target="blank">' . $avatar . '</a></span>';
        $out .= '<span class="name"><a href="' . $user_url . '" target="blank">' . $name . '</a></span>';
        $out .= '<span class="m-remove"><div class="custom-button"><a href="' . $url . '" class="button member-remove" title="' . __( 'Delete', 'mif-bpc' ) . '"><i class="fa fa-times" aria-hidden="true"></i></a></div></span>';
        $out .= '</div>';

        return apply_filters( 'mif_bpc_dialogues_member_item', $out, $user_id );
    }



    // 
    // Страница с сообщением о успешной группировке диалогов
    // 

    function dialogues_join_success_page( $msg )
    {
        $out = '';

        $out .= '<div class="messages-empty"><div>';
        $out .= '<i class="fa fa-5x fa-compress" aria-hidden="true"></i>';
        // $out .= '<p><strong>' . __( 'Grouping completed successfully', 'mif-bpc' ) . '</strong>';
        $out .= '<p><strong>' . $msg . '</strong>';
        $out .= '<p>' . __( 'Select a dialogue or', 'mif-bpc' ) . '<br />';
        $out .= '<a href="' . $this->get_dialogues_url() . '" class="dialogues-compose">' . __( 'start new', 'mif-bpc' ) . '</a></p>';
        $out .= '</div></div>';

        return $out;
    }




    //
    // Получить массив HTML-блоков сообщений
    //

    function get_messages_items( $thread_id, $last_message_id )
    {

        // Получить сообщения, начная с $last_message_id
        $messages = $this->get_messages_data( $thread_id, 0, $last_message_id );

        if ( empty( $messages ) ) return false;

        // Оформить сообщения в виде HTML-блоков 
        $arr = array();
        foreach ( (array) $messages as $message ) $arr[$message->id] = $this->message_item( $message );

        $arr = array_reverse( $arr, true );

        return apply_filters( 'mif_bpc_dialogues_get_messages_items', $arr, $thread_id, $last_message_id );
    }



    //
    // Список диалогов or пользователей
    //

    function get_dialogues_default_threads( $ajax = false )
    {
        $out = '';

        $out .= '<div class="thread-scroller-wrap scroller-wrap"><div class="thread-scroller scroller"><div class="thread-scroller-container scroller-container">';
        
        if( bp_is_current_action( 'compose' ) && ! $ajax ) {

            $out .= $this->get_members_items();

        } else {

            $out .= $this->get_threads_items();

        }

        $out .= '</div><div class="thread-scroller__bar scroller__bar"></div></div></div>';
        
        return apply_filters( 'mif_bpc_dialogues_get_dialogues_default_threads', $out );
    }



    //
    // Скрытые поля для AJAX-запросов
    //

    function get_hidden_fields()
    {
        $out = '';

        $threads_update_timestamp = time();
        $out .= '<input type="hidden" id="threads_update_timestamp" value="' . $threads_update_timestamp . '">';
        
        $threads_mode = ( bp_is_current_action( 'compose' ) ) ? 'compose' : 'threads';
        $out .= '<input type="hidden" id="threads_mode" value="' . $threads_mode . '">';

        $out .= '<input type="hidden" id="dialogues_refresh_nonce" value="' . wp_create_nonce( 'mif-bpc-dialogues-refresh-nonce' ) . '">';
        $out .= '<input type="hidden" id="dialogues_join_nonce" value="' . wp_create_nonce( 'mif-bpc-dialogues-join-nonce' ) . '">';
        $out .= '<input type="hidden" id="dialogues_message_remove_nonce" value="' . wp_create_nonce( 'mif-bpc-dialogues-message-remove-nonce' ) . '">';
        $out .= '<input type="hidden" id="dialogues_thread_remove_window_nonce" value="' . wp_create_nonce( 'mif-bpc-dialogues-thread-remove-window-nonce' ) . '">';
        $out .= '<input type="hidden" id="dialogues_thread_remove_nonce" value="' . wp_create_nonce( 'mif-bpc-dialogues-thread-remove-nonce' ) . '">';
        $out .= '<input type="hidden" id="dialogues_thread_nonce" value="' . wp_create_nonce( 'mif-bpc-dialogues-thread-nonce' ) . '">';
        $out .= '<input type="hidden" id="dialogues_compose_form_nonce" value="' . wp_create_nonce( 'mif-bpc-dialogues-compose-form-nonce' ) . '">';
        $out .= '<input type="hidden" id="dialogues_compose_nonce" value="' . wp_create_nonce( 'mif-bpc-dialogues-compose-nonce' ) . '">';
        $out .= '<input type="hidden" id="dialogues_search_nonce" value="' . wp_create_nonce( 'mif-bpc-dialogues-search-nonce' ) . '">';
        $out .= '<input type="hidden" id="dialogues_write_notification_nonce" value="' . wp_create_nonce( 'mif-bpc-dialogues-write-notification-nonce' ) . '">';

        return apply_filters( 'mif_bpc_dialogues_get_hidden_fields', $out );
    }



    //
    // Страница по умолчанию
    //

    function get_dialogues_default_page( $ajax = false )
    {
        $out = '';

        if ( bp_is_current_action( 'view' ) && ! $ajax  ) {

            $thread_id = (int) bp_action_variable( 0 );
            $out .= $this->get_messages_page_wrapped( $thread_id );
            $out .= '<script>addEventListener( "load", function(){ messages_actions_init(); scroll_message_to_bottom(); } )</script>';

        } elseif( bp_is_current_action( 'compose' ) && ! $ajax ) {

            $recipient_id = bp_core_get_userid_from_nicename( bp_action_variable( 0 ) );
            $out .= $this->get_compose_form( array( $recipient_id ) );

        } else {

            $out .= '<div class="messages-empty"><div>';
            $out .= '<i class="fa fa-5x fa-comments-o" aria-hidden="true"></i>';
            $out .= '<p>' . __( 'Select a dialogue or', 'mif-bpc' ) . '<br />';
            $out .= '<a href="' . $this->get_dialogues_url() . '" class="dialogues-compose">' . __( 'start new', 'mif-bpc' ) . '</a></p>';
            $out .= '</div></div>';

        }

        return apply_filters( 'mif_bpc_dialogues_get_dialogues_default_page', $out );;
    }



    //
    // Заголовок по умолчанию
    //

    function get_dialogues_default_header( $ajax = false )
    {
        $out = '';

        if ( bp_is_current_action( 'view' ) && ! $ajax ) {

            $thread_id = (int) bp_action_variable( 0 );
            $out .= $this->get_messages_header( $thread_id );

        } elseif( bp_is_current_action( 'compose' ) && ! $ajax ) {

            $out .= $this->get_compose_header();

        } else {

            $out .= '<!-- empty -->';

        }

        return apply_filters( 'mif_bpc_dialogues_get_dialogues_default_header', $out );;
    }



    //
    // Форма по умолчанию
    //

    function get_dialogues_default_form( $ajax = false )
    {
        $out = '';

        if ( bp_is_current_action( 'view' ) && ! $ajax ) {

            $thread_id = (int) bp_action_variable( 0 );
            $out .= $this->get_messages_form( $thread_id );
            $out .= '<script>addEventListener( "load", function(){ message_items_height_correct(); } )</script>';

        } else {

            $out .= '<div class="form-empty"></div>';

        }

        return apply_filters( 'mif_bpc_dialogues_get_dialogues_default_header', $out );;
    }



}


?>