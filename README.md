# REST API blocks #
**Contributors:** spacedmonkey  
**Donate link:** https://example.com/  
**Tags:** comments, spam  
**Requires at least:** 5.0  
**Tested up to:** 5.2  
**Requires PHP:** 7.0.0  
**Stable tag:** 0.1.0  
**License:** GPLv2 or later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html  

Add gutenberg blocks data into the post / page endpoints api.

## Description ##

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

### Using The WordPress Dashboard

1. Navigate to the 'Add New' in the plugins dashboard
2. Search for 'wp-rest-blocks'
3. Click 'Install Now'
4. Activate the plugin on the Plugin dashboard

### Uploading in WordPress Dashboard

1. Navigate to the 'Add New' in the plugins dashboard
2. Navigate to the 'Upload' area
3. Select `wp-rest-blocks.zip` from your computer
4. Click 'Install Now'
5. Activate the plugin in the Plugin dashboard

### Using FTP
1. Download `wp-rest-blocks.zip`
2. Extract the `wp-rest-blocks` directory to your computer
3. Upload the `wp-rest-blocks` directory to the `/wp-content/plugins/` directory
4. Activate the plugin in the Plugin dashboard


## GitHub Updater

The WP User Roles includes native support for the [GitHub Updater](https://github.com/afragen/github-updater) which allows you to provide updates to your WordPress plugin from GitHub.



## Changelog ##

### 0.1.0 ###
* First version.
