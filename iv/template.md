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