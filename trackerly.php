<?php
/*
Plugin Name: Tracker.ly
Plugin URI: http://www.tracker.ly/wordpressplugin
Description: The Tracker.ly WordPress plugin redirects non-WordPress links through the Tracker.ly link redirection application, thus eliminating conflicts between WordPress and Tracker.ly.
Version: 1.0
Author: Tracker.ly
Author URI: http://www.tracker.ly/
*/

$trackerly_install_folder = '';

define('TRACKERLY_INSTALL_FOLDER', '%%trackerly_install_folder%%');
define('TRACKERLY_DEFAULT_INSTALL_FOLDER', 'trackerly');

function trackerly_init() {
	global $trackerly_install_folder;

	$trackerly_install_folder = get_option('trackerly_install_folder');

	if ($trackerly_install_folder === false) {
		if (!preg_match('#^\%{2}(.*)\%{2}$#', TRACKERLY_INSTALL_FOLDER)) {
			update_option('trackerly_install_folder', TRACKERLY_INSTALL_FOLDER);

			$trackerly_install_folder = TRACKERLY_INSTALL_FOLDER;
		} else {
			update_option('trackerly_install_folder', TRACKERLY_DEFAULT_INSTALL_FOLDER);

			$trackerly_install_folder = TRACKERLY_DEFAULT_INSTALL_FOLDER;
		}
	}

	//add_action('admin_menu', 'trackerly_config_page');
	//trackerly_admin_warnings();
}

add_action('init', 'trackerly_init');

if (!function_exists('wp_nonce_field')) {
	function trackerly_nonce_field($action = -1) {
		return;
	}

	$trackerly_nonce = -1;
} else {
	function trackerly_nonce_field($action = -1) {
		return wp_nonce_field($action);
	}

	$trackerly_nonce = 'trackerly-update-install-folder';
}

function trackerly_remove_slashes($string, $point) {
	if (empty($string)) {
		return '';
	}

	switch ($point) {
		case 'start' :
			$string = preg_replace('#^\/#', '', $string);

			break;

		case 'end' :
			$string = preg_replace('#\/$#', '', $string);

			break;

		case 'border' :
			$string = preg_replace('#^\/|\/$#', '', $string);

			break;

		default :
			$string = preg_replace('#\/{2,}#', '/', $string);
	}

	return $string;
}

function trackerly_config_page() {
	if (function_exists('add_submenu_page')) {
		add_submenu_page('plugins.php', __('Tracker.ly Configuration'), __('Tracker.ly Configuration'), 'manage_options', 'trackerly-install-folder-config', 'trackerly_conf');
	}
}

