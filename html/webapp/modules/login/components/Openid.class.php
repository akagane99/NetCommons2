<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * ログインモジュール オープンＩＤ用クラス
 *
 */
class Login_Components_Openid
{
    /**
     * @var ConfigViewオブジェクトを保持
     *
     * @access  private
     */
    var $_config = null;

	/**
	 * @var DIコンテナを保持
	 *
	 * @access	private
	 */
	var $_container = null;

	/**
	 * @var 各種値保持
	 *
	 * @access	private
	 */
	var $_error = null;  //エラーメッセージ

	/**
	 * コンストラクター
	 *
	 * @access	public
	 */
	function Login_Components_Openid() 
	{
		$log =& LogFactory::getLog();
		$log->trace("component login Openid のコンストラクタが実行されました", "loginOpenid#Login_Openid");

		$this->_container =& DIContainerFactory::getContainer();
		$this->_config =& $this->_container->getComponent("configView");

		// OpenID ライブラリを利用するための準備作業
		//

        //php-openidライブラリのpathをinclude_pathに追加
		define('OPENID_LIB_DIR', BASE_DIR."/".MAPLE_DIR."/includes/php-openid-master");
        ini_set("include_path", (ini_get("include_path").PATH_SEPARATOR.OPENID_LIB_DIR));
        $inc_path = ini_get("include_path");

		$this->doIncludes();		//OpenID ライブラリを利用するための各種include実施

		global $pape_policy_uris;					//PAPEファイルのグローバル定義
		$pape_policy_uris = array(
            PAPE_AUTH_MULTI_FACTOR_PHYSICAL,
            PAPE_AUTH_MULTI_FACTOR,
            PAPE_AUTH_PHISHING_RESISTANT
        );
	}
	
	/**
	 * OpenID利用が可能かシステム設定を調べ返す
	 * @param 
	 * @return 
	 * @access	public
	 */
	function getOpenidUses() {
		$openid_uses_arry = $this->_config->getConfigByConfname(_SYS_CONF_MODID, 'openid_uses');
		return ( ( is_array($openid_uses_arry) && isset($openid_uses_arry['conf_value']) ) ? $openid_uses_arry['conf_value'] : false );
	}

	/**
	 * 携帯電話専用でOpenIDが利用がどうかの設定を調べる。
	 * @param 
	 * @return 
	 * @access	public
	 */
	function getMobileUseKetaiOpenid() {
        $modulesView =& $this->_container->getComponent("modulesView");
        $mobile_obj = $modulesView->getModuleByDirname("mobile");
        $config = $this->_config->getConfig($mobile_obj["module_id"], false);
        if ($config == false) {
			$ret = _OFF;
		} else {
        	$ret = ($config["mobile_use_ketai_openid"]["conf_value"] == _ON) ? _ON : _OFF;
		}
		return $ret;
	}

	/**
	 * モバイル用OpenID利用が可能かシステム設定を調べ返す(携帯電話、スマートフォン共用)
	 * @param 
	 * @return 
	 * @access	public
	 */
	function getOpenidUsesForMobile() {
        //readerも考慮しつつ、モバイルかどうか調べる。
        $session =& $this->_container->getComponent("Session");
        $mobile_flag = $session->getParameter("_mobile_flag");
        if (!isset($mobile_flag)) {
			//モバイルかどうか未定なので、調べてセッションにセットしておく。
            $mobileCheck =& MobileCheck::getInstance();
            $mobile_flag = $mobileCheck->isMobile();
            $session->setParameter("_mobile_flag", $mobile_flag);
        }
        if ($mobile_flag != _ON) {
			//モバイルじゃない、、、つまりPCなのだが、リーダーってこともあるので調べる。
			//
            $reader_flag = intval($session->getParameter("_reader_flag"));
            if ($reader_flag != _ON) {
                //mobileフラグがoffでかつreaderフラグがoff。つまり、ＰＣってこと。
            } else {
				//リーダーってことは、PCだけどモバイル扱いにしないといけない。。。
        		$mobile_flag = _ON;	//強制的にモバイルとしてしまう。
			}
        }

        //ここまできたら、$mobile_flagを、リーダーを考慮したPC、モバイルの判断として使える。
		//
		if($mobile_flag != _ON){
			//PCブラウザなので、サーバ設定のOpenID設定をつかう。
			return $this->getOpenidUses();
		} else {
			//モバイルだが、さらに携帯かスマホかを区別する。
            $smartphone_flag = $session->getParameter("_smartphone_flag");
            if($smartphone_flag == _ON){
				//スマートフォンなので、OpenIDに関してはPCブラウザと同じに扱う。つまり、サーバ設定のOpenID設定をそのまま使う。
				return $this->getOpenidUses();
			} else {
				//携帯電話なので、サーバ設定のOpenID設定とモバイル管理の携帯電話用OpenID設定の論理積を使う。
				//
				return (($this->getOpenidUses() == _ON && $this->getMobileUseKetaiOpenid() == _ON) ? _ON : _OFF);
			}
		}
	}

