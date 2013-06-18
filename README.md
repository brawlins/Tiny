# Tiny

***

## Overview

Tiny is a light-weight templating class for outputting HTML for your view layer.

Most MVC frameworks and templating engines I've seen are a bit overkill for smaller projects and teams. They tend to be bloated and sometimes have a learning curve because they introduce new syntax. 

When I designed Tiny I wanted to create something that was small (a single class) and easy to use, but still powerful enough to use as a templating system. At minimum, it had to meet the following requirements:

1. Use standard PHP code in templates (no new syntax to learn)
2. Support nesting
3. Support conditionals
4. Handle looping
5. Handle name collisions

## Nesting

Tiny uses a simple nested template structure based on php includes. The object keeps track of a single template, which is the outermost or parent template. This is usually the HTML skeleton or page structure. However, templates can be nested inside each other as many levels deep as you need. The nested or child templates are usually HTML fragments that are included at the specified place in their parent template file.

## Usage

Here's a very simple example of how to use this class:

### 1. Create the object

```php	
$Tiny = new Tiny();	
```

### 2. Set paths to supporting files

Tiny automatically finds the files you call, so you only have to provide the filename, not the full path. However, to expedite the search, you can tell it where to look by defining paths to typical supporting files (relative to the document root). If no paths are defined, the object will search the entire document root recursively. 

```php
$Tiny->setPath('html', '/path/to/html-templates/');
$Tiny->setPath('image', '/path/to/images/');
$Tiny->setPath('css', '/path/to/css-files/');
$Tiny->setPath('js', '/path/to/javascripts/');
```

N.B.: The caveat here is that your template filenames must be unique. Tiny returns the first matching file it finds, so if you have two templates by the same name, it may not load the one you want. 

### 3. Set the outer template

Only the filename is required. See #2. 

```php
$Tiny->setHtml('hello-template.php');
```

hello-template.php:

```php
// contents of the template file
<h4>Hello. My name is <i><?=$name?></i>.</h4>
```


### 4. Set variables

Set all the variables you'll need in your template (including any nested templates).

```php
// $name = 'Bob'
$Tiny->setVar('name', 'Bob');
```

### 5. Display it

```php
$Tiny->render();
```

This grabs your template file, passes it all the variables you defined, and echoes the resulting html like this:

<h4>Hello. My name is <i>Bob</i>.</h4>


## Too simple. Right?

It becomes more useful when you start nesting templates, peforming conditional logic, and looping through child templates. 

### Nesting and looping

outer-template.php

```php
// include a nested template into this one
<?php $this->includeHtml('nested-template.php'); ?>

// or you can assign the template name to a variable
<?php $this->includeHtml($sidebar_template); ?>	

// perform conditional logic and loops
<?php if (!empty($products)): ?>
	<?php foreach($products as $product): ?>
		<div class="product">
			<?php $this->includeHtml('product-template.php'); ?>
		</div>
	<?php endforeach; ?>
<?php endif; ?>
```
	
### Setting HTML \<head\> data

Tiny has a method called <code>displayHead()</code> that assembles all the page assets and meta data and echoes out everything that goes in the <code>\<head\></code> section of your page. Meta tags can be set by passing an array of key/value pairs representing the attributes. Here are some examples:

```php
$Tiny->setHead('title', 'My Page');
$Tiny->setHead('js', 'local.js'); 
$Tiny->setHead('js', 'http://example.com/js/remote.js'); 
$Tiny->setHead('css', 'local.css'); 
$Tiny->setHead('css', 'http://example.com/css/remote.css'); 
$Tiny->setHead('css', array('media' => 'screen', 'href' => 'screen.css')); 
$Tiny->setHead('link', array('rel' => 'shortcut icon', 'type' => 'image/ico', 'href' => 'site.ico')); 
$Tiny->setHead('meta', array('name' => 'charset', 'content' => 'utf-8')); 
$Tiny->setHead('meta', array('name' => 'description', 'content' => 'This is my description')); 
$Tiny->setHead('meta', array('name' => 'http-equiv', 'content' => array('attribute' => 'value')));
$Tiny->setHead('custom', '<!--[if lte IE 9]>conditional comments here<![endif]-->');
```

In your template file you'd display it like this:

```php
<head>
	<?php $this->displayHead(); ?>
</head>
```

Which will render this:

```html
<head>
	<title>My Page</title>
	<meta charset="utf-8">
	<meta name="description" content="This is my description">
	<meta http-equiv="attribute" content="value">
	<link rel="stylesheet" href="local.css">
	<link rel="stylesheet" href="http://example.com/css/remote.css">
	<link rel="stylesheet" media="screen" href="screen.css">
	<script src="local.js"></script>
	<script src="http://example.com/js/remote.js"></script>
	<!--[if lte IE 9]>conditional comments here<![endif]-->
</head>
```
