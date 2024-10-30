<?php

//
// Configuration всплывающих уведомлений
// 
//


defined( 'ABSPATH' ) || exit;


if ( mif_bpc_options( 'websocket' ) ) {

    global $mif_bpc_websocket;
    $mif_bpc_websocket = new mif_bpc_websocket();

}


class mif_bpc_websocket {

    //
    // Механизм всплывающих уведомлений - есть эхо-сервер, с которым устанавливают websocket-соединения браузеры клиентов. Каждый клиент
    // подключается к своему каналу, ассоциированному с его учетной записью. Когда на сайте появляется новое уведомление (or исчезают 
    // старые) - вордпресс дает об этом знать эхо-серверу, сообщая идентификатор канала нужного пользователя. Эхо-сервер тот шлет уведомление 
    // о событии через websocket-соединение. Bраузеры, получив событие, делают ajax-запрос для получения информации о новых уведомлениях.
    //
    // Требуется настройка эхо-сервера (node.js и socket.io) на машине с вордпрессом or где-то еще. Для лучшей безопасности нужно указать
    // общий ключ для вордпресса и эхо-сервера, а также на эхо-сервере указать ip-адрес водрпресса, который может делать рассылку уведомлений.
    // 
    // Для настройки используйте wp-config.php:
    // 
    // define( 'ECHOSERVER_PORT', ... );
    // define( 'ECHOSERVER_SECURE_KEY', '...' );
    // define( 'ECHOSERVER_NOTIFICATION_DELAY', ... );
    // 


    //
    // Порт эхо-сервера
    //

    public $port = 8080;

    //
    // Секретный ключ эхо-сервера
    //

    public $secure_key = 'A4nYoRiq0dispfmCkFzfUAtAnV6wBglC';

    //
    // Время задержки между всплывающими сообщениями (секунды)
    //
    
    public $notification_delay = 3;
    
    //
    // Использовать ли звуковое сопровождение уведомлений
    //

    public $default_notify = 'no';


    function __construct()
    {
       
        // Отправка уведомлений клиентам

        add_action( 'bp_notification_before_update', array( $this, 'notification_before_update' ), 10, 2 );
        add_action( 'bp_notification_before_delete', array( $this, 'notification_before_delete' ) );
        add_action( 'bp_notification_before_save', array( $this, 'notification_before_save' ) );

        // Обновление и уведомления дислогов

        add_action( 'mif_bpc_dialogues_after_send', array( $this, 'dialogues_after_send' ) );
        add_action( 'mif_bpc_dialogues_write_notification', array( $this, 'dialogues_write_notification' ), 10, 3 );

        // JS-скрипты для связки "браузер - эхо-сервер"

        add_action( 'wp_print_scripts', array( $this, 'load_js_helper' ) );            				
        add_action( 'wp_head', array( $this, 'add_js_param' ) );

    }


    // 
    // Выяснить ID пользователя при изменении состояния уведомлений и отправить уведомление клиенту по поводу обновления уведомлений
    // 

    function notification_before_update( $data1, $data2 )
    {
        if ( empty( $data2['id'] ) ) return;
        $notification = bp_notifications_get_notification( $data2['id'] );
        $this->send_notification( $notification->user_id );
    }

    function notification_before_delete( $data )
    {
        $notification = bp_notifications_get_notification( $data['id'] );
        $this->send_notification( $notification->user_id );
    }

    function notification_before_save( $data )
    {
        $this->send_notification( $data->user_id, $this->default_notify );
    }



    // 
    // Отправляет уведомление клиенту о новом уведомлении
    // 

    function send_notification( $user_id = NULL, $default_notify = 'no' )
    {
        if ( $user_id == NULL ) $user_id = bp_loggedin_user_id();
        
        // $url = $this->get_local_url();
        // $port = $this->get_port();

        $notify = ( bp_loggedin_user_id() == $user_id ) ? 'no' : $default_notify;

        $last_notification = (int) get_user_meta( $user_id, 'last_notification_timestamp', true );
        $now = time();

        // Выйти, если уведомление уже запланировано

        if ( $last_notification > $now ) return;

        // Рассчитать время задержки
        
        $min_delay = $this->get_notification_delay();
        $delay = ( $now > $last_notification + $min_delay ) ? 0 : $last_notification + $min_delay - $now;

        $last_notification = $now + $delay;
        update_user_meta( $user_id, 'last_notification_timestamp', $last_notification );

        $delay = ( $delay + 1 ) * 1000;

        $args = array(
                    'room' => $this->get_user_room( $user_id ),
                    'event' => 'float_notification_update',
                    'data' => '1',
                    'notify' => $notify,
                    'delay' => $delay,
                    // 'key' => $this->get_secure_key(),
                );

        $this->curl_echo_server( $args );

    }



    // 
    // Отправляет уведомление клиенту о новом сообщении
    // 

