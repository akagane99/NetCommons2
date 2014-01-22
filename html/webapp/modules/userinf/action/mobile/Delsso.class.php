<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * 携帯SSO情報削除処理
 *
 * @package     NetCommons
 * @author      Noriko Arai,Ryuji Masukawa
 * @copyright   2006-2007 NetCommons Project
 * @license     http://www.netcommons.org/license.txt  NetCommons License
 * @project     NetCommons Project, supported by National Institute of Informatics
 * @access      public
 */
class Userinf_Action_Mobile_Delsso extends Action
{
    // リクエストパラメータを受け取るため
    var $user_id = null;
    var $auth_type = null;
   

	/**
	 * execute実行
	 *
	 * @access  public
	 */
    function execute()
    {

        if(!isset($this->user_id) || $this->user_id == "0") {
            // 不正なuser_id
            return 'error';
        }
        if(!isset($this->auth_type) || $this->auth_type == "0") {
            // 不正なauth_type
            return 'error';
        }

        $container =& DIContainerFactory::getContainer();
        $db =& $container->getComponent("DbObject");

        $where = array( 'user_id' => $this->user_id, 'auth_type' => $this->auth_type);
        $result = $db->deleteExecute('login_external_user_mapping', $where );
        if($result===false){
            return 'error';
        }

        return 'success';
    }
}
?>
