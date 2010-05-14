=== AdGear Ad Manager ===
Tags: ad, ads
Requires at least: 2.9.2
Tested up to: 2.9.2
Stable tag: trunk

Serve ads from your AdGear account automatically.

== Description ==

AdGear Ad Manager allows you to serve your ads without leaving your WordPress installation.

== Installation ==

1. Ensure you use PHP5, for the json_decode() function and the CURL module
2. Login to your AdGear account, click on your username to edit your settings, enable API access, and copy your API key
3. Create a website for your WordPress blog
4. Upload the ZIP file through WordPress' Plugins UI
5. Activate the plugin
6. Go to Settings, AdGear Settings, then set your username, paste your API key, and set the root API endpoint (should be http://api.admin.adgear.com/, which is filled in by default)
7. Save the settings, then choose your site from the dropdown
8. You're done!

=== Adding an Ad in the sidebar ===

Use the AdGear Widget to put an ad. Click on Appearance, Widgets.

=== Using the Shortcode tag ===

Anywhere in the body of your post, you may use the AdGear Shortcode:

    [adgear_ad id=123]

This Shortcode will generate so-called Static AdSpot Tags.

Sometimes you want to show one set of AdSpots on single post pages vs list pages. You can do so with the `single` option:

    [adgear_ad name="List Sidebar" single=no]
    [adgear_ad name="Sidebar for Article" single=yes]

The List Sidebar AdSpot would be generated only on list pages: archives, front page, etc. The Sidebar for Article AdSpot would be generated if the visitor is viewing a single post's page.

=== Using the Shortcode tag with Dynamic AdSpots ===

Dynamic AdSpots are used when you want AdGear to learn about your site's hierarchy automatically. Please refer to [Creating a Website With Dynamically Managed Ad Spots](http://adgear.com/support/quick-start/configuring-a-complex-website#Creating_a_Website_With_Dynamically_Managed_Ad_Spots) on the AdGear support site for details.

The Shortcode API is very similar to the Static AdSpot tags:

    [adgear_ad format=3 single=yes path=by_categories]
    [adgear_ad format=7 single=yes path=by_tags]
    [adgear_ad format=9 single=no]
    [adgear_ad format=4 single=no]

=== Low-level access for use in themes ===

You can call the `adgear_ad()` PHP function to generate the correct embed tag at a specific point in your layout. `adgear_ad()` can be called in one of 2 modes:

  adgear_ad($adspot_id);
  adgear_ad($format_id, $path)

`$path` must be an array of strings. There are no convenience functions to generate using tags or such. Even the single vs list page is not taken care of.

== Frequently Asked Questions ==

= How do I guess the format IDs? =

Use the META box at the bottom of the post form to build a Shortcode for you.
