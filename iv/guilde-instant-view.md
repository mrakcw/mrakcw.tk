# Руководство по Instant View Telegram

## Первый этап

В этом документе описывается формат Instant View Telegram в **stupefying detail**. Если вы впервые слышите о Instant View, пожалуйста, ознакомьтесь с нашим **[Введение](https://instantview.telegram.org/)** и **[Примеры шаблонов](https://instantview.telegram.org/samples/)** прежде чем нырнуть.

Если вас устраивает идея шаблонов Instant View, давайте посмотрим, что ими движет. Начнем с того, что это идея нашего штатного художника о том, как создаются страницы Instant View.:

Оказывается, он не совсем неправ. Вот как на самом деле генерируются страницы Instant View:

![Telegram instant view](https://prod-files-secure.s3.us-west-2.amazonaws.com/74dbe9ad-225f-42cc-96e0-72cbf5cc2133/23d33a48-d67f-4f34-b5bc-187937672028/a582b1940327a15468)

1. Всякий раз, когда Telegram необходимо отобразить предварительный просмотр ссылки для URL-адреса, он также проверяет, **Instant View template** существует для этого домена.
2. Если шаблон существует, наш Instant View Bot получает страницу, используя URL (он обрабатывает только страницы, имеющиеMIME-type `text/html`).
3. Затем бот применяет **rules** из шаблона, определяющего расположение ключевых элементов на исходной странице (с использованием [XPath 1.0](https://www.w3.org/TR/xpath/) выражения) и изменяет содержимое страницы в соответствии с **Instant View format**.
4. Отредактированная страница используется для создания новой **Instant View page** который будет отображаться пользователю.

В этом документе будут рассмотрены:
- [Instant View Формат](https://instantview.telegram.org/docs#instant-view-format),
- [Типы правил](https://instantview.telegram.org/docs#types-of-rules) ваш шаблон может использовать, а также некоторые полезные [XPath конструирует](https://instantview.telegram.org/docs#extended-xpath),
- [Условия](https://instantview.telegram.org/docs#supported-conditions), 
- [Функции](https://instantview.telegram.org/docs#supported-functions) это может помочь вам создавать лучшие шаблоны.

Это руководство предназначено для использования в качестве справочного материала, поэтому вам не обязательно читать его целиком, чтобы начать работу. 
Наш [образeц шаблона](https://instantview.telegram.org/samples) сделать гораздо лучшую точку входа. Вы можете вернуться сюда, если что-то не ясно.

### Недавние изменения

- Добавлена новая [~allowed_origin](https://instantview.telegram.org/docs#allowed-origin-new) опция
- Добавлена новая [@load](https://instantview.telegram.org/docs#load-new) функция
- [@inline](https://instantview.telegram.org/docs#inline) функция теперь поддерживает `silent` параметр, который игнорирует ошибки при загрузке страницы

### March 20, 2019

## Второй этап **Version 2.1**

- Поддержка `srcset` атрибут в **Image** и **Icon** типах. Механизм IV автоматически принимает самое высокое доступное разрешение изображения (но не выше 2560 пикселей).
- [@match](https://instantview.telegram.org/docs#match) функция теперь возвращает только узлы, содержимое которых соответствует регулярному выражению
- [@replace](https://instantview.telegram.org/docs#replace) функция теперь возвращает только узлы, содержимое которых было заменено регулярным выражением
- [@inline](https://instantview.telegram.org/docs#inline) функция больше не следует каноническим ссылкам перенаправления при загрузке страницы

**Также в этом обновлении:**

- Добавлена новая [@split_parent](https://instantview.telegram.org/docs#split-parent) функция

### Декабрь 10, 2018

**Instant View 2.0** расширяет платформу поддержкой языков с письмом справа налево, новыми блоками страниц, полезными функциями и многим другим. Мы рекомендуем использовать последнюю версию в ваших шаблонах. Для этого добавьте следующее правило **at the beginning** шаблона:

```
~version: "2.0"
```

Ниже приведен список изменений в версии 2.0:

Новые [свойства](https://instantview.telegram.org/docs#properties):

- kicker
- site_name

Новые [типы](https://instantview.telegram.org/docs#supported-types):

- Map
- Table
- Details
- RelatedArticles
- Marked
- Subscript
- Superscript
- Icon
- PhoneLink
- Reference

Новые [типы правил](https://instantview.telegram.org/docs#types-of-rules):

- [Параметры](https://instantview.telegram.org/docs#options)
- [Функции блока](https://instantview.telegram.org/docs#block-functions)

Новые [функции](https://instantview.telegram.org/docs#supported-functions):

- [wrap_inner](https://instantview.telegram.org/docs#wrap-inner)
- [style_to_attrs](https://instantview.telegram.org/docs#style-to-attrs)

Другие функции и улучшения:

- Xpath результаты запроса можно добавлять в переменные, используя `+` ([узнать больше](https://instantview.telegram.org/docs#variables))
- Новая функция XPath [ends-with](https://instantview.telegram.org/docs#ends-with)
- Списки могут содержать блочные элементы, такие как абзацы, вложенные списки, таблицы и т. д.
- Поддержка указания авторства в заголовках мультимедиа (тег `<cite>`)
- Поддержка ссылок на изображения (атрибут `href` для типа изображения)
- Неподдерживаемые элементы (например, изображение внутри цитаты) теперь генерируют ошибку, а не перемещаются вверх.
- Улучшена функция @simplify для лучшей обработки RichText (например, сохраняет разрывы строк, образованные элементами блока).

## Формат мгновенного просмотра

Страница Instant View — это объект со следующими **[properties](https://instantview.telegram.org/docs#properties)**:

| Параметр | Тип | Описание |
| --- | --- | --- |
| title Required | RichText | Заголовок страницы |
| subtitle | RichText | Подзаголовок страницы |
| kicker | RichText | Кикер |
| author | String | Имя автора |
| author_url | Url | Ссылка автора |
| published_date | Unixtime | Дата публикации |
| description | String | Краткое описание (используется при предварительном просмотре ссылки) |
| image_url | Url | Фотография предварительного просмотра ссылки (используется при предварительном просмотре ссылки) |
| document_url | Url | Документ предварительного просмотра ссылки (используется при предварительном просмотре ссылки) |
| site_name | String | Название веб-сайта (используется при предварительном просмотре ссылки) |
| channel | String | Имя пользователя автора статьи (или исходного веб-сайта) https://telegram.org/blog/channels в Telegram в формате @username |
| cover | Media (Image/Video/Embed/Map) | Обложка страницы |
| body Required | Article | Содержимое страницы |

### RTL поддержка

Платформа поддерживает языки с письмом справа налево. Если тег `<html>` или тег, на который ссылается свойство body, имеет атрибут `dir="rtl"`, страница будет помечена как RTL. Если этот атрибут не установлен, вы можете настроить его вручную для отображения страницы в Instant View как RTL, используя следующее правило:

```
  @set_attr(dir, "rtl"): $body   # если тело уже определено
  @set_attr(dir, "rtl"): /html   # альтернативный способ
```

## Поддерживаемые типы

Объект страницы IV может содержать следующие **типы**:

| Тип | Разрешенные вложенности | Описание | HTML дубликат |
| --- | --- | --- | --- |
| Article | Header Subheader Paragraph Preformatted Divider Anchor List Blockquote Pullquote Media Image Video Audio Embed Slideshow Table Details Footer RelatedArticles | Содержимое страницы | <article> |
| Header | RichText | Основной заголовок | Мы используем верхний уровень заголовков <h1> – <h4>, найденных на исходной странице. |
| Subheader | RichText | Второстепенный заголовок | Остальные заголовки мы используем <h1> – <h4>, а также заголовки <h5> – <h6>. |
| Paragraph | RichText | Параграф | <p> |
| Preformatted | RichText | Предварительно отформатированный текст | <pre> с необязательным атрибутом data-language https://instantview.telegram.org/docs#note-on-code-languages |
| Anchor | – | Якорь | <anchor> с именем атрибута, который содержит имя привязки |
| Divider | – | Сепаратор | <hr> |
| List | ListItem | Список | <ul> для маркированного списка, <ol> для нумерованного списка |
| ListItem | version 1.0: RichText version 2.0: Header Subheader Paragraph Preformatted Divider Anchor List Blockquote Pullquote Media Image Video Audio Embed Slideshow Table Details | Элемент списка | <li> |
| Blockquote | RichText QuoteCaption | Блочная цитата | <blockquote> |
| Pullquote | RichText QuoteCaption | Цитата | <aside> |
| QuoteCaption | RichText | Подпись к цитате | <cite> |
| Media | Image Video Audio Embed Map MediaCaption | Медиа-контент | <figure> |
| Image | – | An image | <img> с атрибутом src и необязательным атрибутом href, чтобы сделать изображение кликабельным. Разрешенные форматы: GIF, JPG, PNG (GIF будет преобразован в тип видео с помощью IV) По состоянию на https://instantview.telegram.org/docs#march-20-2019, поддерживает атрибут srcset (srcset имеет более высокий приоритет, чем src, как и в браузере; IV получает самое высокое доступное разрешение в 2560px). |
| Video | – | Видео | <video> с атрибутом src или содержащим тег <source type="video/mp4"> с атрибутом src |
| Audio | – | Аудио | <audio> с атрибутом src, либо содержащий тег <source> с атрибутом src и типом атрибута (возможные типы: audio/ogg, audio/mpeg, audio/mp4) |
| Embed | – | Встроенный элемент | <iframe> с атрибутом src. https://instantview.telegram.org/docs#embedded-elements |
| Map | – | Карта | <img> or <iframe> с атрибутом src, ссылающимся на карты Google или Яндекс. |
| Slideshow | Media (Image/Video) Image Video MediaCaption | Слайд-шоу | <slideshow> |
| MediaCaption | RichText | Медиа-заголовок | <figcaption> Начиная с IV 2.0, может содержать дополнительный тег <cite> с указанием авторства (автор, создатель или источник носителя). |
| Table | TableCaption TableRow | Таблица | <table> |
| TableCaption | RichText | Заголовок таблицы | <caption> |
| TableRow | TableCell | Строка таблицы | <tr> с необязательными атрибутами align, valign, может быть заключен в <thead> или <tbody> или <tfoot> с необязательными атрибутами align, valign |
| TableCell | RichText | Ячейка таблицы | <td> для стандартной ячейки, <th> для ячейки заголовка, с дополнительными атрибутами align, valign, colspan, rowspan |
| Details  (version 2.0+) | DetailsHeader Header Subheader Paragraph Preformatted Divider Anchor List Blockquote Pullquote Media Image Video Audio Embed Slideshow Table Details | Складной блок | <details> с необязательным атрибутом open, который будет открыт по умолчанию |
| DetailsHeader  (version 2.0+) | RichText | Видимый заголовок складного блока | <summary> |
| RelatedArticles | Header Link | Статьи по Теме | <related> содержащий один из тегов <h1> – <h6> в качестве заголовка блока и тегов <a> с атрибутом href, содержащим URL-адрес соответствующей статьи. Примечание. В этом блоке на странице IV будут отображаться только статьи, имеющие IV. |
| Footer | RichText | Нижний колонтитул | <footer> |
| RichText | Bold Italic Underline Strike Fixed Marked Subscript Superscript Icon Link EmailLink PhoneLink LineBreak Anchor Reference String | Форматированный текст | Текстовые элементы и поддерживаемые теги |
| Bold | RichText | Жирный текст | <b> or <strong> |
| Italic | RichText | Курсивный текст | <i> or <em> |
| Underline | RichText | Подчеркнутый текст | <u> or <ins> |
| Strike | RichText | Зачеркнутый текст | <s> or <del> or <strike> |
| Fixed | RichText | Моноширинный текст | <code> or <kbd> or <samp> or <tt> |
| Marked | RichText | Выделенный текст | <mark> |
| Subscript | RichText | Подстрочный текст | <sub> |
| Superscript | RichText | Надстрочный текст | <sup> |
| Icon | – | Небольшое изображение внутри текста | <pic> с атрибутом src, width, height. Может содержать необязательный атрибут, если изображение не важно и его можно игнорировать. Разрешенные форматы: JPG, PNG.По состоянию на https://instantview.telegram.org/docs#march-20-2019, поддерживает атрибут srcset (srcset имеет более высокий приоритет, чем src, как и в браузере). |
| Link | RichText | Ссылка | <a> с атрибутом href, содержащим URL-адрес |
| EmailLink | RichText | Ссылка на адрес электронной почты | <a> с атрибутом href, содержащим mailto: link |
| PhoneLink | RichText | Ссылка на номер телефона | <a> с атрибутом href, содержащим tel: link |
| Reference | RichText | Ссылка | <reference> с именем атрибута, содержащим ссылочное имя |
| LineBreak | – | Разрыв строки | <br> |

## Примечание о языках кода

Приложения Telegram в настоящее время не поддерживают подсветку кода, но в будущем она будет поддерживаться. По этой причине желательно включать атрибут языка кода (`data-language`) для больших блоков `<pre>`, если он указан в исходном коде.

## Типы правил

Правила Instant View — это инструкции, которые сообщают нашему боту IV, где найти метаэлементы на исходной странице и как ему следует изменить содержимое страницы при создании страницы Instant View.

Каждая новая строка в шаблоне описывает новое правило. Когда бот преобразует страницу в формат Instant View, он применяет правила из соответствующего шаблона одно за другим и игнорирует любые пустые строки. Вы можете оставлять комментарии, начиная строку с `#`, весь следующий текст в этой строке будет игнорироваться, если он не заключен в кавычки. Вы можете использовать символ `\` для переноса правила на следующую строку, например:

```
# Комментарий
# You can break up \
  a long rule into \
  multiple strings
```

Большинство правил основано на [XPath 1.0](https://www.w3.org/TR/xpath/) выражения, используемые для поиска необходимых узлов на исходной странице.

Блок правил может иметь имя. В этом случае его можно повторно использовать в других правилах.

Мы поддерживаем следующие типы правил:

## Условия

Условия предлагают неограниченную гибкость для ваших шаблонов. Правила этого типа начинаются с символов `?` или `!` и имеют следующий формат:

```
?condition:  xpath_query   # пример условия
!condition:  regexp        # параметр справа зависит от типа условия
?condition                 # некоторые условия не имеют параметров
```

Группы условий, которые следуют друг за другом, интерпретируются как блок. `?`-правила следуют логике ИЛИ при объединении, а `!`-правила следуют логике И. Это означает, что для того, чтобы бот применил каждый блок, должны быть выполнены все `!`-условия в блоке и хотя бы одно из `?`-условий внутри него. Это также означает, что каждый блок должен иметь хотя бы одно `?`-условие.

Блоки условий разбивают набор правил на группы. Группы правил, не содержащие каких-либо условий, применяются всегда. Все остальные группы применяются только при соблюдении их условий.

**Примеры**

```
# Если здесь размещено правило, оно будет применено.
# То же самое с тем, что здесь

?false               # Это условие всегда ложно
# Помещенное здесь правило никогда не будет применено, поскольку условие не выполнено.
# Действительно очень полезно

?exists: //article   # Это условие истинно, если на странице есть тег статьи.
# На этих строках располагается новая группа правил, и она будет применяться, если
# на странице есть тег статьи, несмотря на условие ?false выше

?exists: //article
?exists: //div[@id="article"]
!exists: //meta[@property="og:title"]
# Правила ниже этого блока будут применяться, если тег <article> или
# <div id="article"> можно найти, этот тег также должен присутствовать: <meta property="og:title">
```

> [Check out the Supported Conditions to see what works out of the box »](https://instantview.telegram.org/docs#supported-conditions)

## Характеристики

Свойства — это строительные блоки вашей IV-страницы. Проверить [Instant View Формат](https://instantview.telegram.org/docs#instant-view-format) для списка свойств, которые можно использовать при создании IV-страниц (вы также можете определить пользовательские свойства, но они не будут использоваться нигде на результирующей IV-странице). Используйте этот формат для заполнения свойств:

```
property: xpath_query
property: "Some string"
property: null
```

В свойствах хранится первый узел, соответствующий выражению XPath `xpath_query`. По умолчанию, если свойство уже имеет значение, оно **not** будет перезаписано. Вы можете изменить это поведение, добавив `!` к имени свойства — в этом случае можно присвоить новое непустое значение. Если вы добавите `!!` к имени свойства, свойству можно присвоить даже пустое новое значение.

Вы также можете присвоить свойству строку вместо xpath_query. В этом случае свойство будет содержать текстовый элемент с указанным текстом. Также можно присвоить `null`, чтобы отбросить значение свойства (работает только с `!!`).

**Примеры**

```
title:   //article//h1      # Ищет 'title' в статье
title:   //h1               # Если не найден, проверка вообще на наличие заголовков
title:   //head/title       # Если тоже не повезет, попробуйте тег <title>
?path: /blog/.*             # На страницах в /blog/ раздел
title!:  //div[@id="title"] # мы предпочитаем использовать <div id="title">, если имеется
?path: /news/.*             # На страницах в /news/ раздел
title!!: //h3               # заголовок всегда находится в теге h3

author:   //article/@author  # Получить имя автора из атрибута автора
?exists:  //article[has-class("anonymous")]
author!!: null               # Не отображать автора сообщений
```


> **Напоминание.** Свойства *title* и *body* необходимы для создания IV-страницы. 

## Переменные

Переменные полезны для хранения узлов и управления ими перед назначением их [свойствам](https://instantview.telegram.org/docs#properties) для последней IV страницы. Имена переменных начинаются с символа `$`. Используйте этот формат для их инициализации:

```
$variable: xpath_query
$variable: "Немного текста"
$variable: null
```

Переменные хранят список узлов, соответствующих выражению Xpath `xpath_query`. Если переменная присваивается второй раз, ее предыдущее значение перезаписывается. Вы можете изменить это поведение, добавив `?` к имени переменной. Вы также можете добавить список узлов к предыдущему, добавив «+» к имени переменной.

Вы также можете присвоить переменной `string` вместо xpath_query. В этом случае переменная будет содержать список с одним текстовым элементом, содержащим указанный текст. Также возможно присвоить `null`, чтобы отбросить значение переменной.

**Примеры**

```
$images:  //img
$images:  //img[@src]     # предыдущее значение будет перезаписано
$images?: //article//img  # новое значение будет присвоено только в том случае, если переменная пуста
$medias:  //img
$medias+: //video         # теперь $medias содержит все теги img и video
```

## Параметры

Параметры влияют на то, как страница обрабатывается ботом. Опции начинаются с символа `~`. Используйте этот формат, чтобы установить их:

```
~option: "value"
~option: true
```

Параметры можно задать со значениями в формате JSON.

**Примеры**

```
~version: "2.1"
```

> Ознакомьтесь с [Поддерживаемые параметрами](https://instantview.telegram.org/docs#supported-options) чтобы посмотреть, что еще работает «из коробки».

## Функции

Функции чрезвычайно гибки, но вы, вероятно, в основном будете использовать их для удаления ненужных узлов со страницы и замены одних элементов другими. Имена функций начинаются с символа `@`, вы можете использовать следующий формат:

```
@function:                 xpath_query   # функция без параметров
@function(param):          xpath_query   # дополнительные параметры помещаются в круглые скобки
@function(p1 p2):          xpath_query   # функция с двумя параметрами
@function(p1, "param #2"): xpath_query   # параметры можно разделять запятыми
                                         # и при необходимости заключайте в кавычки
@function:                 "Some text"   # при необходимости используйте строку вместо xpath_query
```

Основным аргументом функции является список узлов, соответствующих выражению Xpath `xpath_query`. Вы также можете использовать `string` в качестве основного аргумента вместо `xpath_query`. В этом случае основным аргументом, передаваемым в функцию, будет список с одним текстовым элементом, содержащим указанный текст.

**Примеры**

```
@remove:            //header               # удаляет все теги <header>, найденные на странице
@replace_tag(<h1>): //div[@class="header"] # заменяет все теги <div class="header"> на <h1>
<h1>:               //div[@class="header"] # псевдоним для @replace_tag(<h1>)
```

**Примечание:** Вы можете найти функцию [@debug](https://instantview.telegram.org/docs#debug) особенно полезно при создании выражений XPath.

> Ознакомьтесь с [Поддерживаемые функции](https://instantview.telegram.org/docs#supported-functions) чтобы посмотреть, что еще работает «из коробки».

## Block Functions

Блочные функции управляют блоками правил. Имена функций блоков также начинаются с символа `@`, вы можете использовать следующий формат:

```
@function(xpath_query) {   # открывающая скобка должна находиться в конце строки
  $variable: xpath_query   # здесь еще несколько правил
  @function: $@            # внутри функции блока
}                          # закрывающая скобка должна быть на отдельной строке
```

Функция блока может содержать другую функцию блока, поэтому их можно вкладывать. Функция блока не может содержать [условия](https://instantview.telegram.org/docs#conditions).

> Обратите внимание, что блочные функции, которые выполняют блок правил несколько раз **(map**, **repeat**, **while**, **while_not)** иметь ограничение на общее количество итераций для всего шаблона.

**Примеры**

```
@if( //article ) {    # если <article> существует
  @append(<p>): $$    # добавить в него абзац
}
```

> Ознакомьтесь с [Поддерживаемые функции блока](https://instantview.telegram.org/docs#supported-block-functions) чтобы посмотреть, что еще работает «из коробки».


## Включать

> **Примечание:** Это сервисное правило, оно **will not work** в ваших шаблонах. Он включен в этот справочник, чтобы дать вам лучшее понимание того, как работает система. Вы можете увидеть пример работы этого правила в разделе [Страницы обработки](https://instantview.telegram.org/docs#processing-pages).

Правила этого типа начинаются с символа `+` и имеют следующий формат:

```
+ rules
```

Это правило вставляет блок правил с указанным именем. В большинстве случаев имя соответствует домену, к которому применяются правила.

**Примеры**

```
+ core.telegram.org # вставка блока правил, который используется для core.telegram.org
?not_exists: $body
+ telegram.org      # вставка блока правил, который используется для telegram.org
```

## Специальные переменные `$$` и `$@`

Специальные переменные работают внутри группы правил.

- Переменная `$$` всегда содержит результат самого последнего запроса XPath.
- Переменная `$@` содержит результат последней запущенной функции.

Эти переменные пригодятся при создании цепочек правил. Если в правиле отсутствует xpath_query, оператор считается равным `$$`.

**Примеры**

```
# Поместите изображение в тег <figure>, затем установите его в качестве обложки.
@wrap(<figure>): //img[@id="cover"]
cover:           $@

# Вставьте разделитель перед каждым div.divider, который больше не требуется.
@before(<hr>):   //div[has-class("divider")]
@remove          # это то же самое, что @remove: $$
```

## Extended XPath

Для вашего удобства вы можете использовать следующие конструкции в выражениях XPath.

## Context

Обычные запросы XPath ищут узлы в контексте всего документа. Чтобы указать контекст для конкретного выражения, вы можете использовать переменные в формате `$context`. Если соответствующая переменная существует, запрос будет выполнен относительно каждого узла переменной. Если это не так и соответствующее свойство существует, запрос будет выполнен относительно узла свойства. Если соответствующих переменных или свойств не существует, запрос возвращает пустой список.

**Примеры**

```
$headers:      //h1              # все теги <h1> на странице
article:       //article         # первый тег <article> на странице
$art_headers:  $article//h1      # все теги <h1> внутри статьи
$header_links: $art_headers//a   # все теги <a> внутри каждого узла $art_headers
```

## Нацеливание на узлы

Результатом запроса XPath всегда является список совпадающих узлов. Если вы хотите сузить список до одного узла, вы можете использовать следующий синтаксис: `(xpath_query)[n]`, где `n` — номер искомого узла. Нумерация начинается с 1, вы можете получить последний узел, используя `last()` функцию. Этот синтаксис можно применить только ко всему выражению.

**Примеры**

```
$headers:    //h1                    # все теги <h1> на странице
$header2:    (//h1)[2]               # второй тег <h1> на странице
$header2:    ($headers)[2]           # такой же
$last_link:  ($header2//a)[last()]   # последняя ссылка внутри $header2
```

### has-class

You will often need to locate nodes that have a certain class. To do this, you can use the `has-class("class")` function that serves as an alias for the expression `contains(concat(" ", normalize-space(@class), " "), " class ")`.

**Examples**

```
# Transform all div.header elements into h1
<h1>: //div[contains(concat(" ", normalize-space(@class), " "), " header ")]
# same idea, but much shorter
<h1>: //div[has-class("header")]
```

### ends-with

XPath has a `starts-with` function that checks whether the first string starts with the second string but does not have `ends-with`. In IV you can use the `ends-with("haystack","needle")` function that serves as an alias for the expression `(substring("haystack", string-length("haystack") - string-length("needle") + 1) = "needle")`.

**Examples**

```
# Select all JPG images
@debug: //img[(substring(@src, string-length(@src) - string-length(".jpg") + 1) = ".jpg")]
# the same, but much shorter
@debug: //img[ends-with(@src, ".jpg")]
```

### prev-sibling

An axis that selects the previous sibling of the current node. Serves as an alias for the expression `preceding-sibling::*[1]/self`.

**Examples**

```
# Transform all div nodes, that immediately follow img nodes into figcaption
<figcaption>: //div[./preceding-sibling::*[1]/self::img]
# the same
<figcaption>: //div[./prev-sibling::img]
```

### next-sibling

An axis that selects the next sibling of the current node. Serves as an alias for the expression `following-sibling::*[1]/self`

**Examples**

```
# Join paragraphs following each other into one, separating them with a line break
@combine(<br>): //p/following-sibling::*[1]/self::p
# the same, but shorter
@combine(<br>): //p/next-sibling::p
```

### Supported conditions

Below are conditions supported in Instant View rules.

### domain

```
domain: regexp
```

Checks whether the domain of the current page matches a regular expression. The check is case-insensitive, so the actual expression used will look like this: `/^regexp$/i`.

**Examples**

```
# Rule for *.subdomain.example.com URLs
?domain: .+\.subdomain\.example\.com
```

### domain_not

```
domain_not: regexp
```

Checks whether the domain of the current page does not match a regular expression. The check is case-insensitive, so the actual expression used will look like this: `/^regexp$/i`.

**Examples**

```
# Rule for *.subdomain.example.com URLs with an exception for dev.subdomain.example.com
?domain:     .+\.subdomain\.example\.com
!domain_not: dev\.subdomain\.example\.com
```

### path

```
path: regexp
```

Checks whether the path to the current page matches a regular expression. The check is case-insensitive, so the actual expression used will look like this: `/^regexp$/i`.

**Examples**

```
# Rule for example.com/news/* links
?path: /news/.+   # no need to escape the slash
```

### path_not

```
path_not: regexp
```

Checks whether the path to the current page does not match a regular expression. The check is case-insensitive, so the actual expression used will look like this: `/^regexp$/i`.

**Examples**

```
# Rule for example.com/news/* links with an exception for example.com/news/recent
?path:     /news/.+
!path_not: /news/recent
```

### exists

```
exists: xpath_query
```

Checks whether target nodes exist on the current page.

**Examples**

```
# Apply rule only to pages with an <article> tag
?exists: //article
```

### not_exists

```
not_exists: xpath_query
```

Checks whether target nodes do not exist on the current page.

**Examples**

```
# Apply rule only to pages with an <article> tag, that do not include any quotes.
?exists:     //article
!not_exists: //article//blockquote
```

### true

```
true
```

A condition that is always true.

**Examples**

```
?path:    /blog/.+
# Rules for the blog section go here
?true
# Rules that go here will be applied to all pages
```

### false

```
false
```

A condition that is always false.

**Examples**

```
?path:    /news/.+
# Rules for the news section go here
?false
# Rules that go here will never be applied
```

## Поддерживаемые параметры

Ниже приведены параметры, поддерживаемые правилами Instant View.

### version

```
~version: "2.1"
```

Устанавливает версию IV, используемую в шаблоне. Поведение некоторых функций IV может меняться в зависимости от предоставленной версии (см. руководство по соответствующей функции).

Значение должно быть одним из «1.0», «2.0» или «2.1». Мы рекомендуем использовать последнюю версию **2.1**.

**Примеры**

```
~version: "2.1"
# Теперь вы можете использовать новые возможности IV 2.1.
```

> Обратите внимание, что `version` должен быть установлен в начале шаблона перед любыми другими правилами.

### allowed_origin **NEW**

```
~allowed_origin: "https://example.com"
~allowed_origin: ["https://example.com", "https://subdomain.example.com"]
```

Устанавливает источник (или список источников), из которого контент может быть загружен с помощью [@load](https://instantview.telegram.org/docs#load-new) и [@inline](https://instantview .telegram.org/docs#inline) функции.

Значение должно быть *Строка* или *Массив строк*.

**Examples**

```
~allowed_origin: "https://api.example.com"
# Now you can load content from https://api.example.com regardless of the origin of current page
@load: "https://api.example.com/get/article"
```

## Supported functions

The general format for a function is the following:

```
@function: xpath_query
```

Each function accepts the list of nodes returned by the XPath statement as the main parameter. You may omit `xpath_query`, in which case `$$`, the result of the previous XPath query, will be passed into the function. The function then processes each element in the list and returns a list of transformed nodes which are stored in the `$@` variable.

Below is a list of functions that are supported in Instant View rules.

### debug

```
@debug: xpath_query
```

Logs the elements passed into the function, these elements will be displayed in the status line at the bottom of the screen in the [Instant View Editor](https://instantview.telegram.org/#instant-view-editor). Returns the elements passed. Consider combining with `[$$` and `$@`](https://instantview.telegram.org/docs#special-variables-and).

**Example**

```
@debug: //article//a     # displays all links from the page in the log
@debug                   # displays all links again
```

Look for debug output at the bottom of your Editor:

[Debug console at the bottom of the screen](https://instantview.telegram.org/file/811140378/19ec/lgDhyQ_HKcc.120673/c7602c526f316dc5a4)

https://prod-files-secure.s3.us-west-2.amazonaws.com/74dbe9ad-225f-42cc-96e0-72cbf5cc2133/7cc87b65-a1a1-446d-a17b-b02d179eed3d/c7602c526f316dc5a4

### remove

```
@remove: xpath_query
```

Removes target nodes from the page, returns an empty list.

**Examples**

```
@remove: //div[has-class("related")] # remove article div.related
@debug                               # empty list
```

### append

```
@append("Some text"): xpath_query
@append(@attr):       xpath_query
@append(<tag>):       xpath_query
@append(<tag>, attr, value[, attr, value[, ...]]): xpath_query
```

Inserts content, specified by the parameter, to the end of each target node.

- If the first parameter is in angle brackets, a new `<tag>` node will be created. The following two parameters then specify the name and value of an attribute for that node. If `value` has the format `@attr`, the value of the attribute `attr` in the target node will be used as the value. `value` can be an XPath expression that begins with `.`, in this case the query will be executed relative to the relevant node, and the text value of the returned elements will be used as the attribute's value. Otherwise, `value` will be used as the attribute's value.
- If the first parameter has the format `@attr`, a new text element with value of the attribute `attr` of the targeted node will be created.
- Otherwise, a text element with the text specified in the first parameter will be created.

Returns a list of the newly created nodes.

**Examples**

```
# <div class="a"><em>1</em></div>
@append(<p>): //div
# <div class="a"><em>1</em><p></p></div>
```

### prepend

```
@prepend("Some text"): xpath_query
@prepend(@attr):       xpath_query
@prepend(<tag>):       xpath_query
@prepend(<tag>, attr, value[, attr, value[, ...]]): xpath_query
```

Inserts content, specified by the parameter, to the beginning of each target node.

- If the first parameter is in angle brackets, a new `<tag>` node will be created. The following two parameters then specify the name and value of an attribute for that node. If `value` has the format `@attr`, the value of the attribute `attr` in the target node will be used as the value. `value` can be an XPath expression that begins with `.`, in this case the query will be executed relative to the relevant node, and the text value of the returned elements will be used as the attribute's value. Otherwise, `value` will be used as the attribute's value.
- If the first parameter has the format `@attr`, a new text element with value of the attribute `attr` of the targeted node will be created.
- Otherwise, a text element with the text specified in the first parameter will be created.

Returns a list of the newly created nodes.

**Examples**

```
# <div class="a"><em>1</em></div>
@prepend(<p>, data-class, @class): //div
# <div class="a"><p data-class="a"></p><em>1</em></div>
```

### after

```
@after("Some text"): xpath_query
@after(@attr):       xpath_query
@after(<tag>):       xpath_query
@after(<tag>, attr, value[, attr, value[, ...]]): xpath_query
```

Inserts content, specified by the parameter, after each target node.

- If the first parameter is in angle brackets, a new `<tag>` node will be created. The following two parameters then specify the name and value of an attribute for that node. If `value` has the format `@attr`, the value of the attribute `attr` in the target node will be used as the value. `value` can be an XPath expression that begins with `.`, in this case the query will be executed relative to the relevant node, and the text value of the returned elements will be used as the attribute's value. Otherwise, `value` will be used as the attribute's value.
- If the first parameter has the format `@attr`, a new text element with value of the attribute `attr` of the targeted node will be created.
- Otherwise, a text element with the text specified in the first parameter will be created.

Returns a list of the newly created nodes.

**Examples**

```
# <div class="a"><em>1</em><em>2</em></div>
@after("!"): //div/em
# <div class="a"><em>1</em>!<em>2</em>!</div>
```

### before

```
@before("Some text"): xpath_query
@before(@attr):       xpath_query
@before(<tag>):       xpath_query
@before(<tag>, attr, value[, attr, value[, ...]]): xpath_query
```

Inserts content, specified by the parameter, before each target node.

- If the first parameter is in angle brackets, a new `<tag>` node will be created. The following two parameters then specify the name and value of an attribute for that node. If `value` has the format `@attr`, the value of the attribute `attr` in the target node will be used as the value. `value` can be an XPath expression that begins with `.`, in this case the query will be executed relative to the relevant node, and the text value of the returned elements will be used as the attribute's value. Otherwise, `value` will be used as the attribute's value.
- If the first parameter has the format `@attr`, a new text element with value of the attribute `attr` of the targeted node will be created.
- Otherwise, a text element with the text specified in the first parameter will be created.

Returns a list of the newly created nodes.

**Examples**

```
# <div class="a"><em>1</em></div>
@before("@"): //div/em
# <div class="a">@<em>1</em></div>
```

### append_to

```
@append_to(xpath_query): xpath_query
@append_to($var):        xpath_query
```

Inserts each target node to the end of the base node.

The first parameter specifies the base node. You can pass an XPath expression that begins with `.`, in this case the query will be executed relative to the relevant node. The *first relevant node* will be used as the base node. You can also pass a variable name. If such a variable exists, the *first relevant node* will be used as the base node. If the variable doesn't exist or is empty, the property with a matching name will be used as the base node.

Returns a list of target nodes.

**Examples**

```
$div:             //div
# <div class="a"><em></em></div><p>Text</p>
@append_to($div): //p
# <div class="a"><em></em><p>Text</p></div>
```

### prepend_to

```
@prepend_to(xpath_query): xpath_query
@prepend_to($var):        xpath_query
```

Inserts each target node to the beginning of the base node.

The first parameter specifies the base node. You can pass an XPath expression that begins with `.`, in this case the query will be executed relative to the relevant node. The *first relevant node* will be used as the base node. You can also pass a variable name. If such a variable exists, the *first relevant node* will be used as the base node. If the variable doesn't exist or is empty, the property with a matching name will be used as the base node.

Returns a list of target nodes.

**Examples**

```
$div:              //div
# <div class="a"><em></em></div><p>Text</p>
@prepend_to($div): //p
# <div class="a"><p>Text</p><em></em></div>
```

### after_el

```
@after_el(xpath_query): xpath_query
@after_el($var):        xpath_query
```

Inserts each target node after the base node.

The first parameter specifies the base node. You can pass an XPath expression that begins with `.`, in this case the query will be executed relative to the relevant node. The *first relevant node* will be used as the base node. You can also pass a variable name. If such a variable exists, the *first relevant node* will be used as the base node. If the variable doesn't exist or is empty, the property with a matching name will be used as the base node.

Returns a list of target nodes.

**Examples**

```
$div:                        //div
# <div class="a"><p>Text</p><em></em></div>
@after_el("./../self::div"): //p
# <div class="a"><em></em></div><p>Text</p>
```

### before_el

```
@before_el(xpath_query): xpath_query
@before_el($var):        xpath_query
```

Inserts each target node before the base node.

The first parameter specifies the base node. You can pass an XPath expression that begins with `.`, in this case the query will be executed relative to the relevant node. The *first relevant node* will be used as the base node. You can also pass a variable name. If such a variable exists, the *first relevant node* will be used as the base node. If the variable doesn't exist or is empty, the property with a matching name will be used as the base node.

Returns a list of target nodes.

**Examples**

```
$div:                        //div
# <div class="a"><p>Text</p><em></em></div>
@before_el("./../self::div"): //p
# <p>Text</p><div class="a"><em></em></div>
```

### replace_tag

```
@replace_tag(<tag>):     xpath_query
<tag>:                   xpath_query
```

Changes the name of the tag. The new name for the tag should be passed as the first parameter. Returns target nodes.

**Examples**

```
# <div class="list unordered"><div class="item"></div><div class="item"></div></div>
@replace_tag(<li>): //div[has-class("item")]
<ul>:               //div[has-class("list")]
# <ul class="list unordered"><li class="item"></li><li class="item"></li></ul>
```

### wrap

```
@wrap(<tag>): xpath_query
```

Wrap each target node in the `<tag>` tag. Returns a list of the new `<tag>` elements.

**Examples**

```
# <em>1</em><em>2</em>
@wrap(<b>): //em
# <b><em>1</em></b><b><em>2</em></b>
@wrap(<u>)
# <b><u><em>1</em></u></b><b><u><em>2</em></u></b>
@wrap(<p>): $@
# <b><p><u><em>1</em></u></p></b><b><p><u><em>2</em></u></p></b>
```

### wrap_inner

```
@wrap_inner(<tag>): xpath_query
```

Wrap an HTML structure around the content of each target node in the `<tag>` tag. Returns a list of new `<tag>` elements.

**Examples**

```
# <p>H<sub>2</sub>0</p>
@wrap_inner(<b>): //p
# <p><b>H<sub>2</sub>0</b></p>
```

### clone

```
@clone: xpath_query
```

Creates a copy of each target node. Returns a list of the newly created nodes.

**Examples**

```
# <p class="text">Paragraph</p>
@clone: //p
# <p class="text">Paragraph</p><p class="text">Paragraph</p>
```

### detach

```
@detach: xpath_query
```

Separates the target node from the rest in the parent tag. Creates a copy of the parent tag and wraps the target node into this new instance. Returns a list of parent tags that contain target nodes.

**Examples**

```
# <a href="#1">
#   <b>1</b>
#   <p>Link #1</p>
# </a>
# <a href="#2">
#   <b>2</b>
#   <p>Link #2</p>
# </a>

@detach: //a/b

# <a href="#1">
#   <b>1</b>
# </a>
# <a href="#1">
#   <p>Link #1</p>
# </a>
# <a href="#2">
#   <b>2</b>
# </a>
# <a href="#2">
#   <p>Link #2</p>
# </a>

@after(<br>): $@

# <a href="#1">
#   <b>1</b>
# </a><br>
# <a href="#1">
#   <p>Link #1</p>
# </a>
# <a href="#2">
#   <b>2</b>
# </a><br>
# <a href="#2">
#   <p>Link #2</p>
# </a>
```

### split_parent

```
@split_parent: xpath_query
```

Splits the parent tag by the target node. Places preceding siblings wrapped into parent element before the target node. Places following siblings wrapped into parent element placed after target node.

Returns a list of target nodes.

**Examples**

```
# <p class="text">
#   <b>First</b> line
#   <figure><img src="photo.jpg"></figure>
#   <i>Second</i> line
# </p>
@split_parent: //p/figure
# <p class="text">
#   <b>First</b> line
# </p>
# <figure><img src="photo.jpg"></figure>
# <p class="text">
#   <i>Second</i> line
# </p>
```

### pre

```
@pre: xpath_query
```

Specifies that the text inside the target node is already formatted. Returns a list of matching nodes.

**Examples**

```
# <p>     Some          text  , </p>
# <p>  Some  another      text  </p>
@pre: (//p)[1]
# Result in the Instant View page:
#      Some          text  ,
# Some another text
```

### set_attr

```
@set_attr(attr, value):       xpath_query
@set_attr(attr, @attr):       xpath_query
@set_attr(attr, "Some text"): xpath_query
@set_attr(attr, "Some text", @from-attr, ...): xpath_query
```

Sets an attribute in each matching node. The name of the attribute is passed in the first parameter. The value of the attribute is the rest of the parameters concatenated.

If `value` has the format `@attr`, the value of the attribute `attr` of the target node will be used as the value. `value` can be an XPath expression that begins with `.`, in this case the query will be executed relative to the relevant node, and the text value of the returned elements will be used as the attribute's value. Otherwise, `value` will be used as the attribute's value.

Returns a list of attribute nodes.

**Examples**

```
# <p class="a"></p>
@set_attr(data-class, @class)
# <p class="a" data-class="a"></p>
@set_attr(id, @class, "_", ./@data-class)
# <p class="a" data-class="a" id="a_a"></p>
```

### set_attrs

```
@set_attrs(attr, value): xpath_query
@set_attrs(attr, value[, attr, value[, ...]]): xpath_query
```

Sets multiple attributes for each of the target nodes. Each two parameters specify the name and value for the new attributes.

If `value` has the format `@attr`, the value of the attribute `attr` of the target node will be used as the value. `value` can be an XPath expression that begins with `.`, in this case the query will be executed relative to the relevant node, and the text value of the returned elements will be used as the attribute's value. Otherwise, `value` will be used as the attribute's value.

Returns a list of the matching nodes.

**Examples**

```
# <p class="a"></p>
@set_attrs(data-class, @class, id, "a_a"): //p
# <p class="a" data-class="a" id="a_a"></p>
```

### match

```
@match(regexp):                         xpath_query
@match(regexp, match_index):            xpath_query
@match(regexp, match_index, modifiers): xpath_query
```

Performs a search based on a regular expression. Replaces the content of the target node with the search result. The second parameter specifies the number of the captured parenthesized subpattern. If this is not specified, the text that matched the full pattern will be used. You can specify modifiers for the regular expression using the optional parameter ([ims modifiers](http://pcre.org/pcre.txt) are supported).

Returns a list of target nodes. As of IV **2.1**, returns a list of target nodes the content of which matched the regular expression.

**Examples**

```
# <p class="plainText">Hello, world!</p>
@match("[a-z]+!"): //p
# <p class="plainText">world!</p>
@match("([a-z]+)!", 1): //p/text()
# <p class="plainText">world</p>
@match("..t", 0, "i"): //p/@class
# <p class="inT">Bye, world!</p>

# <ul><li>a1</li><li>a</li><li>21b</li><li>.</li></ul>
@match("[0-9]+"): //ul/li
# <ul><li>1</li><li></li><li>21</li><li></li></ul>
@debug: $@
# Debug 2 nodes:
#   [0]: <li>1</li>
#   [1]: <li>21</li>
```

### replace

```
@replace(regexp, replacement):            xpath_query
@replace(regexp, replacement, modifiers): xpath_query
```

Performs a search and replace operation using the regular expression. You can specify modifiers for the regular expression using the optional parameter ([ims modifiers](http://pcre.org/pcre.txt) are supported).

Returns a list of target nodes. As of IV **2.1**, returns a list of target nodes the content of which was replaced by the regular expression.

**Examples**

```
# <p class="text">Hello, world!</p>
@replace("Hello", "Goodbye"): //p/text()
# <p class="text">Goodbye, world!</p>
@replace(".t$", "mp"): //p/@class
# <p class="temp">Goodbye, world!</p>
@replace("goodb", "B", "i"): //p/text()
# <p class="temp">Bye, world!</p>

# <ul><li>a1</li><li>a</li><li>21b</li><li>.</li></ul>
@replace("[0-9]+", "($0)"): //ul/li
# <ul><li>a(1)</li><li>a</li><li>(21)b</li><li>.</li></ul>
@debug: $@
# Debug 2 nodes:
#   [0]: <li>a(1)</li>
#   [1]: <li>(21)b</li>
```

### urlencode

```
@urlencode: xpath_query
```

Encodes the content of the target node as per RFC 3986. Returns target nodes.

**Examples**

```
# <a href="https://telegram.org">link</a>
$a: //a
@urlencode: $a/@href
# <a href="https%3A%2F%2Ftelegram.org">link</a>
@set_attr(href, "/?url=", @href): $a
# <a href="/?url=https%3A%2F%2Ftelegram.org">link</a>
```

### urldecode

```
@urldecode: xpath_query
```

Decodes the content of the target node as per RFC 3986. Returns target nodes.

**Examples**

```
# <a href="/?url=https%3A%2F%2Ftelegram.org">link</a>
$a: //a
@match("^/\?url=(.+)$", 1): $a/@href
# <a href="https%3A%2F%2Ftelegram.org">link</a>
@urldecode
# <a href="https://telegram.org">link</a>
```

### htmlencode

```
@htmlencode: xpath_query
```

Convert special characters in the target node to HTML entities. Returns the target nodes.

**Examples**

```
# <p>&lt;b&gt;Some text&lt;\b&gt;</p>
@htmlencode: //p
# <p>&amp;lt;b&amp;gt;Some text&amp;lt;\b&amp;gt;</p>
```

### htmldecode

```
@htmldecode: xpath_query
```

Convert special HTML entities in the target node back to characters. Returns the target nodes.

**Examples**

```
# <p>&amp;lt;b&amp;gt;Some text&amp;lt;\b&amp;gt;</p>
@htmldecode: //p
# <p>&lt;b&gt;Some text&lt;\b&gt;</p>
```

### style_to_attrs

```
@style_to_attrs(css_prop, attr): xpath_query
@style_to_attrs(css_prop, attr[, css_prop, attr[, ...]]): xpath_query
```

Gets a value of a CSS property from the style attribute and sets it into an attribute for each of the target nodes. Each two parameters specify the name for the new attribute and the name of CSS property. Returns a list of matching nodes.

**Examples**

```
# <img style="width: 20px; height: 20px">
@style_to_attrs(width, data-width, height, data-height): //img
# <img style="width: 20px; height: 20px" data-width="20" data-height="20">
```

### background_to_image

```
@background_to_image: xpath_query
```

Transforms the target node with a background picture into an `<img>` tag with an `src` attribute. The target node must contain a `style` attribute with the background. Returns a list of transformed nodes.

**Examples**

```
# <div class="bg_image" style="background-image:url(image.jpg)"></div>
@background_to_image: //div[has-class("bg_image")]
# <img src="image.jpg">
```

### json_to_xml

```
@json_to_xml: xpath_query
```

Transforms the content of the target node to the XML format. The contents must be in valid JSON format. The resulting XML tree will be inserted into the document. Returns the root element of the XML tree.

**Examples**

```
# <div data-var='{"a":1,"b":[1,2],"c":{"p":"Hello!"}}'></div>
@json_to_xml: //div/@data-var
@debug: $@
# <xml><a>1</a><b><item>1</item><item>2</item></b><c><p>Hello!</p></c></xml>
```

### html_to_dom

```
@html_to_dom: xpath_query
```

Parse the content of the target node in HTML format and insert it into the document. Returns the root element of the inserted HTML tree.

**Examples**

```
# <div data-content="&lt;b&gt;Some text&lt;\b&gt;"></div>
@html_to_dom: //div/@data-content
@debug: $@
# <dom><b>Some text</b></dom>
```

### combine

```
@combine:                        xpath_query
@combine(" - "):                 xpath_query
@combine(<tag>):                 xpath_query
@combine(<tag>[, " - "[, ...]]): xpath_query
```

Combines each target node with its preceding node if such a node exists. You can use parameters to specify nodes to be inserted above the target node. If the parameter is in angular brackets, a new `<tag>` node will be created. Otherwise a text element with the text specified by the parameter will be used.

Returns a list of nodes, preceding the target nodes.

**Examples**

```
# <pre>1 2 3</pre>
# <pre> 4 5 </pre>
# <pre>6 7 8</pre>
@combine:             //pre/following-sibling::*[1]/self::pre
# <pre>1 2 3 4 5 6 7 8</pre>

# <p>first</p>
# <p>second</p>
@combine(<br>, <br>): //p/following-sibling::*[1]/self::p
# <p>first<br><br>second</p>

# <h1>Title</h1>
# <strong>Subtitle</strong>
@combine(<br>, "\n"): //h1/following-sibling::*[1]/self::strong
# <h1>Title<br>
# Subtitle</h1>
```

### datetime

```
@datetime(-2): xpath_query
@datetime(-2, "en-US", "yyyy-MM-dd"): xpath_query
```

Transforms the date and time from the text of the target node into a unixtime timestamp. The parameter specifies the timezone of the original date and time.

You can also pass a locale and a pattern to parse the date and time in more complicated cases. If a non-gregorian calendar is used, this needs to be specified in the locale. For example, `"fa-IR@calendar=persian"`. Available patterns are documented [here](http://userguide.icu-project.org/formatparse/datetime#TOC-Date-Time-Format-Syntax).

On success, returns a text element with the unixtime timestamp. Otherwise, returns the target element.

**Examples**

```
# <div id="date">1 January 2017, 15:04</div>
@datetime(-2):  //div[@id="date"]
published_date: $@   # the property published_date will hold a unixtime timestamp

# <time>دوشنبه, ۲۳ اسفند ۱۳۹۵</time>
@datetime(0, "fa-IR@calendar=persian", "EEEE, dd MMMM yyyy"): //time
published_date: $@   # the property published_date will hold a unixtime timestamp
```

### simplify

> 
> 
> 
> **Note:** This is a service function. There is no reason for you to use it in your templates. It was included in this reference to give you a better understanding of how the system works (see the `..after` block).
> 

```
@simplify: xpath_query
```

Processes the target nodes according to the Instant View format. Returns the target nodes.

**Examples**

```
# <p><span><b>S</b>ome</span> <em>important</em> text!</p>
@simplify: //p
# <p><b>S</b>ome <em>important</em> text!</p>
```

### inline

```
@inline:         xpath_query
@inline(silent): xpath_query
```

If the target element is an `<iframe>` with the attribute `src`, the content of the iframe will be loaded. The target element will be replaced with the content of the iframe.

If the target node is an HTML-comment, a `<comment>` element with the content of the comment will be created. The comment will be replaced by the newly created node.

You can use `silent` parameter to ignore errors while processing this function. You will still see an error in the debug console, but the rules that come after will continue to execute.

Returns a list of replaced nodes.

> 
> 
> 
> Note: The @inline function adheres to the [same-origin policy](https://en.wikipedia.org/wiki/Same-origin_policy). This means that the target and the inlined pages should have the same origin. You can specify additional allowed origins using the option [~allowed_origin](https://instantview.telegram.org/docs#allowed-origin-new)
> 

**Examples**

```
# <p><iframe src="/subpage.html"></iframe></p>
# /subpage.html:
#   <html><body><p>Hello!</p></body></html>
@inline: //p/iframe
# <p><html><body><p>Hello!</p></body></html></p>
@debug
# <html><body><p>Hello!</p></body></html>

# <p><!--<b>Hello!</b>, world!--></p>
@inline: //p/comment()
# <p><comment><b>Hello!</b>, world!</comment></p>
@debug
# <comment><b>Hello!</b>, world!</comment>
```

### load **NEW**

```
@load:         "https://example.com"
@load(silent): xpath_query
```

Loads the content of the URL. The URL can be specified as a string or be the content of the target node.

You can use `silent` parameter to ignore errors while processing this function. You will still see an error in the debug console, but the rules that come after will continue to execute.

Returns the newly created node with loaded content.

> 
> 
> 
> Note: The @load function adheres to the [same-origin policy](https://en.wikipedia.org/wiki/Same-origin_policy). This means that the target and the loaded pages should have the same origin. You can specify additional allowed origins using the option [~allowed_origin](https://instantview.telegram.org/docs#allowed-origin-new)
> 

**Examples**

```
@append(<iframe> src "/subpage.html"): /html/body
$iframe: $@
@remove
@inline: $iframe
# the same, but much shorter
@load: "/subpage.html"

@load(silent): //meta[@property="api:url"]/@content
# trying to load content from api:url
@if ( $@ ) {
  # Do something with content
}
```

### unsupported

```
@unsupported: xpath_query
```

Specifies that the target node contains essential content that can't be represented in the Instant View format. Returns the target nodes.

**Examples**

```
# <p>See the graph below:</p>
# <canvas class="article_graph" width="600" height="400"></canvas>
@unsupported: //canvas[has-class("article_graph")]
```

> 
> 
> 
> **Tip:** It is important to identify that a page contains essential unsupported content — no IV page will be generated to ensure that the user never gets incomplete info from an article. The system will take care of the most popular cases in the `..after` block, but your template must cover everything that the system doesn't catch.
> 

### Supported block functions

The general format for a block function is the following:

```
@function(xpath_query) {
  # block of rules
}
```

Block functions usually accept the list of nodes returned by the XPath statement as a parameter. Depending on the function and its parameters, rules inside the block can be run several times or never. Block functions can contain any type of rules except conditions.

Below is a list of block functions that are supported in Instant View rules.

### if

```
@if(xpath_query) {
  # block of rules
}
```

Executes the block of rules if target nodes exist on the current page. If target nodes do not exist, the block of rules will be skipped. The `$$` and `$@` variables contain target nodes found by the XPath query.

**Example**

```
@if( //div[has-class("blockquote")] ) { # if div.blockquote exists
  <blockquote>: $@                      #   change tag name to blockquote
  <cite>: $@//div[has-class("author")]  #   and mark div.author as a caption
}
```

### if_not

```
@if_not(xpath_query) {
  # block of rules
}
```

Executes a block of rules if target nodes do not exist on the current page. If such nodes exist, the block of rules will be skipped. The `$$` and `$@` variables contain target nodes found by the XPath query.

**Example**

```
@if_not( $body//footer ) {     # if footer does not exist
  @append(<footer>): $body     #   append it into body
}
```

### map

```
@map(xpath_query) {
  # block of rules
}
```

Executes a block of rules once per each target node found by the XPath query. At the beginning of each iteration, the following variables will be set:

- `$$` will contain the target nodes found by XPath query;
- `$@` will contain the current target node;
- `$index` will contain the index of the current target node (starts with 1);
- `$first` will contain `<true>` if the current target node is the first in the list and an empty list otherwise;
- `$middle` will contain `<true>` if the current target node is between the first and the last in the list, empty list otherwise;
- `$last` would contain `<true>` if the current target node is the last one in the list, empty list otherwise.

**Example**

```
@map( //div[@id="list"]/p ) {   # for each paragraph in list
  $p: $@                        #   store the current paragraph in $p variable
  @prepend_to($p): ". "         #   and
  @prepend_to($p): $index       #   number them starting with 1
}
```

> 
> 
> 
> Please note that the `@map` function should be used **only** if it is impossible to use **regular functions** with xpath queries (for example you need to use $index). Otherwise, regular functions are faster because they process several nodes at once.
> 

**Example**

```
# Bad way:
@map( //div[has-class("image")] ) {
  $image: $@
  <cite>:       $image//p/span
  <figcaption>: $image//p
  <figure>:     $image
}
# The same result, but faster:
$image:       //div[has-class("image")]
<cite>:       $image//p/span
<figcaption>: $image//p
<figure>:     $image
```

### repeat

```
@repeat(n) {
  # block of rules
}
```

Executes a block of rules **n** times. At the beginning of each iteration, the following variables will be set:

- `$$` and `$@` will contain the previous value of `$$`;
- `$index` will contain the index of the current iteration (starts with 1);
- `$first` will contain `<true>` if this is the first iteration, an empty list otherwise;
- `$middle` will contain `<true>` if the current iteration is between the first and last, empty list otherwise;
- `$last` will contain `<true>` if this is the last iteration, empty list otherwise.

**Example**

```
# appends "1, 2, 3, 4, 5" to $body
@repeat( 5 ) {
  @append_to($body): $index
  @if_not( $last ) {
    @append_to($body): ", "
  }
}
```

### while

```
@while(xpath_query) {
  # block of rules
}
```

Executes a block of rules while target nodes exist on the current page. At the beginning of each iteration the following variables will be set:

- `$$` and `$@` will contain target nodes found by XPath query;
- `$index` will contain the index of the current iteration (starts with 1);
- `$first` will contain `<true>` if it is the first iteration, an empty list otherwise.

**Example**

```
@while( //iframe ) {
  @inline: $@          # inline all nested iframes
}
```

### while_not

```
@while_not(xpath_query) {
  # block of rules
}
```

Executes a block of rules while target nodes do not exist on the current page. At the beginning of each iteration the following variables will be set:

- `$$` and `$@` will contain target nodes found by XPath query;
- `$index` will contain the index of the current iteration (starts with 1);
- `$first` will contain `<true>` if this is the first iteration, an empty list otherwise.

**Example**

```
@while_not( //video ) {   # if no video exists on the current page
  @inline: //iframe       #   try to inline iframes, maybe video is inside the frame
}
```

### Embedded elements

Instant View supports widgets from popular services. We display them as embedded elements or specially formatted quotes. The Instant View bot analyzes all iframes in the document body and generates a widget if the service is supported.

> 
> 
> 
> Some popular widgets do not use iframes. Note that the `..after` block of rules contains rules to transform such widgets to an Instant View compatible format.
> 

We currently support the following services:

- Youtube
- Vimeo
- Tweets & Twitter Videos
- Facebook Posts & Videos
- Instagram
- Giphy
- SoundCloud
- GithubGist
- Aparat
- VK.com Videos

### Processing pages

All pages are processed according to the following rules:

```
# Url: http://example.com/some_page.html
+ example.com
?true
+ ..after
```

If a page is on a third-level domain and above, the following rules are applied:

```
# Url: http://some.subdomain.example.com/some_page.html
+ some.subdomain.example.com
?not_exists: $body
+ subdomain.example.com
?not_exists: $body
+ example.com
?true
+ ..after
```

In other words, at first the bot attempts to use a block of rules that features the full domain name. If such a block does not exist, the bot checks for the next level up to the second-level domain.

`..after` is a special block of rules that is applied to all pages irrespective of the domain name.

### Working with subdomains

If the page is on a third-level domain or higher, you need to manually select the domain level to which your template will apply. Use this menu in the top left corner of the page:

Choose a level that hosts pages with the same structure, so that your rules are relevant to all the matching pages on this domain.

> 
> 
> 
> For example, use `wikipedia.org` to create an IV page for `https://en.wikipedia.org/wiki/Domain_name` because the page structure doesn't depend on language in Wikipedia.
>