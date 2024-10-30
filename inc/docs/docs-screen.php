<?php

//
// Documents (экранные функции)
// 
//


defined( 'ABSPATH' ) || exit;



class mif_bpc_docs_screen extends mif_bpc_docs_core {


    //
    // Размер аватарки пользователя
    //

    public $avatar_size = 50;

    //
    // Description уровней доступа к папке
    //

    // public $access_mode_descr = array();



    function __construct()
    {
        
        // $this->access_mode_descr = apply_filters( 'mif_bpc_docs_access_mode_descr', array(
        //     'default' => __( 'As in group settings', 'mif-bpc' ),
        //     'only_admin' => __( 'Only the folder owner and administrator can publish and delete documents', 'mif-bpc' ),
        //     'everyone_create' => __( 'Everyone can upload documents, but delete only their own', 'mif-bpc' ),
        //     'everyone_delete' => __( 'Everyone can upload and delete any documents', 'mif-bpc' ),
        // ) );
        
        parent::__construct();
    }



    // 
    // Описание уровней доступа
    // 

    function get_access_mode_descr()
    {
        $access_mode_descr = array(
            'default' => __( 'As in group settings', 'mif-bpc' ),
            'only_admin' => __( 'Only the folder owner and administrator can publish and delete documents', 'mif-bpc' ),
            'everyone_create' => __( 'Everyone can upload documents, but delete only their own', 'mif-bpc' ),
            'everyone_delete' => __( 'Everyone can upload and delete any documents', 'mif-bpc' ),
        );

        return apply_filters( 'mif_bpc_docs_access_mode_descr', $access_mode_descr );
    }



    // 
    // Форма загрузки
    // 

    function get_upload_form( $folder_id = NULL )
    {
        if ( ! $this->is_folder( $folder_id ) ) return;
        if ( ! $this->is_access( $folder_id, 'write' ) ) return;
        
        $folder = get_post( $folder_id );

        if ( $folder->post_status == 'trash' ) return;

        $out = '';

        $out .= '<div class="upload-form">';
        $out .= '<form>';
        $out .= '<div class="drop-box">';
        // $out .= '<div class="response-box clearfix"></div>';
        $out .= '<div class="template">' . $this->get_doc_item() . '</div>
        <p>' . __( 'Drag files here', 'mif-bpc' ) . '...</p>
        <input type="file" name="files[]" multiple="multiple" class="docs-upload-form">
        <input name="MAX_FILE_SIZE" value="' . $this->get_max_upload_size() . '" type="hidden">
        <input name="max_file_error" value="' . __( 'The file is too large', 'mif-bpc' ) . '" type="hidden">';
        $out .= '</div>';
        $out .= '<p>... ' . __( 'or', 'mif-bpc' ) . ' <a href="#" class="show-link-box">' . __( 'specify Internet link', 'mif-bpc' ) . '</a></p>';

        $out .= '<div class="link-box">
        <p><input type="text" name="link" placeholder="' . __( 'URL', 'mif-bpc' ) . '">
        <p><input type="text" name="descr" placeholder="' . __( 'Description', 'mif-bpc' ) . '">
        <p><input type="submit" value="' . __( 'Publish', 'mif-bpc' ) . '">
        </div>';

        $out .= '<input type="hidden" name="upload_nonce" value="' . wp_create_nonce( 'mif-bpc-docs-file-upload-nonce' ) . '">';
        $out .= '<input type="hidden" name="folder_id" value="' . $folder_id . '">';
        $out .= '<input type="hidden" name="action" value="mif-bpc-docs-upload-files">';
        
        $out .= '</form>';
        $out .= '</div>';

        return apply_filters( 'mif_bpc_docs_get_upload_form', $out, $folder_id );
    }



    // 
    // Все папки пользователя or группы
    // 

    function get_folders( $page = 1, $item_id = NULL, $mode = NULL, $trashed = false )
    {

        // Уточнить размещение, если оно не указано

        if ( empty( $item_id ) ) {

            $parents_data = $this->get_parents_data();

            $item_id = $parents_data['item_id'];
            $mode = $parents_data['mode'];

        }

        // Сформировать страницу

        $out = '';

        $sortable = ( $this->is_access( 'all-folders', 'write' ) ) ? ' sortable' : '';

        if ( $page === 1 ) $out .= '<div class="collection' . $sortable . ' clearfix">';

        $folders = $this->get_folders_data( $item_id, $mode, $page, $trashed );

        if ( $folders ) {

            $arr = array();
            foreach( $folders as $folder ) $arr[] = $this->get_folder_item( $folder );

            $out .= implode( "\n", $arr );
            if ( count( $folders ) == $this->folders_on_page ) $out .= $this->get_more_button( $page );

        } else {

            if ( $page === 1 ) $out = mif_bpc_message( __( 'Folders were not located', 'mif-bpc' ) );

        }

        if ( $page === 1 ) $out .= '</div>';

        return apply_filters( 'mif_bpc_docs_get_folders', $out, $page, $item_id, $mode );
    }



    // 
    // Выводит страницу создания or настройки папки
    // 

