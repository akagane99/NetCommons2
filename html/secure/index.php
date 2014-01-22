<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * NetCommons2.0
 *
 * Use Maple - PHP Web Application Framework
 * PHP versions 4 and 5
 *
 * @package     NetCommons.filter
 * @author      Noriko Arai,Ryuji Masukawa
 * @copyright   2006-2007 NetCommons Project
 * @license     http://www.netcommons.org/license.txt  NetCommons License
 * @project     NetCommons Project, supported by National Institute of Informatics
 * @access      public
 */
$actionName = 'login_view_main_externalLogin';

$_REQUEST['action'] = $actionName;
$_GET['action'] = $actionName;

//シボレスのクッキー情報を破棄
if (!empty($_COOKIE)) {
	foreach ($_COOKIE as $name=>$value) {
		if (preg_match("/^_shib/iu", $name)) {
			setcookie($name, '', time()- 3600, "/");
		}
	}
}

require_once dirname(__FILE__) . '/../index.php';
?>