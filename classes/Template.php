<?php
/**
 * Template Class
 *
 * @since: v.1.0.0
 */
namespace TUTOR;


if ( ! defined( 'ABSPATH' ) )
	exit;


class Template extends Tutor_Base {

	public function __construct() {
		parent::__construct();

		add_filter( 'template_include', array($this, 'load_course_archive_template'), 99 );
		add_filter( 'template_include', array($this, 'load_single_course_template'), 99 );
		add_filter( 'template_include', array($this, 'load_single_lesson_template'), 99 );

		add_filter( 'template_include', array($this, 'play_private_video'), 99 );

		add_filter('the_content', array($this, 'students_dashboard_page'));
	}

	/**
	 * @param $template
	 *
	 * @return bool|string
	 *
	 * Load default template for course
	 *
	 * @since v.1.0.0
	 *
	 */
	public function load_course_archive_template($template){
		global $wp_query;

		$post_type = get_query_var('post_type');

		if ($post_type === $this->course_post_type && $wp_query->is_archive){
			$template = tutor_get_template('archive-course');
			return $template;
		}
		return $template;
	}

	/**
	 * @param $template
	 *
	 * @return bool|string
	 *
	 * Load Single Course Template
	 *
	 * @since v.1.0.0
	 */
	public function load_single_course_template($template){
		global $wp_query;

		if ($wp_query->is_single && ! empty($wp_query->query_vars['post_type']) && $wp_query->query_vars['post_type'] === $this->course_post_type){

			if (empty( $wp_query->query_vars['course_subpage'])) {
				$template = tutor_get_template( 'single-course' );

				if ( is_user_logged_in() ) {
					if ( tutor_utils()->is_enrolled() ) {
						$template = tutor_get_template( 'single-course-enrolled' );
					}
				}
			}else{
				//If Course Subpage Exists
				if ( is_user_logged_in() ) {
					$template = tutor_get_template( 'single-course-enrolled-'.$wp_query->query_vars['course_subpage']);
				}else{
					$template = tutor_get_template('login');
				}
			}
			return $template;
		}
		return $template;
	}

	/**
	 * @param $template
	 *
	 * @return bool|string
	 *
	 * Load lesson template
	 *
	 * @since v.1.0.0
	 */

	public function load_single_lesson_template($template){
		global $wp_query;

		if ($wp_query->is_single && ! empty($wp_query->query_vars['post_type']) && $wp_query->query_vars['post_type'] === $this->lesson_post_type){
			if (is_user_logged_in()){
				$is_course_enrolled = tutor_utils()->is_course_enrolled_by_lesson();

				if ($is_course_enrolled) {
					$template = tutor_get_template( 'single-lesson' );
				}else{
					//You need to enroll first
					$template = tutor_get_template( 'single.lesson.required-enroll' );
				}
			}else{
				$template = tutor_get_template('login');
			}
			return $template;
		}
		return $template;
	}

	/**
	 * @param $template
	 *
	 * @return mixed
	 *
	 * Play the video in this url.
	 */
	public function play_private_video($template){
		global $wp_query;

		if ($wp_query->is_single && ! empty($wp_query->query_vars['lesson_video']) && $wp_query->query_vars['lesson_video'] === 'true') {
			if (tutor_utils()->is_course_enrolled_by_lesson()) {
				$video_info = tutor_utils()->get_video_info();
				if ( $video_info ) {
					$stream = new Video_Stream( $video_info->path );
					$stream->start();
				}
			}else{
				_e('Permission denied', 'tutor');
			}
			exit();
		}

		return $template;
	}

	public function students_dashboard_page($content){
		$student_dashboard_page_id = (int) tutor_utils()->get_option('student_dashboard');
		if (! get_the_ID() || $student_dashboard_page_id !== get_the_ID()){
			return $content;
		}

		ob_start();
		tutor_load_template( 'dashboard.student.index' );
		return apply_filters( 'tutor_dashboard/student/index', ob_get_clean() );
	}

}