<?php

//
// Documents
// 
//


defined( 'ABSPATH' ) || exit;

if ( mif_bpc_options( 'docs' ) ) {

    global $mif_bpc_docs;
    $mif_bpc_docs = new mif_bpc_docs();

}


class mif_bpc_docs extends mif_bpc_docs_screen {



    function __construct()
    {

        parent::__construct();

        // Configuration страницы документов
        add_action( 'bp_activity_setup_nav', array( $this, 'nav' ) );
        add_action( 'bp_screens', array( $this, 'doc_page' ) );

        // Configuration типа записи
        add_action( 'bp_init', array( $this, 'create_post_type' ) );

        // Скачивание файла
        add_action( 'bp_init', array( $this, 'force_download' ) );

        // Помощник удаления файлов
        add_action( 'before_delete_post', array( $this, 'delete_doc_helper' ) );

        // Возвращает сведения о прикрепленном документе
        add_action( 'mif_bpc_get_attachments_data', array( $this, 'attachments_data' ), 10, 2 );

        // Функции ajax-запросов
        global $mif_bpc_docs_ajax;
        $mif_bpc_docs_ajax = new mif_bpc_docs_ajax();

        // Размещение документов в ленте активности
        global $mif_bpc_docs_activity;
        $mif_bpc_docs_activity = new mif_bpc_docs_activity();

        // Прикрепленные файлы в диалогах
        if ( mif_bpc_options( 'dialogues' ) ) {

            global $mif_bpc_docs_dialogues;
            $mif_bpc_docs_dialogues = new mif_bpc_docs_dialogues();

        }

        // Инструменты администратора
        global $mif_bpc_docs_admin;
        $mif_bpc_docs_admin = new mif_bpc_docs_admin();
       
    }



    // 
    // Страница документов
    // 

    function nav()
    {
        global $bp, $mif_bpc_docs;

        // $url = $bp->displayed_user->domain . $this->slug . '/';
        // $parent_slug = $bp->messages->slug;
        $url = trailingslashit( $this->get_docs_url() );
        $data = $mif_bpc_docs->get_all_folders_size();

        bp_core_new_nav_item( array(  
                'name' => __( 'Documents', 'mif-bpc' ) . ' <span>' . $data['count'] . '</span>',
                'slug' => $this->slug,
                'position' => 90,
                'show_for_displayed_user' => true,
                // 'screen_function' => array( $this, 'screen' ), 
                'default_subnav_slug' => 'folder',
                // 'item_css_id' => $this->slug
            ) );

        bp_core_new_subnav_item( array(  
                'name' => __( 'Folders', 'mif-bpc' ),
                'slug' => 'folder',
                'parent_url' => $url, 
                'parent_slug' => $this->slug, 
                'screen_function' => array( $this, 'screen' ), 
                'position' => 10,
                // 'user_has_access'=>  bp_is_my_profile() 
            ) );

        bp_core_new_subnav_item( array(  
                'name' => __( 'Create folder', 'mif-bpc' ),
                'slug' => 'new-folder',
                'parent_url' => $url, 
                'parent_slug' => $this->slug, 
                'screen_function' => array( $this, 'screen' ), 
                'position' => 20,
                // 'user_has_access'=>  bp_is_my_profile() 
                'user_has_access'=> $this->is_access( 'all-folders', 'write' ), 
            ) );

        bp_core_new_subnav_item( array(  
                'name' => __( 'Statistics', 'mif-bpc' ),
                'slug' => 'stat',
                'parent_url' => $url, 
                'parent_slug' => $this->slug, 
                'screen_function' => array( $this, 'screen' ), 
                'position' => 30,
                'user_has_access'=>  bp_is_my_profile() 
            ) );

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
        $tpl_file = 'docs-page.php';

        if ( $template = locate_template( $tpl_file ) ) {
            load_template( $template, false );
        } else {
            load_template( dirname( __FILE__ ) . '/../templates/' . $tpl_file, false );
        }
    }



    // 
    // Инициализация страницы просмотра отдельного документа
    // 

    function doc_page()
    {
        if ( bp_current_component() != 'docs' || ! is_numeric( bp_current_action() ) ) return false;

        // bp_core_load_template( 'members/docs-page-doc' );
    	global $wp_query;

        $tpl_file = 'docs-page-doc.php';

        status_header( 200 );
		$wp_query->is_page     = true;
		$wp_query->is_singular = true;
		$wp_query->is_404      = false;

        if ( $template = locate_template( $tpl_file ) ) {
            load_template( $template, false );
        } else {
            load_template( dirname( __FILE__ ) . '/../templates/' . $tpl_file, false );
        }

        exit();
    }


}






?>