    function dialogues_after_send( $recipients = NULL, $default_notify = 'no'  )
    {
        if ( $recipients == NULL ) return;

        $sender_id = bp_loggedin_user_id();

        foreach ( (array) $recipients as $user_id ) {

            if ( $user_id == $sender_id ) continue;

            $notify = ( bp_loggedin_user_id() == $user_id ) ? 'no' : $default_notify;

            $args = array(
                        'room' => $this->get_user_room( $user_id ),
                        'event' => 'dialogues_update',
                        'data' => '1',
                        'notify' => $notify,
                    );

            $this->curl_echo_server( $args );

        }
        
    }



    // 
    // Отправляет уведомление клиенту о том, что пользователь вводит сообщение
    // 

    function dialogues_write_notification( $thread_id = NULL, $recipients = NULL, $sender_id = NULL )
    {
        if ( $thread_id == NULL ) return;
        if ( $recipients == NULL ) return;
        if ( $sender_id == NULL ) $sender_id = bp_loggedin_user_id();

        foreach ( (array) $recipients as $user_id ) {

            if ( $user_id == $sender_id ) continue;

            $args = array(
                        'room' => $this->get_user_room( $user_id ),
                        'event' => 'dialogues_write',
                        'thread_id' => $thread_id,
                        'sender_id' => $sender_id,
                    );

            $this->curl_echo_server( $args );

        }
        
    }



    //
    // Передает сообщение эхо-серверу, чтобы тот сообщил это клиентам
    //

    function curl_echo_server( $args )
    {
        $url = $this->get_local_url();
        $port = $this->get_port();

        $args['key'] = $this->get_secure_key();

        try {

            $conn = curl_init();
        	curl_setopt( $conn, CURLOPT_URL, $url . ':' . $port . '?' . http_build_query( $args ) );
        	curl_setopt( $conn, CURLOPT_NOBODY, 1 );
        	curl_exec( $conn );
        	curl_close( $conn );

            $ret = true;

        } catch ( Exception $e ) {

            // Сообщение о том, что эхо-сервер не работает

            do_action( 'mif_bpc_echo_server_not_worked' );

            $ret = false;

        };

        return $ret;
    }



    //
    // Options подключения клиента к эхо-серверу
    //

    function add_js_param()
    {
        if ( ! is_user_logged_in() ) return;

        $url = $this->get_url();
        $port = $this->get_port();
        $room = $this->get_user_room();

        $out = '<script>websocket_param = { url: "' . $url . '", port: ' . $port . ', room: "' . $room . '" }</script>';
        
        echo $out;
    }


    //
    // JS-помощник и библиотека для связки "браузер - эхо-сервер"
    //

    function load_js_helper()
    {
        if ( ! is_user_logged_in() ) return;

        wp_enqueue_script( 'mif_bpc_websocket', plugins_url( '../js/websocket.js', __FILE__ ), array( 'mif_bpc_socket_io' ) );
        wp_enqueue_script( 'mif_bpc_socket_io', plugins_url( '../js/socket.io/socket.io.min.js', __FILE__ ) );

    }


    //
    // Получить адрес эхо-сервера для клиента
    //

    function get_url()
    {
        return apply_filters( 'mif_bpc_websocket_url', get_option( 'mif_bpc_websocket_url', home_url() ) );
    }


    //
    // Получить адрес эхо-сервера для локальной машины
    //

    function get_local_url()
    {
        return apply_filters( 'mif_bpc_websocket_url', get_option( 'mif_bpc_websocket_local_url', 'http://localhost' ) );
    }


    //
    // Получить порт, на котором работает эхо-сервер
    //

    function get_port()
    {
        $port = ( defined( 'ECHOSERVER_PORT' ) ) ? ECHOSERVER_PORT : $this->port;
        return apply_filters( 'mif_bpc_websocket_port', $port );
    }


    //
    // Получить секретный ключ для эхо-сервера
    //

    function get_secure_key()
    {
        $secure_key = ( defined( 'ECHOSERVER_SECURE_KEY' ) ) ? ECHOSERVER_SECURE_KEY : $this->secure_key;
        return apply_filters( 'mif_bpc_websocket_secure_key', $secure_key );
    }


    //
    // Получить время задержки между всплывающими сообщениями
    //

    function get_notification_delay()
    {
        $notification_delay = ( defined( 'ECHOSERVER_NOTIFICATION_DELAY' ) ) ? ECHOSERVER_NOTIFICATION_DELAY : $this->notification_delay;
        return apply_filters( 'mif_bpc_websocket_notification_delay', $notification_delay );
    }


    //
    // Получить имя комнаты (канала) для пользователя
    //

    function get_user_room( $user_id = NULL )
    {
        if ( $user_id == NULL ) $user_id = bp_loggedin_user_id();
        $user = get_user_by( 'id', $user_id );
        return apply_filters( 'mif_bpc_websocket_room', wp_hash( $user->user_login . '-' . $user->user_pass ) );
    }


}


?>