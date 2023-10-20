# Part #1
	# В этом образце шаблона показано, как мы можем превратить сообщение Medium слева в страницу мгновенного просмотра Telegram, как показано справа, за несколько простых шагов. Если вы не уверены, что делают некоторые из используемых здесь элементов, ознакомьтесь с полной документацией здесь.: https://instantview.telegram.org/docs

	# Поместите версию в начало шаблона. Мы рекомендуем всегда использовать последнюю доступную версию Instant View.
	~version: "2.0"

	### ШАГ 1. Определите, какие страницы поддерживают мгновенный просмотр, а какие нет.

	# Мы хотим создавать только страницы мгновенного просмотра для статей. Другие вещи, такие как списки статей, профили, раздел «О программе» и т. д., следует игнорировать.
	# Удобно, что на всех страницах статей на Medium есть <meta property="article:published_time"> tag.
	# Если этот тег отсутствует, мы будем считать, что страница не является статьей и не требует страницы мгновенного просмотра.
	# Этот *condition* делает свое дело:
	?exists: /html/head/meta[@property="article:published_time"]

	### ШАГ 2: Определите основные элементы

	# Свойства body и title необходимы для работы страницы Instant View.
	# «Субтитры» не являются обязательными для страниц IV, но сообщения Medium могут иметь субтитры, поэтому важно отразить это в нашем шаблоне.
	body:     //article
	title:    $body//h1[1]
	subtitle: $title/next-sibling::h2

	### Теперь мы установим изображение обложки. Это также не обязательно, но оно нам нужно, если мы хотим, чтобы наша страница мгновенного просмотра выглядела круто.

	# Некоторые сообщения Medium имеют обложку на фоне заголовка, мы можем ее использовать.
	# Во-первых, давайте назначим фоновый узел *переменной* '$bg_section' для последующего использования.
	$bg_section: $body//section[has-class("is-imageBackgrounded")]

	# Вызовите функцию @background_to_image *, чтобы преобразовать фоновое изображение в <img>
	@background_to_image: $bg_section//div[has-class("section-backgroundImage")]

	# Замените тег //div на <figure> tags.
	<figure>: $bg_section//div[has-class("section-background")]

	# Установите цифру в качестве значения «обложки» *property*.
	cover: $bg_section//figure

	# Если мы не нашли изображение обложки, проверьте несколько других блоков, которые могут его содержать.
	# Обратите внимание, что «cover:» — это *property*, поэтому по умолчанию оно не будет перезаписано после первого присвоения значения. Это означает, что каждое из следующих правил будет применяться только в том случае, если у нас еще нет покрытия.
	cover: $title/preceding::figure[.//img][not(has-class("is-partialWidth"))]
	cover: $title/next-sibling::figure[.//img]
	cover: $subtitle/next-sibling::figure[.//img]
	cover: //figure[has-class("graf--layoutFillWidth")]

	### Теперь нужно найти изображение для превью ссылок. Ссылки, опубликованные через Telegram, будут показывать расширенный предварительный просмотр с небольшой картинкой в ​​чате. Давайте попробуем найти что-нибудь к этому изображению.

	# Если у нас уже есть обложка, мы будем использовать ее и для изображения предварительного просмотра ссылки.
	image_url: $cover/self::img/@src
	image_url: $cover/self::figure//img/@src

	# Если обложку не нашли, то возьмем картинку по метатегам.
	# В противном случае предварительный просмотр ссылки будет содержать только текст, что тоже нормально.
	image_url: //head/meta[@property="og:image"]/@content

	### ШАГ 3: Дальнейшие улучшения

	# Pullquote в Medium содержит тег «strong», но это не должно делать всю цитату жирной.
	<span>:  //blockquote[has-class("graf--pullquote")]//strong
	<aside>: //blockquote[has-class("graf--pullquote")]

	# Теперь мы встроим встраивания, заключенные в дополнительные фреймы, с помощью'@inline' *function*.
	@inline: $body//iframe[starts-with(@src, "/media/")]

	# Medium отображает предварительно отформатированные блоки/цитаты вместе, если они находятся рядом друг с другом. Мы объединим их, используя два разрыва в качестве разделителя.
	@combine(<br>,<br>): $body//pre/next-sibling::pre
	@combine(<br>,<br>): $body//blockquote/next-sibling::blockquote

	# Статьи Medium могут содержать встроенные превью ссылок, мы будем показывать такие превью в виде цитирования.
	$embed:           $body//div[has-class("graf--mixtapeEmbed")]
	@remove:          $embed//a[has-class("mixtapeImage")]
	$embed_link:      $embed/a
	@detach:          $embed_link/strong
	@before_el(./..): $embed_link/*
	@wrap(<cite>):    $embed_link
	<blockquote>:     $embed

	## ШАГ 4: Очистка

	# Удалите лишние узлы, на IV странице они нам не нужны.
	@remove: //article/header
	@remove: //article/footer
	@remove: $body//a[contains(@href,"/adServer.bs")]
	@remove: $body//p//*[has-class("graf-dropCap")]//img[has-class("graf-dropCapImage")]
	@remove: $body//*[has-class("js-postMetaLockup")][.//a[@data-action="show-user-card"]]

	# Удалите первый разделитель, он все равно невидим на страницах среднего размера.
	@remove: $body//section[1]//hr
	@remove: $body//section[has-class("section--first")]//hr
	@remove: $body//section[has-class("section--cover")]/following-sibling::*[1]/self::section//hr
	@remove: $body//section[has-class("is-backgrounded")]/following-sibling::*[1]/self::section//hr

	# На некоторых страницах в качестве разделителя используются тонкие изображения. Telegram не очень любит тонкие изображения, поэтому заменим их простым разделителем.
	@before(<hr>): $body//figure[.//img[number(@data-height) < 30][(number(@data-width) > number(@data-height) * 30)]]
	@remove

	### И все, мы закончили. Теперь правила из блока «..after» будут применены, и страница мгновенного просмотра готова. Не стесняйтесь нажать на панель ниже, чтобы увидеть, что именно делается в разделе «..после».

# Part #2

	# Мы применяем некоторые дополнительные правила к каждому шаблону Instant View.
	?exists: $body

	#####   Виджеты   #####

	# Большинство виджетов представляют собой iFrame, совместимые с форматом Instant View и готовые к работе без дополнительной настройки. Но некоторые популярные виджеты не используют iFrames и требуют небольшой доработки.
		
	# Мы преобразуем такие виджеты в формат, совместимый с Instant-View — в iFrame с атрибутом src и используем @before для вставки их на страницу. Затем мы используем функцию @remove, чтобы удалить неподдерживаемый виджет (поскольку необходимый контент из него теперь присутствует на странице IV).
		
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
		
	#####   Добавить якоря   #####
	# Instant View поддерживает привязки. Мы можем добавить их перед каждым тегом <a>, имеющим атрибут имени.
		
	@before(<anchor>, name, @name): $body//a[@name]
		
	#####   RTL   #####
	# Немного магии для лучшей поддержки RTL.
		
	<bdi>: $body//span[@dir="ltr" or @dir="rtl" or @dir="auto"]
		
	#####  Неподдерживаемые элементы   #####
		
	# Мы отмечаем контент, который не поддерживается в формате Instant View, с помощью функции @unsupported. Бот Instant View не будет создавать страницу Instant View, если на странице есть совпадающие элементы. Это сделано для того, чтобы пользователи никогда не получали страницу мгновенного просмотра с неполной информацией.
		
	@unsupported: $body//embed
	@unsupported: $body//object
	@unsupported: $body//canvas
	@unsupported: $body//table//table
		
	# Мы также отмечаем некоторые популярные виджеты, которые еще не поддерживаются, но могут получить поддержку позже.
		
	# imgur
	@unsupported: $body//blockquote[has-class("imgur-embed-pub")]
		
	# reddit
	@unsupported: $body//div[has-class("reddit-card")]
	@unsupported: $body//div[has-class("reddit-embed")]
		
	# playbuzz
	@unsupported: $body//div[has-class("pb_feed")][@data-item]
		
	# tiktok
	@unsupported: $body//blockquote[has-class("tiktok-embed")]
		
	#####   Очистка   #####
	# Удалите все комментарии, стили и скрипты HTML.
		
	@remove: //comment()
	@remove: //script
	@remove: //style
		
	#####   Упрощение   #####
	# Мы используем функцию @simplify для обработки целевых узлов в соответствии с форматом Instant View. Заголовок, подзаголовок, кикер и обложка уже существуют как отдельные элементы на странице Instant View, поэтому они нам больше не нужны в теле статьи.
		
	@simplify: $title
	@remove
	@simplify: $subtitle
	@remove
	@simplify: $kicker
	@remove
	@simplify: $cover
	@remove
		
	# Элементом body должен быть <article>, чтобы упрощение работало корректно. Это последний шаг.
	<article>: $body
	@simplify
	body!
		
	#####   Мета информация   #####
		
	# Даже если страница не имеет связанного шаблона Instant View, мы все равно можем получить от нее некоторую метаинформацию и использовать ее для предварительного просмотра ссылок. Поэтому мы применяем следующие правила ко всем страницам:
	?true
		
	$head: /html/head
	$meta: $head/meta
		
	# Мета-теги могут иметь различные форматы..
	# Сначала приводим их к одному формату <meta name="key" content="value">.
	@set_attr(name, @property): $meta[not(@name) and @property]
	@set_attr(name, @itemprop): $meta[not(@name) and @itemprop]
	@set_attr(content, @value): $meta[not(@content) and @value]
		
	# У статьи может быть несколько авторов.
	# Объединяем имена авторов, используя немного магии.
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
		
	# Базовую информацию обычно можно получить из метатегов Open Graph и Twitter Cards.
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