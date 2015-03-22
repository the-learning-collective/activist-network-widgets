# Network Posts Module Development

## Purpose

Create a pluggable module that will display the most recent posts from all the sites on the network.

## Features

  * Enable the large image on the left to be easily changed using the CMS dashboard
  * Enable the name of the section to be easily added (or omitted)
  * Enable the posts that are displayed to be limited in the following ways:
    * Total number
    * Number per site (e.g. don't show more than 2 posts for each site)
    * By post category (e.g. only display posts that have been categorized as News or Updates)
    * Exclude site (e.g. don't show posts from the Weather and the Boring Stuff sites)
  
  
## Implementation

Create a Wordpress plugin that creates a widget.

  * Widget
  	* Create widget form
  	* Save widget form
  * Stylesheet
  * Function to get and render posts
  	* Accept parameters
  	  * number_posts
  	  * posts_per_site
  	  * include_categories
  	  * exclude_sites
  	* Find matching posts
  	* Return HTML

---
### Data Output
The posts array should be formatted as follows:

    array(
        ['post-id'] => ,
        ['post-title'] => ,
        ['post-date'] => ,
        ['post-excerpt'] => ,
        ['permalink'] => , 
        ['categories'] => array (
            ['category-name'] => category-link
        ), 
        ['site-id'] => ,
        ['site-name'] => ,
        ['site-link'] => ,
    );

### Render

The posts should be rendered as an HTML list in the following format:

    <article id="highlights-module" class="module row highlights clearfix">
        <h2 class="module-heading">Updates</h2>
        <ul class="highlights-list">
            <li class="list-item siteid-4">
                <header class="post-header">
                    <h3 class="post-title">
                        <a href="http://activistnetwork.site/labor/2015/01/13/32bj-members-in-washington-heights/">32BJ members in Washington Heights</a>
                    </h3>
                    <div class="meta">
                        <h6 class="blog-name"><a href="http://activistnetwork.site/labor">Labor</a></h6>
                        <h6 class="post-date date">January 13, 2015</h6>
                        <h6 class="post-author"><a href="http://activistnetwork.site/labor/author/misfist">misfist</a></h6>
                    </div>
                </header>
                <div class="post-excerpt">
                    <p>Today 32BJ members in Washington Heights were mobilizing for the People’s Climate March Source: ...
                    <a href="http://activistnetwork.site/labor/2015/01/13/32bj-members-in-washington-heights/">more</a></p>
                </div>
            </li>
        </ul>
    </article>


### File Structure


    glocal-highlights-module
        glocal-highlights-module-tinymce.php
        glocal-highlights-module-widget.php
        glocal-highlights-module.php
        js
        stylesheets
            css
            sass

## Optional Considerations (not necessarily MVP)

  * Disable plugin styles
    * This makes it easier to customize visual display)
  * Shortcode (e.g. [highlights-module] a text shortcode that can be used to embed directly into the body of a post or page)
    * TinyMCE Editory button
      * This adds a button to the wysiwyg editor that automatically inserts the shortcode into the editor (useful when the widget has a lot of possible parameters that are hard/confusing to remember)