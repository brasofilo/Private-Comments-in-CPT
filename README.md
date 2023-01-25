![MTT logo](https://raw.github.com/brasofilo/Private-Comments-in-CPT/master/logo.png)

# Private Comments for CPT
Enables internal comments for a given Custom Post Type when Editing Draft or Pending posts. The comments are only visible in the backend. And marked as internal in the dashboard.
* Contributors: brasofilo, baden03
* Stable tag: 2023.01.25.01
* Tested up to: 6.1.1
* License: [GPLv3](https://www.gnu.org/licenses/gpl-3.0.html) or later
* License URI: https://www.gnu.org/licenses/gpl-3.0.html

## Description
Based on this [WordPress Question](http://wordpress.stackexchange.com/q/74018/12615).

 - Comments do not appear in frontend
 - Comments do not appear when editing a Published post
 - Controlled via Karma and Comment Meta

## Screenshots
###All comments screen - Custom column - Custom background
![All Comments](https://raw.github.com/brasofilo/Private-Comments-in-CPT/master/screenshot-1.png)

###Private comments screen - No custom column - No custom background
![Private Comments](https://raw.github.com/brasofilo/Private-Comments-in-CPT/master/screenshot-2.png)

###Editing a Draft CPT
![Editing CPT](https://raw.github.com/brasofilo/Private-Comments-in-CPT/master/screenshot-3.png)

## Requirements
* WordPress version 3.4 and later (not tested with previous versions)

## Installation
 - Define the custom post types using the `internal_comments_cpt` filter:
```
add_filter( 'internal_comments_cpt', 'my_ic_cpts');
function my_ic_cpts( $cpt_arr ){
	return array('some_cpt_slug', 'some_other_cpt_slug');
}
```
 - The `helper-cpt.php` can be used to create a test post type

## Other Notes
### References
 - http://core.trac.wordpress.org/browser/tags/3.4.2/wp-admin/includes/ajax-actions.php#L719
 - http://wordpress.org/support/topic/using-comment_type-field-for-my-own-purposes
 - http://wordpress.stackexchange.com/q/39784/12615
 - http://wordpress.stackexchange.com/q/56652/12615
 - http://wordpress.stackexchange.com/q/61072/12615
 - http://wordpress.stackexchange.com/q/63422/12615
 - http://wordpress.stackexchange.com/q/64973/12615
 - http://wordpress.stackexchange.com/q/72210/12615
 - http://wordpress.stackexchange.com/q/74018/12615
 - http://stackoverflow.com/q/4054943/1287812

### Admin style of screenshots: https://github.com/toscho/T5-Clean-Admin