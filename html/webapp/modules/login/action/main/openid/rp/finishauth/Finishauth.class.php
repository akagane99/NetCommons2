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
class Login_Action_Main_Openid_Rp_Finishauth extends Action
{
	// リクエストパラメータを受け取るため
    var $block_id = null;
	var $login_id = null;
	//var $password = null;
	//var $rememberme = null;

    // 使用コンポーネントを受け取るため
    var $loginView = null;
    var $loginOpenid = null;
	var $usersView = null;
    var $authoritiesView = null;
    var $configView = null;
	var $db = null;

	// コンポーネントを受け取るため. その２
	var $usersAction = null;
	var $session = null;
	var $request = null;
	var $getdata = null;
	var $pagesView = null;
	var $commonMain = null;

    // 値をセットするため
	var $openid_url = null;

    var $error = null;

    var $user_id  				= null;
    var $handle 				= null;
    var $role_authority_id 		= null;
    var $timezone_offset		= null;
    var $last_login_time		= null;
    var $system_flag			= null;
    var $lang_dirname			= null;

    var $role_authority_name	= null;
    var $user_authority_id		= null;
    var $allow_attachment		= null;
    var $allow_htmltag_flag		= null;
    var $allow_video			= null;
    var $allow_layout_flag		= null;
    var $max_size				= null;
	var $mobile 				= null;

    /**
     * [OpenID 認証結果受信]
     *
     * @access  public
     */
    function execute()
    {
		//op情報(=ユーザがえらんだOPのこと)はあとで使うが、ここで退避しておく。
		$op = '';
		$yadis = unserialize($_SESSION['_yadis_services__openid_consumer_']);
		if(isset($yadis) && is_array($yadis) && isset($yadis['starting_url']) && strlen($yadis['starting_url']) > 0){
			$op = $yadis['starting_url'];
		}

		//モバイルならば$this->mobileをtrueにする。	
		//
        $container =& DIContainerFactory::getContainer();
        $session =& $container->getComponent("Session");
        $mobile_flag = $session->getParameter("_mobile_flag");
        if (!isset($mobile_flag)) {
            $mobileCheck =& MobileCheck::getInstance();
            $mobile_flag = $mobileCheck->isMobile();
            $session->setParameter("_mobile_flag", $mobile_flag);
        }
        if ($mobile_flag == _ON) {
			$this->mobile = true;
		} else {
            $reader_flag = intval($session->getParameter("_reader_flag"));
            if ($reader_flag == _ON) {
				$this->mobile = true;
			}
		}

		$identity = $this->run();
		if($identity===false){
			return 'openidautherror'; 	//OpenIDが取得できなかった
		}

		//OPから帰ってきたOpenID URLを一致するデータが、mappingテーブルに存在するかどうか調べる。
        $where = array( 'external_id' => $identity, 'auth_type' => 1);
        $result = $this->db->selectExecute('login_external_user_mapping', $where );
		$user_id = false;
        if(is_array($result) && count($result) > 0 && is_string($result[0]['user_id']) && strlen($result[0]['user_id']) > 0){
			$user_id = $result[0]['user_id']; 	//mappingに一致するOpenIDURLが存在したので、その人をユーザとする。
			$this->user_id = $user_id;
        }
		if($user_id === false){
			//mappingに一致するOpenIDURLが存在しない.
        	$openid_url_and_op = $identity.'|'.$op;
        	$session->setParameter("_openid_url_and_op",$openid_url_and_op); //OPから帰ってきた_openid_url(とop)をセッションに記録
			return 'loginwithopenid'; 	//OpenIDURL割り付けを伴うログイン表示へ
		}
		
		//その人のユーザ情報を取得
		$params = array("user_id" => $user_id);
        $result = $this->db->execute("SELECT user_id,handle,role_authority_id,timezone_offset,last_login_time,system_flag,lang_dirname,login_id,password FROM {users} WHERE user_id=? AND active_flag="._USER_ACTIVE_FLAG_ON,$params,0,null,false);
        if(!is_array($result)){
			//ユーザ情報の取得エラー
			return 'openidautherror';
		}
        if(!isset($result[0][0])) {
			//ユーザ情報の取得エラー
			return 'openidautherror';
		}
		//ユーザのuser_authority_idの有無をチェック
        $authorities =& $this->authoritiesView->getAuthorityById($result[0][2]);
        if($authorities === false || !isset($authorities['user_authority_id'])) {
			//ユーザのuser_authority_idがない!
			return 'openidautherror';
		}

        $config = $this->configView->getConfigByCatid(_SYS_CONF_MODID, _GENERAL_CONF_CATID);
        if($config['closesite']['conf_value'] == _ON &&
			$authorities['user_authority_id'] < $config['closesite_okgrp']['conf_value']) {
			//閉鎖中のサイトにログインしようとした
            $error = LOGIN_ACTION_CLOSESITE;
			return 'openidautherror';
        }

		//mobileAction->setLogin()調査結果.
		//setLogin(user_id,login_id,password,user_name)で、条件があえば、mobile_usersテーブルに、
		//このユーザ情報をdelete&insertしている。
		//
        $mobileAction =& $container->getComponent("mobileAction");
        //////$mobileAction->setLogin($result[0][0], $attributes[0], $params['password'], $result[0][1]);
        $mobileAction->setLogin($result[0][0], $result[0][7], $result[0][8], $result[0][1]);

        //$this->user_id 				= $result[0][0];
        $this->handle  				= $result[0][1];
        $this->role_authority_id 	= $result[0][2];
        $this->timezone_offset		= $result[0][3];
        $this->last_login_time		= $result[0][4];
        $this->system_flag			= $result[0][5];
        $this->lang_dirname			= $result[0][6];
        $this->login_id				= $result[0][7];
        //$this->password				= $result[0][8];  //setLogin()用にSELECTの射影に追加しただけ。$this登録不要。

        $this->role_authority_name	= $authorities['role_authority_name'];
        $this->user_authority_id	= $authorities['user_authority_id'];
        $this->allow_attachment		= $authorities['allow_attachment'];
        $this->allow_htmltag_flag	= $authorities['allow_htmltag_flag'];
        $this->allow_video			= $authorities['allow_video'];
        $this->allow_layout_flag	= $authorities['allow_layout_flag'];
        $this->max_size				= $authorities['max_size'];

		return $this->doLoginAndDispPage();
    }

