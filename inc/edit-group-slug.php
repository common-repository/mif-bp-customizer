<?php

//
// Configuration короткого адреса для группы
// 
//


defined( 'ABSPATH' ) || exit;



if ( mif_bpc_options( 'edit-group-slug' ) ) 
    add_action( 'bp_init', 'mif_bpc_edit_group_slug_init' );


function mif_bpc_edit_group_slug_init() {

	class mif_bpc_edit_group_slug extends BP_Group_Extension {

		var $visibility = 'private';

		var $enable_nav_item = false;
		var $enable_create_step = true;
		var $enable_edit_item = true;

		function __construct() 
        {

            $this->name = __( 'Address', 'mif-bpc' );
            $this->slug = 'group-slug';

			$this->create_step_position = 11;
			$this->nav_item_position = 11;

		}


        function settings_screen( $group_id = NULL ) 
        {
            global $bp;

            $group_url = $bp->root_domain . '/' . BP_GROUPS_SLUG . '/';
            $slug = $bp->groups->current_group->slug;

            $out = '';

            $out .= '<h3>' . __( 'Address', 'mif-bpc' ) . '</h3>';
            $out .= '<p>' . __( 'Configuration of the group name in the address bar', 'mif-bpc' ) . '</p>';
            $out .= '<p>' . __( 'The group name in the address bar is set automatically based on the group name, specified when it was created. You can leave the existing name or set another one.', 'mif-bpc' ) . '</p>';

            $out .= '<div class="slug-edit">';
            $out .= '<div>' . $group_url . '</div>';
            $out .= '<input type="text" name="slug" value="' . $slug . '">';
            $out .= '</div>';

            $out .= '<p>' . __( '** Придумайте адрес, который будет коротким и запоминающимся. Вы можете использовать строчные латинские буквы, цифры, подчёркивание и тире.', 'mif-bpc' ) . '</p>';
            $out .= '<p>&nbsp;';

            echo $out;

        }


		function create_screen_save( $group_id = NULL ) 
        {
			$this->save( $group_id, 'create' );
		}



        function settings_screen_save( $group_id = NULL ) 
        {
            $this->save( $group_id, 'screen' );
        }


        function save( $group_id = NULL, $mode = 'screen' ) 
        {
            global $bp;

            $msg = array(   0 => __( 'The group address has been successfully changed.', 'mif-bpc' ), 
                            1 => __( 'The group address has not changed.', 'mif-bpc' ),
                            2 => __( 'Specified address is already in use. Please, come up with another one.', 'mif-bpc' ),
                            3 => __( 'Such address is not allowed. Please, come up with another one.', 'mif-bpc' ),
                            4 => __( 'The address contains invalid characters. Use only lowercase Latin letters, numbers, underscore and dash.', 'mif-bpc' )
                        );


            $slug = sanitize_text_field( $_POST['slug'] );
            $error_code = $this->slug_check( $slug );

            if ( $mode == 'screen' ) {

                if ( $error_code == 0 ) {

                    bp_core_add_message( $msg[$error_code] );
    
                    if ( $this->save_slug( $slug, $group_id ) ) {

                        $bp->groups->current_group->slug = $slug;
                        $redirect = bp_get_group_permalink( $bp->groups->current_group ) . 'admin/group-slug/';
                        bp_core_redirect( $redirect );

                    } else {

                        bp_core_add_message( __( 'Error occurred while changing the address.', 'mif-bpc' ), 'error' );

                    }

                } else {

                    bp_core_add_message( $msg[$error_code], 'error' );

                }

            } elseif ( $mode == 'create' ) {

                if ( $error_code == 0 ) {
                    
                    bp_core_add_message( $msg[$error_code] );
                    if ( ! $this->save_slug( $slug, $group_id ) ) bp_core_add_message( __( 'Error occurred while changing the address.', 'mif-bpc' ), 'error' );

                }

                if ( in_array( $error_code, array( 2, 3, 4 ) ) ) bp_core_add_message( $msg[$error_code], $error );

                if ( $error_code != 1 ) {

                    $redirect = apply_filters( 'bp_get_group_creation_form_action', trailingslashit( bp_get_groups_directory_permalink() . 'create/step/group-slug' ) );
                    bp_core_redirect( $redirect );

                }

            }

        }



        function save_slug( $slug, $group_id )
        {
			global $bp, $wpdb;

			if ( $slug && $group_id ) {
				$sql = $wpdb->prepare( "UPDATE {$bp->groups->table_name} SET slug = %s WHERE id = %d", $slug, $group_id );
				return $wpdb->query( $sql );
			}

			return false;
        }


        function slug_check( $slug ) 
        {
			global $bp;

            // совпадает со старым
			if ( $slug == $bp->groups->current_group->slug ) return 1;

            // уже используется для другой группы
			if ( BP_Groups_Group::check_slug( $slug ) ) return 2;

            // попадает в запрещенные имена
			if ( in_array( $slug, (array) $bp->groups->forbidden_names ) ) return 3;

            // содержит запрещенные буквы
            $clean_slug = preg_replace( "/[^a-z0-9_\-]/", '', $slug );
            if ( $slug != $clean_slug )  return 4;

			return 0;
		}



    }

	bp_register_group_extension( 'mif_bpc_edit_group_slug' );

}




?>