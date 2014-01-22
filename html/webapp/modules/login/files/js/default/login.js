var clsLogin = Class.create();
var loginCls = Array();

clsLogin.prototype = {
	initialize: function(id) {
		this.id = id;
		this.sslIframeSrc = null;
		this.loginIdValue = null;
	},

	showLogin: function(event) {
		commonCls.displayVisible($('login_popup'));
		commonCls.sendPopupView(event, {'action':'login_view_main_init'}, {'center_flag':true});

		var sslIframe = $('login_ssl_iframe' + this.id);
		if (sslIframe == null) {
			this.initializeFocus();
			return;
		}

		if (this.sslIframeSrc != null) {
			sslIframe.focus();
			sslIframe.src = this.sslIframeSrc;
		}
	},

	initializeFocus: function(errorCount) {
		try {
			var formElement = $('login_form' + this.id);
			var loginIdElement = formElement['login_id'];
			loginIdElement.focus();
			loginIdElement.select();

			if (browser.isIE) {
				loginIdElement.fireEvent('ondblclick');
				loginIdElement.fireEvent('onblur');
			}
		}catch(e){
			if (errorCount < 5) {
				errorCount++;
				setTimeout(function(){this.initializeFocus(errorCount);}.bind(this), 300);
			}
		}
	},

	setButtonStyle: function(element) {
		if (element == null) {
			return;
		}
		var styleValue = "border-radius:4px;-webkit-border-radius:4px;-moz-border-radius:4px;";
		element.setAttribute('style', styleValue);
	},

	loginLogout: function(event) {
		//Shibboleth
		this.shibbolethLogout();

		var load_el = Event.element(event);
		//パラメータ取得
		var logout_params = new Object();
		var top_el = $(this.id);

		logout_params["method"] = "post";
		logout_params["param"] = {"action":"login_action_main_logout"};
		logout_params["loading_el"] = load_el;

		commonCls.send(logout_params);
	},

	shibbolethLogout: function() {
		//Shibboleth
		new Ajax.Request(
			"https:" + "//" + location.hostname + "/Shibboleth.sso/Logout",
			{
				method:"post"
			}
		);
	},

	insAutoregist: function (form_el) {
		var reg_params = new Object();
		reg_params["param"] = {'action': "login_action_main_autoregist"};
		reg_params["form_el"] = form_el;
		reg_params["top_el"] = $(this.id);
		reg_params["target_el"] = $("target"+this.id);
		reg_params["method"] = "post";
		reg_params['form_prefix'] = "login_attachment";
		reg_params["callbackfunc_error"] = function(file_list, res){
			// エラー時(File)
			this.focusError(res);
		}.bind(this);
		commonCls.sendAttachment(reg_params);
	},
	focusError: function(res) {
		res = commonCls.cutErrorMes(res);
		if(res.match(":")) {
			var mesArr = res.split(":");
			var alert_res = "";
			for(var i = 1; i < mesArr.length; i++) {
				alert_res += mesArr[i];
			}
			// チェックボックス等の場合、うまく動作しないかも
			var focus_el = $("login_items"+ this.id + "_" + mesArr[0]);
			if(focus_el) {
				commonCls.alert(alert_res);
				commonCls.focus(focus_el);
			} else {
				commonCls.alert(alert_res);
			}
		} else {
			commonCls.alert(res);
		}
	},
    openidRpTryauth: function(form_el, pages_action) {
        var top_el = $(this.id);
        var params = new Object();
        params["param"] = "action=login_action_main_openid_rp_tryauth&" + Form.serialize(form_el);
        params["method"] = "post";
        params["top_el"] = top_el;
        params["loading_el"] = top_el;
        params["callbackfunc_error"] = function(res) {
			commonCls.alert(res);
		}.bind(this);
        params["callbackfunc"] = function(res) {
			var loc = "Location=", jsform = "JavaScriptForm=";
			var target_loc = "", target_jsform = "";
			var bd = null;
			//commonCls.alert('openidRpTryauth_res:'+res);

			if(res.substr(0, loc.length)==loc){
				target_loc = res.substr(loc.length);
				location.href = target_loc;
				return;
			}

			if(res.substr(0, jsform.length)==jsform){
				target_jsform = res.substr(jsform.length);
				bd = $$('body')[0];
				bd.innerHTML = target_jsform;
				setTimeout( function() {
					//ここでロードしたJSFomrをsubmit()する
					document.forms[0].submit();
				}, 200);
				return;
			}

            //location.href = _nc_base_url + _nc_index_file_name + "?action=" + pages_action +
            //                "&active_center=search_view_main_center&active_block_id=" + this.block_id +
            //                "&page_id=" + _nc_main_page_id;
        }.bind(this);
        commonCls.send(params);
		//commonCls.alert('id['+this.id+'] doSend');
	}
}