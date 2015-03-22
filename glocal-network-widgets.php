<?php
/*
Plugin Name: Activist Network Widgets
Description: Widgets that display network content on the site.
Author: Pea, Glocal
Author URI: http://glocal.coop
Version: 0.1
License: GPL
*/

/************* NETWORK POSTS MAIN FUNCTION *****************/

/************* Parameters *****************
	@number_posts - the total number of posts to display (default: 10)
	@posts_per_site - the number of posts for each site (default: no limit)
	@include_categories - the categories of posts to include (default: all categories)
	@exclude_sites - the site from which posts should be excluded (default: all sites (public sites, except archived, deleted and spam))
	@output - HTML or array (default: HTML)
	@style - normal (list), block or highlights (default: normal) - ignored if @output is 'array'
	@ignore_styles - don't use plugin stylesheet (default: false) - ignored if @output is 'array'
	@id - ID used in list markup (default: network-posts-RAND) - ignored if @output is 'array'
	@class - class used in list markup (default: post-list) - ignored if @output is 'array'
	@title - title displayed for list (default: Posts) - ignored unless @style is 'highlights'
	@title_image - image displayed behind title (default: home-highlight.png) - ignored unless @style is 'highlights'
	@show_thumbnail - display post thumbnail (default: False) - ignored if @output is 'array'
	@show_meta - if meta info should be displayed (default: True) - ignored if @output is 'array'
	@show_excerpt - if excerpt should be displayed (default: True) - ignored if @output is 'array' or if @show_meta is False
	@excerpt_length - number of words to display for excerpt (default: 50) - ignored if @show_excerpt is False
	@show_site_name - if site name should be displayed (default: True) - ignored if @output is 'array'
*/

// Input: user-selected options array
// Output: list of posts from all sites, rendered as HTML or returned as array
function glocal_networkwide_posts_module($parameters = []) {

    // Default parameters
    // There aren't any now, but there might be some day.
    $defaults = array(
        'number_posts' => 10, //
        'exclude_sites' => null, 
        'include_categories' => null,
        'posts_per_site' => null,
        'output' => 'html',
        'style' => 'normal',
        'id' => 'network-posts-' . rand(),
        'class' => 'post-list',
        'title' => 'Posts',
        'title_image' => null,
        'show_thumbnail' => False,
        'show_meta' => True,
        'show_excerpt' => True,
		'excerpt_length' => 55,
        'show_site_name' => True,
    );
	
	// CALL MERGE FUNCTION
	$settings = get_merged_settings($parameters, $defaults);

    // Extract each parameter as its own variable
    extract( $settings, EXTR_SKIP );
	
    $exclude = $exclude_sites;
    // Strip out all characters except numbers and commas. This is working!
    $exclude = preg_replace("/[^0-9,]/", "", $exclude);
    $exclude = explode(",", $exclude);
	   
    // Get a list of sites
    $siteargs = array(
        'archived'   => 0,
        'spam'       => 0,
        'deleted'    => 0,
        'public'     => 1
    );
    $sites = wp_get_sites($siteargs);

    // CALL EXCLUDE SITES FUNCTION
    $sites_list = exclude_sites($exclude, $sites);
    
    // CALL GET POSTS FUNCTION
    $posts_list = get_posts_list($sites_list, $settings);  
    
    if($output == 'array') {
        
        // Return an array
        return $posts_list;
        
        // For testing
        //return '<pre>' . var_dump($posts_list) . '</pre>';
            
    } else {
        // CALL RENDER FUNCTION
        return render_html($posts_list, $settings);
        
    }

}


/************* NETWORK SITES MAIN FUNCTION *****************/

