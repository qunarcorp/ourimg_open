import { observable, action, runInAction } from 'mobx';
import { listApi } from 'CONST/api'
// import params from '../UTIL/params';
// import cookie from 'js-cookie';

export default class Google {
    @observable filterObj = {
        big_type: {},
        country: [],
        ext: {},
        purpose: {},
        size_type: {},
        small_type: {},
        time_list: {}
    };
    @observable location = {
        province: [],
        city: []
    }

    @action.bound
    getFilterOption() {
        $.get(listApi.filterOption).then(res => {
            if (res.ret) {
                this.filterObj = res.data;
            }
        })
    }
    @action.bound
    getLocation(params) {
        $.get(listApi.location, params).then(res => {
            if (res.ret) {
                let str = '';
                str = params.province ? 'city' : 'province';
                this.location[str] = res.data.info
            }
        })
    }
    @action.bound
    setLocation(str) {
        if (str === 'country') {
            this.location = {
                province: [],
                city: []
            }
        } else if (str === 'province') {
            this.location = {
                province: this.location.province,
                city: []
            }
        }
    }
}