	function escape($thing) {
    	return htmlentities($thing);	//エスケープ処理をする。
	}

	function run() {
       	$esc_identity = false;
    	$consumer = $this->loginOpenid->getConsumer();
		if($consumer===false){
			return false;
		}

    	// OPサーバの応答を使い認証処理を完了させる。
    	$return_to = $this->loginOpenid->getReturnTo($this->mobile);	//リターンパスを取得
    	$response = $consumer->complete($return_to);

    	// 応答状態をチェックする。
    	if ($response->status == Auth_OpenID_CANCEL) {
        	// This means the authentication was cancelled.
        	$msg = 'Verification cancelled.';
    	} else if ($response->status == Auth_OpenID_FAILURE) {
        	// Authentication failed; display the error message.
        	$msg = "OpenID authentication failed: " . $response->message;
    	} else if ($response->status == Auth_OpenID_SUCCESS) {
        	// This means the authentication succeeded; extract the
        	// identity URL and Simple Registration data (if it was
        	// returned).
        	$openid = $response->getDisplayIdentifier();
        	$esc_identity = $this->escape($openid);
			$this->openid_url = $openid;
        	$success = sprintf('You have successfully verified ' .
            	               '<a href="%s">%s</a> as your identity.',
               	            $esc_identity, $esc_identity);

        	if ($response->endpoint->canonicalID) {
            	$escaped_canonicalID = $this->escape($response->endpoint->canonicalID);
            	$success .= '  (XRI CanonicalID: '.$escaped_canonicalID.') ';
        	}

        	$sreg_resp = Auth_OpenID_SRegResponse::fromSuccessResponse($response);

        	$sreg = $sreg_resp->contents();

        	if (@$sreg['email']) {
            	$success .= "  You also returned '".$this->escape($sreg['email']).  "' as your email.";
        	}

        	if (@$sreg['nickname']) {
				$success .= "  Your nickname is '".$this->escape($sreg['nickname']).  "'.";
        	}

        	if (@$sreg['fullname']) {
            	$success .= "  Your fullname is '".$this->escape($sreg['fullname']).  "'.";
        	}

			$pape_resp = Auth_OpenID_PAPE_Response::fromSuccessResponse($response);

			if ($pape_resp) {
            	if ($pape_resp->auth_policies) {
                	$success .= "<p>The following PAPE policies affected the authentication:</p><ul>";

                	foreach ($pape_resp->auth_policies as $uri) {
                    	$escaped_uri = $this->escape($uri);
                    	$success .= "<li><tt>$escaped_uri</tt></li>";
                	}

                	$success .= "</ul>";
            	} else {
                	$success .= "<p>No PAPE policies affected the authentication.</p>";
            	}

            	if ($pape_resp->auth_age) {
                	$age = $this->escape($pape_resp->auth_age);
                	$success .= "<p>The authentication age returned by the " .  "server is: <tt>".$age."</tt></p>";
            	}

            	if ($pape_resp->nist_auth_level) {
                	$auth_level = $this->escape($pape_resp->nist_auth_level);
                	$success .= "<p>The NIST auth level returned by the " .  "server is: <tt>".$auth_level."</tt></p>";
            	}

			} else {
            	$success .= "<p>No PAPE response was sent by the provider.</p>";
			}
    	}

    	//include 'index.php';
		return $esc_identity;
	}