/************* Parameters *****************
	@return - Return (display list of sites or return array of sites) (default: display)
	@numbersites - Number of sites to display/return (default: no limit)
	@excludesites - ID of sites to exclude (default: 1 (usually, the main site))
	@sortby - newest, updated, active, alpha (registered, last_updated, post_count, blogname) (default: alpha)
	@defaultimage - Default image to display if site doesn't have a custom header image (default: none)
	@instanceid - ID name for site list instance (default: network-sites-RAND)
	@classname - CSS class name(s) (default: network-sites-list)
	@hidemeta - Select in order to update date and latest post. Only relevant when return = 'display'. (default: false)
	@showjoin - Future
	@jointext - Future
	@hideimage - Select in order to hide site image. (default: false)
*/

// Input: user-selected options array
// Output: list of sites, rendered as HTML or returned as array
function glocal_networkwide_sites_module($parameters = []) {

	/** Default parameters **/
    $defaults = array(
        'return' => 'display',
        'numbersites' => 0,
        'exclude_sites' => '1', 
        'sortby' => 'alpha',
        'defaultimage' => null,
		'hide_site_meta' => False,
        'hide_site_image' => False,
        'instanceid' => 'network-sites-' . rand(),
        'classname' => 'network-sites-list',
    );
	
	// CALL MERGE FUNCTION
	$settings = get_merged_settings($parameters, $defaults);

    // Extract each parameter as its own variable
    extract( $settings, EXTR_SKIP );
	
	// CALL GET SITES FUNCTION
	$sites_list = get_sites_list($settings);
	
	if($return == 'array') {
		return $sites_list;
	}
	else {
		
	// CALL RENDER FUNCTION
	
		return render_sites_list($sites_list, $settings);
		
	}
	
}



// Inputs: uses global variable $styles
// Output: conditionally renders stylesheet using WP add_action() method
// Conditionals don't work. Loading on all pages...
add_action('wp_enqueue_scripts','load_highlight_styles', 200);
function load_highlight_styles() {    
    wp_enqueue_style( 'glocal-network-posts', plugins_url( '/stylesheets/css/style.css' , __FILE__ ) );
}


/************* GET FUNCTIONS *****************/

// Input: array of user inputs and array of default values
// Output: merged array of $settings
function get_merged_settings($user_selections_array, $default_values_array) {

	$parameters = $user_selections_array;
	$defaults = $default_values_array;


    // Parse & merge parameters with the defaults - http://codex.wordpress.org/Function_Reference/wp_parse_args
    $settings = wp_parse_args( $parameters, $defaults );

    // Strip out tags
    foreach($settings as $parameter => $value) {
        // Strip everything
        $settings[$parameter] = strip_tags($value);
    }
	
	return $settings;

}

// Input: parameters array
// Output: array of sites with site information
function get_sites_list($options_array) {

	$settings = $options_array;
	
	// Make each parameter as its own variable
    extract( $settings, EXTR_SKIP );
	
	// Turn exclude setting into array
    $exclude = $exclude_sites;
    // Strip out all characters except numbers and commas. This is working!
    $exclude = preg_replace("/[^0-9,]/", "", $exclude);
    // Convert string to array
    $exclude = explode(",", $exclude);
		
    $siteargs = array(
        'limit'      => $numbersites,
        'archived'   => 0,
        'spam'       => 0,
        'deleted'    => 0,
    );
	
    $sites = wp_get_sites($siteargs);
	
	// CALL EXCLUDE SITES FUNCTION
	$sites = exclude_sites($exclude, $sites);
	
	$site_list = array();
	
	foreach($sites as $site) {
		
        $site_id = $site['blog_id'];
        $site_details = get_blog_details($site_id);
		
		$site_list[$site_id] = array(
			'blog_id' => $site_id,  // Put site ID into array
			'blogname' => $site_details->blogname,  // Put site name into array
			'siteurl' => $site_details->siteurl,  // Put site URL into array
            'path' => $site_details->path,  // Put site path into array
            'registered' => $site_details->registered,
            'last_updated' => $site_details->last_updated,
            'post_count' => $site_details->post_count,
		);
		
		
		// CALL GET SITE IMAGE FUNCTION
		$site_image = get_site_header_image($site_id);
		
		if($site_image) {
			$site_list[$site_id]['site-image'] = $site_image;
		}
		elseif($defaultimage) {
			$site_list[$site_id]['site-image'] = $defaultimage;
		}
		else {
			$site_list[$site_id]['site-image'] = '';
		}
		
		$site_list[$site_id]['recent-post'] = get_most_recent_post($site_id);
	
	}
	
	return $site_list;
	
}

