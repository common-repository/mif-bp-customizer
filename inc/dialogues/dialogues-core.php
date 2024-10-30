<?php

//
// Dialogues (параметры и методы ядра)
// 
//


defined( 'ABSPATH' ) || exit;


class mif_bpc_dialogues_core {

    //
    // Диалогов на одной странице в списке диалогов
    //

    public $threads_on_page = 10;

    //
    // Сообщений на одной странице сообщений
    //

    public $messages_on_page = 10;

    //
    // Пользователей на одной странице сообщений
    //

    public $members_on_page = 20;

    //
    // Время устаревания сообщения (секунд). Используется для определения - обновлять текущее сообщение, or создавать новое
    //

    public $message_outdate_time = 60;

    //
    // Мета-поле прикрепленных файлов
    //

    public $message_attachment_meta_key = 'mif-bpc-attachment';



    function __construct()
    {
       
    }



    //
    // Получить заголовок диалога
    //

    function get_thread_title( $thread, $links = false )
    {
        $sender_ids = $thread['user_ids'];
        $subject = $thread['subject'];

        $arr = array();

        if ( count( $sender_ids ) > 3 ) {

                $arr[] = $this->get_username( $thread['sender_id'], $links );
                $sender_ids_without_sender_id = array_merge( $sender_ids, array( $thread['sender_id'] ) );
                $arr[] = $this->get_username( $sender_ids_without_sender_id[0], $links );

                $title = implode( ', ', $arr );
                $title .= ' ' . sprintf( __( 'And others (%s in total)', 'mif-bpc' ), number_format_i18n( count( $sender_ids ) ) );

        } else {

            foreach ( (array) $sender_ids as $sender_id ) $arr[] = $this->get_username( $sender_id, $links );
            $title = implode( ', ', $arr );

        }

        return apply_filters( 'mif_bpc_dialogues_thread_title', $title, $thread );
    }



    //
    // Сформировать имя пользователя для заголовков диалогов
    //

    function get_username( $user_id, $links = false )
    {
        $username = bp_core_get_user_displayname( $user_id );

        if ( empty( $username ) ) { 

            $username = 'deleted';

        } elseif ( $links ) {

            $url = bp_core_get_user_domain( $user_id );
            $username = '<a href="' . $url . '">' . $username . '</a>';

        }

        return apply_filters( 'mif_bpc_dialogues_get_username', $username, $user_id, $links );
    }


    //
    // Начало последней фразы сообщения
    //

    function get_message_excerpt( $message )
    {
        $old = $message;
        $message = array_pop( explode( "\n", $message ) );
        $message = preg_replace( '/[\s]+/s', ' ', $message );
        $message = apply_filters( 'mif_bpc_dialogues_message_item_message', $message );
        $message = bp_create_excerpt( $message, 50, array( 'ending' => '...' ) );

        return apply_filters( 'mif_bpc_dialogues_message_excerpt', $message, $old );
    }



    //
    // Получить данные списка диалогов
    //