    function get_folder_settings( $folder_id = NULL )
    {
        $out = '<div class="folder-settings">';

        if ( $folder_id == NULL ) {

            // Создаем новую папку

            $parents_data = $this->get_parents_data();
            $item_id = $parents_data['item_id'];
            $mode = $parents_data['mode'];

            $out .= '<h2>' . __( 'New folder', 'mif-bpc' ) . '</h2>
            <form id="new-folder">
            <input type="hidden" name="item_id" value="' . $item_id . '">
            <input type="hidden" name="mode" value="' . $mode . '">
            <input type="hidden" name="redirect" value="' . $this->get_docs_url() . '/">
            <input type="hidden" name="_wpnonce" value="' . wp_create_nonce( 'mif-bpc-docs-new-folder-nonce' ) . '">';

            $name = $this->default_folder_name;
            $desc = '';
            $publish = ' checked';
            $remove_box = '';
            $disabled = '';

        } else {

            // Редактируем существующую папку

            $folder = get_post( $folder_id );

            if ( ! ( $this->is_admin() || $folder->post_author == bp_loggedin_user_id() ) ) return false;
            if ( ! $this->is_folder( $folder ) ) return false;

            $out .= '<h2>' . __( 'Folder settings', 'mif-bpc' ) . '</h2>';
            
            $remove_box = '<p><a href="' . $this->get_folder_url( $folder_id ) . '" class="remove-box-toggle dotted">' . __( 'Delete folder', 'mif-bpc' ) . '</a></p>
            <div class="remove-box">
            <div class="message warning">
            <p>' . __( 'The folder and all of its documents will be moved to the Recycle Bin and will be deleted permanently in a few days. While the materials are stored in the Recycle Bin, you can restore them.', 'mif-bpc' ) . '</p>
            <p><input type="button" class="remove to-trash" value="' . __( 'Delete', 'mif-bpc' ) . '"></p>
            </div>
            </div>';

            $disabled = '';
            if ( $folder->post_status == 'trash' ) {

                $out .= $this->folder_restore_delete_tool( $folder_id );
                $disabled = ' disabled';
                $remove_box = '';

            }

            $out .= '<form id="folder-settings" class="' . $folder->post_status . '">
            <input type="hidden" name="folder_id" value="' . $folder_id . '">
            <input type="hidden" name="_wpnonce" value="' . wp_create_nonce( 'mif-bpc-docs-folder-settings-nonce' ) . '">';

            $name = $folder->post_title;
            $desc = $folder->post_content;
            $publish = ( $folder->post_status == 'publish' ) ? ' checked' : '';

        }

        $out .= '<p>' . __( 'Name', 'mif-bpc' ) . ':</p>
        <p><input type="text" name="name" value="' . $name .'"' . $disabled . '></p>
        <p>' . __( 'Description', 'mif-bpc' ) . ':</p>
        <p><textarea name="desc"' . $disabled . '>' . $desc . '</textarea></p>
        <p>' . __( 'Access mode', 'mif-bpc' ) . ':</p>
        <p><label><input type="checkbox" name="publish"' . $publish  . $disabled . '> ' . __( 'Is published', 'mif-bpc' ) . '</label></p><p>';

        if ( bp_is_group() ) {

            $arr['default'] = '';
            $arr['only_admin'] = '';
            $arr['everyone_create'] = '';
            $arr['everyone_delete'] = '';

            $access_mode = $this->get_access_mode_to_folder( $folder_id, false );
            $arr[$access_mode] = ' checked';

            $access_mode_descr = $this->get_access_mode_descr();

            $out .= '<p>' . __( 'Possibilities to upload and delete documents', 'mif-bpc' ) . ':</p>';
            $out .= '<p><label><input type="radio" name="access_mode" value="default"' . $arr['default']  . $disabled . '> ' . $access_mode_descr['default'] . '</label><br />';
            $out .= '<label><input type="radio" name="access_mode" value="only_admin"' . $arr['only_admin']  . $disabled . '> ' . $access_mode_descr['only_admin'] . '</label><br />';
            $out .= '<label><input type="radio" name="access_mode" value="everyone_create"' . $arr['everyone_create']  . $disabled . '> ' . $access_mode_descr['everyone_create'] . '</label><br />';
            $out .= '<label><input type="radio" name="access_mode" value="everyone_delete"' . $arr['everyone_delete']  . $disabled . '> ' . $access_mode_descr['everyone_delete'] . '</label><p>';
        }

        if ( ! $disabled ) $out .= '<input type="submit" value="' . __( 'Save', 'mif-bpc' ) . '"> ';

        $out .= '<input type="button" id="cancel" value="' . __( 'Cancel', 'mif-bpc' ) . '">
        </p>' . $remove_box . '</form>';

        $out .= '</div>';

        return apply_filters( 'mif_bpc_docs_get_folder_settings', $out, $folder_id );
    }



    // 
    // Выводит содержимое страницы системы документов
    // 

    function get_docs_content()
    {
        $out = '';

        // Определить текущие параметры

        if ( bp_is_user() ) {

            $ca = bp_current_action();
            $param = bp_action_variable( 0 );

        } elseif ( bp_is_group() ) {

            $ca = bp_action_variable( 0 );
            $param = bp_action_variable( 1 );

            // if ( is_numeric( $ca ) ) {

            //     $param = $ca;
            //     $ca = 'folder';

            // }

        } else {

            return false;

        }

        // Вывести содержимое согласно параметрам

        if ( $ca == 'new-folder' ) {

            // Создание новой папки

            $out .= $this->get_folder_settings();

        } elseif ( $ca == 'folder' && is_numeric( $param ) ) {

            // Отобразить страницу папки

            $out .= $this->get_folder_content( $param );

        } elseif ( $ca == 'stat' ) {

            // Отобразить статистику пользователя

            $out .= $this->get_user_stat();

        } else {

            // Главная страница системы документов - папки и др.

            $out .= $this->get_folders();
            $out .= $this->get_folder_statusbar();
            $out .= $this->get_folder_nonce( 'all-folders' );

        }

        return apply_filters( 'mif_bpc_docs_get_docs_content', $out, $ca );
    }    



    // 
    // Все documents, расположенные в папке
    // 

