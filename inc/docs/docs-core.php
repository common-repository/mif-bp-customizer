<?php

//
// Documents (параметры и методы ядра)
// 
//



defined( 'ABSPATH' ) || exit;


abstract class mif_bpc_docs_core {

    //
    // Folders на одной странице
    //

    public $folders_on_page = 12;

    //
    // Documents на одной странице
    //

    public $docs_on_page = 18;

    //
    // Ярлык системы документов
    //

    public $slug = 'docs';

    //
    // Name папки с документами в uploads
    //

    public $path = 'docs';

    //
    // Name папки по умолчанию
    //

    public $default_folder_name = 'New folder';

    //
    // Name папки ленты активности
    //

    public $activity_stream_folder_name = 'Activity Stream';

    //
    // Name папки диалогов
    //

    public $dialogues_folder_name = 'Private Messages';

    //
    // Мета-ключ родительского объекта папки
    //

    public $folder_parent_meta_key = 'mif-bpc-folder-parent';

    //
    // Мета-ключ настроек параметров доступа к папке
    //

    public $folder_access_mode_meta_key = 'mif-bpc-folder-access-mode';

    //
    // Мета-ключ настроек системы документов в группе
    //

    public $group_access_mode_meta_key = 'mif-bpc-group-access-mode';

    //
    // Мета-ключ папки ленты активности
    //

    public $activity_folder_meta_key = 'mif-bpc-activity-folder';

    //
    // Мета-ключ папки прикрепленных файлов в диалогах
    //

    public $dialogues_folder_meta_key = 'mif-bpc-dialogues-folder';
    
    //
    // Шаблон для определения файла-обложки
    //

    private $cover_pattern = '/cover[\d]*\.(png|jpg|gif)/';



    function __construct()
    {

        // // Configuration типа записи
        // add_action( 'bp_init', array( $this, 'create_post_type' ) );

        // // Скачивание файла
        // add_action( 'bp_init', array( $this, 'force_download' ) );

        // // Помощник удаления файлов
        // add_action( 'before_delete_post', array( $this, 'delete_doc_helper' ) );

        $this->default_folder_name = __( 'New folder', 'mif-bpc' );
        $this->activity_stream_folder_name = __( 'Activity feed', 'mif-bpc' );
        $this->dialogues_folder_name = __( 'Private messages', 'mif-bpc' );
    }



    // 
    // Создание типов записей
    // 

    function create_post_type()
    {
        // Тип записей - документ

        register_post_type( 'mif-bpc-doc',
            array(
                'labels' => array(
                    'name' => __( 'Documents', 'mif-bpc' ),
                    'singular_name' => __( 'Document', 'mif-bpc' ),
                    'add_new' => __( 'Add new', 'mif-bpc' ),
                    'add_new_item' => __( 'New document', 'mif-bpc' ),
                    'edit' => __( 'Edit', 'mif-bpc' ),
                    'edit_item' => __( 'Edit document', 'mif-bpc' ),
                    'new_item' => __( 'New document', 'mif-bpc' ),
                    'view' => __( 'Viewing', 'mif-bpc' ),
                    'view_item' => __( 'Document viewing', 'mif-bpc' ),
                    'search_items' => __( 'Find a document', 'mif-bpc' ),
                    'not_found' => __( 'Documents were not found', 'mif-bpc' ),
                    'not_found_in_trash' => __( 'Not found in the Recycle Bin', 'mif-bpc' ),
                    'parent' => __( 'Folder', 'mif-bpc' ),
                ),
                'public' => false,
                'menu_position' => 15,
                'supports' => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
                'taxonomies' => array( 'mif-bpc-doc-folder-tax' ),
                'menu_icon' => 'dashicons-paperclip',
                'has_archive' => true,
                'rewrite' => array( 'slug' => $this->slug, 'with_front' => false ),                
            )
        );

        // Тип записей - папка

        register_post_type( 'mif-bpc-folder',
            array(
                'labels' => array(
                    'name' => __( 'Folders', 'mif-bpc' ),
                    'singular_name' => __( 'Folder', 'mif-bpc' ),
                    'add_new' => __( 'Add new', 'mif-bpc' ),
                    'add_new_item' => __( 'New folder', 'mif-bpc' ),
                    'edit' => __( 'Edit', 'mif-bpc' ),
                    'edit_item' => __( 'Edit folder', 'mif-bpc' ),
                    'new_item' => __( 'New folder', 'mif-bpc' ),
                    'view' => __( 'Viewing', 'mif-bpc' ),
                    'view_item' => __( 'Folder viewing', 'mif-bpc' ),
                    'search_items' => __( 'Find a folder', 'mif-bpc' ),
                    'not_found' => __( 'Folders were not found', 'mif-bpc' ),
                    'not_found_in_trash' => __( 'Not found in the Recycle Bin', 'mif-bpc' ),
                    'parent' => __( 'Folder', 'mif-bpc' ),
                ),
                'public' => false,
                'menu_position' => 15,
                'supports' => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
                'taxonomies' => array( 'mif-bpc-doc-folder-tax' ),
                'menu_icon' => 'dashicons-paperclip',
                'has_archive' => true,
                'rewrite' => array( 'slug' => $this->slug, 'with_front' => false ),                
            )
        );

        // Таксономия для документов и папок

        register_taxonomy( 'mif-bpc-doc-folder-tax', 
            array( 'mif-bpc-doc', 'mif-bpc-folder' ), 
            array(
                'hierarchical' => false,
                'show_ui' => true,
                'query_var' => true,
                'rewrite' => array( 'slug' => 'olympic-tax' ),
            )
        );

    }



    // 
    // Delete document в корзину
    // 

    function trash_doc( $doc_id = NULL )
    {
        if ( ! $this->is_doc( $doc_id ) ) return false;
        if ( ! $this->is_access( $doc_id, 'delete' ) ) return false;

        $doc = get_post( $doc_id );
        $this->clean_folder_size( $doc->post_parent );
        $this->clean_user_size( $doc->post_author );

        $ret = wp_trash_post( $doc_id );

        return apply_filters( 'mif_bpc_docs_trash_doc', $ret, $doc_id );
    }



    // 
    // Restore документ из корзины
    // 

