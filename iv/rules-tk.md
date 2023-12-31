# https://instantview.telegram.org/docs
# https://instantview.telegram.org/samples/medium.com/
# https://instantview.telegram.org/docs#instant-view-format
# https://instantview.telegram.org/my/



~version: "2.0"

?exists: /html/head/meta[@property="article:published_time"]
?exists: /html/head/meta[@property="og:title"]

body:     //article
# title:    $body//h1[1]
title: meta[@property="og:title"]
subtitle: $title/next-sibling::h2

$bg_section: $body//section[has-class("is-imageBackgrounded")]
@background_to_image: $bg_section//div[has-class("section-backgroundImage")]
<figure>: $bg_section//div[has-class("section-background")]
cover: $bg_section//figure
cover: $title/preceding::figure[has-class("d-none")][.//img][not(has-class("d-none"))]
cover: $title/next-sibling::figure[.//img]
cover: $subtitle/next-sibling::figure[.//img]
cover: //figure[has-class("d-none")]

image_url: $cover/self::img/@src
image_url: $cover/self::figure//img/@src
image_url: //head/meta[@property="og:image"]/@content

details: //details
detailsHeader: //summary


<span>:  //blockquote[has-class("graf--pullquote")]//strong
<aside>: //blockquote[has-class("graf--pullquote")]

@inline: $body//iframe[starts-with(@src, "/media/")]

@combine(<br>,<br>): $body//pre/next-sibling::pre
@combine(<br>,<br>): $body//blockquote/next-sibling::blockquote


$embed:           $body//div[has-class("graf--mixtapeEmbed")]
@remove:          $embed//a[has-class("mixtapeImage")]
$embed_link:      $embed/a
@detach:          $embed_link/strong
@before_el(./..): $embed_link/*
@wrap(<cite>):    $embed_link
<blockquote>:     $embed


@remove: //article/header
@remove: //article/footer
@remove: $body//a[contains(@href,"/adServer.bs")]
@remove: $body//p//*[has-class("graf-dropCap")]//img[has-class("graf-dropCapImage")]
@remove: $body//*[has-class("js-postMetaLockup")][.//a[@data-action="show-user-card"]]


@remove: $body//section[1]//hr
@remove: $body//section[has-class("section--first")]//hr
@remove: $body//section[has-class("section--cover")]/following-sibling::*[1]/self::section//hr
@remove: $body//section[has-class("is-backgrounded")]/following-sibling::*[1]/self::section//hr

@before(<hr>): $body//figure[.//img[number(@data-height) < 30][(number(@data-width) > number(@data-height) * 30)]]
@remove









--- 

after

---

	# We apply some additional rules to each Instant View template.
	?exists: $body

	#####   Widgets   #####

	# Most widgets are iFrames that are compatible with the Instant View format and will work out of the box. But some popular widgets do not use iFrames and need a little extra work.

	# We transform such widgets to an Instant-View-compatible format - into an iFrame with an 'src' attribute and use @before to insert them into the page. Then we use the @remove function to delete the unsupported widget (because the essential content from it is now present on the IV page).

	# twitter-tweet
	@before(<iframe>, \
	  src, ".//a[starts-with(@href, \"https://twitter.com/\")][contains(@href, \"/status/\") or contains(@href, \"/statuses/\")]/@href", \
	  class, "twitter-tweet" \
	): $body//blockquote[has-class("twitter-tweet")]
	@remove

	# twitter-video
	@before(<iframe>, \
	  src, ".//a[starts-with(@href, \"https://twitter.com/\")][contains(@href, \"/status/\") or contains(@href, \"/statuses/\")]/@href", \
	  class, "twitter-video" \
	): $body//blockquote[has-class("twitter-video")]
	@remove

	# facebook post
	$fb_post: $body//div[has-class("fb-post")][@data-href]
	@urlencode: $fb_post/@data-href
	@set_attr(data-src, "https://www.facebook.com/plugins/post.php?href=", @data-href, "&show_text=", @data-show-text, "&width=640"): $fb_post
	@before(<iframe>, src, @data-src, class, "fb-post"): $fb_post
	@remove

	# facebook video
	$fb_video: $body//div[has-class("fb-video")][@data-href]
	@urlencode: $fb_video/@data-href
	@set_attr(data-src, "https://www.facebook.com/plugins/video.php?href=", @data-href, "&show_text=", @data-show-text, "&width=640"): $fb_video
	@before(<iframe>, src, @data-src, class, "fb-video"): $fb_video
	@remove

	# aparat
	$aparat_video: $body//script[starts-with(@src, "https://www.aparat.com/embed/")]
	@set_attr(data-hash, @src)
	@match("^https://www.aparat.com/embed/([^?]+)", 1): $@
	@set_attr(data-src, "https://www.aparat.com/video/video/embed/videohash/", @data-hash, "\\/vt/frame"): $aparat_video
	@before(<iframe>, src, @data-src, class, "aparat"): $aparat_video
	@remove

	# instagram
	@before(<iframe>, \
	  src, ".//a[contains(@href, \"instagram.com/p/\")]/@href", \
	  class, "instagram" \
	): $body//blockquote[has-class("instagram-media")]
	@remove

	# github
	<iframe>: $body//script[starts-with(@src,"https://gist.github.com/")]

	# telegram
	$tg_post: $body//*[self::script or self::blockquote][@data-telegram-post]
	@set_attr(data-src, "https://t.me/", @data-telegram-post, "?embed=1"): $tg_post
	@set_attr(data-src, @data-src, "&userpic=", @data-userpic): $tg_post[@data-userpic]
	@set_attr(data-src, @data-src, "&single=1"): $tg_post[@data-single]
	@before(<iframe>, src, @data-src, class, "telegram-post"): $tg_post
	@remove


	#####   Add Anchors   #####
	# Instant View supports anchors. We can add them before each <a> tag that has a name attribute.

	@before(<anchor>, name, @name): $body//a[@name]

	#####   RTL   #####
	# Some magic for better RTL support.

	<bdi>: $body//span[@dir="ltr" or @dir="rtl" or @dir="auto"]

	#####   Unsupported elements   #####

	# We mark content that is not supported in the Instant View format using the @unsupported function. The Instant View bot will not generate an Instant View page if a page features matching elements. This is to make sure that users never get an Instant View page with incomplete information.

	@unsupported: $body//embed
	@unsupported: $body//object
	@unsupported: $body//canvas
	@unsupported: $body//table//table

	# We also mark some popular widgets that are not supported yet but may get supported later.

	# imgur
	@unsupported: $body//blockquote[has-class("imgur-embed-pub")]

	# reddit
	@unsupported: $body//div[has-class("reddit-card")]
	@unsupported: $body//div[has-class("reddit-embed")]

	# playbuzz
	@unsupported: $body//div[has-class("pb_feed")][@data-item]

	# tiktok
	@unsupported: $body//blockquote[has-class("tiktok-embed")]

	#####   Cleanup   #####
	# Remove all html comments, styles and scripts.

	@remove: //comment()
	@remove: //script
	@remove: //style

	#####   Simplifying   #####
	# We use the @simplify function to process target nodes according to the Instant View format. The title, subtitle, kicker and cover already exist as separate elements on the Instant View page, so we no longer need them in the article's body.

	@simplify: $title
	@remove
	@simplify: $subtitle
	@remove
	@simplify: $kicker
	@remove
	@simplify: $cover
	@remove

	# The body element should be an <article> for simplify to work correctly. This is the last step.
	<article>: $body
	@simplify
	body!

	#####   Meta information   #####

	# Even if a page doesn't have an associated Instant View template, we can still get some meta information from it and use it for link previews. So we apply the following rules to all pages:
	?true

	$head: /html/head
	$meta: $head/meta

	# Meta tags may have various formats.
	# We first bring them to the same format <meta name="key" content="value">.
	@set_attr(name, @property): $meta[not(@name) and @property]
	@set_attr(name, @itemprop): $meta[not(@name) and @itemprop]
	@set_attr(content, @value): $meta[not(@content) and @value]

	# An article can have several authors.
	# We combine author names using a little magic.
	@set_attr(name, "author"): $meta[@name="article:author"]

	@append(<div>): $head
	$authors_el:    $@
	$authors:       $meta[@name="author"]

	@append_to($authors_el): $authors
	@remove: $authors[@content = ./following-sibling::meta[@name="author"]/@content]
	$authors: $authors_el/meta[@name="author"]
	@before(", ")
	@remove: ($@)[1]
	@before(@content): $authors
	@remove
	author: $authors_el

	# Basic information can be usualy obtained from Open Graph or Twitter Cards metatags.
	title: $meta[@name="twitter:title"]/@content[normalize-space()]
	title: $meta[@name="og:title"]/@content[normalize-space()]
	title: $head/title[normalize-space()]

	description: $meta[@name="twitter:description"]/@content[normalize-space()]
	description: $meta[@name="og:description"]/@content[normalize-space()]
	description: $body/p[normalize-space()]
	description: $meta[@name="description"]/@content[normalize-space()]

	image_url: $meta[@name="twitter:image"]/@content[normalize-space()]
	image_url: $meta[@name="og:image"]/@content[normalize-space()]

	published_date: $meta[@name="article:published_time"]/@content

	site_name: $meta[@name="og:site_name"]/@content

	channel: $meta[@name="telegram:channel"]/@content

