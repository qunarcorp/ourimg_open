import { observable, action, runInAction } from 'mobx';
import { globalApi, cartApi } from 'CONST/api'
import cookie from 'js-cookie';
import { message } from 'antd';

export default class Global {

    @observable isLogin = true;
    @observable userInfo = {
        userImg: '',
        userName: cookie.get('_USERNAME'),
        realName: cookie.get('_REALNAME'),
        role: ['super_admin'],
        auth_state: 0,
        auth_date: "",
        points_info: {
            total_points: 0,
            current_points: 0,
            last_date_points: 0
        }
    };
    // @observable userName = '';
    // @observable userEmplid = '';
    // @observable userNameCN = '';
    // @observable isSign = true;
    @observable globalSearch = {
        keyword: '',
        big_type: ''
    };
    @observable cartCount = 0;

    @action.bound
    async checkLogin() {
        // let bool = !!cookie.get('_USERNAME');
        // this.isLogin = bool;
        // if (bool) {
        //     this.userInfo = {
        //         userName: cookie.get('_USERNAME'),
        //         realName: cookie.get('_REALNAME')
        //     }
        // }

        $.get(globalApi.checkLogin).then(res => {
            let bool = !!cookie.get('_USERNAME');
            if ((res.ret || res.status === 0) && res.is_login && bool) {
                this.isLogin = true;
                this.userInfo = {
                    userImg: res.userinfo.user_img,
                    userName: res.userinfo.username,
                    realName: res.userinfo.name,
                    role: res.userinfo.role || [],
                    // role: ['normal'],
                    auth_state: res.userinfo.auth_state,
                    auth_date: res.userinfo.auth_date,
                    points_info: res.userinfo.points_info || {total_points: 0,
                        current_points: 0,
                        last_date_points: 0}
                };
                this.getCartCount();
            } else if ((res.ret || res.status === 0) && res.is_login && !bool) {
                this.logout();
            } else if (bool && !res.is_login) {
                this.isLogin = false;
                cookie.remove('_USERNAME');
                cookie.remove('_REALNAME');
            } else {
                this.isLogin = false;
            }
        })
    }

    @action.bound
    logout() {
        $.get(globalApi.loginout).then(res => {
            if (res.status === 0) {
                this.isLogin = false;
                message.success('退出登录成功');
                location.reload();
            }
        })
    }

    @action.bound
    updateSearchParams(params) {
        // 长度超过20个字符的搜索词直接扔掉
        let checkObj = {};
        let arr = [];
        params.keyword.split(/[,，]/).map(item => {
            if (item && !checkObj[item] && item.length <= 20) {
                arr.push(item)
                checkObj[item] = true;
            }
        })
        params.keyword = arr.join(',')
        this.globalSearch = {...this.globalSearch, ...params};
    }

    @action.bound
    async getCartCount(params, callback) {
      const res = await $.get(cartApi.cartCount, params);
      runInAction(() => {
        if (res.status == 0) {
          this.cartCount = res.data;
          callback && callback();
        }
      });
    }

    @action.bound
    async setCopyrightAuth(params, callback) {
      const res = await $.post(globalApi.copyrightAuth, params);
      runInAction(() => {
        if (res.status == 0) {
          callback && callback();
          message.success('授权成功');
          this.checkLogin();
        }
      });
    }
}
