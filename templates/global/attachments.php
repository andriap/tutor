<?php
/**
 * Display attachments
 *
 * @since v.1.0.0
 * @author themeum
 * @url https://themeum.com
 */

if ( ! defined( 'ABSPATH' ) )
	exit;

$attachments = tutor_utils()->get_attachments();

do_action('tutor_global/before/attachments');

if (is_array($attachments) && count($attachments)){
	?>
    <div class="tutor-page-segment tutor-attachments-wrap">
        <h3><?php _e('Attachments', 'tutor'); ?></h3>
        <?php
        foreach ($attachments as $attachment){
            ?>
            <a href="<?php echo $attachment->url; ?>" class="tutor-lesson-attachment clearfix">
                <div class="tutor-attachment-icon">
                    <img src="<?php echo $attachment->icon; ?>" />
                </div>

                <div class="tutor-attachment-info">
                    <p><?php echo $attachment->name; ?></p>
                    <span><?php echo $attachment->size; ?></span>
                </div>
            </a>
            <?php
        }
        ?>
    </div>
<?php }

do_action('tutor_global/after/attachments'); ?>