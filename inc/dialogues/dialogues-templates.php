<?php

//
// Dialogues (функции шаблона)
// 
//


defined( 'ABSPATH' ) || exit;



// 
// Выводит адрес страницы диалогов пользователя
// 

function mif_bpc_the_dialogues_url()
{
    global $mif_bpc_dialogues;
    echo $mif_bpc_dialogues->get_dialogues_url();
}



// 
// Выводит список диалогов
// 

function mif_bpc_the_dialogues_default_threads()
{
    global $mif_bpc_dialogues;

    echo $mif_bpc_dialogues->get_dialogues_default_threads();
    echo $mif_bpc_dialogues->get_hidden_fields();

}



// 
// Выводит страницу по умолчанию
// 

function mif_bpc_the_dialogues_default_page()
{
    global $mif_bpc_dialogues;
    echo $mif_bpc_dialogues->get_dialogues_default_page();
}



//
// Выводит заголовок по умолчанию
//

function mif_bpc_the_dialogues_default_header()
{
    global $mif_bpc_dialogues;
    echo $mif_bpc_dialogues->get_dialogues_default_header();
}



//
// Выводит форму по умолчанию
//

function mif_bpc_the_dialogues_default_form()
{
    global $mif_bpc_dialogues;
    echo $mif_bpc_dialogues->get_dialogues_default_form();
}



// 
// Корректировка прикрепленных файлов (конвертация данных плагина BuddyPress Message Attachment)
// Запустить несколько раз при настройке плагина
// 

function mif_bpc_msgat_convert()
{
    global $bp, $wpdb;
    global $mif_bpc_dialogues;
    
    $posts = get_posts( array(
            'numberposts' => 250,
        	'post_type'   => 'messageattachements',
    ) );

    foreach ( $posts as $post ) {

        $meta = get_post_meta( $post->ID, 'bp_msgat_message_id', true );
        $arr = explode( '=', $meta );

        $sql = $wpdb->prepare( "SELECT id FROM {$bp->messages->table_name_messages} WHERE thread_id = %d AND date_sent = %s", $arr[0], $arr[1] );
        $message_id = $wpdb->get_var( $sql );

        if ( $message_id ) {
            
            if ( bp_messages_update_meta( $message_id, $mif_bpc_dialogues->message_attachment_meta_key, $post->post_excerpt ) ) {

                wp_delete_post( $post->ID );
                echo $post->ID . ', ';

            };
        }
    }
}



?>