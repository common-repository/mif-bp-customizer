<?php

//
// Documents (лента активности)
// 
//


defined( 'ABSPATH' ) || exit;



class mif_bpc_docs_admin extends mif_bpc_docs_core {

    function __construct()
    {
      
        parent::__construct();

        // add_action( 'bp_init', array( $this, 'gallery_convert' ) );
        
    }


    //
    // Конвертация всех старых папок и документов
    //
    // http://edu.vspu.ru/members/admin/?gallery_converted=user
    // http://edu.vspu.ru/groups/sandbox/?gallery_converted=group
    //

    function gallery_convert()
    {
		if ( ! isset( $_GET['gallery_converted'] ) ) return;
		if ( ! $this->is_admin() ) return;
        
        global $galleries_template;
        global $medias_template;

		if ( $_GET['gallery_converted'] == 'user' ) {

            $owner_type = 'user';
            // $owner_id = 2;

            $args = array( 'number' => 500, 'orderby' => 'registered' );
            $args['meta_query'] = array(
                                        array(
                                            'key' => 'mif-bpc-gallery-converted',
                                            'compare' => 'NOT EXISTS'
                                        )
                                    );

            $users = get_users( $args );

            foreach ( $users as $user ) {

                $this->gallery_convert_item( $user->ID, $owner_type );
                 
            }


        }

		if ( $_GET['gallery_converted'] == 'group' ) {

            if ( bp_is_user() ) {

                p( 'Do action from group context' );

            } else {

                $args = array( 
                        'type' => 'alphabetical',
                        'per_page' => 50, 
                        'show_hidden' => true,
                        );

                $args['meta_query'] = array(
                                            array(
                                                'key' => 'mif-bpc-gallery-converted',
                                                'compare' => 'NOT EXISTS'
                                            )
                                        );

                $groups = groups_get_groups( $args );

                foreach ( $groups['groups'] as $group ) {

                    $owner_type = 'groups';
                    $this->gallery_convert_item( $group->id, $owner_type );
                    
                }

            }

        }

    }



    //
    // Конвертация старых папок и документов для конкретного пользователя or группы
    //

    function gallery_convert_item( $owner_id, $owner_type )
    {
        global $galleries_template;
        global $medias_template;

        if ( bp_has_galleries( array( 'owner_type' => $owner_type, 'owner_id' => $owner_id, 'filter' => array( 'public', 'private', 'friendsonly' ), 'per_page' => 1000 ) ) ) :

            if ( $owner_type == 'user' ) echo "<pre>\n===== " . mif_bpc_get_member_name( $owner_id ) . " =====\n</pre>";
            if ( $owner_type == 'groups' ) echo "<pre>\n===== " . bp_get_group_name( groups_get_group( $owner_id ) ) . " =====\n</pre>";

            while ( bp_galleries() ) : bp_the_gallery() ;
        
                echo '<pre>';

                echo "\nitem_id => " . $item_id = $galleries_template->gallery->owner_object_id;
                echo "\nmode_id => " . $mode = ( $galleries_template->gallery->owner_object_type == 'user' ) ? 'user' : 'group';
                echo "\nname => " . $name = $galleries_template->gallery->title;
                echo "\ndesc => " . $desc = $galleries_template->gallery->description;
                echo "\npublish => " . $publish = ( $galleries_template->gallery->status == 'private' && $galleries_template->gallery->owner_object_type == 'user' ) ? 'off' : 'on' ;
                echo "\nauthor_id => " . $author_id = $galleries_template->gallery->creator_id;
                echo "\npost_date => " . $post_date = date( 'Y-m-d H:i:s', $galleries_template->gallery->date_created );
                echo "\npost_modified => " . $post_modified = date( 'Y-m-d H:i:s', $galleries_template->gallery->date_updated );
        
                // $folder_id = true;
                $folder_id = false;
                if ( $name ) $folder_id = $this->folder_save( $item_id, $mode, $name, $desc, $publish, $author_id, $post_date, $post_modified );

                $args = array( 'gallery_id' => $galleries_template->gallery->id, 'filter' => array( 'public', 'private', 'friendsonly' ), 'per_page' => 1000 );
                if( $owner_type == 'user' ) $args['user_id'] = $owner_id;

                if ( $folder_id && bp_gallery_has_medias( $args ) ) : 
		            while ( bp_gallery_medias() ) : bp_gallery_the_media();

                        echo "\n";
                        echo "\n--- name => " . $name = $medias_template->media->title;
                        echo "\n--- path => " . $path = ( $medias_template->media->local_orig_path ) ? trailingslashit( bp_get_root_domain() ) . $medias_template->media->local_orig_path : $medias_template->media->remote_url;
                        echo "\n--- user_id => " . $user_id = $medias_template->media->user_id;
                        echo "\n--- folder_id => " . $folder_id;
                        echo "\n--- file_type => " . $file_type = '';
                        echo "\n--- order => " . $order = $medias_template->media->sort_order;
                        echo "\n--- descr => " . $descr = $medias_template->media->description;
                        echo "\n--- post_date => " . $post_date = $medias_template->media->date_updated;
                        echo "\n--- post_modified => " . $post_modified = $medias_template->media->date_updated;
        
                        if ( $name ) $this->doc_save( $name, $path, $user_id, $folder_id, $file_type, $order, $descr, $post_date, $post_modified );

                    endwhile;
                endif; 

                echo '</pre>';
            
            endwhile;

        endif;



        if ( $owner_type == 'user' ) update_user_meta( $owner_id, 'mif-bpc-gallery-converted', 1 );
        if ( $owner_type == 'groups' ) groups_update_groupmeta( $owner_id, 'mif-bpc-gallery-converted', 1 );


        // p('123');

    }



}






?>