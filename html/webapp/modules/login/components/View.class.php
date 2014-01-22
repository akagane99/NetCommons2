<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * ログインモジュール表示用クラス
 *
 */
class Login_Components_View
{
	/**
	 * @var ConfigViewオブジェクトを保持
	 *
	 * @access	private
	 */
	var $_config = null;

	/**
	 * @var DIコンテナを保持
	 *
	 * @access	private
	 */
	var $_container = null;

	/**
	 * @var DBオブジェクトを保持
	 *
	 * @access	private
	 */
	var $_db = null;


	/**
	 * コンストラクター
	 *
	 * @access	public
	 */
	function Login_Components_View()
	{
		$log =& LogFactory::getLog();
		$log->trace("component login View のコンストラクタが実行されました", "loginView#Login_View");

		$this->_container =& DIContainerFactory::getContainer();
		$this->_config =& $this->_container->getComponent("configView");
		$this->_db =& $this->_container->getComponent('DbObject');

		$renderer =& SmartyTemplate::getInstance();
		$renderer->assign('autoregist_use', $this->getAutoregistUse() );
		$renderer->assign('autoregist_disclaimer', $this->getDisclaimer() );
	}

	/**
	 * 新規登録が可能かシステム設定を調べ返す
	 * @param
	 * @return
	 * @access	public
	 */
	function getAutoregistUse() {
		$autoregist_use_arry = $this->_config->getConfigByConfname(_SYS_CONF_MODID, 'autoregist_use');
		if( $autoregist_use_arry != null ) {
			if( isset( $autoregist_use_arry['conf_value'] ) ) {
				return ($autoregist_use_arry['conf_value']);
			}
		}
		return false;
	}
	/**
	 * 新規登録時、登録規約を表示することになっているかシステム設定を調べ返す
	 * @param
	 * @return
	 * @access	public
	 */
	function getDisclaimer() {
		$disclaimer_use_arry = $this->_config->getConfigByConfname(_SYS_CONF_MODID, 'autoregist_disclaimer');
		if( $disclaimer_use_arry != null ) {
			if( isset( $disclaimer_use_arry['conf_value'] ) ) {
				return ($disclaimer_use_arry['conf_value']);
			}
		}
		return false;
	}

	/**
	 * ユーザログインデータを取得する(Shibboleth)
	 *
	 * @param string $external_id SSO識別子
	 * @param integer $auth_type 認証タイプ
	 * @return array ユーザログインデータ配列
	 * @access public
	 */
	function getLoginByExternalId( $external_id, $auth_type )
	{
		// マッピングデータを取ってくる処理

		$params = array(
			'external_id' => $external_id,
			'auth_type' => $auth_type
		);

		// ユーザーが有効になっている人だけとってくる。
		$sql = "SELECT users.user_id,users.handle,users.role_authority_id,users.timezone_offset,users.last_login_time,users.system_flag,users.lang_dirname "
		        . " FROM {users} users "
		        . " INNER JOIN {login_external_user_mapping} mapping "
		        . " ON ( users.user_id = mapping.user_id ) "
		        . " WHERE mapping.external_id = ? "
		        . " AND mapping.auth_type= ? "
		        . " AND mapping.active_flag="._USER_ACTIVE_FLAG_ON
		        . " AND users.active_flag="._USER_ACTIVE_FLAG_ON;

		$result = $this->_db->execute($sql, $params, 0, null, false);
		if ($result === false) {
			$this->_db->addError();
			return $result;
		}

		return $result;
	}

	/**
	 * SSO連携の件数を取得する
	 *
	 * @param string $user_id ユーザID
	 * @param integer $auth_type 認証タイプ
	 * @return array ユーザログインデータ配列
	 * @access public
	 */
	function getLoginByUserIdCnt( $user_id, $auth_type ) {
		$where = array(
			'user_id' => $user_id,
			'auth_type' => $auth_type
		);

		$result = $this->_db->selectExecute('login_external_user_mapping', $where );
		$cnt = 0;
		if(is_array($result) && count($result) > 0 ) {
			$cnt = count($result);
		}
		return $cnt;
	}
}
?>