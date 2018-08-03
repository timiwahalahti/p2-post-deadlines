<div class="p2-post-deadlines p2-post-deadlines-upcoming-list">
	<?php foreach ( $posts as $post ) : ?>
		<div class="post">
			<p>
				<a href="<?php echo get_the_permalink( $post['post_id'] ) ?>"><?php echo $post['title'] ?></a><br />
				<?php echo $post['deadline_str'] ?>
			</p>
		</tr>
	<?php endforeach; ?>
</div>