    function untrash_doc( $doc_id = NULL )
    {
        if ( ! $this->is_doc( $doc_id ) ) return false;
        if ( ! $this->is_access( $doc_id, 'delete' ) ) return false;

        $doc = get_post( $doc_id );
        $this->clean_folder_size( $doc->post_parent );
        
        // Restore папку (вдруг она тоже в корзине?)
        //
        // Вернёт в $ret2:
        //          true - если это не папка
        //          false - если папка, но не в корзине
        //          $post (array) - если папка была в корзине и она восстановлена

        $ret2 = true;
        if ( $this->is_folder( $doc->post_parent ) ) {

            $ret2 = wp_untrash_post( $doc->post_parent );
            $ret3 = delete_post_meta( $doc->post_parent, 'mif-bpc-trashed-docs' );

        }

        $ret = wp_untrash_post( $doc_id );

        return apply_filters( 'mif_bpc_docs_untrash_doc', $ret, $ret2, $ret3, $doc_id );
    }



    // 
    // Delete document навсегда
    // 

    function delete_doc( $doc_id = NULL )
    {
        if ( ! $this->is_doc( $doc_id ) ) return false;
        if ( ! $this->is_access( $doc_id, 'delete' ) ) return false;
        $ret = wp_delete_post( $doc_id );
        return apply_filters( 'mif_bpc_docs_untrash_doc', $ret, $doc_id );
    }



    // 
    // Помощник удаления документа (удаляет сам файл)
    // 

    function delete_doc_helper( $doc_id )
    {
        $doc = get_post( $doc_id );
        $doc_type = $this->get_doc_type( $doc );
        if ( ! ( $doc_type == 'file' || $doc_type == 'image' ) ) return;

        unlink( $this->get_doc_path( $doc ) );
        // f($this->get_doc_path( $doc ));
    }



    // 
    // Save документ
    // 

    function doc_save( $name, $path, $user_id = NULL, $folder_id = NULL, $file_type = NULL, $order = 0, $descr = '', $post_date = NULL, $post_modified = NULL )
    {
        if ( $folder_id == 'activity_stream_folder' ) $folder_id = $this->get_activity_folder_id();
        if ( $folder_id == 'dialogues_folder' ) $folder_id = $this->get_dialogues_folder_id();

        if ( ! $this->is_folder( $folder_id ) ) return false;
        if ( ! $this->is_access( $folder_id, 'write' ) ) return false;

        if ( $user_id == NULL ) $user_id = bp_loggedin_user_id();

        $doc_data = array(
            'post_type' => 'mif-bpc-doc',
            'post_title' => $name,
            'post_content' => $path,
            'post_status' => 'publish',
            'post_parent' => (int) $folder_id,
            'post_author' => (int) $user_id,
            'menu_order' => (int) $order,
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'post_excerpt' => $descr,
        );

        if ( isset( $file_type ) ) $doc_data['post_mime_type'] = $file_type;

        if ( isset( $post_date ) ) $doc_data['post_date'] = $post_date;
        if ( isset( $post_modified ) ) $doc_data['post_modified'] = $post_modified;

        $doc_data = apply_filters( 'mif_bpc_docs_doc_save_doc_data', $doc_data, $name, $path, $user_id, $folder_id, $file_type, $order, $descr, $post_date, $post_modified );
    
        $post_id = wp_insert_post( wp_slash( $doc_data ) );
        
        $this->clean_folder_size( $folder_id );
        $this->clean_user_size( $user_id );

        groups_update_last_activity();

        return apply_filters( 'mif_bpc_docs_doc_save', $post_id, $name, $path, $user_id, $folder_id, $file_type, $order, $descr, $post_date, $post_modified );
    }




    // 
    // Принять файл и сохранить как документ
    // 

    function upload_and_save( $target_id = NULL, $user_id = NULL )
    {
        if ( $target_id == NULL ) return false;
        if ( $user_id == NULL ) $user_id = bp_loggedin_user_id();
        if ( empty( $user_id ) ) return false;

        if ( isset( $_FILES['file']['tmp_name'] ) ) {

            // Проверить размер
            if ( $_FILES['file']['size'] > $this->get_max_upload_size() ) return false;

            $filename = basename( $_FILES['file']['name'] );
            $path = trailingslashit( $this->get_docs_path() ) . md5( uniqid( rand(), true ) ); 
            $upload_dir = (object) wp_upload_dir();
            $order = ( isset( $_POST['order'] ) ) ? (int) $_POST['order'] : time();

            if ( move_uploaded_file( $_FILES['file']['tmp_name'], $upload_dir->basedir . $path ) ) {

                $post_id = $this->doc_save( $filename, $path, $user_id, $target_id, $_FILES['file']['type'], $order );

            } else {
                
                return false;

            }

        }

        return apply_filters( 'mif_bpc_docs_upload_and_save', $post_id );
    }




    // 
    // Получить идентификатор папки ленты активности
    // 

    function get_activity_folder_id()
    {
        global $bp;

        $item_id = false;

        if ( bp_is_user() ) {

            $item_id = bp_loggedin_user_id();
            $mode = 'user';
            $author_id = $item_id;

        }

        if ( bp_is_group() ) {

            $item_id = $bp->groups->current_group->id;
            $mode = 'group';
            $author_id = $bp->groups->current_group->creator_id;

        }
        
        if ( $item_id ) {

            $args = array(
                'posts_per_page' => 1,
                'paged' => 1,
                'post_type' => 'mif-bpc-folder',
                'post_status' => 'publish,private',
                'meta_key' => $this->activity_folder_meta_key,
                'meta_value' => $mode . '-' . $item_id,
            );

            $folders = get_posts( $args );

            if ( isset( $folders[0]->ID ) ) {

                $folder_id = $folders[0]->ID;

            } else {

                $name = $this->activity_stream_folder_name;
                $desc = __( 'Files, that are published in the activity feed', 'mif-bpc' );
                $folder_id = $this->folder_save( $item_id, $mode, $name, $desc, $publish = 'on', $author_id );

                update_post_meta( $folder_id, $this->activity_folder_meta_key, $mode . '-' . $item_id );
                update_post_meta( $folder_id, $this->folder_access_mode_meta_key, 'everyone_create' );

            }

        }

        return apply_filters( 'mif_bpc_docs_get_activity_folder_id', $folder_id );
    }




    // 
    // Получить идентификатор папки диалогов
    // 

    function get_dialogues_folder_id( $user_id = NULL )
    {
        global $bp;

        if ( $user_id == NULL ) $user_id = bp_loggedin_user_id();
        
        $args = array(
            'posts_per_page' => 1,
            'paged' => 1,
            'post_type' => 'mif-bpc-folder',
            'post_status' => 'private',
            'meta_key' => $this->dialogues_folder_meta_key,
            'meta_value' => $user_id,
        );

        $folders = get_posts( $args );

        if ( isset( $folders[0]->ID ) ) {

            $folder_id = $folders[0]->ID;

        } else {

            $name = $this->dialogues_folder_name;
            $desc = __( 'Files, that are attached to the private messages', 'mif-bpc' );
            $folder_id = $this->folder_save( $user_id, 'user', $name, $desc, $publish = 'off', $user_id );

            update_post_meta( $folder_id, $this->dialogues_folder_meta_key, $user_id );

        }

        return apply_filters( 'mif_bpc_docs_get_dialogues_folder_id', $folder_id, $user_id );
    }