function trackerly_conf() {
	$ms = array();

	if (isset($_POST['submit'])) {
		if (function_exists('current_user_can') && !current_user_can('manage_options')) {
			die(__('Cheatin&#8217; uh?'));
		}

		check_admin_referer($trackerly_nonce);

		$install_folder		   = trim($_POST['install_folder']);
		$install_folder_status = trackerly_verify_install_folder($install_folder);

		if ($install_folder_status == 'valid') {
			update_option('trackerly_install_folder', $install_folder);

			$ms[] = 'new_install_folder_valid';
		} elseif ($install_folder_status == 'invalid') {
			$ms[] = 'new_install_folder_invalid';
		} else if ($install_folder_status == 'failed') {
			$ms[] = 'new_install_folder_failed';
		}
	} else {
		$install_folder		   = trackerly_get_install_folder();
		$install_folder_status = trackerly_verify_install_folder($install_folder);

		if ($install_folder_status == 'invalid') {
			$ms[] = 'install_folder_invalid';
		} else if (($install_folder !== false) && ($install_folder_status == 'failed')) {
			$ms[] = 'install_folder_failed';
		}
	}

	$messages = array(
		'new_install_folder_valid'	 => array('color' => '2d2', 'text' => __('Tracker.ly installation folder has been verified. You can use Tracker.ly now.')),
		'new_install_folder_invalid' => array('color' => 'd22', 'text' => __('Tracker.ly installation folder you entered is invalid. Please double-check it.')),
		'new_install_folder_failed'  => array('color' => 'd22', 'text' => __('Tracker.ly installation folder cann\'t be verified because a connection to your server could not be established. Please check your server configuration.')),
		'install_folder_invalid'	 => array('color' => 'd22', 'text' => __('Current Tracker.ly installation folder is invalid. Please check it.')),
		'install_folder_failed'		 => array('color' => 'd22', 'text' => __('Tracker.ly installation folder cann\'t be verified because a connection to your server could not be established. Please check your server configuration.'))
	);
?>
<?php
	if (!empty($_POST['submit']) && ($install_folder_status == 'valid')) {
?>
<div id = "message" class = "updated fade"><p><strong><?php _e('Options saved.') ?></strong></p></div>
<?php
	}
?>
<div class="wrap">
	<h2><?php _e('Tracker.ly Configuration'); ?></h2>
	<div class="narrow">
		<form action="" method="post" id="trackerly-conf" style="margin: auto; width: 400px; ">
			<p><?php _e('The Tracker.ly WordPress plugin redirects non-WordPress links through the Tracker.ly link redirection application, thus eliminating conflicts between WordPress and Tracker.ly.'); ?></p>
			<h3><label for="install_folder"><?php _e('Tracker.ly Installation Folder'); ?></label></h3>
<?php
	foreach ($ms as $m) {
?>
			<p style="padding: .5em; background-color: #<?php echo $messages[$m]['color']; ?>; color: #fff; font-weight: bold;"><?php echo $messages[$m]['text']; ?></p>
<?php
	}
?>
			<p><input id="install_folder" name="install_folder" type="text" size="20" value="<?php echo $install_folder; ?>" style="font-family: 'Courier New', Courier, mono; font-size: 1.5em;" /> (<?php _e('<a href="http://www.tracker.ly/wordpressplugin">What is this?</a>'); ?>)</p>
<?php
	if ($install_folder_status == 'invalid') {
?>
			<ol type = "1">
				<li><?php _e('Does the folder name match where you installed Tracker.ly?'); ?></li>
				<li><?php _e('Check the format: Example, "trackerly" or "/trackerly" is correct and "\trackerly" is not.'); ?></li>
		 		<li><?php _e('Are there invalid characters like spaces?'); ?></li>
		 		<li><?php _e('Is the path relative to the home page directory?'); ?></li>
		 		<li><?php _e('If your links are working and your blog is in maintenance mode, ignore this error.'); ?></li>
		 	</ol>
<?php
	}
?>
<?php
	trackerly_nonce_field($trackerly_nonce);
?>
			<p class="submit"><input type="submit" name="submit" value="<?php _e('Update options &raquo;'); ?>" /></p>
		</form>
	</div>
</div>
<?php
}

function trackerly_get_install_folder() {
	global $trackerly_install_folder;

	if ($trackerly_install_folder !== false) {
		return $trackerly_install_folder;
	}

	return get_option('trackerly_install_folder');
}

function trackerly_verify_install_folder($install_folder) {
	$install_folder = trackerly_remove_slashes($install_folder, 'extra');

	if (!preg_match('#^/?((\w+\.?)*\w+/?)*$#', $install_folder)) {
		return 'invalid';
	}

	$trackerly_install_folder = trackerly_get_install_folder();
	$blog_home_url_details	  = parse_url(get_option('home'));
	$path					  = '/iqurguhfsdfglkj' . mt_rand(10000, 99999);

	update_option('trackerly_install_folder', $install_folder);

	$test_response = trackerly_http_get('trackerly-redirect-test=1', $blog_home_url_details['host'], $path);

	update_option('trackerly_install_folder', $trackerly_install_folder);

	if (!is_array($test_response) || !isset($test_response[1])) {
		return 'failed';
	}

	if (!preg_match('#trackerly-redirect-test#', trim((string)$test_response[1]))) {
		return 'invalid';
	}

	return 'valid';
}

function trackerly_http_get($request, $http_host, $path) {
	$http_request  = 'GET ' . $path . (!empty($request)? '?' . $request : '') . " HTTP/1.0\r\n";
	$http_request .= 'User-Agent: ' . $_SERVER['HTTP_USER_AGENT'] . "\r\n";
	$http_request .= 'HOST: ' . $http_host . "\r\n";
	$http_request .= 'Connection: Close' . "\r\n\r\n";

	$response = '';

	if (($fs = @fsockopen($http_host, 80, $errno, $errstr, 10)) != false) {
		fwrite($fs, $http_request);

		while (!feof($fs)) {
			$response .= fread($fs, 1024); // One TCP-IP packet
		}

		fclose($fs);

		$response = explode("\r\n\r\n", $response, 2);
	}

	return $response;
}