    function get_threads_data( $page = 0, $user_id = NULL, $last_updated = NULL )
    {
        global $bp, $wpdb;

        if ( $user_id == NULL ) $user_id = bp_loggedin_user_id();

        // Получить сведения о диалогах (номер, дата, id последнего сообщения, количество непрочитанных сообщений)

        $search_sql = '';
        $user_id_sql = $wpdb->prepare( 'r.user_id = %d', $user_id );
        $pag_sql = $wpdb->prepare( "LIMIT %d, %d", intval( ( $page ) * $this->threads_on_page ), intval( $this->threads_on_page ) );

        // Условие для обновления
        
        if ( isset( $last_updated ) ) {

            $sql = array();
            $sql['select'] = 'SELECT DISTINCT m.thread_id';
            $sql['from']   = "FROM {$bp->messages->table_name_messages} m INNER JOIN {$bp->messages->table_name_meta} t ON m.id=t.message_id";
            $sql['where']  = $wpdb->prepare( "WHERE t.meta_key = 'last_updated' AND t.meta_value >= %d", $last_updated );
            // $sql['where']  = $wpdb->prepare( "WHERE t.meta_key='last_updated' AND t.meta_value >= %d AND m.sender_id=%d", $last_updated, $user_id );
            $new_ids = $wpdb->get_col( implode( ' ', $sql ) );

            if ( ! empty( $new_ids ) ) {

                $only_news_sql = 'AND m.thread_id IN (' . implode( ',', $new_ids) . ')';
                $pag_sql = '';

            }
        }

        // Условие для поиска

        if ( isset( $_POST['s'] ) ) {

            // Выбрать собеседников всех диалогов

            $recipients = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT r2.user_id FROM {$bp->messages->table_name_recipients} AS r1 INNER JOIN {$bp->messages->table_name_recipients} AS r2 ON r1.thread_id = r2.thread_id WHERE r1.user_id=%d AND r1.is_deleted=0 AND r2.user_id<>%d AND r2.is_deleted=0 ORDER BY r2.user_id", $user_id, $user_id ) );

            // Отобрать только тех, кто подходит по поиску

            $recipients_search_result = new BP_User_Query( array( 'include' => $recipients, 'search_terms' => sanitize_text_field( $_POST['s'] ) ) );

            if ( ! empty( $recipients_search_result->user_ids ) ) {
                
                // Выбрать номера диалогов пользователя и тех людей, которые подошли по поиску

                $resipients_ids = implode( ',', $recipients_search_result->user_ids );
                $threads_arr = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT r1.thread_id FROM {$bp->messages->table_name_recipients} AS r1 INNER JOIN {$bp->messages->table_name_recipients} AS r2 ON r1.thread_id = r2.thread_id WHERE r1.user_id=%d AND r1.is_deleted=0 AND r2.user_id IN ({$resipients_ids}) AND r2.is_deleted=0", $user_id ) );

                // Сформировать требование для поиска диалога

                $search_sql = ( $threads_arr ) ? "AND r.thread_id IN (" . implode( ',', $threads_arr ) . ")" : "AND 1=0";

            } else {

                $search_sql = "AND 1=0";

            }

        }


   		$sql = array();
		$sql['select'] = 'SELECT m.thread_id, MAX(m.date_sent) AS date_sent, MAX(m.id) AS message_id, r.unread_count';
		$sql['from']   = "FROM {$bp->messages->table_name_recipients} r INNER JOIN {$bp->messages->table_name_messages} m ON m.thread_id = r.thread_id";
		$sql['where']  = "WHERE r.is_deleted = 0 AND {$user_id_sql} {$only_news_sql} {$search_sql} AND m.id NOT IN (SELECT message_id FROM {$bp->messages->table_name_meta} WHERE meta_key = 'deleted' AND meta_value={$user_id})";
		$sql['misc']   = "GROUP BY m.thread_id ORDER BY date_sent DESC {$pag_sql}";

        $threads = $wpdb->get_results( implode( ' ', $sql ) );

        if ( empty( $threads) ) return array();

        $arr = array();
        $thread_ids = array();
        $message_ids = array();
        foreach ( (array) $threads as $thread ) {

            $thread_ids[] = (int) $thread->thread_id;
            $message_ids[] = (int) $thread->message_id;
            $arr[(int) $thread->thread_id]['date_sent'] = $thread->date_sent;
            $arr[(int) $thread->thread_id]['thread_id'] = $thread->thread_id;
            $arr[(int) $thread->thread_id]['unread_count'] = $thread->unread_count;

        }

        // Для каждого диалога из списка узнать получателей (кроме текущего пользователя)

        $where_sql = $wpdb->prepare( 'r.user_id <> (%d)', $user_id );

		$sql = array();
		$sql['select'] = 'SELECT r.thread_id, GROUP_CONCAT(DISTINCT r.user_id) AS user_ids';
		$sql['from']   = "FROM {$bp->messages->table_name_recipients} r";
		$sql['where']  = 'WHERE r.thread_id IN (' . implode( ',', $thread_ids ) . ') AND ' . $where_sql;
		$sql['misc']   = "GROUP BY r.thread_id";

        $threads = $wpdb->get_results( implode( ' ', $sql ) );

        foreach ( (array) $threads as $thread ) $arr[(int) $thread->thread_id]['user_ids'] = explode( ',', $thread->user_ids );
        
        // Для каждого диалога из списка узнать автора, тему и начало последнего сообщения

		$sql = array();
		$sql['select'] = 'SELECT thread_id, sender_id, subject, message';
		$sql['from']   = "FROM {$bp->messages->table_name_messages}";
		$sql['where']  = 'WHERE id IN (' . implode( ',', $message_ids ) . ')';

        $threads = $wpdb->get_results( implode( ' ', $sql ) );

        foreach ( (array) $threads as $thread ) {
            
            $arr[(int) $thread->thread_id]['message'] = $thread->message;
            $arr[(int) $thread->thread_id]['subject'] = $thread->subject;
            $arr[(int) $thread->thread_id]['sender_id'] = $thread->sender_id;

        }

        return apply_filters( 'mif_bpc_dialogues_get_threads_data', $arr, $page, $user_id );
    }