// Inputs: exclude array and sites array
// Output: array of sites, excluding those specfied in parameters
function exclude_sites($exclude_array, $sites_array) {

    $exclude = $exclude_array;
    $sites = $sites_array;

    $exclude_length = sizeof($exclude);
    $sites_length = sizeof($sites);
	
    // If there are any sites to exclude, remove them from the array of sites
    if($exclude_length) {

        for($i = 0; $i < $exclude_length; $i++) {

            for($j = 0; $j < $sites_length; $j++) {

                if($sites[$j]['blog_id'] == $exclude[$i]) {
                    // Remove the site from the list
                    unset($sites[$j]);
                }

            }
        }

        // Fix the array indexes so they're in order again
        $sites = array_values($sites);

        return $sites;

    }

}

// Inputs: array of sites and parameters array
// Output: single array of posts with site information, sorted by post-date
function get_posts_list($sites_array, $options_array) {

    $sites = $sites_array;
    $settings = $options_array;

    // Make each parameter as its own variable
    extract( $settings, EXTR_SKIP );

    $post_list = array();

    // For each site, get the posts
    foreach($sites as $site => $detail) {

        $site_id = $detail['blog_id'];
        $site_details = get_blog_details($site_id);

        // Switch to the site to get details and posts
        switch_to_blog($site_id);
        
        // CALL GET SITE'S POST FUNCTION
        // And add to array of posts
        $post_list = $post_list + get_sites_posts($site_id, $settings);

        // Unswitch the site
        restore_current_blog();

    }

    // CALL SORT FUNCTION
    $post_list = sort_by_date($post_list);
    
    // CALL LIMIT FUNCTIONS
    $post_list = limit_number_posts($post_list, $number_posts);

    return $post_list;

}

// Input: site id and parameters array
// Ouput: array of posts for site
function get_sites_posts($site_id, $options_array) {
    
    $site_id = $site_id;
    $settings = $options_array;

    // Make each parameter as its own variable
    extract( $settings, EXTR_SKIP );
    
    $site_details = get_blog_details($site_id);
    
    $post_args = array(
        'posts_per_page' => $posts_per_site,
        'category_name' => $include_categories
    );
    
    $recent_posts = wp_get_recent_posts($post_args);

    // Put all the posts in a single array
    foreach($recent_posts as $post => $postdetail) {

        global $post;

        $post_id = $postdetail['ID'];
        $author_id = $postdetail['post_author'];
        $prefix = $postdetail['post_date'] . '-' . $postdetail['post_name'];

        //CALL POST MARKUP FUNCTION
        $post_markup_class = get_post_markup_class($postdetail['post-id']);
        $post_markup_class .= ' siteid-' . $site_id;

        //Returns an array
        $post_thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'thumbnail' );

        if($postdetail['post_excerpt']) {
            $excerpt = $postdetail['post_excerpt'];
        } else {
            $excerpt = custom_post_excerpt($post_id, $excerpt_length, ' ...');
        }

        $post_list[$prefix] = array(
            'post-id' => $post_id,
            'post-title' => $postdetail['post_title'],
            'post-date' => $postdetail['post_date'],
            'post-author' => get_the_author_meta( 'display_name', $postdetail['post_author'] ),
            'post-content' => $postdetail['post_content'],
            'post-excerpt' => $excerpt,
            'permalink' => get_permalink($post_id),
            'post-image' => $post_thumbnail[0],
            'post-class' => $post_markup_class,
            'site-id' => $site_id,
            'site-name' => $site_details->blogname,
            'site-link' => $site_details->siteurl,
        );

        //Get post categories
        $post_categories = wp_get_post_categories($post_id);

        foreach($post_categories as $post_category) {
            $cat = get_category($post_category);
            $post_list[$prefix]['categories'][] = $cat->name;
        }

    }
        
    return $post_list;
    
}

