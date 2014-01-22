<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * ログインモジュール
 *
 * @package     NetCommons
 * @author      Noriko Arai,Ryuji Masukawa
 * @copyright   2006-2007 NetCommons Project
 * @license     http://www.netcommons.org/license.txt  NetCommons License
 * @project     NetCommons Project, supported by National Institute of Informatics
 * @access      public
 */
class Login_View_Main_Init extends Action
{
	// リクエストパラメータを受け取るため
	var $error_mes = null;
	var $block_id = null;
	var $http = null;

	var $prefix_id_name = null;

	// コンポーネントを使用するため
	var $session = null;
	var $configView = null;
	var $token = null;
	var $filterChain = null;
	var $request = null;
	var $loginOpenid = null;

	// 値をセットするため
	var $login_id = "";
	var $autologin = _OFF;
	var $ssl_base_url = "";
	var $token_value = "";
	var $closesite = null;
	var $formClassName = null;
	var $sslOuterClassName = null;
	var $redirect_url = "";
	var $use_ssl = 0;
	var $iframeSsl = null;
	var $dialog_name = null;
	var $autocomplete = null;

	var $openid_uses = null;
	var $openid_url = null;

	//Shibboleth
	var $isMapping = null;
	var $iframeSrc = "";
	var $use_external = 0;

	/**
	 * ログインモジュール
	 *
	 * @access  public
	 */
	function execute()
	{
		$this->redirect_url = $this->request->getParameter('_redirect_url');
		$this->redirect_url =  str_replace("#", "@@", $this->redirect_url);
		$this->redirect_url =  str_replace("&", "@", $this->redirect_url);
		$this->redirect_url =  str_replace("?action=", "?_sub_action=", $this->redirect_url);

		//if(!isset($_SERVER['HTTPS']) && $this->session->getParameter("_user_id")) {
		if($this->session->getParameter("_user_id")
			&& $this->block_id != 0) {
			return 'success_login';
		}

		$config = $this->configView->getConfigByCatid(_SYS_CONF_MODID, _GENERAL_CONF_CATID);
		$this->use_ssl = $config['use_ssl']['conf_value'];
		if($config['autologin_use']['conf_value'] != _AUTOLOGIN_NO) {
			// ログインIDのみ取得
			$autologin_login_cookie_name = $config['autologin_login_cookie_name']['conf_value'];
			$this->login_id = isset($_COOKIE[$autologin_login_cookie_name]) ? $_COOKIE[$autologin_login_cookie_name] : "";
		}
		if($config['autologin_use']['conf_value'] == _AUTOLOGIN_OK && empty($this->isMapping)) {
			$this->autologin = _ON;
		}
		if (empty($this->block_id)) {
			$this->formClassName = 'login_popup';
		} else {
			$this->formClassName = 'login_block';
		}
		if ($this->autologin == _ON) {
			$this->sslOuterClassName = 'login_ssl_outer_rememberme';
		} else {
			$this->sslOuterClassName = 'login_ssl_outer';
		}

		// shibboleth関連
		$config_server = $this->configView->getConfigByCatid(_SYS_CONF_MODID, _SERVER_CONF_CATID);
		$this->use_external = $config_server['use_external']['conf_value'];
		if ($this->use_external == _ON) {
			if (LOGIN_USE_SHIBBOLETH_DS == _ON) {
				$this->sslOuterClassName = 'login_ssl_outer_rememberme';
			} else {
				$this->sslOuterClassName = 'login_ssl_outer';
			}
			$this->autologin = _OFF;
		}

		$this->autocomplete = 'off';
		if (!empty($config['login_autocomplete']['conf_value'])) {
			$this->autocomplete = 'on';

			if (!ereg('Chrome', $_SERVER['HTTP_USER_AGENT'])
				&& ereg('Safari', $_SERVER['HTTP_USER_AGENT'])) {
				$this->login_id = '';
			}
		}

		$smartyAssign =& $this->filterChain->getFilterByName('SmartyAssign');
		$this->dialog_name = $smartyAssign->getLang('login');
		$this->closesite = $config['closesite']['conf_value'];
		$this->token_value = $this->token->getValue();

		$isBaseUrlHttps = preg_match("/^https:\/\//i", BASE_URL);
		$view =& $this->filterChain->getFilterByName("View");

		//openid RP関連
        $this->openid_uses = $this->loginOpenid->getOpenidUses();

		//openid_urlがセッションに記録されていたら、取り出す
		if($this->request->getParameter("action") == "login_action_main_openid_rp_finishauth"){	//forPC
        	$openid_url_and_op = $this->session->getParameter("_openid_url_and_op");
        	if(is_string($openid_url_and_op) && strlen($openid_url_and_op) > 0){
				list($openid_url, $op) = explode('|',$openid_url_and_op);
       			$this->openid_url = $openid_url;
			}
		} else if($this->request->getParameter("finauth_chain") == "1"){	//for PC (loginのみhttpsでiframe)
        	$openid_url_and_op = $this->session->getParameter("_openid_url_and_op");
        	if(is_string($openid_url_and_op) && strlen($openid_url_and_op) > 0){
				list($openid_url, $op) = explode('|',$openid_url_and_op);
       			$this->openid_url = $openid_url;
			}
		} else {
			//通常ケース.なにもしない
		}

		if (!empty($this->http)) {
			$this->iframeSsl = true;
			$view->setAttribute('define:theme', 0);

			return 'success';
		}

		if (!empty($this->error_mes)) {
			$view->setAttribute('define:close_popup_func',
								'location.href=\'' . BASE_URL . INDEX_FILE_NAME . '\';'
								. 'return false;');
		}

		if ($config['use_ssl']['conf_value'] != 0
			&& !$isBaseUrlHttps
			&& empty($this->http)) {
			$this->ssl_base_url = preg_replace("/^http:\/\//i","https://", BASE_URL);
			$this->redirect_url = preg_replace("/^http:\/\//i","https://", $this->redirect_url);
			$this->iframeSrc = $this->ssl_base_url . INDEX_FILE_NAME
								. '?action=login_view_main_init'
								. '&block_id=' . $this->block_id
								. '&parent_id=' . $this->session->getParameter('_id')
								. '&error_mes=' . $this->error_mes
								. '&http=' . _ON
								. '&_redirect_url=' . $this->redirect_url;

			return 'success_ssl';
		}

		$this->sslOuterClassName = '';

		return 'success';
	}
}
?>