    //
    // Количество непрочитанных сообщений (фильтр)
    //

    function total_unread_messages_count( $count )
    {
        return $this->get_unread_count();
    }



    //
    // Получить количество непрочитанных сообщений
    //

    function get_unread_count( $user_id = NULL )
    {
        global $bp, $wpdb;
        
        if ( $user_id == NULL ) $user_id = bp_loggedin_user_id();

        $sql = $wpdb->prepare( "SELECT COUNT(DISTINCT r.thread_id) AS count FROM {$bp->messages->table_name_recipients} AS r INNER JOIN {$bp->messages->table_name_messages} AS m ON m.thread_id=r.thread_id WHERE user_id=%d  AND is_deleted=0 AND unread_count>0", $user_id );
        $count = $wpdb->get_var( $sql );

        return apply_filters( 'mif_bpc_dialogues_get_unread_messages_count', $count );
    }



    //
    // Получить ID последнего сообщения в диалоге
    //

    function get_last_message_id( $thread_id )
    {
        global $bp, $wpdb;

        $sql = $wpdb->prepare( "SELECT MAX(id) AS message_id FROM {$bp->messages->table_name_messages} WHERE thread_id = %d", $thread_id );
        $message_id = $wpdb->get_var( $sql );

        return apply_filters( 'mif_bpc_dialogues_get_last_message_id', $message_id, $thread_id );
    }



    //
    // Получить данные сообщений из диалога
    //

    function get_messages_data( $thread_id = NULL, $page = 0, $last_message_id = NULL )
    {
        if ( $thread_id == NULL ) return false;
        $user_id = bp_loggedin_user_id();

        global $bp, $wpdb;

        // Выбрать страницу сообщений or всё с последнего обновления?

        if ( $last_message_id == NULL ) {

            $where_sql = $wpdb->prepare( 'thread_id = %d', $thread_id );
            $pag_sql = $wpdb->prepare( "LIMIT %d, %d", intval( ( $page ) * $this->messages_on_page ), intval( $this->messages_on_page ) );

        } else {

            $where_sql = $wpdb->prepare( 'thread_id = %d AND id >= %d', $thread_id, $last_message_id );
            $pag_sql = '';

        }

		$sql = array();
		$sql['select'] = 'SELECT id, sender_id, subject, message, date_sent';
		$sql['from']   = "FROM {$bp->messages->table_name_messages}";
		$sql['where']  = "WHERE {$where_sql} AND id NOT IN (SELECT message_id FROM {$bp->messages->table_name_meta} AS mt INNER JOIN {$bp->messages->table_name_messages} AS ms ON mt.message_id = ms.id WHERE meta_key = 'deleted' AND meta_value={$user_id}  AND thread_id={$thread_id})";
        $sql['misc']   = "ORDER BY date_sent DESC {$pag_sql}";

        $messages = $wpdb->get_results( implode( ' ', $sql ) );

        $new_message_ids = $this->get_new_message_ids( $thread_id );
        foreach ( (array) $messages as $key => $message ) if ( in_array( $message->id, $new_message_ids) ) $messages[$key]->new = true;

        return apply_filters( 'mif_bpc_dialogues_get_messages_data', $messages, $thread_id, $page, $last_message_id );
    }



    // 
    // Получить номера новых для пользователя сообщений
    // 
    
    function get_new_message_ids( $thread_id = NULL, $user_id = NULL )
    {
        global $bp, $wpdb;
        
        if ( $thread_id == NULL ) return false;
        if ( $user_id == NULL ) $user_id = bp_loggedin_user_id();

        $sql = $wpdb->prepare( "SELECT unread_count FROM {$bp->messages->table_name_recipients} WHERE thread_id = %d AND user_id = %d", $thread_id, $user_id );
        $unread_count = (int) $wpdb->get_var( $sql );

        $arr = array();
        
        if ( $unread_count ) {

            $sql = $wpdb->prepare( "SELECT id FROM {$bp->messages->table_name_messages} WHERE thread_id = %d ORDER BY date_sent DESC LIMIT %d", $thread_id, $unread_count );
            $arr = $wpdb->get_col( $sql );

        }

        return apply_filters( 'mif_bpc_dialogues_get_new_message_ids', $arr, $thread_id, $user_id );

    }



