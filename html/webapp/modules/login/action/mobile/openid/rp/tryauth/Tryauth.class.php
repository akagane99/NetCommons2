<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * [openid 認証実行]
 *
 * @package     NetCommons
 * @author      Noriko Arai,Ryuji Masukawa
 * @copyright   2006-2007 NetCommons Project
 * @license     http://www.netcommons.org/license.txt  NetCommons License
 * @project     NetCommons Project, supported by National Institute of Informatics
 * @access      public
 */
class Login_Action_Mobile_Openid_Rp_Tryauth extends Action
{
    // リクエストパラメータを受け取るため
    var $block_id = null;

    // 使用コンポーネントを受け取るため
    var $loginView = null;
    var $loginOpenid = null;
	var $filterChain = null;
	var $actionChain = null;
	var $request = null;

    // 値をセットするため
	var $smartyAssign = null;
	var $redirect_data = null;

	//var $msg = null;
    var $error = null;
    //var $success = null;
    //var $pape_policy_uris = null;
    //var $openid_uses = null;
    //var $oepnid_opids = null;
    //var $oepnid_opnames = null;
	var $mobile = null;
	var $smartphone = null;

    /**
     * [OpenID 認証実行]
     *
     * @access  public
     */
    function execute()
    {
        $this->smartyAssign =& $this->filterChain->getFilterByName('SmartyAssign');

		//モバイルフラグがONか、あるいは、リーダーフラグがONならモバイルとして処理する。
		//
        //モバイルかどうか調べ、モバイルならsuccess。そうでないなら、エラーにする。
        $container =& DIContainerFactory::getContainer();
        $session =& $container->getComponent("Session");
        $mobile_flag = $session->getParameter("_mobile_flag");
        if (!isset($mobile_flag)) {
            $mobileCheck =& MobileCheck::getInstance();
            $mobile_flag = $mobileCheck->isMobile();
            $session->setParameter("_mobile_flag", $mobile_flag);
        }
        if ($mobile_flag != _ON) {
			$reader_flag = intval($session->getParameter("_reader_flag"));
        	if ($reader_flag != _ON) {
				//mobileフラグがoffでかつreaderフラグがoff。PCでここにやってくるのはおかしいので、エラー

				$errorList =& $this->actionChain->getCurErrorList();
				$errorList->add(get_class($this), LOGIN_OPENID_INVALID_TERM);
				return 'error';
			}
        }
		//ここまできたら、モバイルをＯＮにする。
		$this->mobile = true;	

		//次に、スマートフォンかどうかを記録しておく。
        $smartphone_flag = $session->getParameter("_smartphone_flag");
        if (isset($smartphone_flag) && $smartphone_flag == _ON) {
			$this->smartphone = true;
		}

		return $this->run();
    }

	function getOpenIDURL()
	{
    	// Render a default page if we got a submission without an openid value.
    	//if (empty($_REQUEST['openid_identifier'])) {   //GET -> REQUEST
		//	return false;
    	//}
    	//return $_REQUEST['openid_identifier'];	//GET -> REQUEST

        //NCのgetParameterメソッドを使う.
        $openid_identifier = $this->request->getParameter("openid_identifier");
        if(isset($openid_identifier) && is_string($openid_identifier) && strlen($openid_identifier) > 0){
            return $openid_identifier;
        }
        return false;
	}

	function run() {
        $errorList =& $this->actionChain->getCurErrorList(); 	//内部でエラーが発生した時の準備

		$openid = $this->getOpenIDURL(); 						//OpenIDのURL(NCではOP識別子のみ想定)
		if($openid === false){ 
        	$msg = $this->smartyAssign->getLang('openid_expected_openid_url_error');
        	$errorList->add(get_class($this), $msg);
			return 'error';
		}

    	$consumer = $this->loginOpenid->getConsumer(); 			//consumer(=RP)オブジェクトを取得
    	if($consumer===false){
        	$errorList->add(get_class($this), $this->smartyAssign->getLang('openid_error'));
			return 'error';
		}

    	if(!($auth_request = $consumer->begin($openid))){ 		// OpenID 認証 開始
			//OpenID認証要求でないなら、OpenIDを開始しない！
			//
        	$errorList->add(get_class($this), $this->smartyAssign->getLang('openid_error'));
			return 'error';
    	}

    	$sreg_request = Auth_OpenID_SRegRequest::build(			//SReq要求をbuild
                                     array('nickname'), 			// 必須
                                     array('fullname', 'email')); 	// 任意
   		if ($sreg_request) {
        	$auth_request->addExtension($sreg_request);
    	}

		//PAPE ポリシーファイルの指定があれば取得(NCでは未サポート)
		$policy_uris = null;
		//if (isset($_GET['policies'])) {
    	//	$policy_uris = $_GET['policies'];
		//}
    	$pape_request = new Auth_OpenID_PAPE_Request($policy_uris);
    	//if ($pape_request) {
        //	$auth_request->addExtension($pape_request);
    	//}

    	// OpenID認証のためエンドユーザーからのアクセスをOpenIDサーバへリダイレクトする。

		// この認証用のOpenID tokenを保存するので、OpenIDサーバからの返信を
		// チェックできる。

    	// OpenID 1.xの場合、redirectを実行する.
		// OpenID 2の場合、OpenIDサーバへ要求をPOSTするためのJavaScriptフォームを使う.

    	$shouldRedirect = $auth_request->shouldSendRedirect();

    	if ($shouldRedirect) {
        	$redirect_url = $auth_request->redirectURL($this->loginOpenid->getTrustRoot(), $this->loginOpenid->getReturnTo($this->mobile));

        	// If the redirect URL can't be built, display an error message.
        	if (Auth_OpenID::isFailure($redirect_url)) {
        		$errorList->add(get_class($this),
					sprintf($this->smartyAssign->getLang('openid_could_not_redirect_to_server'), $redirect_url->message));
				return 'error';
        	} else {
            	// Send redirect.

				if($this->smartphone === true){
					//スマートフォン
        			$this->redirect_data = "__LocationStart__".$redirect_url."__LocationEnd__";
					return 'mobredirect';
				} else {
					//携帯電話
            		header("Location: ".$redirect_url);
				}
			}
    	} else {
        	// Generate form markup and render it.
        	$form_id = 'openid_message';
        	//$form_html = $auth_request->htmlMarkup($this->loginOpenid->getTrustRoot(), $this->loginOpenid->getReturnTo($this->mobile), false, array('id' => $form_id));
        	$form_html = $auth_request->htmlMarkupForNc($this->loginOpenid->getTrustRoot(), $this->loginOpenid->getReturnTo($this->mobile), false, array('id' => $form_id), $this->mobile, LOGIN_OPENID_CONTINUE_MSG);

        	// Display an error if the form markup couldn't be generated;
        	// otherwise, render the HTML.
        	if (Auth_OpenID::isFailure($form_html)) {
        		$errorList->add(get_class($this),
					sprintf($this->smartyAssign->getLang('openid_could_not_redirect_jsform_to_server'), $form_html->message));
				return 'error';
        	} else {
				if($this->smartphone === true){
					//スマートフォン
        			$this->redirect_data = "__JavaScriptStart__".$form_html."__JavaScriptEnd__";
					return 'mobredirect';
				} else {
					//携帯電話
        			$this->redirect_data = $form_html;
					return 'mobredirect';
				}
        	}
    	}

       	$errorList->add(get_class($this),
			sprintf($this->smartyAssign->getLang('openid_logical_error'))); //ここに飛び込むのは論理エラー
		return 'error';
	}
}
?>
