<?php
namespace EssentialBlocks\Pro\Core\DynamicTags\Post;

use EssentialBlocks\Traits\HasSingletone;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PostFields {
    use HasSingletone;

    function __construct() {
        add_filter( "essential-blocks-pro/post/fields", [$this, 'get_fields'], 1 );
    }

    /**
     * Function for registering the source.
     *
     * @param array previous sources object.
     * @return array newly generated $sources object.
     */
    public static function get_fields( $fields = [] ) {
        return array_merge(
            $fields,
            [
                'post-title'             => __( 'Post Title', ESSENTIAL_BLOCKS_PRO_NAME ),
                'post-url'               => __( 'Post URL', ESSENTIAL_BLOCKS_PRO_NAME ),
                'post-id'                => __( 'Post ID', ESSENTIAL_BLOCKS_PRO_NAME ),
                'post-slug'              => __( 'Post Slug', ESSENTIAL_BLOCKS_PRO_NAME ),
                'post-excerpt'           => __( 'Post Excerpt', ESSENTIAL_BLOCKS_PRO_NAME ),
                'post-date'              => __( 'Post Date', ESSENTIAL_BLOCKS_PRO_NAME ),
                'post-date-gmt'          => __( 'Post Date GMT', ESSENTIAL_BLOCKS_PRO_NAME ),
                'post-modified'          => __( 'Post Modified', ESSENTIAL_BLOCKS_PRO_NAME ),
                'post-modified-gmt'      => __( 'Post Modified GMT', ESSENTIAL_BLOCKS_PRO_NAME ),
                'post-type'              => __( 'Post Type', ESSENTIAL_BLOCKS_PRO_NAME ),
                'post-status'            => __( 'Post Status', ESSENTIAL_BLOCKS_PRO_NAME ),
                'author-name'            => __( 'Author Name', ESSENTIAL_BLOCKS_PRO_NAME ),
                'author-id'              => __( 'Author ID', ESSENTIAL_BLOCKS_PRO_NAME ),
                'author-posts-url'       => __( 'Author Post URL', ESSENTIAL_BLOCKS_PRO_NAME ),
                'author-profile-picture' => __( 'Author Profile Picture', ESSENTIAL_BLOCKS_PRO_NAME ),
                'author-posts'           => __( 'Author Posts Count', ESSENTIAL_BLOCKS_PRO_NAME ),
                'author-first-name'      => __( 'Author First Name', ESSENTIAL_BLOCKS_PRO_NAME ),
                'author-last-name'       => __( 'Author Last Name', ESSENTIAL_BLOCKS_PRO_NAME ),
                'comment-number'         => __( 'Comment Number', ESSENTIAL_BLOCKS_PRO_NAME )

            ]
        );
    }

    /**
     * Function for getting the content values.
     *
     * @param string field_key
     * @param int $post_id
     * @return string generated value.
     */
    public static function get_values( $field, $post_id ) {

        switch ( $field ) {
            case 'post-title':
                return self::get_post_title( $post_id );
            case 'post-url':
                return self::get_post_url( $post_id );
            case 'post-id':
                return $post_id;
            case 'post-slug':
                return self::get_post_slug( $post_id );
            case 'post-excerpt':
                return self::get_post_excerpt( $post_id );
            case 'post-date':
                return self::get_post_date( $post_id );
            case 'post-date-gmt':
                return self::get_post_date_gmt( $post_id );
            case 'post-modified':
                return self::get_post_modified( $post_id );
            case 'post-modified-gmt':
                return self::get_post_modified_gmt( $post_id );
            case 'post-type':
                return self::get_post_type( $post_id );
            case 'post-status':
                return self::get_post_status( $post_id );
            case 'author-name':
                return self::get_author_name( $post_id );
            case 'author-id':
                return self::get_author_id( $post_id );
            case 'author-posts-url':
                return self::get_author_posts_url( $post_id );
            case 'author-profile-picture':
                return self::get_author_profile_picture( $post_id );
            case 'author-posts':
                return self::get_author_posts( $post_id );
            case 'author-first-name':
                return self::get_author_first_name( $post_id );
            case 'author-last-name':
                return self::get_author_last_name( $post_id );
            case 'comment-number':
                return self::get_comment_number( $post_id );
            default:
                return __( 'The field doesn\'t exists.', ESSENTIAL_BLOCKS_PRO_NAME );
        }
    }

    /**
     * Function for displaying the post-title content.
     *
     * @param string $post_id
     * @return string generated output
     */
    public static function get_post_title( $post_id ) {
        return esc_html(get_the_title( $post_id ));
    }

    /**
     * Function for getting the post URL.
     *
     * @param string post id
     * @return string post url
     */
    public static function get_post_url( $post_id ) {
        return esc_url(get_permalink( $post_id ));
    }

    /**
     * Function for displaying the post-id content.
     *
     * @param int $post_id
     * @return string generated output
     */
    public static function get_post_slug( $post_id ) {
        return get_post_field( 'post_name', $post_id );
    }

    /**
     * Function for displaying the post-excerpt content.
     *
     * @param int $post_id
     * @return string generated output
     */
    public static function get_post_excerpt( $post_id ) {
        return wp_strip_all_tags( get_the_excerpt( $post_id ) );
    }

    /**
     * Function for displaying the post-date content.
     *
     * @param int $post_id
     * @return string generated output
     */
    public static function get_post_date( $post_id ) {
        return get_post_field( 'post_date', $post_id );
    }

    /**
     * Function for displaying the post-date-gmt content.
     *
     * @param int $post_id
     * @return string generated output
     */
    public static function get_post_date_gmt( $post_id ) {
        return get_post_field( 'post_date_gmt', $post_id );
    }

    /**
     * Function for displaying the post-modified content.
     *
     * @param int $post_id
     * @return string generated output
     */
    public static function get_post_modified( $post_id ) {
        return get_post_field( 'post_modified', $post_id );
    }

    /**
     * Function for displaying the post-modified-gmt content.
     *
     * @param int $post_id
     * @return string generated output
     */
    public static function get_post_modified_gmt( $post_id ) {
        return get_post_field( 'post_modified_gmt', $post_id );
    }

    /**
     * Function for displaying the post-type content.
     *
     * @param int $post_id
     * @return string generated output
     */
    public static function get_post_type( $post_id ) {
        return wp_kses_post( get_post_field( 'post_type', $post_id ) );
    }

    /**
     * Function for displaying the post-type content.
     *
     * @param int $post_id
     * @return string generated output
     */
    public static function get_post_status( $post_id ) {
        return wp_kses_post( get_post_field( 'post_status', $post_id ) );
    }

    /**
     * Function for displaying the author-name content.
     *
     * @param int $post_id
     * @return string generated output
     */
    public static function get_author_name( $post_id ) {
        $author_id = get_post_field( 'post_author', $post_id );
        return wp_kses_post( get_the_author_meta( 'display_name', $author_id ) );
    }

    /**
     * Function for displaying the author-id content.
     *
     * @param int $post_id
     * @return string generated output
     */
    public static function get_author_id( $post_id ) {
        return wp_kses_post( get_post_field( 'post_author', $post_id ) );
    }

    /**
     * Function for getting the author posts URL
     *
     * @param string author ID
     * @return string author posts url
     */
    public static function get_author_posts_url( $author_id ) {
        return esc_url(get_author_posts_url( $author_id ));
    }

    /**
     * Function for displaying the author-profile-picture content.
     *
     * @param int $post_id
     * @return string generated output
     */
    public static function get_author_profile_picture( $post_id ) {
        $author_id = get_post_field( 'post_author', $post_id );
        return esc_url(get_avatar_url( $author_id ));
    }

    /**
     * Function for displaying the author-posts content.
     *
     * @param int $post_id
     * @return string generated output
     */
    public static function get_author_posts( $post_id ) {
        $author_id = get_post_field( 'post_author', $post_id );
        return wp_kses_post( count_user_posts( $author_id ) );
    }

    /**
     * Function for displaying the author-first-name content.
     *
     * @param int $post_id
     * @return string generated output
     */
    public static function get_author_first_name( $post_id ) {
        $author_id = get_post_field( 'post_author', $post_id );
        return wp_kses_post( get_the_author_meta( 'first_name', $author_id ) );
    }

    /**
     * Function for displaying the author-last-name content.
     *
     * @param int $post_id
     * @return string generated output
     */
    public static function get_author_last_name( $post_id ) {
        $author_id = get_post_field( 'post_author', $post_id );
        return wp_kses_post( get_the_author_meta( 'last_name', $author_id ) );
    }

    /**
     * Function for displaying the comment-number content.
     *
     * @param int $post_id
     * @return string generated output
     */
    public static function get_comment_number( $post_id ) {
        $comments_number = get_comments_number( $post_id );
        return wp_kses_post( number_format_i18n( $comments_number ) );
    }

    /**
     * Get the featured image object.
     *
     * @param int $post_id
     * @return string image url.
     */
    public static function get_featured_image( $post_id ) {
        $thumbnail_id = get_post_thumbnail_id( $post_id );
        if ( ! $thumbnail_id ) {
            return '';
        }

        $attachment = get_post( $thumbnail_id );
        $size       = 'full';
        return esc_url(wp_get_attachment_image_src( $attachment->ID, $size ));
    }

    /**
     * Function for handling the search post field.
     *
     * @param array previous output value
     * @param string keyword
     * @return array post data object
     */
    function search_posts( $output, $s ) {
        $post_id = [
            'posts_per_page' => 5,
            's'              => $s
        ];

        $the_query = new \WP_Query( $post_id );

        $posts = [];
        if ( $the_query->have_posts() ) {
            while ( $the_query->have_posts() ): $the_query->the_post();
                $posts[get_the_ID()] = get_the_title() . ' (#' . get_the_ID() . ')';
            endwhile;
        }

        wp_reset_postdata();
        return $posts;
    }
}
