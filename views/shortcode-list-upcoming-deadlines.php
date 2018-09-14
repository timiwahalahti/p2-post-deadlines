<div class="p2-post-deadlines p2-post-deadlines-upcoming-list">
	<?php foreach ( $posts as $post ) : ?>
		<div class="post">
			<p>
				<a href="<?php echo get_the_permalink( $post['post_id'] ) ?>"><?php echo $post['title'] ?></a><br />
				<?php if ( $post['deadline']['is_soon'] ) : ?>
					<strong><?php echo $post['deadline']['str'] ?></strong>
				<?php else : ?>
					<?php echo $post['deadline']['str'] ?>
				<?php endif; ?>
			</p>
		</tr>
	<?php endforeach; ?>
</div>
