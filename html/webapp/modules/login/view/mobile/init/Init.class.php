<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * ログインの表示
 *
 * @package     NetCommons
 * @author      Noriko Arai,Ryuji Masukawa
 * @copyright   2006-2007 NetCommons Project
 * @license     http://www.netcommons.org/license.txt  NetCommons License
 * @project     NetCommons Project, supported by National Institute of Informatics
 * @access      public
 */
class Login_View_Mobile_Init extends Action
{
	var $loginOpenid = null;
	var $openid_uses = null;
    var $openid_url = null;

    /**
     * execute実行
     *
     * @access  public
     */
    function execute()
    {
		//openid RP関連
		//$this->openid_uses = $this->loginOpenid->getOpenidUses();
		$this->openid_uses = $this->loginOpenid->getOpenidUsesForMobile();

        //openid_urlがセッションに記録されていたら、取り出す
        $container =& DIContainerFactory::getContainer();
        $request =& $container->getComponent("Request");
        $action = $request->getParameter("action");
        $session =& $container->getComponent("Session");
        $openid_url_and_op = $session->getParameter("_openid_url_and_op");

        if($action == "login_action_mobile_openid_rp_finishauth"){  //for モバイル
            //OPから返されたOpenidURLが不一致または未登録で自動登録するため、内部chainでlogin画面にやってきたケース
        	list($openid_url, $op) = explode('|',$openid_url_and_op);
        	$this->openid_url = $openid_url;
        } else {
            //通常($action==login_view_mobile_init)時	//for モバイル
            //(セッション変数はそのまま)
        }

		return 'success';
    }
}
?>