    // 
    // Получить максимально возможный размер загружаемого файла
    // 

    function get_max_upload_size()
    {
        $max_upload_size = wp_max_upload_size();
        return apply_filters( 'mif_bpc_docs_get_max_upload_size', $max_upload_size );
    }




    // 
    // Save папку
    // 

    function folder_save( $item_id = NULL, $mode = 'user', $name = '', $desc = '', $publish = 'on', $author_id = NULL, $post_date = NULL, $post_modified = NULL )
    {
        if ( $item_id == NULL ) return false;
        
        if ( $mode == 'user' ) $author_id = $item_id;
        if ( $author_id == NULL ) $author_id = bp_loggedin_user_id();

        $publish = ( $publish == 'on' ) ? 'publish' : 'private';
        $name = ( trim( $name ) == '' ) ? $this->default_folder_name : trim( $name );

        // Получить первую папку по порядку сортировки
        $top_folder = $this->get_folders_data( $item_id, $mode, 1, 1, 1 );
        $order = 0;
        if ( isset( $top_folder[0]->menu_order ) ) $order = $top_folder[0]->menu_order + 1;

        $folder_data = array(
            'post_type' => 'mif-bpc-folder',
            'post_title' => $name,
            'post_content' => trim( $desc ),
            'post_status' => $publish,
            'post_author' => $author_id,
            'menu_order' => $order,
            'comment_status' => 'closed',
            'ping_status' => 'closed'

        );

        if ( isset( $post_date ) ) $folder_data['post_date'] = $post_date;
        if ( isset( $post_modified ) ) $folder_data['post_modified'] = $post_modified;

        $folder_data = apply_filters( 'mif_bpc_docs_folder_save_folder_data', $folder_data, $item_id, $mode, $name, $desc, $publish, $author_id, $post_date, $post_modified ); 

        $post_id = wp_insert_post( wp_slash( $folder_data ) );

        if ( $mode != 'user' ) update_post_meta( $post_id, $this->folder_parent_meta_key, $mode . '-' . $item_id );

        groups_update_last_activity();

        return apply_filters( 'mif_bpc_docs_folder_save', $post_id, $item_id, $mode, $name, $desc, $publish, $author_id, $post_date, $post_modified );
    }



    
    // 
    // Получить данные коллекции папок
    // 

    function get_folders_data( $item_id = NULL, $mode = 'user', $page = NULL, $trashed = false, $posts_per_page = NULL )
    {
        $arr = array( 'publish' );
        
        // private и trash показывать только для владельца файла or админа

        if ( bp_loggedin_user_id() == $item_id || $this->is_admin() ) {

            $arr[] = 'private';
            if ( $trashed ) $arr[] = 'trash';

        }

        if ( $posts_per_page == NULL ) $posts_per_page = $this->folders_on_page;

        $args = array(
            'posts_per_page' => $posts_per_page,
            'paged' => $page,
            'orderby' => 'menu_order',
            'order' => 'DESC',
            'post_type' => 'mif-bpc-folder',
            'post_status' => implode( ',', $arr ),
        );

        if ( $mode == 'user' ) {

            // Если пользователь, то выбрать папки по автору и без сведений о родительском объекте

            if ( empty( $item_id ) ) $item_id = bp_displayed_user_id();

            $args['author'] = $item_id;
            $args['meta_query'] = array(
                        array(
                            'key' => $this->folder_parent_meta_key,
                            'compare' => 'NOT EXISTS'
                        )
                	);

        } else {

            // Если не пользователь, то выбрать папки по мета-полю родительского объекта

            $args['meta_key'] = $this->folder_parent_meta_key;
            $args['meta_value'] = $mode . '-' . $item_id;

        }

        $folders = get_posts( $args );

        return apply_filters( 'mif_bpc_docs_get_folders_data', $folders, $item_id, $mode, $page, $posts_per_page );
    }


    
    // 
    // Получить обложку папки
    // 

    function get_folder_cover( $folder_id )
    {
        $args = array(
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'DESC',
            'post_type' => 'mif-bpc-doc',
            'post_parent' => $folder_id,
            'post_status' => 'publish, private',
            's' => 'cover',
            // 'exact' => true,
        );

        $docs = get_posts( $args );

        $arr = array();

        foreach ( (array) $docs as $doc ) $arr[$doc->ID] = $doc->post_title;

        // $pattern = '/cover[\d]*\.(png|jpg|gif)/';

        foreach ( $arr as $key => $item ) if ( preg_match( $this->cover_pattern, $item ) ) return $key;
       
        return false;
    }


    
    // 
    // Получить данные коллекции документов
    // 

    function get_docs_collection_data( $folder_id, $page = NULL, $trashed = false, $posts_per_page = NULL, $all_privated = false )
    {
        if ( $posts_per_page == NULL ) $posts_per_page = $this->docs_on_page;

        // Узнать номера чужих private и trash документов

        $exclude_doc_id_arr = array();

        if ( ! ( $this->is_admin() || $all_privated ) ) {

            $args = array(
                'posts_per_page' => -1,
                'post_type' => 'mif-bpc-doc',
                'post_parent' => $folder_id,
                'post_status' => 'private, trash',
                'author__not_in' => bp_loggedin_user_id(),
                'paged' => 0,
            );

            $exclude_doc_arr = get_posts( $args );
            foreach ( (array) $exclude_doc_arr as $item ) $exclude_doc_id_arr[] = $item->ID;

        }

        // Получить данные документов

        $arr = array( 'publish', 'private' );
        if ( $trashed ) $arr[] = 'trash';

        $args = array(
            'posts_per_page' => $posts_per_page,
            'orderby' => 'menu_order',
            'order' => 'DESC',
            'post_type' => 'mif-bpc-doc',
            'post_parent' => $folder_id,
            'post_status' => implode( ',', $arr ),
            'post__not_in' => $exclude_doc_id_arr,
            'paged' => $page,
        );

        $docs = get_posts( $args );

        return apply_filters( 'mif_bpc_docs_get_docs_collection_data', $docs, $folder_id, $page, $posts_per_page );
    }



    // 
    // Возвращает размер всех документов пользователя
    // 

