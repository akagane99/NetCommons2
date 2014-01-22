<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * SSO情報削除時のチェック
 *  リクエストパラメータ
 *  $user_id,$auth_type
 *
 * @package     NetCommons.validator
 * @author      Noriko Arai,Ryuji Masukawa
 * @copyright   2006-2007 NetCommons Project
 * @license     http://www.netcommons.org/license.txt  NetCommons License
 * @project     NetCommons Project, supported by National Institute of Informatics
 * @access      public
 */
class Userinf_Validator_DelssoInputs extends Validator
{
    /**
     * validate実行
     *
     * @param   mixed   $attributes チェックする値(user_id, auth_type)
     *                  
     * @param   string  $errStr     エラー文字列(未使用：エラーメッセージ固定)
     * @param   array   $params     オプション引数
     * @return  string  エラー文字列(エラーの場合)
     * @access  public
     */
    function validate($attributes, $errStr, $params)
    {
        $log =& LogFactory::getLog();
        $log->trace("DelssoInputsの処理にはいりました", "Validator_DelssoInputs.class.php#validate");

    	// container取得
		$container =& DIContainerFactory::getContainer();
    	$session =& $container->getComponent("Session");


        // 編集するuser情報
        // 現在は基本的に自分の情報しか編集できないことになっている

        // 自分
        $user_id = $session->getParameter("_user_id");
        if($user_id == "0")  return $errStr;
        // 自分の権限
        $_user_auth_id = $session->getParameter("_user_auth_id");

        //
        // 編集対象の人物のuser_id
        //
        if(isset($attributes['user_id'])) {
            $edit_user_id = $attributes['user_id'];
        } else {
            $edit_user_id = $session->getParameter("_user_id");
        }
        if( $user_id != $edit_user_id ) {
			//実行者がSSO情報の所有者ではない
            return $errStr;
        }

		//
		// SSO種別チェック(いまのところ、OpenIDのみサポート)
		//
        if(!(isset($attributes['auth_type']) && $attributes['auth_type']=='1')) {
            return $errStr;
		}

		return;
    }
}
?>
