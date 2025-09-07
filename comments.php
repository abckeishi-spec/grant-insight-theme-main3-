<?php
/**
 * The template for displaying comments
 *
 * @package Grant_Insight_V4
 */

// Return early if password protected
if (post_password_required()) {
    return;
}
?>

<div id="comments" class="comments-area" style="margin-top: 40px; padding: 30px; background: #f9f9f9; border-radius: 8px;">

    <?php if (have_comments()) : ?>
        <h2 class="comments-title" style="margin-bottom: 20px;">
            <?php
            $comment_count = get_comments_number();
            if ('1' === $comment_count) {
                echo '1件のコメント';
            } else {
                printf('%s件のコメント', $comment_count);
            }
            ?>
        </h2>

        <ol class="comment-list" style="list-style: none; padding: 0;">
            <?php
            wp_list_comments(array(
                'style'      => 'ol',
                'short_ping' => true,
                'avatar_size' => 50,
            ));
            ?>
        </ol>

        <?php
        the_comments_navigation(array(
            'prev_text' => '← 古いコメント',
            'next_text' => '新しいコメント →',
        ));
        ?>

    <?php endif; ?>

    <?php
    // If comments are closed and there are comments
    if (!comments_open() && get_comments_number() && post_type_supports(get_post_type(), 'comments')) :
        ?>
        <p class="no-comments" style="padding: 20px; background: #fff; border-radius: 4px;">
            コメントは閉じられています。
        </p>
    <?php endif; ?>

    <?php
    // Comment form
    $commenter = wp_get_current_commenter();
    $req = get_option('require_name_email');
    $aria_req = ($req ? " aria-required='true'" : '');

    $fields = array(
        'author' =>
            '<p class="comment-form-author">' .
            '<label for="author">名前 ' . ($req ? '<span class="required">*</span>' : '') . '</label> ' .
            '<input id="author" name="author" type="text" value="' . esc_attr($commenter['comment_author']) . '"' . $aria_req . ' />' .
            '</p>',
        
        'email' =>
            '<p class="comment-form-email">' .
            '<label for="email">メールアドレス ' . ($req ? '<span class="required">*</span>' : '') . '</label> ' .
            '<input id="email" name="email" type="text" value="' . esc_attr($commenter['comment_author_email']) . '"' . $aria_req . ' />' .
            '</p>',
        
        'url' =>
            '<p class="comment-form-url">' .
            '<label for="url">ウェブサイト</label>' .
            '<input id="url" name="url" type="text" value="' . esc_attr($commenter['comment_author_url']) . '" />' .
            '</p>',
    );

    $args = array(
        'id_form'           => 'commentform',
        'class_form'        => 'comment-form',
        'id_submit'         => 'submit',
        'class_submit'      => 'submit',
        'name_submit'       => 'submit',
        'title_reply'       => 'コメントを残す',
        'title_reply_to'    => '%s へ返信',
        'cancel_reply_link' => 'キャンセル',
        'label_submit'      => 'コメントを送信',
        'format'            => 'xhtml',
        'comment_field'     =>
            '<p class="comment-form-comment">' .
            '<label for="comment">コメント</label>' .
            '<textarea id="comment" name="comment" cols="45" rows="8" aria-required="true"></textarea>' .
            '</p>',
        'fields'            => apply_filters('comment_form_default_fields', $fields),
    );

    comment_form($args);
    ?>

</div><!-- #comments -->