	/**
	 * OpenID利用可の時、OP識別子が登録されているかを調べ、登録されていれば返す。
	 * @param 
	 * @return 
	 * @access	public
	 */
	function getOpenidOpids() {
		if($this->getOpenidUses() != _ON) {
			return false;
		}

		$opids_ary = $this->_config->getConfigByConfname(_SYS_CONF_MODID, 'openid_opids');
		if(is_array($opids_ary) && isset($opids_ary['conf_value'])){
			$opids = array();
			foreach((explode('|',$opids_ary['conf_value'])) as $opid){
        		$opids[] = trim($opid);
    		}
			return $opids;
		}
		return false;
	}

	/**
	 * OpenID利用可の時、OP名称が登録されているかを調べ、登録されていれば返す。
	 * @param 
	 * @return 
	 * @access	public
	 */
	function getOpenidOpnames() {
		if($this->getOpenidUses() != _ON) {
			return false;
		}

		$opnames_ary = $this->_config->getConfigByConfname(_SYS_CONF_MODID, 'openid_opnames');
		if(is_array($opnames_ary) && isset($opnames_ary['conf_value'])){
			$opnames = array();
			foreach((explode('|',$opnames_ary['conf_value'])) as $opname){
        		$opnames[] = trim($opname);
    		}
			return $opnames;
		}
		return false;
	}

	/**
	 * OpenID RP関連でエラーが発生した時、tryauth画面にエラーを表示するための関数
	 * @param  message メッセージ
	 * @return 
	 * @access	public
	 */
	function displayError($message) {
    	$this->_error = $message;
    	//include 'index.php';

		//TODO: 何等かの方法で、login_view_main_openid_rp_tryauth画面を表示させる必要がある。
	}

	/**
	 * OpenID RP関連のインクルードをまとめて実行するサブルーチン
	 * @param 
	 * @return 
	 * @access	public
	 */
	function doIncludes() {
		require_once 'Auth/OpenID/Consumer.php'; 	// OpenID LibのRPコードの組み込み

    	require_once 'Auth/OpenID/FileStore.php'; 	// OpenID Libのファイル保存コードの組み込み

    	require_once 'Auth/OpenID/SReg.php'; 		// OpenID Libの"OpenID Simple Registration 
													// Extension 1.0(通称：SReg)"用コードの組み込み

    	require_once "Auth/OpenID/PAPE.php"; 		// OpenID Libの"OpenID Provider Authentication 
													// Policy Extension 1.0(通称：PAPE）"用コードの組み込み
	}


