<?php
/**
 * Template for displaying enrolled course view nav menu
 *
 * @since v.1.0.0
 *
 * @author Themeum
 * @url https://themeum.com
 *
 * @package TutorLMS/Templates
 * @version 1.4.3
 */


if ( ! defined( 'ABSPATH' ) )
	exit;

$course_nav_item = tutor_utils()->course_sub_pages(get_the_ID());
?>

<?php do_action('tutor_course/single/enrolled/nav/before'); ?>

<div class="course-enrolled-nav-wrap course-enrolled-nav-wrap-<?php the_ID(); ?>">
	<nav id="course-enrolled-nav-<?php echo get_the_ID(); ?>" class="course-enrolled-nav course-enrolled-nav-<?php the_ID(); ?>">
		<ul>
			<?php
			$sub_page = get_query_var('course_subpage');
			!$sub_page ? $sub_page='info' : 0;

			foreach ($course_nav_item as $nav_key => $nav_item){
				$active_class = $sub_page === $nav_key? 'active' : '';
				$url = trailingslashit(get_permalink()).($nav_key=='info' ? '' : $nav_key);
				echo "<li class='{$active_class}'>
						<a href='{$url}'>{$nav_item['title']}</a> 
					</li>";
			}
			?>
		</ul>
	</nav>
</div>

<?php do_action('tutor_course/single/enrolled/nav/after'); ?>