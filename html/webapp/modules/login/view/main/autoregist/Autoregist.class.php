<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

 /**
 * 会員登録を受け付ける場合の自動登録画面表示
 *
 * @package     NetCommons Action
 * @author      Noriko Arai,Ryuji Masukawa
 * @copyright   2006-2007 NetCommons Project
 * @license     http://www.netcommons.org/license.txt  NetCommons License
 * @project     NetCommons Project, supported by National Institute of Informatics
 * @access      public
 */

class Login_View_Main_Autoregist extends Action
{
	// リクエストパラメータを受け取るため
	var $active_center = null;

	// Filterによりセット
	var $user_name = null;
	var $email = null;

	// コンポーネントを使用するため
	var $configView = null;
	var $usersView = null;
	var $actionChain = null;
	var $session = null;

	// 値をセットするため
	var $items = null;
	var $autoregist_use_items = array();
	var $autoregist_use_items_req = array();
	var $autoregist_disclaimer = "";
	var $autoregist_input_key = "";

	var $user_name_jp = "";
	var $user_name_en = "";
	var $affiliation = "";
	var $section = "";

    /**
     * 会員登録を受け付ける場合の自動登録画面表示
     *
     * @access  public
     */
    function execute()
    {
    	$config = $this->configView->getConfigByCatid(_SYS_CONF_MODID, _ENTER_EXIT_CONF_CATID);

    	if($config['autoregist_use']['conf_value'] != _ON) {
    		$errorList =& $this->actionChain->getCurErrorList();
    		$errorList->add(get_class($this), LOGIN_MES_ERR_AUTOREGIST);
    		return 'error';
    	}
    	$_system_user_id = $this->session->getParameter("_system_user_id");
    	$this->items =& $this->usersView->getShowItems($_system_user_id, _AUTH_ADMIN, array("display_flag"=>_ON), true);
    	if($this->items === false) return 'error';

    	$autoregist_use_items = explode("|", $config['autoregist_use_items']['conf_value']);
    	foreach($autoregist_use_items as $autoregist_use_item) {
    		$buf_arr = explode(":", $autoregist_use_item);
    		if(isset($buf_arr[0]) && $buf_arr[0] != "") {
    			$this->autoregist_use_items[$buf_arr[0]] = $buf_arr[0];
    			$this->autoregist_use_items_req[$buf_arr[0]] = $buf_arr[1];
    		}
    	}

    	$this->autoregist_disclaimer = $config['autoregist_disclaimer']['conf_value'];
    	$this->autoregist_use_input_key = $config['autoregist_use_input_key']['conf_value'];

		//Shibbolethの値をデフォルトとして設定
		$login_external = $this->session->getParameter(array('login_external'));
		if (!empty($login_external)) {
			if (isset($login_external["user_name"])) {
				$this->user_name_jp = $login_external["user_name"];
			}
			if (isset($login_external["user_name_en"])) {
				$this->user_name_en = $login_external["user_name_en"];
			}
			if ($this->user_name == "") {
			}
			if ($this->email == "" && isset($login_external["email"])) {
				$this->email = $login_external["email"];
			}
			if (isset($login_external["affiliation"])) {
				$this->affiliation = $login_external["affiliation"];
			}
			if (isset($login_external["section"])) {
				$this->section = $login_external["section"];
			}
		}

		if($this->active_center != null) {
			$this->session->removeParameter(array('login_external', 'redirect'));
			return 'success_center';
		}
		return 'success';
	}
}
?>
