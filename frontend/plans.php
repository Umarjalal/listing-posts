<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$plans = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}blp_plans WHERE active = 1 ORDER BY price ASC");

// Get plugin instance to check subscription
$blp = new BusinessListingsPro();
?>

<div class="blp-plans-container">
    <div class="blp-plans-header">
        <h2><?php _e('Choose Your Plan', 'business-listings-pro'); ?></h2>
        <p><?php _e('Select the perfect plan for your business needs', 'business-listings-pro'); ?></p>
    </div>
    
    <?php if (!empty($plans)): ?>
        <div class="blp-plans-grid">
            <?php foreach ($plans as $plan): ?>
                <div class="blp-plan-card <?php echo $plan->name === 'Professional Plan' ? 'blp-plan-featured' : ''; ?>" data-plan-id="<?php echo $plan->id; ?>">
                    <?php if ($plan->name === 'Professional Plan'): ?>
                        <div class="blp-plan-badge"><?php _e('Most Popular', 'business-listings-pro'); ?></div>
                    <?php endif; ?>
                    
                    <div class="blp-plan-header">
                        <h3 class="blp-plan-name"><?php echo esc_html($plan->name); ?></h3>
                        <div class="blp-plan-price">
                            <span class="currency">$</span>
                            <span class="amount"><?php echo number_format($plan->price, 0); ?></span>
                            <span class="period">/<?php echo $plan->duration; ?> <?php _e('days', 'business-listings-pro'); ?></span>
                        </div>
                    </div>
                    
                    <div class="blp-plan-description">
                        <p><?php echo esc_html($plan->description); ?></p>
                    </div>
                    
                    <div class="blp-plan-features">
                        <ul>
                            <?php 
                            $features = explode("\n", $plan->features);
                            foreach ($features as $feature):
                                $feature = trim($feature);
                                if (!empty($feature)):
                            ?>
                                <li><i class="blp-check"></i> <?php echo esc_html($feature); ?></li>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </ul>
                    </div>
                    
                    <div class="blp-plan-action">
                        <?php if (is_user_logged_in()): ?>
                            <?php if ($blp->user_has_active_subscription()): ?>
                                <a href="<?php echo home_url('/dashboard'); ?>" class="blp-btn blp-btn-secondary">
                                    <?php _e('Go to Dashboard', 'business-listings-pro'); ?>
                                </a>
                            <?php else: ?>
                                <button class="blp-btn blp-btn-primary blp-select-plan" data-plan-id="<?php echo $plan->id; ?>">
                                    <?php _e('Select Plan', 'business-listings-pro'); ?>
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <button class="blp-btn blp-btn-primary blp-select-plan" data-plan-id="<?php echo $plan->id; ?>">
                                <?php _e('Get Started', 'business-listings-pro'); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="blp-no-plans">
            <h3><?php _e('No Plans Available', 'business-listings-pro'); ?></h3>
            <p><?php _e('Please check back later for available subscription plans.', 'business-listings-pro'); ?></p>
        </div>
    <?php endif; ?>
</div>

