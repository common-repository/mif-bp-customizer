<?php

//
// Documents (описание раздела группы)
// 
//



defined( 'ABSPATH' ) || exit;


if ( mif_bpc_options( 'docs' ) ) 
    add_action( 'bp_init', 'mif_bpc_docs_group_init' );



function mif_bpc_docs_group_init() {

    class mif_bpc_docs_group extends BP_Group_Extension {

        var $visibility = 'private';

        // Показывать при создании группы

        var $enable_create_step = false;
        
        // Показывать в меню группы
        
        var $enable_nav_item = true;

        // Показывать в настройках группы

        var $enable_edit_item = true;

       

        function __construct() 
        {
            global $bp, $mif_bpc_docs;

            if ( isset( $bp->groups->current_group->id ) ) $access_mode = groups_get_groupmeta( $bp->groups->current_group->id, $mif_bpc_docs->group_access_mode_meta_key );
            if ( ! empty( $access_mode ) && empty( $access_mode['docs_allowed'] ) ) $this->enable_nav_item = false;
            // f($access_mode);
            
            $data = $mif_bpc_docs->get_all_folders_size();

            $this->name = __( 'Documents', 'mif-bpc' );
            $this->nav_item_name = __( 'Documents', 'mif-bpc' ) . ' <span>' . $data['count'] . '</span>';
            $this->slug = 'docs';
            // $this->create_step_position = 10;
            $this->nav_item_position = 30;

        }



        // 
        // Страница документов
        // 

        function display( $group_id = NULL ) 
        {
            global $mif_bpc_docs;
            
            $action = bp_action_variable();

            echo $this->subnav( $group_id );

            if ( $action == 'folder' ) {

                $mif_bpc_docs->body();

            } elseif ( $action == 'new-folder' ) {

                $mif_bpc_docs->body();

            } elseif ( is_numeric( $action ) ) {

                $mif_bpc_docs->doc_page();

            } else {

                $mif_bpc_docs->body();

            }

        }


        //
        // Панель внутренней навигации
        //        

        function subnav()
        {
            global $mif_bpc_docs;

            $url = trailingslashit( $mif_bpc_docs->get_docs_url() );
            
            $out = '';
            $out .= '<div class="item-list-tabs no-ajax" id="subnav" role="navigation"><ul>';

            $current1 = ' class="current"';
            $current2 = '';

            if ( bp_action_variable() == 'new-folder' ) {

                $current1 = '';
                $current2 = ' class="current"';

            }

            $out .= '<li' . $current1 . '><a href="' . $url . '">' . __( 'Folders', 'mif-bpc' ) . '</a></li>';
            if ( $mif_bpc_docs->is_access( 'all-folders', 'write' ) ) $out .= '<li' . $current2 . '><a href="' . $url . 'new-folder/">' . __( 'Create folder', 'mif-bpc' ) . '</a></li>';

            $out .= '</ul></div>';

            return apply_filters( 'mif_bpc_docs_group_subnav', $out );
        }



        //
        // Страница настройки документов в группе
        //        

        function settings_screen( $group_id = NULL ) 
        {
            global $bp, $mif_bpc_docs;

            $access_mode = groups_get_groupmeta( $group_id, $mif_bpc_docs->group_access_mode_meta_key );
            
            if ( empty( $access_mode ) ) {
            
                $docs_allowed = ' checked';
                $everyone_create = '';
                $everyone_delete = '';

            } else {

                $docs_allowed = ( $access_mode['docs_allowed'] ) ? ' checked' : '';
                $everyone_create = ( $access_mode['everyone_create'] ) ? ' checked' : '';
                $everyone_delete = ( $access_mode['everyone_delete'] ) ? ' checked' : '';

            }

            $out = '';

            $out .= '<h3>' . __( 'Documents', 'mif-bpc' ) . '</h3>';
            $out .= '<p>' . __( 'Configuration of document system in a group. You can override creation and removal options for each specific folder.', 'mif-bpc' ) . '</p>';
            $out .= '<hr>';

            $out .= '<div class="checkbox"><label><input type="checkbox" name="docs_allowed"' . $docs_allowed . '>' . __( 'Allow documents in a group', 'mif-bpc' ) . '
            <ul>
            <li>' . __( 'Creates "Document" section in the group', 'mif-bpc' ) . '</li>
            <li>' . __( 'You can create folders, upload files, post links to Internet resources', 'mif-bpc' ) . '</li>
            </ul>
            </label></div>';

            $out .= '<div class="checkbox"><label><input type="checkbox" name="everyone_create"' . $everyone_create . '>' . __( 'Every member of the group can create folders and upload documents', 'mif-bpc' ) . '
            <ul>
            <li>' . __( 'By default, only group administrators can create folders and upload documents', 'mif-bpc' ) . '</li>
            <li>' . __( 'If this option is selected, all group members will be able to create folders and publish documents', 'mif-bpc' ) . '</li>
            </ul>
            </label></div>';

            $out .= '<div class="checkbox"><label><input type="checkbox" name="everyone_delete"' . $everyone_delete . '>' . __( 'Group members can delete other users’ folders and documents', 'mif-bpc' ) . '
            <ul>
            <li>' . __( 'By default, only group administrators can delete any folders and documents', 'mif-bpc' ) . '</li>
            <li>' . __( 'Regular users can delete only those documents and folders that were created by them', 'mif-bpc' ) . '</li>
            <li>' . __( 'If this option is selected, users will be able to remove other users’ folders and documents (not only their own)', 'mif-bpc' ) . '</li>
            <li>' . __( 'Also everyone will be allowed to create folders and publish documents', 'mif-bpc' ) . '</li>
            </ul>
            </label></div>';

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



        //
        // Сохранение настроек
        //

        function save( $group_id = NULL, $mode = 'screen' ) 
        {
            global $mif_bpc_docs;

            $access_mode = array();

            $access_mode['docs_allowed'] = ( isset( $_POST['docs_allowed'] ) ) ? true : false;
            $access_mode['everyone_create'] = ( isset( $_POST['everyone_create'] ) ) ? true : false;
            $access_mode['everyone_delete'] = ( isset( $_POST['everyone_delete'] ) ) ? true : false;

            if ( $access_mode['everyone_delete'] ) $access_mode['everyone_create'] = true;

            groups_update_groupmeta( $group_id, $mif_bpc_docs->group_access_mode_meta_key, $access_mode );
            groups_update_last_activity();
        }



    }

    bp_register_group_extension( 'mif_bpc_docs_group' );

}

?>