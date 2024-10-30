<?php

//
// Configuration фона для страниц пользователя и групп
// 
//


defined( 'ABSPATH' ) || exit;

//
// Description инструмента настройки для группы. 
// Использует методы общего класса mif_bpc_custom_background
//

if ( mif_bpc_options( 'custom-background' ) ) 
    add_action( 'bp_init', 'mif_bpc_custom_background_groups_init' );

function mif_bpc_custom_background_groups_init() {

    class mif_bpc_custom_background_groups extends BP_Group_Extension {

        var $visibility = 'private';
        var $enable_create_step = false;
        var $enable_nav_item = false;
        var $enable_edit_item = true;

        function __construct() 
        {

            $this->name = __( 'Background', 'mif-bpc' );
            $this->slug = 'custom-background';
            $this->nav_item_position = 30;

        }
        
        
        function settings_screen( $group_id = NULL ) 
        {

            echo '<h3>' . __( 'Background image', 'mif-bpc' ) . '</h3>';

            echo mif_bpc_custom_background::admin_page();

        }


        function settings_screen_save( $group_id = NULL ) 
        {

            mif_bpc_custom_background::submit_handler();

        }

    }

    bp_register_group_extension( 'mif_bpc_custom_background_groups' );
}



//
// Общий класс для настройки фона для профиля пользователя и группы
//
//

if ( mif_bpc_options( 'custom-background' ) ) 
    new mif_bpc_custom_background();

class mif_bpc_custom_background {
  
    function __construct() 
    {

        add_filter( 'body_class', array( $this, 'get_body_class' ), 30 );
        add_action( 'wp_head', array( $this, 'add_css' ) );
        add_action( 'bp_setup_nav', array( $this, 'setup_nav' ) );
        add_action( 'bp_init', array( $this, 'settings_save' ) );
        
    }


    public function setup_nav()
    {
        global $bp;

        if ( bp_is_user() ) {

            $parent_url = $bp->loggedin_user->domain . $bp->profile->slug . '/';
            $parent_slug = $bp->profile->slug;
            $position = 40;

        } else {

            return false;

        }

        bp_core_new_subnav_item( array( 
                                    'name' => __( 'Background', 'mif-bpc' ), 
                                    'slug' => 'custom-background', 
                                    'parent_url' => $parent_url, 
                                    'parent_slug' => $parent_slug, 
                                    'screen_function' => array( $this, 'change_custom_background' ), 
                                    'position' => $position,
                                    ) );

    }


    public function change_custom_background()
    {
        global $bp;
        add_action( 'bp_template_title', array( $this,'settings_title' ) );
        add_action( 'bp_template_content', array( $this, 'settings_screen' ) );
        bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
    }


    public function settings_title()
    {
        echo __( 'Background image', 'mif-bpc' );
    }


    public function settings_screen()
    {

        $out = '';

        $out .= '<form name="users-settings-form" id="users-settings-form" class="standard-form" method="post" enctype="multipart/form-data" role="main">';

        $out .= self::admin_page();

        $out .= wp_nonce_field( 'mif-bp-customizer-custom-background-submit', '_wpnonce', true, false );
        $out .= '</form>';


        echo $out;

    }