<!-- Payment Modal -->
<div id="blp-payment-modal" class="blp-modal" style="display: none;">
    <div class="blp-modal-content">
        <div class="blp-modal-header">
            <h3><?php _e('Complete Your Purchase', 'business-listings-pro'); ?></h3>
            <button class="blp-modal-close">&times;</button>
        </div>
        
        <div class="blp-modal-body">
            <div class="blp-selected-plan-info">
                <h4><?php _e('Selected Plan', 'business-listings-pro'); ?></h4>
                <div class="blp-plan-summary">
                    <span class="plan-name"></span>
                    <span class="plan-price"></span>
                </div>
            </div>
            
            <div class="blp-payment-methods">
                <h4><?php _e('Choose Payment Method', 'business-listings-pro'); ?></h4>
                <div class="blp-payment-options">
                    <?php if (get_option('blp_paypal_email')): ?>
                        <button class="blp-payment-method" data-method="paypal">
                            <div class="blp-payment-icon">
                                <svg width="60" height="24" viewBox="0 0 60 24" fill="none">
                                    <path d="M7.266 2.184c.43-.02.86-.02 1.29 0 2.37.12 4.29.84 5.52 2.28 1.23 1.44 1.41 3.48.84 5.76-.57 2.28-1.89 4.02-3.72 4.98-1.83.96-4.17 1.08-6.66.84L3.426 18.6H.006L2.286 2.184h4.98zm1.32 3.36c-.36 0-.72.03-1.08.09l-.84 5.04c.24.03.48.03.72 0 1.08-.06 1.98-.42 2.58-1.08.6-.66.84-1.56.66-2.52-.18-.96-.72-1.53-2.04-1.53z" fill="#003087"/>
                                    <path d="M16.446 8.184c1.32 0 2.34.36 2.94 1.08.6.72.66 1.74.18 2.88-.48 1.14-1.32 2.04-2.4 2.58-1.08.54-2.34.72-3.66.54l-.36 2.16h-2.88l2.28-13.68h2.88l-.36 2.16c.48-.48 1.02-.84 1.62-1.08.6-.24 1.26-.36 1.98-.36zm-1.08 3.36c-.36 0-.66.09-.9.27-.24.18-.42.42-.54.72l-.54 3.24c.18.03.36.03.54 0 .54-.03.99-.21 1.35-.54.36-.33.57-.75.63-1.26.06-.51-.03-.93-.27-1.26-.24-.33-.63-.51-1.17-.51z" fill="#0070ba"/>
                                    <path d="M28.446 8.184c1.32 0 2.34.36 2.94 1.08.6.72.66 1.74.18 2.88-.48 1.14-1.32 2.04-2.4 2.58-1.08.54-2.34.72-3.66.54l-.36 2.16h-2.88l2.28-13.68h2.88l-.36 2.16c.48-.48 1.02-.84 1.62-1.08.6-.24 1.26-.36 1.98-.36zm-1.08 3.36c-.36 0-.66.09-.9.27-.24.18-.42.42-.54.72l-.54 3.24c.18.03.36.03.54 0 .54-.03.99-.21 1.35-.54.36-.33.57-.75.63-1.26.06-.51-.03-.93-.27-1.26-.24-.33-.63-.51-1.17-.51z" fill="#0070ba"/>
                                </svg>
                            </div>
                            <span><?php _e('PayPal', 'business-listings-pro'); ?></span>
                        </button>
                    <?php endif; ?>
                    
                    <?php if (get_option('blp_stripe_public_key')): ?>
                        <button class="blp-payment-method" data-method="stripe">
                            <div class="blp-payment-icon">
                                <svg width="60" height="24" viewBox="0 0 60 24" fill="none">
                                    <path d="M59.64 14.28h-8.06c.19 1.93 1.6 2.55 3.2 2.55 1.64 0 2.96-.37 4.05-.95v3.32a8.33 8.33 0 0 1-4.56 1.1c-4.01 0-6.83-2.5-6.83-7.48 0-4.19 2.39-7.52 6.3-7.52 3.92 0 5.96 3.28 5.96 7.5 0 .4-.04.86-.06 1.48zm-5.92-5.62c-1.03 0-2.17.73-2.17 2.58h4.25c0-1.85-1.07-2.58-2.08-2.58zM40.95 20.3c-1.44 0-2.32-.6-2.9-1.04l-.02 4.63-4.12.87V5.57h3.76l.08 1.02a4.7 4.7 0 0 1 3.23-1.29c2.9 0 5.62 2.6 5.62 7.4 0 5.23-2.7 7.6-5.65 7.6zM40 8.95c-.95 0-1.54.34-1.97.81l.02 6.12c.4.44.98.78 1.95.78 1.52 0 2.54-1.65 2.54-3.87 0-2.15-1.04-3.84-2.54-3.84zM28.24 5.57h4.13v14.44h-4.13V5.57zm-4.32 9.35c0 1.48-.73 2.18-1.9 2.18-.59 0-1.42-.24-1.42-.24V8.95s.49-.21 1.28-.21c1.33 0 2.04.79 2.04 2.43v3.75zm-8.55 4.72c2.03 0 4.75-.82 4.75-5.41V5.57h-4.02v8.45c0 .73-.31 1.04-.73 1.04-.17 0-.49-.05-.49-.05v3.09c.25.05.67.14 1.49.14zM9.72 5.57h-4.1v14.44h4.1V5.57z" fill="#6772e5"/>
                                </svg>
                            </div>
                            <span><?php _e('Credit Card', 'business-listings-pro'); ?></span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <div id="blp-stripe-form" style="display: none;">
                <form id="blp-stripe-payment-form">
                    <div class="blp-form-group">
                        <label for="card-number"><?php _e('Card Number', 'business-listings-pro'); ?></label>
                        <input type="text" id="card-number" placeholder="1234 5678 9012 3456" maxlength="19">
                    </div>
                    
                    <div class="blp-form-row">
                        <div class="blp-form-group">
                            <label for="card-expiry"><?php _e('MM/YY', 'business-listings-pro'); ?></label>
                            <input type="text" id="card-expiry" placeholder="MM/YY" maxlength="5">
                        </div>
                        <div class="blp-form-group">
                            <label for="card-cvc"><?php _e('CVC', 'business-listings-pro'); ?></label>
                            <input type="text" id="card-cvc" placeholder="123" maxlength="4">
                        </div>
                    </div>
                    
                    <button type="submit" class="blp-btn blp-btn-primary blp-btn-block">
                        <?php _e('Pay Now', 'business-listings-pro'); ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Login/Signup Modal -->
