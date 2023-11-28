<?php
/**
 * Convert array to rgba value.
 *
 * @since 1.0.0
 *
 * @param array  $rgba
 * @param string $default
 *
 * @return string
 */
function wedocs_get_array_to_rgba( $rgba, $default = '' ) {
    if ( empty( $rgba ) ) {
        return $default;
    }

    list( $r, $g, $b, $a ) = array_values( $rgba );
    return "rgba( {$r}, {$g}, {$b}, {$a} )";
}

/**
 * Convert array to hexadecimal value.
 *
 * @since 1.0.0
 *
 * @param array  $rgba
 * @param string $default
 *
 * @return string
 */
function wedocs_get_array_to_hex( $rgba, $default = '' ) {
    if ( empty( $rgba ) ) {
        return $default;
    }

    list( $r, $g, $b, $a ) = array_values( $rgba );
    return sprintf("#%02x%02x%02x%02x", $r, $g, $b, $a * 255 );
}

/**
 * Get the value of permission settings.
 *
 * @since 1.0.0
 *
 * @param string $field_name permission settings field name.
 * @param string $default    default data if settings not found.
 *
 * @return mixed
 */
function wedocs_get_permission_settings( $field_name = '', $default = '' ) {
    $permission_settings  = wedocs_get_option( 'permission', 'wedocs_settings', [] );

    if ( ! empty( $field_name ) ) {
        // Check from general settings if not found then collect data from wedocs_settings.
        return ! empty( $permission_settings[ $field_name ] ) ? $permission_settings[ $field_name ] : $default;
    }

    return $permission_settings;
}

/**
 * Send an email via floating messaging form.
 *
 * @since 1.0.0
 *
 * @param string $name       Sender name.
 * @param string $email      Sender email.
 * @param string $message    Email body.
 * @param array  $attachment Email attachment.
 *
 * @return bool|mixed
 */
function wedocs_send_floating_message( $name, $email, $message, $attachment = array() ) {
    $wp_email = 'wordpress@' . preg_replace( '/[^\w\.\-@]|#^www\.#|_/', '', strtolower( $_SERVER['SERVER_NAME'] ) );
    $blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

    // Collect message sending email address & prepare body.
    $assistant_settings = wedocs_get_option( 'assistant', 'wedocs_settings', [] );
    $email_to           = ! empty( $assistant_settings[ 'message' ][ 'email_address' ] ) ? preg_split( '/[,|)]/', $assistant_settings[ 'message' ][ 'email_address' ] ) : [];
    if ( empty( $email_to ) ) {
        $admin_email     = get_option( 'admin_email' );
        $is_email_enable = wedocs_get_general_settings( 'email', 'on' );
        $email_to        = ( $is_email_enable === 'on' ) ? wedocs_get_general_settings( 'email_to', $admin_email ) : $admin_email;
    }

    $subject  = sprintf( __( '[%1$s] New Message from weDocs', 'wedocs-pro' ), $blogname );
    $from     = "From: $name <$wp_email>";
    $reply_to = "Reply-To: $email <$email>";

    $message_headers  = "$from\n" . 'Content-Type: text/plain; charset ="' . get_option( 'blog_charset' ) . "\"\n";
    $message_headers .= $reply_to . "\n";

	/* translators: Do not translate USERNAME, URL_DELETE, SITENAME, SITEURL: those are placeholders. */
	$mail_body = __(
		"Howdy,

Someone submitted a form through weDocs Pro from ###SITEURL###. The form details below:
<br>
<b>Name:</b> $name<br>
<b>e-mail:</b> $email<br>
<b>Message:</b> $message<br>

Thanks for using weDocs Pro"
	);

	/**
	 * Filters the text for the email sent when WPFeather frontend form is submitted.
	 *
	 * @since 1.0.0
	 *
	 * @param string $mail_body The email text.
	 */
	$mail_body = apply_filters( 'wedocs_messaging_form_email_content', $mail_body );
	$mail_body = str_replace( '###SITEURL###', network_home_url(), $mail_body );

    return wp_mail( $email_to, wp_specialchars_decode( $subject ), $mail_body, $message_headers, $attachment );
}