    // 
    // Преобразует переводы строк в знаки абзаца
    // 
    
    function autop( $text )
    {
        $text = preg_replace( '/[\r|\n]+/', "\n", trim( $text ) );
        $text = preg_replace( '/\n/', '<p>', $text );
        return $text;
    }



    // 
    // Форматирует время сообщений
    // 
    
    function time_since( $time )
    {
        return mif_bpc_time_since( $time, true );
    }



    //
    // Проверить, что пользователь имеет право просматривать сообщения
    //

    function is_access( $thread_id = NULL, $user_id = NULL )
    {
        global $bp, $wpdb;

        if ( $thread_id == NULL ) return false;
        if ( $user_id == NULL ) $user_id = bp_loggedin_user_id();

        $sql = $wpdb->prepare( "SELECT user_id FROM {$bp->messages->table_name_recipients} WHERE thread_id = %d AND user_id = %d AND is_deleted=0", $thread_id, $user_id );
        $user_id = $wpdb->get_var( $sql );

        $res = ( isset( $user_id ) ) ? true : false; 

        return apply_filters( 'mif_bpc_dialogues_is_alone', $res, $thread_id, $user_id );
    }



    //
    // Проверить, что пользователь одинок
    //

    function is_alone( $thread_id = NULL, $user_id = NULL )
    {
        global $bp, $wpdb;

        if ( $thread_id == NULL ) return false;
        if ( $user_id == NULL ) $user_id = bp_loggedin_user_id();

        $sql = $wpdb->prepare( "SELECT user_id, is_deleted FROM {$bp->messages->table_name_recipients} WHERE thread_id = %d AND user_id <> %d", $thread_id, $user_id );
        $results = $wpdb->get_results( $sql );

        $present = false;
        foreach ( (array) $results as $result ) if ( $result->is_deleted == 0 ) $present = true;

        $msg = '';
        if ( ! $present && count( $results ) == 0 ) $msg = __( 'The companions were not found', 'mif-bpc' );
        if ( ! $present && count( $results ) == 1 ) $msg = __( 'User left the dialogue', 'mif-bpc' );
        if ( ! $present && count( $results ) > 1 ) $msg = __( 'All users have left the dialogue', 'mif-bpc' );

        return apply_filters( 'mif_bpc_dialogues_is_alone', $msg, $thread_id, $user_id );
    }



    //
    // Получить заголовок диалога
    //

    function get_messages_header( $thread_id = NULL, $user_id = NULL )
    {
        global $bp, $wpdb;

        if ( $thread_id == NULL ) return false;
        if ( $user_id == NULL ) $user_id = bp_loggedin_user_id();

        // Проверка прав пользователя на просмотр этих сообщений

        if ( ! $this->is_access( $thread_id ) ) return false;

        $where_sql = $wpdb->prepare( 'm.thread_id = %d AND r.user_id <> (%d)', $thread_id, $user_id );

		$sql = array();
		$sql['select'] = 'SELECT m.sender_id, GROUP_CONCAT(DISTINCT r.user_id) AS user_ids';
		$sql['from']   = "FROM {$bp->messages->table_name_recipients} r INNER JOIN {$bp->messages->table_name_messages} m ON m.thread_id = r.thread_id";
		$sql['where']  = "WHERE {$where_sql}";
		$sql['misc']   = "GROUP BY m.thread_id ORDER BY date_sent DESC";

        $thread_objects = $wpdb->get_results( implode( ' ', $sql ) );

        $thread['sender_id'] = $thread_objects[0]->sender_id;
        $thread['user_ids'] = explode( ',', $thread_objects[0]->user_ids );

        $thread_title = $this->get_thread_title( $thread, true );
        $header = '<span class="title">' . $thread_title . '</span>';

        if ( count( $thread['user_ids'] ) == 1 ) {

            $user_id = $thread['user_ids'][0];
            if ( $user_id ) {

                $last_activity = bp_get_last_activity( $user_id );
                $header .= ' <span class="time-since">' . $last_activity . '</span>';

            }

        }

        return apply_filters( 'mif_bpc_dialogues_get_messages_header', $header, $thread_title, $avatar, $thread_id, $user_id );
    }



