<?php

//
// Страница настроек плагина
//
//


defined( 'ABSPATH' ) || exit;


class mif_bpc_console_settings_page {
    
    function __construct() 
    {
        add_action( 'admin_menu', array( $this, 'register_menu_page' ) );
    }

    function register_menu_page()
    {
        add_options_page( __( 'BP Customizer plugin configuration', 'mif-bpc' ), __( 'BP Customizer', 'mif-bpc' ), 'manage_options', 'mif-bpc', array( $this, 'page' ) );
        wp_register_style( 'mif-bpc-styles', plugins_url( '../mif-bpc-styles.css', __FILE__ ) );
        wp_enqueue_style( 'mif-bpc-styles' );
    }

    function page()
    {
        $out = '<h1>' . __( 'BP Customizer plugin configuration', 'mif-bpc' ) . '</h1>';
        $out .= '<p>' . __( 'MIF BP Customizer plugin adds new features to BuddyPress. Here you can specify what exactly should be applied in your social network.', 'mif-bpc' );
        $out .= '<p>&nbsp;';
      
        $out .= $this->update_mif_bpc_options();

        $args = get_mif_bpc_options();
        foreach ( $args as $key => $value ) {
            $chk[$key] = ( $value ) ? ' checked' : '';
        }

        $out .= '<form method="POST">';
        $out .= '<table class="form-table">';

        if ( is_main_site() ) {

            $out .= '<tr><td colspan="3">
                    <h2>' . __( 'Activity feed', 'mif-bpc' ) . '</h2>
                    </td></tr>';

            $out .= '<tr>
                    <th>' . __( 'Special activity feed', 'mif-bpc' ) . '</th>
                    <td><input type="checkbox"' . $chk['activity-stream'] . ' value="yes" name="activity-stream" id="activity-stream"></td>
                    <td><label for="activity-stream">' . __( 'Changes the appearance and behavior of the activity feed on users’ pages (on personal page - "Whole feed", on other users’ pages – only their activity). Allows to use content blocking tools.', 'mif-bpc' ) . '</label></td>
                    </tr>';

            $out .= '<tr>
                    <th>' . __( 'Post types of activity feed', 'mif-bpc' ) . '</th>
                    <td><input type="checkbox"' . $chk['activity-exclude'] . ' value="yes" name="activity-exclude" id="activity-exclude"></td>
                    <td><label for="activity-exclude">' . __( 'Allows to specify activity types, which should be displayed in user’s feed ("Special activity feed" option is required).', 'mif-bpc' ) . '</label></td>
                    </tr>';

            $out .= '<tr>
                    <th>' . __( 'User blocking', 'mif-bpc' ) . '</th>
                    <td><input type="checkbox"' . $chk['banned-users'] . ' value="yes" name="banned-users" id="banned-users"></td>
                    <td><label for="banned-users">' . __( 'Allows to maintain a list of users, whose information is blocked in your activity feed ("Special activity feed" option is required).', 'mif-bpc' ) . '</label></td>
                    </tr>';

            $out .= '<tr><td colspan="3">
                    <h2>' . __( 'Site behavior', 'mif-bpc' ) . '</h2>
                    </td></tr>';

            $out .= '<tr>
                    <th>' . __( 'Profile as a homepage', 'mif-bpc' ) . '</th>
                    <td><input type="checkbox"' . $chk['profile-as-homepage'] . ' value="yes" name="profile-as-homepage" id="profile-as-homepage"></td>
                    <td><label for="profile-as-homepage">' . __( 'Set user profile as his home page.', 'mif-bpc' ) . '</label></td>
                    </tr>';

            $out .= '<tr>
                    <th>' . __( 'Profile privacy', 'mif-bpc' ) . '</th>
                    <td><input type="checkbox"' . $chk['profile-privacy'] . ' value="yes" name="profile-privacy" id="profile-privacy"></td>
                    <td><label for="profile-privacy">' . __( 'Allow users to restrict access to their profiles.', 'mif-bpc' ) . '</label></td>
                    </tr>';

            $out .= '<tr>
                    <th>' . __( 'Subscribers', 'mif-bpc' ) . '</th>
                    <td><input type="checkbox"' . $chk['followers'] . ' value="yes" name="followers" id="followers"></td>
                    <td><label for="followers">' . __( 'Enable subscription option for user updates (subscription = one-way friendship).', 'mif-bpc' ) . '</label></td>
                    </tr>';

            $out .= '<tr>
                    <th>' . __( 'Notifications', 'mif-bpc' ) . '</th>
                    <td><input type="checkbox"' . $chk['notifications'] . ' value="yes" name="notifications" id="notifications"></td>
                    <td><label for="notifications">' . __( 'Enable advanced notification mode.', 'mif-bpc' ) . '</label></td>
                    </tr>';

            $out .= '<tr>
                    <th>' . __( 'Dialogues', 'mif-bpc' ) . '</th>
                    <td><input type="checkbox"' . $chk['dialogues'] . ' value="yes" name="dialogues" id="dialogues"></td>
                    <td><label for="dialogues">' . __( 'Enable simple and convenient dialogues instead of the standard system of private messages.', 'mif-bpc' ) . '</label></td>
                    </tr>';

            $out .= '<tr>
                    <th>' . __( 'Pop-up messages', 'mif-bpc' ) . '</th>
                    <td><input type="checkbox"' . $chk['websocket'] . ' value="yes" name="websocket" id="websocket"></td>
                    <td><label for="websocket">' . __( 'Enable mechanism of pop-up messages. Echo-server configuration is required.', 'mif-bpc' ) . '</label></td>
                    </tr>';

            $out .= '<tr>
                    <th>' . __( 'Documents', 'mif-bpc' ) . '</th>
                    <td><input type="checkbox"' . $chk['docs'] . ' value="yes" name="docs" id="docs"></td>
                    <td><label for="docs">' . __( 'Documents and files upload', 'mif-bpc' ) . '</label></td>
                    </tr>';

            $out .= '<tr>
                    <th>' . __( 'Background image', 'mif-bpc' ) . '</th>
                    <td><input type="checkbox"' . $chk['custom-background'] . ' value="yes" name="custom-background" id="custom-background"></td>
                    <td><label for="custom-background">' . __( 'Allow to use custom image as a background for user profile or group.', 'mif-bpc' ) . '</label></td>
                    </tr>';

            $out .= '<tr>
                    <th>' . __( 'Group address', 'mif-bpc' ) . '</th>
                    <td><input type="checkbox"' . $chk['edit-group-slug'] . ' value="yes" name="edit-group-slug" id="edit-group-slug"></td>
                    <td><label for="edit-group-slug">' . __( 'Allow to change the group address in its settings and at creation.', 'mif-bpc' ) . '</label></td>
                    </tr>';
            
            $out .= '<tr>
                    <th>' . __( '"Like" button', 'mif-bpc' ) . '</th>
                    <td><input type="checkbox"' . $chk['like-button'] . ' value="yes" name="like-button" id="like-button"></td>
                    <td><label for="like-button">' . __( 'Allow to use the "Like" button.', 'mif-bpc' ) . '</label></td>
                    </tr>';
            
            $out .= '<tr>
                    <th>' . __( '"Repost" button', 'mif-bpc' ) . '</th>
                    <td><input type="checkbox"' . $chk['repost-button'] . ' value="yes" name="repost-button" id="repost-button"></td>
                    <td><label for="repost-button">' . __( 'Allow to use the "Repost" button.', 'mif-bpc' ) . '</label></td>
                    </tr>';
            
            $out .= '<tr>
                    <th>' . __( '"Favorite", "Delete" buttons', 'mif-bpc' ) . '</th>
                    <td><input type="checkbox"' . $chk['activity-button-customize'] . ' value="yes" name="activity-button-customize" id="activity-button-customize"></td>
                    <td><label for="activity-button-customize">' . __( 'Use special buttons "Favorite", "Remove".', 'mif-bpc' ) . '</label></td>
                    </tr>';
            


        }

        $out .= '<tr><td colspan="3">';
        $out .= '<h2>' . __( 'Visual elements', 'mif-bpc' ) . '</h2>';
        $out .= '</td></tr>';

        $out .= '<tr>
                <th>' . __( 'Site members widget', 'mif-bpc' ) . '</th>
                <td><input type="checkbox"' . $chk['members-widget'] . ' value="yes" name="members-widget" id="members-widget"></td>
                <td><label for="members-widget">' . __( 'Allow to use site member widget. Displays member’s avatars in the widget area.', 'mif-bpc' ) . '</label></td>
                </tr>';

        $out .= '<tr>
                <th>' . __( 'Group widget', 'mif-bpc' ) . '</th>
                <td><input type="checkbox"' . $chk['groups-widget'] . ' value="yes" name="groups-widget" id="groups-widget"></td>
                <td><label for="groups-widget">' . __( 'Allow to use group widget. Displays group avatars in the widget area.', 'mif-bpc' ) . '</label></td>
                </tr>';
            
        $out .= '<tr><td colspan="3">';
        $out .= wp_nonce_field( "mif-bpc-admin-settings-page-nonce", "_wpnonce", true, false );
        $out .= '<p><input type="submit" class="button button-primary" name="update-mif-bpc-settings" value="' . __( 'Save the changes', 'mif-bpc' ) . '">';
        $out .= '</td></tr>';

        $out .= '</table>';
        $out .= '</form>';

        $out .= $this->donate();
          
        echo $out;
    }

