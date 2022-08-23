<?php
namespace Tutor\Models;

/**
 * Class QuizModel
 *
 * @since 2.0.10
 */
class QuizModel {

	/**
	 * Get all of the attempts by an user of a quiz
	 *
	 * @param int $quiz_id
	 * @param int $user_id
	 *
	 * @return array|bool|null|object
	 *
	 * @since 1.0.0
	 */

	public function quiz_attempts( $quiz_id = 0, $user_id = 0 ) {
		global $wpdb;

		$quiz_id = tutor_utils()->get_post_id( $quiz_id );
		$user_id = tutor_utils()->get_user_id( $user_id );

		$attempts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT *
			FROM 	{$wpdb->prefix}tutor_quiz_attempts
			WHERE 	quiz_id = %d
					AND user_id = %d
					ORDER BY attempt_id  DESC
			",
				$quiz_id,
				$user_id
			)
		);

		if ( is_array( $attempts ) && count( $attempts ) ) {
			return $attempts;
		}

		return false;
	}

	/**
	 * Get Quiz question by question id
	 *
	 * @param int $question_id
	 *
	 * @return array|bool|object|void|null
	 */
	public static function get_quiz_question_by_id( $question_id = 0 ) {
		global $wpdb;

		if ( $question_id ) {
			$question = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT *
				FROM 	{$wpdb->prefix}tutor_quiz_questions
				WHERE 	question_id = %d
				LIMIT 0, 1;
				",
					$question_id
				)
			);

			return $question;
		}

		return false;
	}

	/**
	 * Get all ended attempts by an user of a quiz
	 *
	 * @param int $quiz_id
	 * @param int $user_id
	 *
	 * @return array|bool|null|object
	 *
	 * @since 1.4.1
	 */
	public function quiz_ended_attempts( $quiz_id = 0, $user_id = 0 ) {
		global $wpdb;

		$quiz_id = tutor_utils()->get_post_id( $quiz_id );
		$user_id = tutor_utils()->get_user_id( $user_id );

		$attempts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT *
			FROM 	{$wpdb->prefix}tutor_quiz_attempts
			WHERE 	quiz_id = %d
					AND user_id = %d
					AND attempt_status != %s
			",
				$quiz_id,
				$user_id,
				'attempt_started'
			)
		);

		if ( is_array( $attempts ) && count( $attempts ) ) {
			return $attempts;
		}

		return false;
	}

	/**
	 * Get the next question order ID
	 *
	 * @param $quiz_id
	 * @return int
	 *
	 * @since 1.0.0
	 */
	public static function quiz_next_question_order_id( $quiz_id ) {
		global $wpdb;

		$last_order = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT MAX(question_order)
			FROM 	{$wpdb->prefix}tutor_quiz_questions
			WHERE 	quiz_id = %d ;
			",
				$quiz_id
			)
		);

		return $last_order + 1;
	}

	/**
	 * Get next quiz question ID
	 *
	 * @param $quiz_id
	 *
	 * @return int
	 * @since 1.0.0
	 */
	public static function quiz_next_question_id() {
		global $wpdb;

		$last_order = (int) $wpdb->get_var( "SELECT MAX(question_id) FROM {$wpdb->prefix}tutor_quiz_questions;" );
		return $last_order + 1;
	}

	/**
	 * Total number of quiz attempts
	 *
	 * @param string $search_term
	 *
	 * @return int
	 * @since 1.0.0
	 */
	public static function get_total_quiz_attempts( $search_term = '', int $course_id = 0, string $tab = '', $date_filter = '' ) {
		global $wpdb;

		if ( '' !== $search_term ) {
			$search_term = '%' . $wpdb->esc_like( $search_term ) . '%';
		}

		// Set query based on action tab.
		$pass_mark     = "(((SUBSTRING_INDEX(SUBSTRING_INDEX(quiz_attempts.attempt_info, '\"passing_grade\";s:2:\"', -1), '\"', 1))/100)*quiz_attempts.total_marks)";
		$pending_count = "(SELECT COUNT(DISTINCT attempt_answer_id) FROM {$wpdb->prefix}tutor_quiz_attempt_answers WHERE quiz_attempt_id=quiz_attempts.attempt_id AND is_correct IS NULL)";

		$tab_join   = '';
		$tab_clause = '';
		if ( '' !== $tab ) {
			$tab_join = "INNER JOIN {$wpdb->prefix}tutor_quiz_attempt_answers AS ans ON quiz_attempts.attempt_id = ans.quiz_attempt_id";
		}
		switch ( $tab ) {
			case 'pass':
				// Just check if the earned mark is greater than pass mark.
				// It doesn't matter if there is any pending or failed question.
				$tab_clause = " AND quiz_attempts.earned_marks >= {$pass_mark}  ";
				break;

			case 'fail':
				// Check if earned marks is less than pass mark and there is no pending question.
				$tab_clause = " AND quiz_attempts.earned_marks < {$pass_mark}
									AND {$pending_count} = 0 ";
				break;
			case 'pending':
				$tab_clause = " AND {$pending_count} > 0 ";
				break;
		}

		$course_join   = '';
		$course_clause = '';
		if ( $course_id || '' !== $search_term ) {
			$course_join = "INNER JOIN {$wpdb->posts} AS course ON course.ID = quiz_attempts.course_id";
		}
		if ( $course_id ) {
			$course_clause = " AND quiz_attempts.course_id = $course_id";
		}

		$user_join    = '';
		$user_clause  = '';
		$search_term1 = sanitize_text_field( $search_term );
		$search_term2 = sanitize_text_field( $search_term );
		$search_term3 = sanitize_text_field( $search_term );
		if ( '' !== $search_term ) {
			$user_join = "INNER JOIN {$wpdb->users}
			ON quiz_attempts.user_id = {$wpdb->users}.ID";

			$user_clause = "AND ( user_email LIKE '%$search_term1%' OR display_name LIKE '%$search_term2%' OR course.post_title LIKE '%$search_term3%' )";
		}

		if ( '' !== $date_filter ) {
			$date_filter = $date_filter != '' ? tutor_get_formated_date( 'Y-m-d', $date_filter ) : '';
			$date_filter = $date_filter != '' ? " AND  DATE(quiz_attempts.attempt_started_at) = '$date_filter' " : '';
		}

		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT( DISTINCT attempt_id)
		 	FROM 	{$wpdb->prefix}tutor_quiz_attempts quiz_attempts
					INNER JOIN {$wpdb->posts} quiz
							ON quiz_attempts.quiz_id = quiz.ID
					{$user_join}
					{$course_join}
					{$tab_join}
			WHERE 	attempt_status != %s
				{$user_clause}
				{$course_clause}
				{$tab_clause}
				{$date_filter}
			",
				'attempt_started'
			)
		);

		return (int) $count;
	}

	/**
	 * Get the all quiz attempts
	 *
	 * @param int    $start
	 * @param int    $limit
	 * @param string $search_term
	 *
	 * @since 1.0.0
	 * @return array|null|object
	 *
	 * Sorting paramas added
	 *
	 * @since 1.9.5
	 */
	public static function get_quiz_attempts( $start = 0, $limit = 10, $search_filter = '', $course_filter = '', $date_filter = '', $order_filter = 'DESC', $result_state = null, $count_only = false, $instructor_id_check = false ) {
		global $wpdb;

		$search_term_raw = $search_filter;
		$search_filter   = '%' . $wpdb->esc_like( $search_filter ) . '%';

		// Filter by course
		if ( $course_filter != '' ) {
			! is_array( $course_filter ) ? $course_filter = array( $course_filter ) : 0;
			$course_ids                                   = implode( ',', $course_filter );
			$course_filter                                = " AND quiz_attempts.course_id IN ($course_ids) ";
		}

		// Filter by date
		$date_filter = $date_filter != '' ? tutor_get_formated_date( 'Y-m-d', $date_filter ) : '';
		$date_filter = $date_filter != '' ? " AND  DATE(quiz_attempts.attempt_started_at) = '$date_filter' " : '';

		$result_clause  = '';
		$select_columns = $count_only ? 'COUNT(DISTINCT quiz_attempts.attempt_id)' : 'DISTINCT quiz_attempts.*, quiz.post_title, users.user_email, users.user_login, users.display_name';
		$limit_offset   = $count_only ? '' : ' LIMIT ' . $limit . ' OFFSET ' . $start;

		$pass_mark     = "(((SUBSTRING_INDEX(SUBSTRING_INDEX(quiz_attempts.attempt_info, '\"passing_grade\";s:2:\"', -1), '\"', 1))/100)*quiz_attempts.total_marks)";
		$pending_count = "(SELECT COUNT(DISTINCT attempt_answer_id) FROM {$wpdb->prefix}tutor_quiz_attempt_answers WHERE quiz_attempt_id=quiz_attempts.attempt_id AND is_correct IS NULL)";

		// Get attempts by instructor ID
		$instructor_clause = '';
		$instructor_join   = '';
		if ( $instructor_id_check ) {
			$current_user_id = get_current_user_id();
			$instructor_id   = tutor_utils()->has_user_role( 'administrator', $current_user_id ) ? null : $current_user_id;

			if ( $instructor_id ) {
				// $instructor_clause = " AND (instructor_meta.meta_key='_tutor_instructor_course_id' AND instructor_meta.user_id=$instructor_id)";
				$instructor_clause = " INNER JOIN {$wpdb->prefix}usermeta AS instructor_meta ON course.ID = instructor_meta.meta_value AND (instructor_meta.meta_key='_tutor_instructor_course_id' AND instructor_meta.user_id=$instructor_id) ";
			}
		}

		// Switc hthrough result state and assign meta clause
		switch ( $result_state ) {
			case 'pass':
				// Just check if the earned mark is greater than pass mark
				// It doesn't matter if there is any pending or failed question
				$result_clause = " AND quiz_attempts.earned_marks>={$pass_mark}  ";
				break;

			case 'fail':
				// Check if earned marks is less than pass mark and there is no pending question
				//
				$result_clause = " AND quiz_attempts.earned_marks<{$pass_mark}
								   AND {$pending_count}=0 ";
				break;

			case 'pending':
				$result_clause = " AND {$pending_count}>0 ";
				break;
		}

		$query = $wpdb->prepare(
			"SELECT {$select_columns}
		 	FROM {$wpdb->prefix}tutor_quiz_attempts quiz_attempts
					INNER JOIN {$wpdb->posts} quiz ON quiz_attempts.quiz_id = quiz.ID
					INNER JOIN {$wpdb->users} AS users ON quiz_attempts.user_id = users.ID
					INNER JOIN {$wpdb->posts} AS course ON course.ID = quiz_attempts.course_id
					INNER JOIN {$wpdb->prefix}tutor_quiz_attempt_answers AS ans ON quiz_attempts.attempt_id = ans.quiz_attempt_id
					{$instructor_clause}
			WHERE 	quiz_attempts.attempt_ended_at IS NOT NULL
					AND (
							users.user_email = %s
							OR users.display_name LIKE %s
							OR quiz.post_title LIKE %s
							OR course.post_title LIKE %s
						)
					AND quiz_attempts.attempt_ended_at IS NOT NULL
					{$result_clause}
					{$course_filter}
					{$date_filter}
			ORDER 	BY quiz_attempts.attempt_ended_at {$order_filter} {$limit_offset}",
			$search_term_raw,
			$search_filter,
			$search_filter,
			$search_filter
		);

		return $count_only ? $wpdb->get_var( $query ) : $wpdb->get_results( $query );
	}

	/**
	 * Delete quizattempt for user
	 *
	 * @param mixed $attempt_ids
	 * @since 1.9.5
	 */
	public static function delete_quiz_attempt( $attempt_ids ) {
		global $wpdb;

		// Singlular to array
		! is_array( $attempt_ids ) ? $attempt_ids = array( $attempt_ids ) : 0;

		if ( count( $attempt_ids ) ) {
			$attempt_ids = implode( ',', $attempt_ids );

			// Deleting attempt (comment), child attempt and attempt meta (comment meta)
			$wpdb->query( "DELETE FROM {$wpdb->prefix}tutor_quiz_attempts WHERE attempt_id IN($attempt_ids)" );
			$wpdb->query( "DELETE FROM {$wpdb->prefix}tutor_quiz_attempt_answers WHERE quiz_attempt_id IN($attempt_ids)" );
		}
	}

	/**
	 * Sorting params added on quiz attempt
	 *
	 * SQL query updated
	 *
	 * @since 1.9.5
	 */
	public static function get_quiz_attempts_by_course_ids( $start = 0, $limit = 10, $course_ids = array(), $search_filter = '', $course_filter = '', $date_filter = '', $order_filter = '', $user_id = null, $count_only = false, $all_attempt = false ) {
		global $wpdb;
		$search_filter = sanitize_text_field( $search_filter );
		$course_filter = sanitize_text_field( $course_filter );
		$date_filter   = sanitize_text_field( $date_filter );

		$course_ids = array_map(
			function ( $id ) {
				return "'" . esc_sql( $id ) . "'";
			},
			$course_ids
		);

		$course_ids_in = count( $course_ids ) ? ' AND quiz_attempts.course_id IN (' . implode( ', ', $course_ids ) . ') ' : '';

		$search_filter   = $search_filter ? '%' . $wpdb->esc_like( $search_filter ) . '%' : '';
		$search_term_raw = $search_filter;
		$search_filter   = $search_filter ? "AND ( users.user_email = '{$search_term_raw}' OR users.display_name LIKE {$search_filter} OR quiz.post_title LIKE {$search_filter} OR course.post_title LIKE {$search_filter} )" : '';

		$course_filter = $course_filter != '' ? " AND quiz_attempts.course_id = $course_filter " : '';
		$date_filter   = $date_filter != '' ? tutor_get_formated_date( 'Y-m-d', $date_filter ) : '';
		$date_filter   = $date_filter != '' ? " AND  DATE(quiz_attempts.attempt_started_at) = '$date_filter' " : '';
		$user_filter   = $user_id ? ' AND user_id=\'' . esc_sql( $user_id ) . '\' ' : '';

		$limit_offset = $count_only ? '' : " LIMIT 	{$start}, {$limit} ";
		$select_col   = $count_only ? ' COUNT(DISTINCT quiz_attempts.attempt_id) ' : ' quiz_attempts.*, users.*, quiz.* ';

		$attempt_type = $all_attempt ? '' : " AND quiz_attempts.attempt_status != 'attempt_started' ";

		$query = "SELECT {$select_col}
			FROM	{$wpdb->prefix}tutor_quiz_attempts AS quiz_attempts
					INNER JOIN {$wpdb->posts} AS quiz
							ON quiz_attempts.quiz_id = quiz.ID
					INNER JOIN {$wpdb->users} AS users
							ON quiz_attempts.user_id = users.ID
					INNER JOIN {$wpdb->posts} AS course
							ON course.ID = quiz_attempts.course_id
			WHERE 	1=1
					{$attempt_type}
					{$course_ids_in}
					{$search_filter}
					{$course_filter}
					{$date_filter}
					{$user_filter}
			ORDER 	BY quiz_attempts.attempt_id {$order_filter} {$limit_offset};";

		return $count_only ? $wpdb->get_var( $query ) : $wpdb->get_results( $query );
	}

	/**
	 * Get answers list by quiz question
	 *
	 * @param int  $question_id
	 * @param bool $rand
	 *
	 * @return array|bool|null|object
	 *
	 * @since 1.0.0
	 */
	public static function get_answers_by_quiz_question( $question_id, $rand = false ) {
		global $wpdb;

		$question = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT *
			FROM	{$wpdb->prefix}tutor_quiz_questions
			WHERE	question_id = %d;
			",
				$question_id
			)
		);

		if ( ! $question ) {
			return false;
		}

		$order = ' answer_order ASC ';
		if ( $question->question_type === 'ordering' ) {
			$order = ' RAND() ';
		}

		if ( $rand ) {
			$order = ' RAND() ';
		}

		$answers = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT *
			FROM 	{$wpdb->prefix}tutor_quiz_question_answers
			WHERE 	belongs_question_id = %d
					AND belongs_question_type = %s
			ORDER BY {$order}
			",
				$question_id,
				$question->question_type
			)
		);

		return $answers;
	}

	/**
	 * Get quiz answers by attempt id
	 *
	 * @param $attempt_id
	 *
	 * @return array|null|object
	 *
	 * @since 1.0.0
	 */
	public static function get_quiz_answers_by_attempt_id( $attempt_id, $add_index = false ) {
		global $wpdb;

		$ids    = is_array( $attempt_id ) ? $attempt_id : array( $attempt_id );
		$ids_in = implode( ',', $ids );

		if ( empty( $ids_in ) ) {
			// Prevent empty
			return array();
		}

		$results = $wpdb->get_results(
			"SELECT answers.*,
					question.question_title,
					question.question_type
			FROM 	{$wpdb->prefix}tutor_quiz_attempt_answers answers
					LEFT JOIN {$wpdb->prefix}tutor_quiz_questions question
						   ON answers.question_id = question.question_id
			WHERE 	answers.quiz_attempt_id IN ({$ids_in})
			ORDER BY attempt_answer_id ASC;"
		);

		if ( $add_index ) {
			$new_array = array();

			foreach ( $results as $result ) {
				! isset( $new_array[ $result->quiz_attempt_id ] ) ? $new_array[ $result->quiz_attempt_id ] = array() : 0;
				$new_array[ $result->quiz_attempt_id ][] = $result;
			}

			return $new_array;
		}

		return $results;
	}

	/**
	 * Get single answer by answer_id
	 *
	 * @param $answer_id array|init
	 *
	 * @return array|null|object
	 *
	 * @since 1.0.0
	 */
	public static function get_answer_by_id( $answer_id ) {
		global $wpdb;

		! is_array( $answer_id ) ? $answer_id = array( $answer_id ) : 0;

		$answer_id = array_map(
			function ( $id ) {
				return "'" . esc_sql( $id ) . "'";
			},
			$answer_id
		);

		$in_ids_string = implode( ', ', $answer_id );

		$answer = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT answer.*,
					question.question_title,
					question.question_type
			FROM 	{$wpdb->prefix}tutor_quiz_question_answers answer
					LEFT JOIN {$wpdb->prefix}tutor_quiz_questions question
						   ON answer.belongs_question_id = question.question_id
			WHERE 	answer.answer_id IN (" . $in_ids_string . ')
					AND 1 = %d;
			',
				1
			)
		);

		return $answer;
	}

	/**
	 * Get quiz attempt timing
	 *
	 * @param mixed $attempt_data
	 * @return array
	 * 
	 * @since 1.0.0
	 */
	public static function get_quiz_attempt_timing( $attempt_data ) {
		$attempt_duration       = '';
		$attempt_duration_taken = '';
		$attempt_info           = @unserialize( $attempt_data->attempt_info );
		if ( is_array( $attempt_info ) ) {
			// Allowed duration
			if ( isset( $attempt_info['time_limit'] ) ) {
				$attempt_duration = $attempt_info['time_limit']['time_value'] . ' ' . __( ucwords( $attempt_info['time_limit']['time_type'] ), 'tutor' );
			}

			// Taken duration
			$seconds                = strtotime( $attempt_data->attempt_ended_at ) - strtotime( $attempt_data->attempt_started_at );
			$attempt_duration_taken = tutor_utils()->seconds_to_time( $seconds );
		}

		return compact( 'attempt_duration', 'attempt_duration_taken' );
	}
}