    function get_user_size( $user_id = NULL )
    {
        if ( $user_id == NULL ) $user_id = bp_loggedin_user_id();

        $size = get_user_meta( $user_id, 'mif-bpc-user-size', true );

        if ( $size === '' ) {

            $args = array(
                'posts_per_page' => -1,
                'post_type' => 'mif-bpc-doc',
                'post_status' => 'publish,private',
                'paged' => 0,
            );

            $docs = get_posts( $args );

            $size = 0;
            foreach ( (array) $docs as $doc ) $size += $this->get_doc_size( $doc );

            update_user_meta( $user_id, 'mif-bpc-user-size', $size );

        }

        return apply_filters( 'mif_bpc_docs_get_user_size', $size, $user_id );
    }



    // 
    // Очищает размер всех документов пользователя
    // 

    function clean_user_size( $user_id = NULL )
    {
        if ( $user_id == NULL ) $user_id = bp_loggedin_user_id();
        $ret = delete_user_meta( $user_id, 'mif-bpc-user-size' );
        return apply_filters( 'mif_bpc_docs_clean_user_size', $ret, $user_id );
    }



    // 
    // Возвращает размер всех папок данного места размещения (количество папок и общий объем файлов)
    // 

    function get_all_folders_size()
    {
        $parents_data = $this->get_parents_data();

        $item_id = $parents_data['item_id'];
        $mode = $parents_data['mode'];

        $folders = $this->get_folders_data( $item_id, $mode, 0, false, -1 );

        $count = count( $folders );
        
        $size = 0;
        foreach ( (array) $folders as $folder ) {
            
            $folder_size = $this->get_folder_size( $folder );
            $size += $folder_size['size'];

        }

        $data = array( 'count' => $count, 'size' => $size );
     
        return apply_filters( 'mif_bpc_docs_get_all_folders_size', $data );
    }




    // 
    // Возвращает размер документа (байты на диске)
    // 

    function get_doc_size( $doc = NULL )
    {
        if ( ! is_object( $doc ) ) $doc = get_post( $doc );
        if ( $doc == NULL ) return 0;

        $doc_type = $this->get_doc_type( $doc );

        if ( ! ( $doc_type == 'file' || $doc_type == 'image' ) ) return 0;

        $ret = get_post_meta( $doc->ID, 'mif-bpc-doc-size', true );

        if ( $ret === '' ) {

            // $upload_dir = (object) wp_upload_dir();
            // $file = $upload_dir->basedir . $doc->post_content; 
            $file = $this->get_doc_path( $doc );

            $ret = filesize ( $file );

            if ( $ret ) update_post_meta( $doc->ID, 'mif-bpc-doc-size', $ret );
        }

        return apply_filters( 'mif_bpc_docs_get_doc_size', $ret, $doc );
    }



    // 
    // Возвращает размер папки (количество и общий объем файлов)
    // 

    function get_folder_size( $folder = NULL )
    {
        if ( is_numeric( $folder ) ) $folder = get_post( $folder );
        if ( ! $this->is_folder( $folder->ID ) ) return false;

        $data = get_post_meta( $folder->ID, 'mif-bpc-folder-size', true );

        if ( $data === '' ) {

            $trashed = ( $folder->post_status == 'trash' ) ? true : false;
            $docs = $this->get_docs_collection_data( $folder->ID, 0, $trashed, -1, true );

            $count = 0;
            $size = 0;
            $private = array();
            foreach ( (array) $docs as $doc ) {

                if ( $doc->post_status == 'publish' ) {

                    $count ++;
                    $size += $this->get_doc_size( $doc );

                } elseif ( $doc->post_status == 'private' ) {

                    $private[$doc->post_author]['count'] = ( isset( $private[$doc->post_author]['count'] ) ) ? $private[$doc->post_author]['count'] + 1 : 1;
                    $private[$doc->post_author]['size'] = ( isset( $private[$doc->post_author]['size'] ) ) ? $private[$doc->post_author]['size'] + $this->get_doc_size( $doc ) : $this->get_doc_size( $doc );


                }

            }

            $data = array( 'count' => $count, 'size' => $size, 'private' => $private );

            update_post_meta( $folder->ID, 'mif-bpc-folder-size', $data );

        }

        $ret['count'] = $data['count'];
        $ret['size'] = $data['size'];

        if ( $this->is_admin() ) {
        
            // Если админ, то посчитать все приватные documents

            foreach ( (array) $data['private'] as $private ) {

                $ret['count'] += $private['count'];
                $ret['size'] += $private['size'];

            }

        } elseif ( is_user_logged_in() ) {

            // Если есть пользователь, то учесть его возможные приватные данные

            $user_id = bp_loggedin_user_id();

            if ( isset( $data['private'][$user_id] ) ) {

                $private = $data['private'][$user_id];
                $ret['count'] += $private['count'];
                $ret['size'] += $private['size'];

            }

        }

        return apply_filters( 'mif_bpc_docs_get_folder_size', $ret, $data, $folder );
    }



    // 
    // Очищает данные о размере папки
    // 

    function clean_folder_size( $folder = NULL )
    {
        if ( is_numeric( $folder ) ) $folder = get_post( $folder );
        if ( ! $this->is_folder( $folder->ID ) ) return false;

        $ret = delete_post_meta( $folder->ID, 'mif-bpc-folder-size' );

        return apply_filters( 'mif_bpc_docs_clean_folder_size', $ret, $folder );
    }



    // 
    // Delete folder в корзину
    // 

    function trash_folder( $folder_id = NULL )
    {
        if ( ! $this->is_folder( $folder_id ) ) return false;
        if ( ! $this->is_access( $folder_id, 'delete' ) ) return false;

        $docs = $this->get_docs_collection_data( $folder_id, 0, 0, -1 );

        // Delete в корзину все documents папки, запомнив их номера

        $arr = array();
        $ret = array();
        $success = true;

        foreach ( (array) $docs as $doc ) {

            $arr[] = $doc->ID;
            if ( ! $this->trash_doc( $doc->ID ) ) $success = false;

        }

        if ( $success ) {

            // Save в мета-поле папки номера удаленных документов

            $ret2 = update_post_meta( $folder_id, 'mif-bpc-trashed-docs', implode( ',', $arr ) );

            // Delete folder

            $this->clean_folder_size( $folder_id );
            $ret3 = wp_trash_post( $folder_id );

        } else {

            $ret3 = false;

        }


        return apply_filters( 'mif_bpc_docs_trash_folder', $ret3, $ret2, $ret, $folder_id );
    }



    // 
    // Restore папку из корзины
    // 

