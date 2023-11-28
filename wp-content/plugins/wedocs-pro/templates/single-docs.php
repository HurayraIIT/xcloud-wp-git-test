<?php
/**
 * The template for displaying a single doc
 *
 * To customize this template, create a folder in your current theme named "wedocs" and copy it there.
 */
$skip_sidebar    = ( get_post_meta( $post->ID, 'skip_sidebar', true ) == 'yes' ) ? true : false;
$layout_settings = wedocs_get_option( 'layout', 'wedocs_settings', [] );
$right_sidebar   = ! empty( $layout_settings[ 'right_bar' ] ) ? esc_html( $layout_settings[ 'right_bar' ] ) : 'on';
$template_name   = ! empty( $layout_settings[ 'template' ] ) ? esc_html( $layout_settings[ 'template' ] ) : 'default';
$active_text     = ! empty( $layout_settings[ 'active_text' ] ) ? esc_html( wedocs_get_array_to_hex( $layout_settings[ 'active_text' ] ) ) : '#3B82F6';
$active_nav_bg   = ! empty( $layout_settings[ 'active_nav_bg' ] ) ? esc_html( wedocs_get_array_to_hex( $layout_settings[ 'active_nav_bg' ] ) ) : '#3B82F6';
$active_nav_text = ! empty( $layout_settings[ 'active_nav_text' ] ) ? esc_html( wedocs_get_array_to_hex( $layout_settings[ 'active_nav_text' ] ) ) : '#fff';

$nav_icon = ! empty( $layout_settings[ 'nav_icon' ] ) ? esc_html( $layout_settings[ 'nav_icon' ] ) : 'on';
$nav_icon = $template_name === 'default' ? $nav_icon : 'off';

$left_bar_args = array(
    'pro'           => true,
    'post'          => $post,
    'nav_icon'      => $nav_icon,
    'right_bar'     => $right_sidebar,
    'template_name' => $template_name,
    'active_nav_bg' => $active_nav_bg,
);

$right_bar_args = array(
    'pro'             => true,
    'post'            => $post,
    'tailwind_layout' => $template_name === 'tailwind',
);

get_header(); ?>

<?php
/**
 * @since 1.4
 *
 * @hooked wedocs_template_wrapper_start - 10
 */
do_action( 'wedocs_before_main_content' );
?>

