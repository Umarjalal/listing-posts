jQuery(document).ready(function($) {
    'use strict';
    
    // Initialize admin functionality
    init();
    
    function init() {
        bindEvents();
        initializeComponents();
    }
    
    function bindEvents() {
        // Plan management
        $(document).on('click', '.blp-edit-plan', handleEditPlan);
        $(document).on('click', '.blp-delete-plan', handleDeletePlan);
        
        // Form validation
        $(document).on('submit', 'form[action*="blp"]', validateForms);
        
        // Settings management
        $(document).on('change', '#blp_paypal_sandbox', togglePayPalMode);
        $(document).on('click', '.blp-test-connection', testApiConnections);
        
        // Listing management
        $(document).on('click', '.blp-approve-listing', handleListingApproval);
        $(document).on('click', '.blp-reject-listing', handleListingRejection);
        
        // Bulk actions
        $(document).on('change', '#bulk-action-selector-top', handleBulkActions);
        
        // Dashboard stats refresh
        $(document).on('click', '.blp-refresh-stats', refreshDashboardStats);
        
        // Tooltips and help text
        initializeTooltips();
    }
    
    function initializeComponents() {
        // Initialize sortable tables
        if ($.fn.sortable) {
            $('.blp-sortable-table tbody').sortable({
                handle: '.blp-sort-handle',
                update: function(event, ui) {
                    updateSortOrder();
                }
            });
        }
        
        // Initialize date pickers
        if ($.fn.datepicker) {
            $('.blp-datepicker').datepicker({
                dateFormat: 'yy-mm-dd'
            });
        }
        
        // Initialize rich text editors
        if (typeof wp !== 'undefined' && wp.editor) {
            wp.editor.initialize('blp-editor', {
                tinymce: true,
                quicktags: true
            });
        }
    }
    
    function handleEditPlan() {
        const planId = $(this).data('plan-id');
        const row = $(this).closest('tr');
        
        // Get plan data from the row
        const planData = {
            id: planId,
            name: row.find('.plan-name').text(),
            description: row.find('.plan-description').text(),
            price: row.find('.plan-price').text().replace('$', ''),
            duration: row.find('.plan-duration').text().replace(' days', ''),
            features: row.find('.plan-features').text()
        };
        
        // Populate edit form
        populatePlanForm(planData);
        
        // Scroll to form
        $('html, body').animate({
            scrollTop: $('.blp-plan-form').offset().top - 50
        }, 500);
    }
    
    function handleDeletePlan() {
        const planId = $(this).data('plan-id');
        const planName = $(this).data('plan-name');
        
        if (!confirm(`Are you sure you want to delete the plan "${planName}"? This action cannot be undone.`)) {
            return;
        }
        
        const deleteData = {
            action: 'blp_delete_plan',
            plan_id: planId,
            nonce: blp_admin_ajax.nonce
        };
        
        $.post(ajaxurl, deleteData)
            .done(function(response) {
                if (response.success) {
                    showAdminNotice('Plan deleted successfully.', 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showAdminNotice(response.data || 'Error deleting plan.', 'error');
                }
            })
            .fail(function() {
                showAdminNotice('An error occurred while deleting the plan.', 'error');
            });
    }
    
    function handleListingApproval() {
        const listingId = $(this).data('listing-id');
        updateListingStatus(listingId, 'publish', 'approved');
    }
    
    function handleListingRejection() {
        const listingId = $(this).data('listing-id');
        const reason = prompt('Please provide a reason for rejection (optional):');
        updateListingStatus(listingId, 'draft', 'rejected', reason);
    }
    
    function updateListingStatus(listingId, status, action, reason = '') {
        const statusData = {
            action: 'blp_update_listing_status',
            listing_id: listingId,
            status: status,
            listing_action: action,
            reason: reason,
            nonce: blp_admin_ajax.nonce
        };
        
        $.post(ajaxurl, statusData)
            .done(function(response) {
                if (response.success) {
                    showAdminNotice(`Listing ${action} successfully.`, 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showAdminNotice(response.data || `Error ${action.slice(0, -2)}ing listing.`, 'error');
                }
            })
            .fail(function() {
                showAdminNotice('An error occurred while updating the listing.', 'error');
            });
    }
    
    function validateForms(e) {
        const form = $(this);
        let isValid = true;
        let errors = [];
        
        // Plan form validation
        if (form.find('#name').length) {
            const name = form.find('#name').val().trim();
            const price = parseFloat(form.find('#price').val());
            const duration = parseInt(form.find('#duration').val());
            
            if (!name) {
                errors.push('Plan name is required.');
                form.find('#name').addClass('error');
            }
            
            if (isNaN(price) || price <= 0) {
                errors.push('Price must be a positive number.');
                form.find('#price').addClass('error');
            }
            
            if (isNaN(duration) || duration <= 0) {
                errors.push('Duration must be a positive number.');
                form.find('#duration').addClass('error');
            }
        }
        
        // Settings form validation
        if (form.find('#paypal_email').length) {
            const email = form.find('#paypal_email').val().trim();
            if (email && !isValidEmail(email)) {
                errors.push('Please enter a valid PayPal email address.');
                form.find('#paypal_email').addClass('error');
            }
        }
        
        if (errors.length > 0) {
            e.preventDefault();
            showAdminNotice(errors.join('<br>'), 'error');
            isValid = false;
        }
        
        return isValid;
    }
    
    function togglePayPalMode() {
        const isSandbox = $(this).is(':checked');
        const modeText = isSandbox ? 'sandbox (testing)' : 'live (production)';
        
        $('#paypal-mode-indicator').remove();
        $(this).after(`<span id="paypal-mode-indicator" style="margin-left: 10px; font-weight: bold; color: ${isSandbox ? '#d63638' : '#00a32a'};">${modeText}</span>`);
    }
    
    function testApiConnections() {
        const apiType = $(this).data('api');
        const button = $(this);
        const originalText = button.text();
        
        button.text('Testing...').prop('disabled', true);
        
        const testData = {
            action: 'blp_test_api_connection',
            api_type: apiType,
            nonce: blp_admin_ajax.nonce
        };
        
        if (apiType === 'paypal') {
            testData.email = $('#paypal_email').val();
            testData.sandbox = $('#paypal_sandbox').is(':checked');
        } else if (apiType === 'stripe') {
            testData.public_key = $('#stripe_public_key').val();
            testData.secret_key = $('#stripe_secret_key').val();
        }
        
        $.post(ajaxurl, testData)
            .done(function(response) {
                if (response.success) {
                    showAdminNotice(`${apiType.toUpperCase()} connection successful!`, 'success');
                } else {
                    showAdminNotice(response.data || `${apiType.toUpperCase()} connection failed.`, 'error');
                }
            })
            .fail(function() {
                showAdminNotice(`Error testing ${apiType.toUpperCase()} connection.`, 'error');
            })
            .always(function() {
                button.text(originalText).prop('disabled', false);
            });
    }
    
    function handleBulkActions() {
        const action = $(this).val();
        const checkboxes = $('.blp-bulk-checkbox:checked');
        
        if (action && checkboxes.length > 0) {
            const items = checkboxes.map(function() {
                return $(this).val();
            }).get();
            
            if (confirm(`Are you sure you want to ${action} ${items.length} item(s)?`)) {
                processBulkAction(action, items);
            }
        }
    }
    
    function processBulkAction(action, items) {
        const bulkData = {
            action: 'blp_bulk_action',
            bulk_action: action,
            items: items,
            nonce: blp_admin_ajax.nonce
        };
        
        $.post(ajaxurl, bulkData)
            .done(function(response) {
                if (response.success) {
                    showAdminNotice(`Bulk ${action} completed successfully.`, 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showAdminNotice(response.data || `Bulk ${action} failed.`, 'error');
                }
            })
            .fail(function() {
                showAdminNotice(`Error processing bulk ${action}.`, 'error');
            });
    }
    
    function refreshDashboardStats() {
        const button = $(this);
        const originalText = button.text();
        
        button.text('Refreshing...').prop('disabled', true);
        
        $.post(ajaxurl, {
            action: 'blp_refresh_stats',
            nonce: blp_admin_ajax.nonce
        })
        .done(function(response) {
            if (response.success) {
                updateDashboardStats(response.data);
                showAdminNotice('Statistics refreshed successfully.', 'success');
            } else {
                showAdminNotice('Error refreshing statistics.', 'error');
            }
        })
        .fail(function() {
            showAdminNotice('Error refreshing statistics.', 'error');
        })
        .always(function() {
            button.text(originalText).prop('disabled', false);
        });
    }
    
    function updateDashboardStats(stats) {
        $('.blp-stat-number').each(function() {
            const statType = $(this).data('stat');
            if (stats[statType] !== undefined) {
                $(this).text(stats[statType]);
            }
        });
    }
    
    function populatePlanForm(planData) {
        $('#name').val(planData.name);
        $('#description').val(planData.description);
        $('#price').val(planData.price);
        $('#duration').val(planData.duration);
        $('#features').val(planData.features);
        
        $('input[name="action"]').val('edit_plan');
        $('input[name="plan_id"]').val(planData.id);
        
        $('.blp-plan-form h2').text('Edit Plan');
        $('input[type="submit"]').val('Update Plan');
    }
    
    function updateSortOrder() {
        const order = $('.blp-sortable-table tbody tr').map(function() {
            return $(this).data('id');
        }).get();
        
        $.post(ajaxurl, {
            action: 'blp_update_sort_order',
            order: order,
            nonce: blp_admin_ajax.nonce
        });
    }
    
    function initializeTooltips() {
        if ($.fn.tooltip) {
            $('.blp-tooltip').tooltip({
                position: { my: "center bottom-20", at: "center top" }
            });
        }
    }
    
    function showAdminNotice(message, type = 'info') {
        const noticeClass = type === 'error' ? 'notice-error' : 
                           type === 'success' ? 'notice-success' : 
                           type === 'warning' ? 'notice-warning' : 'notice-info';
        
        const notice = $(`
            <div class="notice ${noticeClass} is-dismissible" style="margin: 20px 0;">
                <p>${message}</p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                </button>
            </div>
        `);
        
        $('.wrap h1').after(notice);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            notice.fadeOut(() => notice.remove());
        }, 5000);
        
        // Handle manual dismiss
        notice.find('.notice-dismiss').on('click', function() {
            notice.fadeOut(() => notice.remove());
        });
    }
    
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    // Clear form errors on input
    $(document).on('input', '.error', function() {
        $(this).removeClass('error');
    });
    
    // Enhanced table features
    $('.blp-enhanced-table').each(function() {
        const table = $(this);
        
        // Add search functionality
        const searchInput = $('<input type="text" placeholder="Search..." class="blp-table-search" style="margin-bottom: 10px; width: 200px;">');
        table.before(searchInput);
        
        searchInput.on('keyup', function() {
            const value = $(this).val().toLowerCase();
            table.find('tbody tr').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
            });
        });
        
        // Add column sorting
        table.find('th').each(function() {
            if (!$(this).hasClass('no-sort')) {
                $(this).css('cursor', 'pointer').on('click', function() {
                    sortTableColumn(table, $(this).index());
                });
            }
        });
    });
    
    function sortTableColumn(table, columnIndex) {
        const tbody = table.find('tbody');
        const rows = tbody.find('tr').toArray();
        
        rows.sort(function(a, b) {
            const aValue = $(a).find('td').eq(columnIndex).text().trim();
            const bValue = $(b).find('td').eq(columnIndex).text().trim();
            
            // Check if values are numbers
            if (!isNaN(aValue) && !isNaN(bValue)) {
                return parseFloat(aValue) - parseFloat(bValue);
            }
            
            return aValue.localeCompare(bValue);
        });
        
        tbody.empty().append(rows);
    }
    
    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl+S to save forms
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            $('form input[type="submit"]:first').click();
        }
        
        // Escape to clear search
        if (e.key === 'Escape') {
            $('.blp-table-search').val('').trigger('keyup');
        }
    });
    
    // Auto-save drafts for large forms
    let autoSaveTimer;
    $('textarea, input[type="text"]:not(.blp-table-search)').on('input', function() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(() => {
            autoSaveDraft();
        }, 2000);
    });
    
    function autoSaveDraft() {
        const formData = $('form').serialize();
        if (formData) {
            localStorage.setItem('blp_form_draft', formData);
        }
    }
    
    // Restore draft on page load
    const savedDraft = localStorage.getItem('blp_form_draft');
    if (savedDraft && confirm('Restore previously saved draft?')) {
        const params = new URLSearchParams(savedDraft);
        params.forEach((value, name) => {
            $(`[name="${name}"]`).val(value);
        });
        localStorage.removeItem('blp_form_draft');
    }
});