    function untrash_folder( $folder_id = NULL )
    {
        if ( ! $this->is_folder( $folder_id ) ) return false;
        if ( ! $this->is_access( $folder_id, 'delete' ) ) return false;

        // Restore папку

        $ret = wp_untrash_post( $folder_id );

        // Restore все documents

        $docs_ids = get_post_meta( $folder_id, 'mif-bpc-trashed-docs', true );
        $arr = explode( ',', $docs_ids );

        $ret2 = array();
        foreach ( (array) $arr as $doc_id ) $ret2[] = $this->untrash_doc( $doc_id );

        // Очистить информацию о ранее удаленных документах

        $ret3 = delete_post_meta( $folder_id, 'mif-bpc-trashed-docs' );

        return apply_filters( 'mif_bpc_docs_untrash_folder', $ret, $ret2, $ret3, $folder_id );
    }



    // 
    // Delete folder навсегда
    // 

    function delete_folder( $folder_id = NULL )
    {
        if ( ! $this->is_folder( $folder_id ) ) return false;
        if ( ! $this->is_access( $folder_id, 'delete' ) ) return false;

        $docs = $this->get_docs_collection_data( $folder_id, 0, 1, -1 );

        // Delete навсегда все documents папки

        $ret = array();
        foreach ( (array) $docs as $doc ) $ret[] = wp_delete_post( $doc->ID );

        // Delete folder

        $ret3 = wp_delete_post( $folder_id );

        return apply_filters( 'mif_bpc_docs_delete_folder', $ret3, $ret2, $ret, $folder_id );
    }



    // 
    // Проверяет, является ли объект документом
    // 

    function is_doc( $doc_id = NULL )
    {
        if ( $doc_id == NULL ) return false;

        $doc = get_post( $doc_id );

        $ret = false;
        if ( isset( $doc->post_type ) && $doc->post_type == 'mif-bpc-doc' ) $ret = true;

        return apply_filters( 'mif_bpc_docs_is_doc', $ret, $doc_id );
    }



    // 
    // Проверяет, является ли объект папкой
    // 

    function is_folder( $folder_id = NULL )
    {
        if ( $folder_id == NULL ) return false;

        $folder = get_post( $folder_id );

        $ret = false;
        if ( isset( $folder->post_type ) && $folder->post_type == 'mif-bpc-folder' ) $ret = true;

        return apply_filters( 'mif_bpc_docs_is_folder', $ret, $folder_id );
    }



    // 
    // Address папки для файлов пользователя
    // 
    // /docs/2017/<user_id>
    // Используется как продолжение /wp-content/uploads
    //

    function get_docs_path( $user_id = NULL )
    {
        if ( $user_id == NULL ) $user_id = bp_loggedin_user_id();

        // $upload = wp_upload_dir();
        // $basedir = $upload['basedir'];

        $basedir = $this->get_doc_path();

        $time = current_time( 'mysql' );
		$y = substr( $time, 0, 4 ); // год
		$m = substr( $time, 5, 2 ); // месяц

        $path = '/' . $this->path . '/' . $y . '/' . $user_id;
        $path = apply_filters( 'mif_bpc_docs_get_path', $path, $user_id );

        $ret = ( wp_mkdir_p( $basedir . $path ) ) ? $path : false;

        $this->basedir_safity();

        return apply_filters( 'mif_bpc_docs_get_docs_path', $ret, $user_id, $upload_dir, $y, $m );
    }




    //
    // Возвращает сведения о прикрепленном документе
    //

    function attachments_data( $empty, $doc_id )
    {
        if ( ! $this->is_doc( $doc_id ) ) return false;
        if ( ! $this->is_access( $doc_id, 'read' ) ) return false;

        $doc = get_post( $doc_id );

        $arr = array();

        $arr['name'] = $this->get_doc_name( $doc );
        $arr['icon'] = $this->get_file_logo( $doc, 1 );
        $arr['url'] = $this->get_doc_url( $doc->ID );
        $arr['ext'] = $this->get_doc_ext( $doc->post_title );

        return apply_filters( 'mif_bpc_docs_attachments_data', $arr, $doc_id );
    }




    //
    // Закрывает базовый каталог от прямого доступа
    //

    function basedir_safity()
    {
        $basedir = $this->get_doc_path();
        $safity_file = $basedir . '/' . $this->path . '/.htaccess';

        if ( file_exists( $safity_file ) ) return;

        file_put_contents( $safity_file, "<Files *>\ndeny from all\n</Files>" );
    }



    //
    // Address страницы документов
    //

    function get_docs_url()
    {
        global $bp;

        if ( bp_is_user() ) {

            // $url = trailingslashit( $bp->displayed_user->domain ) . $this->slug;
            $url = bp_core_get_user_domain( bp_displayed_user_id() ) . $this->slug;

        } elseif ( bp_is_group() ) {

            $url = bp_get_group_permalink( $bp->groups->current_group ) . $this->slug;

        } else {

            $url = '';

        }

        return apply_filters( 'mif_bpc_dialogues_get_docs_url', $url );
    }



    //
    // Address конкретной папки
    //

    function get_folder_url( $folder_id = NULL )
    {
        if ( ! $this->is_folder( $folder_id ) ) return;

        $folder_url = $this->get_docs_url() . '/folder/' . $folder_id . '/';

        return apply_filters( 'mif_bpc_docs_get_folder_url', $folder_url, $folder_id );
    }



    //
    // Address конкретного документа
    //

    function get_doc_url( $doc_id = NULL )
    {
        if ( ! $this->is_doc( $doc_id ) ) return;

        $doc = get_post( $doc_id );
        $doc_url = trailingslashit(  bp_core_get_user_domain( $doc->post_author ) ) . $this->slug . '/' . $doc_id . '/';

        return apply_filters( 'mif_bpc_docs_get_doc_url', $doc_url, $doc_id );
    }



    //
    // Имя документа
    //

    function get_doc_name( $doc = NULL )
    {
        if ( $doc == NULL ) return;
        if ( ! is_object( $doc ) ) $doc = get_post( $doc ); 

        $name = $doc->post_title;
        
        $icon = mif_bpc_get_file_icon( $name );

        if ( in_array( $this->get_doc_type( $doc ), array( 'file', 'image' ) ) && preg_match( '/noext/', $icon ) ) $name = preg_replace( '/\.\w+$/', '', $name );

        return apply_filters( 'mif_bpc_docs_get_doc_name', $name, $doc );
    }



    // 
    // Инициирует скачивание документа
    // 

    function force_download()
    {
        if ( bp_current_component() != 'docs' || ! is_numeric( bp_current_action() ) ) return false;
        if ( bp_action_variable( 0 ) != 'download' ) return false;
        
        $this->download( bp_current_action() );
    }



    // 
    // Скачивание документа
    // 

