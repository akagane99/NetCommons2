<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Embedded DS画面表示処理アクションクラス
 *
 * @package     NetCommons Action
 * @author      Noriko Arai,Ryuji Masukawa
 * @copyright   2006-2007 NetCommons Project
 * @license     http://www.netcommons.org/license.txt  NetCommons License
 * @project     NetCommons Project, supported by National Institute of Informatics
 * @access      public
 */

class Login_View_Main_Discovery extends Action
{
	// リクエストパラメータを受け取るため
	var $block_id = null;

	// コンポーネントを使用するため
	var $session = null;
	var $db = null;
	var $configView = null;

	//Shibboleth
	var $login_wayf_not_auto_login = 0;

	/**
	 * ユーザマッピング画面表示処理アクション
	 *
	 * @access  public
	 */
	function execute()
	{
		$this->login_wayf_not_auto_login = $this->session->getParameter("login_wayf_not_auto_login");
		$this->session->removeParameter("login_wayf_not_auto_login");

		$config = $this->configView->getConfigByCatid(_SYS_CONF_MODID, _SERVER_CONF_CATID);
		$use_external = $config['use_external']['conf_value'];

		// Shibbolethが有効の時のみ画面遷移
		if ( $use_external == _ON ) {
			return 'success';
		}
		else {
			return 'error';
		}
	}
}
?>