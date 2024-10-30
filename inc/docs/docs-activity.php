<?php

//
// Documents (лента активности)
// 
//


defined( 'ABSPATH' ) || exit;



class mif_bpc_docs_activity extends mif_bpc_docs_screen {

    function __construct()
    {
      
        parent::__construct();

        add_action( 'bp_after_activity_post_form', array( $this, 'repost_doc_helper' ) );
        add_action( 'bp_after_activity_post_form', array( $this, 'docs_form' ) );
        add_filter( 'bp_get_activity_content_body', array( $this, 'content_body' ), 5 );
        add_filter( 'bp_get_activity_latest_update_excerpt', array( $this, 'latest_update' ), 10, 2 );

        add_action( 'wp_ajax_mif-bpc-docs-upload-files-activity', array( $this, 'ajax_upload_activity_helper' ) );

    }



    //
    // Форма загрузки документов в ленте активности
    //

    function docs_form()
    {
        if ( isset( $_GET['repost'] ) ) return;
        
        $out = '';
        
        $out .= '<span class="hidden">';

        $out .= '<div id="docs-form" class="docs-form">
        <div class="response-box attach clearfix hidden"></div>
        <div class="template">' . $this->get_item_inline() . '</div>
        <div class="drop-box"><p>' . __( 'Drag photos and files here', 'mif-bpc' ) . '</p>
        <input type="file" name="files[]" multiple="multiple" class="docs-upload-form"></div>
        <input name="MAX_FILE_SIZE" value="' . $this->get_max_upload_size() . '" type="hidden">
        <input name="max_file_error" value="' . __( 'The file is too large', 'mif-bpc' ) . '" type="hidden">
        <input type="hidden" name="upload_nonce" value="' . wp_create_nonce( 'mif-bpc-docs-file-upload-nonce' ) . '">
        <input type="hidden" name="action" value="mif-bpc-docs-upload-files-activity">
        
        <a href="#" class="button file-form-toggle"><i class="fa fa-camera"></i></a>
        </div>';

        $out .= '</span>';

        $out = apply_filters( 'mif_bpc_docs_activity_docs_form', $out );

        echo $out;
    }




    // 
    // Ajax-помощник загрузки файлов
    // 

    function ajax_upload_activity_helper()
    {
        check_ajax_referer( 'mif-bpc-docs-file-upload-nonce' );

        $post_id = $this->upload_and_save( 'activity_stream_folder' );

        if ( $post_id ) {

            $arr = array( 
                        'item' => $this->get_item_inline( $post_id ),
                        'doc_id' => $post_id,
                        );
            $arr = apply_filters( 'mif_bpc_docs_ajax_upload_activity_helper', $arr, $user_id, $post_id );

            echo json_encode( $arr );

        } 

        wp_die();
    }




    //
    // Оформление документов в ленте активности (корректировка последнего обновления)
    //

    function latest_update( $content, $user_id = NULL )
    {
        if ( $user_id == NULL ) return false;

		if ( ! $update = bp_get_user_meta( $user_id, 'bp_latest_update', true ) ) return false;

        $content = $this->content_body( $update['content'] );
        $content = preg_replace( '/span><span/', 'span> <span', $content );

        $content = wp_strip_all_tags( bp_create_excerpt( $content, 358 ) );

        return apply_filters( 'mif_bpc_docs_activity_latest_update', $content, $user_id );
    }




    //
    // Оформление документов в ленте активности
    //

    function content_body( $content )
    {
        $content_copy = $content;

        // Регулярные выражения для поиска опубликованных документов
        // Идентификатор папки or документа должен быть в последней группе поиска
        // Можно уточнить внешним плагином, если планируется обращатся к документам по иным адресам (сокращение ссылок or др.)

        $regexp_arr = apply_filters( 'mif_bpc_docs_activity_content_body_reg_arr', array( 
                                    preg_replace( '/\//', '\/', trailingslashit( bp_get_root_domain() ) . '(' . bp_get_members_root_slug() . '/)?' . '(' . bp_get_groups_root_slug() . '/)?' . '[^/]+/' . $this->slug . '/(folder/)?(\d+)/?' ),
                                    '\[\[(\d+)\]\]', 
                                ) );

        // foreach ( $regexp_arr as $regexp => $num ) $content = preg_replace( '/' . $regexp . '/', $this->get_item( '\1' ), $content );
        foreach ( $regexp_arr as $regexp ) $content = preg_replace_callback( '/' . $regexp . '/', array( $this, 'get_item' ), $content );

        $content = preg_replace( '/span>\s+<span/', 'span><span', $content );
        $content = preg_replace( '/(<span class=\"docs-item.+span>)/', '<span class="attach clearfix">\1</span>', $content );

        return apply_filters( 'mif_bpc_docs_activity_content_body', $content, $content_copy );
    }



    //
    // Функция-помощник замены в регулярном выражении
    //

    function get_item( $matches )
    {
        $item_id = (int) array_pop( $matches );
        $item_activity = $this->get_item_inline( $item_id );
        return apply_filters( 'mif_bpc_docs_activity_get_item', $item_activity, $item_id, $matches );
    }




    //
    // Помощник публикации документа or папки в ленте активности
    //

    function repost_doc_helper()
    {
        if ( !  isset( $_GET['_wpnonce'] ) ) return;
        if( ! wp_verify_nonce( $_GET['_wpnonce'], 'mif_bpc_docs_repost_button' ) ) return;

        if ( $this->is_doc( (int) $_GET['doc'] ) || $this->is_folder( (int) $_GET['doc'] ) ) {

            echo '<input type="hidden" id="doc-repost-id" value="' . (int) $_GET['doc'] . '">';

        }
        
        //
        // Примечание. Наличие этого поля анализирует js-сценарий и выводит данные в форму
        //

    }


}






?>