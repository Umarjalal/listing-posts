<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

$subscriptions = $wpdb->get_results("
    SELECT s.*, p.name as plan_name, p.price, u.display_name, u.user_email 
    FROM {$wpdb->prefix}blp_subscriptions s
    LEFT JOIN {$wpdb->prefix}blp_plans p ON s.plan_id = p.id
    LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
    ORDER BY s.created_at DESC
");
?>

<div class="wrap">
    <h1>Subscriptions</h1>
    
    <?php if ($subscriptions): ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Plan</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Payment ID</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($subscriptions as $sub): ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($sub->display_name); ?></strong><br>
                            <small><?php echo esc_html($sub->user_email); ?></small>
                        </td>
                        <td><?php echo esc_html($sub->plan_name); ?></td>
                        <td>$<?php echo number_format($sub->price, 2); ?></td>
                        <td>
                            <span class="subscription-status status-<?php echo $sub->status; ?>">
                                <?php echo ucfirst($sub->status); ?>
                            </span>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($sub->start_date)); ?></td>
                        <td><?php echo $sub->end_date ? date('M j, Y', strtotime($sub->end_date)) : 'N/A'; ?></td>
                        <td><code><?php echo esc_html($sub->payment_id); ?></code></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No subscriptions found.</p>
    <?php endif; ?>
</div>