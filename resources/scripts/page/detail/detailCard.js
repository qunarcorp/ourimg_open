import React, { Component } from 'react'
import moment from 'moment'
import copy from 'copy-to-clipboard';
import { Button, Tag, Modal, Input, Icon } from 'antd';
import { Link } from 'react-router-dom';
import { observer, inject } from 'mobx-react';
import { withRouter } from 'react-router-dom';
import { message } from 'antd';
import { purposeMap } from 'CONST/map';
import QImgModal from 'COMPONENT/qImgModal';
import QCRSelect from 'COMPONENT/qCRSelect';
import CopyrightFileModal from 'COMPONENT/copyrightFileModal';

@inject(state => ({
    history: state.history,
    router: state.router,
    hash: state.router.location.hash,
    isLogin: state.store.global.isLogin,
    filterObj: state.store.google.filterObj,
    getFilterOption: state.store.google.getFilterOption,
    resizeUrl: state.store.detail.resizeUrl,
    detailData: state.store.detail.detailData,
    downloadImg: state.store.detail.downloadImg,
    resizeImg: state.store.detail.resizeImg,
    updateSearchParams: state.store.global.updateSearchParams,
    likeDetailImg: state.store.detail.likeDetailImg,
    collectDetailImg: state.store.detail.collectDetailImg,
    addDetailImgToCart: state.store.detail.addDetailImgToCart,
    getCartCount: state.store.global.getCartCount,
    locationDetail: state.store.detail.locationDetail
}))
@withRouter
@observer
class DetailCard extends Component {

    state = {
        createVisible: false,
        copyrightFileVisible: false,
        linkVisible: false,
        imgHeight: '',
        imgWidth: '',
        imgVisible: false
    }

    clickJumpToList(key, value) {
        this.props.history.push({
            pathname: '/list',
            state: { [key]: value }
        });
    }

    componentDidMount() {
        this.props.getFilterOption();
    }

    openDialog = () => {
        if (!this.props.isLogin) {
            message.error('未登录用户不允许操作');
            return;
        }
        if (!this.props.detailData.download_permission) {
            Modal.warning({
                title: '没有下载权限',
                content: (
                    <div>
                        <p>申请开通图片下载权限</p>
                        <p>请邮件至申请，注明申请原因</p>
                    </div>
                ),
                onOk() {
                }
            });
            return
        }
        this.setState({
            createVisible: true,
            imgHeight: this.props.detailData.height,
            imgWidth: this.props.detailData.width,
            aspectRatio: this.props.detailData.height / this.props.detailData.width,
        })
    }
    openCopyrightFile = () => {
        this.setState({
            copyrightFileVisible: true,
        })
    }

    editWidth = (e) => {
        const { value } = e.target;
        const reg = /^\d+$/;
        (!value || reg.test(value)) && this.setState({
            imgWidth: value,
            imgHeight: Math.round((value / this.props.detailData.width) * this.props.detailData.height),
        })
    }

    editHeight = (e) => {
        const { value } = e.target;
        const reg = /^\d+$/;
        (!value || reg.test(value)) && this.setState({
            imgHeight: value,
            imgWidth: Math.round((value / this.props.detailData.height) * this.props.detailData.width),
        })
    }

    onCreateCancel = () => {
        this.setState({
            createVisible: false,
            imgHeight: '',
            imgWidth: ''
        })
    }

    onCopyrightFileCancel = () => {
        this.setState({
            copyrightFileVisible: false,
        })
    }

    submitCreate = () => {
        let params = {
            eid: this.props.detailData.eid,
            width: this.state.imgWidth,
            height: this.state.imgHeight
        };
        this.props.resizeImg(params, (only_edit_purpose) => {
            if (only_edit_purpose.length) {
                Modal.warning({
                    title: "温馨提示",
                    content: (
                        <div className="edit-confirm-content link-modal">
                            {'此图片仅限编辑传媒类使用，不可用作广告宣传等商业用途'}
                        </div>
                    ),
                    className: 'detail-card--page',
                    maskClosable: true,
                    onOk: () => {
                      this.setState({
                          linkVisible: true,
                          imgHeight: '',
                          imgWidth: ''
                      })
                    }
                });
                this.setState({
                    createVisible: false,
                    imgHeight: '',
                    imgWidth: ''
                })
            }else{
                this.setState({
                    createVisible: false,
                    linkVisible: true,
                    imgHeight: '',
                    imgWidth: ''
                })
            }
        });
    }

    keepDownload = () => {
        this.setState({
            createVisible: false,
            linkVisible: true,
            imgHeight: '',
            imgWidth: ''
        })
    }

