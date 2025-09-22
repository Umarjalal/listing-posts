<?php
if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
$user_listings = get_posts(array(
    'post_type' => 'business_listing',
    'author' => $current_user->ID,
    'post_status' => array('publish', 'pending', 'draft'),
    'numberposts' => -1
));

// Get user subscription info
$blp = new BusinessListingsPro();
$subscription = $blp->get_user_subscription();
?>

<div class="blp-dashboard">
    <div class="blp-dashboard-header">
        <h2><?php printf(__('Welcome, %s!', 'business-listings-pro'), esc_html($current_user->display_name)); ?></h2>
        <p><?php _e('Manage your business listings from your dashboard.', 'business-listings-pro'); ?></p>
    </div>
    
    <?php if ($subscription): ?>
        <div class="blp-subscription-info">
            <div class="blp-subscription-card">
                <h3><?php _e('Your Subscription', 'business-listings-pro'); ?></h3>
                <div class="blp-subscription-details">
                    <div class="blp-sub-item">
                        <span class="label"><?php _e('Plan:', 'business-listings-pro'); ?></span>
                        <span class="value"><?php echo esc_html($subscription->plan_name); ?></span>
                    </div>
                    <div class="blp-sub-item">
                        <span class="label"><?php _e('Status:', 'business-listings-pro'); ?></span>
                        <span class="value status-<?php echo esc_attr($subscription->status); ?>">
                            <?php echo esc_html(ucfirst($subscription->status)); ?>
                        </span>
                    </div>
                    <?php if ($subscription->end_date): ?>
                        <div class="blp-sub-item">
                            <span class="label"><?php _e('Expires:', 'business-listings-pro'); ?></span>
                            <span class="value"><?php echo date_i18n(get_option('date_format'), strtotime($subscription->end_date)); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="blp-dashboard-stats">
        <div class="blp-stat-item">
            <div class="blp-stat-number"><?php echo count($user_listings); ?></div>
            <div class="blp-stat-label"><?php _e('Total Listings', 'business-listings-pro'); ?></div>
        </div>
        <div class="blp-stat-item">
            <div class="blp-stat-number">
                <?php 
                $published = array_filter($user_listings, function($post) {
                    return $post->post_status === 'publish';
                });
                echo count($published);
                ?>
            </div>
            <div class="blp-stat-label"><?php _e('Published', 'business-listings-pro'); ?></div>
        </div>
        <div class="blp-stat-item">
            <div class="blp-stat-number">
                <?php 
                $pending = array_filter($user_listings, function($post) {
                    return $post->post_status === 'pending';
                });
                echo count($pending);
                ?>
            </div>
            <div class="blp-stat-label"><?php _e('Pending Approval', 'business-listings-pro'); ?></div>
        </div>
    </div>
    
    <div class="blp-dashboard-actions">
        <button id="blp-add-listing-btn" class="blp-btn blp-btn-primary">
            <i class="blp-icon-plus"></i>
            <?php _e('Add New Listing', 'business-listings-pro'); ?>
        </button>
    </div>
    
    <div class="blp-listings-section">
        <h3><?php _e('Your Listings', 'business-listings-pro'); ?></h3>
        
        <?php if ($user_listings): ?>
            <div class="blp-listings-table-container">
                <table class="blp-table">
                    <thead>
                        <tr>
                            <th><?php _e('Title', 'business-listings-pro'); ?></th>
                            <th><?php _e('Category', 'business-listings-pro'); ?></th>
                            <th><?php _e('Status', 'business-listings-pro'); ?></th>
                            <th><?php _e('Date', 'business-listings-pro'); ?></th>
                            <th><?php _e('Actions', 'business-listings-pro'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($user_listings as $listing): ?>
                            <tr>
                                <td>
                                    <div class="blp-listing-title-cell">
                                        <strong><?php echo esc_html($listing->post_title); ?></strong>
                                        <?php if ($listing->post_status === 'publish'): ?>
                                            <br><a href="<?php echo get_permalink($listing->ID); ?>" target="_blank" class="blp-view-link">
                                                <?php _e('View Listing', 'business-listings-pro'); ?> ↗
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                    $category = get_post_meta($listing->ID, 'business_category', true);
                                    echo $category ? esc_html(ucfirst(str_replace('-', ' ', $category))) : '—';
                                    ?>
                                </td>
                                <td>
                                    <span class="blp-status blp-status-<?php echo $listing->post_status; ?>">
                                        <?php 
                                        switch($listing->post_status) {
                                            case 'publish':
                                                _e('Published', 'business-listings-pro');
                                                break;
                                            case 'pending':
                                                _e('Pending Review', 'business-listings-pro');
                                                break;
                                            case 'draft':
                                                _e('Draft', 'business-listings-pro');
                                                break;
                                            default:
                                                echo esc_html(ucfirst($listing->post_status));
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo get_the_date('M j, Y', $listing->ID); ?></td>
                                <td>
                                    <div class="blp-action-buttons">
                                        <button class="blp-btn blp-btn-small blp-edit-listing" 
                                                data-listing-id="<?php echo $listing->ID; ?>"
                                                title="<?php _e('Edit Listing', 'business-listings-pro'); ?>">
                                            <?php _e('Edit', 'business-listings-pro'); ?>
                                        </button>
                                        <button class="blp-btn blp-btn-small blp-btn-danger blp-delete-listing" 
                                                data-listing-id="<?php echo $listing->ID; ?>"
                                                title="<?php _e('Delete Listing', 'business-listings-pro'); ?>">
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
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"/>
                        <path d="M8 21v-4a2 2 0 012-2h4a2 2 0 012 2v4"/>
                    </svg>
                </div>
                <h4><?php _e('No listings yet', 'business-listings-pro'); ?></h4>
                <p><?php _e('Create your first business listing to get started!', 'business-listings-pro'); ?></p>
                <button id="blp-add-first-listing" class="blp-btn blp-btn-primary">
                    <?php _e('Add Your First Listing', 'business-listings-pro'); ?>
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add/Edit Listing Modal -->
<div id="blp-listing-modal" class="blp-modal" style="display: none;">
    <div class="blp-modal-content blp-modal-large">
        <div class="blp-modal-header">
            <h3 id="blp-listing-modal-title"><?php _e('Add New Listing', 'business-listings-pro'); ?></h3>
            <button class="blp-modal-close">&times;</button>
        </div>
        
        <div class="blp-modal-body">
            <form id="blp-listing-form" enctype="multipart/form-data">
                <input type="hidden" id="listing-id" name="listing_id" value="">
                
                <div class="blp-form-group">
                    <label for="listing-title"><?php _e('Business Name', 'business-listings-pro'); ?> *</label>
                    <input type="text" id="listing-title" name="title" required 
                           placeholder="<?php _e('Enter your business name', 'business-listings-pro'); ?>">
                </div>
                
                <div class="blp-form-group">
                    <label for="listing-description"><?php _e('Description', 'business-listings-pro'); ?> *</label>
                    <textarea id="listing-description" name="description" rows="5" required 
                              placeholder="<?php _e('Describe your business, services, and what makes you unique...', 'business-listings-pro'); ?>"></textarea>
                </div>
                
                <div class="blp-form-row">
                    <div class="blp-form-group">
                        <label for="listing-category"><?php _e('Category', 'business-listings-pro'); ?> *</label>
                        <select id="listing-category" name="category" required>
                            <option value=""><?php _e('Select Category', 'business-listings-pro'); ?></option>
                            <option value="restaurant"><?php _e('Restaurant', 'business-listings-pro'); ?></option>
                            <option value="retail"><?php _e('Retail', 'business-listings-pro'); ?></option>
                            <option value="services"><?php _e('Services', 'business-listings-pro'); ?></option>
                            <option value="healthcare"><?php _e('Healthcare', 'business-listings-pro'); ?></option>
                            <option value="automotive"><?php _e('Automotive', 'business-listings-pro'); ?></option>
                            <option value="real-estate"><?php _e('Real Estate', 'business-listings-pro'); ?></option>
                            <option value="technology"><?php _e('Technology', 'business-listings-pro'); ?></option>
                            <option value="education"><?php _e('Education', 'business-listings-pro'); ?></option>
                            <option value="other"><?php _e('Other', 'business-listings-pro'); ?></option>
                        </select>
                    </div>
                    
                    <div class="blp-form-group">
                        <label for="listing-phone"><?php _e('Phone Number', 'business-listings-pro'); ?></label>
                        <input type="tel" id="listing-phone" name="phone" 
                               placeholder="<?php _e('(555) 123-4567', 'business-listings-pro'); ?>">
                    </div>
                </div>
                
                <div class="blp-form-group">
                    <label for="listing-email"><?php _e('Email Address', 'business-listings-pro'); ?></label>
                    <input type="email" id="listing-email" name="email" 
                           placeholder="<?php _e('contact@yourbusiness.com', 'business-listings-pro'); ?>">
                </div>
                
                <div class="blp-form-group">
                    <label for="listing-address"><?php _e('Address', 'business-listings-pro'); ?></label>
                    <textarea id="listing-address" name="address" rows="2" 
                              placeholder="<?php _e('123 Main Street, City, State 12345', 'business-listings-pro'); ?>"></textarea>
                </div>
                
                <div class="blp-form-group">
                    <label for="listing-website"><?php _e('Website', 'business-listings-pro'); ?></label>
                    <input type="url" id="listing-website" name="website" 
                           placeholder="https://www.yourbusiness.com">
                </div>
                
                <div class="blp-form-group">
                    <label for="listing-image"><?php _e('Business Image', 'business-listings-pro'); ?></label>
                    <input type="file" id="listing-image" name="image" accept="image/*">
                    <small class="blp-form-help">
                        <?php _e('Upload an image of your business (JPG, PNG, GIF - Max 2MB)', 'business-listings-pro'); ?>
                    </small>
                    <div id="image-preview" style="display: none; margin-top: 10px;">
                        <img style="max-width: 200px; height: auto; border-radius: 4px; border: 1px solid #ddd;">
                    </div>
                </div>
                
                <div class="blp-form-actions">
                    <button type="submit" class="blp-btn blp-btn-primary">
                        <span class="blp-btn-text"><?php _e('Save Listing', 'business-listings-pro'); ?></span>
                        <span class="blp-btn-loading" style="display: none;">
                            <i class="blp-spinner"></i> <?php _e('Saving...', 'business-listings-pro'); ?>
                        </span>
                    </button>
                    <button type="button" class="blp-btn blp-btn-secondary blp-cancel-listing">
                        <?php _e('Cancel', 'business-listings-pro'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>