<?php
if (!defined('ABSPATH')) exit;

// Handle form submission
if (isset($_POST['save_settings'])) {
    update_option('blp_paypal_email', sanitize_email($_POST['paypal_email']));
    update_option('blp_paypal_sandbox', isset($_POST['paypal_sandbox']));
    update_option('blp_stripe_public_key', sanitize_text_field($_POST['stripe_public_key']));
    update_option('blp_stripe_secret_key', sanitize_text_field($_POST['stripe_secret_key']));
    update_option('blp_listings_per_page', intval($_POST['listings_per_page']));
    update_option('blp_require_approval', isset($_POST['require_approval']));
    
    echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
}

$paypal_email = get_option('blp_paypal_email', '');
$paypal_sandbox = get_option('blp_paypal_sandbox', true);
$stripe_public_key = get_option('blp_stripe_public_key', '');
$stripe_secret_key = get_option('blp_stripe_secret_key', '');
$listings_per_page = get_option('blp_listings_per_page', 10);
$require_approval = get_option('blp_require_approval', true);
?>

<div class="wrap">
    <h1>Settings</h1>
    
    <form method="post" action="">
        <h2>Payment Settings</h2>
        
        <h3>PayPal</h3>
        <table class="form-table">
            <tr>
                <th><label for="paypal_email">PayPal Email</label></th>
                <td><input type="email" id="paypal_email" name="paypal_email" value="<?php echo esc_attr($paypal_email); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="paypal_sandbox">Sandbox Mode</label></th>
                <td><input type="checkbox" id="paypal_sandbox" name="paypal_sandbox" <?php checked($paypal_sandbox); ?>> Use PayPal Sandbox for testing</td>
            </tr>
        </table>
        
        <h3>Stripe</h3>
        <table class="form-table">
            <tr>
                <th><label for="stripe_public_key">Stripe Publishable Key</label></th>
                <td><input type="text" id="stripe_public_key" name="stripe_public_key" value="<?php echo esc_attr($stripe_public_key); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="stripe_secret_key">Stripe Secret Key</label></th>
                <td><input type="password" id="stripe_secret_key" name="stripe_secret_key" value="<?php echo esc_attr($stripe_secret_key); ?>" class="regular-text"></td>
            </tr>
        </table>
        
        <h2>Listing Settings</h2>
        <table class="form-table">
            <tr>
                <th><label for="listings_per_page">Listings Per Page</label></th>
                <td><input type="number" id="listings_per_page" name="listings_per_page" value="<?php echo $listings_per_page; ?>" min="1" class="small-text"></td>
            </tr>
            <tr>
                <th><label for="require_approval">Require Approval</label></th>
                <td><input type="checkbox" id="require_approval" name="require_approval" <?php checked($require_approval); ?>> Require admin approval for new listings</td>
            </tr>
        </table>
        
        <p class="submit">
            <input type="submit" name="save_settings" class="button-primary" value="Save Settings">
        </p>
    </form>
</div>