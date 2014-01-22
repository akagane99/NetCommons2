<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * ログイン処理中画面表示処理アクションクラス
 *
 * @package     NetCommons Action
 * @author      Noriko Arai,Ryuji Masukawa
 * @copyright   2006-2007 NetCommons Project
 * @license     http://www.netcommons.org/license.txt  NetCommons License
 * @project     NetCommons Project, supported by National Institute of Informatics
 * @access      public
 */

class Login_View_Main_Logging extends Action
{
    // コンポーネントを受け取るため
	var $token = null;
	var $session = null;
	var $request = null;
	var $redirect_action = null;

	// 値をセットするため
	var $token_value = "";
	var $submit_time = "3000";

	/**
	 * ローディング画面表示処理アクション
	 *
	 * @access  public
	 */
	function execute()
	{
		$this->token_value = $this->token->getValue();

		$external_id = $this->session->getParameter(array('login_external', 'external_id'));

		return 'success';
	}
}
?>