<div id="blp-auth-modal" class="blp-modal" style="display: none;">
    <div class="blp-modal-content">
        <div class="blp-modal-header">
            <h3 id="blp-auth-title"><?php _e('Get Started', 'business-listings-pro'); ?></h3>
            <button class="blp-modal-close">&times;</button>
        </div>
        
        <div class="blp-modal-body">
            <div class="blp-auth-tabs">
                <button class="blp-auth-tab active" data-tab="signup"><?php _e('Sign Up', 'business-listings-pro'); ?></button>
                <button class="blp-auth-tab" data-tab="login"><?php _e('Login', 'business-listings-pro'); ?></button>
            </div>
            
            <form id="blp-signup-form" class="blp-auth-form">
                <div class="blp-form-group">
                    <label for="signup-username"><?php _e('Username', 'business-listings-pro'); ?> *</label>
                    <input type="text" id="signup-username" name="username" required>
                </div>
                <div class="blp-form-group">
                    <label for="signup-email"><?php _e('Email', 'business-listings-pro'); ?> *</label>
                    <input type="email" id="signup-email" name="email" required>
                </div>
                <div class="blp-form-group">
                    <label for="signup-password"><?php _e('Password', 'business-listings-pro'); ?> *</label>
                    <input type="password" id="signup-password" name="password" required minlength="6">
                </div>
                <button type="submit" class="blp-btn blp-btn-primary blp-btn-block">
                    <?php _e('Create Account', 'business-listings-pro'); ?>
                </button>
            </form>
            
            <form id="blp-login-form" class="blp-auth-form" style="display: none;">
                <div class="blp-form-group">
                    <label for="login-username"><?php _e('Username or Email', 'business-listings-pro'); ?> *</label>
                    <input type="text" id="login-username" name="username" required>
                </div>
                <div class="blp-form-group">
                    <label for="login-password"><?php _e('Password', 'business-listings-pro'); ?> *</label>
                    <input type="password" id="login-password" name="password" required>
                </div>
                <button type="submit" class="blp-btn blp-btn-primary blp-btn-block">
                    <?php _e('Login', 'business-listings-pro'); ?>
                </button>
            </form>
        </div>
    </div>
</div>