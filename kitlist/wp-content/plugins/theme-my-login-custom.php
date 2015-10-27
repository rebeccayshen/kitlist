<?php
/**
 * Created by IntelliJ IDEA.
 * User: markfussell
 * Date: 5/28/15
 * Time: 11:04 AM
 */


function tml_user_register( $user_id ) {
    if ( !empty( $_POST['first_name'] ) )
        update_user_meta( $user_id, 'first_name', $_POST['first_name'] );
    if ( !empty( $_POST['last_name'] ) )
        update_user_meta( $user_id, 'last_name', $_POST['last_name'] );

    if (!empty($_POST['kit_user_meta_role'])) {
        update_user_meta($user_id, 'kit_user_meta_role', $_POST['wpcf-kit-role']);

    }

}

add_action( 'user_register', 'tml_user_register' );



//for user registration updating the user meta information and update user role



?>