// Input: site_id
// Output: array post data for single post
function get_most_recent_post($site_id) {

	$site_id = $site_id;
	
	// Switch to current blog
	switch_to_blog( $site_id );

	// Get most recent post
	$recent_posts = wp_get_recent_posts('numberposts=1');
	
	// Get most recent post info
	foreach($recent_posts as $post) {
		$post_id = $post['ID'];

		// Post into $site_list array
		$recent_post_data = array (
			'post_id' => $post_id,
			'post_author' => $post['post_author'],
			'post_slug' => $post['post_name'],
			'post_date' => $post['post_date'],
			'post_title' => $post['post_title'],
			'post_content' => $post['post_content'],
			'permalink' => get_permalink($post_id),
		);

		// If there is a featured image, add URL to array, else leave empty
		if( wp_get_attachment_url( get_post_thumbnail_id($post_id) ) ) {
			$recent_post_data['thumbnail'] = wp_get_attachment_url(get_post_thumbnail_id($post_id));
		} else {
			$recent_post_data['thumbnail'] = '';
		}
	}

	// Exit
	restore_current_blog();
	
	return $recent_post_data;

}


/************* RENDERING FUNCTIONS *****************/

// Input: array of posts and parameters
// Output: rendered as 'normal' or 'highlight' HTML
function render_html($posts_array, $options_array) {
    
    $posts_array = $posts_array;
    $settings = $options_array;

    // Make each parameter as its own variable
    extract( $settings, EXTR_SKIP );
    
    if($style == 'highlights') {
        
        //CALL RENDER HIGHLIGHTS HTML FUNCTION
        $rendered_html = render_highlights_html($posts_array, $settings);
        
    } elseif($style == 'block') {
        
        //CALL RENDER BLOCK HTML FUNCTION
        $rendered_html = render_block_html($posts_array, $settings);
                
    } else {
	
		//CALL RENDER LIST HTML FUNCTION
        $rendered_html = render_list_html($posts_array, $settings);
		
	}
    
    return $rendered_html;
}

// Input: array of post data
// Output: HTML list of posts
function render_list_html($posts_array, $options_array) {

    $posts_array = $posts_array;
    $settings = $options_array;
    
    // Make each parameter as its own variable
    extract( $settings, EXTR_SKIP );
        
    $html = '<ul class="network-posts-list">';

    foreach($posts_array as $post => $post_detail) {

        global $post;
        
        $post_id = $post_detail['post-id'];
        $post_categories = implode(", ", $post_detail['categories']);
                    
        $html .= '<li class="post type-post list-item siteid-' . $post_detail['site-id'] . '">';
        if($show_thumbnail && $post_detail['post-image']) {
            //Show image
            $html .= '<a href="' . $post_detail['permalink'] . '" class="post-thumbnail">';
            $html .= '<img class="attachment-post-thumbnail wp-post-image item-image" src="' . $post_detail['post-image'] . '">';
            $html .= '</a>';
        }
        $html .= '<h4 class="post-title">';
        $html .= '<a href="' . $post_detail['permalink'] . '">';
        $html .= $post_detail['post-title'];
        $html .= '</a>';
        $html .= '</h4>';
		
		if($show_meta) {
			$html .= '<div class="meta">';
			if($show_site_name) {
				$html .= '<span class="blog-name"><a href="' . $post_detail['site-link'] . '">';
				$html .= $post_detail['site-name'];
				$html .= '</a></span>';
			}
			$html .= '<span class="post-date posted-on date"><time class="entry-date published updated" datetime="' . $post_detail['post-date'] . '">';
			$html .= date_i18n( get_option( 'date_format' ), strtotime( $post_detail['post-date'] ) );
			$html .= '</time></span>';
			$html .= '<span class="post-author byline author vcard"><a href="' . $post_detail['site-link'] . '/author/' . $post_detail['post-author'] . '">';
			$html .= $post_detail['post-author'];
			$html .= '</a></span>';
			$html .= '</div>';
		}
		if($show_excerpt) {
			$html .= '<div class="post-excerpt" itemprop="articleBody">' . $post_detail['post-excerpt'] . '</div>';
		}
		if($show_meta) {
			$html .= '<div class="meta">';
			$html .= '<div class="post-categories cat-links tags">' . $post_categories . '</div>';
			$html .= '</div>';
		}
        $html .= '</li>';

    }

    $html .= '</ul>';

    return $html;

}

