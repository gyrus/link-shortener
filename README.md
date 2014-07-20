Link Shortener
=================

A WordPress plugin for managing short redirect links.

## Installation

Note that the plugin folder should be named `link-shortener`. This is because if the [GitHub Updater plugin](https://github.com/afragen/github-updater) is used to update this plugin, if the folder is named something other than this, it will get deleted, and the updated plugin folder with a different name will cause the plugin to be silently deactivated.

__NB:__ If the redirects don't seem to work, try going to _Settings > Permalinks_ and pressing _Save Changes_.

## Constants

Set these in `wp-config.php`. They're designed to be configurable, but to not change once they're set.

* `LS_ENDPOINT_NAME` - The name for the redirect endpoint (default: 'link')

## Filter hooks

* `ls_link_post_type_args` - Filters the default arguments for creating the link custom post type