    copyUrl = () => {
        copy(this.props.resizeUrl);
        message.success('已复制到剪贴板');
        this.onLinkCancel();
    }

    onLinkCancel = () => {
        this.setState({
            linkVisible: false
        })
    }

    downloadImg = () => {
        if (!this.props.isLogin) {
            message.error('未登录用户不允许操作');
            return;
        }
        if (!this.props.detailData.download_permission) {
            Modal.warning({
                title: '没有下载权限',
                content: (
                    <div>
                        <p>申请开通图片下载权限</p>
                        <p>请邮件至申请，注明申请原因</p>
                    </div>
                ),
                onOk() {
                }
            });
            return
        }
        this.props.downloadImg({
            eid: this.props.detailData.eid,
            action: 'download'
        }, (action, img_url, only_edit_purpose) => {
            if (action == 'download') {
                if (only_edit_purpose.length) {
                    Modal.warning({
                        title: "温馨提示",
                        content: (
                            <div className="edit-confirm-content link-modal">
                                {'此图片仅限编辑传媒类使用，不可用作广告宣传等商业用途'}
                            </div>
                        ),
                        className: 'detail-card--page',
                        maskClosable: true,
                        onOk: () => {
                            window.location = img_url;
                        }
                    });
                }else{
                    window.location = img_url;
                }
            }
        });
    }

    showBigImg = ({ visible }) => {
        this.setState({
            imgVisible: visible
        });
    }

    onTagClick = (item) => {
        // let pathname = this.props.history.location.pathname;
        this.props.updateSearchParams({
            keyword: item
        });
        // pathname === '/list' ? '' : this.props.history.push(`/list`);
        this.props.history.push(`/list`)
    }

    addShopCart = () => {
        if (!this.props.isLogin) {
            message.error('未登录用户不允许操作');
            return;
        }
        if (!this.props.detailData.download_permission) {
            Modal.warning({
                title: '没有下载权限',
                content: (
                    <div>
                        <p>申请开通图片下载权限</p>
                        <p>请邮件至申请，注明申请原因</p>
                    </div>
                ),
                onOk() {
                }
            });
            return
        }
        this.props.addDetailImgToCart(() => {
            this.props.getCartCount();
        });
    }