    function download( $doc_id = NULL )
    {
        if ( ! $this->is_doc( $doc_id ) ) return false;
        if ( ! $this->is_access( $doc_id, 'read' ) ) return false;

        $doc = get_post( $doc_id );

        if ( empty( $doc ) ) return false;

        // $folder_id = $doc->post_parent;
        // if ( ! $this->is_access( $folder_id, 'read' ) ) return false;

        $file = $this->get_doc_path( $doc ); 
        $filename = str_replace( array( '*', '|', '\\', ':', '"', '<', '>', '?', '/' ), '_', $doc->post_title );

        if ( file_exists( $file ) ) {

            if ( ob_get_level() ) ob_end_clean();

            header( 'Content-Description: File Transfer' );
            header( 'Content-Type: ' . $doc->post_mime_type );
            header('Content-Type: application/octet-stream');
            header( 'Content-Disposition: attachment; filename="' . $filename . '"');
            header( 'Content-Transfer-Encoding: binary');
            header( 'Expires: 0');
            header( 'Cache-Control: must-revalidate');
            header( 'Pragma: public');
            header( 'Content-Length: ' . filesize( $file ) );

            if ( $fd = fopen( $file, 'rb' ) ) {

                while ( ! feof( $fd ) ) print fread( $fd, 1024 );
                fclose( $fd );

            }

        }

        exit;
    }



    // 
    // Возвращает физический путь до файла
    // 

    function get_doc_path( $doc = NULL )
    {
        $upload_dir = (object) wp_upload_dir();
        $basedir = apply_filters( 'mif_bpc_docs_basedir', $upload_dir->basedir );

        if ( $doc == NULL ) return $basedir;

        $file = $doc->post_content;

        while ( strpos( $file, '../' ) > 1 ) $file = preg_replace( '![^/]+/\.\./!', '', $file );
        $file = preg_replace( '!^/(\.\./)+!', '', $file );

        $path = $basedir . $file;

        return apply_filters( 'mif_bpc_docs_get_doc_path', $path, $doc );
    }



    // 
    // Возвращает расширение файла документа
    // 

    function get_doc_ext( $name )
    {
        $arr = explode( ".", $name );
        $ext = ( count( $arr ) > 1 ) ? end( $arr ) : '';
        return apply_filters( 'mif_bpc_docs_get_doc_ext', $ext, $name );
    }



    // 
    // Возвращает тип документа (image, file, link or html)
    // 

    function get_doc_type( $doc )
    {
        if ( ! is_object( $doc ) ) $doc = get_post( $doc );
        if ( empty( $doc ) ) return false;

        if ( preg_match( '/^\/' . $this->path . '\//', $doc->post_content ) ) {

            // Если содержимое начинается с /docs/

            // $ext = end( explode( ".", $doc->post_title ) );
            $ext = $this->get_doc_ext( $doc->post_title );
            $img_types = apply_filters( 'mif_bpc_docs_img_types', array( 'png', 'jpg', 'jpeg', 'gif' ) );

            $ret = ( in_array( $ext, $img_types ) ) ? 'image' : 'file';
            
        } elseif ( preg_match( '/^https?:\/\//', $doc->post_content ) ) {

            // Если содержимое начинается с http

            $ret = 'link';

        } else {

            // Если содержимое начинается с http

            $ret = 'html';

        }

        return apply_filters( 'mif_bpc_docs_get_doc_type', $ret, $doc );
    }



    //
    // Обеспечивает сохранность расширения файла при изменении имени
    //
    
    function ext_safety( $new_name, $old_name = '' )
    {
        if ( $old_name == '' ) return $new_name;

        $new_ext = $this->get_doc_ext( $new_name );
        $old_ext = $this->get_doc_ext( $old_name );

        $name = $new_name;
        if ( $new_ext != $old_ext && $old_ext != '' ) $name = $new_name . '.' . $old_ext;

        return apply_filters( 'mif_bpc_docs_ext_safety', $name, $new_name, $old_name );
    }



    //
    // Получает данные документа, отображаемого на экране
    //

    function get_doc_data()
    {
        if ( bp_current_component() != 'docs' || ! is_numeric( bp_current_action() ) ) return false;

        $doc_id = bp_current_action();

        // if ( ! $this->is_access( $doc_id, 'read' ) ) return false;

        $doc_data = get_post( $doc_id );

        return apply_filters( 'mif_bpc_docs_get_doc_data', $doc_data, $doc_id );
    }



    //
    // Сортирует documents в папке
    //

    function docs_reorder( $folder_id, $order_raw )
    {
        if ( ! $this->is_folder( $folder_id ) ) return false;
        if ( ! $this->is_access( $folder_id, 'write' ) ) return false;
        
        // Получить массив ID всех документов папки (включая удаленные)

        $docs = $this->get_docs_collection_data( $folder_id, 0, 1, -1 );
        $arr = array();
        foreach ( (array) $docs as $doc ) $arr[] = $doc->ID;

        // Из записи doc-NNN оставить только NNN, относящиеся к документам в папке
        $order = array();
        foreach ( (array) $order_raw as $key => $value ) {

            $nnn = (int) end( explode( "-", $value ) );
            if ( $nnn && in_array( $nnn, $arr ) ) $order[] = $nnn;

        }

        // Add к порядку отсутствующие documents папки

        foreach ( $arr as $item ) if ( ! in_array( $item, $order ) ) $order[] = $item;

        // Update порядок в базе данных

        $count = count( $order );
        foreach ( $order as $key => $value ) {

            $data = array(
                    'ID' => $value,
                    'menu_order' => $count - $key,
                );

            $ret = wp_update_post( wp_slash( $data ) );

        }

        groups_update_last_activity();

        return apply_filters( 'mif_bpc_docs_docs_reorder', $ret, $folder_id, $order );
    }



    //
    // Сортирует папки
    //

    function folders_reorder( $item_id, $mode, $order_raw )
    {
        if ( ! $this->is_access( 'all-folders', 'write' ) ) return false;
                
        // Получить массив ID всех папок (включая удаленные)

        $folders = $this->get_folders_data( $item_id, $mode, 0, 1, -1 );
        $arr = array();
        foreach ( (array) $folders as $folder ) $arr[] = $folder->ID;

        // Из записи doc-NNN оставить только NNN, относящиеся к правильным папкам
        $order = array();
        foreach ( (array) $order_raw as $key => $value ) {

            $nnn = (int) end( explode( "-", $value ) );
            if ( $nnn && in_array( $nnn, $arr ) ) $order[] = $nnn;

        }

        // Add к порядку отсутствующие папки

        foreach ( $arr as $item ) if ( ! in_array( $item, $order ) ) $order[] = $item;

        // Update порядок в базе данных

        $count = count( $order );
        foreach ( $order as $key => $value ) {

            $data = array(
                    'ID' => $value,
                    'menu_order' => $count - $key,
                );

            $ret = wp_update_post( wp_slash( $data ) );

        }

        groups_update_last_activity();

        return apply_filters( 'mif_bpc_docs_folders_reorder', $ret, $item_id, $mode, $order );
    }



