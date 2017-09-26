# Writing Module Documentation <a id="module-documentation"></a>

![Markdown](img/markdown.png)

Icinga Web 2 is capable of viewing your module's documentation, if the documentation is written in
[Markdown](http://en.wikipedia.org/wiki/Markdown). Please refer to
[Markdown Syntax Documentation](http://daringfireball.net/projects/markdown/syntax) for Markdown's formatting syntax.

## Where to Put Module Documentation? <a id="module-documentation-location"></a>

By default, your module's Markdown documentation files must be placed in the `doc` directory beneath your module's root
directory, e.g.:

```
example-module/doc
```

## Chapters <a id="module-documentation-chapters"></a>

Each Markdown documentation file represents a chapter of your module's documentation. The first found heading inside
each file is the chapter's title. The order of chapters is based on the case insensitive "Natural Order" of your files'
names. <dfn>Natural Order</dfn> means that the file names are ordered in the way which seems natural to humans.
It is best practice to prefix Markdown documentation file names with numbers to ensure that they appear in the correct
order, e.g.:

```
01-About.md
02-Installation.md
03-Configuration.md
```

## Table Of Contents <a id="module-documentation-toc"></a>

The table of contents for your module's documentation is auto-generated based on all found headings inside each
Markdown documentation file.

## Linking Between Headings <a id="module-documentation-linking"></a>

For linking between headings, place an anchor **after the text** where you want to link to, e.g.:

```
# Heading <a id="heading"></a> Heading
```

Please note that anchors have to be unique across all your Markdown documentation files.

Now you can reference the anchor either in the same or **in another** Markdown documentation file, e.g.:

```
This is a link to [Heading](#heading).
```

Other tools support linking between headings by giving the filename plus the anchor to link to, e.g.:

```
This is a link to [About/Heading](01-About.md#heading)
```

This syntax is also supported in Icinga Web 2.

## Including Images <a id="module-documentation-images"></a>

Images must placed in the `doc` directory beneath your module's root directory, e.g.:

```
/path/to/icingaweb2/modules/example-module/doc/img/example.png
```

Note that the `img` sub directory is not mandatory but good for organizing your directory structure.

Module images can be accessed using the following URL:

```
{baseURL}/doc/module/{moduleName}/image/{image} e.g. icingaweb2/doc/module/example-module/image/img/example.png
```

Markdown's image syntax is very similar to Markdown's link syntax, but prefixed with an exclamation mark, e.g.:

```
![Alt text](http://path/to/img.png "Optional Title")
```

URLs to images inside your Markdown documentation files must be specified without the base URL, e.g.:

```
![Example](img/example.png)
```
