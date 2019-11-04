import React, { Component } from 'react'
import { Button, message, Modal } from 'antd';
import { Link, withRouter } from 'react-router-dom';
import { inject, observer } from "mobx-react";
@inject(state => ({
    downloadImg: state.store.detail.downloadImg
}))
@withRouter
@observer
class QImgCard extends Component {

    state = {
        likeLoading: false,
        collectionLoading: false,
        addCartLoading: false,
        delVisible: false
    }
    downloadImg = (eid) => {
        if (!this.props.isLogin) {
            message.error('未登录用户不允许操作');
            return;
        }
        // this.props.downloadImg({ eid: eid });
        this.props.downloadImg({
            eid: eid,
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

    handleImgOperation(index, type) {
        let status = type + 'Loading';
        if (this.state[status]) {
            return;
        }
        this.setState({
            [status]: true
        });
        this.props.onOperate(index, type, () => {
            this.setState({
                [status]: false
            });
        });
    }

    showDeleteModal = (value) => {
        this.setState({
            delVisible: value
        });
    }

    delSubmit = () => {
        this.props.onOperate(this.props.index, 'del', () => {
            this.setState({
                delVisible: false
            });
        });
    }
    goToDetail = (eid) => {
        window.open('#/detail?eid=' + eid)
        // this.props.history.push('/detail?eid=' + eid);
    }
    goToAuthor = (username) => {
        this.props.history.push('/user?uid=' + username);
    }

    render() {
        let {
            eid, small_img500heihgt, big_img, download, user_praised,
            user_favorited, download_permission, realname, avatar, username, width, height, upload_source
        } = this.props.data;
        let { isLogin, isMyUser } = this.props;
        let edFlag;
        edFlag = (isLogin && isMyUser)
        const showWidth = width / height * 226;

        if (upload_source) {
            avatar = "/img/ff1a003aa731b0d4e2dd3d39687c8a54.png";
            realname = upload_source;
        }

        return (
            <div className="q-img-card">
                <img src={ small_img500heihgt }
                    style={{width: `${showWidth}px`, flexGrow: showWidth}}
                     className="img-element"/>
                <div className="hover-btn"
                     onClick={ () => this.props.onClickImg ? this.props.onClickImg(eid) : this.goToDetail(eid) }>
                    <div className={ 'hover-btn-layout' }>
                        <div className={ 'hover-btn-top' }>
                            <Button className="img-btn" style={ { background: 'rgba(0, 0, 0, 0.41)', width: '35px' } }
                                    size="small"
                                    onClick={ (e) => {
                                        e.stopPropagation();
                                        this.props.onShow({ visible: true, imgUrl: big_img })
                                    } }>
                                <i className="icon-font-ourimg">&#xf407;</i>
                            </Button>
                        </div>
                        <div className={ 'hover-btn-bottom' }>
                            { /*<Link to={ { pathname: "/user", search: `?uid=${username}` } }>*/ }
                            <div className={ 'author-info' } onClick={ (e) => {
                                e.stopPropagation();
                                this.goToAuthor(username);
                            } }>
                                <div className={ 'author-info-img' }>
                                    <img style={ { width: '100%', height: '100%' } }
                                         src={ avatar }
                                         alt=""/>
                                </div>
                                <div className={ 'author-info-name' }>{ realname }</div>
                            </div>
                            { /*</Link>*/ }
                            <div>
                                {
                                    isLogin ?
                                        <Button className={ `img-btn ${user_praised ? 'marked' : ''}` } size="small"
                                                onClick={ (e) => {
                                                    e.stopPropagation();
                                                    this.handleImgOperation(this.props.index, 'like')
                                                } }>
                                            <i className="icon-font-ourimg">&#xf06f;</i>
                                        </Button> : null
                                }
                                {
                                    isLogin ? <Button className={ `img-btn ${user_favorited ? 'marked' : ''}` }
                                                      size="small"
                                                      onClick={ (e) => {
                                                          e.stopPropagation();
                                                          this.handleImgOperation(this.props.index, 'collection')
                                                      } }>
                                        <i className="icon-font-ourimg">&#xe09f;</i>
                                    </Button> : null
                                }
                                {
                                    isLogin && download_permission ?
                                        <Button className={ `img-btn` }
                                                size="small"
                                                onClick={ (e) => {
                                                    e.stopPropagation();
                                                    this.downloadImg(eid)
                                                } }>
                                            <i className="icon-font-ourimg">&#xf0aa;</i>
                                        </Button> : null
                                }
                                {
                                    edFlag ? <Button className={ `img-btn` } size="small"
                                                     onClick={ (e) => {
                                                         e.stopPropagation();
                                                         this.props.onOperate(this.props.index, 'edit')
                                                     } }>
                                        <i className="icon-font-ourimg">&#xf1bf;</i>
                                    </Button> : null
                                }
                                {
                                    edFlag ? <Button className={ `img-btn` } size="small"
                                                     onClick={ (e) => {
                                                         e.stopPropagation();
                                                         this.showDeleteModal(true)
                                                     } }>
                                        <i className="icon-font-ourimg">&#xf05b;</i>
                                    </Button> : null
                                }
                            </div>
                        </div>

                    </div>


                </div>
                <Modal
                    visible={ this.state.delVisible }
                    footer={ null }
                    title={ null }
                    onCancel={ () => this.showDeleteModal(false) }
                    width={ 380 }
                    className="del-img-modal"
                >
                    <p className="edit-confirm-content">
                        <p>确认删除所选素材？</p>
                        {
                            download > 0 &&
                            <p className="hint-text">该素材被{ download }用户下载过，已获得的永久使用权不受删除操作的限制</p>
                        }
                    </p>
                    <div className="edit-confirm-btngroup">
                        <Button type="primary" onClick={ this.delSubmit } className='left-btn'>确认</Button>
                        <Button onClick={ () => this.showDeleteModal(false) }>取消</Button>
                    </div>
                </Modal>
            </div>
        )
    }
}

export default QImgCard;