	function &getStore() {
    	/**
    	 * This is where the example will store its OpenID information.
    	 * You should change this path if you want the example store to be
    	 * created elsewhere.  After you're done playing with the example
    	 * script, you'll have to remove this directory manually.
    	 */
    	//$store_path = null;
    	//if (function_exists('sys_get_temp_dir')) {
        //	$store_path = sys_get_temp_dir();
    	//}
    	//else {
        //	if (strpos(PHP_OS, 'WIN') === 0) {
        //   	$store_path = $_ENV['TMP'];
        //    	if (!isset($store_path)) {
        //        	$dir = 'C:\Windows\Temp';
        //    	}
        //	}
        //	else {
        //    	$store_path = @$_ENV['TMPDIR'];
        //    	if (!isset($store_path)) {
        //        	$store_path = '/tmp';
        //    	}
        //	}
    	//}
    	//$store_path .= DIRECTORY_SEPARATOR . '_php_consumer_test';

    	//if (!file_exists($store_path) && !mkdir($store_path)) {
       	//	print "Could not create the FileStore directory '$store_path'. ".
        //   	" Please check the effective permissions.";
       	//	exit(0);
   		//}

    	$store_path = WEBAPP_DIR.'/openidstore';	//NetCommonsではこの場所をstore場所とする。
													//loginのインストールまたはアップデートでこのディレクトリが
													//存在することは担保しておく。
        if(is_dir($store_path) && is_writable($store_path)){
			$store = new Auth_OpenID_FileStore($store_path);

			//expiredしたassociationとnonce(いずれもファイル)のクリーンアップをここで行うように改良した。
			//注: nonceは $Auth_OpenID_SKEW の初期値が(60 * 60 * 5 =)5時間なので5時間以上経過したnonce履歴は削除される。
			//注: associationは $SECRET_LIFETIME(=lifetime)の初期値 が1209600(=14*24*60*60)=14日間なので発行(issued)時より
			//    14日以上経過しているassoc情報は削除される。
			//
			$removed_nonces_cnt = $store->cleanupNonces();
			$removed_assoc_cnt = $store->cleanupAssociations();

    		return $store;
		} else {	//エラーケースを考慮した。
			return false;
		}
	}

	function &getConsumer() {
    	/**
    	 * Create a consumer object using the store object created
    	 * earlier.
    	 */
    	$store = $this->getStore();
    	if($store===false){	//エラーケースを考慮した。
			return false;
		}
		$r = new Auth_OpenID_Consumer($store);
    	return $r;
	}

	function getScheme() {
    	$scheme = 'http';
    	if (isset($_SERVER['HTTPS']) and $_SERVER['HTTPS'] == 'on') {  //PHP仕様上はHTTPSの場合、$_SERVER['HTTPS']は空でない値が設定されるとだけ定義されているので、"on"と決めつけれない。
        	$scheme .= 's';
    	}
    	return $scheme;
	}

	function getReturnTo($mobile=false) {
    	//$returnto = sprintf("%s://%s:%s%s/finish_auth.php",
        //           $this->getScheme(), $_SERVER['SERVER_NAME'],
        //           $_SERVER['SERVER_PORT'],
        //           dirname($_SERVER['PHP_SELF']));
    	//$returnto = sprintf("%s://%s:%s%s/index.php?action=login_view_main_openid_rp_finishauth",
    	$returnto = sprintf("%s://%s:%s%s/index.php?action=login_action_". ( $mobile===true ? "mobile":"main") ."_openid_rp_finishauth",
                   $this->getScheme(), $_SERVER['SERVER_NAME'],
                   $_SERVER['SERVER_PORT'],
                   dirname($_SERVER['PHP_SELF']));
		return $returnto;
	}

	function getTrustRoot() {
    	$trustroot = sprintf("%s://%s:%s%s/",
                   $this->getScheme(), $_SERVER['SERVER_NAME'],
                   $_SERVER['SERVER_PORT'],
                   dirname($_SERVER['PHP_SELF']));
		return $trustroot;
	}

    function getOpenidUrlCnt($user_id) {
		$db =& $this->_container->getComponent("DbObject");
        $where = array( 'user_id' => $user_id, 'auth_type' => 1);
        $result = $db->selectExecute('login_external_user_mapping', $where );
        $openid_cnt = 0;
        if(is_array($result) && count($result) > 0 ) {
            $openid_cnt = count($result);
        }
		return $openid_cnt;
	}

}
?>