    function get_docs_collection( $folder_id, $page = 1, $trashed = false )
    {
        $folder = get_post( $folder_id );

        if ( ! $this->is_folder( $folder_id ) ) return;
        
        if ( ! $this->is_access( $folder_id, 'read' ) ) {

            $out = mif_bpc_message( __( 'Access is restricted', 'mif-bpc' ) );
            return apply_filters( 'mif_bpc_docs_get_docs_collection_access_denied', $out, $folder_id );

        }

        $out = '';

        if ( $folder->post_status == 'private' ) $out .= $this->folder_publisher_tool( $folder_id );

        $sortable = ( $this->is_access( $folder_id, 'write' ) ) ? ' sortable' : '';

        if ( $page === 1 ) $out .= '<div class="collection' . $sortable . ' response-box clearfix">';

        if ( $folder->post_status == 'trash' ) {
            
            $out .= $this->folder_restore_delete_tool( $folder_id );
            $trashed = true;

        }


        $docs = $this->get_docs_collection_data( $folder_id, $page, $trashed );

        if ( $docs ) {

            $arr = array();
            foreach( $docs as $doc ) $arr[] = $this->get_doc_item( $doc );

            $out .= implode( "\n", $arr );
            if ( count( $docs ) == $this->docs_on_page ) $out .= $this->get_more_button( $page, array( 'folder_id' => $folder_id ) );
        
        } else {

            if ( $page === 1 ) $out .= '</div><div class="folder-is-empty-msg">' . mif_bpc_message( __( 'Documents were not located', 'mif-bpc' ) ) . '</div><div>';
            
        }

        if ( $page === 1 ) $out .= '</div>';

        return apply_filters( 'mif_bpc_docs_get_docs_collection', $out, $page, $folder_id );
    }




    // 
    // Окно публикации приватного документа
    // 

    function doc_publisher_tool( $doc_id )
    {
        if ( ! $this->is_doc( $doc_id ) ) return;

        $out = '';

        $out .= __( 'The document isn’t published and only you can access it', 'mif-bpc' );
        $out .= '<form>
        <input type="button" name="publish" class="publish" value="' . __( 'Publish', 'mif-bpc' ) . '">
        <input type="hidden" name="item_id" value="' . $doc_id . '">
        </form>';

        $ret = mif_bpc_message( $out, 'warning doc-publisher' );

        return apply_filters( 'mif_bpc_docs_doc_publisher_tool', $ret, $out, $doc_id );
    }




    // 
    // Окно восстановления or окончательного удаления документа
    // 

    function doc_restore_delete_tool( $doc_id )
    {
        if ( ! $this->is_doc( $doc_id ) ) return;

        $out = '';

        $out .= __( 'The document is in the Recycle Bin and will be deleted permanently in a few days. While it hasn’t happened, you can restore it or delete from the Recycle Bin by yourself.', 'mif-bpc' );
        $out .= '<div class="doc-restore-delete">
        <form>
        <input type="button" name="delete" class="delete" value="' . __( 'Delete permanently', 'mif-bpc' ) . '">
        <input type="button" name="restore" class="restore" value="' . __( 'Restore', 'mif-bpc' ) . '">
        <input type="hidden" name="item_id" value="' . $doc_id . '">
        </form>
        </div>';

        $ret = mif_bpc_message( $out, 'warning' );

        return apply_filters( 'mif_bpc_docs_doc_restore_delete_tool', $ret, $out, $doc_id );
    }



    // 
    // Окно публикации приватной папки
    // 

    function folder_publisher_tool( $folder_id )
    {
        if ( ! $this->is_folder( $folder_id ) ) return;

        $out = '';

        $out .= __( 'The folder isn’t published and only you can access it', 'mif-bpc' );
        $out .= '<form>
        <input type="button" name="publish" class="publish" value="' . __( 'Publish', 'mif-bpc' ) . '">
        <input type="hidden" name="item_id" value="' . $folder_id . '">
        </form>';

        $ret = mif_bpc_message( $out, 'warning folder-publisher' );

        return apply_filters( 'mif_bpc_docs_folder_publisher_tool', $ret, $out, $folder_id );
    }




    // 
    // Окно восстановления or окончательного удаления папки
    // 

    function folder_restore_delete_tool( $folder_id )
    {
        if ( ! $this->is_folder( $folder_id ) ) return;

        $out = '';

        $out .= __( 'The folder is in the Recycle Bin and will be deleted permanently in a few days. While it hasn’t happened, you can restore it or delete from the Recycle Bin by yourself.', 'mif-bpc' );
        $out .= '<div class="folder-restore-delete">
        <form>
        <input type="button" name="delete" class="delete" value="' . __( 'Delete permanently', 'mif-bpc' ) . '">
        <input type="button" name="restore" class="restore" value="' . __( 'Restore', 'mif-bpc' ) . '">
        <input type="hidden" name="item_id" value="' . $folder_id . '">
        </form>
        </div>';

        $ret = mif_bpc_message( $out, 'warning' );

        return apply_filters( 'mif_bpc_docs_folder_restore_delete_tool', $ret, $out, $folder_id );
    }

   


    // 
    // Выводит кнопку "Show more"
    // 

    function get_more_button( $page, $args = array() )
    {
        $out = '';

        $out .= '<div class="more"><form>
        <button>' . __( 'Show more', 'mif-bpc' ) . '</button>
        <i class="fa fa-spinner fa-spin fa-3x fa-fw"></i>';
        $out .= '<input type="hidden" name="_wpnonce" value="' . wp_create_nonce( 'mif-bpc-docs-nonce' ) . '">';

        foreach ( $args as $key => $value ) $out .= '<input type="hidden" name="' . $key . '" value="' . $value . '">';

        $next_page = (int) $page + 1;
        $out .= '<input type="hidden" name="page" value="' . $next_page . '">';
        $out .= '</form></div>';

        return apply_filters( 'mif_bpc_docs_get_more_button', $out, $page, $args );
    }



    // 
    // Выводит страницу статистики пользователя
    // 

    function get_user_stat()
    {
        $out = '';

        $out .= '<div class="stat">';
        $out .= '<p>' . __( 'This page displays statistics for all personal documents of user and group profile', 'mif-bpc' ) . '</p>';
        $out .= '<span class="one">' . __( 'Used', 'mif-bpc' ) . ':</span> ';
        $out .= '<span class="two">' . mif_bpc_format_file_size( $this->get_user_size() ) . '</span>';
        $out .= '<p>&nbsp;';
        $out .= '</div>';

        return apply_filters( 'mif_bpc_docs_get_user_stat', $out );
    }



    // 
    // Выводит изображение документа
    // 

