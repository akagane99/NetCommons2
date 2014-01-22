<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * 外部SSO用識別子存在チェックバリデータクラス
 *
 * @package     NetCommons.validator
 * @author      Noriko Arai,Ryuji Masukawa
 * @copyright   2006-2007 NetCommons Project
 * @license     http://www.netcommons.org/license.txt  NetCommons License
 * @project     NetCommons Project, supported by National Institute of Informatics
 * @access      public
 */
class Login_Validator_ExternalIdExists extends Validator
{
	/**
	 * 外部SSO用識別子存在チェック
	 *
	 * @param mixed $attributes チェックする値
	 * @param string $errStr エラー文字列
	 * @param array $params オプション引数
	 * @return string エラー文字列(エラーの場合)
	 * @access public
	 */
	function validate($attributes, $errStr, $params)
	{
		//Shibbolethの設定によって、eppn属性にREDIRECT_が付与されてしまうことがある
		$persistentid = "";
		for ($i=0; $i<5; $i++) {
			$prefix = str_repeat("REDIRECT_", $i);
			$login_external_id = !empty($_SERVER[$prefix.LOGIN_SHIBBOLETH_EXTERNAL_ID_NAME]) ?
											$_SERVER[$prefix.LOGIN_SHIBBOLETH_EXTERNAL_ID_NAME] : "";
			if (!empty($_SERVER[$prefix.LOGIN_SHIBBOLETH_DEF_EDUPERSONTARGETEDID])) {
				$persistentid = $_SERVER[$prefix.LOGIN_SHIBBOLETH_DEF_EDUPERSONTARGETEDID];
			}
			if ($login_external_id != "") { break; }
		}
		if ($login_external_id == "" && $persistentid != "") {
			$login_external_id = $persistentid;
		}

		$container =& DIContainerFactory::getContainer();
		$session =& $container->getComponent("Session");

		if (empty($login_external_id)) {
			$commonMain =& $container->getComponent("commonMain");
			$session->setParameter("login_wayf_not_auto_login", _ON);
			$url = BASE_URL_HTTPS."/Shibboleth.sso/Logout?return=".rawurlencode(BASE_URL);
			$commonMain->redirectHeader($url, 10, $errStr);
			return $errStr;
		}
		$session->removeParameter("login_wayf_not_auto_login");
		return;
	}
}
?>