<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$plans_table = $wpdb->prefix . 'blp_plans';

// Handle form submissions
if (isset($_POST['action'])) {
    if ($_POST['action'] === 'add_plan') {
        $wpdb->insert($plans_table, array(
            'name' => sanitize_text_field($_POST['name']),
            'description' => sanitize_textarea_field($_POST['description']),
            'price' => floatval($_POST['price']),
            'duration' => intval($_POST['duration']),
            'features' => sanitize_textarea_field($_POST['features'])
        ));
        echo '<div class="notice notice-success"><p>Plan added successfully!</p></div>';
    } elseif ($_POST['action'] === 'edit_plan') {
        $wpdb->update($plans_table, array(
            'name' => sanitize_text_field($_POST['name']),
            'description' => sanitize_textarea_field($_POST['description']),
            'price' => floatval($_POST['price']),
            'duration' => intval($_POST['duration']),
            'features' => sanitize_textarea_field($_POST['features'])
        ), array('id' => intval($_POST['plan_id'])));
        echo '<div class="notice notice-success"><p>Plan updated successfully!</p></div>';
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $wpdb->delete($plans_table, array('id' => intval($_GET['delete'])));
    echo '<div class="notice notice-success"><p>Plan deleted successfully!</p></div>';
}

$plans = $wpdb->get_results("SELECT * FROM $plans_table ORDER BY created_at DESC");
$editing_plan = null;
if (isset($_GET['edit'])) {
    $editing_plan = $wpdb->get_row($wpdb->prepare("SELECT * FROM $plans_table WHERE id = %d", intval($_GET['edit'])));
}
?>

<div class="wrap">
    <h1>Manage Plans</h1>
    
    <div class="blp-plans-container">
        <div class="blp-plan-form">
            <h2><?php echo $editing_plan ? 'Edit Plan' : 'Add New Plan'; ?></h2>
            
            <form method="post" action="">
                <input type="hidden" name="action" value="<?php echo $editing_plan ? 'edit_plan' : 'add_plan'; ?>">
                <?php if ($editing_plan): ?>
                    <input type="hidden" name="plan_id" value="<?php echo $editing_plan->id; ?>">
                <?php endif; ?>
                
                <table class="form-table">
                    <tr>
                        <th><label for="name">Plan Name</label></th>
                        <td><input type="text" id="name" name="name" value="<?php echo $editing_plan ? esc_attr($editing_plan->name) : ''; ?>" required class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="description">Description</label></th>
                        <td><textarea id="description" name="description" rows="3" class="large-text"><?php echo $editing_plan ? esc_textarea($editing_plan->description) : ''; ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="price">Price ($)</label></th>
                        <td><input type="number" id="price" name="price" step="0.01" value="<?php echo $editing_plan ? $editing_plan->price : ''; ?>" required class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="duration">Duration (days)</label></th>
                        <td><input type="number" id="duration" name="duration" value="<?php echo $editing_plan ? $editing_plan->duration : '30'; ?>" required class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="features">Features</label></th>
                        <td><textarea id="features" name="features" rows="5" class="large-text" placeholder="One feature per line"><?php echo $editing_plan ? esc_textarea($editing_plan->features) : ''; ?></textarea></td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" class="button-primary" value="<?php echo $editing_plan ? 'Update Plan' : 'Add Plan'; ?>">
                    <?php if ($editing_plan): ?>
                        <a href="<?php echo admin_url('admin.php?page=blp-plans'); ?>" class="button">Cancel</a>
                    <?php endif; ?>
                </p>
            </form>
        </div>
        
        <div class="blp-plans-list">
            <h2>Existing Plans</h2>
            
            <?php if ($plans): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Duration</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($plans as $plan): ?>
                            <tr>
                                <td><strong><?php echo esc_html($plan->name); ?></strong><br>
                                    <small><?php echo esc_html($plan->description); ?></small></td>
                                <td>$<?php echo number_format($plan->price, 2); ?></td>
                                <td><?php echo $plan->duration; ?> days</td>
                                <td><?php echo $plan->active ? 'Active' : 'Inactive'; ?></td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=blp-plans&edit=' . $plan->id); ?>" class="button button-small">Edit</a>
                                    <a href="<?php echo admin_url('admin.php?page=blp-plans&delete=' . $plan->id); ?>" 
                                       class="button button-small" 
                                       onclick="return confirm('Are you sure you want to delete this plan?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No plans found. Add your first plan above.</p>
            <?php endif; ?>
        </div>
    </div>
</div>