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
class Login_Action_Mobile_Openid_Rp_Finishauth extends Action
{
    /**
     * [OpenID 認証実行]
     *
     * @access  public
     */
    function execute()
    {
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

		//ここまできたら、モバイルなので、ＰＣ側の処理にチェーンする
		return 'success';
    }
}
