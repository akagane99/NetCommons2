<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * OpenID OP認証実行チェック
 *
 * @package     NetCommons.validator
 * @author      Noriko Arai,Ryuji Masukawa
 * @copyright   2006-2007 NetCommons Project
 * @license     http://www.netcommons.org/license.txt  NetCommons License
 * @project     NetCommons Project, supported by National Institute of Informatics
 * @access      public
 */
class Login_Validator_Openidtryauth extends Validator
{
    /**
     * validate&リダイレクト実行!
     *
     * @param   mixed   $attributes チェックする値(emai, code_date)
     *
     * @param   string  $errStr     エラー文字列(未使用：エラーメッセージ固定)
     * @param   array   $params     オプション引数
     * @return  string  エラー文字列(エラーの場合)
     * @access  public
     */
    function validate($attributes, $errStr, $params)
    {
    	if(isset($attributes['openid_identifier'])){
			//TODO: openid_identifier変数あり!今後必要あればここにロジック実装すること。
    	}
    	return;
    }
}
?>
