<?php

//
// Documents (прикрепленные сообщения в диалогах)
// 
//


defined( 'ABSPATH' ) || exit;



class mif_bpc_docs_dialogues extends mif_bpc_docs_screen {

    function __construct()
    {
      
        parent::__construct();

        add_action( 'mif_bpc_dialogues_get_messages_form_attachment', array( $this, 'docs_form' ), 10, 2 );
        // add_action( 'mif_bpc_get_attachment_item', array( $this, 'attachment_item' ), 10, 2 );

        add_action( 'wp_ajax_mif-bpc-docs-upload-files-dialogues', array( $this, 'ajax_upload_dialogues_helper' ) );

    }



    // //
    // // Изображение документа в сообщениях диалога
    // //

    // function attachment_item( $empty, $item_id )
    // {
    //     $out = '';
        
    //     $item_inline = $this->get_item_inline( $item_id );
        
    //     if ( ! empty( $item_inline ) ) $out = '<span class="attach clearfix">' . $this->get_item_inline( $item_id ) . '</span>';
        
    //     return apply_filters( 'mif_bpc_docs_dialogues_attachment_item', $out, $item_id );
    // }



    //
    // Форма загрузки документов в диалоге
    //

    function docs_form( $empty, $thread_id )
    {
        $out = '';
        
        $out .= '<tr id="attachment-form"><td colspan="3">';

        $out .= '<div class="response-box attach clearfix hidden"></div>
        <div class="template">' . $this->get_item_inline() . '</div>
        <div class="drop-box"><p>' . __( 'Drag files here', 'mif-bpc' ) . '</p>
        <input type="file" name="files[]" multiple="multiple" class="docs-upload-form"></div>
        <input name="MAX_FILE_SIZE" value="' . $this->get_max_upload_size() . '" type="hidden">
        <input name="max_file_error" value="' . __( 'The file is too large', 'mif-bpc' ) . '" type="hidden">
        <input type="hidden" name="upload_nonce" value="' . wp_create_nonce( 'mif-bpc-docs-file-upload-nonce' ) . '">
        <input type="hidden" name="action" value="mif-bpc-docs-upload-files-dialogues">';

        $out .= '</td></tr>';

        return apply_filters( 'mif_bpc_docs_dialogues_docs_form', $out, $thread_id );
    }




    // 
    // Ajax-помощник загрузки файлов
    // 

    function ajax_upload_dialogues_helper()
    {
        check_ajax_referer( 'mif-bpc-docs-file-upload-nonce' );

        $post_id = $this->upload_and_save( 'dialogues_folder' );

        if ( $post_id ) {

            $arr = array( 
                        'item' => $this->get_item_inline( $post_id, true ),
                        'doc_id' => $post_id,
                        );
            $arr = apply_filters( 'mif_bpc_docs_ajax_upload_dialogues_helper', $arr, $user_id, $post_id );

            echo json_encode( $arr );

        } 

        wp_die();
    }

}






?>