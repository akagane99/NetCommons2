<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * 外部ログイン画面処理アクションクラス
 *
 * @package     NetCommons Action
 * @author      Noriko Arai,Ryuji Masukawa
 * @copyright   2006-2007 NetCommons Project
 * @license     http://www.netcommons.org/license.txt  NetCommons License
 * @project     NetCommons Project, supported by National Institute of Informatics
 * @access      public
 */

class Login_View_Main_ExternalLogin extends Action
{
	// リクエストパラメータを受け取るため
	var $iframe = null;
	var $block_id = null;
	var $page_id = null;
	var $redirect = null;
	var $redirect_action = null;

	// 新規登録に遷移させる際のリクエストパラメータ
	var $user_name = null;
	var $user_name_en = null;
	var $user_name_kana = null;
	var $user_another_name = null;
	var $email = null;
	var $affiliation = null;
	var $affiliation_en = null;
	var $section = null;
	var $section_en = null;
	var $job = null;
	var $job_en = null;

    // コンポーネントを受け取るため
	var $session = null;
	var $loginView = null;
	var $token = null;
	var $preexecuteMain = null;
	var $filterChain = null;
	var $request = null;
	var $db = null;

	// 値をセットするため
	var $token_value = "";

	/**
	 * ユーザマッピング処理アクション
	 *
	 * @access  public
	 */
	function execute()
	{
		//フェデレーション内の一意になるエンティティ
		$persistentid = "";
		$eppn_flag = _ON;
		$login_external_id = "";
		$entityid = "";
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
			$eppn_flag = _OFF;

			$pos = strpos($login_external_id, "!");
			if ($pos !== false) {
				$entityid = substr($login_external_id, 0, $pos);
			}
		} else {
			$pos = strpos($login_external_id, "@");
			if ($pos !== false) {
				$scope = substr($login_external_id, $pos+1);
				$ret = $this->db->selectExecute("shibboleth_idp", array("scope"=>$scope, "use_flag != 0"=>null));
				if ($ret) {
					$entityid = $ret[0]["entityid"];
				}
			}
		}
		$this->session->setParameter(array('login_external', 'external_id'), $login_external_id);
		$this->session->setParameter(array('login_external', 'eppn_flag'), $eppn_flag);
		$this->session->setParameter(array('login_external', 'entityid'), $entityid);

		$user_id = $this->session->getParameter("_user_id");
		$shib_session_value = $this->session->getParameter("shib_session_value");

//		if (isset($user_id) && $user_id != "") {
		if ( !empty( $user_id ) ) {
			//既にログイン済
			$params = array(
				"user_id"=> $user_id
			);
			$users = $this->db->selectExecute("users", $params);
			if($users === false || !isset($users[0])) {
				return 'db_error';
			}
			$redirect_url = BASE_URL.'/'.$users[0]["permalink"];
			$this->request->setParameter("_redirect_url", $redirect_url);
			return 'success';
		}

		//Shibbolethの設定によって、各属性にREDIRECT_が付与されてしまうことがあるためそのプレフィックスを取得する
		$buf_name = "";
		for ($i=0; $i<5; $i++) {
			$prefix = str_repeat("REDIRECT_", $i);
			$buf_name = !empty($_SERVER[$prefix . LOGIN_SHIBBOLETH_GET_PREFIX_ATTRIBUTE]) ? $_SERVER[$prefix . LOGIN_SHIBBOLETH_GET_PREFIX_ATTRIBUTE] : "";
			if ($buf_name != "") { break; }
		}

		if (!empty($buf_name)) {
			//メールアドレス
			if (isset($_SERVER[$prefix . LOGIN_SHIBBOLETH_DEF_MAIL])) {
				$buf_name = $_SERVER[$prefix . LOGIN_SHIBBOLETH_DEF_MAIL];
				$this->session->setParameter(array('login_external', 'email'), $buf_name);
			}
			//氏名(日本語)
			if (isset($_SERVER[$prefix . LOGIN_SHIBBOLETH_DEF_JADISPLAYNAME]) || isset($_SERVER[$prefix . LOGIN_SHIBBOLETH_DEF_JASURNAME]) && isset($_SERVER[$prefix . LOGIN_SHIBBOLETH_DEF_JAGIVENNAME])) {
				if (isset($_SERVER[$prefix . LOGIN_SHIBBOLETH_DEF_JADISPLAYNAME])) {
					$buf_name = $_SERVER[$prefix . LOGIN_SHIBBOLETH_DEF_JADISPLAYNAME];
				} else {
					$buf_name = $_SERVER[$prefix . LOGIN_SHIBBOLETH_DEF_JASURNAME];
					$buf_name .= " ". $_SERVER[$prefix . LOGIN_SHIBBOLETH_DEF_JAGIVENNAME];
				}
				$this->session->setParameter(array('login_external', 'user_name'), $buf_name);
			}
			//所属(日本語)
			if (isset($_SERVER[$prefix . LOGIN_SHIBBOLETH_DEF_JAORGANIZATIONNAME])) {
				$buf_name = $_SERVER[$prefix . LOGIN_SHIBBOLETH_DEF_JAORGANIZATIONNAME];
				$this->session->setParameter(array('login_external', 'affiliation'), $buf_name);
			}
			//部署(日本語)
			if (isset($_SERVER[$prefix . LOGIN_SHIBBOLETH_DEF_JAORGANIZATIONALUNITNAME])) {
				$buf_name = $_SERVER[$prefix . LOGIN_SHIBBOLETH_DEF_JAORGANIZATIONALUNITNAME];
				$this->session->setParameter(array('login_external', 'section'), $buf_name);
			}
			//氏名(英語)
			if (isset($_SERVER[$prefix . LOGIN_SHIBBOLETH_DEF_DISPLAYNAME]) || isset($_SERVER[$prefix . LOGIN_SHIBBOLETH_DEF_SURNAME]) && isset($_SERVER[$prefix . LOGIN_SHIBBOLETH_DEF_GIVENNAME])) {
				if (isset($_SERVER[$prefix . LOGIN_SHIBBOLETH_DEF_DISPLAYNAME])) {
					$buf_name = $_SERVER[$prefix . LOGIN_SHIBBOLETH_DEF_DISPLAYNAME];
				} else {
					$buf_name = $_SERVER[$prefix . LOGIN_SHIBBOLETH_DEF_GIVENNAME];
					$buf_name .= " ".$_SERVER[$prefix . LOGIN_SHIBBOLETH_DEF_SURNAME];
				}
				$this->session->setParameter(array('login_external', 'user_name_en'), $buf_name);
			}
			//所属(英語)
			if (isset($_SERVER[$prefix . LOGIN_SHIBBOLETH_DEF_ORGANIZATIONNAME])) {
				$buf_name = $_SERVER[$prefix . LOGIN_SHIBBOLETH_DEF_ORGANIZATIONNAME];
				$this->session->setParameter(array('login_external', 'affiliation_en'), $buf_name);
			}
			//部署(英語)
			if (isset($_SERVER[$prefix . LOGIN_SHIBBOLETH_DEF_ORGANIZATIONALUNITNAME])) {
				$buf_name = $_SERVER[$prefix . LOGIN_SHIBBOLETH_DEF_ORGANIZATIONALUNITNAME];
				$this->session->setParameter(array('login_external', 'section_en'), $buf_name);
			}
		}

