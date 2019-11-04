import { observable, action, runInAction } from 'mobx';
import { galleryApi } from 'CONST/api';
import { message } from 'antd';

  export default class Home {

    @observable imgData = [];
    @observable galleryLoading = false;
    @observable offset = 0;

    @action.bound
    async getImgGallery(params, callback) {
      if (params.offset == 0 || params.offset != this.offset) {
        this.offset = params.offset;
        this.galleryLoading = true;
        const res = await $.get(galleryApi.getImgs, params);
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
}