<?php while ( have_posts() ) {
	the_post(); ?>

	<div class="wedocs-single-wrap">
		<?php
        if ( ! $skip_sidebar ) {
            wedocs_get_template_part( 'left', 'sidebar', $left_bar_args );
        } ?>

		<div class="<?php echo esc_attr( $right_sidebar === 'on' ? 'border-x max-w-[55%] w-[55%]' : 'border-l max-w-[75%] w-[75%]' ); ?> main-content prose prose-headings:pt-2 px-6 border-solid border-[#eee]">
			<?php wedocs_breadcrumbs(); ?>

			<article id="post-<?php the_ID(); ?>" <?php post_class(); ?> itemscope itemtype="http://schema.org/Article">
				<header class="entry-header relative">
					<?php the_title( '<h1 class="entry-title" itemprop="headline">', '</h1>' ); ?>

					<?php if ( wedocs_get_general_settings( 'print', 'on' ) === 'on' ) { ?>
                        <a
                            href="#"
                            title="<?php echo esc_attr( __( 'Print this article', 'wedocs-pro' ) ); ?>"
                            class="wedocs-print-article wedocs-hide-print wedocs-hide-mobile absolute top-1/2 right-0 -translate-y-1/2"
                        >
                            <i class="wedocs-icon wedocs-icon-print"></i>
                        </a>
					<?php } ?>
				</header><!-- .entry-header -->

				<div class="entry-content" itemprop="articleBody">
					<?php
					the_content( sprintf(
					/* translators: %s: Name of current post. */
						wp_kses( __( 'Continue reading %s <span class="meta-nav">&rarr;</span>', 'wedocs-pro' ), [ 'span' => [ 'class' => [] ] ] ),
						the_title( '<span class="screen-reader-text">"', '"</span>', false )
					) );

					wp_link_pages( [
						'before' => '<div class="page-links">' . esc_html__( 'Docs:', 'wedocs-pro' ),
						'after'  => '</div>',
					] );

					$children = wp_list_pages( 'title_li=&order=menu_order&child_of=' . $post->ID . '&echo=0&post_type=' . $post->post_type );
                    $children = str_replace(
                        array( "<ul class='children'>" ),
                        array( '<ul class="children p-0">' ),
                        $children
                    );

					if ( $children ) {
						echo '<div class="article-child well">';
						echo '<h3>' . __( 'Articles', 'wedocs-pro' ) . '</h3>';
						echo '<ul class="p-0">';
						echo $children;
						echo '</ul>';
						echo '</div>';
					}

					$tags_list = wedocs_get_the_doc_tags( $post->ID, '', ', ' );

					if ( $tags_list ) {
						printf( '<span class="tags-links"><span class="screen-reader-text">%1$s </span>%2$s</span>',
							_x( 'Tags', 'Used before tag names.', 'wedocs-pro' ),
							$tags_list
						);
					}
					?>
				</div><!-- .entry-content -->

                <?php wedocs_doc_nav(); ?>

				<footer class="entry-footer wedocs-entry-footer <?php echo esc_attr( $right_sidebar ); ?>">
                    <?php if ( wedocs_get_general_settings( 'email', 'on' ) === 'on' ) { ?>
                        <div class='help-content wedocs-hide-mobile'>
                            <div class='help-panel'>
                                <span class='help-icon'>
                                    <svg width="26" height="25" fill="none" class='wedocs-icon'>
                                        <path
                                            d="M1.429 21.292V9.924c0-.851.425-1.646 1.134-2.118l8.911-5.941c.855-.57 1.969-.57 2.825 0l8.911 5.941c.708.472 1.134 1.267 1.134 2.118v11.367m-22.914 0c0 1.406 1.14 2.546 2.546 2.546h17.822c1.406 0 2.546-1.14 2.546-2.546m-22.914 0l8.593-5.728m14.321 5.728l-8.593-5.728M1.429 9.835l8.593 5.728m14.321-5.728l-8.593 5.728m0 0l-1.452.968c-.855.57-1.969.57-2.825 0l-1.452-.968"
                                            stroke="#9559ff"
                                            stroke-width="1.67"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />
                                    </svg>
                                </span>
                                <span class="wedocs-help-link wedocs-hide-print">
                                    <?php
                                    esc_html_e( 'Still stuck? ', 'wedocs-pro' );

                                    if ( $right_sidebar === 'off' ) {
                                        printf( '<a id="wedocs-stuck-modal" href="%s">%s</a>', '#', __( 'How can we help?', 'wedocs-pro' ) );
                                    }
                                    ?>

                                    <div class="wedocs-article-author" itemprop="author" itemscope itemtype="https://schema.org/Person">
                                        <meta itemprop="name" content="<?php echo get_the_author(); ?>" />
                                        <meta itemprop="url" content="<?php echo get_author_posts_url( get_the_author_meta( 'ID' ) ); ?>" />
                                    </div>

                                    <meta itemprop="datePublished" content="<?php echo get_the_time( 'c' ); ?>"/>
                                    <time itemprop="dateModified" datetime="<?php echo esc_attr( get_the_modified_date( 'c' ) ); ?>"><?php printf( __( 'Updated on %s', 'wedocs-pro' ), get_the_modified_date() ); ?></time>
                                </span>
                                <?php if ( $right_sidebar === 'on' ) : ?>
                                    <span class='help-button'>
                                        <?php printf( '<a id="wedocs-stuck-modal" href="%s">%s</a>', '#', __( 'Contact', 'wedocs-pro' ) ); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php } ?>


                    <?php if ( wedocs_get_general_settings( 'helpful', 'on' ) === 'on' ) { ?>
                        <div class='feedback-content' >
                            <?php wedocs_get_template_part( 'content', 'feedback' ); ?>
                        </div>
                    <?php } ?>
				</footer>

				<?php if ( wedocs_get_general_settings( 'email', 'on' ) === 'on' ) { ?>
					<?php wedocs_get_template_part( 'content', 'modal' ); ?>
				<?php } ?>

				<?php if ( wedocs_get_general_settings( 'comments', 'off' ) === 'on' ) { ?>
					<?php if ( comments_open() || get_comments_number() ) { ?>
						<div class="wedocs-comments-wrap">
							<?php comments_template(); ?>
						</div>
					<?php } ?>
				<?php } ?>

			</article><!-- #post-## -->
		</div><!-- .wedocs-single-content -->

		<?php if ( $right_sidebar === 'on' ) : ?>
			<div class='right-sidebar w-[23%] wedocs-hide-mobile'>
				<?php wedocs_get_template_part( 'right', 'sidebar', $right_bar_args ); ?>
			</div>
		<?php endif; ?>
	</div><!-- .wedocs-single-wrap -->

<?php } ?>

