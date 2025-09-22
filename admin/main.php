<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

// Get statistics
$listings_count = wp_count_posts('business_listing');
$plans_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}blp_plans WHERE active = 1");
$subscriptions_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}blp_subscriptions WHERE status = 'active'");
$total_revenue = $wpdb->get_var("
    SELECT SUM(p.price) 
    FROM {$wpdb->prefix}blp_subscriptions s 
    LEFT JOIN {$wpdb->prefix}blp_plans p ON s.plan_id = p.id 
    WHERE s.status = 'active'
");

// Get recent activity
$recent_listings = get_posts(array(
    'post_type' => 'business_listing',
    'numberposts' => 10,
    'post_status' => array('publish', 'pending', 'draft'),
    'orderby' => 'date',
    'order' => 'DESC'
));

$recent_subscriptions = $wpdb->get_results("
    SELECT s.*, p.name as plan_name, u.display_name, u.user_email 
    FROM {$wpdb->prefix}blp_subscriptions s
    LEFT JOIN {$wpdb->prefix}blp_plans p ON s.plan_id = p.id
    LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
    ORDER BY s.created_at DESC
    LIMIT 5
");
?>

<div class="wrap">
    <h1><?php _e('Business Listings Pro Dashboard', 'business-listings-pro'); ?></h1>
    
    <div class="blp-dashboard-stats">
        <div class="blp-stat-card">
            <div class="blp-stat-icon">
                <span class="dashicons dashicons-building"></span>
            </div>
            <div class="blp-stat-content">
                <h3><?php _e('Total Listings', 'business-listings-pro'); ?></h3>
                <div class="blp-stat-number"><?php echo ($listings_count->publish + $listings_count->pending + $listings_count->draft); ?></div>
                <div class="blp-stat-sub">
                    <span class="published"><?php echo $listings_count->publish; ?> <?php _e('Published', 'business-listings-pro'); ?></span> | 
                    <span class="pending"><?php echo $listings_count->pending; ?> <?php _e('Pending', 'business-listings-pro'); ?></span>
                    <?php if ($listings_count->draft > 0): ?>
                        | <span class="draft"><?php echo $listings_count->draft; ?> <?php _e('Draft', 'business-listings-pro'); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="blp-stat-card">
            <div class="blp-stat-icon">
                <span class="dashicons dashicons-tag"></span>
            </div>
            <div class="blp-stat-content">
                <h3><?php _e('Active Plans', 'business-listings-pro'); ?></h3>
                <div class="blp-stat-number"><?php echo $plans_count; ?></div>
                <div class="blp-stat-sub">
                    <a href="<?php echo admin_url('admin.php?page=blp-plans'); ?>">
                        <?php _e('Manage Plans', 'business-listings-pro'); ?>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="blp-stat-card">
            <div class="blp-stat-icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="blp-stat-content">
                <h3><?php _e('Active Subscriptions', 'business-listings-pro'); ?></h3>
                <div class="blp-stat-number"><?php echo $subscriptions_count; ?></div>
                <div class="blp-stat-sub">
                    <a href="<?php echo admin_url('admin.php?page=blp-subscriptions'); ?>">
                        <?php _e('View All', 'business-listings-pro'); ?>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="blp-stat-card">
            <div class="blp-stat-icon">
                <span class="dashicons dashicons-money-alt"></span>
            </div>
            <div class="blp-stat-content">
                <h3><?php _e('Monthly Revenue', 'business-listings-pro'); ?></h3>
                <div class="blp-stat-number">$<?php echo number_format($total_revenue ?: 0, 2); ?></div>
                <div class="blp-stat-sub">
                    <?php _e('From active subscriptions', 'business-listings-pro'); ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="blp-admin-grid">
        <div class="blp-admin-section">
            <div class="blp-quick-actions">
                <h2><?php _e('Quick Actions', 'business-listings-pro'); ?></h2>
                <div class="blp-action-buttons">
                    <a href="<?php echo admin_url('post-new.php?post_type=business_listing'); ?>" class="button button-primary">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php _e('Add New Listing', 'business-listings-pro'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=blp-plans'); ?>" class="button">
                        <span class="dashicons dashicons-tag"></span>
                        <?php _e('Manage Plans', 'business-listings-pro'); ?>
                    </a>
                    <a href="<?php echo admin_url('edit.php?post_type=business_listing&post_status=pending'); ?>" class="button">
                        <span class="dashicons dashicons-visibility"></span>
                        <?php _e('Review Pending', 'business-listings-pro'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=blp-settings'); ?>" class="button">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <?php _e('Settings', 'business-listings-pro'); ?>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="blp-admin-section">
            <div class="blp-system-status">
                <h2><?php _e('System Status', 'business-listings-pro'); ?></h2>
                <div class="blp-status-items">
                    <div class="blp-status-item">
                        <span class="blp-status-label"><?php _e('PayPal Configuration:', 'business-listings-pro'); ?></span>
                        <span class="blp-status-value <?php echo get_option('blp_paypal_email') ? 'status-ok' : 'status-warning'; ?>">
                            <?php echo get_option('blp_paypal_email') ? __('Configured', 'business-listings-pro') : __('Not Configured', 'business-listings-pro'); ?>
                        </span>
                    </div>
                    <div class="blp-status-item">
                        <span class="blp-status-label"><?php _e('Stripe Configuration:', 'business-listings-pro'); ?></span>
                        <span class="blp-status-value <?php echo get_option('blp_stripe_public_key') ? 'status-ok' : 'status-warning'; ?>">
                            <?php echo get_option('blp_stripe_public_key') ? __('Configured', 'business-listings-pro') : __('Not Configured', 'business-listings-pro'); ?>
                        </span>
                    </div>
                    <div class="blp-status-item">
                        <span class="blp-status-label"><?php _e('Approval Required:', 'business-listings-pro'); ?></span>
                        <span class="blp-status-value">
                            <?php echo get_option('blp_require_approval', true) ? __('Yes', 'business-listings-pro') : __('No', 'business-listings-pro'); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="blp-admin-grid">
        <div class="blp-admin-section">
            <div class="blp-recent-activity">
                <h2><?php _e('Recent Listings', 'business-listings-pro'); ?></h2>
                
                <?php if ($recent_listings): ?>
                    <div class="blp-activity-list">
                        <?php foreach ($recent_listings as $listing): ?>
                            <div class="blp-activity-item">
                                <div class="blp-activity-content">
                                    <div class="blp-activity-title">
                                        <a href="<?php echo get_edit_post_link($listing->ID); ?>">
                                            <?php echo esc_html($listing->post_title); ?>
                                        </a>
                                    </div>
                                    <div class="blp-activity-meta">
                                        <span class="blp-activity-author">
                                            <?php _e('by', 'business-listings-pro'); ?> 
                                            <?php echo get_the_author_meta('display_name', $listing->post_author); ?>
                                        </span>
                                        <span class="blp-activity-date">
                                            <?php echo human_time_diff(strtotime($listing->post_date), current_time('timestamp')); ?> 
                                            <?php _e('ago', 'business-listings-pro'); ?>
                                        </span>
                                        <span class="blp-activity-status status-<?php echo $listing->post_status; ?>">
                                            <?php echo ucfirst($listing->post_status); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="blp-activity-actions">
                                    <?php if ($listing->post_status === 'pending'): ?>
                                        <button class="button button-small blp-approve-listing" 
                                                data-listing-id="<?php echo $listing->ID; ?>"
                                                title="<?php _e('Approve Listing', 'business-listings-pro'); ?>">
                                            <?php _e('Approve', 'business-listings-pro'); ?>
                                        </button>
                                    <?php endif; ?>
                                    <a href="<?php echo get_edit_post_link($listing->ID); ?>" 
                                       class="button button-small">
                                        <?php _e('Edit', 'business-listings-pro'); ?>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="blp-activity-footer">
                        <a href="<?php echo admin_url('edit.php?post_type=business_listing'); ?>" class="button">
                            <?php _e('View All Listings', 'business-listings-pro'); ?>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="blp-empty-state">
                        <p><?php _e('No listings found.', 'business-listings-pro'); ?></p>
                        <a href="<?php echo admin_url('post-new.php?post_type=business_listing'); ?>" class="button button-primary">
                            <?php _e('Create First Listing', 'business-listings-pro'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="blp-admin-section">
            <div class="blp-recent-subscriptions">
                <h2><?php _e('Recent Subscriptions', 'business-listings-pro'); ?></h2>
                
                <?php if ($recent_subscriptions): ?>
                    <div class="blp-activity-list">
                        <?php foreach ($recent_subscriptions as $subscription): ?>
                            <div class="blp-activity-item">
                                <div class="blp-activity-content">
                                    <div class="blp-activity-title">
                                        <?php echo esc_html($subscription->display_name); ?>
                                    </div>
                                    <div class="blp-activity-meta">
                                        <span class="blp-activity-plan">
                                            <?php echo esc_html($subscription->plan_name); ?>
                                        </span>
                                        <span class="blp-activity-date">
                                            <?php echo human_time_diff(strtotime($subscription->created_at), current_time('timestamp')); ?> 
                                            <?php _e('ago', 'business-listings-pro'); ?>
                                        </span>
                                        <span class="blp-activity-status status-<?php echo $subscription->status; ?>">
                                            <?php echo ucfirst($subscription->status); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="blp-activity-price">
                                    $<?php echo number_format($subscription->price ?? 0, 2); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="blp-activity-footer">
                        <a href="<?php echo admin_url('admin.php?page=blp-subscriptions'); ?>" class="button">
                            <?php _e('View All Subscriptions', 'business-listings-pro'); ?>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="blp-empty-state">
                        <p><?php _e('No subscriptions found.', 'business-listings-pro'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=blp-plans'); ?>" class="button button-primary">
                            <?php _e('Create Plans', 'business-listings-pro'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="blp-admin-section blp-shortcodes-info">
        <h2><?php _e('Shortcodes', 'business-listings-pro'); ?></h2>
        <p><?php _e('Use these shortcodes to display content on your pages:', 'business-listings-pro'); ?></p>
        
        <div class="blp-shortcode-grid">
            <div class="blp-shortcode-item">
                <div class="blp-shortcode-code">
                    <code>[blp_plans]</code>
                    <button class="blp-copy-shortcode" data-shortcode="[blp_plans]" title="<?php _e('Copy to clipboard', 'business-listings-pro'); ?>">
                        <span class="dashicons dashicons-admin-page"></span>
                    </button>
                </div>
                <div class="blp-shortcode-desc">
                    <?php _e('Display subscription plans for users to select and purchase', 'business-listings-pro'); ?>
                </div>
            </div>
            
            <div class="blp-shortcode-item">
                <div class="blp-shortcode-code">
                    <code>[blp_listings]</code>
                    <button class="blp-copy-shortcode" data-shortcode="[blp_listings]" title="<?php _e('Copy to clipboard', 'business-listings-pro'); ?>">
                        <span class="dashicons dashicons-admin-page"></span>
                    </button>
                </div>
                <div class="blp-shortcode-desc">
                    <?php _e('Display published business listings in a grid layout', 'business-listings-pro'); ?>
                </div>
            </div>
            
            <div class="blp-shortcode-item">
                <div class="blp-shortcode-code">
                    <code>[blp_dashboard]</code>
                    <button class="blp-copy-shortcode" data-shortcode="[blp_dashboard]" title="<?php _e('Copy to clipboard', 'business-listings-pro'); ?>">
                        <span class="dashicons dashicons-admin-page"></span>
                    </button>
                </div>
                <div class="blp-shortcode-desc">
                    <?php _e('Display user dashboard for managing their business listings (requires login)', 'business-listings-pro'); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Copy shortcode functionality
    $('.blp-copy-shortcode').on('click', function() {
        const shortcode = $(this).data('shortcode');
        const button = $(this);
        
        // Create temporary input to copy text
        const temp = $('<input>');
        $('body').append(temp);
        temp.val(shortcode).select();
        document.execCommand('copy');
        temp.remove();
        
        // Show feedback
        const originalIcon = button.find('.dashicons').attr('class');
        button.find('.dashicons').attr('class', 'dashicons dashicons-yes-alt');
        
        setTimeout(() => {
            button.find('.dashicons').attr('class', originalIcon);
        }, 2000);
    });
    
    // Approve listing functionality
    $('.blp-approve-listing').on('click', function() {
        const listingId = $(this).data('listing-id');
        const button = $(this);
        const originalText = button.text();
        
        button.text('<?php _e('Approving...', 'business-listings-pro'); ?>').prop('disabled', true);
        
        $.post(ajaxurl, {
            action: 'blp_update_listing_status',
            nonce: '<?php echo wp_create_nonce('blp_admin_nonce'); ?>',
            listing_id: listingId,
            status: 'publish'
        })
        .done(function(response) {
            if (response.success) {
                button.closest('.blp-activity-item').find('.blp-activity-status')
                    .removeClass('status-pending')
                    .addClass('status-publish')
                    .text('<?php _e('Published', 'business-listings-pro'); ?>');
                button.remove();
            } else {
                alert(response.data || '<?php _e('Error approving listing', 'business-listings-pro'); ?>');
                button.text(originalText).prop('disabled', false);
            }
        })
        .fail(function() {
            alert('<?php _e('Error approving listing', 'business-listings-pro'); ?>');
            button.text(originalText).prop('disabled', false);
        });
    });
});
</script>