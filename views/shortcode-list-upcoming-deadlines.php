<div class="post-deadlines post-deadlines-upcoming-list">
	<?php
	// Loop posts.
	foreach ( $posts as $post ) : ?>
		<div class="post">
			<p>
				<a href="<?php echo get_the_permalink( $post['post_id'] ) ?>"><?php echo $post['title'] ?></a><br />
				<?php // If deadline is soon (today or tomorrow), use bold.
				if ( $post['deadline']['is_soon'] ) : ?>
					<strong><?php echo $post['deadline']['str'] ?></strong>
				<?php else : ?>
					<?php echo $post['deadline']['str'] ?>
				<?php endif; ?>
			</p>
		</tr>
	<?php endforeach; ?>
</div>