    //
    // Получить идентификатор диалога для нового сообщения
    //

    function get_thread_id( $recipient_ids = array(), $sender_id = NULL )
    {
        global $bp, $wpdb;
        
        if ( $recipient_ids === array() ) return false;
        if ( $sender_id == NULL ) $sender_id = bp_loggedin_user_id();

        // Если получатель только один, то пытаться найти с ним диалог
        
        $thread_id = false;

        if ( count( $recipient_ids ) == 1 ) {

            $recipient_id = $recipient_ids[0];
            if ( $recipient_id == $sender_id ) return false;

            // Получить идентификатор активного диалога

            $sql = $wpdb->prepare( "SELECT thread_id FROM {$bp->messages->table_name_recipients} WHERE thread_id IN (SELECT DISTINCT r1.thread_id FROM {$bp->messages->table_name_recipients} AS r1 INNER JOIN `wp_bp_messages_recipients` AS r2 ON r1.thread_id = r2.thread_id WHERE r1.user_id=%d AND r2.user_id=%d AND r1.is_deleted=0 AND r2.is_deleted=0) GROUP BY thread_id HAVING count(DISTINCT user_id)=2 ORDER BY thread_id DESC LIMIT 1", $sender_id, $recipient_id );

            $thread_id = $wpdb->get_var( $sql );

        } 
        
        // Если активного диалога нет, то создать новый

        if ( empty( $thread_id ) ) {

            $thread_id = (int) $wpdb->get_var( "SELECT MAX(thread_id) FROM {$bp->messages->table_name_recipients}" ) + 1;

            $sql = $wpdb->prepare( "INSERT INTO {$bp->messages->table_name_recipients} ( thread_id, user_id, sender_only ) VALUES ( %d, %d, 1 )", $thread_id, $sender_id );
            if ( ! $wpdb->query( $sql ) ) return false;
            
            foreach ( (array) $recipient_ids as $recipient_id ) {

                $sql = $wpdb->prepare( "INSERT INTO {$bp->messages->table_name_recipients} ( thread_id, user_id, unread_count ) VALUES ( %d, %d, 0 )", $thread_id, $recipient_id );
                $wpdb->query( $sql );
            
            }

        }

        return apply_filters( 'mif_bpc_dialogues_get_get_thread_id', $thread_id, $recipient_ids, $sender_id );
    }



    //
    // Send сообщение
    //

    function send( $message, $thread_id = NULL, $sender_id = NULL, $subject = 'default', $email_status = 'no' )
    {
        global $bp, $wpdb;
        if ( $thread_id == NULL ) return false;
        if ( $sender_id == NULL ) $sender_id = bp_loggedin_user_id();

        // Получить последнее сообщение в диалоге

        $sql = $wpdb->prepare( "SELECT * FROM {$bp->messages->table_name_messages} WHERE thread_id = %d ORDER BY date_sent DESC LIMIT 1", $thread_id );
        $result = $wpdb->get_row( $sql );
        $message_id = $result->id;

        // Обновлять существующую, or добавлять новую?
        
        $update_flag = false;
        
        if ( $result && $result->sender_id == $sender_id ) {

            $last_updated = bp_messages_get_meta( $message_id, 'last_updated' );
            $deleted = bp_messages_get_meta( $message_id, 'deleted' );
            $outdate_time = apply_filters( 'mif_bpc_dialogues_outdate_time', $this->message_outdate_time );
        
            if ( isset( $last_updated ) && empty( $deleted ) && timestamp_to_now( $last_updated ) < $outdate_time ) $update_flag = true;

        }

        // Save в базе новое сообщение

        if ( $update_flag ) {

            // Update существующую
            $message = $result->message . "\n" . $message;
            $sql = $wpdb->prepare( "UPDATE {$bp->messages->table_name_messages} SET message = %s WHERE id = %d", $message, $message_id );
            if ( ! $wpdb->query( $sql ) ) return false;

        } else {

            // Add new
            $date_sent = bp_core_current_time();
            $sql = $wpdb->prepare( "INSERT INTO {$bp->messages->table_name_messages} ( thread_id, sender_id, subject, message, date_sent ) VALUES ( %d, %d, %s, %s, %s )", $thread_id, $sender_id, $subject, $message, $date_sent );
            if ( ! $wpdb->query( $sql ) ) return false;

            $message_id = $wpdb->get_var( "SELECT LAST_INSERT_ID()" );

        }

        // Update метку последнего обновления

        $now = time();
        bp_messages_update_meta( $message_id, 'last_updated', $now );

        // Update для других пользователей информацию о непрочитанных

        if ( $update_flag ) {

            $sql = $wpdb->prepare( "UPDATE {$bp->messages->table_name_recipients} SET unread_count = 1 WHERE unread_count = 0 AND thread_id = %d AND user_id <> %d", $thread_id, $sender_id );
            $wpdb->query( $sql );

        } else {

            $sql = $wpdb->prepare( "UPDATE {$bp->messages->table_name_recipients} SET unread_count = unread_count + 1 WHERE thread_id = %d AND user_id <> %d", $thread_id, $sender_id );
            $wpdb->query( $sql );

        }

        // Отметить для себя, что всё прочитано

        $ret = $this->mark_as_read( $thread_id, $sender_id );

        // Узнать id получателей сообщения и отправить им уведомление (локальное уведомление, эхо-сервер, почта or др.)

        $recipients = $this->get_recipients_of_thread( $thread_id, $sender_id );
        do_action( 'mif_bpc_dialogues_after_send', $recipients, $thread_id, $sender_id, $message, $email_status );

        return apply_filters( 'mif_bpc_dialogues_send', $message_id, $recipients, $thread_id, $sender_id, $message, $email_status, $ret );
    }