// Input: array of post data
// Output: HTML list of posts
function render_block_html($posts_array, $options_array) {

    $posts_array = $posts_array;
    $settings = $options_array;
    
    // Make each parameter as its own variable
    extract( $settings, EXTR_SKIP );
    	
    $html = '<div class="network-posts-list">';

    foreach($posts_array as $post => $post_detail) {

        global $post;
        
        $post_id = $post_detail['post-id'];
        $post_categories = implode(", ", $post_detail['categories']);
		        
		$html .= '<article id="post-' . $post_id . '" class="post hentry list-item" role="article">';
				
		$html .= '<header class="article-header">';
        if($show_thumbnail && $post_detail['post-image']) {
            //Show image
            $html .= '<div class="item-image thumbnail">';
            $html .= '<a href="' . $post_detail['permalink'] . '" class="post-thumbnail">';
            $html .= '<img class="attachment-post-thumbnail wp-post-image item-image" src="' . $post_detail['post-image'] . '">';
            $html .= '</a>';
            $html .= '</div>';
        }
		$html .= '<h3 class="post-title">';
		$html .= '<a href="' . $post_detail['permalink'] . '">';
		$html .= $post_detail['post-title'];
		$html .= '</a>';
		$html .= '</h3>';
		
		if($show_meta) {
			$html .= '<div class="meta">';

			if($show_site_name) {
				$html .= '<span class="blog-name"><a href="' . $post_detail['site-link'] . '">';
				$html .= $post_detail['site-name'];
				$html .= '</a></span>';
			}

			$html .= '<span class="post-date posted-on date"><time class="entry-date published updated" datetime="' . $post_detail['post-date'] . '">';
			$html .= date_i18n( get_option( 'date_format' ), strtotime( $post_detail['post-date'] ) );
			$html .= '</time></span>';
			$html .= '<span class="post-author byline author vcard"><a href="' . $post_detail['site-link'] . '/author/' . $post_detail['post-author'] . '">';
			$html .= $post_detail['post-author'];
			$html .= '</a></span>';

			$html .= '</div>';
		}
		$html .= '</header>';
		
		$html .= '<section class="entry-content">';
		$html .= '<div class="post-excerpt" itemprop="articleBody">' . $post_detail['post-excerpt'] . '</div>';
		$html .= '</section>';
		
		if($show_meta) {
			$html .= '<footer class="article-footer">';
			$html .= '<div class="meta">';
			$html .= '<div class="post-categories cat-links tags">' . $post_categories . '</div>';
			$html .= '</div>';
			$html .= '</footer>';
		}
		
		$html .= '</article>';

    }

    $html .= '</div>';

    return $html;

}

// Input: array of post data and parameters
// Output: HTML list of posts
function render_highlights_html($posts_array, $options_array) {
    
    $highlight_posts = $posts_array;
    $settings = $options_array;
    
    // Extract each parameter as its own variable
    extract( $settings, EXTR_SKIP );
    
    //var_dump($settings);
    
    if($title_image) {
        $title_image = 'style="background-image:url(' . $title_image . ')"';
    }
    
    $html = '';
    
    $html .= '<article id="highlights-module" class="highlights ' . $class . '">';
    $html .= '<h2 class="module-heading" ' . $title_image . '>';
    $html .= $title;
    $html .= '</h2>';
    $html .= render_list_html($highlight_posts, $settings);
    $html .= '</article>';

    return $html;

}

