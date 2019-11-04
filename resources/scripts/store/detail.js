import { observable, action, runInAction } from 'mobx';
import { galleryApi, detailApi } from 'CONST/api';
import { message } from 'antd';

  export default class Home {

    @observable detailData = {
      keyword: []
    };
    @observable imgData = [];
    @observable galleryLoading = false;
    @observable resizeUrl = '';
    @observable offset = 0;
    @observable locationDetail = {};

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
            callback(res.data.keyword ? res.data.keyword.join(' ') : '');
          } else {
            this.detailData = {
              keyword: []
            };
          }
      });
    }

    @action.bound
    async getImgGallery(params, callback) {
      if (params.offset == 0 || params.offset != this.offset) {
        this.offset = params.offset;
        this.galleryLoading = true;
        const res = await $.get(galleryApi.getRecommendImgs, params);
        runInAction(() => {
            if (res.ret) {
              if (params.offset == 0) {
                this.imgData = res.data;
              } else {
                this.imgData = this.imgData.concat(res.data);
              }
              callback(res.count);
            } else {
              this.imgData = [];
            }
            this.galleryLoading = false;
        });
      }
    }

    @action.bound
    updateImgGallery(index, type, callback) {
      switch(type) {
        case 'like':
          this.likeImg(index, callback);
          break;
        case 'collection':
          this.collectImg(index, callback);
          break;
        case 'addCart':
          this.addImgToCart(index, callback)
          break;
      }
    }

    @action.bound
    async likeImg(index, callback) {
      let state = ''
      if (this.imgData[index].user_praised) {
        state = 'cancel'
      } else {
        state = 'like'
      }
      const res = await $.post(galleryApi.likeImg, {eid: this.imgData[index].eid, state: state});
      runInAction(() => {
          if (res.ret) {
            let picData = this.imgData.slice(0);
            picData[index].praise = parseInt(picData[index].praise) + (picData[index].user_praised ? -1 : 1);
            picData[index].user_praised = !picData[index].user_praised;
            this.imgData = picData;
          }
          callback && callback();
      });
    }

    @action.bound
    async collectImg(index, callback) {
      let state = ''
      if (this.imgData[index].user_favorited) {
        state = 'cancel'
      } else {
        state = 'favortie'
      }
      const res = await $.post(galleryApi.collectImg, {
        eid: this.imgData[index].eid,
        // favorite_type: 'img', //收藏类型预留
        state: state
      });
      runInAction(() => {
          if (res.ret) {
            let picData = this.imgData.slice(0);
            picData[index].favorite = parseInt(picData[index].favorite) + (picData[index].user_favorited ? -1 : 1);
            picData[index].user_favorited = !picData[index].user_favorited;
            this.imgData = picData;
          }
          callback && callback();
      });
    }

    @action.bound
    async addImgToCart(index, callback) {
      let url = ''
      if (this.imgData[index].user_shopcart) {
        url = galleryApi.delCart;
      } else {
        url = galleryApi.add;
      }
      const res = await $.get(url, {
        eid: this.imgData[index].eid
      });
      runInAction(() => {
          if (res.status == 0) {
            let picData = this.imgData.slice(0);
            picData[index].user_shopcart = !picData[index].user_shopcart;
            this.imgData = picData;
          }
          callback && callback();
      });
    }

    @action.bound
    async resizeImg(params, callback) {
      const res = await $.get(detailApi.resizeImg, params);
      runInAction(() => {
          if (res.ret) {
            let { url_resize, only_edit_purpose } = res.data
            this.resizeUrl = url_resize;
            callback(res.data.only_edit_purpose);
          } else {
            this.resizeUrl = '';
          }
      });
    }

    @action.bound
    async downloadImg(params, callback) {
        const res = await $.get(detailApi.downloadImg, params);
        runInAction(() => {
            if (res.status == 0) {
                let { img_url, only_edit_purpose } = res.data
                if (params.action == 'resize') {
                    this.resizeUrl = img_url;
                }
                callback && callback(params.action, img_url, only_edit_purpose);
            }
        });
    }

    @action.bound
    async likeDetailImg() {
      let state = ''
      if (this.detailData.user_praised) {
        state = 'cancel'
      } else {
        state = 'like'
      }
      const res = await $.post(galleryApi.likeImg, {eid: this.detailData.eid, state: state});
      runInAction(() => {
          if (res.ret) {
            this.detailData.praise = parseInt(this.detailData.praise) + (this.detailData.user_praised ? -1 : 1);
            this.detailData.user_praised = !this.detailData.user_praised;
          }
      });
    }

    @action.bound
    async collectDetailImg() {
      let state = ''
      if (this.detailData.user_favorited) {
        state = 'cancel'
      } else {
        state = 'favortie'
      }
      const res = await $.post(galleryApi.collectImg, {
        eid: this.detailData.eid,
        // favorite_type: 'img', //收藏类型预留
        state: state
      });
      runInAction(() => {
          if (res.ret) {
            this.detailData.favorite = parseInt(this.detailData.favorite) + (this.detailData.user_favorited ? -1 : 1);
            this.detailData.user_favorited = !this.detailData.user_favorited;
          }
      });
    }

    @action.bound
    async addDetailImgToCart(callback) {
      const res = await $.get(galleryApi.add, {
        eid: this.detailData.eid
      });
      runInAction(() => {
          if (res.status == 0) {
            this.detailData.user_shopcart = !this.detailData.user_shopcart;
            callback && callback();
          }
      });
    }
}
