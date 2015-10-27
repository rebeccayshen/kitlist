=== CRED Commerce ===
Contributors: AmirHelzer
Donate link: http://wp-types.com/home/cred-commerce/
Tags: CMS, front-end, forms, ecommerce, commerce, e-commerce, webapp, web-app
License: GPLv2
Requires at least: 3.5
Tested up to: 3.8
Stable tag: 0.9.4

Allows to charge payments when visitors submit new content, letting you build classifieds and listing sites easily.

== Description ==

CRED Commerce makes it easy to build websites that require payments from visitors and members. With CRED Commerce you can charge payment for submitting or editing content from front-end forms.

= Easily build classified and directory sites =
Do you need to create a listing site, where visitors can register, submit listings and pay for them to go live?

With CRED and CRED Commerce, you can create forms for submitting new listings. The listings are added directly to the WordPress database. You set the payment for different forms and clients need to pay when they submit content.

= Front-end content creation and editing =
CRED Commerce is a free add-on for the commercial [CRED](http://wp-types.com/home/cred/) plugin. CRED lets you build forms for creating and editing content from the front-end.

= Notifications on purchase and status updates =
CRED Commerce lets you configure notifications to clients and the store admin. You can send personalized emails to visitors after they submit new content, when payments complete (or fail) and when the content status changes in your site.

= Flexible payment processing via WooCommerce =
CRED Commerce uses [WooCommerce](http://wordpress.org/extend/plugins/woocommerce/) for payment processing. You will be able to offer your visitors a wide range of payment options, including PayPal, Google Wallet, bank transfers and other methods. WooCommerce also comes with detailed payment reports, letting you see what you sold, when and to who.

= Requirements and documentation =
To use CRED Commerce, you need to have [CRED](http://wp-types.com/home/cred/), which is part of the [Toolset](http://wp-types.com) family of plugins. You also need to install WooCommerce in your site.

Complete setup and usage instructions are available in the [CRED Commerce guide](http://wp-types.com/home/cred-commerce/).


== Installation ==

1. Upload 'types' to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress


== Frequently Asked Questions ==

= Can I use CRED Commerce by itself? =
CRED Commerce required both CRED and WooCommerce. You can download WooCommerce for free, but you'll need to buy CRED. You can buy CRED by itself or together with all Toolset plugins.

= What payments options can I offer my clients? =
CRED Commerce lets you use any payment option available with WooCommerce. By itself, WooCommerce comes with PayPal standard. You can buy WooCommerce extensions for Google Wallet and many speciality payment processors.

= What themes can I use with CRED Commerce? =
CRED Commerce works with any WordPress theme. Your theme doesn't need to have anything special for payment processing or WooCommerce. CRED Commerce handles the complete payment workflow for you.

= Can I customize the payment pages? =
Certainly! You can create your own custom forms for front-end content creation with CRED. Then, you can fully customize the checkout page by editing Woocommerce templates. You can also add customized messages on the standard WooCommerce checkout and thank-you pages using CRED Commerce.

== Changelog ==

= 0.9.4 =
* Added support for using the slug of CRED forms in API functions (cred_commerce_after_send_notifications and cred_commerce_after_order_completed)

= 0.9.3 =
* Added cred_commerce_after_send_notifications hook for onOrderChange() function

= 0.9 =
* Initial release with CRED 1.1.4 support

== Upgrade Notice ==

= 0.9 =
* Initial release, welcome to CRED Commerce!