    function get_doc_item( $doc = NULL )
    {
        $left = '';
        $right = '';
     
        if ( $doc == NULL ) {

            $logo = '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i>';
            $name = '';
            $loading = ' loading';
            // $sortable = '';
            $a1 = '';
            $a2 = '';
            $remove = '';
            $download = '';
            $id = 'item-tpl';
            $order = '';
            $status = '';
            $title = '';
            $cover = '';
            $cover_class = '';
            
        } else {
            
            if ( is_numeric( $doc ) ) $doc = get_post( $doc );
            
            // $name = $doc->post_title;
            $name = $this->get_doc_name( $doc );
            $logo = $this->get_file_logo( $doc );
            $loading = '';
            // $sortable = ' sortable';
            $id = 'doc-' . $doc->ID;
            $order = $doc->menu_order;
            $status = ' ' . $doc->post_status;
            $cover = '';
            $cover_class = '';

            $url = $this->get_doc_url( $doc->ID );
            $a1 = '<a href="' . $url . '">';
            $a2 = '</a>';
            if ( $this->is_access( $doc, 'delete' ) ) $left = '<a href="' . $url . 'remove/" data-item-id="' . $doc->ID . '" class="button item-remove left" title="' . __( 'Delete', 'mif-bpc' ) . '"><i class="fa fa-times"></i></a>';

            $doc_type = $this->get_doc_type( $doc );

            if ( $doc_type == 'file' || $doc_type == 'image' ) {
                
                $right = '<a href="' . $url . 'download/" class="button doc-download right" title="' . __( 'Download', 'mif-bpc' ) . '"><i class="fa fa-download"></i></a>';
                
            } elseif ( $doc_type == 'link' ) {
                
                $right = '<a href="' . $doc->post_content . '" target="blank" class="button doc-download right" title="' . __( 'Open', 'mif-bpc' ) . '"><i class="fa fa-arrow-up"></i></a>';
                
            } else {
                
                $right = '';
                
            }
            
            // // Картинка для картинок
            
            // if ( $doc_type == 'image' ) {

            //     $cover = ' style="background: url(' . $url . 'download/);"';
            //     $cover_class = ' cover';

            // }

            $title = '';
            
            if ( $doc->post_status == 'trash' ) {

                if ( $this->is_access( $doc, 'delete' ) ) $left = '<a href="' . $url . 'restore/" data-item-id="' . $doc->ID . '" class="button item-remove restore left" title="' . __( 'Restore', 'mif-bpc' ) . '"><i class="fa fa-undo"></i></a>';
                if ( $this->is_access( $doc, 'delete' ) ) $right = '<a href="' . $url . 'remove/" data-item-id="' . $doc->ID . '" class="button item-remove right" title="' . __( 'Delete permanently', 'mif-bpc' ) . '"><i class="fa fa-times"></i></a>';

                $title = ' title="' . __( 'Document is in the Recycle Bin', 'mif-bpc' ) . '"';

            }

            if ( $doc->post_status == 'private' ) {

                $title = ' title="' . __( 'Only you can access the document', 'mif-bpc' ) . '"';

            }

        }


        $out = '<div class="file' . $status . $loading . $cover_class . '" id="' . $id . '" data-order="' . $order . '"' . $title . $cover . '>
        ' . $a1 . '
        <span class="logo">' . $logo . '</span>
        <span class="name">' . $name . '</span>
        ' . $a2 . '
        <span class="reorder-loading right"><i class="fa fa-spinner fa-spin fa-fw"></i></span>
        ' . $left . '
        ' . $right . '
        </div>';

        return apply_filters( 'mif_bpc_docs_get_doc_item', $out, $doc );
    }



    // 
    // Выводит изображение папки
    // 

    function get_folder_item( $folder = NULL )
    {
        if ( is_numeric( $folder ) ) $folder = get_post( $folder );

        if ( ! $this->is_folder( $folder->ID ) ) return;

        $data = $this->get_folder_size( $folder->ID );

        $left = '';
        $right = '';
        $title = '';
        $url = $this->get_folder_url( $folder->ID );


        if ( $folder->post_status == 'trash' ) {

            if ( $this->is_access( $folder, 'delete' ) ) $left = '<a href="' . $url . '/restore/" data-item-id="' . $folder->ID . '" class="button item-remove restore left" title="' . __( 'Restore', 'mif-bpc' ) . '"><i class="fa fa-undo"></i></a>';
            if ( $this->is_access( $folder, 'delete' ) ) $right = '<a href="' . $url . '/remove/" data-item-id="' . $folder->ID . '" class="button item-remove right" title="' . __( 'Delete permanently', 'mif-bpc' ) . '"><i class="fa fa-times"></i></a>';

            $title = ' title="' . __( 'The folder is in the Recycle Bin', 'mif-bpc' ) . '"';

        } else {

            if ( $this->is_access( $folder, 'delete' ) ) if ( $data['count'] == 0 ) $left = '<a href="' . $url . '/remove/" data-item-id="' . $folder->ID . '" class="button item-remove left" title="' . __( 'Delete', 'mif-bpc' ) . '"><i class="fa fa-times"></i></a>';

        }

        if ( $folder->post_status == 'private' ) {

            $title = ' title="' . __( 'Only you can access the folder', 'mif-bpc' ) . '"';

        }

        $cover_id = $this->get_folder_cover( $folder->ID );

        if ( $cover_id ) {

            $cover_url = $this->get_doc_url( $cover_id );
            $cover = ' style="background: url(' . $cover_url . 'download/?cover=show);"';
            $cover_class = ' cover';

        }

        $out = '<div class="file folder ' . $folder->post_status . $cover_class . '" id="folder-' . $folder->ID . '"' . $title . $cover . '>
        <a href="' . $this->get_folder_url( $folder->ID ) . '">
        <span class="logo"><i class="fa fa-folder-open-o fa-3x"></i></span>
        <span class="name">' . $folder->post_title . '</span>
        <span class="count right">' . $data['count'] . '</span>
        <span class="reorder-loading right"><i class="fa fa-spinner fa-spin fa-fw"></i></span>
        ' . $left . '
        ' . $right . '
        </a>
        </div>';

        return apply_filters( 'mif_bpc_docs_get_folder_item', $out, $folder );
    }