    public function settings_save()
    {

        if ( ! ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'mif-bp-customizer-custom-background-submit' ) ) ) return;
        if ( empty( $_POST['save'] ) && empty( $_POST['delete'] ) ) return;

        // if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'mif-bp-customizer-custom-background-submit' ) ) {
        //     bp_core_add_message( sprintf( __( 'Authorization error', 'mif-bpc' ) ), 'error' );
        //     return false;
        // }

        self::submit_handler();

    }


    public function add_css()
    {
        $image_url = $this->get_image();
        if( empty( $image_url ) ) return;

        $out = '<style type="text/css">body.custom-background {background: url(' . $image_url . ');}</style>';

        echo $out;
    }


    public function get_body_class( $data )
    {
        $image_url = $this->get_image();
        if( empty( $image_url ) ) return $data;

        if ( bp_is_user() || bp_is_group() )  $data[] = 'custom-background';

        return $data;
    }


    public static function get_image( $item_id = NULL)
    {
        global $bp;

        if ( bp_is_user() ) {

            if ( ! $item_id ) $item_id = $bp->displayed_user->id;
            if ( empty( $item_id ) ) return false;
            $image_url = get_user_meta( $item_id, 'profile_bg', true );

            return apply_filters( 'mif_bpc_custom_background_user', $image_url, $item_id );

        } elseif ( bp_is_group() ) {

            if ( ! $item_id ) $item_id = $bp->groups->current_group->id;
            if ( empty( $item_id ) ) return false;
            $image_url = groups_get_groupmeta( $item_id, 'profile_bg' );

            return apply_filters( 'mif_bpc_custom_background_group', $image_url, $item_id );

        } else {

            return false;

        }
    
    }


    public static function admin_page()
    {

        $image_url = self::get_image();

        $image = ( $image_url ) ? '<div class="custom-background-image"><img src="' . $image_url . '"></div>' : '';

        $out .= '<div class="custom-background-wrapper">';
        $out .= '<p>' . __( 'The background image is used for the page design. You can select on your computer and upload JPG, GIF or PNG image.', 'mif-bpc' ) . '</p>';
        $out .= '<p>&nbsp;</p>';

        $out .= $image;

        $out .= '<p>&nbsp;<p><input type="file" name="file" id="custom-background-upload" accept="image/jpeg,image/png,image/gif" />';
        $out .= '<p>&nbsp;</div>';
        $out .= '<p><input type="submit" name="save" value="' . __( 'Save the changes', 'mif-bpc' ) . '" /> ';
        if ( $image_url ) $out .= '<input type="submit" name="delete" value="' . __( 'Delete file', 'mif-bpc' ) . '" />';

        return $out;

    }


    public static function submit_handler() 
    {
        global $bp;

        if ( $_POST['delete'] ) {

            $image_url = self::get_image();
            if ( $image_url ) bp_core_add_message( __( 'Image is removed', 'mif-bpc' ) );

            self::delete_custom_background();

            return true;

        }

        require_once( ABSPATH . '/wp-admin/includes/file.php' );

        $max_upload_size = self::get_max_upload_size();
        if ( $max_upload_size ) $max_upload_size = $max_upload_size * 1024;

        $file=$_FILES;

        if ( $file['error'] ) {
            bp_core_add_message( __( 'File upload error', 'mif-bpc' ), 'error' );
            return false;
        }

        if ( $max_upload_size && $file['file']['size'] > $max_upload_size ) {
            bp_core_add_message( sprintf( __( 'The file is too large. You can upload file not larger than %s.', 'mif-bpc' ), size_format( $max_upload_size ) ), 'error' );
            return false;
        }
            
        if ( empty( $file['file']['name'] ) ) {
            bp_core_add_message( __( 'You forgot to attach a file.', 'mif-bpc' ), 'error' );
            return false;
        }

        if ( ( ! empty( $file['file']['type'] ) && ! preg_match( '/(jpe?g|gif|png)$/i', $file['file']['type'] ) ) || ! preg_match( '/(jpe?g|gif|png)$/i', $file['file']['name'] ) ) {
            bp_core_add_message( __( 'Invalid file type. You can use only JPG, GIF or PNG images.', 'mif-bpc' ), 'error' );
            return false;
        }

        $uploaded_file = wp_handle_upload( $file['file'], array( 'test_form' => false ) );

        if ( ! empty( $uploaded_file['error'] ) ) {
            bp_core_add_message( sprintf( __( 'File upload error: %s', 'buddypress' ), $uploaded_file['error'] ), 'error' );
            return false;
        }

        self::delete_custom_background();

        if ( bp_is_user() ) {

            update_user_meta( bp_loggedin_user_id(), 'profile_bg', $uploaded_file['url'] );
            update_user_meta( bp_loggedin_user_id(), 'profile_bg_file_path', $uploaded_file['file'] );

        } elseif ( bp_is_group() ) {

            $group_id = groups_get_current_group()->id;
            groups_update_groupmeta( $group_id, 'profile_bg', $uploaded_file['url'] );
            groups_update_groupmeta( $group_id, 'profile_bg_file_path', $uploaded_file['file'] );

        }

        bp_core_add_message( sprintf( __( 'The image is uploaded successfully', 'buddypress' ) ) );

        do_action( 'mif_bpc_custom_background_uploaded', $uploaded_file, $uploaded_file );

        return true;
    }


    public static function delete_custom_background()
    {
        global $bp;

        if ( bp_is_user() ) {

            $item_id=$bp->displayed_user->id;

            $old_file_path = get_user_meta( $item_id, 'profile_bg_file_path', true );
            
            if ( $old_file_path ) unlink( $old_file_path );

            delete_user_meta( $item_id, 'profile_bg_file_path' );
            delete_user_meta( $item_id, 'profile_bg' );

        } elseif ( bp_is_group() ) {

            $group_id = groups_get_current_group()->id;
            groups_delete_groupmeta( $group_id, 'profile_bg' );
            groups_delete_groupmeta( $group_id, 'profile_bg_file_path' );

        }



        return true;
    }


    public static function get_max_upload_size()
    {
        $max_file_sizein_kb = get_site_option('fileupload_maxk');
                
        if( empty( $max_file_sizein_kb ) ) {
        
            $max_upload_size = (int) ini_get( 'upload_max_filesize' );
            $max_post_size = (int) ini_get( 'post_max_size' ) ;
            $memory_limit = (int) ini_get( 'memory_limit' );
            $max_file_sizein_mb= min( $max_upload_size, $max_post_size, $memory_limit );
            $max_file_sizein_kb=$max_file_sizein_mb * 1024;

        }

        return $max_file_sizein_kb;
    }

}

?>