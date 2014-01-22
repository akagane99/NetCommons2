<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * ユーザマッピング画面表示処理アクションクラス
 *
 * @package     NetCommons Action
 * @author      Noriko Arai,Ryuji Masukawa
 * @copyright   2006-2007 NetCommons Project
 * @license     http://www.netcommons.org/license.txt  NetCommons License
 * @project     NetCommons Project, supported by National Institute of Informatics
 * @access      public
 */

class Login_View_Main_Mapping extends Action
{
	// リクエストパラメータを受け取るため
	var $block_id = null;
	var $page_id = null;
	var $active_center = null;

	// コンポーネントを使用するため
	var $session = null;
	var $request = null;

	//Shibboleth
	var $iframeSrc = null;

	/**
	 * ユーザマッピング画面表示処理アクション
	 *
	 * @access  public
	 */
	function execute()
	{
		$this->iframeSrc = BASE_URL_HTTPS . INDEX_FILE_NAME
								. '?action=login_view_main_init'
								. '&block_id=' . $this->block_id
								. '&parent_id=' . $this->session->getParameter('_id')
								. '&http=' . _ON
								. '&isMapping=' . _ON
								. '&prefix_id_name=login_mapping';

		if ($this->active_center != "") {
			$this->session->removeParameter(array('login_external', 'redirect'));
		}
		$redirect = $this->session->getParameter(array('login_external', 'redirect'));
		if (!empty($redirect)) {
			$this->request->setParameter("page_id", _SELF_TOPPUBLIC_ID);
		}

		return 'success';
	}
}
?>