function trackerly_admin_warnings() {
	if (empty($_POST['submit'])) {
		function trackerly_check_redirect() {
			$install_folder = trackerly_get_install_folder();

			if (trackerly_verify_install_folder($install_folder) != 'valid') {
				echo '<div id = "trackerly-warning" class = "updated fade"><p><strong>' . __('Tracker.ly plugin is configured incorrectly.') . '</strong> ' . sprintf(__('You have to <a href="%1$s">set correct Tracker.ly installation folder</a> for it to work.'), 'plugins.php?page=trackerly-install-folder-config') . '</p></div>';
			}
		}

		add_action('admin_notices', 'trackerly_check_redirect');

		return;
	}
}

function trackerly_cookie_test() {
	if (!empty($_SERVER['REQUEST_URI'])) {
		$url = $_SERVER['REQUEST_URI'];
	} elseif (!empty($_SERVER['HTTP_X_REWRITE_URL'])) {
    	$url = $_SERVER['HTTP_X_REWRITE_URL'];
	} elseif (!empty($_SERVER['QUERY_STRING'])) {
    	$pattern = '`.+' . $_SERVER['SERVER_NAME'] . '(:[0-9]+)?(/.+)`';

	    if (preg_match_all($pattern, $_SERVER['QUERY_STRING'], $url)) {
	    	$url = $url[2][0];
	    } else {
	    	$url = null;
	    }
	}

	if (!empty($url)) {
		if (preg_match('`(.+)\?(.+)`', $url, $res)) {
		    $code = $res[1];
		} else {
			$code = $url;
		}

		if (!empty($code) && $code{0} == '/') {
	    	$code = substr($code, 1);
		}

		if (!empty($code) && substr($code, -1) == '/') {
	    	$code = substr($code, 0, -1);
		}
	} else {
		$code = '';
	}

	$blog_home_url_details = parse_url(strtolower(get_option('home')));
	$dom_				   = str_replace('.', '_', preg_replace('#^www\.#', '', $blog_home_url_details['host']));

	if (!empty($_COOKIE['trackerly_redirect' . md5($dom_ . '_' . $code)])) {
		return true;
	}

	return false;
}

function get_trackerly_redirect_path() {
	$trackerly_install_folder = trackerly_remove_slashes(trackerly_get_install_folder(), 'border');
	$trackerly_redirect_path  = $trackerly_install_folder . '/trackerly.php';
	$script_path			  = trackerly_remove_slashes(dirname($_SERVER['PHP_SELF']), 'border');

	if (!empty($script_path)) {
		$trackerly_redirect_path  = str_repeat('../', count(explode('/', $script_path))) . $trackerly_redirect_path;
	}

	return $trackerly_redirect_path;
}

function check_trackerly_redirect() {
	if (isset($_GET['trackerly-plugin-redirect-test'])) {
		echo 'trackerly-plugin-redirect-test';

		exit();
	}

	if (isset($_GET['trackerly-plugin-get-trackerly-install-folder'])) {
		echo trackerly_get_install_folder();

		exit();
	}

	$trackerly_redirect_path = get_trackerly_redirect_path();

	if (!is_404() || !file_exists($trackerly_redirect_path) || trackerly_cookie_test() || !empty($_POST)) {
		return false;
	}

	return true;
}

function trackerly_redirect() {
	$trackerly_redirect_path = get_trackerly_redirect_path();

	if (check_trackerly_redirect()) {
		if (!defined('DONOTCACHEPAGE')) {
			define('DONOTCACHEPAGE', true);
		}

		if (!defined('TRACKERLY_OUT_SOFT_REDIRECT')) {
			define('TRACKERLY_OUT_SOFT_REDIRECT', true);
		}

		chdir(dirname($trackerly_redirect_path));

		require_once basename($trackerly_redirect_path);

		exit();
	}
}

add_action('wp', 'trackerly_redirect', 1);

?>