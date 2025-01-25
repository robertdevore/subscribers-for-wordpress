<?php
/*
Plugin Name: Subscribers for WordPress®
Description: A plugin to handle subscribers and social media notifications.
Version: 1.0
Author: Your Name
*/

// Security: Prevent direct access
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

// Add settings page
function sfwp_add_settings_page() {
    add_options_page(
        __( 'Subscribers Settings', 'sfwp' ),
        __( 'Subscribers', 'sfwp' ),
        'manage_options',
        'sfwp-settings',
        'sfwp_render_settings_page'
    );
}
add_action( 'admin_menu', 'sfwp_add_settings_page' );

/**
 * Render the settings page
 */
function sfwp_render_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Subscribers Settings', 'sfwp' ); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'sfwp_options_group' );
            do_settings_sections( 'sfwp-settings' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

/**
 * Register settings
 */
function sfwp_register_settings() {
    register_setting( 'sfwp_options_group', 'sfwp_options' );

    add_settings_section(
        'sfwp_social_auth_section',
        __( 'Social Authentication', 'sfwp' ),
        '__return_false',
        'sfwp-settings'
    );

    add_settings_field(
        'sfwp_facebook_auth',
        __( 'Facebook Auth', 'sfwp' ),
        'sfwp_render_facebook_auth_button',
        'sfwp-settings',
        'sfwp_social_auth_section'
    );

    add_settings_field(
        'sfwp_linkedin_auth',
        __( 'LinkedIn Auth', 'sfwp' ),
        'sfwp_render_linkedin_auth_button',
        'sfwp-settings',
        'sfwp_social_auth_section'
    );
}
add_action( 'admin_init', 'sfwp_register_settings' );

/**
 * Render Facebook Auth button
 */
function sfwp_render_facebook_auth_button() {
    ?>
    <button type="button"><?php esc_html_e( 'Connect to Facebook', 'sfwp' ); ?></button>
    <?php
}

/**
 * Render LinkedIn Auth button
 */
function sfwp_render_linkedin_auth_button() {
    ?>
    <button type="button"><?php esc_html_e( 'Connect to LinkedIn', 'sfwp' ); ?></button>
    <?php
}

/**
 * Add subscription form to posts
 */
function sfwp_add_subscription_form( $content ) {
    if ( is_single() && is_main_query() ) {
        $form = '<form method="post" class="sfwp-subscribe-form">';
        $form .= '<input type="email" name="sfwp_email" placeholder="' . esc_attr__( 'Enter your email', 'sfwp' ) . '" required />';
        $form .= '<button type="submit">' . esc_html__( 'Subscribe', 'sfwp' ) . '</button>';
        $form .= '</form>';
        $content .= $form;
    }
    return $content;
}
add_filter( 'the_content', 'sfwp_add_subscription_form' );

/**
 * Handle form submission
 */
function sfwp_handle_form_submission() {
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['sfwp_email'] ) ) {
        $email = sanitize_email( $_POST['sfwp_email'] );
        if ( is_email( $email ) ) {
            $user_id = wp_create_user( $email, wp_generate_password(), $email );
            if ( ! is_wp_error( $user_id ) ) {
                $user = new WP_User( $user_id );
                $user->set_role( 'subscriber' );
            }
        }
    }
}
add_action( 'init', 'sfwp_handle_form_submission' );

/**
 * Send email to subscribers on new post
 */
function sfwp_send_email_on_publish( $ID, $post ) {
    if ( 'publish' === $post->post_status && 'post' === $post->post_type ) {
        $subscribers = get_users( [ 'role' => 'subscriber' ] );
        $subject     = sprintf( __( 'New Post: %s', 'sfwp' ), $post->post_title );
        $message     = '<html><body>';
        $message    .= '<h1>' . esc_html( $post->post_title ) . '</h1>';
        $message    .= '<p>' . esc_html( $post->post_excerpt ) . '</p>';
        $message    .= '<a href="' . esc_url( get_permalink( $ID ) ) . '">' . esc_html__( 'Read more', 'sfwp' ) . '</a>';
        $message    .= '</body></html>';

        foreach ( $subscribers as $subscriber ) {
            wp_mail( $subscriber->user_email, $subject, $message, [ 'Content-Type: text/html; charset=UTF-8' ] );
        }
    }
}
add_action( 'publish_post', 'sfwp_send_email_on_publish', 10, 2 );

/**
 * Enqueue inline CSS in the head.
 */
function sfwp_subscribe_form_inline_css() {
    ?>
    <style>
        .sfwp-subscribe-form {
            display: flex;
            flex-direction: column;
            max-width: 300px;
            margin: 20px 0;
        }

        .sfwp-subscribe-form input[type="email"] {
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .sfwp-subscribe-form button {
            padding: 10px;
            background-color: #0073aa;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .sfwp-subscribe-form button:hover {
            background-color: #005177;
        }
    </style>
    <?php
}
add_action( 'wp_head', 'sfwp_subscribe_form_inline_css' );
