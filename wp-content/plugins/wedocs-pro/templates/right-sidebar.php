<div class="wedocs-hide-mobile sticky top-2">
	<?php
	$post_content = get_post( $post->id )->post_content; // Get the post content.
    $pattern      = '/<h([2-6])(.*?)>(.*?)<\/h[2-6]>/si';

    preg_match_all( $pattern, $post_content, $matches, PREG_SET_ORDER );

    $headings = array();
    foreach ( $matches as $match ) {
        $heading_level = intval( $match[1] );
        $heading_text  = strip_tags( $match[3] );
        $heading_id    = sanitize_title( $heading_text );

        $headings[] = array(
            'level' => $heading_level,
            'text'  => $heading_text,
            'id'    => $heading_id
        );
    }
	?>

	<h3 class='widget-title border-b border-solid border-[#eee] !mb-4 pb-4 pt-2'>
		<strong class='!font-bold ml-8'>
			<?php esc_html_e( 'On this page', 'wedocs-pro' ); ?>
		</strong>
	</h3>

	<?php if ( $headings ) : ?>
		<ul class="doc-nav-list list-none ml-5 text-[#333]">
			<?php foreach ( $headings as $index => $heading ) : ?>
				<li class='text-base py-1.5 <?php echo esc_attr( $heading['level'] > 2 ? "font-normal" : "font-semibold" ); ?>'>
					<?php if ( $heading['level'] > 2 ) : ?>
						<span class='<?php echo esc_attr( $tailwind_layout ? "pr-2" : "pr-5" ); ?> border-[#712cf9] text-[#712cf9]'></span>
					<?php endif; ?>
					<span
                        data-tag='h<?php echo esc_attr( $heading['level'] ); ?>'
                        data-value='<?php echo esc_attr( $heading['text'] ); ?>'
                        class='<?php echo esc_attr( ! $tailwind_layout ? 'border-l-2 border-solid border-transparent' : '' ); ?> right-bar-link cursor-pointer text-[#333] py-1 pl-3 pr-2.5'
                    >
                        <?php if ( $heading['level'] > 2 && $tailwind_layout ) : ?>
                            <span class="dashicons dashicons-arrow-right-alt2 text-[12px] h-fit mt-1.5"></span>
                        <?php endif; ?>
						<?php echo wedocs_apply_short_content( strip_tags( $heading['text'] ), 25 ); ?>
					</span>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</div>
