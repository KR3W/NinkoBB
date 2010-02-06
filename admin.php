<?php
/**
 * admin.php
 * 
 * Admin control panel allowing users to manage the forum
 * @author Nijiko Yonskai <me@nijikokun.com>
 * @version 1.1
 * @copyright (c) 2010 ANIGAIKU
 * @package ninko
 */

/**
 * Include common.php
 */
include("include/common.php");

// Are they logged in?
if(!$_SESSION['logged_in'])
{
	header('location: ' . $config['url_path']);
}

// Not admin? Go home!
if(!is_admin($user_data['username']))
{
	header('location: ' . $config['url_path']);
}

// Requesting what page?
switch($_GET['a'])
{
	case "home": $action = "home"; break;
	case "settings": $action = "settings"; break;
	case "users": $action = "users"; break;
	case "topics": $action = "topics"; break;
	case "posts": $action = "posts"; break;
	case "plugins": $action = "plugins"; break;
	default: $action = "home"; break;
}

if($action == "home")
{
	/**
	 * Include header
	 */
	include($config['template_path'] . "header.php");

	/**
	 * Include navigation
	 */
	include($config['template_path'] . "navigation.php");


	/**
	 * Include admin navigation
	 */
	include($config['template_path'] . "admin/navigation.php");

	/**
	 * Include admin home page
	 */
	include($config['template_path'] . "admin/home.php");
}
else if($action == "settings")
{
	// Requesting what settings page?
	switch($_GET['area'])
	{
		case "main": $area = "main"; break;
		case "register": $area = "register"; break;
		case "user": $area = "user"; break;
		case "topics": $area = "topics"; break;
		case "posts": $area = "posts"; break;
		default: $area = "main"; break;
	}
	
	if(isset($_POST['settings']))
	{
		// Check for the true / false boxes
		if($area == "main")
		{
			if(!in_array('allow_cookies', $_POST)){ update_config('allow_cookies', false); }
		}
		else if($area == "register")
		{
			if(!in_array('email_validation', $_POST)){ update_config('email_validation', false); }
		}
		else if($area == "user")
		{
			if(!in_array('avatar_md5_use', $_POST)){ update_config('avatar_md5_use', false); }
		}
		else if($area == "topics")
		{
			if(!in_array('show_first_post', $_POST)){ update_config('show_first_post', false); }
			if(!in_array('allow_quick_reply', $_POST)){ update_config('allow_quick_reply', false); }
			if(!in_array('bbcode', $_POST)){ update_config('bbcode', false); }
			if(!in_array('bbcode_url', $_POST)){ update_config('bbcode_url', false); }
			if(!in_array('bbcode_image', $_POST)){ update_config('bbcode_image', false); }
		}
		
		foreach($_POST as $key => $setting)
		{
			if($key == "settings"){ continue; }
			if($setting == "on"){ $setting = true; }
			
			
			// Update the item only when its not already set to that inside of the config
			if($setting != $config[$key])
			{
				update_config($key, mysql_clean(stripslashes($setting)));
			}
		}
	}
	
	/**
	 * Include header
	 */
	include($config['template_path'] . "header.php");

	/**
	 * Include navigation
	 */
	include($config['template_path'] . "navigation.php");

	/**
	 * Include admin navigation
	 */
	include($config['template_path'] . "admin/navigation.php");
	
	/**
	 * Include admin settings page
	 */
	include($config['template_path'] . "admin/settings.php");
}
else if($action == "users")
{
	if(isset($_GET['ban']))
	{
		$result = ban_user($_GET['ban']);
	}
	else if(isset($_GET['unban']))
	{
		$result = unban_user($_GET['unban']);
	}
	else if(isset($_POST['edit']))
	{
		// User data
		if(alpha($_GET['edit'], 'numeric'))
		{
			$update_user_data = user_data($_GET['edit']);
		
			// If no email we just don't update it.
			if($_POST['username'] != "")
			{
				// Make sure we aren't just submitting the same email.
				if($_POST['username'] != $update_user_data['username'])
				{
					if(alpha($_POST['username'], 'alpha-underscore'))
					{
						update_user($update_user_data['id'], false, 'username', $_POST['username']);
					}
					else
					{
						$error = lang_parse('error_invalid_chars', array(lang('username')));
					}
				}
			}
			
			// Update user
			if(!$error)
			{
				// Admin
				if($_POST['admin']){ $_POST['admin'] = true; }
				
				// Make sure we aren't just submitting the same email.
				if($_POST['admin'] != $update_user_data['admin'])
				{
					update_user($update_user_data['id'], false, 'admin', $_POST['admin']);
				}

				// Moderator
				if($_POST['moderator']){ $_POST['moderator'] = true; }
				
				// Make sure we aren't just submitting the same email.
				if($_POST['moderator'] != $update_user_data['moderator'])
				{
					update_user($update_user_data['id'], false, 'moderator', $_POST['moderator']);
				}

				// Banned
				if($_POST['banned']){ $_POST['banned'] = true; }
				
				// Make sure we aren't just submitting the same email.
				if($_POST['banned'] != $update_user_data['banned'])
				{
					update_user($update_user_data['id'], false, 'banned', $_POST['banned']);
				}
			}
			
			// If no email we just don't update it.
			if($_POST['email'] != "" && !$error)
			{
				// Make sure we aren't just submitting the same email.
				if($_POST['email'] != $update_user_data['email'])
				{
					if(is_email($_POST['email']))
					{
						update_user($update_user_data['id'], false, 'email', $_POST['email']);
					}
					else
					{
						$error = lang_parse('error_invalid_chars', array(lang('email')));
					}
				}
			}
			
			// New password, Log them out, log them in.
			if($_POST['npassword'] != "" && !$error)
			{
				if($_POST['npassword'] == $_POST['npassworda'])
				{
					# Check Password Length
					$length = length($_POST['npassword'], $config['min_name_length'], $config['max_name_length']);
						
					if($length)
					{
						if($length == "TOO_LONG")
						{
							return lang('error_password_too_long');
						}
						else
						{
							return lang('error_password_too_short');
						}
					}
					
					// Are there any errors? If not update password.
					if(!$error)
					{
						$password = md5( $_POST['npassword'] );
					
						update_user($update_user_data['id'], false, 'password', $password);
					}
				}
				else
				{
					$error = lang('error_password_match');
				}
			}
			
			if(!$error)
			{
				$update_user_data = user_data($update_user_data['id']);
				$success = lang('success_updated') . " {$update_user_data['styled_name']}!";
			}
		}
		else
		{
			$error = lang_parse('error_given_not_numeric', array(lang('id_c')));
		}
	}

	/**
	 * Include header
	 */
	include($config['template_path'] . "header.php");

	/**
	 * Include navigation
	 */
	include($config['template_path'] . "navigation.php");

	/**
	 * Include admin navigation
	 */
	include($config['template_path'] . "admin/navigation.php");
	
	if(!$_GET['edit'])
	{
		// Start point
		@$page = $_GET['page'];

		// What page are we on?
		if(is_numeric($page))
		{
			if (!isset($page) || $page < 0) $page = 0;
		}
		else
		{
			$page = 0;
		}
			
		// Start point
		$start = $page * 20;

		// Check the numbers to fetch.
		if(isset($start))
		{
			if(is_numeric($start))
			{
				$users = user_data(false, false, intval($start), 20);
			}
			else
			{
				$users = fetch(false, false, 0, 20);
			}
		}
		else
		{
			$users = fetch(false, false, 0, 20);
		}

		// Topic count
		$user_count = count_users();

		// Messages per page
		$user_pagination = generate_pagination($config['url_path'] . '/admin.php?a=users', $user_count, 20, $start);
	}
	else
	{
		if(!alpha($_GET['edit'], 'numeric'))
		{
			$major_error = lang_parse('error_given_not_numeric', array(lang('year_c')));
		}
		
		$update_user_data = user_data($_GET['edit']);
		
		if(!$update_user_data)
		{
			$major_error = lang('error_user_doesnt_exist');
		}
	}
	
	/**
	 * Include users
	 */
	include($config['template_path'] . "admin/users.php");
}
else if($action == "topics")
{
	if(isset($_GET['delete']))
	{
		$result = delete_topic($_GET['delete']);
		
		// User data
		if($result === "ID_INVALID")
		{
			$error = lang_parse('error_invalid_given', array(lang('id')));
		}
		else if($result === "DELETING_POSTS")
		{
			$error = lang('error_deleting_posts');
		}
		else if($result === "DELETING_TOPIC")
		{
			$error = lang('error_deleting_topic');
		}
		
		if(!$error)
		{
			$success = lang('success_deleted_topic');
		}
	}
	
	if(!$_GET['edit'])
	{
		// Start point
		@$page = $_GET['page'];

		// What page are we on?
		if(is_numeric($page)) {
			if (!isset($page) || $page < 0) $page = 0;
		}
		else
		{
			$page = 0;
		}
			
		// Start point
		$start = $page * $config['messages_per_page'];

		// Check the numbers to fetch.
		if(isset($start))
		{
			if(is_numeric($start))
			{
				$topics = fetch_all(false, intval($start), $config['messages_per_page']);
			}
			else
			{
				$topics = fetch_all(false, 0, $config['messages_per_page']);
			}
		}
		else
		{
			$topics = fetch_all(false, 0, $config['messages_per_page']);
		}

		// Topic count
		$topic_count = forum_count('*', false, false, true);

		// Messages per page
		$topics_pagination = generate_pagination($config['url_path'] . '/admin.php?a=topics', $topic_count, $config['messages_per_page'], $start);
	}
	
	/**
	 * Include header
	 */
	include($config['template_path'] . "header.php");

	/**
	 * Include navigation
	 */
	include($config['template_path'] . "navigation.php");

	/**
	 * Include admin navigation
	 */
	include($config['template_path'] . "admin/navigation.php");
	
	/**
	 * Include topics
	 */
	include($config['template_path'] . "admin/topics.php");
}
else if($action == "posts")
{
	if(isset($_GET['delete']))
	{
		$result = delete_post($_GET['delete']);
		
		// User data
		if($result === "ID_INVALID")
		{
			$error = lang_parse('error_invalid_given', array(lang('id')));
		}
		else if($result === "DELETING_POST")
		{
			$error = lang('error_deleting_post');
		}
		
		if(!$error)
		{
			$success = lang('success_deleted_post');
		}
	}
	
	if(!$_GET['edit'])
	{
		// Start point
		@$page = $_GET['page'];

		// What page are we on?
		if(is_numeric($page)) {
			if (!isset($page) || $page < 0) $page = 0;
		}
		else
		{
			$page = 0;
		}
			
		// Start point
		$start = $page * $config['messages_per_page'];

		// Check the numbers to fetch.
		if(isset($start))
		{
			if(is_numeric($start))
			{
				$posts = fetch_all(true, intval($start), $config['messages_per_page']);
			}
			else
			{
				$posts = fetch_all(true, 0, $config['messages_per_page']);
			}
		}
		else
		{
			$posts = fetch_all(true, 0, $config['messages_per_page']);
		}

		// Topic count
		$post_count = forum_count(false, false, false, false, true);

		// Messages per page
		$post_pagination = generate_pagination($config['url_path'] . '/admin.php?a=posts', $post_count, $config['messages_per_page'], $start);
	}
	
	/**
	 * Include header
	 */
	include($config['template_path'] . "header.php");

	/**
	 * Include navigation
	 */
	include($config['template_path'] . "navigation.php");

	/**
	 * Include admin navigation
	 */
	include($config['template_path'] . "admin/navigation.php");
	
	/**
	 * Include posts
	 */
	include($config['template_path'] . "admin/posts.php");
}
else if($action == "plugins")
{
	if(isset($_GET['activate']))
	{
		// set to load
		$result = load_plugin(urldecode($_GET['activate']));
		
		// User data
		if(is_string($result))
		{
			$error = lang($result);
		}
		
		if(!$error)
		{
			// set as loaded
			plugin_loaded($_GET['activate']);
			
			// set as active
			$success = lang('success_plugin_activate');
		}
	}
	else if(isset($_GET['deactivate']))
	{
		// unset the load
		$result = unload_plugin(urldecode($_GET['deactivate']));
		
		// User data
		if(is_string($result))
		{
			$error = lang($result);
		}
		
		if(!$error)
		{
			// it has been done.
			$success = lang('success_plugin_deactivate');
		}
	}
	
	/**
	 * Include header
	 */
	include($config['template_path'] . "header.php");

	/**
	 * Include navigation
	 */
	include($config['template_path'] . "navigation.php");

	/**
	 * Include admin navigation
	 */
	include($config['template_path'] . "admin/navigation.php");
	
	/**
	 * Include posts
	 */
	include($config['template_path'] . "admin/plugins.php");
}
?>