    //
    // Оформление документа or папки в списках (лента активности, диалоги)
    //

    function get_item_inline( $item_id = NULL, $hidden_field = false )
    {
        $out = '';
        if ( $this->is_doc( $item_id ) ) {


            if ( ! $this->is_access( $item_id, 'read' ) ) return;
    
            $doc = get_post( $item_id );

            $name = $this->get_doc_name( $doc );
            $logo = $this->get_file_logo( $doc, 1 );
            $url = $this->get_doc_url( $doc->ID );

            $doc_type = $this->get_doc_type( $doc );

            if ( $doc_type == 'image' ) {
                
                $out .= '<span class="docs-item image clearfix"><a href="' . $url . 'download/"><img src="' . $url . 'download/"></a>';

            } elseif ( $doc_type == 'file' ) {

                $out .= '<span class="docs-item file clearfix"><a href="' . $url . 'download/"><span class="icon">' . $logo . '</span><span class="name">' . $name . '</span></a>';

            } else {

                $out .= '<span class="docs-item file clearfix"><a href="' . $url . '"><span class="icon">' . $logo . '</span><span class="name">' . $name . '</span></a>';

            } 

            if ( $hidden_field ) $out .= '<input type="hidden" class="attachment" name="a_id[]" value="' . $item_id . '">';

            $out .= '</span>';

        } elseif ( $this->is_folder( $item_id ) ) {

            if ( ! $this->is_access( $itemr_id, 'read' ) ) return;
    
            $folder = get_post( $item_id );

            $name = $folder->post_title;
            $url = $this->get_folder_url( $folder->ID );
            $data = $this->get_folder_size( $folder->ID );

            $out .= '<span class="docs-item folder clearfix"><a href="' . $url . '"><span class="icon"><i class="fa fa-folder-open-o"></i></span><span class="name">' . $name . '</span></a></span>';

        } elseif ( $item_id == NULL ) {

            $out .= '<span class="docs-item file clearfix"><span class="icon"><i class="fa fa-spinner fa-spin fa-fw"></i></span><span class="name"></span></span>';

        }


        return apply_filters( 'mif_bpc_docs_get_item_inline', $out, $item_id );
    }


    // 
    // Выводит заголовок на странице папки
    // 

    function get_folder_header( $folder_id = NULL )
    {
        $folder = get_post( $folder_id );

        $out = '<h2><a href="' . $this->get_docs_url() . '/">' . __( 'Folders', 'mif-bpc' ) . '</a> /  
        <a href="' . $this->get_folder_url( $folder->ID ) . '">' . $folder->post_title . '</a></h2>
        <div class="folder-description">' . $folder->post_content . '</div>';

        return apply_filters( 'mif_bpc_docs_get_folder_header', $out, $folder );
    }



    // 
    // Выводит описание режима доступа к папке
    // 

    function get_folder_access_mode( $folder_id = NULL )
    {
        $out = '';
        $access_mode_descr = $this->get_access_mode_descr();

        if ( $this->place( $folder_id ) == 'group' ) {

            $access_mode = $this->get_access_mode_to_folder( $folder_id, true );
            if ( isset( $access_mode_descr[$access_mode] ) ) $out .= '<div class="access_mode"><span class="one">' . __( 'Access level', 'mif-bpc' ) . ':</span> <span class="two">' . $access_mode_descr[$access_mode] . '</span></div>';

            $folder = get_post( $folder_id );
            
            $avatar = get_avatar( $folder->post_author, apply_filters( 'mif_bpc_docs_avatar_size', $this->avatar_size ) );
            $author = mif_bpc_get_member_name( $folder->post_author );

            $out .= '<div class="folder_meta_info clearfix">
                    <div class="owner"><a href="' . bp_core_get_user_domain( $doc->post_author ) . '" target="blank"><span class="one">' . $avatar . '</span><span class="two">' . $author . '</span></a></div>
                    </div>';
        }

        return apply_filters( 'mif_bpc_docs_get_folder_access_mode', $out, $folder_id );
    }



    // 
    // Содержимое папки
    // 

    function get_folder_content( $folder_id = NULL, $msg = false )
    {
        if ( ! $this->is_folder( $folder_id ) ) return;
        
        $out = '';
        // $folder = get_post( $folder_id );

        $out .= $this->get_folder_header( $folder_id );
        $out .= $this->get_upload_form( $folder_id );

        if ( $msg ) $out .= mif_bpc_message( $msg );

        $out .= $this->get_docs_collection( $folder_id );
        $out .= $this->get_folder_statusbar( $folder_id );
        $out .= $this->get_folder_nonce( $folder_id );
             
        return apply_filters( 'mif_bpc_docs_get_folder_content', $out, $folder_id );
    }



    //
    // Содержимое страницы документа
    //