    //
    // Узнать данные текущего места размещения папки
    //

    function get_parents_data()
    {

        if ( bp_is_user() ) {
            
            $item_id = bp_displayed_user_id();
            $mode = 'user';
        
        } elseif ( bp_is_group() ) {
            
            $item_id = bp_get_current_group_id();
            $mode = 'group';

        } else {

            // Здесь можно уточнить идентификаторы нестандартного размещения
            
            $item_id = apply_filters( 'mif_bpc_docs_get_parents_data_item_id', 0 );
            $mode = apply_filters( 'mif_bpc_docs_get_parents_data_mode', '' );;

        }

        $parents_data = array( 'item_id' => $item_id, 'mode' => $mode );

        return apply_filters( 'mif_bpc_docs_get_parents_data', $parents_data );
    }



    //
    // Проверяет, является ли текущий пользователь администартором всего сайта or группы
    //

    function is_admin( $group_id = NULL )
    {
        global $bp;

        $ret = current_user_can( 'manage_options' );

        if ( bp_is_group()) $group_id = $bp->groups->current_group->id;
        if ( $group_id && groups_is_user_admin( bp_loggedin_user_id(), $group_id ) ) $ret = true;

        return apply_filters( 'mif_bpc_docs_is_admin', $ret, $group_id );
    }



    //
    // Является ли папка - папкой диалогов?
    //

    function is_dialogues_folder( $folder = NULL )
    {
        if ( is_numeric( $folder ) ) $folder = get_post( $folder );
        if ( ! $this->is_folder( $folder ) ) return false;

        $meta = get_post_meta( $folder->ID, $this->dialogues_folder_meta_key, true );
        $ret = ( empty( $meta ) ) ? false : true;

        return apply_filters( 'mif_bpc_docs_is_dialogues_folder', $ret, $folder );
    }



    //
    // Есть ли доступ к объекту?
    // режимы - read, write, delete
    //

    function is_access( $item, $level = 'write' ) 
    {
        // Админ сайта всегда может всё
        if ( $this->is_admin() ) return apply_filters( 'mif_bpc_docs_is_access_admin', true, $item, $level );

        // Settings доступа в целом для системы документов
        
        $ret = false;
        if ( $item == '' ) $item = 'all-folders';
        if ( $item === 'all-folders' ) {

            switch ( $level ) {

                case 'read' :
                    if ( bp_is_user() ) $ret = true;
                    if ( bp_is_group() ) $ret = $this->is_access_to_group( 'read' );
                    $ret = apply_filters( 'mif_bpc_docs_is_access_all_folders_read', $ret, $item, $level );
                    break;

                case 'write' :
                    if ( bp_is_user() ) $ret = ( bp_loggedin_user_id() && bp_loggedin_user_id() == bp_displayed_user_id() ) ? true : false;
                    if ( bp_is_group() ) $ret = $this->is_access_to_group( 'write' );
                    $ret = apply_filters( 'mif_bpc_docs_is_access_all_folders_write', $ret, $item, $level );
                    break;

                case 'delete' :
                    if ( bp_is_user() ) $ret = ( bp_loggedin_user_id() && bp_loggedin_user_id() == bp_displayed_user_id() ) ? true : false;
                    if ( bp_is_group() ) $ret = $this->is_access_to_group( 'delete' );
                    $ret = apply_filters( 'mif_bpc_docs_is_access_all_folders_delete', $ret, $item, $level );
                    break;

            }

            return apply_filters( 'mif_bpc_docs_is_access_all_folders', $ret, $item, $level );

        }
        
        // Settings доступа для конкретной папки or документа

        if ( ! is_object( $item ) ) $item = get_post( $item );

        $ret = false;
        $place_mode = $this->place( $item );
        $place_id = $this->place( $item, true );

        if ( $this->is_folder( $item ) ) {

            switch ( $level ) {
                case 'read' :
                    if ( $place_mode == 'user' ) $ret = ( $item->post_status == 'publish' || $item->post_author == bp_loggedin_user_id() ) ? true : false;
                    if ( $place_mode == 'group' ) $ret = $this->is_access_to_group( 'read', $place_id );
                    $ret = apply_filters( 'mif_bpc_docs_is_access_folder_read', $ret, $item, $level );
                    break;

                case 'write' :

                    if ( $place_mode == 'user' ) $ret = ( bp_loggedin_user_id() && $item->post_author == bp_loggedin_user_id() ) ? true : false;
                    if ( $place_mode == 'group' ) $ret = ( groups_is_user_member( bp_loggedin_user_id(), $place_id ) && 
                                                            ( $this->get_access_mode_to_folder( $item->ID ) == 'everyone_create' ||
                                                                $item->post_author == bp_loggedin_user_id() ||
                                                                groups_is_user_admin( bp_loggedin_user_id(), $place_id ) ) ) ? true : false;
                    $ret = apply_filters( 'mif_bpc_docs_is_access_folder_write', $ret, $item, $level );
                    break;

                case 'delete' :
                    if ( $place_mode == 'user' ) $ret = ( bp_loggedin_user_id() && $item->post_author == bp_loggedin_user_id() ) ? true : false;
                    if ( $place_mode == 'group' ) $ret = ( groups_is_user_member( bp_loggedin_user_id(), $place_id ) && 
                                                            ( $this->get_access_mode_to_folder( $item->ID ) == 'everyone_delete' ||
                                                                $item->post_author == bp_loggedin_user_id() ||
                                                                groups_is_user_admin( bp_loggedin_user_id(), $place_id ) ) ) ? true : false;
                    $ret = apply_filters( 'mif_bpc_docs_is_access_folder_delete', $ret, $item, $level );
                    break;

            }

        } elseif ( $this->is_doc( $item ) ) {

            $folder = get_post( $item->post_parent );

            switch ( $level ) {

                case 'read' :
                    if ( $place_mode == 'user' ) $ret = ( ( $item->post_status == 'publish' && $folder->post_status == 'publish' ) || 
                                                                $item->post_author == bp_loggedin_user_id() ) ? true : false;
                    if ( $place_mode == 'group' ) $ret = $this->is_access_to_group( 'read', $place_id );

                    // Сделать исключение для обложки папки

                    if ( isset( $_REQUEST['cover'] ) && $_REQUEST['cover'] == 'show' && preg_match( $this->cover_pattern, $item->post_title ) ) $ret = true;

                    $ret = apply_filters( 'mif_bpc_docs_is_access_doc_read', $ret, $item, $level );
                    break;

                case 'write' :
                    if ( $place_mode == 'user' ) $ret = ( bp_loggedin_user_id() && $item->post_author == bp_loggedin_user_id() ) ? true : false;
                    if ( $place_mode == 'group' ) $ret = ( groups_is_user_member( bp_loggedin_user_id(), $place_id ) && 
                                                            ( $this->get_access_mode_to_doc( $item->ID ) == 'everyone_create' ||
                                                                $item->post_author == bp_loggedin_user_id() ||
                                                                groups_is_user_admin( bp_loggedin_user_id(), $place_id ) ) ) ? true : false;
                    $ret = apply_filters( 'mif_bpc_docs_is_access_doc_write', $ret, $item, $level );
                    break;

                case 'delete' :
                    if ( $place_mode == 'user' ) $ret = ( bp_loggedin_user_id() && $item->post_author == bp_loggedin_user_id() ) ? true : false;
                    if ( $place_mode == 'group' ) $ret = ( groups_is_user_member( bp_loggedin_user_id(), $place_id ) && 
                                                            ( $this->get_access_mode_to_doc( $item->ID ) == 'everyone_delete' ||
                                                                $item->post_author == bp_loggedin_user_id() ||
                                                                groups_is_user_admin( bp_loggedin_user_id(), $place_id ) ) ) ? true : false;
                    $ret = apply_filters( 'mif_bpc_docs_is_access_doc_delete', $ret, $item, $level );
                    break;

            }

            if ( $this->is_dialogues_folder( $folder ) && $place_mode == 'user' ) $ret = apply_filters( 'mif_bpc_docs_dialogues_doc_access', $ret, $item, $level );

        }

        return apply_filters( 'mif_bpc_docs_is_access', $ret, $item, $level );
    }


