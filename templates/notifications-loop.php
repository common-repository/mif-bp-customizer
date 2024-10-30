<p><br />

<?php do_action( 'mif_bpc_before_notification_loop' );  ?>

<?php if ( bp_has_notifications( array( 'per_page' => mif_bpc_get_notifications_per_page(), 'is_new' => 0 ) ) ) : ?>

<form action="" method="post" id="notifications-bulk-management">
	<table class="custom-notifications">
		<thead>
			<tr>
                <td class="cn-check"><input id="select-all-notifications" type="checkbox"></td>
                <td class="cn-head" colspan="3"><label for="select-all-notifications"><strong><?php echo __( 'All notifications', 'mif-bpc' ); ?></strong></label></td>
                <td class="cn-actions" colspan="2">
                    <div class="custom-button"><a href="<?php mif_bpc_the_notification_bulk_url( 'delete' ); ?>" class="button notification-bulk-delete" title="<?php echo __( 'Delete', 'mif-bpc' ); ?>"><i class="fa fa-trash-o" aria-hidden="true"></i></a></div>
                    <div class="custom-button"><a href="<?php mif_bpc_the_notification_bulk_url( 'not_is_new' ); ?>" class="button notification-bulk-not-is-new" title="<?php echo __( 'Mark as read', 'mif-bpc' ); ?>"><i class="fa  fa-envelope-open-o" aria-hidden="true"></i></a></div> 
                
                </td>
			</tr>
		</thead>

		<tbody>

			<?php while ( bp_the_notifications() ) : bp_the_notification(); 

                mif_bpc_the_notification_row();
			
             endwhile; 
             
             mif_bpc_the_notification_load_more();

             ?>

		</tbody>
	</table>

	<?php wp_nonce_field( 'notifications_bulk_nonce', 'notifications_bulk_nonce' ); ?>
</form>

<?php else : ?>

	<?php bp_get_template_part( 'members/single/notifications/feedback-no-notifications' ); ?>

<?php endif; ?>

<?php do_action( 'mif_bpc_after_notification_loop' );