    function get_doc_content( $doc, $msg = false )
    {
        if ( ! is_object( $doc ) ) $doc = get_post( $doc );
        if ( empty( $doc ) ) return;

        if ( ! $this->is_access( $doc, 'read' ) ) {

            $out = mif_bpc_message( __( 'Access is restricted', 'mif-bpc' ) );
            return apply_filters( 'mif_bpc_docs_get_doc_content_access_denied', $out, $doc );
        }

        $out = '<div class="doc clearfix">';

        if ( $msg ) $out .= mif_bpc_message( $msg );

        if ( $doc->post_status == 'private' ) $out .= $this->doc_publisher_tool( $doc->ID );
        if ( $doc->post_status == 'trash' ) $out .= $this->doc_restore_delete_tool( $doc->ID );

        $doc_type = $this->get_doc_type( $doc );
        $url = $this->get_doc_url( $doc->ID ) . 'download';
        $html = $doc->the_content;

        // Если link, то решить, отображать ее как HTML or как простую ссылку (оформляется как файл)

        if ( $doc_type == 'link' ) {

            $html = wp_oembed_get( $doc->post_content );

            if ( $html  ) {

                $doc_type = 'html';

            } else {

                $doc_type = 'file';
                $url = $doc->post_content;

            }

        }

        // Показать HTML (из базы данных, or сформироанную выше через oembed)

        if ( $doc_type == 'html' ) {

            $name = ( preg_match( '/^https?:\/\//', $doc->post_title ) ) ? '' : '<div class="name">' . $doc->post_title . '</div>';

            $out .= '
            <div class="html">' . $html . '</div>
            <div>
                ' . $name . '
                <div class="description">' . $doc->post_excerpt . '</div>
            </div>';

        }

        // Показать файл (or простую ссылку)

        if ( $doc_type == 'file' ) {

            $item = $this->get_file_logo( $doc );

            $out .= '
            <div class="file">
                <a href="' . $url . '"><span class="item">' . $item . '</span></a>
            </div>
            <div>
                <div class="name"><a href="' . $url . '">' . $this->get_doc_name( $doc ) . '</a></div>
                <div class="description">' . $doc->post_excerpt . '</div>
            </div>';

        } 
        
        // Показать картинку (целиком)

        if ( $doc_type == 'image' ) {

            // $url = $this->get_docs_url() . '/' . $doc->ID . '/download';

            $out .= '
            <div class="image">
                <a href="' . $url . '"><img src="' . $url . '"></a>
            </div>
            <div>
                <div class="name"><span class="one">' . __( 'File', 'mif-bpc' ) . ':</span> <span class="two"><a href="' . $url . '">' . $doc->post_title . '</a></span></div>
                <div class="description">' . $doc->post_excerpt . '</div>
            </div>';

        } 
        
        $out .= '</div>';

        $out .= $this->get_doc_statusbar( $doc->ID );
        $out .= $this->get_doc_nonce( $doc->ID );
       
        return apply_filters( 'mif_bpc_docs_get_doc_content', $out, $doc );
    }



    //
    // Выводит мета-информацию на страницу документа
    //

    function get_doc_meta( $doc = NULL )
    {
        $out = '';

        if ( $doc == NULL ) $doc = $this->get_doc_data();
        if ( ! is_object( $doc ) ) $doc = get_post( $doc );
        if ( empty( $doc ) ) return;

        $out .= $this->get_folder( $doc );
        $out .= $this->get_group( $doc );
        $out .= $this->get_date( $doc );
        $out .= $this->get_owner( $doc );
        // $out .= $this->get_prev( $doc );
        // $out .= $this->get_next( $doc );
        
        return apply_filters( 'mif_bpc_docs_get_meta', $out, $doc );
    }




    //
    // Содержимое страницы документа
    //

    function get_doc_settings( $doc )
    {
        if ( ! is_object( $doc ) ) $doc = get_post( $doc );
        if ( empty( $doc ) ) return;

        $out = '<div class="doc-settings clearfix">';

        $out .= '<h2>' . __( 'Document options', 'mif-bpc' ) . '</h2>';

        $remove_box = '<p><a href="' . $this->get_doc_url( $doc->ID ) . '" class="remove-box-toggle dotted">' . __( 'Delete document', 'mif-bpc' ) . '</a></p>
        <div class="remove-box">
        <div class="message warning">
        <p>' . __( 'The document will be sent to the Recycle Bin and will be deleted permanently in a few days. While the document is stored in the Recycle Bin, you can restore it.', 'mif-bpc' ) . '</p>
        <p><input type="button" class="remove to-trash" value="' . __( 'Delete', 'mif-bpc' ) . '"></p>
        </div>
        </div>';

        $disabled = '';
        if ( $doc->post_status == 'trash' ) {

            $out .= $this->doc_restore_delete_tool( $doc->ID );
            $disabled = ' disabled';
            $remove_box = '';

        }

        $out .= '<form id="doc-settings" class="' . $doc->post_status . '">
        <input type="hidden" name="doc_id" value="' . $doc->ID . '">
        <input type="hidden" name="_wpnonce" value="' . wp_create_nonce( 'mif-bpc-docs-doc-settings-nonce' ) . '">';

        $name = $doc->post_title;
        $desc = $doc->post_excerpt;
        $publish = ( $doc->post_status == 'publish' ) ? ' checked' : '';

        $out .= '<p>' . __( 'Name', 'mif-bpc' ) . ':</p>
        <p><input type="text" name="name" value="' . $name .'"' . $disabled . '></p>
        <p>' . __( 'Description', 'mif-bpc' ) . ':</p>
        <p><textarea name="desc"' . $disabled . '>' . $desc . '</textarea></p>
        <p>' . __( 'Access mode', 'mif-bpc' ) . ':</p>
        <p><label><input type="checkbox" name="publish"' . $publish  . $disabled . '> ' . __( 'Is published', 'mif-bpc' ) . '</label></p><p>';

        if ( ! $disabled ) $out .= '<input type="submit" value="' . __( 'Save', 'mif-bpc' ) . '"> ';

        $out .= '<input type="button" id="cancel" value="' . __( 'Cancel', 'mif-bpc' ) . '">
        </p>' . $remove_box . '</form>';

        $out .= '</div>';
        
        return apply_filters( 'mif_bpc_docs_get_doc_settings', $out, $doc );
    }




    // 
    // Выводит nonce-поля и другую информацию для поддержки AJAX-запросов на странице папки
    // 

    function get_folder_nonce( $folder_id = NULL )
    {
        $out = '';
        $out .= '<input type="hidden" id="docs-folder-nonce" value="' . wp_create_nonce( 'mif-bpc-docs-nonce' ) . '">';
        
        if ( is_numeric( $folder_id ) ) $out .= '<input type="hidden" name="folder_id" id="docs-folder-id" value="' . $folder_id . '">';
        if ( $folder_id == 'all-folders' ) $out .= '<input type="hidden" name="all_folders" id="docs-all-folders" value="on">';

        return apply_filters( 'mif_bpc_docs_get_folder_nonce', $out, $folder_id );
    }




    // 
    // Выводит nonce-поля и другую информацию для поддержки AJAX-запросов на странице документа
    // 

