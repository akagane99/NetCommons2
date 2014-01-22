<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * [openid 認証表示]
 *
 * @package     NetCommons
 * @author      Noriko Arai,Ryuji Masukawa
 * @copyright   2006-2007 NetCommons Project
 * @license     http://www.netcommons.org/license.txt  NetCommons License
 * @project     NetCommons Project, supported by National Institute of Informatics
 * @access      public
 */
class Login_View_Main_Openid_Rp_Tryauth extends Action
{
    // リクエストパラメータを受け取るため
    var $block_id = null;

    // 使用コンポーネントを受け取るため
    var $loginView = null;
    var $loginOpenid = null;

    // 値をセットするため
	var $msg = null;
    var $error = null;
    var $success = null;
    var $pape_policy_uris = null;
    var $openid_uses = null;
    var $oepnid_opids = null;
    var $oepnid_opnames = null;

    /**
     * [OpenID 認証表示]
     *
     * @access  public
     */
    function execute()
    {
		$this->openid_uses = $this->loginOpenid->getOpenidUses();

		if($this->openid_uses == _ON) {

			//global $pape_policy_uris;
			//$this->pape_policy_uris = $pape_policy_uris;

			$this->openid_opids = $this->loginOpenid->getOpenidOpids();
			$this->openid_opnames = $this->loginOpenid->getOpenidOpnames();

			return 'success';
		} else {
			return 'noopenidsupport';
		}
    }
}
?>
