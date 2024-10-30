<?php

//
// Dialogues
// 
//


defined( 'ABSPATH' ) || exit;

if ( mif_bpc_options( 'dialogues' ) ) {

    global $mif_bpc_dialogues;
    $mif_bpc_dialogues = new mif_bpc_dialogues();

}


class mif_bpc_dialogues extends mif_bpc_dialogues_screen {

    //
    // Простые и удобные диалоги вместо стандартной системы сообщений
    //


    function __construct()
    {
        parent::__construct();       

        // Configuration страницы диалогов
        add_action( 'bp_init', array( $this, 'dialogues_nav' ) );
        add_action( 'bp_screens', array( $this, 'compose_screen' ) );
        add_filter( 'messages_template_view_message', array( $this, 'view_screen' ) );
        add_filter( 'bp_get_total_unread_messages_count', array( $this, 'total_unread_messages_count' ) );
        add_filter( 'bp_get_send_private_message_link', array( $this, 'message_link' ) );

        // Обработка текста сообщений
        add_filter( 'mif_bpc_dialogues_message_item_message', array( $this, 'autop' ) );
        add_filter( 'mif_bpc_dialogues_message_item_message', 'stripslashes_deep' );

        // Стандартные фильтры обработки текста сообщений
        // add_filter( 'mif_bpc_dialogues_message_item_message', 'wp_filter_kses', 1 );
        // add_filter( 'mif_bpc_dialogues_message_item_message', 'force_balance_tags', 1 );
        // add_filter( 'mif_bpc_dialogues_message_item_message', 'wptexturize' );
        // add_filter( 'mif_bpc_dialogues_message_item_message', 'convert_chars' );
        // add_filter( 'mif_bpc_dialogues_message_item_message', 'wpautop' );

        add_filter( 'mif_bpc_docs_dialogues_doc_access', array( $this, 'access_to_attachment' ), 10, 3 );

                    

        // Функции ajax-запросов
        global $mif_bpc_dialogues_ajax;
        $mif_bpc_dialogues_ajax = new mif_bpc_dialogues_ajax();
    }



    // 
    // Configuration страницы прямого создания сообщения
    // 

    function compose_screen()
    {
        if ( ! bp_is_messages_component() || ! bp_is_current_action( 'compose' ) ) return false;
        $this->screen();
    }



    // 
    // Configuration кнопки прямого создания сообщения
    // 

    function message_link()
    {
        return bp_loggedin_user_domain() . bp_get_messages_slug() . '/compose/' . bp_core_get_username( bp_displayed_user_id() );
    }



    // 
    // Configuration страницы прямого просмотра диалога
    // 

    function view_screen( $template )
    {
        if ( $template == 'members/single/home' ) {

            $template = 'members/single/plugins';
            add_action( 'bp_template_content', array( $this, 'body' ) );

        }

        return apply_filters( 'mif_bpc_dialogues_view_screen', $template );
    }



    // 
    // Уточнение меню вкладки сообщений
    // 

    function dialogues_nav()
    {
        global $bp;

        bp_core_remove_subnav_item( 'messages', 'compose' );
        bp_core_remove_subnav_item( 'messages', 'compose' );
        bp_core_remove_subnav_item( 'messages', 'starred' );
        bp_core_remove_subnav_item( 'messages', 'sentbox' );
        bp_core_remove_subnav_item( 'messages', 'inbox' );
        // bp_core_remove_subnav_item( 'messages', 'view' );

        $parent_url = $bp->displayed_user->domain . $bp->messages->slug . '/';
        $parent_slug = $bp->messages->slug;

        $sub_nav = array(  
                'name' => __( 'Dialogues', 'mif-bpc' ), 
                'slug' => 'inbox', 
                'parent_url' => $parent_url, 
                'parent_slug' => $parent_slug, 
                'screen_function' => array( $this, 'screen' ), 
                'position' => 10,
                'user_has_access'=>  bp_is_my_profile() 
            );

        bp_core_new_subnav_item( $sub_nav );

    }



    //
    // Содержимое страниц
    //

    function screen()
    {
        add_action( 'bp_template_content', array( $this, 'body' ) );
        bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
    }

    function body()
    {
        if ( $template = locate_template( 'dialogues-page.php' ) ) {
           
            load_template( $template, false );

        } else {

            load_template( dirname( __FILE__ ) . '/../templates/dialogues-page.php', false );

        }
    }
    

}

?>