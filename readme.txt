=== Business Listings Pro ===
Contributors: yourname
Tags: business, listings, directory, subscriptions, payments
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Professional business listings plugin with subscription plans, payment integration, and admin controls.

== Description ==

Business Listings Pro is a comprehensive WordPress plugin that allows you to create a professional business directory with subscription-based access. Users can select from customizable plans, make payments through PayPal or Stripe, and manage their business listings through a user-friendly dashboard.

**Key Features:**

* **Subscription Plans**: Create and manage multiple subscription plans with different features and pricing
* **Payment Integration**: Accept payments through PayPal and Stripe
* **User Dashboard**: Comprehensive dashboard for users to manage their listings
* **Admin Controls**: Full administrative control over plans, listings, and user subscriptions
* **Listing Management**: Custom post type for business listings with approval system
* **Responsive Design**: Mobile-friendly interface with modern design
* **Professional UI**: Apple-level design aesthetics with smooth animations

**Admin Features:**
* Create and edit subscription plans
* Manage user subscriptions
* Approve/reject business listings
* Comprehensive dashboard with statistics
* Payment gateway configuration
* User management

**User Features:**
* Select and purchase subscription plans
* User registration and login
* Dashboard to manage business listings
* Add business information, images, and contact details
* Track listing status and subscription details

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/business-listings-pro` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to Listings Pro > Settings to configure payment gateways.
4. Create your first subscription plan in Listings Pro > Plans.
5. Use shortcodes to display plans and listings on your pages.

== Shortcodes ==

* `[blp_plans]` - Display subscription plans
* `[blp_listings]` - Display published business listings
* `[blp_dashboard]` - Display user dashboard (for logged-in users)

== Configuration ==

**PayPal Setup:**
1. Go to Listings Pro > Settings
2. Enter your PayPal email address
3. Choose between sandbox (testing) and live mode

**Stripe Setup:**
1. Get your API keys from Stripe Dashboard
2. Enter your Publishable Key and Secret Key in settings
3. Test the connection using the test button

== Frequently Asked Questions ==

= How do users access their dashboard? =

After purchasing a subscription and logging in, users can access their dashboard using the `[blp_dashboard]` shortcode or by visiting /dashboard if you've created a page with that slug.

= Can I customize the subscription plans? =

Yes! You can create unlimited subscription plans with custom names, descriptions, prices, durations, and features through the admin interface.

= How does the approval system work? =

When users submit listings, they are set to "pending" status by default. Administrators can review and approve/reject listings from the WordPress admin area.

= Is the plugin responsive? =

Yes, the plugin is fully responsive and works on all devices including mobile phones and tablets.

== Changelog ==

= 1.0.0 =
* Initial release
* Subscription plan management
* PayPal and Stripe integration
* User dashboard and listing management
* Admin approval system
* Responsive design

== Upgrade Notice ==

= 1.0.0 =
This is the initial release of Business Listings Pro.

== Support ==

For support and documentation, please visit our website or contact us through the support channels.