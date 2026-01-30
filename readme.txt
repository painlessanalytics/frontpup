=== FrontPup ===
Contributors: painlessanalytics, amandato
Donate link: https://www.painlessanalytics.com/frontpup-cloudfront-wordpress-plugin/
Tags: cloudfront, aws, cdn, amazon, lightsail
Requires at least: 5.5
Tested up to: 6.9
Stable tag: 1.1
Requires PHP: 8.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Your AWS CloudFront companion. Clear Cache and optimize Cache-Control headers within WordPress.

== Description ==

Welcome to FrontPup, your CloudFront companion.

FrontPup allows you to maximize your WordPress website using the AWS CloudFront Content Delivery Network (CDN).

== FrontPup Features ==

* Clear CloudFront Cache (Invalidate)
* Set no-cache headers for all pages (great for development or testing)
* Set public and private Cache-Control headers for caching in CloudFront and Browsers
* Set separate max-age (browser) and s-maxage (CloudFront) cache duration values

== Turbocharge your WordPress Website with CloudFront ==
Using Amazon CloudFront in front of your WordPress website offers significant benefits by improving performance, security, and scalability. CloudFront is a Delivery Network (CDN) with over 750+ Points of Presence (PoPs) around the world plus over 1,100 PoPs within ISP networks. This highly optimized network makes it _extremely efficient_ at delivering your website to your visitors anywhere around the world. "PoP" locations are designed to reduce latency by caching content closer to your site's visitors.

**Performance**

* **Faster Loading Times**, static content (pages, images, CSS, JavaScript) is cached at "edge locations" around the world
* **Improved User Experience**, Faster load times lead to higher user engagement, reduced bounce rates, and improved search engine optimization (SEO) rankings
* **Reduced Server Load**, by serving cached content from edge locations, CloudFront minimizes requests to your WordPress website

When your website is optimized for performance your [PageSpeed Lighthouse scores](https://pagespeed.web.dev/) should improve.

**Enhanced Security**

* **DDoS Protection**, includes AWS Shield Standard
* **SSL/TLS Security**, force https only and includes free SSL Certificates
* **AWS WAF**, a Web Application Firewall with a depth of options including specific WordPress protections (may incur additional costs)

You should achieve a grade of "A" on [Qualys SSL Server Test](https://www.ssllabs.com/ssltest/) when CloudFront is configured with the recommended TLSv1.2_2021 or newer security policy.

**Scalability and Reliability**

* **Global Reach** with over 750 PoPs plus over 1,100 PoPs within ISP networks
* **High Availability**, can serve cached content when site is down
* **Cost Efficiency**, can be cost-effective, especially for websites with high traffic with their new flat rate plans

**Technology**

* **IPv6**, a superior protocol to IPv4 and in some regions of the world the only protocol that is available
* **HTTP/2 and HTTP/3**, improve web performance through faster loading speeds, enhanced security, and better resource handling
* **Gzip and Brotli compression**, smaller file sizes improve application performance by delivering your content faster to visitors
* **Multi-Proxy**, route specific path patterns to other web applications easily by adding additional "Origins", allowing you to host more than WordPress with the same hostname

Learn more about [AWS CloudFront](https://aws.amazon.com/cloudfront/).

== Developed by an AWS Community Builder ==
FrontPup is developed and maintained by [Angelo Mandato](https://angelo.mandato.com), an [AWS Community Builder](https://builder.aws.com/community/@angelomandato). Angelo has been developing WordPress plugins and themes since 2005 and has been architecting applications including WordPress hosted on Amazon Web Services since 2007.

== Installation ==

= Installation from within WordPress =

1. Visit **Plugins > Add New**.
2. Search for **FrontPup**.
3. Install and activate the FrontPup plugin.

= Manual installation =

1. Upload the entire `frontpup` folder to the `/wp-content/plugins/` directory.
2. Visit **Plugins**.
3. Activate the FrontPup plugin.

= After activation =

1. Visit the new **FrontPup** menu.
2. Enable the options you would like to use.

If you configure Clear Cache Settings, you can now use the FrontPup admin bar menu option to quickly clear the cache. This action uses AJAX and will perform the action without leaving the page or disrupting your work.

== Frequently Asked Questions ==

= Do I need an AWS CloudFront to use this plugin? =

Yes, you need an AWS account with [CloudFront](https://aws.amazon.com/cloudfront/) setup for your website.

= Do I need to host my WordPress site on AWS? =

No, but CloudFront is most effective when used within the AWS network.

= What is the best way to host a WordPress website on AWS? =

There are many ways to host a WordPress website on AWS. Here is quick list.

* Lightsail - A CPanel like approach to setting up a WordPress website with only a few clicks
* EC2 Instance(s) - Run your own server(s) to host your WordPress website
* ECS Tasks - Run Docker containers using the AWS Elastic Container Service
* EKS - Run WordPress on AWS Elastic Kubernetes Service

There are thousands of formulas online that explain how to host WordPress on AWS. It comes down to the architecture you want to use and how much complexity you want with managing the servers.

== Screenshots ==

1. Welcome to FrontPup with CloudFront
2. Welcome screen without CloudFront
3. Page Cache-Control settings
4. Clear cache settings
5. Clear CloudFront cache from WordPress admin bar

== Changelog ==

The FrontPup plugin is maintained on GitHub [https://github.com/painlessanalytics/frontpup](https://github.com/painlessanalytics/frontpup)

Changelog:

= 1.1 =
* Added welcome page for the wp-admin
* Added clear cache settings page
* Reorganized admin class, new base class for future settings pages
* Moved views to subfolder of admin folder
* Added FrontPup admin bar menu bar option with "Clear CloudFront Cache" in sub menu (Made it a sub menu so you have to click twice to avoid accidental cache clearing)
* Ajax code for clearing cache created. For now only users who can manage settings can clear the cache (to be customizable in future versions)

= 1.0 =
* First version of this plugin

== Upgrade Notice ==

= 1.0 =
Nothing to update, this is the first version.
