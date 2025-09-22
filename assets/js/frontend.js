jQuery(document).ready(function($) {
    'use strict';
    
    // Global variables
    let selectedPlan = null;
    let selectedPaymentMethod = null;
    let isSubmitting = false;
    
    // Initialize
    init();
    
    function init() {
        bindEvents();
        handleUrlParams();
        initializeComponents();
    }
    
    function bindEvents() {
        // Plan selection
        $(document).on('click', '.blp-select-plan', handlePlanSelection);
        
        // Payment method selection
        $(document).on('click', '.blp-payment-method', handlePaymentMethodSelection);
        
        // Modal controls
        $(document).on('click', '.blp-modal-close', closeModals);
        $(document).on('click', '.blp-modal', function(e) {
            if (e.target === this) {
                closeModals();
            }
        });
        
        // Auth tabs
        $(document).on('click', '.blp-auth-tab', handleAuthTabs);
        
        // Form submissions
        $(document).on('submit', '#blp-signup-form', handleSignup);
        $(document).on('submit', '#blp-login-form', handleLogin);
        $(document).on('submit', '#blp-listing-form', handleListingSubmission);
        $(document).on('submit', '#blp-stripe-payment-form', handleStripePayment);
        
        // Dashboard actions
        $(document).on('click', '#blp-add-listing-btn, #blp-add-first-listing', showAddListingModal);
        $(document).on('click', '.blp-edit-listing', handleEditListing);
        $(document).on('click', '.blp-delete-listing', handleDeleteListing);
        $(document).on('click', '.blp-cancel-listing', closeModals);
        
        // Credit card formatting
        $(document).on('input', '#card-number', formatCardNumber);
        $(document).on('input', '#card-expiry', formatCardExpiry);
        $(document).on('input', '#card-cvc', formatCardCvc);
        
        // Image preview
        $(document).on('change', '#listing-image', handleImagePreview);
        
        // Form validation
        $(document).on('input', 'input[required], textarea[required], select[required]', clearFieldError);
        
        // Escape key to close modals
        $(document).on('keydown', function(e) {
            if (e.keyCode === 27) {
                closeModals();
            }
        });
    }
    
    function initializeComponents() {
        // Initialize tooltips if available
        if ($.fn.tooltip) {
            $('[data-tooltip]').tooltip();
        }
        
        // Auto-resize textareas
        $('textarea').each(function() {
            this.setAttribute('style', 'height:' + (this.scrollHeight) + 'px;overflow-y:hidden;');
        }).on('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    }
    
    function handleUrlParams() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('payment') === 'success') {
            showMessage(__('Payment successful! Please complete your registration.', 'business-listings-pro'), 'success');
            setTimeout(() => {
                showAuthModal();
            }, 1000);
        } else if (urlParams.get('payment') === 'cancelled') {
            showMessage(__('Payment was cancelled.', 'business-listings-pro'), 'warning');
        }
    }
    
    function handlePlanSelection() {
        if (isSubmitting) return;
        
        selectedPlan = $(this).data('plan-id');
        const planCard = $(this).closest('.blp-plan-card');
        const planName = planCard.find('.blp-plan-name').text();
        const planPrice = planCard.find('.blp-plan-price').text();
        
        // Update modal with selected plan info
        $('.blp-selected-plan-info .plan-name').text(planName);
        $('.blp-selected-plan-info .plan-price').text(planPrice);
        
        if (!blp_ajax.user_logged_in) {
            showAuthModal();
        } else {
            showPaymentModal();
        }
    }
    
    function handlePaymentMethodSelection() {
        if (isSubmitting) return;
        
        $('.blp-payment-method').removeClass('active');
        $(this).addClass('active');
        selectedPaymentMethod = $(this).data('method');
        
        if (selectedPaymentMethod === 'stripe') {
            $('#blp-stripe-form').slideDown(300);
        } else {
            $('#blp-stripe-form').slideUp(300);
            // Auto-process PayPal payment
            setTimeout(() => {
                processPayment();
            }, 500);
        }
    }
    
    function handleAuthTabs() {
        const tab = $(this).data('tab');
        
        $('.blp-auth-tab').removeClass('active');
        $(this).addClass('active');
        
        $('.blp-auth-form').hide();
        $(`#blp-${tab}-form`).show();
        
        $('#blp-auth-title').text(tab === 'signup' ? __('Sign Up', 'business-listings-pro') : __('Login', 'business-listings-pro'));
    }
    
    function handleSignup(e) {
        e.preventDefault();
        
        if (isSubmitting) return;
        
        const form = $(this);
        const formData = {
            username: $('#signup-username').val().trim(),
            email: $('#signup-email').val().trim(),
            password: $('#signup-password').val(),
            action: 'blp_register_user',
            nonce: blp_ajax.nonce
        };
        
        // Validate form
        if (!validateSignupForm(formData)) {
            return;
        }
        
        isSubmitting = true;
        showLoading(form);
        
        $.post(blp_ajax.ajax_url, formData)
            .done(function(response) {
                if (response.success) {
                    showMessage(response.data.message || __('Account created successfully!', 'business-listings-pro'), 'success');
                    closeModals();
                    
                    // Redirect after a short delay
                    setTimeout(() => {
                        if (response.data.redirect) {
                            window.location.href = response.data.redirect;
                        } else {
                            window.location.reload();
                        }
                    }, 1500);
                } else {
                    showMessage(response.data || __('Registration failed. Please try again.', 'business-listings-pro'), 'error');
                }
            })
            .fail(function() {
                showMessage(__('An error occurred. Please try again.', 'business-listings-pro'), 'error');
            })
            .always(function() {
                isSubmitting = false;
                hideLoading(form);
            });
    }
    
    function handleLogin(e) {
        e.preventDefault();
        
        if (isSubmitting) return;
        
        const form = $(this);
        const formData = {
            username: $('#login-username').val().trim(),
            password: $('#login-password').val(),
            action: 'blp_login_user',
            nonce: blp_ajax.nonce
        };
        
        // Validate form
        if (!formData.username || !formData.password) {
            showMessage(__('Username and password are required.', 'business-listings-pro'), 'error');
            return;
        }
        
        isSubmitting = true;
        showLoading(form);
        
        $.post(blp_ajax.ajax_url, formData)
            .done(function(response) {
                if (response.success) {
                    showMessage(response.data.message || __('Login successful!', 'business-listings-pro'), 'success');
                    closeModals();
                    
                    setTimeout(() => {
                        if (response.data.redirect) {
                            window.location.href = response.data.redirect;
                        } else if (selectedPlan) {
                            showPaymentModal();
                        } else {
                            window.location.reload();
                        }
                    }, 1000);
                } else {
                    showMessage(response.data || __('Login failed. Please check your credentials.', 'business-listings-pro'), 'error');
                }
            })
            .fail(function() {
                showMessage(__('An error occurred. Please try again.', 'business-listings-pro'), 'error');
            })
            .always(function() {
                isSubmitting = false;
                hideLoading(form);
            });
    }
    
    function handleListingSubmission(e) {
        e.preventDefault();
        
        if (isSubmitting) return;
        
        const form = $(this);
        const formData = new FormData();
        
        // Add form fields
        formData.append('action', 'blp_submit_listing');
        formData.append('nonce', blp_ajax.nonce);
        formData.append('title', $('#listing-title').val().trim());
        formData.append('description', $('#listing-description').val().trim());
        formData.append('category', $('#listing-category').val());
        formData.append('phone', $('#listing-phone').val().trim());
        formData.append('address', $('#listing-address').val().trim());
        formData.append('website', $('#listing-website').val().trim());
        formData.append('email', $('#listing-email').val().trim());
        
        const listingId = $('#listing-id').val();
        if (listingId) {
            formData.append('listing_id', listingId);
        }
        
        // Add image file if selected
        const imageFile = $('#listing-image')[0].files[0];
        if (imageFile) {
            formData.append('image', imageFile);
        }
        
        // Validate required fields
        if (!formData.get('title') || !formData.get('description') || !formData.get('category')) {
            showMessage(__('Title, description, and category are required.', 'business-listings-pro'), 'error');
            return;
        }
        
        isSubmitting = true;
        showLoading(form);
        
        $.ajax({
            url: blp_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showMessage(response.data || __('Listing saved successfully!', 'business-listings-pro'), 'success');
                    closeModals();
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showMessage(response.data || __('Error saving listing. Please try again.', 'business-listings-pro'), 'error');
                }
            },
            error: function() {
                showMessage(__('An error occurred. Please try again.', 'business-listings-pro'), 'error');
            },
            complete: function() {
                isSubmitting = false;
                hideLoading(form);
            }
        });
    }
    
    function handleStripePayment(e) {
        e.preventDefault();
        
        if (isSubmitting) return;
        
        // For now, show a message that Stripe is coming soon
        showMessage(__('Stripe integration coming soon. Please use PayPal for now.', 'business-listings-pro'), 'info');
    }
    
    function processPayment() {
        if (!selectedPlan || !selectedPaymentMethod || isSubmitting) {
            showMessage(__('Please select a plan and payment method.', 'business-listings-pro'), 'error');
            return;
        }
        
        const paymentData = {
            action: 'blp_process_payment',
            nonce: blp_ajax.nonce,
            plan_id: selectedPlan,
            payment_method: selectedPaymentMethod
        };
        
        isSubmitting = true;
        showLoading($('.blp-payment-options'));
        
        $.post(blp_ajax.ajax_url, paymentData)
            .done(function(response) {
                if (response.success) {
                    if (response.data.redirect) {
                        showMessage(__('Redirecting to payment...', 'business-listings-pro'), 'info');
                        setTimeout(() => {
                            window.location.href = response.data.redirect;
                        }, 1000);
                    } else {
                        showMessage(response.data.message || __('Payment processed successfully!', 'business-listings-pro'), 'success');
                        closeModals();
                    }
                } else {
                    showMessage(response.data || __('Payment processing failed. Please try again.', 'business-listings-pro'), 'error');
                }
            })
            .fail(function() {
                showMessage(__('Payment processing failed. Please try again.', 'business-listings-pro'), 'error');
            })
            .always(function() {
                isSubmitting = false;
                hideLoading($('.blp-payment-options'));
            });
    }
    
    function handleEditListing() {
        if (isSubmitting) return;
        
        const listingId = $(this).data('listing-id');
        const button = $(this);
        const originalText = button.html();
        
        button.html('<i class="blp-spinner"></i> ' + __('Loading...', 'business-listings-pro')).prop('disabled', true);
        
        // Get listing data via AJAX
        $.post(blp_ajax.ajax_url, {
            action: 'blp_get_listing',
            nonce: blp_ajax.nonce,
            listing_id: listingId
        })
        .done(function(response) {
            if (response.success) {
                populateListingForm(response.data, listingId);
                showAddListingModal();
                $('#blp-listing-modal-title').text(__('Edit Listing', 'business-listings-pro'));
                $('#blp-listing-form button[type="submit"] .blp-btn-text').text(__('Update Listing', 'business-listings-pro'));
            } else {
                showMessage(response.data || __('Error loading listing data.', 'business-listings-pro'), 'error');
            }
        })
        .fail(function() {
            showMessage(__('Error loading listing data.', 'business-listings-pro'), 'error');
        })
        .always(function() {
            button.html(originalText).prop('disabled', false);
        });
    }
    
    function handleDeleteListing() {
        if (isSubmitting) return;
        
        const listingId = $(this).data('listing-id');
        const listingTitle = $(this).closest('tr').find('.blp-listing-title-cell strong').text();
        
        if (!confirm(__('Are you sure you want to delete "%s"? This action cannot be undone.', 'business-listings-pro').replace('%s', listingTitle))) {
            return;
        }
        
        isSubmitting = true;
        const button = $(this);
        const originalText = button.text();
        button.text(__('Deleting...', 'business-listings-pro')).prop('disabled', true);
        
        $.post(blp_ajax.ajax_url, {
            action: 'blp_delete_listing',
            nonce: blp_ajax.nonce,
            listing_id: listingId
        })
        .done(function(response) {
            if (response.success) {
                showMessage(response.data || __('Listing deleted successfully.', 'business-listings-pro'), 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showMessage(response.data || __('Error deleting listing.', 'business-listings-pro'), 'error');
            }
        })
        .fail(function() {
            showMessage(__('An error occurred. Please try again.', 'business-listings-pro'), 'error');
        })
        .always(function() {
            isSubmitting = false;
            button.text(originalText).prop('disabled', false);
        });
    }
    
    function handleImagePreview() {
        const file = this.files[0];
        const preview = $('#image-preview');
        
        if (file) {
            // Validate file type
            if (!file.type.match('image.*')) {
                showMessage(__('Please select a valid image file.', 'business-listings-pro'), 'error');
                $(this).val('');
                preview.hide();
                return;
            }
            
            // Validate file size (2MB max)
            if (file.size > 2 * 1024 * 1024) {
                showMessage(__('Image file size must be less than 2MB.', 'business-listings-pro'), 'error');
                $(this).val('');
                preview.hide();
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.find('img').attr('src', e.target.result);
                preview.show();
            };
            reader.readAsDataURL(file);
        } else {
            preview.hide();
        }
    }
    
    // Modal functions
    function showPaymentModal() {
        $('#blp-payment-modal').fadeIn(300);
        $('body').addClass('blp-modal-open');
    }
    
    function showAuthModal() {
        $('#blp-auth-modal').fadeIn(300);
        $('body').addClass('blp-modal-open');
    }
    
    function showAddListingModal() {
        resetListingForm();
        $('#blp-listing-modal').fadeIn(300);
        $('body').addClass('blp-modal-open');
    }
    
    function closeModals() {
        $('.blp-modal').fadeOut(300);
        $('body').removeClass('blp-modal-open');
        resetForms();
        selectedPaymentMethod = null;
    }
    
    function resetForms() {
        $('form').each(function() {
            if (this.id !== 'blp-listing-form') {
                this.reset();
            }
        });
        $('.blp-payment-method').removeClass('active');
        $('#blp-stripe-form').hide();
        $('.blp-form-group').removeClass('has-error');
        $('.blp-error-message').remove();
    }
    
    function resetListingForm() {
        $('#blp-listing-form')[0].reset();
        $('#listing-id').val('');
        $('#blp-listing-modal-title').text(__('Add New Listing', 'business-listings-pro'));
        $('#blp-listing-form button[type="submit"] .blp-btn-text').text(__('Save Listing', 'business-listings-pro'));
        $('#image-preview').hide();
        $('.blp-form-group').removeClass('has-error');
        $('.blp-error-message').remove();
    }
    
    function populateListingForm(data, listingId) {
        $('#listing-id').val(listingId);
        $('#listing-title').val(data.title);
        $('#listing-description').val(data.description);
        $('#listing-category').val(data.category);
        $('#listing-phone').val(data.phone);
        $('#listing-address').val(data.address);
        $('#listing-website').val(data.website);
        $('#listing-email').val(data.email);
        
        // Show existing image if available
        if (data.image_url) {
            $('#image-preview img').attr('src', data.image_url);
            $('#image-preview').show();
        }
    }
    
    // Utility functions
    function formatCardNumber() {
        let value = $(this).val().replace(/\D/g, '');
        value = value.replace(/(\d{4})(?=\d)/g, '$1 ');
        $(this).val(value);
    }
    
    function formatCardExpiry() {
        let value = $(this).val().replace(/\D/g, '');
        if (value.length >= 2) {
            value = value.substring(0, 2) + '/' + value.substring(2, 4);
        }
        $(this).val(value);
    }
    
    function formatCardCvc() {
        let value = $(this).val().replace(/\D/g, '');
        $(this).val(value);
    }
    
    function showLoading($element) {
        $element.addClass('blp-loading');
        $element.find('button[type="submit"]').prop('disabled', true);
        $element.find('.blp-btn-text').hide();
        $element.find('.blp-btn-loading').show();
    }
    
    function hideLoading($element) {
        $element.removeClass('blp-loading');
        $element.find('button[type="submit"]').prop('disabled', false);
        $element.find('.blp-btn-text').show();
        $element.find('.blp-btn-loading').hide();
    }
    
    function showMessage(message, type = 'info') {
        // Remove existing messages
        $('.blp-message').remove();
        
        const messageClass = type === 'error' ? 'blp-message-error' : 
                           type === 'success' ? 'blp-message-success' : 
                           type === 'warning' ? 'blp-message-warning' : 'blp-message-info';
        
        // Add icon based on type
        let icon = '';
        switch(type) {
            case 'success':
                icon = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>';
                break;
            case 'error':
                icon = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>';
                break;
            case 'warning':
                icon = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>';
                break;
            default:
                icon = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>';
        }
        
        const messageHtml = `
            <div class="blp-message ${messageClass}">
                <div class="blp-message-content">
                    <div class="blp-message-icon">${icon}</div>
                    <span class="blp-message-text">${message}</span>
                    <button class="blp-message-close">&times;</button>
                </div>
            </div>
        `;
        
        $('body').append(messageHtml);
        
        // Auto-dismiss after 6 seconds for better UX
        setTimeout(() => {
            $('.blp-message').fadeOut(300, function() {
                $(this).remove();
            });
        }, 6000);
        
        // Handle manual dismiss
        $('.blp-message-close').on('click', function() {
            $(this).closest('.blp-message').fadeOut(300, function() {
                $(this).remove();
            });
        });
    }
    
    function validateSignupForm(data) {
        let isValid = true;
        
        if (!data.username || data.username.length < 3) {
            showFieldError('#signup-username', __('Username must be at least 3 characters long.', 'business-listings-pro'));
            isValid = false;
        }
        
        if (!data.email || !isValidEmail(data.email)) {
            showFieldError('#signup-email', __('Please enter a valid email address.', 'business-listings-pro'));
            isValid = false;
        }
        
        if (!data.password || data.password.length < 6) {
            showFieldError('#signup-password', __('Password must be at least 6 characters long.', 'business-listings-pro'));
            isValid = false;
        }
        
        return isValid;
    }
    
    function showFieldError(fieldSelector, message) {
        const field = $(fieldSelector);
        const group = field.closest('.blp-form-group');
        
        group.addClass('has-error');
        group.find('.blp-error-message').remove();
        group.append(`<div class="blp-error-message">${message}</div>`);
    }
    
    function clearFieldError() {
        const group = $(this).closest('.blp-form-group');
        group.removeClass('has-error');
        group.find('.blp-error-message').remove();
    }
    
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    function __(text, domain) {
        // Simple translation function - in a real plugin, this would use WordPress i18n
        return text;
    }
    
    // Smooth scrolling for anchor links
    $('a[href*="#"]').on('click', function(e) {
        const target = $(this.hash);
        if (target.length) {
            e.preventDefault();
            $('html, body').animate({
                scrollTop: target.offset().top - 100
            }, 800);
        }
    });
    
    // Prevent body scroll when modal is open
    $('body').on('DOMNodeInserted', '.blp-modal', function() {
        if ($(this).is(':visible')) {
            $('body').addClass('blp-modal-open');
        }
    });
    
    // Handle window resize
    $(window).on('resize', function() {
        // Adjust modal positioning if needed
        $('.blp-modal:visible').each(function() {
            const modal = $(this);
            const content = modal.find('.blp-modal-content');
            
            if (content.height() > $(window).height() - 40) {
                content.css('max-height', $(window).height() - 40);
            }
        });
    });
    
    // Enhanced form validation with real-time feedback
    $('input[required], textarea[required], select[required]').on('blur', function() {
        const field = $(this);
        const value = field.val().trim();
        const group = field.closest('.blp-form-group');
        
        if (!value) {
            group.addClass('has-error');
            if (!group.find('.blp-error-message').length) {
                group.append('<div class="blp-error-message">' + __('This field is required.', 'business-listings-pro') + '</div>');
            }
        } else {
            group.removeClass('has-error');
            group.find('.blp-error-message').remove();
        }
    });
    
    // Email validation
    $('input[type="email"]').on('blur', function() {
        const field = $(this);
        const value = field.val().trim();
        const group = field.closest('.blp-form-group');
        
        if (value && !isValidEmail(value)) {
            group.addClass('has-error');
            group.find('.blp-error-message').remove();
            group.append('<div class="blp-error-message">' + __('Please enter a valid email address.', 'business-listings-pro') + '</div>');
        }
    });
    
    // URL validation
    $('input[type="url"]').on('blur', function() {
        const field = $(this);
        const value = field.val().trim();
        const group = field.closest('.blp-form-group');
        
        if (value && !isValidUrl(value)) {
            group.addClass('has-error');
            group.find('.blp-error-message').remove();
            group.append('<div class="blp-error-message">' + __('Please enter a valid URL (e.g., https://example.com).', 'business-listings-pro') + '</div>');
        }
    });
    
    function isValidUrl(url) {
        try {
            new URL(url);
            return true;
        } catch {
            return false;
        }
    }
    
    // Add loading animation to buttons
    $('.blp-btn').on('click', function() {
        const button = $(this);
        if (!button.hasClass('blp-loading') && button.attr('type') === 'submit') {
            setTimeout(() => {
                if (!button.hasClass('blp-loading')) {
                    button.addClass('blp-btn-loading-state');
                }
            }, 100);
        }
    });
});