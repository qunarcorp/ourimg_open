import { observable, action, runInAction } from 'mobx';
import { galleryApi, globalApi } from 'CONST/api';
import { message } from 'antd';
import { getParams } from 'UTIL/params';
  export default class Home {

    @observable imgData = [];
    @observable galleryLoading = false;
    @observable userInfo = {
      dept: []
    };
    @observable offset = 0;
    @observable totalCount = {};
    @observable tabsList = {};

    @action.bound
    async getTotalCount(params) {
      const res = await $.get(globalApi.totalCount, params);
      runInAction(() => {
        if(res.ret) {
          this.totalCount = res.data;
        } else {
          this.totalCount = {};
        }
      })
    }

    @action.bound
    async getTabsList() {
      const res = await $.get(globalApi.tabsList);
      runInAction(() => {
        if(res.ret) {
          this.tabsList = res.data;
        } else {
          this.tabsList = {};
        }
      })
    }

    @action.bound
    async getImgGallery(params, callback) {
      if (params.offset == 0 || params.offset != this.offset) {
        this.offset = params.offset;
        const type = params.myTab;
        let url = galleryApi.getMyUploadsImgs;
        params.page_source === 'others' && (url = galleryApi.getOtherImgs);
        type === 'download' && (url = galleryApi.getMyDownloadImgs);
        type === 'favorite' && (url = galleryApi.getMyFavoriteImgs);
        delete params.myTab;

        this.galleryLoading = true;
        const res = await $.get(url, params);
        runInAction(() => {
            if (res.ret) {
              if (params.offset == 0) {
                this.imgData = res.data;
              } else {
                this.imgData = this.imgData.concat(res.data);
              }
              this.userInfo = res.userinfo;
              callback(res.count);
            } else {
              this.imgData = [];
              this.userInfo = {
                dept: []
              };
            }
            this.galleryLoading = false;
        });
      }
    }

    @action.bound
    delGalleryImg(index) {
      let picData = this.imgData.slice(0);
      picData.splice(index, 1);
      this.imgData = picData;
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
          callback && callback(res);
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
}