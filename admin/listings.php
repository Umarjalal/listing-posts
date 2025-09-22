<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

// Handle status updates
if (isset($_POST['action']) && $_POST['action'] === 'update_listing_status') {
    $listing_id = intval($_POST['listing_id']);
    $new_status = sanitize_text_field($_POST['new_status']);
    
    if (in_array($new_status, ['publish', 'pending', 'draft'])) {
        wp_update_post(array(
            'ID' => $listing_id,
            'post_status' => $new_status
        ));
        
        // Send notification to user if needed
        $post = get_post($listing_id);
        $user = get_user_by('id', $post->post_author);
        
        if ($new_status === 'publish') {
            // Send approval notification
            wp_mail(
                $user->user_email,
                __('Your listing has been approved', 'business-listings-pro'),
                sprintf(__('Your listing "%s" has been approved and is now live.', 'business-listings-pro'), $post->post_title)
            );
        } elseif ($new_status === 'draft') {
            // Send rejection notification
            wp_mail(
                $user->user_email,
                __('Your listing needs attention', 'business-listings-pro'),
                sprintf(__('Your listing "%s" has been declined. Please review and resubmit.', 'business-listings-pro'), $post->post_title)
            );
        }
        
        echo '<div class="notice notice-success"><p>' . __('Listing status updated successfully!', 'business-listings-pro') . '</p></div>';
    }
}

