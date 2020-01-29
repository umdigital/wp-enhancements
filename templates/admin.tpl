<div class="wrap">
    <h2>WP Enhancements</h2>
    <form method="post" action="options.php">
        <?php settings_fields( 'wp_enhancements' ); ?>

        <?php do_action( 'um_wpe_admin' ); ?>

        <?php submit_button(); ?>
    </form>
</div>
