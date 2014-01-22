<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * SSO情報画面表示
 *
 * @package     NetCommons
 * @author      Noriko Arai,Ryuji Masukawa
 * @copyright   2006-2007 NetCommons Project
 * @license     http://www.netcommons.org/license.txt  NetCommons License
 * @project     NetCommons Project, supported by National Institute of Informatics
 * @access      public
 */
class Userinf_View_Main_Ssoinfo extends Action
{
    // リクエストパラメータを受け取るため
    var $user_id = null;

    // コンポーネントを使用するため
    var $loginOpenid = null;
    var $authoritiesView = null;
    var $configView = null;
    var $loginView = null;

    // バリデートによりセット
    var $user = null;

    // 値をセットするため
	var $openid_cnt = null;
	var $disp_del_flag = null;
	var $openid_uses = null;

	// Shibboleth
	var $use_external = null;
	var $shibboleth_cnt = null;

	/**
	 * execute実行
	 *
	 * @access  public
	 */
    function execute()
    {
    	$role_auth_id = $this->user['role_authority_id'];
    	$authority = $this->authoritiesView->getAuthorityById($role_auth_id);
    	if($authority === false) {
			return 'error';
		}

		$this->openid_uses = (($openid_uses = $this->loginOpenid->getOpenidUses()) == _ON) ? _ON : _OFF;
		$config = $this->configView->getConfigByCatid(_SYS_CONF_MODID, _SERVER_CONF_CATID);
		$this->use_external = $config['use_external']['conf_value'];

		if($this->openid_uses != _ON && $this->use_external != _ON){
			//OpenID設定,Shibboleth設定無効につき、移行の処理はしない。
			return 'success';
		}

		$this->openid_cnt = $this->loginOpenid->getOpenidUrlCnt($this->user_id);
		$this->shibboleth_cnt = $this->loginView->getLoginByUserIdCnt( $this->user_id, _LOGIN_CERT_SHIBBOLETH );

		//削除ボタンをだすかださないかの判断
		//ログインしている人のuser_id
        $container =& DIContainerFactory::getContainer();
        $session =& $container->getComponent("Session");
		$login_user_id = $session->getParameter('_user_id');
		if (isset($login_user_id)){
			if($login_user_id == $this->user_id){
				//ログインしているの人が本人なので、削除ボタンを出す。
				$this->disp_del_flag = '1';
			} else {
				if ($session->getParameter('_user_auth_id') == _AUTH_ADMIN) {
					//ログインしているの権限が管理者権限者なので、削除ボタンを出す。
					$this->disp_del_flag = '1';
				}
			}
		}

		return 'success';
    }
}
?>
