# REST API blocks #
**Contributors:** spacedmonkey

**Donate link:** https://github.com/sponsors/spacedmonkey

**Tags:** blocks, gutenberg

**Requires at least:** 5.8

**Tested up to:** 5.9

**Requires PHP:** 7.0.0

**Stable tag:** 0.5.0

**License:** GPLv3 or later

**License URI:** https://www.gnu.org/licenses/gpl-3.0.en.html

Add gutenberg blocks data into post / page / widget REST API endpoints.

## Description ##

[![Build Status](https://travis-ci.com/spacedmonkey/wp-rest-blocks.svg?branch=master)](https://travis-ci.com/spacedmonkey/wp-rest-blocks)

A simple plugin to add block data in json format into the rest api. Once installed, there will be two new fields added to the rest api, `has_blocks` and `blocks`.
For example output.
```
"has_blocks": true,
"blocks": [
  {
	"blockName": "core/image",
	"attrs": {
	  "url": "https://www.spacedmonkey.com/wp-content/uploads/2018/12/test-image.jpg",
	  "alt": "Terminal de aeropuerto",
	  "caption": "fsfsdfdsfdssfd",
	  "href": "https://www.spacedmonkey.com/test-image",
	  "rel": "noreferrer noopener",
	  "linkClass": "jonny-123",
	  "linkTarget": "_blank",
	  "id": 147355,
	  "width": 582,
	  "height": 327,
	  "linkDestination": "attachment"
	},
	"innerBlocks": [

	],
	"innerHTML": "\n<figure class=\"wp-block-image is-resized\"><a class=\"jonny-123\" href=\"https://www.spacedmonkey.com/test-image\" target=\"_blank\" rel=\"noreferrer noopener\"><img src=\"https://www.spacedmonkey.com/wp-content/uploads/2018/12/test-image.jpg\" alt=\"Terminal de aeropuerto\" class=\"wp-image-147355\" width=\"582\" height=\"327\"/></a><figcaption>fsfsdfdsfdssfd</figcaption></figure>\n",
	"innerContent": [
	  "\n<figure class=\"wp-block-image is-resized\"><a class=\"jonny-123\" href=\"https://www.spacedmonkey.com/test-image\" target=\"_blank\" rel=\"noreferrer noopener\"><img src=\"https://www.spacedmonkey.com/wp-content/uploads/2018/12/test-image.jpg\" alt=\"Terminal de aeropuerto\" class=\"wp-image-147355\" width=\"582\" height=\"327\"/></a><figcaption>fsfsdfdsfdssfd</figcaption></figure>\n"
	],
	"rendered": "\n<figure class=\"wp-block-image is-resized\"><a class=\"jonny-123\" href=\"https://www.spacedmonkey.com/test-image\" target=\"_blank\" rel=\"noreferrer noopener\"><img src=\"https://www.spacedmonkey.com/wp-content/uploads/2018/12/test-image.jpg\" alt=\"Terminal de aeropuerto\" class=\"wp-image-147355\" width=\"582\" height=\"327\"/></a><figcaption>fsfsdfdsfdssfd</figcaption></figure>\n"
  }
],
```

## Installation ##

Installation requires you to check the project out in plugin directory and do a `composer install`.

## Changelog ##

### 0.5.0 ###
* Add support for new post types added in WordPress 5.9.

### 0.4.0 ###
* Added support for block based widget, added in WordPress 5.8. Block data is added to the /wp/v2/widgets endpoint.

### 0.3.2 ###
* Update translations

### 0.3.1 ###
* Hot fix.

### 0.3.0 ###

* Improve support for block that have attributes that use query source type.
* Improve error handling for those that install this plugin without using composer.

### 0.2.1 ###
* Update dependency.

### 0.2.0 ###
* Breaking change. Field names have changed and required WordPress 5.5+

### 0.1.0 ###
* First version.
