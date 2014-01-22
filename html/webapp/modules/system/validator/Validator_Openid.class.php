<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * OpenID OP識別子チェック
 *
 * @package     NetCommons.validator
 * @author      Noriko Arai,Ryuji Masukawa
 * @copyright   2006-2007 NetCommons Project
 * @license     http://www.netcommons.org/license.txt  NetCommons License
 * @project     NetCommons Project, supported by National Institute of Informatics
 * @access      public
 */
class System_Validator_Openid extends Validator
{
    /**
     * validate実行
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
    	if(isset($attributes['openid_uses']) && intval($attributes['openid_uses']) == _ON) {

    		$container =& DIContainerFactory::getContainer();
        	$filterChain =& $container->getComponent("FilterChain");
			$smartyAssign =& $filterChain->getFilterByName("SmartyAssign");

    		// OpenID使用(ON)ならば、各OP識別子の必須チェック
    	    $opids = explode('|', $attributes['openid_opids']);
			foreach($opids as $i => $opid){
				if(strlen($opid) == 0){
    	    		return sprintf($smartyAssign->getLang('system_err_number_opid_required'), ($i+1));
				}
            }

    		// OpenID使用(ON)ならば、各OP名称の必須チェック
    	    $opnames = explode('|', $attributes['openid_opnames']);
			foreach($opnames as $i => $opname){
				if(strlen($opname) == 0){
    	    		return sprintf($smartyAssign->getLang('system_err_number_opname_required'), ($i+1));
				}
            }

			//OP識別子とOP名称の数が一致するかチェック
			if(count($opids) != count($opnames)){
    	    	return $smartyAssign->getLang('system_err_opids_opnames_count_unmatch');
			}

    	}
    	return;
    }
}
?>