    render() {
        let {
            title, big_img, eid, filesize, ext, username, realname, img_url,upload_source,
            keyword, current_time, upload_time, purpose, favorite, download, praise, browse,
            location, width, height, small_type, user_shopcart, user_favorited,
            user_praised, copyright_auth_date
        } = this.props.detailData;
        let { createVisible, linkVisible, imgWidth, imgHeight, imgVisible, copyrightFileVisible } = this.state;

        if (upload_source) {
            img_url = "/img/ff1a003aa731b0d4e2dd3d39687c8a54.png";
            realname = upload_source;
        }

        return (
            <div className="img-detail-card">
                <div className="header">
                    <img src={ img_url } className="avatar"/>
                    <div className="user-info">
                        <div className="name">{ realname }</div>
                        <div className="time">{ moment(upload_time).format('YYYY-MM-DD HH:mm') + ' 上传' }</div>
                    </div>
                    <div className="user-btn copyright-file-btn" onClick={ () => this.openCopyrightFile({ copyrightFileVisible: true }) }>
                        <Icon className="copyright-file-icon" type="file-text" />
                        <span>TA的授权书</span>
                    </div>
                    <Link to={ { pathname: "/user", search: ! upload_source ? `?uid=${username}` : `dept=${upload_source}` } }>
                        <div className="user-btn">TA的相关素材</div>
                    </Link>
                </div>
                <div className="container">
                    <div className="hover-btn">
                        <Button className="img-btn" size="small" onClick={ () => this.showBigImg({ visible: true }) }>
                            <i className="icon-font-ourimg">&#xf407;</i>
                        </Button>
                    </div>
                    <div className="img-element-container">
                        <img src={ big_img } className="img-element"/>
                    </div>
                    <div className="img-info">
                        <div className="title">{ title }</div>
                        <div className="info-list">
                            <p>图片ID：{ eid }</p>
                            <div>版权所有：<span className="copyright">
                                <QCRSelect editFlag={ false } purpose={ purpose } iconShow={ true }/>
                            </span></div>
                            <p>
                                所属分类：
                                {
                                    small_type && small_type.map((key) =>
                                        <span
                                            key={ key.toString() }
                                            className='detail-click-a'
                                            onClick={ () => this.clickJumpToList('small_type', key.toString()) }>
                                            { this.props.filterObj.small_type ?
                                                this.props.filterObj.small_type[key] : '' }
                                        </span>
                                    )
                                }
                            </p>
                            <p>体积：{ filesize }</p>
                            <p>规格：{ `${width} x ${height} px` }</p>
                            <p>格式：{ ext }</p>
                            <p>拍摄地点：
                                {
                                    location && location.map((item, index) => <span  key={ item }>
                                    <span
                                        className='detail-click-b'
                                        onClick={ () => this.clickJumpToList('city', this.props.locationDetail[item]) }>
                                        { item }</span>
                                    <span>{ index === location.length - 1 ? '' : '/' }</span>
                                </span>)
                                }
                            </p>
                        </div>
                        <div className="img-tags">
                            <div className="title">标签</div>
                            <div className="tags">
                                {
                                    keyword && keyword.map(item => <Tag key={ item }
                                                                        onClick={ this.onTagClick.bind(this, item) }>{ item }</Tag>)
                                }
                            </div>
                        </div>
                        <div>
                            <Button className="download-btn" type="primary" onClick={ this.downloadImg }>立即下载</Button>
                            <Button className="link-btn" onClick={ this.openDialog }>
                                <img className="link-icon" src="/img/link-icon.png"/>
                                生成链接
                            </Button>
                            <Button className="link-btn" onClick={ this.addShopCart } disabled={ user_shopcart }>
                                <img className="link-icon" src="/img/shop-icon.png"/>
                                { user_shopcart ? '已加入购物车' : '加入购物车' }
                            </Button>
                        </div>
                    </div>
                </div>
                <div className="footer">
                    <div>
                        <span className="stat-item">
                            <i className="icon-font-ourimg">&#xe09d;</i>
                            <span className="type">浏览</span>
                            <span className="num">{ browse }</span>
                        </span>
                        <span className="stat-item">
                            <i className="icon-font-ourimg big-icon">&#xf0aa;</i>
                            <span className="type">下载</span>
                            <span className="num">{ download }</span>
                        </span>
                    </div>
                    <div>
                        <span className="stat-item">
                            <div className="click-area" onClick={ this.props.collectDetailImg }>
                                <i className={ `icon-font-ourimg ${user_favorited ? 'checked' : ''}` }>&#xe09f;</i>
                                <span className="type save-btn">收藏</span>
                            </div>
                            <span className="num">{ favorite }</span>
                        </span>
                        <span className="stat-item">
                            <div className="click-area" onClick={ this.props.likeDetailImg }>
                                <i className={ `icon-font-ourimg big-icon ${user_praised ? 'checked' : ''}` }>&#xf06f;</i>
                                <span className="type save-btn">点赞</span>
                            </div>
                            <span className="num">{ praise }</span>
                        </span>
                    </div>
                </div>
                <Modal
                    visible={ createVisible }
                    footer={ null }
                    title={ null }
                    onCancel={ this.onCreateCancel }
                    width={ 380 }>
                    <div className="edit-confirm-content">
                        <div>
                            <p className="edit-hint">填写需要生成的图片规格</p>
                            <div className="edit-data">
                                宽<Input value={ imgWidth } onChange={ this.editWidth }/>
                                x&nbsp;&nbsp;&nbsp;
                                高<Input value={ imgHeight } onChange={ this.editHeight }/>
                            </div>
                        </div>
                    </div>
                    <div className="edit-confirm-btngroup">
                        <Button type="primary" disabled={ !imgWidth || !imgHeight }
                                onClick={ this.submitCreate }>确认</Button>
                    </div>
                </Modal>
                <Modal
                    visible={ linkVisible }
                    footer={ null }
                    title={ null }
                    onCancel={ this.onLinkCancel }
                    width={ 380 }>
                    <div className="edit-confirm-content link-modal">
                        外网链接：{ this.props.resizeUrl }
                    </div>
                    <div className="edit-confirm-btngroup">
                        <Button type="primary" onClick={ this.copyUrl }>复制</Button>
                    </div>
                </Modal>
                <Modal
                    visible={ copyrightFileVisible }
                    footer={ null }
                    title={ null }
                    onCancel={ this.onCopyrightFileCancel }
                    width={ 860 }>
                    <CopyrightFileModal
                        copyrightAuthDate={copyright_auth_date}
                        copyrightRealName={realname}
                        copyrightUserName={username}/>
                </Modal>
                <QImgModal visible={ imgVisible } url={ big_img } onClose={ this.showBigImg }/>
            </div>
        )
    }
}

export default DetailCard;