    function get_doc_nonce( $doc_id = NULL )
    {
        $out = '';
        $out .= '<input type="hidden" id="docs-doc-nonce" value="' . wp_create_nonce( 'mif-bpc-docs-nonce' ) . '">';
        
        if ( is_numeric( $doc_id ) ) $out .= '<input type="hidden" name="doc_id" id="docs-doc-id" value="' . $doc_id . '">';

        return apply_filters( 'mif_bpc_docs_get_doc_nonce', $out, $doc_id );
    }



    // 
    // Выводит статусную строку документа
    // 

    function get_doc_statusbar( $doc = NULL )
    {
        if ( $doc == NULL) $doc = $this->get_doc_data();
        if ( is_numeric( $doc ) ) $doc = get_post( $doc );

        if ( ! $this->is_doc( $doc->ID ) ) return;

        $out = '';

        $out .= '<div class="statusbar"><span class="info">&nbsp;</span><span class="tools">';

        if ( $this->is_access( $doc, 'write' ) ) $out .= '<span class="item"><span class="two" title="' . __( 'Options', 'mif-bpc' ) . '"><a href="' . trailingslashit( $this->get_doc_url( $doc->ID ) ) . 'settings/" id="doc-settings"><i class="fa fa-cog"></i></a></span></span>';

        if ( bp_loggedin_user_id() && $this->is_access( $doc, 'read' ) ) $out .= '<span class="item"><span class="two" title="' . __( 'Publish in the activity feed', 'mif-bpc' ) . '"><a href="' . $this->get_repost_link( $doc ) . '" id="repost"><i class="fa fa-share"></i></a></span></span>';

        $out .= '</span></div>';

        return apply_filters( 'mif_bpc_docs_get_doc_statusbar', $out, $doc );
    }



    // 
    // Получает ссылку для репоста документа
    // 

    function get_repost_link( $doc )
    {
        $place = $this->place( $doc );
        $place_id = $this->place( $doc, true );

        if ( $place == 'group' && groups_is_user_member( bp_loggedin_user_id(), $place_id ) ) {

            $group = groups_get_group( $place_id );
            $url = bp_get_group_permalink( $group );

        } else {

            $url = bp_core_get_user_domain( bp_loggedin_user_id() );

        }
        
        $link =  wp_nonce_url( $url . '?doc=' . $doc->ID, 'mif_bpc_docs_repost_button' );
        return apply_filters( 'mif_bpc_docs_get_repost_link', $link, $doc );
    }



    // 
    // Выводит информацию документа в статусной строке
    // 

    function get_doc_statusbar_info( $doc_id = NULL )
    {
        $doc = get_post( $doc_id );
        $size = $this->get_doc_size( $doc );
        $ext = $this->get_doc_ext( $doc->post_title );
        $type = ( in_array( $this->get_doc_type( $doc_id ), array( 'image', 'file' ) ) ) ? mb_strtoupper( $ext ) : '<a href="' . $doc->post_content . '" target="blank">' . __( 'link', 'mif-bpc' ) . '</a>';

        // $out = '<span class="one">' . __( 'Volume', 'mif-bpc' ) . ':</span> <span class="two">' . mif_bpc_format_file_size( $size ) . '</span>';
        $out = '<span class="two">' . mif_bpc_format_file_size( $size ) . '</span><span class="one">' . $type . '</span>';

        return apply_filters( 'mif_bpc_docs_get_doc_statusbar_info', $out, $folder_id, $data );
    }






    // 
    // Выводит статусную строку папки
    // 

    function get_folder_statusbar( $folder_id = NULL )
    {
        if ( $folder_id == NULL && bp_current_action() == 'folder' && is_numeric( bp_action_variable( 0 ) ) ) $folder_id = bp_action_variable( 0 );

        $out = '';

        $out .= '<div class="statusbar"><span class="info">&nbsp;</span><span class="tools">';

        if ( $this->is_access( $folder_id, 'write' ) ) $out .= '<span class="item"><label title="' . __( 'Show deleted', 'mif-bpc' ) . '"><span class="one"><input type="checkbox" id="show-remove-docs"></span><span class="two"><i class="fa fa-trash-o"></i></span></label></span>';

        if ( $this->is_folder( $folder_id ) ) {

            $folder = get_post( $folder_id );
            if ( $this->is_admin() || $folder->post_author == bp_loggedin_user_id() ) $out .= '<span class="item"><span class="two" title="' . __( 'Settings', 'mif-bpc' ) . '"><a href="' . trailingslashit( $this->get_folder_url( $folder_id ) ) . 'settings/" id="folder-settings"><i class="fa fa-cog"></i></a></span></span>';

        }

        if ( $this->is_folder( $folder_id ) && bp_loggedin_user_id() && $this->is_access( $folder, 'read' ) ) $out .= '<span class="item"><span class="two" title="' . __( 'Publish in the activity feed', 'mif-bpc' ) . '"><a href="' . $this->get_repost_link( $folder ) . '" id="repost"><i class="fa fa-share"></i></a></span></span>';

        $out .= '</span></div>';

        $out .= $this->get_folder_access_mode( $folder_id );

        return apply_filters( 'mif_bpc_docs_get_folder_statusbar', $out, $folder_id );
    }



    // 
    // Выводит информацию папки в статусной строке
    // 

    function get_folder_statusbar_info( $folder_id = NULL )
    {
        if ( $folder_id == NULL ) {

            if ( ! ( bp_current_action() == 'folder' && is_numeric( bp_action_variable( 0 ) ) ) ) return;
            $folder_id = bp_action_variable( 0 );

        }

        $data = $this->get_folder_size( $folder_id );

        $out = '<span class="one">' . __( 'Documents', 'mif-bpc' ) . ':</span> <span class="two">' . $data['count'] . '</span>
        <span class="one">' . __( 'Volume', 'mif-bpc' ) . ':</span> <span class="two">' . mif_bpc_format_file_size( $data['size'] ) . '</span>';

        return apply_filters( 'mif_bpc_docs_get_folder_statusbar_info', $out, $folder_id, $data );
    }




    // 
    // Выводит информацию всех папок в статусной строке
    // 

