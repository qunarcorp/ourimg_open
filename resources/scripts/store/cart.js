import { observable, action, runInAction } from 'mobx';
import { cartApi, detailApi } from 'CONST/api';
import { message, Modal } from 'antd';

export default class Cart {
    @observable selectedItems = [];
    @observable allCheck = false;
    @observable onlyEditPurposeVisible = false;
    @observable downloadData = {
        noticeMsg: "",
        downloadUrl: "",
        callback: ""
    }
    @observable downloadUrl = "";
    @observable list = [];
    @observable dataCount = {
        1:{},2:{},3:{},4:{}
    };
    @observable detailData = {
        keyword: []
      };
    @observable locationDetail = {};

    @action.bound
    async getList(params) {
        const res = await $.get(cartApi.list, params);
        runInAction(() => {
            if (res.status === 0) {
                res.data.count.map(item => {
                    this.dataCount[item.big_type] = item;
                });
                this.list = res.data.list;
            }
        });
    }

    @action.bound
    setSelectedItems(eid, check) {
        if (check) {
            this.selectedItems.push(eid);
            if (this.selectedItems.length === this.list.length) {
                this.allCheck = true;
            }
        } else {
            this.selectedItems = this.selectedItems.filter(id => id!==eid);
            this.allCheck = false;
        }
    }

    @action.bound
    checkAllSelected() {
        if (!this.allCheck) {
            this.allCheck = true;
            this.selectedItems = this.list.map(item => item.sc_id);
        } else {
            this.allCheck = false;
            this.selectedItems = [];
        }
    }

    @action.bound
    clearAllSelected() {
        this.allCheck = false;
        this.selectedItems = [];
    }

    @action.bound
    onEditPurposeCancel() {
        this.onlyEditPurposeVisible = false;
        this.onlyEditPurposeNoticeMsg = "";
        this.downloadUrl = "";
    }

    @action.bound
    downloadZip(downloadUrl, callback) {
        window.location = downloadUrl;
        callback && callback();
        this.selectedItems = [];
        this.getList();
        this.onEditPurposeCancel()
    }

    @action.bound
    async deleteItems(params, callback) {
        const res = await $.get(cartApi.deleteItems, params);
        runInAction(() => {
            if (res.status == 0) {
                this.getList();
                this.selectedItems = [];
                callback();
            }
        });
    }

    @action.bound
    async downloadImg(params, callback) {
      const res = await $.get(cartApi.downloadImg, params);
      runInAction(() => {
        if (res.status == 0) {
            let { img_url, only_edit_purpose } = res.data;
            if (only_edit_purpose.length) {
                this.onlyEditPurposeVisible = true;
                only_edit_purpose = only_edit_purpose.map(item => "「<span class=\"color-primary\">" + item.title + "</span>」")
                this.downloadData = {
                    noticeMsg: only_edit_purpose.join(" ") + " 仅限编辑传媒类使用，不可用作广告宣传等商业用途",
                    downloadUrl: img_url,
                    callback: callback
                }
            }else{
                this.downloadZip(img_url, callback)
            }
        }
      });
    }

    @action.bound
    async getImgDetail(params, callback) {
      this.detailData = {
        keyword: []
      }
      const res = await $.get(detailApi.getImgDetail, params);
      runInAction(() => {
          if (res.ret) {
            this.detailData = res.data;
            let location = res.data.location_detail_arr;
            let paramsObj = {};
            Object.keys(location).map(place => {
              switch (place) {
                case "country":
                  paramsObj[location[place]] = {location: location.country, keyword: ""};
                  break;
                case "province":
                  paramsObj[location[place]] = {location: `${location.country}/${location.province}`, keyword: ""};
                  break;
                case "city":
                  paramsObj[location[place]] = (location.city === location.province) ?
                  {location: `${location.country}/${location.province}`, keyword: ""} : {location: `${location.country}/${location.province}/${location.city}`,keyword: ""};
                  break;
                case "other":
                  location.other.map((item,index) => {
                    // let arr = location.other.slice(0, index + 1);
                    paramsObj[item] = {location: `${location.country}/${location.province}/${location.city}`,keyword: item}
                  });
                  break;
                default:
                  break;
              }
            });
            this.locationDetail = {...paramsObj};
            // callback(res.data.keyword ? res.data.keyword.join(' ') : '');
          } else {
            this.detailData = {
              keyword: []
            };
          }
      });
    }
}
