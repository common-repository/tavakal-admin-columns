<?php

/**
 * Plugin Name: Admin columns
 * Description: Free powerful plugin to add or customise columns on the administration screens.
 * Author: Tavakal4devs
 * Version: 1.0.0
 * Donate link: https://paypal.me/MohAsly
 * License:         GPL-2.0+
 * License URI:     http://www.gnu.org/licenses/gpl-2.0.txt
 */



// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

require dirname( __FILE__ ) .'/includes/TavakalColumn.php';

require dirname( __FILE__ ) .'/includes/columnTypes/TavakalTypeInterface.php';

require dirname( __FILE__ ) .'/includes/columnTypes/BooleanType.php';
require dirname( __FILE__ ) .'/includes/columnTypes/TextType.php';
require dirname( __FILE__ ) .'/includes/columnTypes/ObjectType.php';
require dirname( __FILE__ ) .'/includes/columnTypes/TaxonomyType.php';
require dirname( __FILE__ ) .'/includes/columnTypes/DateType.php';
require dirname( __FILE__ ) .'/includes/columnTypes/ImageType.php';

require dirname( __FILE__ ) .'/includes/TavakalAdmin.php';
require dirname( __FILE__ ) .'/includes/TavakalTable.php';

new TavakalAdmin();

global $pagenow;
if (( $pagenow == 'edit.php' ) && $_GET['post_type']) {
    new TavakalTable(sanitize_text_field( $_GET['post_type'] ));
}

else if(( $pagenow == 'users.php' )){
    new TavakalTable(esc_attr('user'));
}

register_deactivation_hook(__FILE__, 'tavakal_admin_column_deactivate');


function tavakal_admin_column_deactivate()
{
    $post_types = get_post_types();

    foreach($post_types as $post_type){
        delete_option('tavakal_' . $post_type . '_columns');
    }
    delete_option('tavakal_user_columns');


}