import React, { Component } from 'react'
import { observer, inject } from 'mobx-react';
import { withRouter } from 'react-router-dom';
import QGallery from 'COMPONENT/qGallery';
import DetailCard from './detailCard';

@inject(state => ({
    history: state.history,
    router: state.router,
    hash: state.router.location.hash,
    imgData: state.store.detail.imgData,
    galleryLoading: state.store.detail.galleryLoading,
    detailData: state.store.detail.detailData,
    getImgDetail: state.store.detail.getImgDetail,
    getImgGallery: state.store.detail.getImgGallery,
    updateImgGallery: state.store.detail.updateImgGallery,
    getCartCount: state.store.global.getCartCount
}))
@withRouter
@observer
class Detail extends Component {

    state = {
        eid: '',
        keyword: '',
        offset: 0,
        limit: 10,
        loadCompleted: false
    }

    componentDidMount() {
    // componentWillMount() {
        let params = new URLSearchParams(this.props.history.location.search);
        this.eid = params.get('eid');
        this.props.getImgDetail({eid: this.eid}, (keyword) => {
            this.setState({
                eid: this.eid,
                keyword
            },this.loadData);
            window.scrollTo(0, 0);
        });
    }

    onClickImg = (eid) => {
        window.open('#/detail?eid=' + eid)
        // this.props.getImgDetail({eid}, (keyword) => {
        //     this.setState({
        //         eid,
        //         keyword,
        //         offset: 0,
        //         loadCompleted: false
        //     },this.loadData);
        //     window.scrollTo(0, 0);
        // });
    }

    loadData = () => {
        let { offset, limit, keyword, eid } = this.state;
        let params = {
            eid,
            offset,
            limit,
            keyword
        };
        this.props.getImgGallery(params, (count) => {
            if (offset + limit < count) {
                this.setState({
                    offset: offset + limit,
                    loadCompleted: false
                });
            } else {
                this.setState({
                    loadCompleted: true
                });
            }
        });
    }

    reloadGallery = (index, type, callback) => {
        this.props.updateImgGallery(index, type, () => {
            callback && callback();
            if (type === 'addCart') {
                this.props.getCartCount();
            }
        });
    }

    render() {
        let { loadCompleted } = this.state;
        let { imgData, galleryLoading } = this.props;
        return (
            <div className="q-detail-page q-content">
                <DetailCard/>
                <div className="q-gallery-title">为你推荐</div>
                <QGallery
                    onClickImg={this.onClickImg}
                    loading={galleryLoading}
                    completed={loadCompleted}
                    elements={imgData}
                    load={this.loadData}
                    reload={this.reloadGallery}
                    hideHint={true}
                ></QGallery>
                <div className="q-detail-copyright">
                    <div className="copyright-title">版权声明</div>
                    <div className="copyright-content">为保护图片库用户知识产权和合法权益，若您认为您的知识产权或其他合法权益受到侵犯，可依法向知识产权投诉渠道以邮件的方式反馈。</div>
                    <div className="copyright-content">本网站平台展示的图片，视频、设计素材等作品均经有关著作权人合法授权，素材作品的一切商业用途仅限于及其授权的个人/公司使用，如超出使用范围，或造成任何侵犯作品著作权的行为，有关著作权人或其授权的本平台将依据著作权侵权惩罚性赔偿标准或最高达50万元人民币的法定赔偿标准，要求侵权方赔偿损失。</div>
                </div>
            </div>
        )
    }
}

export default Detail;
