<?php
/**
 * The sidebar containing the main widget area
 *
 * @package Grant_Insight_V4
 */

if (!is_active_sidebar('sidebar-1')) {
    return;
}
?>

<aside id="secondary" class="widget-area" style="padding: 20px; background: #f5f5f5; border-radius: 8px;">
    <?php dynamic_sidebar('sidebar-1'); ?>
</aside><!-- #secondary -->