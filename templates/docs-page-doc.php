<?php get_header( 'buddypress' ); ?>

    <div id="page-header" class="clearfix">

        <h1><?php _e( 'Documents', 'mif-bpc' ); ?></h1>

    </div>
      
    <div id="item-body" class="docs-page-doc clearfix">

        <div class="content">

            <?php mif_bpc_docs_the_doc(); ?>

        </div>

        <div class="meta">

            <?php mif_bpc_docs_the_meta(); ?>

        </div>

	</div>

<?php get_sidebar( 'buddypress' ); ?>
<?php get_footer( 'buddypress' ); ?>
   