    function get_all_folders_statusbar_info()
    {
        $data = $this->get_all_folders_size();

        $out = '<span class="one">' . __( 'Folders', 'mif-bpc' ) . ':</span> <span class="two">' . $data['count'] . '</span>
        <span class="one">' . __( 'Total volume', 'mif-bpc' ) . ':</span> <span class="two">' . mif_bpc_format_file_size( $data['size'] ) . '</span>';

        return apply_filters( 'mif_bpc_docs_get_folder_statusbar_info', $out, $folder_id, $data );
    }



    // 
    // Выводит сообщение об ошибке
    // 

    function error_msg( $s = '000' )
    {
        $out = mif_bpc_message( sprintf( __( 'Error %s. Something went wrong', 'mif-bpc' ), $s ), 'error' );
        return apply_filters( 'mif_bpc_docs_error_msg', $out, $s );
    }



    // 
    // Логотип файла
    // 

    function get_file_logo( $doc, $size = 3 )
    {
        $type = ( preg_match( '/^http/', $doc->post_content ) ) ? $doc->post_content : $doc->post_title;
        return apply_filters( 'mif_bpc_docs_get_file_logo', mif_bpc_get_file_icon( $type, 'fa-' . $size . 'x' ), $doc );
    }
    //
    // Выводит имя документа
    //

    function get_name()
    {
        $out = '';

        $doc = $this->get_doc_data();
        if ( empty( $doc ) ) return;

        $out .= $this->get_doc_name( $doc );

        return apply_filters( 'mif_bpc_docs_get_name', $out, $doc );
    }



    //
    // Выводит документ на страницу документа
    //

    function get_doc()
    {
        $out = '';

        $doc = $this->get_doc_data();
        if ( empty( $doc ) ) return;

        $out .= $this->get_doc_content( $doc );

        return apply_filters( 'mif_bpc_docs_get_doc', $out, $doc );
    }



    //
    // Выводит владельца документа
    //

    function get_owner( $doc = NULL )
    {
        $out = '';

        if ( $doc == NULL ) $doc = $this->get_doc_data();
        if ( ! is_object( $doc ) ) $doc = get_post( $doc );
        if ( empty( $doc ) ) return;

        $avatar = get_avatar( $doc->post_author, apply_filters( 'mif_bpc_docs_avatar_size', $this->avatar_size ) );
        $author = mif_bpc_get_member_name( $doc->post_author );

        $out .= '<div class="owner clearfix"><a href="' . bp_core_get_user_domain( $doc->post_author ) . '" target="blank"><span class="one">' . $avatar . '</span><span class="two">' . $author . '</span></a></div>';

        return apply_filters( 'mif_bpc_docs_get_owner', $out, $doc );
    }


    //
    // Выводит папку документа
    //

    function get_folder( $doc = NULL )
    {
        $out = '';

        if ( $doc == NULL ) $doc = $this->get_doc_data();
        if ( ! is_object( $doc ) ) $doc = get_post( $doc );
        if ( empty( $doc ) ) return;

        $folder = get_post( $doc->post_parent );
        if ( empty( $folder ) ) return;

        $folder_url = $this->get_folder_url( $folder->ID );

        $out .= '<div class="folder"><span class="one">' . __( 'Folder', 'mif-bpc' ) . ':</span> <span class="two"><a href="' . $folder_url . '">' . $folder->post_title . '</a></span></div>';

        return apply_filters( 'mif_bpc_docs_get_folder', $out, $doc, $folder );
    }



    //
    // Выводит группу документа
    //

    function get_group( $doc = NULL )
    {
        if ( $doc == NULL ) $doc = $this->get_doc_data();

        $out = '';

        $parent_data = get_post_meta( $doc->post_parent, $this->folder_parent_meta_key, true );

        if ( $parent_data ) {

            $arr = (array) explode( '-', $parent_data );
            $item_id = (int) array_pop( $arr );
            $mode = implode( '-', $arr );

            if ( $mode == 'group' ) {

                $group = groups_get_group( $item_id );

                $url = trailingslashit( bp_get_group_permalink( $group ) ) . $this->slug;
                $name = bp_get_group_name( $group );
                
                if ( isset( $url ) && isset( $name ) ) $out .= '<div class="group"><span class="one">' . __( 'Group', 'mif-bpc' ) . ':</span> <span class="two"><a href="' . $url . '">' . $name . '</a></span></div>';

            }

        }

        return apply_filters( 'mif_bpc_docs_get_group', $out, $doc );
    }



    //
    // Выводит время размещения документа
    //

    function get_date( $doc = NULL )
    {
        $out = '';

        if ( $doc == NULL ) $doc = $this->get_doc_data();
        if ( ! is_object( $doc ) ) $doc = get_post( $doc );
        if ( empty( $doc ) ) return;

        $txt = ( $doc->post_date_gmt == $doc->post_modified_gmt ) ? __( 'Is published', 'mif-bpc' ) : __( 'Changed', 'mif-bpc' );

        $out .= '<div class="date"><span class="one">' . $txt . ':</span> <span class="two">' . mif_bpc_time_since( $doc->post_modified_gmt ) . '</span></div>';

        return apply_filters( 'mif_bpc_docs_get_date', $out, $doc );
    }



    //
    // Выводит ссылку на следующий документ
    //

    function get_next( $doc = NULL )
    {
        $out = '';

        $out .= '<div class="next"><a href="11"><span>' . __( 'there', 'mif-bpc' ) . '</span> <i class="fa fa-arrow-right"></i></a></div>';

        return apply_filters( 'mif_bpc_docs_get_next', $out, $doc );
    }



    //
    // Выводит ссылку на предыдущий документ
    //

    function get_prev( $doc = NULL )
    {
        $out = '';

        $out .= '<div class="prev"><a href="22"><i class="fa fa-arrow-left"></i> <span>' . __( 'here', 'mif-bpc' ) . '</span></a></div>';

        return apply_filters( 'mif_bpc_docs_get_prev', $out, $doc );
    }


}



?>