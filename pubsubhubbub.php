<?php
/*
Plugin Name: PubSubHubbub
Plugin URI: http://code.google.com/p/pubsubhubbub/
Description: A better way to tell the world when your blog is updated.  Set a custom hub on the <a href="./options-general.php?page=pubsubhubbub/pubsubhubbub">PubSubHubbub settings page</a> 
Version: 1.0
Author: Josh Fraser
Author Email: josh@eventvue.com
Author URI: http://www.joshfraser.com
*/

include("publisher.php");

// function that is called whenever a new post is published
function publish_to_hub($post_id)  {
    
    $atom_url = get_bloginfo('atom_url');
    // get the address of the publish endpoint on the hub
    $hub_url = get_pubsub_endpoint();
    $p = new Publisher($hub_url);
    // need better error handling
    if (!$p->publish_update($atom_url, "http_post_wp")) {
        print_r($p->last_response());
    }
    return $post_id;
}

function add_link_tag() {    
    $sub_url = get_pubsub_endpoint();
    echo '<link rel="hub" href="'.$sub_url.'" />';
}

// add a link to our settings page in the WP menu
function add_plugin_menu() {
    add_options_page('PubSubHubbub Settings', 'PubSubHubbub', 8, __FILE__, 'add_settings_page');
}

// get the endpoints from the wordpress options table
// valid parameters are "publish" or "subscribe"
function get_pubsub_endpoint() {
    $endpoint = get_option('pubsub_endpoint');

    // if no values have been set, revert to the defaults (pubsubhubbub on app engine)
    if (!$endpoint) {
        $endpoint = "http://pubsubhubbub.appspot.com";
    }
    return $endpoint;
}

// write the content for our settings page that allows you to define your endpoints
function add_settings_page() { ?>
    <div class="wrap">
    <h2>Define a custom endpoint</h2>
    
    <form method="post" action="options.php">
    <?php wp_nonce_field('update-options'); ?>
    
    <?php
    
    // load the existing pubsub endpoint value from the wordpress options table
    $pubsub_endpoint = get_pubsub_endpoint();
    
    ?>

    <table class="form-table">

    <tr valign="top">
    <th scope="row">Endpoint URL:</th>
    <td><input type="text" name="pubsub_endpoint" value="<?php echo $pubsub_endpoint; ?>" size="50" /></td>
    </tr>

    </table>

    <input type="hidden" name="action" value="update" />
    <input type="hidden" name="page_options" value="pubsub_endpoint" />

    <p class="submit">
    <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
    </p>

    </form>
    
    <br /><br />
    Thanks for using PubSubHubbub.  Learn more about PubSubHubbub and author of this plugin:
    <ul>
        <li><a href='http://www.onlineaspect.com'>Subscribe to Online Aspect</a></li>
        <li><a href='http://www.twitter.com/joshfraser'>Follow Josh Fraser on twitter</a></li>
        <li><a href='http://code.google.com/p/pubsubhubbub/'>Learn more about the PubSubHubbub protocol</a></li>
    </ul>
    
    </div>

<?php }


// helper function to use the WP-friendly snoopy library 
if (!function_exists('get_snoopy')) {
	function get_snoopy() {
		include_once(ABSPATH.'/wp-includes/class-snoopy.php');
		return new Snoopy;
	}
}

// over-ride the default curl http function to post to the hub endpoint
function http_post_wp($url, $post_vars) {
    
    // turn the query string into an array for snoopy
    parse_str($post_vars);
    $post_vars = array();
    $post_vars['hub.mode'] = $hub_mode;  // PHP converts the periods to underscores
    $post_vars['hub.url'] = $hub_url;    
    
    // more universal than curl
    $snoopy = get_snoopy();
    $snoopy->agent = "(PubSubHubbub-Publisher-WP/1.0)";
	$snoopy->submit($url,$post_vars);
	$response = $snoopy->results;
	// TODO: store the last_response.  requires a litle refactoring work.
	$response_code = $snoopy->response_code;
	if ($response_code == 204)
	    return true;
    return false;
}


// attach the handler that gets called every time you publish a post
add_action('publish_post', 'publish_to_hub');
// add the link to our settings page in the WP menu structure
add_action('admin_menu', 'add_plugin_menu');
// add the link tag that points to the hub in the header of our template & in our atom feed
add_action('atom_head', 'add_link_tag');
// not sure if we want to include this long-term or not.  it's important for us to have for now since so 
// many people use feedburner and the feedburner-forwarding plugin for wordpress
add_action('wp_head', 'add_link_tag');

?>