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
1. Upload `adgear-ad-manager.php` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Login to your AdGear account, click on your username to edit your settings, enable API access, and copy your API key
1. In AdGear, create a website for your WordPress blog
1. Back in WordPress, go to Settings, AdGear Settings, then set your username, paste your API key, and set the root API endpoint (should be http://api.admin.adgear.com/, which is filled in by default)
1. Save the settings, then choose your site from the dropdown

=== Adding an Ad in the sidebar ===

You have two choices: you can use Shortcodes or straight PHP from your template. If you use Shortcodes, you have to enable them for all plugins, globally. There is a slight security risk associated with this: if a malicious plugin used a Shortcode without your knowledge in a sidebar, the Shortcode would be interpreted, and might do something.

=== Using the Shortcode tag ===

Anywhere in the body of your post or in widgets (if Shortcodes are enabled in sidebars), you may use the AdGear Shortcode:

    [adgear_ad name=in-article]
    [adgear_ad id=123]

These two Shortcodes will generate so-called Static AdSpot Tags. If your ad spot's name includes a space, you will need to enclose the name in double quotes:

    [adgear_ad name="Front Page"]

Sometimes you want to show one set of AdSpots on single post pages vs list pages. You can do so with the `single` option:

    [adgear_ad name="List Sidebar" single=no]
    [adgear_ad name="Sidebar for Article" single=yes]

The List Sidebar AdSpot would be generated only on list pages: archives, front page, etc. The Sidebar for Article AdSpot would be generated if the visitor is viewing a single post's page.

=== Using the Shortcode tag with Dynamic AdSpots ===

Dynamic AdSpots are used when you want AdGear to learn about your site's hierarchy automatically. Please refer to [Creating a Website With Dynamically Managed Ad Spots](http://adgear.com/support/quick-start/configuring-a-complex-website#Creating_a_Website_With_Dynamically_Managed_Ad_Spots) on the AdGear support site for details.

The Shortcode API is very similar to the Static AdSpot tags:

    [adgear_ad format=banner single=yes path=by_categories]
    [adgear_ad format=skyscraper single=yes path=by_tags]
    [adgear_ad format=leaderboard single=no]
    [adgear_ad format=half-button single=no]

== Frequently Asked Questions ==

= A question that someone might have =

An answer to that question.

= What about foo bar? =

Answer to foo bar dilemma.

== Screenshots ==

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from
the directory of the stable readme.txt, so in this case, `/tags/4.3/screenshot-1.png` (or jpg, jpeg, gif)
2. This is the second screen shot

== Changelog ==

= 1.0 =
* A change since the previous version.
* Another change.

= 0.5 =
* List versions from most recent at top to oldest at bottom.

== Upgrade Notice ==

= 1.0 =
Upgrade notices describe the reason a user should upgrade.  No more than 300 characters.

= 0.5 =
This version fixes a security related bug.  Upgrade immediately.

== Arbitrary section ==

You may provide arbitrary sections, in the same format as the ones above.  This may be of use for extremely complicated
plugins where more information needs to be conveyed that doesn't fit into the categories of "description" or
"installation."  Arbitrary sections will be shown below the built-in sections outlined above.

== A brief Markdown Example ==

Ordered list:

1. Some feature
1. Another feature
1. Something else about the plugin

Unordered list:

* something
* something else
* third thing

Here's a link to [WordPress](http://wordpress.org/ "Your favorite software") and one to [Markdown's Syntax Documentation][markdown syntax].
Titles are optional, naturally.

[markdown syntax]: http://daringfireball.net/projects/markdown/syntax
            "Markdown is what the parser uses to process much of the readme file"

Markdown uses email style notation for blockquotes and I've been told:
> Asterisks for *emphasis*. Double it up  for **strong**.

`<?php code(); // goes in backticks ?>`
