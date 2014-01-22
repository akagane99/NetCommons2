<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * [[機能説明]]
 *  ログイン管理モジュールアップデートクラス
 *
 * @package     NetCommons
 * @author      Noriko Arai,Ryuji Masukawa
 * @copyright   2006-2007 NetCommons Project
 * @license     http://www.netcommons.org/license.txt  NetCommons License
 * @project     NetCommons Project, supported by National Institute of Informatics
 * @access      public
 */
class Login_Update extends Action
{
	//使用コンポーネントを受け取るため
	var $db = null;

    /**
     * execute実行
     *
     * @access  public
     */
	function execute()
	{
		// itemsに新しいレコードを追加
		$add_items_params = array(
			array( 	"item_name"=>"USER_ITEM_SSO_INFO",
					"type"=>USER_TYPE_SYSTEM,
					"tag_name"=>"userinf_view_main_ssoinfo",
					"system_flag"=>1,
					"require_flag"=>0,
					"define_flag"=>1,
					"display_flag"=>1,
					"allow_public_flag"=>0,
					"allow_email_reception_flag"=>0,
					"col_num"=>0,
					"row_num"=>0
			)
		);
		$authority_params = array(
			array( "user_authority_id"=>_AUTH_ADMIN, "under_public_flag"=>USER_PUBLIC, "self_public_flag"=>USER_PUBLIC, "over_public_flag"=>USER_PUBLIC ),
			array( "user_authority_id"=>_AUTH_CHIEF, "under_public_flag"=>USER_PUBLIC, "self_public_flag"=>USER_PUBLIC, "over_public_flag"=>USER_NO_PUBLIC ),
			array( "user_authority_id"=>_AUTH_MODERATE, "under_public_flag"=>USER_PUBLIC, "self_public_flag"=>USER_PUBLIC, "over_public_flag"=>USER_NO_PUBLIC ),
			array( "user_authority_id"=>_AUTH_GENERAL, "under_public_flag"=>USER_NO_PUBLIC, "self_public_flag"=>USER_PUBLIC, "over_public_flag"=>USER_NO_PUBLIC ),
			array( "user_authority_id"=>_AUTH_GUEST, "under_public_flag"=>USER_NO_PUBLIC, "self_public_flag"=>USER_PUBLIC, "over_public_flag"=>USER_NO_PUBLIC )
		);

		foreach( $add_items_params as $item ) {
			// まだ追加対象の項目が入っていないことを確認する
			$ret = $this->db->countExecute( "items", array( "item_name"=>$item['item_name'] ) );
			if( $ret == 1 ) {
				// もう存在していたら次へ
				continue;
			}
			// 1列目の最終行の値を調べる
			$row_max = $this->db->maxExecute( "items", "row_num", array( "col_num"=>1 ) );
			// 最終行の値をセット
			$item['row_num'] = $row_max+1;
			// 新規追加
			$ret = $this->db->insertExecute( "items", $item, true, "item_id" );
			if( $ret == false ) {
				return false;
			}
			// 追加したItem_idを覚えておく
			$item_id = $ret;

			// items_authorities_linkに追加
			foreach( $authority_params as $auth_param ) {
				$auth_param += array( "item_id"=>$item_id );
				$ret = $this->db->insertExecute( "items_authorities_link", $auth_param );
				if( $ret == false ) {
					$this->db->deleteExecute( "items", array( "item_id"=>$item_id ) );
					return false;
				}
			}

			// items_descはなし

			// items_optionはなし
		}

		//OpenIDライブラリのstore場所の追加
		$openid_store_dir = WEBAPP_DIR.'/openidstore';
		if(!is_dir($openid_store_dir)){
			//openid用storeディレクトリが存在しないのでmkdirする.
			if(!@mkdir($openid_store_dir,0755)){
				return false; 	//openid用storeディレクトリの作成に失敗
			}
		}
		if(!is_writable($openid_store_dir)){
			//openid用storeディレクトリは存在するが、その下への書き込み権がないのでchmodで付与する。
			if(!@chmod($openid_store_dir,0755)){
				return false; 	//openid用storeディレクトリ下への書込み権付与に失敗
			}
		}

		//login拡張ユーザマッピングテーブルの追加
		//
		$tgt_tbl_name = $this->db->getPrefix() . 'login_external_user_mapping';

		$sql = "SHOW COLUMNS FROM `".$tgt_tbl_name."` ;";
		$result = $this->db->execute($sql);
		if($result === false){
			$sql = "CREATE TABLE `".$tgt_tbl_name."` (" .
				"  `external_id`        varchar(256) NOT NULL," .
				"  `user_id`            varchar(40) NOT NULL DEFAULT ''," .
				"  `eppn_flag`          tinyint(3) NOT NULL DEFAULT '1'," .
				"  `active_flag`        tinyint(3) NOT NULL DEFAULT '1'," .
				"  `entityid`           varchar(256) NOT NULL," .
				"  `auth_type`          tinyint(3) NOT NULL DEFAULT '1'," .
				"  `insert_time`        varchar(14) NOT NULL DEFAULT ''," .
				"  `insert_user_id`     varchar(40) NOT NULL DEFAULT ''," .
				"  `insert_user_name`   varchar(255) NOT NULL DEFAULT ''," .
				"  `update_time`        varchar(14) NOT NULL DEFAULT ''," .
				"  `update_user_id`     varchar(40) NOT NULL DEFAULT ''," .
				"  `update_user_name`   varchar(255) NOT NULL DEFAULT ''," .
				"  PRIMARY KEY (`external_id`)," .
				"  KEY `user_id` (`user_id`)" .
				") ENGINE=MyISAM;";
			$result = $this->db->execute($sql);
			if($result === false){
				//テーブルが生成できないのは致命的。
				return false;
			}
		} else {
			//テーブルがすでに存在する。
			//OpenID RPとシボレスSPのNetCommonsへの同時組み込みなので、
			//このテーブルが先行してかつ、形式が異なることは想定しない。
		}
		return true;
	}
}
?>