// Get all listings with user information
$listings = $wpdb->get_results("
    SELECT p.*, u.display_name, u.user_email,
           pm1.meta_value as business_category,
           pm2.meta_value as business_phone,
           pm3.meta_value as business_email
    FROM {$wpdb->posts} p
    LEFT JOIN {$wpdb->users} u ON p.post_author = u.ID
    LEFT JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = 'business_category'
    LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = 'business_phone'
    LEFT JOIN {$wpdb->postmeta} pm3 ON p.ID = pm3.post_id AND pm3.meta_key = 'business_email'
    WHERE p.post_type = 'business_listing'
    ORDER BY p.post_date DESC
");
?>

<div class="wrap">
    <h1><?php _e('All Business Listings', 'business-listings-pro'); ?></h1>
    
    <div class="blp-listings-stats">
        <div class="blp-stat-card">
            <div class="blp-stat-icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="blp-stat-content">
                <h3><?php _e('Published', 'business-listings-pro'); ?></h3>
                <div class="blp-stat-number">
                    <?php echo count(array_filter($listings, function($l) { return $l->post_status === 'publish'; })); ?>
                </div>
            </div>
        </div>
        
        <div class="blp-stat-card">
            <div class="blp-stat-icon">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="blp-stat-content">
                <h3><?php _e('Pending Review', 'business-listings-pro'); ?></h3>
                <div class="blp-stat-number">
                    <?php echo count(array_filter($listings, function($l) { return $l->post_status === 'pending'; })); ?>
                </div>
            </div>
        </div>
        
        <div class="blp-stat-card">
            <div class="blp-stat-icon">
                <span class="dashicons dashicons-dismiss"></span>
            </div>
            <div class="blp-stat-content">
                <h3><?php _e('Declined', 'business-listings-pro'); ?></h3>
                <div class="blp-stat-number">
                    <?php echo count(array_filter($listings, function($l) { return $l->post_status === 'draft'; })); ?>
                </div>
            </div>
        </div>
        
        <div class="blp-stat-card">
            <div class="blp-stat-icon">
                <span class="dashicons dashicons-building"></span>
            </div>
            <div class="blp-stat-content">
                <h3><?php _e('Total Listings', 'business-listings-pro'); ?></h3>
                <div class="blp-stat-number"><?php echo count($listings); ?></div>
            </div>
        </div>
    </div>
    
    <?php if ($listings): ?>
        <div class="blp-listings-table-wrapper">
            <table class="wp-list-table widefat fixed striped blp-listings-table">
                <thead>
                    <tr>
                        <th class="column-title"><?php _e('Business Name', 'business-listings-pro'); ?></th>
                        <th class="column-author"><?php _e('Owner', 'business-listings-pro'); ?></th>
                        <th class="column-category"><?php _e('Category', 'business-listings-pro'); ?></th>
                        <th class="column-contact"><?php _e('Contact', 'business-listings-pro'); ?></th>
                        <th class="column-status"><?php _e('Status', 'business-listings-pro'); ?></th>
                        <th class="column-date"><?php _e('Date', 'business-listings-pro'); ?></th>
                        <th class="column-actions"><?php _e('Actions', 'business-listings-pro'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($listings as $listing): ?>
                        <tr data-listing-id="<?php echo $listing->ID; ?>">
                            <td class="column-title">
                                <div class="blp-listing-title">
                                    <strong>
                                        <a href="<?php echo get_edit_post_link($listing->ID); ?>" target="_blank">
                                            <?php echo esc_html($listing->post_title); ?>
                                        </a>
                                    </strong>
                                    <?php if ($listing->post_status === 'publish'): ?>
                                        <br><a href="<?php echo get_permalink($listing->ID); ?>" target="_blank" class="blp-view-link">
                                            <?php _e('View Live', 'business-listings-pro'); ?> ↗
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="column-author">
                                <div class="blp-author-info">
                                    <strong><?php echo esc_html($listing->display_name); ?></strong>
                                    <br><small><?php echo esc_html($listing->user_email); ?></small>
                                </div>
                            </td>
                            <td class="column-category">
                                <?php if ($listing->business_category): ?>
                                    <span class="blp-category-badge">
                                        <?php echo esc_html(ucfirst(str_replace('-', ' ', $listing->business_category))); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="blp-no-data">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="column-contact">
                                <div class="blp-contact-info">
                                    <?php if ($listing->business_phone): ?>
                                        <div><span class="dashicons dashicons-phone"></span> <?php echo esc_html($listing->business_phone); ?></div>
                                    <?php endif; ?>
                                    <?php if ($listing->business_email): ?>
                                        <div><span class="dashicons dashicons-email"></span> <?php echo esc_html($listing->business_email); ?></div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="column-status">
                                <form method="post" class="blp-status-form" data-listing-id="<?php echo $listing->ID; ?>">
                                    <input type="hidden" name="action" value="update_listing_status">
                                    <input type="hidden" name="listing_id" value="<?php echo $listing->ID; ?>">
                                    <select name="new_status" class="blp-status-select" onchange="this.form.submit()">
                                        <option value="publish" <?php selected($listing->post_status, 'publish'); ?>>
                                            <?php _e('Published', 'business-listings-pro'); ?>
                                        </option>
                                        <option value="pending" <?php selected($listing->post_status, 'pending'); ?>>
                                            <?php _e('Pending Review', 'business-listings-pro'); ?>
                                        </option>
                                        <option value="draft" <?php selected($listing->post_status, 'draft'); ?>>
                                            <?php _e('Declined', 'business-listings-pro'); ?>
                                        </option>
                                    </select>
                                </form>
                            </td>
                            <td class="column-date">
                                <div class="blp-date-info">
                                    <?php echo date_i18n('M j, Y', strtotime($listing->post_date)); ?>
                                    <br><small><?php echo date_i18n('g:i a', strtotime($listing->post_date)); ?></small>
                                </div>
                            </td>
                            <td class="column-actions">
                                <div class="blp-action-buttons">
                                    <a href="<?php echo get_edit_post_link($listing->ID); ?>" 
                                       class="button button-small" 
                                       title="<?php _e('Edit Listing', 'business-listings-pro'); ?>">
                                        <span class="dashicons dashicons-edit"></span>
                                        <?php _e('Edit', 'business-listings-pro'); ?>
                                    </a>
                                    <button class="button button-small blp-delete-listing" 
                                            data-listing-id="<?php echo $listing->ID; ?>"
                                            data-listing-title="<?php echo esc_attr($listing->post_title); ?>"
                                            title="<?php _e('Delete Listing', 'business-listings-pro'); ?>">
                                        <span class="dashicons dashicons-trash"></span>
                                        <?php _e('Delete', 'business-listings-pro'); ?>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="blp-empty-state">
            <div class="blp-empty-icon">
                <span class="dashicons dashicons-building"></span>
            </div>
            <h3><?php _e('No listings found', 'business-listings-pro'); ?></h3>
            <p><?php _e('No business listings have been submitted yet.', 'business-listings-pro'); ?></p>
        </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Handle listing deletion
    $('.blp-delete-listing').on('click', function() {
        const listingId = $(this).data('listing-id');
        const listingTitle = $(this).data('listing-title');
        
        if (!confirm('<?php _e('Are you sure you want to delete', 'business-listings-pro'); ?> "' + listingTitle + '"? <?php _e('This action cannot be undone.', 'business-listings-pro'); ?>')) {
            return;
        }
        
        const button = $(this);
        const originalText = button.html();
        button.html('<span class="dashicons dashicons-update spin"></span> <?php _e('Deleting...', 'business-listings-pro'); ?>').prop('disabled', true);
        
        $.post(ajaxurl, {
            action: 'blp_admin_delete_listing',
            nonce: '<?php echo wp_create_nonce('blp_admin_nonce'); ?>',
            listing_id: listingId
        })
        .done(function(response) {
            if (response.success) {
                button.closest('tr').fadeOut(300, function() {
                    $(this).remove();
                });
            } else {
                alert(response.data || '<?php _e('Error deleting listing', 'business-listings-pro'); ?>');
                button.html(originalText).prop('disabled', false);
            }
        })
        .fail(function() {
            alert('<?php _e('Error deleting listing', 'business-listings-pro'); ?>');
            button.html(originalText).prop('disabled', false);
        });
    });
    
    // Add loading state to status changes
    $('.blp-status-select').on('change', function() {
        const form = $(this).closest('form');
        const select = $(this);
        
        select.prop('disabled', true);
        form.append('<div class="blp-loading-overlay"><span class="dashicons dashicons-update spin"></span></div>');
    });
});
</script>