    //
    // Имеет ли текущий пользователь доступ к группе?
    //

    function is_access_to_group( $level = 'read', $group = NULL )
    {
        global $bp;
        
        if ( is_numeric( $group ) ) $group = groups_get_group( $group );
        if ( $group == NULL ) $group = $bp->groups->current_group;

        $group_id = $group->id;
        $user_id = bp_loggedin_user_id();

        $access_mode = groups_get_groupmeta( $group_id, $this->group_access_mode_meta_key );

        $everyone_create = ( isset( $access_mode['everyone_create'] ) && $access_mode['everyone_create'] ) ? true : false;
        $everyone_delete = ( isset( $access_mode['everyone_delete'] ) && $access_mode['everyone_delete'] ) ? true : false;

        if ( $level == 'read' ) $ret = bp_group_is_visible( $group );
        if ( $level == 'write' ) $ret = ( groups_is_user_admin( $user_id, $group_id ) || ( groups_is_user_member( $user_id, $group_id ) && $everyone_create ) ) ? true : false;
        if ( $level == 'delete' ) $ret = ( groups_is_user_admin( $user_id, $group_id ) || ( groups_is_user_member( $user_id, $group_id ) && $everyone_delete ) ) ? true : false;

        return apply_filters( 'mif_bpc_docs_is_access_to_group', $ret, $level, $group );
    }



    //
    // Возвращает расположение папки or документа (пользователь, группа or др.)
    //

    function place( $item, $id = false )
    {
        if ( is_numeric( $item ) ) $item = get_post( $item );
        
        $folder_id = NULL;
        
        if ( $this->is_folder( $item ) ) $folder_id = $item->ID;
        
        if ( $this->is_doc( $item ) ) {

            $folder_id = $item->post_parent;

        }

        if ( $folder_id ) {

            $parent_data = get_post_meta( $folder_id, $this->folder_parent_meta_key, true );

            if ( $parent_data ) {

                $arr = (array) explode( '-', $parent_data );
                $item_id = (int) array_pop( $arr );
                $place = implode( '-', $arr );

            } else {

                $item_id = $item->post_author;
                $place = 'user';

            }

        } else {

            $item_id = false;
            $place = false;

        }

        $ret = ( $id ) ? $item_id : $place;

        return apply_filters( 'mif_bpc_docs_place', $ret, $item, $id );
    }



    //
    // Возвращает режим доступа к documents (только для документов групп)
    //

    function get_access_mode_to_doc( $doc_id )
    {
        $doc = get_post( $doc_id );
        $ret = $this->get_access_mode_to_folder( $doc->post_parent );
        return apply_filters( 'mif_bpc_docs_get_access_mode_to_doc', $ret, $doc_id );
    }



    //
    // Возвращает режим доступа к папке (только для папок групп)
    //

    function get_access_mode_to_folder( $folder_id, $computed = true )
    {
        $ret = 'default';

        if ( empty( $folder_id ) ) return apply_filters( 'mif_bpc_docs_get_access_mode_to_folder_empty', $ret, $folder_id, $computed );;

        $access_mode = get_post_meta( $folder_id, $this->folder_access_mode_meta_key, true );
        if ( in_array( $access_mode, array( 'only_admin', 'everyone_create', 'everyone_delete' ) ) ) $ret = $access_mode;

        if ( $ret == 'default' && $computed && $this->place( $folder_id ) == 'group' ) {

            $ret = 'only_admin';
            $group_access_mode = groups_get_groupmeta( $this->place( $folder_id, true ), $this->group_access_mode_meta_key );
            if ( isset( $group_access_mode['everyone_create'] ) && $group_access_mode['everyone_create'] ) $ret = 'everyone_create';
            if ( isset( $group_access_mode['everyone_delete'] ) && $group_access_mode['everyone_delete'] ) $ret = 'everyone_delete';

        }

        return apply_filters( 'mif_bpc_docs_get_access_mode_to_folder', $ret, $folder_id, $computed );
    }



    //
    // Устанавливает режим доступа к папке (только для папок групп)
    //

    function set_access_mode_to_folder( $folder_id, $access_mode )
    {
        if ( ! $this->is_folder( $folder_id ) ) return false;
        if ( ! in_array( $access_mode, array( 'default', 'only_admin', 'everyone_create', 'everyone_delete' ) ) ) apply_filters( 'mif_bpc_docs_set_access_mode_to_folder_arr', false, $folder_id, $access_mode );

        $ret = update_post_meta( $folder_id, $this->folder_access_mode_meta_key, $access_mode );

        return apply_filters( 'mif_bpc_docs_set_access_mode_to_folder', $ret, $folder_id, $access_mode );
    }


}



?>