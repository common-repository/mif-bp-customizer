<div class="repost-activity">

	<div class="activity-avatar">
		<a href="<?php mif_bpc_repost_activity_user_link(); ?>">

			<?php mif_bpc_repost_activity_avatar(); ?>

		</a>
	</div>

	<div class="activity-content">

		<div class="activity-header">

			<?php mif_bpc_repost_activity_action(); ?>

		</div>

		<?php if ( mif_bpc_repost_activity_has_content() ) : ?>

			<div class="activity-inner">

				<?php mif_bpc_repost_activity_content_body(); ?>

			</div>

		<?php endif; ?>
	</div>

</div>