		$user = $this->loginView->getLoginByExternalId( $login_external_id, _LOGIN_CERT_SHIBBOLETH );
		if ($this->redirect != LOGIN_SHIBBOLETH_REDIRECT_SIGNUP && $this->redirect != LOGIN_SHIBBOLETH_REDIRECT_TOP) {

			 	$this->redirect = "";
		}
		$this->session->setParameter(array('login_external', 'redirect'), $this->redirect);
		if (empty($user)) {
			$login_external = $this->session->getParameter(array('login_external'));

			//メールアドレス
			if (!isset($login_external['email']) && $this->email != "") {
				$this->session->setParameter(array('login_external', 'email'), $this->email);
			}
			//氏名(日本語)
			if (!isset($login_external['user_name']) && $this->user_name != "") {
				$this->session->setParameter(array('login_external', 'user_name'), $this->user_name);
			}
			//氏名(カナ)
			if (!isset($login_external['user_name_kana']) && $this->user_name_kana != "") {
				$this->session->setParameter(array('login_external', 'user_name_kana'), $this->user_name_kana);
			}
			//通称等の別名
			if (!isset($login_external['user_another_name']) && $this->user_another_name != "") {
				$this->session->setParameter(array('login_external', 'user_another_name'), $this->user_another_name);
			}

			//所属(日本語)
			if (!isset($login_external['affiliation']) && $this->affiliation != "") {
				$this->session->setParameter(array('login_external', 'affiliation'), $this->affiliation);
			}
			//部署(日本語)
			if (!isset($login_external['section']) && $this->section != "") {
				$this->session->setParameter(array('login_external', 'section'), $this->section);
			}

			//職名(日本語)
			if (!isset($login_external['job']) && $this->job != "") {
				$this->session->setParameter(array('login_external', 'job'), $this->job);
			}
			//氏名(英語)
			if (!isset($login_external['user_name_en']) && $this->user_name_en != "") {
				$this->session->setParameter(array('login_external', 'user_name_en'), $this->user_name_en);
			}
			//所属(英語)
			if (!isset($login_external['affiliation_en']) && $this->affiliation_en != "") {
				$this->session->setParameter(array('login_external', 'affiliation_en'), $this->affiliation_en);
			}
			//部署(英語)
			if (!isset($login_external['section_en']) && $this->section_en != "") {
				$this->session->setParameter(array('login_external', 'section_en'), $this->section_en);
			}
			//職位(英語)
			if (!isset($login_external['job_en']) && $this->job_en != "") {
				$this->session->setParameter(array('login_external', 'job_en'), $this->job_en);
			}

			$login_external = $this->session->getParameter(array('login_external'));
			if ($this->redirect == LOGIN_SHIBBOLETH_REDIRECT_SIGNUP) {
				return 'autoregist';
			} else {
				if ($this->redirect == LOGIN_SHIBBOLETH_REDIRECT_TOP) {
					$view =& $this->filterChain->getFilterByName("View");
					if ($this->redirect == LOGIN_SHIBBOLETH_REDIRECT_TOP) {
						$view->setAttribute("mapping", "location_script:");
					} else {
						$view->setAttribute("mapping", "location_script:?action=login_view_main_mapping");
					}
				}
				return 'mapping';
			}
		}

		$this->token_value = $this->token->getValue();
		if ($this->redirect_action != "" && preg_match("/^([_a-z]+)@?(.*)$/ius", strtolower($this->redirect_action))) {
			$view =& $this->filterChain->getFilterByName("View");
			if (!empty($user_id)) {
				$redirect_action = "?action=".str_replace("@", "&", $this->redirect_action);
			} else {
				$redirect_action = "?action=login_view_main_logging&redirect_action=".$this->redirect_action;
			}
			$view->setAttribute("logging", "location_script:".$redirect_action);
		}
		return 'logging';
	}
}
?>