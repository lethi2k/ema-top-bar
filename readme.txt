=== EMA4WP: ZozoEMA Top Bar ===
Contributors: Ibericode, DvanKooten, hchouhan, lapzor
Donate link: https://zozo.vn/ung-dung-email-marketing
Tags: zozoema, form, newsletter, ema4wp, email, opt-in, subscribe, call to action
Requires at least: 1.0.1
Tested up to: 1.0.1
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Requires PHP: 5.3

Adds a ZozoEMA opt-in form to the top or bottom of your WordPress site.

== Description ==

Adds a beautiful, customizable sign-up bar to the top of your WordPress site. This bar is guaranteed to get the attention of your visitor and
increase your ZozoEMA subscribers.

> This plugin is an add-on for the [EMA4WP: ZozoEMA for WordPress plugin](https://wordpress.org/plugins/zozoema-for-wp/).

= ZozoEMA Sign-Up Bar, at a glance.. =

ZozoEMA Top Bar adds a simple yet beautiful & customizable opt-in bar to the top or bottom of your WordPress site.

Using this bar, people can subscribe to a ZozoEMA list of your choice.

- Guaranteed to boost conversions.
- Unobtrusive, visitors can easily dismiss the bar.
- Easy to install & configure, just select a ZozoEMA list and you're good to.
- Customizable, you can edit the bar text and colors from the plugin settings.
- The bar can be at the top or bottom of the visitor's screen
- Lightweight, the plugin consists of just a single 4kb JavaScript file.

= Development of ZozoEMA Top Bar =

Bug reports (and Pull Requests) for [ZozoEMA Top Bar are welcomed on GitHub](https://github.com/lethi2k/ema-top-bar). Please note that GitHub is _not_ a support forum.

**More information**

- [EMA4WP: ZozoEMA for WordPress](https://wordpress.org/plugins/zozoema-for-wp/)
- Developers; follow or contribute to the [ZozoEMA Top Bar plugin on GitHub]https://github.com/lethi2k/ema-top-bar)

== Installation ==

= ZozoEMA for WordPress =

Since this plugin depends on the [ZozoEMA for WordPress plugin](https://wordpress.org/plugins/zozoema-for-wp/), you will need to install that first.

= Installing ZozoEMA Top Bar =

1. In your WordPress admin panel, go to *Plugins > New Plugin*, search for **ZozoEMA Top Bar** and click "*Install now*"
1. Alternatively, download the plugin and upload the contents of `zozoema-top-bar.zip` to your plugins directory, which usually is `/wp-content/plugins/`.
1. Activate the plugin
1. Set [your ZozoEMA API key](https://appv4.zozo.vn/account/api) in **ZozoEMA for WP > ZozoEMA Settings**.
1. Select a ZozoEMA list to subscribe to in **ZozoEMA for WP > Top Bar**.
1. _(Optional)_ Customize the look & position of your opt-in bar.

== Frequently Asked Questions ==

= How to disable the bar on certain pages? =

For now, you will have to use a filter to disable the bar on certain pages. The following example only loads the Top Bar on your blog post pages.

`
add_filter( 'ematb_show_bar', function( $show ) {
	return is_single();
} );
`

Another example, this only loads the bar on your "contact" page.

`
add_filter( 'ematb_show_bar', function( $show ) {
	return is_page('contact');
} );
`

Have a look at the [Conditional Tags](https://appv4.zozo.vn/frontend/docs/api/v1) page for all accepted functions.

= How to add a name field to the bar? =

You can use the following code snippet to show a "NAME" field in your bar.

`
add_action( 'ematb_before_submit_button', function() {
    echo '<input type="text" name="NAME" placeholder="Your name" />';
});

add_filter( 'ematb_subscriber_data', function( $subscriber ) {
    if( ! empty( $_POST['NAME'] ) ) {
        $subscriber->merge_fields['NAME'] = sanitize_text_field( $_POST['NAME'] );
    }

    return $subscriber;
});
`

**KB:** [Add name field to ZozoEMA Top Bar](https://appv4.zozo.vn/frontend/docs/api/v1)

= How to hide the bar on small screens? =

Adding the following CSS to your site should hide the bar on all screens smaller than 600px. The [Simple Custom CSS](https://wordpress.org/plugins/simple-custom-css/) plugin is great for adding custom CSS.

`
@media( max-width: 600px ) {
	&#35;zozoema-top-bar { display: none !important; }
}
`

= I think I found a bug. What now? =

Please report it on [GitHub issues](https://github.com/lethi2k/ema-top-bar) if it's not in the list of known issues.

= I have another question =

Please open a topic on the [WordPress.org plugin support forums](https://github.com/lethi2k/ema-top-bar).


== Screenshots ==

1. The ZozoEMA Top Bar in action on the [ZozoEMA for WordPress site](https://appv4.zozo.vn/lists).
2. The settings page of the ZozoEMA Top Bar plugin.

== Changelog ==


#### 1.0.1 - Mar 9, 2020

- Add setting to disable bar (stop loading it altogether) after it is used.
- Increase default cookie lifetime to 1 year.

