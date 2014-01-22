<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Login定数定義
 *
 * @package     NetCommons Components
 * @author      Noriko Arai,Ryuji Masukawa
 * @copyright   2006-2007 NetCommons Project
 * @license     http://www.netcommons.org/license.txt  NetCommons License
 * @project     NetCommons Project, supported by National Institute of Informatics
 * @access      public
 */


//https://www.gakunin.jp/docs/fed/technical/attribute
//define("LOGIN_SHIBBOLETH_EXTERNAL_ID_NAME", "REMOTE_USER");
define("LOGIN_SHIBBOLETH_EXTERNAL_ID_NAME", "eppn");
define("LOGIN_SHIBBOLETH_GET_PREFIX_ATTRIBUTE", "Shib-Session-ID");

define("LOGIN_SHIBBOLETH_DEF_MAIL", "mail"); //メール
define("LOGIN_SHIBBOLETH_DEF_SURNAME", "sn"); //氏名(姓)の英字
define("LOGIN_SHIBBOLETH_DEF_GIVENNAME", "givenName"); //氏名(名)の英字
define("LOGIN_SHIBBOLETH_DEF_DISPLAYNAME", "displayName"); //英字氏名（表示名）
define("LOGIN_SHIBBOLETH_DEF_ORGANIZATIONNAME", "o"); //組織名称の英字
define("LOGIN_SHIBBOLETH_DEF_ORGANIZATIONALUNITNAME", "ou"); //組織内所属名称の英字

//define("LOGIN_SHIBBOLETH_DEF_EDUPERSONAFFILIATION", "unscoped-affiliation"); //利用者の職種等
//define("LOGIN_SHIBBOLETH_DEF_EDUPERSONPRINCIPALNAME", "eppn"); //フェデレーション内の一意になるエンティティ
//define("LOGIN_SHIBBOLETH_DEF_EDUPERSONENTITLEMENT", "entitlement"); //特定のアプリケーションを利用する資格情報
//define("LOGIN_SHIBBOLETH_DEF_EDUPERSONSCOPEDAFFILIATION", "affiliation"); //利用者が所属する組織内での職種
define("LOGIN_SHIBBOLETH_DEF_EDUPERSONTARGETEDID", "persistent-id"); //フェデレーション内のエンティティを匿名で表す

define("LOGIN_SHIBBOLETH_DEF_JASURNAME", "jasn"); //氏名（姓）の日本語
define("LOGIN_SHIBBOLETH_DEF_JAGIVENNAME", "jaGivenName"); //氏名（名）の日本語
define("LOGIN_SHIBBOLETH_DEF_JADISPLAYNAME", "jaDisplayName"); //アプリケーション上に日本語で表わす氏名等（表示名）
define("LOGIN_SHIBBOLETH_DEF_JAORGANIZATIONNAME", "jao"); //組織名称の日本語
define("LOGIN_SHIBBOLETH_DEF_JAORGANIZATIONALUNITNAME", "jaou"); //組織内所属名称の日本語

define("LOGIN_SHIBBOLETH_PATH", "/secure/index.php");
define("LOGIN_USE_SHIBBOLETH_DS", _ON);

define("LOGIN_SHIBBOLETH_GAKUNIN_IDP_NOUSED", "0");
define("LOGIN_SHIBBOLETH_GAKUNIN_IDP_USED", "1");
define("LOGIN_SHIBBOLETH_ORGINAL_IDP_NOUSED", "2");
define("LOGIN_SHIBBOLETH_ORGINAL_IDP_USED", "3");


//define("LOGIN_SHIBBOLETH_DS_PATH", "https://ds.gakunin.nii.ac.jp/WAYF");    //TODO: 本番環境
define("LOGIN_SHIBBOLETH_DS_PATH", "https://test-ds.gakunin.nii.ac.jp/WAYF"); //TODO: テスト、開発環境

define("LOGIN_SHIBBOLETH_SP_HOST", BASE_URL_HTTPS);

//define("LOGIN_SHIBBOLETH_DISCOFEED_URL", "https://office.gakunin.nii.ac.jp/ProdFed/export/discofeed/PSxxxx"); //TODO: 本番環境
//define("LOGIN_SHIBBOLETH_DISCOFEED_URL", "https://office.gakunin.nii.ac.jp/TestFed/export/discofeed/TSxxxx"); //TODO: テスト、開発環境
define("LOGIN_SHIBBOLETH_DISCOFEED_URL", "");

//Shibboleth認証後にリダイレクト用定数
define("LOGIN_SHIBBOLETH_REDIRECT_TOP", "top");
define("LOGIN_SHIBBOLETH_REDIRECT_SIGNUP", "sign-up");

//EmbeddedDSのデフォルトで表示するIdP
define("LOGIN_SHIBBOLETH_DS_DEFUALT_IDP", "");

//EmbeddedDSの表示しないカテゴリ
define("LOGIN_SHIBBOLETH_DS_HIDE_CATEGORIES", '');

//EmbeddedDSで表示させるIdPのリスト
define("LOGIN_SHIBBOLETH_DS_UNHIDE_IDP", '');

?>