/**
 * Get documentation children ids by using children type.
 *
 * @since WEDOCS_SINCE_PRO
 *
 * @param int    $doc_id        Parent documentation id.
 * @param string $children_type Children type.
 *
 * @return array Children (article/section) ids array
 */
function wedocs_get_documentation_children_by_type( $doc_id, $children_type = 'article' ) {
    if ( empty( $doc_id ) ) {
        return array();
    }

    if ( ! ( $children_type === 'article' || $children_type === 'section' ) ) {
        return array();
    }

    $sections = get_children( array(
        'post_type'   => 'docs',
        'post_status' => 'publish',
        'post_parent' => $doc_id,
    ) );

    $section_ids = wp_list_pluck( $sections, 'ID' );
    if ( $children_type === 'section' ) {
        return $section_ids;
    }

    $childrens    = wedocs_get_posts_children( $doc_id, 'docs' );
    $children_ids = wp_list_pluck( $childrens, 'ID' );
    $article_ids  = array_diff( $children_ids, $section_ids );

    return $article_ids;
}

/**
 * Get the list of documentation contributors.
 *
 * @since 1.0.0
 *
 * @param int   $doc_id      Parent documentation id.
 * @param array $article_ids Documentation article ids.
 *
 * @return array
 */
function wedocs_get_documentation_contributors( $doc_id, $article_ids ) {
    $contributors = array( $doc_id );
    foreach ( $article_ids as $article_id ) {
        $article_contributors = get_post_meta( $article_id, 'wedocs_contributors', true );
        foreach ( (array) $article_contributors as $contributor ) {
            if ( ! empty( $contributor ) && ! in_array( $contributor, $contributors ) ) {
                array_push( $contributors, absint( $contributor ) );
            }
        }
    }

    return $contributors;
}

/**
 * Update the list of documentation contributors.
 *
 * @since 1.0.0
 *
 * @param int   $article_id Article id.
 * @param array $article_contributors Article contributor list.
 *
 * @return void
 */
function wedocs_update_documentation_contributors( $article_id, $article_contributors ) {
    $ancestors               = get_post_ancestors( $article_id );
    $grand_parent_id         = end( $ancestors );
    $parent_contributors     = get_post_meta( $grand_parent_id, 'wedocs_contributors', true );
    $parent_contributors     = ! empty( $parent_contributors ) ? $parent_contributors : array();
    $additional_contributors = array_diff( $article_contributors, $parent_contributors );

    if ( ! empty( $additional_contributors ) ) {
        $updated_contributors = array_merge( $parent_contributors, $additional_contributors );
        update_post_meta( $grand_parent_id, 'wedocs_contributors', (array) $updated_contributors );
    }
}

/**
 * Check is appsero license valid.
 *
 * @since 1.0.0
 *
 * @return bool
 */
function wedocs_is_license_valid() {
    $client = new Appsero\Client(
        'c8eba6c5-b459-4401-ae9c-6300936f24b6',
        'weDocs Pro',
        WEDOCS_PRO_FILE
    );

    return $client->license()->is_valid();
}

/**
 * Check if current user can handle settings panel.
 *
 * @since 1.0.0
 *
 * @return bool
 */
function wedocs_current_user_can_access_settings() {
    $current_user       = wp_get_current_user();
    $current_user_roles = $current_user->roles;
    if ( in_array( 'administrator', $current_user_roles, true ) ) {
        return true;
    }

    // Check current user has settings panel access permission.
    $roles = wedocs_get_permission_settings( 'role_wise_permission', [] );
    return ! empty( array_intersect( $roles, $current_user_roles ) );
}
