<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Login登録コンポーネントクラス
 *
 * @package     NetCommons Components
 * @author      Noriko Arai,Ryuji Masukawa
 * @copyright   2006-2007 NetCommons Project
 * @license     http://www.netcommons.org/license.txt  NetCommons License
 * @project     NetCommons Project, supported by National Institute of Informatics
 * @access      public
 */
class Login_Components_Action
{
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
	function Login_Components_Action()
	{
		$container =& DIContainerFactory::getContainer();
		$this->_db =& $container->getComponent('DbObject');
	}

	/**
	 * SSO識別子マッピングデータを登録する
	 *
	 * @param string $external_id SSO識別子
	 * @param string $userId ユーザID
	 * @param integer $auth_type 認証タイプ
	 * @return boolean	true or false
	 * @access	public
	 */
	function setExternalMapping($external_id, $userId, $auth_type)
	{
		$container =& DIContainerFactory::getContainer();
		$requestMain =& $container->getComponent('requestMain');
		$session =& $container->getComponent('Session');
		$login_external = $session->getParameter(array('login_external'));

		$where = array(
			'external_id' => $external_id,
			'auth_type' => $auth_type
		);
		$result = $this->_db->selectExecute('login_external_user_mapping', $where );

		if ($result === false) {
			$this->_db->addError();
			return $result;
		}

		$params = array(
			'external_id' => $external_id,
			'user_id' => $userId,
			'active_flag' => 1,
			'auth_type' => $auth_type
		);
		if (isset($login_external["eppn_flag"])) {
			$params["eppn_flag"] = $login_external["eppn_flag"];
		}
		if (isset($login_external["entityid"])) {
			$params["entityid"] = $login_external["entityid"];
		}

		// 登録
		if ( empty( $result ) ) {
			$result = $this->_db->insertExecute("login_external_user_mapping", $params, true );
			if (!$result) {
				return false;
			}

		// 更新
		} else {
			$result = $this->_db->updateExecute("login_external_user_mapping", $params, "external_id", true);
			if (!$result) {
				return false;
			}
		}

		return true;
	}
}
?>