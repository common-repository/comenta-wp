=== Plugin Name ===
Contributors: oriolfb, mlaboria
Donate link: http://comenta.bloks.cat
Tags: comments, twitter
Requires at least: 2.7.0
Tested up to: 2.7.1
Stable tag: trunk

Publish all your comments directly to a twitter account. You can customize the message published.

== Description ==

Publish all your blog comments directly to twitter.

== Installation ==

1. Upload the folder comenta-wp to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to Options -> Comenta WP to configure your twitter account.
1. Enjoy :-)

Configuration:

* User name: User name used on twitter
* Password: Your twitter's password.
* Message: The message that will be twitted. 
	* Parameters:
		* %a: Author of the comment
		* $u: URL of the post, shortened.
		* %t: Title of the post

* URL Shortener Service: You can choose which service use to shorten the URL.
	* Twitter.com
	* TinyURL.com
	* Is.gd
	* Lost.in

== Frequently Asked Questions ==

= Can I choose wich URL Shorten Service use? =

Yes, you can choose between TinyURL, is.gd and Lost.in

= Why the URL is not published to twitter? =

We don't know why yet. Try to configure Twitter.com as the default URL Shortener Service.


== Screenshots ==

1. Configuration screen.

