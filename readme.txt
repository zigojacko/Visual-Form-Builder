=== Visual Form Builder ===
Contributors: mmuro
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=G87A9UN9CLPH4&lc=US&item_name=Visual%20Form%20Builder&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted
Tags: form, forms, contact form, form to email, email form, email, input, validation, jquery, shortcode
Requires at least: 3.4.1
Tested up to: 3.5
Stable tag: 2.6.7
License: GPLv2 or later

Build beautiful, fully functional forms in only a few minutes without writing PHP, CSS, or HTML.

== Description ==

*Visual Form Builder* is a plugin that allows you to build and manage all kinds of forms for your website in a single place.  Building a fully functional form takes only a few minutes and you don't have to write one bit of PHP, CSS, or HTML!

= Features =

* Add fields with one click
* Drag-and-drop reordering
* Simple, yet effective, logic-based anti-SPAM system
* Automatically stores form entries in your WordPress database
* Manage form entries in the WordPress dashboard
* Export entries to a CSV file
* Send form submissions to multiple emails
* jQuery Form Validation
* Customized Confirmation Messages
* Redirect to a WordPress Page or a URL
* Confirmation Email Receipt to User
* Standard Fields
* Required Fields
* Shortcode works on any Post or Page
* Embed Multiple Forms on a Post/Page
* One-click form duplication. Copy a form you've already built to save time
* Use your own CSS (if you want)
* Multiple field layout options. Arrange your fields in two, three, or a mixture of columns.

= Field Types =

* Fieldset
* Section (group fields within a fieldset)
* Text input (single line)
* Textarea (multiple lines)
* Checkbox
* Radio (multiple choice)
* Select dropdown
* Address (street, city, state, zip, country)
* Date (uses jQuery UI Date Picker)
* Email
* URL
* Currency
* Number
* Time (12 or 24 hour format)
* Phone (US and International formats)
* HTML
* File Upload
* Instructions (plain or HTML-formatted text)

= Entries =

* Manage submitted entries in WordPress dashboard
* Bulk Export to CSV
* Bulk Delete
* Advanced Filtering
* Search across all entries
* Collect submitted data as well as date submitted and IP Address

= Customized Confirmation Messages =

* Control what is displayed after a user submits a form
* Display HTML-formatted text
* Redirect to a WordPress Page
* Redirect to a custom URL

= Notification Emails =

* Send a customized email to the user after a user submits a form
* Additional HTML-formatted text to be included in the body of the email
* Automatically include a copy of the user's entry

= SPAM Protection =