    /**
     * ログインモジュール-OpenID認証完了後、loginモジュール:ログインボタン押下時をトレースする。
     *
     * @access  public
     */
	function doLoginAndDispPage()
	{
		$retstr = 'success';
    	$mobile_flag = $this->session->getParameter("_mobile_flag");
    	$this->session->setParameter("_user_id",$this->user_id);
    	$this->session->setParameter("_login_id",$this->login_id);
		$this->session->setParameter("_site_id",0);
		$this->session->setParameter("_handle",$this->handle);
		$this->session->setParameter("_role_auth_id",$this->role_authority_id);
		$this->session->setParameter("_role_authority_name",$this->role_authority_name);
		$this->session->setParameter("_timezone_offset",$this->timezone_offset);

		//role_authority_idよりデフォルト権限をセッションにセット
		$this->session->setParameter("_user_auth_id",$this->user_authority_id);

		if(!empty($this->lang_dirname)) {
			$this->session->setParameter("_lang",$this->lang_dirname);
		}

		// 権限管理の設定情報をセッションに保存
		$this->session->setParameter("_allow_attachment_flag", $this->allow_attachment);
	    $this->session->setParameter("_allow_htmltag_flag", $this->allow_htmltag_flag);
	    $this->session->setParameter("_allow_video_flag", $this->allow_video);
	    $this->session->setParameter("_allow_layout_flag", $this->allow_layout_flag); 	//レイアウトできるかどうか(ヘッダー,左右カラムの表示非表示切り替え).この値がON＋主担であれば切り替え可能
	    $this->session->setParameter("_private_max_size", $this->max_size); 			// プライベートスペースに対するアップロードの最大容量

		//最終ログイン日時、前回ログイン日時更新
	    if(!empty($this->user_id)) {
			$params = array(
				"last_login_time" => timezone_date(),
				"previous_login_time" => $this->last_login_time
			);
			$where_params = array("user_id" => $this->user_id);
			$result = $this->usersAction->updUsers($params, $where_params, false);
			if($result === false) {
				//DBエラー
				return ($retstr = 'db_error');
			}
	    }
		if ($mobile_flag == _ON) {
			;	//モバイルフラグがONの特は、処理しない。
		} else {
			$config = $this->configView->getConfigByCatid(_SYS_CONF_MODID, _GENERAL_CONF_CATID);
			$path = ini_get("session.cookie_path");
			$domain = ini_get("session.cookie_domain");
			$secure = ini_get("session.cookie_secure");

			$lifetime = time() + _AUTOLOGIN_LIFETIME; 	// 1 week default
			$autologin_login_cookie_name = $config['autologin_login_cookie_name']['conf_value'];
			$autologin_pass_cookie_name = $config['autologin_pass_cookie_name']['conf_value'];
			if(($config['autologin_use']['conf_value'] == _AUTOLOGIN_LOGIN_ID || $config['autologin_use']['conf_value'] == _AUTOLOGIN_OK) &&
				$autologin_login_cookie_name != "") {
				setcookie($autologin_login_cookie_name, $this->login_id, $lifetime, $path, $domain, $secure);
			}

			//OpenIDの場合、remembermeは使わない
			//
			//if($this->rememberme == _ON && $config['autologin_use']['conf_value'] == _AUTOLOGIN_OK &&
			//	$autologin_login_cookie_name != "" && $autologin_pass_cookie_name != "") {
			//	setcookie($autologin_pass_cookie_name, md5($this->password), $lifetime, $path, $domain, $secure);
			//	setcookie($autologin_login_cookie_name, $this->login_id, $lifetime, $path, $domain, $secure);
			//}
		}

		//
		// 固定リンク用
		//
		$result = $this->db->selectExecute("pages", array("space_type IN ("._SPACE_TYPE_PUBLIC.","._SPACE_TYPE_GROUP.") " => null,
									"private_flag" => _ON, "thread_num" => 0, "insert_user_id" => $this->user_id), array("default_entry_flag" => "DESC"), 2, 0);

		$_permalink_flag = $this->session->getParameter("_permalink_flag");

		if(isset($result[0])) {
			$this->session->setParameter("_self_myroom_page", $result[0]);
		}
		//if(isset($result[1])) {
		//	$this->session->setParameter("_self_my_page", $result[1]);
		//}
		$_redirect_url = $this->request->getParameter("_redirect_url");
		if((!isset($_redirect_url) || $_redirect_url == "")) {
			//  && isset($result[0])

			//
			// リダイレクト先がないならば、デフォルト表示するページIDを取得
			//
			$active_page = $this->_getDefaultPage();
			if($active_page['permalink'] != "") {
				$active_page['permalink'] .= '/';
			}
			//$this->redirect_url = BASE_URL.'/'.$active_page['permalink'];
			if($_permalink_flag) {
				$this->request->setParameter("_redirect_url", $active_page['permalink']);
			} else {
				$this->request->setParameter("_redirect_url", "?".ACTION_KEY."=".$active_page['action_name']."&page_id=".$active_page['page_id'].$active_page['parameters']);
			}
		}

    	return 'success';
    }
    /**
     * デフォルトのpage_idを取得
     *
     * @return array page
     * @access  public
     */
    function _getDefaultPage() {
    	$page = array();
    	$page_id_arr = array();
    	$_user_id = $this->session->getParameter("_user_id");
		$config = $this->getdata->getParameter("config");
		$first_choice_startpage = intval($config[_GENERAL_CONF_CATID]['first_choice_startpage']['conf_value']);
  		$second_choice_startpage = intval($config[_GENERAL_CONF_CATID]['second_choice_startpage']['conf_value']);
  		$third_choice_startpage = intval($config[_GENERAL_CONF_CATID]['third_choice_startpage']['conf_value']);
		$default_private_space = 0;

  		if($first_choice_startpage == 0) {
  			//指定なし
  			;
  		} elseif($first_choice_startpage != -1) {
  			$page_id_arr[] = $first_choice_startpage;
  		} else {
  			//プライベートスペース
  			$default_private_space = 1;
  		}
  		if($second_choice_startpage == 0) {
  			//指定なし
  			;
  		} elseif($second_choice_startpage != -1) {
  			$page_id_arr[] = $second_choice_startpage;
  		} else {
  			//プライベートスペース
  			if($default_private_space == 0) $default_private_space = 2;
  		}
  		if($third_choice_startpage == 0) {
  			//指定なし
  			;
  		} elseif($third_choice_startpage != -1) {
  			$page_id_arr[] = $third_choice_startpage;
  		} else {
  			//プライベートスペース
  			if($default_private_space == 0) $default_private_space = 3;
  		}

  		$buf_pages_obj =& $this->pagesView->getPageById($page_id_arr);
  		$buf_page_obj = "";
		$show_page_id = 0;
		$set_default_private_space = 4;
		foreach($buf_pages_obj as $page_obj) {
			if(($page_obj['space_type'] == _SPACE_TYPE_PUBLIC && $page_obj['display_flag'] == _ON) ||
					($page_obj['space_type'] == _SPACE_TYPE_GROUP && $page_obj['default_entry_flag'] == _ON && $page_obj['display_flag'] == _ON && $_user_id != "0" &&
					(!isset($page_obj['role_authority_id']) || $page_obj['role_authority_id'] != _ROLE_AUTH_OTHER)) ||
					($page_obj['space_type'] == _SPACE_TYPE_GROUP && $page_obj['display_flag'] == _ON && $_user_id != "0") && (isset($page_obj['authority_id']))) {
				//閲覧できるpage_id有
				if($first_choice_startpage == $page_obj['page_id']) {
					$show_page_id = $page_obj['page_id'];
					$buf_pages_obj[$show_page_id] = $page_obj;
					$set_default_private_space = 1;
				} else if($second_choice_startpage == $page_obj['page_id'] && $set_default_private_space > 2) {
					$show_page_id = $page_obj['page_id'];
					$buf_pages_obj[$show_page_id] = $page_obj;
					$set_default_private_space = 2;
				} else if($third_choice_startpage == $page_obj['page_id'] && $set_default_private_space > 3) {
					$show_page_id = $page_obj['page_id'];
					$buf_pages_obj[$show_page_id] = $page_obj;
					$set_default_private_space = 3;
				}
			}
		}

		//優先順位がプライベートスペースのほうが高い場合
		if(($set_default_private_space == _OFF || $set_default_private_space > $default_private_space) && $default_private_space != 0 && $_user_id != "0") {
			//マイページからpage_id取得
			$buf_page_obj_private =& $this->pagesView->getPrivateSpaceByUserId($_user_id, 1);
			if($buf_page_obj_private) {
				$show_page_id = $buf_page_obj_private[0]['page_id'];
				$buf_pages_obj[$show_page_id] = $buf_page_obj_private[0];
			}
		}
		if($show_page_id != 0) {
			$page_id = $show_page_id;
			$page = $buf_pages_obj[$page_id];
		}
		if(isset($page_id) && $page_id != 0){
			if(isset($buf_pages_obj[$page_id]) && $buf_pages_obj[$page_id]['node_flag'] == _ON && $buf_pages_obj[$page_id]['action_name'] == "") {
				//指定したpage_idがnodeであるならば
				//nodeの子供のうち最も近いページIDを取得
				if($buf_pages_obj[$page_id]['root_id'] == 0) {
					$root_id = $buf_pages_obj[$page_id]['page_id'];
				} else {
					$root_id = $buf_pages_obj[$page_id]['root_id'];
				}
				$where_params = array(
					"action_name!=''"=>null,
					"display_sequence!=0"=>null,
					"display_flag"=>_ON,
					"root_id"=>$root_id,
					"display_position"=>$buf_pages_obj[$page_id]['display_position'],
					"thread_num>".$buf_pages_obj[$page_id]['thread_num']=>null
				);
				$order_params =array(
										"{pages}.thread_num" => "ASC",
										"{pages}.display_sequence" => "ASC"
									);

				$buf_pages_obj_child =& $this->pagesView->getShowPagesList($where_params, $order_params, 1, 0, array($this->pagesView, 'fetchcallback'));
				if($buf_pages_obj_child && isset($buf_pages_obj_child[0])) {
					//親ノードの子供
					$page_id = $buf_pages_obj_child[0]['page_id'];
					$buf_pages_obj[$page_id] = $buf_pages_obj_child[0];
					$page = $buf_pages_obj[$page_id];
				}
			}
		}

		if(!isset($page_id)) {
			//デフォルトページがみつからない
			//見れるページIDを取得
			$where_params = array(
				"action_name!=''"=>null,
				"display_flag"=>_ON,
				"display_sequence!"=>0
			);
			$order_params =array(
									"{pages}.thread_num" => "ASC",
									"{pages}.display_sequence" => "ASC"
									);
			$buf_pages_obj_sub =& $this->pagesView->getShowPagesList($where_params, $order_params, 1, 0, array($this->pagesView, 'fetchcallback'));

			//少なくともバブリックページは１ページはあるとして処理
			$page_id = $buf_pages_obj_sub[0]['page_id'];
			$buf_pages_obj[$page_id] = $buf_pages_obj_sub[0];
			$page = $buf_pages_obj[$page_id];
		}
		return $page;
    }
}
?>