<script>
    const proseSelectors = document.querySelectorAll( '.wedocs-single-wrap .prose a' );
    const leftBarLinkSelectors = document.querySelectorAll( '.doc-nav-list li.page_item a' );
    const rightBarLinkSelectors = document.querySelectorAll( '.right-sidebar .right-bar-link' );

	const parentSelectors = document.querySelector( 'li.page_item.current_page_item a, li.page_item.current_page_ancestor a' );
	const helpLinkSelector = document.querySelector( '.entry-footer.on #wedocs-stuck-modal' );
	const ancestorSelector = document.querySelector( 'li.page_item.current_page_ancestor a[aria-current="page"]' );
	const activeNavSelector = parentSelectors.querySelector( 'span.wedocs-caret' );

    const activeText = '<?php echo $active_text; ?>';
    const activeNavBg = '<?php echo $active_nav_bg; ?>';
    const activeNavText = '<?php echo $active_nav_text; ?>';
    const isTailwindTemplate = '<?php echo $template_name === 'tailwind'; ?>';

    const handleSidebarHoverEffects = ( items ) => {
        items.forEach( sidebar => {
            sidebar.forEach( item => {
                item.addEventListener( 'mouseover', () => {
                    if ( !item.classList.contains( 'active' ) ) {
                        item.style.color = activeText;
                        item.style.borderColor = activeText;
                    }
                });

                item.addEventListener( 'mouseout', () => {
                    if ( !item.classList.contains( 'active' ) ) {
                        item.style.color = '#333';
                        item.style.borderColor = 'transparent';
                    }
                });
            } )
        } );
    };

    // Handle body contents active text color.
    proseSelectors.forEach( item => {
        item.style.color = '<?php echo $active_text; ?>';
    } );

    handleSidebarHoverEffects( [ leftBarLinkSelectors, rightBarLinkSelectors ] );
    rightBarLinkSelectors.forEach( item => {
        item.addEventListener( 'click', ( event ) => {
            const dataTag = event.target.getAttribute( 'data-tag' );
            const dataValue = event.target.getAttribute( 'data-value' );
            const elements = document.querySelectorAll( `.entry-content ${dataTag}` );

            item.style.color = activeText;
            item.style.borderColor = activeText;
            item.style.fontWeight = '600';

            rightBarLinkSelectors.forEach( ( otherItem ) => {
                if ( otherItem !== item ) {
                    otherItem.style.color = '#333';
                    otherItem.style.borderColor = 'transparent';
                    if ( !otherItem.closest( 'li' ).classList.contains( 'font-semibold' ) ) {
                        otherItem.style.fontWeight = '400';
                    }
                }
            });

            elements.forEach( element => {
                if ( element.textContent === dataValue ) {
                    element.scrollIntoView( { behavior: 'smooth', block: 'start' } );
                }
            });

            // Add a class to mark the item as active
            rightBarLinkSelectors.forEach( ( link ) => {
                link.classList.remove( 'active' );
            });

            item.classList.add( 'active' );
        });
    });

    parentSelectors.style.background = activeNavBg;
    parentSelectors.style.color = activeNavText;
    parentSelectors.style.fontWeight = '600';
    parentSelectors.addEventListener( 'mouseout', () => parentSelectors.style.color = activeNavText );
    parentSelectors.addEventListener( 'mouseover', () => parentSelectors.style.color = activeNavText );

    activeNavSelector.style.color = activeNavText;
    activeNavSelector.style.borderColor = activeNavText;
    if ( ancestorSelector !== null ) {
        ancestorSelector.style.background = activeNavBg;
        ancestorSelector.style.color = activeNavText;
        ancestorSelector.style.fontWeight = '600';
        ancestorSelector.addEventListener( 'mouseout', () => ancestorSelector.style.color = activeNavText );
        ancestorSelector.addEventListener( 'mouseover', () => ancestorSelector.style.color = activeNavText );
    }

    helpLinkSelector.style.background = isTailwindTemplate ? activeNavText : activeNavBg;
    helpLinkSelector.style.color = isTailwindTemplate ? '#fff' : activeNavText;

    if ( isTailwindTemplate ) {
        ancestorSelector.style.borderLeft = `1px solid ${ activeNavText }`;
        ancestorSelector.style.marginLeft = `-1px`;
        ancestorSelector.addEventListener( 'mouseout', () => {
            ancestorSelector.style.borderColor = activeNavText;
        });
    }
</script>

<?php get_footer(); ?>