* Automatically included on every form
* Uses a simple, yet effective, logic-based verification system
* [WordPress Nonce](http://codex.wordpress.org/WordPress_Nonces)


== Installation ==

1. Go to Plugins > Add New
1. Click the Upload link
1. Click Browse and locate the `visual-form-builder.x.x.zip` file
1. Click Install Now
1. After WordPress installs, click on the Activate Plugin link

== Frequently Asked Questions ==

= How do I build my form? =

1. Click on the New Form button and enter a few form details
1. Click the form fields from the box on the left to add it to your form.
1. Edit the information for each form field by clicking on the down arrow.
1. Drag and drop the elements to put them in order.
1. Click Save Form to save your changes.

= What's the deal with the fieldsets? =

Fieldsets, a way to group form fields, are an essential piece of this plugin's HTML. As such, at least one fieldset is required and must be first in the order. Subsequent fieldsets may be placed wherever you would like to start your next grouping of fields.

= Can I use my own verification system such as a CAPTCHA? =

Because of the accessibility and usability problems inherent with a CAPTCHA system, Visual Form Builder will not be using such a system. Other methods of SPAM prevention will be explored to further enhance protection of your forms.

= I'm not getting any emails! What's wrong? =

Some people have reported that after the form is submitted, no email is received.  If this is the case for you, it typically means that your server or web host has not properly configured their SMTP settings.

Try using a plugin such as [WP Mail SMTP](http://wordpress.org/extend/plugins/wp-mail-smtp/) to correct the issue.

= Something in my theme isn't working anymore. What's wrong? =

Visual Form Builder is built using preferred WordPress coding standards. In many cases, some theme authors or plugin developers do not follow these standards and it causes conflicts with those that do follow the standards. The two most common issues have to do with either jQuery or CSS.

**jQuery conflicts**

Visual Form Builder requires at least jQuery version 1.7. Please make sure your theme is updated to use the latest version of jQuery.

**CSS conflicts**

If your forms do not look as expected, chances are there's some CSS in your theme conflicting with the built-in CSS of Visual Form Builder. Please follow the instructions on how to customize the CSS.

**Theme conflicts**

If you have confirmed that you are using the latest version of jQuery and can rule out CSS conflicts, there's probably something in your theme still causing problems.

1. Activate the default Twenty Eleven theme
1. Test your site to see if the issue still occurs

Still having problems even with Twenty Eleven running? If not, it's a conflict with your theme. Otherwise, it's probably a plugin conflict.

**Plugin conflicts**

Before following this process, make sure you have updated all plugins to their latest version (yes, even Visual Form Builder).

1. Deactivate ALL plugins
1. Activate Visual Form Builder
1. Test your site to see if the issue still occurs

If everything works with only Visual Form Builder activated, you have a plugin conflict. Re-activate the plugins one by one until you find the problematic plugin(s).

If, after following the above procedures, you are still having problems please report this issue on the [Support Forum](http://wordpress.org/support/plugin/visual-form-builder). 

= How do I customize the CSS? =

If you want to customize the appearance of the forms using your own CSS, here's how to do it:

1. Add this code to your theme's `functions.php` file: `add_filter( 'visual-form-builder-css', '__return_false' );`
1. Copy everything from `css/visual-form-builder.css` into your theme's `style.css`
1. Change the CSS properties in your theme's `style.css` as needed

If you want to customize the jQuery date picker CSS, follow these steps:

1. Add this code to your theme's `functions.php` file: `add_filter( 'vfb-date-picker-css', '__return_false' );`
1. Refer to the [jQuery UI Date Picker documentation on theming](http://jqueryui.com/demos/datepicker/#theming)

= How do I change the Date Picker configuration? =

The jQuery UI Date Picker is a complex and highly configurable plugin.  By default, Visual Form Builder's date field will use the default options and configuration.

To use the more complex features of the Date Picker plugin, [follow this tutorial](http://matthewmuro.com/2012/02/23/how-to-customize-the-date-picker/).

= How do I translate the field validation text to my language? =

The validation messages (ex: 'This field is required' or 'Please enter a valid email address') are generated by the jQuery Form Validation plugin.

By default, these messages are in English. To translate them, you must create a JavaScript file that contains your translations and insert it into your theme.

Follow these instructions:

In your theme folder, create a JavaScript file. In this example, I'm using `myjs.js`. Add the following code to it and customize the language to what you need:

`jQuery(document).ready(function($) { $.extend($.validator.messages, { required: "Eingabe nötig", email: "Bitte eine gültige E-Mail-Adresse eingeben" }); });`

Now, in your functions.php file, add the following piece of code:

`wp_enqueue_script( 'my-visual-form-builder-validation', get_bloginfo( 'template_url' ) . '/myjs.js' , array( 'jquery', 'jquery-form-validation' ), '', true );`

= How do I export my entries to a CSV? =

There are two ways to export your entries to a CSV: Export All or Export Selected.

To Export All:

1. Go to the Export screen
1. Select the form you would like to export and a date range, if needed
1. Click Download Export File and save the file

To Export Selected:

1. Go to the Entries screen
1. Check boxes next to the entries you wish to export
1. Select the `Export Selected` option under the `Bulk Actions` dropdown
1. Click Apply and save the file

== Screenshots ==

1. Visual Form Builder page
2. Configuring field item options
3. Entries management screen
4. Rendered form on a page

== Changelog ==

**Version 2.6.7**

* Update email headers
* Fix bug where notification email did not send
* Fix textarea value formatting in email

**Version 2.6.6**

* Turn off script debugging

**Version 2.6.5**

* Add confirmation to Delete field
* Add new Address label filter
* Add new CSV delimiter filter
* Add CSS Class option to Submit button
* Update some queries to be compatible with WordPress 3.5
* Update first fieldset warning and output a more noticeable error
* Update tooltip CSS
* Fix media button to use correct action
* Fix missing un-prefixed classes

**Version 2.6.4**

* Fix bug where SVN commit mangled code

**Version 2.6.3**

* Update CSS to now prefix all classes to help eliminate theme conflicts
* Update email function to force a From email that exists on the same domain
* Fix bug affecting File Upload field validation
* Fix database install to use PRIMARY KEY instead of UNIQUE KEY
* Fix bug preventing Export from displaying filtering options
* Minor code cleanups

**Version 2.6.2**

* Fix bug where File Upload field would prevent validation
* Fix bug when selecting entries export
* Fix bug that hid the entries export options
* Fix bug for another missing Save Form button
* Update JS and CSS from CDN to use HTTPS

**Version 2.6.1**

* Fix bug for missing Save Form button
* Fix bug for entries screen options and pagination

**Version 2.6**

* Move plugin into its own menu
* Add new 'All Forms' view with an alphabetical group list
* Add new New Form screen
* Add customizable columns to admin form builder (see Screen Options tab)
* Update meta boxes to be reordered or hidden (see Screen Options tab)
* Update and clean up entry form design
* Update email headers to send from admin email for servers having trouble with sending
* Fix bug where form rendering would behave erratically in Internet Explorer 9
* Fix bug where sender emails would be cut off after 25 characters in the entries database

**Version 2.5**

* Add new Export page for exporting all entries
* Add IDs to each form item on output
* Fix bug where extra quote was outputting on radio buttons
* Fix bug where form name override was not being updated when copying a form
* Fix bug where address formatting broke in the email
* Deprecate Export All from Entries Bulk Actions (to export, see new Export page)
* Update name attribute to remove field key in attempts to prevent POST limit from reaching max memory
* Update server side validation to check for required fields
* Update server side validation to denote which field is failing
* Minor admin CSS update

**Version 2.4.1**

* Fix bug where misspelled variable caused email to not send

**Version 2.4**

* Fix bug where label alignment option was not being saved
* Update spam bot check to only execute when form is submitted
* Update list of spam bots

**Version 2.3.3**

* Fix bug for missing media button image

**Version 2.3.2**

* Fix bug that displayed a warning

**Version 2.3.1**

* Fix bug where Export feature was broken
* Fix bug where server validation failed on certain data types
* Add months drop down filter to Entries list

**Version 2.3**

* Add media button to Posts/Pages to easily embed forms (thanks to Paul Armstrong Designs!)
* Add search feature to Entries
* Add Default Value option to fields
* Add Default Country option to Address block
* Fix bug where Required option was not being set on File Upload fields
* Fix bug where Form Name was not required on Add New page
* Update and optimize Entries query
* Update Security Check messages to be more verbose
* Update email formatting to add line breaks
* Update how the entries files are included to eliminate PHP notices
* Minor updates to CSS

**Version 2.2**

* Add Label Alignment option
* Add server side form validation; SPAM hardening
* Add inline Field help tooltip popups
* Add Spanish translation
* Update Form Settings UI
* Update File Upload field to place attachments in Media Library
* Update Field Description to allow HTML tags
* Update Field Name and CSS Classes to enforce a maxlength of 255 characters
* Update jQueryUI version
* Fix bug preventing form deletion

**Version 2.1**

* Add Accepts option to File Upload field
* Add Small size to field options
* Add Options Layout to Radio and Checkbox fields
* Add Field Layout to field options
* Add Bulgarian translation
* Update jQuery in admin
* Verification fields now customizable
* Verification field now can be set to not required

**Version 2.0**

* Fix bug for misspelled languages folder
* Fix bug for slashes appearing in email and admin
* Fix bug for misaligned rows in CSV export
* Update admin notices functionality
* Update the way Addresses were handled during email
* Add Hungarian translation

**Version 1.9.2**

* Bug fix for copied forms with nested fields

**Version 1.9.1**

* Bug fix for Sender Name, Email, and Notification Email overrides

**Version 1.9**

* Add ability for fields to be nested underneath Fieldsets and Sections
* Add Section Form Item
* Update adding/deleting fields to use AJAX
* Update and improve admin tabs functionality
* Update new form building to no longer force require email details
* Update Delete Form link to require confirmation before deleting

**Version 1.8**

* Add Dynamic Add/Delete for Options for Radio, Select, and Checkbox fields
* Add Dynamic Add/Delete for Email(s) To field
* Add CSS Classes configuration option
* Update Instructions field to allow for images
* Submit button text value now customizable

**Version 1.7**

* Add Instructions Form Item
* Add Duplicate Form feature
* Add Sender Name and Sender Email customization fields to Notifications
* Update CSS

**Version 1.6**

* Fix bug where multiple address blocks could not be used
* Add internationalization support
* Add auto-respond feature to separately notify your users after form submission
* Update jQuery Validation to 1.8.1

**Version 1.5.1**

* Fix bug where missing jQuery prevented multiple form fix from working

**Version 1.5**

* Fix bug where multiple forms on same page could not be submitted individually
* Fix bug where Entries form filter did not work
* Update admin CSS to use it's own file instead of one loaded form WordPress

**Version 1.4**

* Fix bug where database charset wasn't being set and causing character encoding issues
* Fix date submitted to match local date and time settings
* Fix Textarea CSS to respond to large size
* Add File Upload and HTML Form Items
* Add Entries Export feature
* Update View Entries to full page view instead of jQuery show/hide quick view

**Version 1.3.1**

* Fix bug where new Confirmation screen was not being installed
* Fix bug where escaped names and descriptions were not being stripped of slashes properly
* Add missing sprite image for Form Items

**Version 1.3**

* Fix bug where jQuery validation was missing from security field
* Update Form Items UI to make it easier and quicker to add fields
* Add six more Form Items
* Add Confirmation customization
* Update CSS output for some elements

**Version 1.2.1**

* Fix bug where entries table does not install

**Version 1.2**

* Fix bug where reserved words may have been used
* Fix bug where multiple open validation dropdowns could not be used in the builder
* Add entries tracking and management feature
* Improve form submission by removing wp_redirect
* Add Sender Name and Email override

**Version 1.1**

* Fix bug that prevented all selected checkbox options from being submitted
* Add more help text on contextual Help tab
* Fix missing closing paragraph tag on success message

**Version 1.0**

* Plugin launch!

== Upgrade Notice ==

= 2.6.7 =
Fix bug where notification email did not send

= 2.6.5 =
Update some queries to be compatible with WordPress 3.5

= 2.6.2 =
Fix bug where File Upload field would prevent validation, JS and CSS now work on HTTPS, minor fixes.

= 2.6 =
VFB now in its own menu, new All Forms UI, other bug fixes

= 2.5 =
Improved Export entries page, improved server side validation

= 2.4.1 =
Update spam bot check, fixed bug where label alignment option was not being saved

= 2.4 =
Update spam bot check, fixed bug where label alignment option was not being saved

= 2.3.3 =
Fixed missing media button image

= 2.3.2 =
Fixed export entries feature and added a date filter to the entries list

= 2.3.1 =
Fixed export entries feature and added a date filter to the entries list

= 2.3 =
Added media button, Entries search and default values

= 2.2 =
Updated Form Settings UI. Additional SPAM hardening, new inline help tooltips, file uploads now added to Media Library, and a lot more!

= 2.1 =
Please note this version requires WordPress 3.3.  Please update your WordPress install before upgrading to Visual Form Builder 2.1.

= 2.0 =
Bug fix misaligned rows in CSV export, misspelled languages folder, and slashes appearing in emails and admin. Other minor improvements.

= 1.9.2 =
Bug fix for copied form with nested fields.

= 1.9.1 =
Recommend update! Bug fix for Sender Name, Email, and Notification Email overrides.

= 1.9 =
Added Section Form Item, ability to nest fields under Fieldsets and Sections. Improve adding/deleting fields.

= 1.8 =
Submit button text now customizable (click Save Form to access). Added dynamic add/delete for Radio, Select, Checkboxes, and Email(s) To fields.

= 1.7 =
Added Instructions Form Item, Duplicate Form feature, and more customizations to the Notifications.

= 1.6 =
Added auto-responder feature, internationalization support, and fixed validation problems for IE users.

= 1.5.1 =
Fix bug where missing jQuery prevented multiple form fix from working.

= 1.5 =
Fix for submitting multiple forms on a single page. Other bug fixes and improvements.

= 1.4 =
Export entries to a CSV, file uploads, and various bug fixes.

= 1.3.1 =
Recommended update immediately! Fix for bug where confirmation screen does not install.

= 1.3 =
New, faster way to add form items and ability to customize Confirmation. Fix for validation on security field.

= 1.2.1 =
Recommended update immediately! Fix for bug where entries table does not install.