    function donate()
    {
        $out = '';
        
        $out .= '<p>&nbsp;<p><strong>' . __( 'You can help make this plugin better', 'mif-wpc' ) . '</strong></p>';
        $out .= '<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
            <input type="hidden" name="cmd" value="_s-xclick">
            <input type="hidden" name="hosted_button_id" value="FDHSU8BPQZ4GL">
            <input type="image" src="https://www.paypalobjects.com/en_US/GB/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal – The safer, easier way to pay online!">
            <img alt="" border="0" src="https://www.paypalobjects.com/ru_RU/i/scr/pixel.gif" width="1" height="1">
            </form>';

        return $out;
    }

    function update_mif_bpc_options()
    {
        if ( empty( $_POST['update-mif-bpc-settings'] ) ) return;
        if ( ! wp_verify_nonce( $_POST['_wpnonce'], "mif-bpc-admin-settings-page-nonce" ) ) return '<div class="err">' . __( 'Authorization error', 'mif-bpc' ) . '</div>';

        $args = get_mif_bpc_options();
        foreach ( $args as $key => $value ) {
            
            if ( isset($_POST[$key]) ) {
                $new_value = ( $_POST[$key] == 'yes' ) ? 1 : sanitize_text_field( $_POST[$key] );
            } else {
                $new_value = 0;    
            }
            
            update_option( $key, $new_value );
        }

        return '<div class="note">' . __( 'Changes saved', 'mif-bpc' ) . '</div>';
    }



}

new mif_bpc_console_settings_page();

?>