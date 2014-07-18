Link Shortener
=================

A WordPress plugin for managing short redirect links.

## Installation

Note that the plugin folder should be named `link-shortener`. This is because if the [GitHub Updater plugin](https://github.com/afragen/github-updater) is used to update this plugin, if the folder is named something other than this, it will get deleted, and the updated plugin folder with a different name will cause the plugin to be silently deactivated.

## Basic usage


## Filter hooks

* `ls_link_post_type_args` - Filters the default arguments for creating the link custom post type
* `ls_slug` - Filters the slug for shortened links (default: 'link')