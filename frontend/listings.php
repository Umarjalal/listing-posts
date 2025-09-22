<?php
if (!defined('ABSPATH')) exit;

$paged = get_query_var('paged') ? get_query_var('paged') : 1;
$per_page = get_option('blp_listings_per_page', 10);

$args = array(
    'post_type' => 'business_listing',
    'post_status' => 'publish',
    'posts_per_page' => $per_page,
    'paged' => $paged
);

$listings = new WP_Query($args);
?>

<div class="blp-listings-container">
    <?php if ($listings->have_posts()): ?>
        <div class="blp-listings-grid">
            <?php while ($listings->have_posts()): $listings->the_post(); ?>
                <div class="blp-listing-card">
                    <div class="blp-listing-image">
                        <?php if (has_post_thumbnail()): ?>
                            <?php the_post_thumbnail('medium'); ?>
                        <?php else: ?>
                            <div class="blp-no-image">No Image</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="blp-listing-content">
                        <h3 class="blp-listing-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h3>
                        
                        <div class="blp-listing-meta">
                            <?php 
                            $category = get_post_meta(get_the_ID(), 'business_category', true);
                            if ($category):
                            ?>
                                <span class="blp-listing-category"><?php echo esc_html($category); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="blp-listing-excerpt">
                            <?php the_excerpt(); ?>
                        </div>
                        
                        <div class="blp-listing-actions">
                            <a href="<?php the_permalink(); ?>" class="blp-btn blp-btn-primary">View Details</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        
        <div class="blp-pagination">
            <?php
            echo paginate_links(array(
                'total' => $listings->max_num_pages,
                'current' => $paged,
                'prev_text' => '« Previous',
                'next_text' => 'Next »'
            ));
            ?>
        </div>
        
        <?php wp_reset_postdata(); ?>
    <?php else: ?>
        <div class="blp-no-listings">
            <h3>No listings found</h3>
            <p>Be the first to add a business listing!</p>
        </div>
    <?php endif; ?>
</div>