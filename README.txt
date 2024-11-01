=== tatrapay+ Payment Gateway ===
Contributors: devtatrabanka
Tags: woocommerce, payments, apple pay, credit card, google pay
Requires PHP: 7.4
Requires at least: 5.0
Tested up to: 6.6
Stable tag: 1.0.10
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Latest payment processing solution from Tatrabanka. Accept Pay Later, credit/debit cards and bank accounts.

== Description ==

= Everything You Need to Accept Online Payments =

Accept online payments on any device or through any channel, regardless of your business model. Implementation is quick, allowing you to add individual payment methods as needed to increase your turnover from site purchases.

Offer your customers fast bank transfers not only within Tatra banka through the tatrapay service but also from the accounts of other banks, according to their preferences.

Enable customers worldwide to pay by credit card or via Apple Pay, Google Pay, and Click to Pay. With card payments and the ComfortPay service, you can utilize card memorization for repeated payments.

Enhance your e-shop with a non-purpose loan option, which you can offer through the "Na splátkyTB" payment method.

Advantages of tatrapay+:

- Different payment methods: tatrapay+ supports multiple payment methods in one place, including bank buttons, card payments, and installments.
- Simple integration: Integrate once to support all payment methods and banks.
- Customization options: Customize the appearance of the payment gateway to suit your preferences.
- Transaction monitoring: Monitor the status of transactions anytime through the web interface and the Business PortalTB application.
- Language support: tatrapay+ is available in Slovak and English, with card payments supporting up to 9 languages.
- Currency support: Accept payments in multiple currencies.
- Recurring payments and subscriptions: Easy login and database protection for convenient and timely customer payments.
- Pay-By-Link payments: Support for payments via Pay-By-Link.

= 3rd party service =

This plugin is using [tatrapay+ API](https://developer.tatrabanka.sk/pages/devportal/sk/#/) for two main things:

1. Creating payment, update status of orders, refund of order
2. Showing "Na splátkyTB" button in product detail

[Terms of use](https://developer.tatrabanka.sk/apihub/img/doc/OpenbankingTB_Terms.pdf)
[Privacy policies](https://www.tatrabanka.sk/sk/o-banke/pravne-informacie/)

= Payment Process =

Payment via tatrapay+ is fast, simple, and secure. Customers need to follow a few simple steps to complete their purchase in your e-shop:

- The customer fills out the order in the e-shop and chooses the tatrapay+ payment method. After being redirected to the tatrapay+ payment gateway, they will see all available payment methods. They then choose their preferred payment method (e.g., bank transfer, card payment).
- For card payments, the customer is redirected to the cardpay gateway, where they can pay by manually entering their card information or using Apple Pay, Google Pay, or Click to Pay.
- For bank transfers, the customer selects their bank from the payment buttons and is redirected to their bank's internet banking/mobile application with a pre-filled payment order.
- If the customer opts to purchase goods through loan, they will use the "Na splátkyTB" option.
- After confirming the payment, the customer is redirected back to the e-shop, and the merchant immediately receives a notification about the payment result, allowing them to ship the goods to the customer promptly.

= Conditions for Obtaining tatrapay+ =

To use tatrapay+, the following conditions must be met:

- Your company must be registered in OR SR or ŽR SR, and you must have a business account with Tatra banka.
- Your online store must not offer goods or services that conflict with the legal regulations of the Slovak Republic.
- You must be a direct supplier of goods or a service provider in the online store and conduct activities in your own name (not as an intermediary).
- You must conclude a contract with Tatra banka for the provision of the tatrapay+ service.
- Finally, adapt your electronic environment according to the technical specifications to implement the services offered within tatrapay+.


== Installation ==

= Automatic installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don’t need to leave your web browser. To do an automatic install of the tatrapay+ Payment Gateway, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.

In the search field type “tatrapay+ Payment Gateway for WooCommerce” and click Search Plugins. Once you’ve found our plugin you can view details about it such as the point release, rating, and description. Most importantly, of course, you can install it by simply clicking "Install Now", then "Activate".

= Manual installation =

The manual installation method involves downloading our plugin and uploading it to your application. The WordPress codex contains [instructions on how to do this here](https://wordpress.org/documentation/article/manage-plugins/#manual-plugin-installation-1).

== Frequently Asked Questions ==

= Where can I find client ID and client secret ? =

You can find your credentials here: https://developer.tatrabanka.sk/

= Where can I find redirect URL ? =

Your redirect URL is located in payment options settings in description.

== Screenshots ==

1. Card and bank transfer payment option in checkout
2. Loan payment option in checkout
3. You can enable loan calculator button on product detail
4. Payment gateway settings page
5. Payment gateway appearances settings, you can upload custom logo and use own brand colors

== Changelog ==

= 1.0.10 =
* Prevent showing "Save card" checkbox in classic checkout if not supported

= 1.0.9 =
* Properly round shipping price - fixed commented code

= 1.0.8 =
* Properly round shipping price

= 1.0.7 =
* Fixed wrong code in UserData model

= 1.0.6 =
* Added more restricted special characters
* Handle IPv6 in code

= 1.0.5 =
* Strip special characters from all fields

= 1.0.2 =
* Set php json_encode serialize_precision to -1
* Fixed width of images in checkout

= 1.0.1 =
* Added option to disable buttons in detail and catalog
* Fixed case when shipping address was empty

= 1.0.0 =
* Initial release with payment options in checkout, update status of payment, PayLater option and much more

== Upgrade Notice ==

= 1.0.0 =
Initial release