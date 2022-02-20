<?php
/**
 * Template for displaying course reviews
 *
 * @since v.1.0.0
 *
 * @author Themeum
 * @url https://themeum.com
 *
 * @package TutorLMS/Templates
 * @version 1.4.5
 */

$disable = ! get_tutor_option( 'enable_course_review' );
if ( $disable ) {
	return;
}

$per_page = tutor_utils()->get_option( 'pagination_per_page', 10 );
$current_page = max(1, (int)tutor_utils()->avalue_dot('current_page', $_POST));
$offset = ($current_page - 1) * $per_page;

$course_id = isset($_POST['course_id']) ? (int)$_POST['course_id'] : get_the_ID();

$reviews = tutor_utils()->get_course_reviews($course_id, $offset, $per_page);
$reviews_total = tutor_utils()->get_course_reviews($course_id, null, null, true);
$rating = tutor_utils()->get_course_rating($course_id);

do_action( 'tutor_course/single/enrolled/before/reviews' );
?>

<div class="tutor-pagination-wrapper-replacable">
	<div class="tutor-course-topics-header">
		<div class="tutor-course-topics-header-left tutor-mb-20">
			<div class="text-primary tutor-text-medium-h6">
				<span>
					<?php
						$review_title = apply_filters( 'tutor_course_reviews_section_title', 'Student Ratings & Reviews' );
						echo esc_html( $review_title, 'tutor' );
					?>
				</span>
			</div>
		</div>
	</div>

	<?php if(! is_array( $reviews ) || ! count( $reviews )): ?>
		<?php tutor_utils()->tutor_empty_state(__('No Review Yet', 'tutor')); ?>
	<?php else: ?>
		<div class="tutor-ratingsreviews">
			<div class="tutor-ratingsreviews-ratings">
				<div class="tutor-ratingsreviews-ratings-avg">
					<div class="text-medium-h1 tutor-color-text-primary">
						<?php echo number_format( $rating->rating_avg, 1 ); ?>
					</div>
					<?php tutor_utils()->star_rating_generator_v2( $rating->rating_avg, null, false, 'tutor-bs-d-block' ); ?>
					<div class="tutor-total-ratings-text tutor-text-regular-body text-subsued">
						<span class="tutor-rating-text-part">
							<?php esc_html_e( 'Total ', 'tutor' ); ?>
						</span>
						<span class="tutor-rating-count-part">
							<?php echo esc_html( count( $reviews ) ); ?>
						</span>
						<span class="tutor-rating-text-part">
							<?php echo esc_html( _n( ' Rating', ' Ratings', count( $reviews ), 'tutor' ) ); ?>
						</span>
					</div>
				</div>
				
				<div class="tutor-ratingsreviews-ratings-all">
					<?php foreach ( $rating->count_by_value as $key => $value ) : ?>
						<?php $rating_count_percent = ( $value > 0 ) ? ( $value * 100 ) / $rating->rating_count : 0; ?>
						<div class="rating-numbers">
							<div class="rating-progress">
								<div class="tutor-ratings tutor-is-sm">
									<div class="tutor-rating-stars">
										<span class="tutor-icon-star-line-filled"></span>
									</div>
									<div class="tutor-rating-text  tutor-text-medium-body  tutor-color-text-primary">
										<?php echo $key; ?>
									</div>
								</div>
								<div class="progress-bar tutor-mt-10" style="--progress-value: <?php echo $rating_count_percent; ?>%">
									<span class="progress-value"></span>
								</div>
							</div>
							<div class="rating-num tutor-text-regular-caption tutor-color-text-subsued">
								<?php
									echo $value . ' ';
									echo $value > 1 ? __( 'ratings', 'tutor' ) : __( 'rating', 'tutor' );
								?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
			
			<div class="tutor-ratingsreviews-reviews">
				<ul class="review-list tutor-m-0">
					<?php
					foreach ( $reviews as $review ) {
						$profile_url = tutor_utils()->profile_url( $review->user_id, false );
						?>
							<li>
								<div>
									<div class="">
										<img class="tutor-avatar-circle tutor-50" src="<?php echo get_avatar_url( $review->user_id ); ?>" alt="student avatar" />
									</div>
									<div class="text-regular-body tutor-color-text-primary tutor-mt-16">
										<a href="<?php echo esc_url( $profile_url ); ?>" class="tutor-reviewer-name">
										<?php echo esc_html( $review->display_name ); ?>
										</a>
									</div>
									<div class="text-regular-small tutor-color-text-hints">
										<span class="tutor-review-time">
											<?php echo sprintf( __( '%s ago', 'tutor' ), human_time_diff( strtotime( $review->comment_date ) ) ); ?>
										</span>
									</div>
								</div>
								<div>
								<?php tutor_utils()->star_rating_generator_v2( $review->rating, null, true, 'tutor-is-sm' ); ?>
									<div class="text-regular-caption tutor-color-text-subsued tutor-mt-10 tutor-review-comment">
									<?php echo htmlspecialchars( $review->comment_content ); ?>
									</div>
								</div>
							</li>
							<?php
					}
					?>
				</ul>
			</div>
		</div>
		
		<?php 
			$pagination_data = array(
				'total_items' => $reviews_total,
				'per_page'    => $per_page,
				'paged'       => $current_page,
				'ajax'		  => array(
					'action' => 'tutor_single_course_reviews_load_more',
					'course_id' => $course_id,
				)
			);

			$pagination_template_frontend = tutor()->path . 'templates/dashboard/elements/pagination.php';
			tutor_load_template_from_custom_path( $pagination_template_frontend, $pagination_data );
		?>
	<?php endif; ?>
</div>

<?php do_action( 'tutor_course/single/enrolled/after/reviews' ); ?>