    //
    // ID участников диалога (кроме отправителя, если он указан )
    //

    function get_recipients_of_thread( $thread_id, $sender_id = NULL )
    {
        global $bp, $wpdb;

        if ( isset( $sender_id ) ) {
            $sql = $wpdb->prepare( "SELECT user_id FROM {$bp->messages->table_name_recipients} WHERE thread_id = %d AND user_id <> %d", $thread_id, $sender_id );
        } else {
            $sql = $wpdb->prepare( "SELECT user_id FROM {$bp->messages->table_name_recipients} WHERE thread_id = %d", $thread_id );
        }

        $recipients = $wpdb->get_col( $sql );

        return apply_filters( 'mif_bpc_dialogues_get_recipients_of_thread', $recipients, $thread_id, $sender_id );
    }



    //
    // Отметить диалог как прочитанный
    //

    function mark_as_read( $thread_id = NULL, $user_id = NULL )
    {
        global $bp, $wpdb;

        if ( $thread_id == NULL ) return;
        if ( $user_id == NULL ) $user_id = bp_loggedin_user_id();

        $sql = $wpdb->prepare( "UPDATE {$bp->messages->table_name_recipients} SET unread_count = 0 WHERE thread_id = %d AND user_id = %d", $thread_id, $user_id );
        $ret = $wpdb->query( $sql );

        return apply_filters( 'mif_bpc_dialogues_mark_as_read', $ret );
    }



    //
    // Delete диалог
    //

    function delete_thread( $thread_id = NULL, $user_id = NULL )
    {
        global $bp, $wpdb;

        if ( $thread_id == NULL ) return;
        if ( $user_id == NULL ) $user_id = bp_loggedin_user_id();

        // Отметить как удаленный
       
        $sql = $wpdb->prepare( "UPDATE {$bp->messages->table_name_recipients} SET is_deleted=1 WHERE thread_id=%d AND user_id=%d", $thread_id, $user_id );
        $ret = $wpdb->query( $sql );

        // Получить список всех активных пользователей диалога

        $sql = $wpdb->prepare( "SELECT user_id FROM {$bp->messages->table_name_recipients} WHERE thread_id=%d AND is_deleted=0", $thread_id );
        $user_ids = $wpdb->get_col( $sql );

        // Группировать личные диалоги после удаления пользователя (склеивание двух новых с удаленным пользователем)
        // Это действие логично выполнять только тогда, когда у всех пользователей диалоги группированы всегда и по умолчанию

        // $sql = $wpdb->prepare( "SELECT user_id FROM {$bp->messages->table_name_recipients} WHERE thread_id=%d", $thread_id );
        // $all_user_ids = $wpdb->get_col( $sql );
        // if ( count( $all_user_ids ) == 2 && count( $user_ids ) == 1 ) $this->threads_joining( $user_ids[0] );

        // Удалять совсем, если активных пользователей у диалога не осталось

        if ( count( $user_ids ) === 0 ) {

            $sql = $wpdb->prepare( "DELETE FROM {$bp->messages->table_name_recipients} WHERE thread_id=%d", $thread_id );
            $ret2 = $wpdb->query( $sql );

            $sql = $wpdb->prepare( "DELETE FROM {$bp->messages->table_name_messages} WHERE thread_id=%d", $thread_id );
            $ret3 = $wpdb->query( $sql );

        }

        return apply_filters( 'mif_bpc_dialogues_delete_thread', $ret, $user_ids, $all_user_ids, $ret2, $ret3 );
    }