// Input: post excerpt text
// Output: cleaned up excerpt text
function custom_post_excerpt($post_id, $length='55', $trailer=' ...') {
    $the_post = get_post($post_id); //Gets post ID

        $the_excerpt = $the_post->post_content; //Gets post_content to be used as a basis for the excerpt
        $excerpt_length = $length; //Sets excerpt length by word count
        $the_excerpt = strip_tags(strip_shortcodes($the_excerpt)); //Strips tags and images
        $words = explode(' ', $the_excerpt, $excerpt_length + 1);

        if(count($words) > $excerpt_length) :
            array_pop($words);
            $trailer = '<a href="' . get_permalink($post_id) . '">' . $trailer . '</a>';
            array_push($words, $trailer);
            $the_excerpt = implode(' ', $words);
        endif;

    return $the_excerpt;
}

// Input: post_id
// Output: string of post classes (to be used in markup)
function get_post_markup_class($post_id) {
    
    $post_id = $post_id;
    
    $markup_class_array = get_post_class(array('list-item'), (int) $post_id);
    
    $post_markup_class = implode(" ", $markup_class_array);
    
    return $post_markup_class;
}

// Input: array of site data and parameters
// Output: list of sites render as HTML
function render_sites_list($sites_array, $options_array) {

	$sites = $sites_array;
	$settings = $options_array;
	
	// Extract each parameter as its own variable
    extract( $settings, EXTR_SKIP );
	
	$html = '<ul id="' . $instanceid . '" class="' . $classname . '">';
	
	foreach($sites as $site) {
				
		$site_id = $site['blog_id'];
		
		// CALL GET SLUG FUNCTION
		$slug = get_site_slug($site['path']);
		
		$html .= '<li id="site-' . $site_id . '" data-posts="' . $site['post_count'] . '" data-slug="' . $slug . '" data-id="' . $site_id . '" data-updated="' . $site['last_updated'] . '">' ;
		if(!$hide_site_image) {
			$html .= '<div class="site-image thumbnail">';
			$html .= '<img src="' . $site['site-image'] . '" class="post-thumbnail" title="' . $site['blogname'] . '">';
			$html .= '</div>';
		}
		$html .= '';
		$html .= '<h3 class="site-name">';
		$html .= '<a href="' . $site['siteurl'] . '">';
		$html .= $site['blogname'];
		$html .= '</a>';
		$html .= '</h3>';
		if(!$hide_site_meta) {
			$html .= '<div class="meta">';
			
			$html .= '<time>';
			$html .= date_i18n( get_option( 'date_format' ), strtotime( $site['last_updated'] ) );
			$html .= '</time>';
			
			$html .= '<div class="recent-post">';
			$html .= '<a href="'. $site['recent-post']['permalink'] .'">';
			$html .= $site['recent-post']['post_title'];
			$html .= '</a>';
			$html .= '<div class="post-meta">';
			$html .= '<time>';
			$html .= date_i18n( get_option( 'date_format' ), strtotime( $site['recent-post']['post_date'] ) );
			$html .= '</time>';
			$html .= '</div>';
			$html .= '</div>';
			
			$html .= '</div>';
		}

		$html .= '</li>';
		
	}
	
	$html .= '</ul>';
	
	return $html;
	
}


/************* SORTING FUNCTIONS *****************/

// Inputs: array of posts data
// Output: array of posts data sorted by post-date
function sort_by_date($posts_array) {

    $posts_array = $posts_array;

    usort($posts_array, function ($b, $a) {
        return strcmp($a['post-date'], $b['post-date']);
    });

    return $posts_array;

}