<style>
.blp-listings-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.blp-listings-table-wrapper {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.blp-listings-table {
    margin: 0;
}

.blp-listings-table th {
    background: #f8f9fa;
    font-weight: 600;
    padding: 15px 12px;
    border-bottom: 2px solid #dee2e6;
}

.blp-listings-table td {
    padding: 15px 12px;
    vertical-align: top;
}

.blp-listing-title strong a {
    color: #2271b1;
    text-decoration: none;
    font-size: 14px;
}

.blp-listing-title strong a:hover {
    color: #135e96;
}

.blp-view-link {
    color: #50575e;
    font-size: 12px;
    text-decoration: none;
}

.blp-view-link:hover {
    color: #2271b1;
}

.blp-author-info strong {
    color: #1d2327;
    font-size: 13px;
}

.blp-author-info small {
    color: #646970;
    font-size: 12px;
}

.blp-category-badge {
    background: linear-gradient(135deg, #2271b1, #135e96);
    color: white;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.blp-contact-info {
    font-size: 12px;
    line-height: 1.6;
}

.blp-contact-info .dashicons {
    font-size: 12px;
    width: 12px;
    height: 12px;
    margin-right: 4px;
    color: #646970;
}

.blp-status-select {
    padding: 4px 8px;
    border-radius: 4px;
    border: 1px solid #8c8f94;
    font-size: 12px;
    min-width: 120px;
}

.blp-status-select option[value="publish"] {
    color: #00a32a;
}

.blp-status-select option[value="pending"] {
    color: #dba617;
}

.blp-status-select option[value="draft"] {
    color: #d63638;
}

.blp-date-info {
    font-size: 13px;
    color: #1d2327;
}

.blp-date-info small {
    color: #646970;
    font-size: 11px;
}

.blp-action-buttons {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

.blp-action-buttons .button {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    padding: 4px 8px;
    height: auto;
    line-height: 1.4;
}

.blp-action-buttons .dashicons {
    font-size: 12px;
    width: 12px;
    height: 12px;
}

.blp-no-data {
    color: #646970;
    font-style: italic;
}

.blp-loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
}

.blp-status-form {
    position: relative;
}

.spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

@media (max-width: 782px) {
    .blp-listings-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .blp-action-buttons {
        flex-direction: column;
    }
    
    .blp-listings-table {
        font-size: 12px;
    }
    
    .blp-listings-table th,
    .blp-listings-table td {
        padding: 10px 8px;
    }
}
</style>