    //
    // Уточнение доступа к прикрепленным документам
    //

    function access_to_attachment( $ret, $item, $level )
    {
        global $bp, $wpdb;

        $sql = $wpdb->prepare( "SELECT DISTINCT m.thread_id FROM {$bp->messages->table_name_messages} m INNER JOIN {$bp->messages->table_name_meta} t ON m.id=t.message_id WHERE meta_key=%s AND meta_value=%d LIMIT 1", $this->message_attachment_meta_key, $item->ID );
        $thread_id = $wpdb->get_var( $sql );
        
        if ( empty( $thread_id ) ) return $ret;

        $sql = $wpdb->prepare( "SELECT DISTINCT user_id FROM {$bp->messages->table_name_recipients} WHERE thread_id = %d", $thread_id );
        $user_ids = $wpdb->get_col( $sql );

        $ret = ( in_array( bp_loggedin_user_id(), $user_ids ) ) ? true : $ret;

        return apply_filters( 'mif_bpc_dialogues_access_to_attachment', $ret, $item, $level );
    }



    //
    // Склеивание диалогов с одинаковыми пользователями в один диалог
    //

    function threads_joining( $user_id = NULL )
    {
        global $bp, $wpdb;

        if ( $user_id == NULL ) $user_id = bp_loggedin_user_id();

        // Выбрать ID всех диалогов пользователя
        
        $sql = $wpdb->prepare( "SELECT thread_id FROM {$bp->messages->table_name_recipients} WHERE user_id = %d AND is_deleted = 0", $user_id );
        $threads_ids = $wpdb->get_col( $sql );

        $arr = array();
        foreach ( (array) $threads_ids as $thread_id ) {

            // Для каждого диалога - получить список собеседников пользователя
            
            $sql = $wpdb->prepare( "SELECT DISTINCT user_id, is_deleted FROM {$bp->messages->table_name_recipients} WHERE thread_id = %d AND user_id <> %d", $thread_id, $user_id );
            $user_ids = $wpdb->get_results( $sql );
            
            // Если собеседник только один, то запомнить номер диалога

            if ( count( $user_ids ) == 1 ) {
                
                $key = $user_ids[0]->user_id . ':' . $user_ids[0]->is_deleted;
                $arr[$key][] = $thread_id;

            }
            
        }

        $ret = __( 'All dialogues are already grouped', 'mif-bpc' );

        foreach ( (array) $arr as $threads_arr ) {

            // Если с собеседником диалог только один, то идти дальше
            if ( count( $threads_arr ) == 1 ) continue;

            $thread_id = array_pop( $threads_arr );
            $threads_list = implode( ',', $threads_arr );

            // Update номера диалогов в таблице сообщений

            $sql = $wpdb->prepare( "UPDATE {$bp->messages->table_name_messages} SET thread_id = %d WHERE thread_id IN ({$threads_list})", $thread_id );
            if ( $wpdb->query( $sql ) ) {

                // Если обновление прошло успешно, то удалить старые номера диалогов в таблице диалогов
                $sql = "DELETE FROM {$bp->messages->table_name_recipients} WHERE thread_id IN ({$threads_list})";
                $ret = __( 'Grouping completed successfully', 'mif-bpc' );
                $ret2 = $wpdb->query( $sql );

                
            } else {

                $ret = false;

            }

        }

        return apply_filters( 'mif_bpc_dialogues_threads_joining', $ret, $user_id, $user_ids, $ret2 );
    }



    //
    // Address страницы диалогов
    //

    function get_dialogues_url()
    {
        global $bp;
        $url = $bp->displayed_user->domain . $bp->messages->slug . '/dialogues/';

        return apply_filters( 'mif_bpc_dialogues_get_dialogues_url', $url );
    }

}


?>