// Inputs: array of posts data
// Output: array of posts data sorted by site
function sort_by_site($posts_array) {

    $posts_array = $posts_array;

    usort($posts_array, function ($b, $a) {
        return strcmp($a['site-id'], $b['site-id']);
    });

    return $posts_array;

}

// Input: array of sites
// Output: array of sites sorted by last_updated
function sort_sites_by_last_updated($sites_array) {
	
	$sites = $sites_array;
	
	usort($sites, function ($b, $a) {
		return strcmp($a['last_updated'], $b['last_updated']);
	});
}

// Input: array of sites
// Output: array of sites sorted by post_count
function sort_sites_by_most_active($sites_array) {
	
	$sites = $sites_array;
	
	usort($sites, function ($b, $a) {
		return strcmp($a['post_count'], $b['post_count']);
	});
}

// Input: associative array and sort key (e.g. 'post_count')
// Output: array sorted by key
function sort_array($array, $sort_key) {
	
	$array = $array;
	$key = $sort_key;
	
	usort($array, function ($b, $a) {
		return strcmp($a[$key], $b[$key]);
	});
}


/************* MISC HELPER FUNCTIONS *****************/

// Input: array of posts and max number parameter
// Output: array of posts reduced to max number
function limit_number_posts($posts_array, $max_number) {
    
    $posts = $posts_array;
    $limit = $max_number;
    
    if(count($posts) > $limit ) {
        array_splice($posts, $limit);
    }
    
    return $posts;
}

// Input: site path
// Output: site slug string
function get_site_slug($site_path) {
	
	$path = $site_path;
	$stripped_path = str_replace('/', '', $path); // Strip slashes from path to get slug
	
	if(!$path) { // If there is no slug (it's the main site), make slug 'main'
		$slug = 'main';
	}
	else { // Otherwise use the stripped path as slug  
		$slug = $stripped_path;
	}
	
	return $slug;
}

// Input: site_id
// Output: site image URL as string
function get_site_header_image($site_id) {
	//store the current blog_id being viewed
	global $blog_id;
	$current_blog_id = $blog_id;

	//switch to the main blog designated in $site_id
	switch_to_blog($site_id);

	$site_image = get_custom_header();

	//switch back to the current blog being viewed
	switch_to_blog($current_blog_id);

	return $site_image->thumbnail_url;
}


/************* SHORTCODE FUNCTIONS *****************/
//[anp_network_posts number_posts=10 exclude_sites="1,5" include_categories="news" posts_per_site=2 style= "highlights" id="my-highlights" class="post-list" title="Network Highlights" title_image="http://activistnetwork.site/wp-content/uploads/2015/03/my_photo.jpg" show_thumbnail=0]

// Inputs: optional parameters
// Output: rendered HTML list of posts
function glocal_networkwide_posts_shortcode( $atts, $content = null ) {

	// Attributes
	extract( shortcode_atts(
		array(), $atts )
	);

    if(function_exists('glocal_networkwide_posts_module')) {
        $html .= glocal_networkwide_posts_module( $atts );
        echo $html;
    }
    
}
add_shortcode( 'anp_network_posts', 'glocal_networkwide_posts_shortcode' );

// Inputs: optional parameters
// Output: rendered HTML list of sites
function glocal_networkwide_sites_shortcode( $atts, $content = null ) {

	// Attributes
	extract( shortcode_atts(
		array(), $atts )
	);

    if(function_exists('glocal_networkwide_sites_module')) {
        $html .= glocal_networkwide_sites_module( $atts );
        echo $html;
    }
    
}
add_shortcode( 'anp_network_sites', 'glocal_networkwide_sites_shortcode' );


/************* WIDGET *****************/

// Add Widget
//require_once dirname( __FILE__ ) . '/glocal-network-sites-widget.php';

/************* TINYMCE EDITOR BUTTON *****************/

// Add TinyMCE button
//require_once dirname( __FILE__ ) . '/glocal-network-tinymce.php';