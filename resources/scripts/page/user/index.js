import React, { Component } from "react";
import { observer, inject } from "mobx-react";
import { withRouter, Link } from "react-router-dom";
import QGallery from "COMPONENT/qGallery";
import QSort from "COMPONENT/qSort";
import QBlank from "COMPONENT/qBlank";
import { Tabs, Input, Button, Icon, Modal } from "antd";
import { get } from "UTIL/mapMethod";
import CopyrightFileModal from 'COMPONENT/copyrightFileModal';

const TabPane = Tabs.TabPane;
const Search = Input.Search;
@inject(state => ({
    history: state.history,
    router: state.router,
    hash: state.router.location.hash,
    imgData: state.store.user.imgData,
    galleryLoading: state.store.user.galleryLoading,
    userInfo: state.store.user.userInfo,
    userName: state.store.global.userInfo.userName,
    getImgGallery: state.store.user.getImgGallery,
    updateImgGallery: state.store.user.updateImgGallery,
    modifyImg: state.store.component.modifyImg,
    delImg: state.store.component.delImg,
    getCartCount: state.store.global.getCartCount,
    delGalleryImg: state.store.user.delGalleryImg,
    totalCount: state.store.user.totalCount,
    getTotalCount: state.store.user.getTotalCount,
    tabsList: state.store.user.tabsList,
    getTabsList: state.store.user.getTabsList
}))
@withRouter
@observer
class User extends Component {
    state = {
        uid: "",
        dept: "",
        isMyself: false,
        myTab: "upload",
        sort: "0",
        offset: 0,
        limit: 10,
        loadCompleted: false,
        keyword: "",
        tabKey: 1,
        copyrightFileVisible: false,
    };

    componentDidMount() {
        let params = new URLSearchParams(this.props.history.location.search);
        let uid = params.get("uid");
        let dept = params.get("dept");
        // 联调
        this.props.getTotalCount({ username: uid, dept: dept });
        this.props.getTabsList();
        this.setState(
            {
                uid,
                dept,
                isMyself: uid === this.props.userName
            },
            this.loadData
        );
    }

    // url切换加载同一组件,数据更新
    componentWillReceiveProps(nextprops) {
        let nextparams = new URLSearchParams(nextprops.history.location.search);
        let nextuid = nextparams.get("uid");
        let nextdept = nextparams.get("dept");
        if (nextuid && (this.state.uid !== nextuid)) {
            this.setState(
                {
                    offset: 0,
                    loadCompleted: false,
                    uid: nextuid,
                    isMyself: nextuid === this.props.userName,
                    keyword: ""
                },
                () => {
                    this.props.getTotalCount({ username: nextuid });
                    this.loadData();
                }
            );
        }else if (nextdept && (this.state.dept !== nextdept)) {
            this.setState(
                {
                    offset: 0,
                    loadCompleted: false,
                    dept: nextdept,
                    isMyself: false,
                    keyword: ""
                },
                () => {
                    this.props.getTotalCount({ dept: nextdept });
                    this.loadData();
                }
            );
        }
    }

    loadData = () => {
        let {
            offset,
            limit,
            sort,
            uid,
            dept,
            myTab,
            isMyself,
            keyword,
            tabKey
        } = this.state;
        let params = {
            myTab,
            offset,
            limit,
            sort_by: sort,
            keyword,
            big_type: tabKey,
            audit_state: 2
        };
        if (!isMyself) {
            if (! uid && !dept) {
                return;
            }
            params = {
                ...params,
                page_source: "others",
                username: uid,
                dept: dept
            };
        }

        this.props.getImgGallery(params, count => {
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
    };

    sortChange = e => {
        this.setState(
            {
                offset: 0,
                limit: 10,
                loadCompleted: false,
                sort: e.target.value
                // keyword: ''
            },
            this.loadData
        );
    };

    switchTab = key => {
        this.setState(
            {
                tabKey: key,
                offset: 0,
                limit: 10,
                loadCompleted: false,
                sort: "0",
                keyword: ""
            },
            this.loadData
        );
    };

    reloadGallery = (index, type, callback) => {
        // 个人中心编辑删除预留
        if (type === "del" || type === "edit") {
            this.ownerOperate(index, type, callback);
            return;
        }
        this.props.updateImgGallery(index, type, res => {
            callback && callback();
            if (type === "addCart") {
                this.props.getCartCount();
            } else if (
                res.ret &&
                type === "collection" &&
                this.state.myTab === "favorite"
            ) {
                this.props.delGalleryImg(index);
            }
        });
    };

    ownerOperate = (index, type, callback) => {
        let target = this.props.imgData[index];
        if (type === "edit") {
            let params = {
                eid: target.eid,
                img: target.big_img,
                size: target.filesize,
                ext: target.ext,
                width: target.width,
                height: target.height,
                height_height: target.width + "x" + target.height + "px",
                city: target.place,
                city_id: target.city_id,
                place: target.place,
                title: target.title,
                keyword: target.keyword,
                purpose: target.purpose,
                small_type: target.small_type,
                upload_source: target.upload_source,
                bind_upload_source: target.upload_source,
                purchase_source: target.purchase_source,
                original_author: target.original_author,
                is_signature: target.is_signature,
                upload_source_type: target.upload_source.length == 0 ? 'personal' : 'department',
            };
            this.props.modifyImg(params, () => {
                this.props.history.push("edit");
            });
        }
        if (type === "del") {
            this.props.delImg({ eid: target.eid }, () => {
                this.props.delGalleryImg(index);
                callback && callback();
            });
        }
    };

    handleSearch = keyword => {
        this.setState(
            {
                offset: 0,
                limit: 10,
                loadCompleted: false,
                keyword
            },
            this.loadData
        );
    };

    // 维护searchValue ,否则从别人主页切自己主页,search value不清空
    handleChange = e => {
        this.setState({
            keyword: e.target.value
        });
    };

    openCopyrightFile = () => {
        this.setState({
            copyrightFileVisible: true,
        })
    }

    onCopyrightFileCancel = () => {
        this.setState({
            copyrightFileVisible: false,
        })
    }

    render() {
        let { isMyself, loadCompleted, keyword, tabKey } = this.state;
        let { imgData, userInfo, galleryLoading } = this.props;
        let isUpload = this.state.myTab === "upload";
        const {
            download_count = 0,
            favorite_count = 0,
            praise_count = 0,
            browse_count = 0
        } = this.props.totalCount;
        const { big_type = {} } = this.props.tabsList;
        let { copyrightFileVisible } = this.state;
        let copyright_auth_date = userInfo.auth_date;
        let copyright_username = userInfo.username;
        let copyright_name = userInfo.name;

        return (
            <div className="q-user-page">
                <div className="q-user-header">
                    <div className="total-count">
                        <div className="count-container">
                            <div className="title-text">收藏</div>
                            <div className="count-text">{favorite_count}</div>
                        </div>
                        <div className="count-container">
                            <div className="title-text">浏览</div>
                            <div className="count-text">{browse_count}</div>
                        </div>
                        <div className="user-info">
                            <img
                                src={userInfo ? userInfo.user_img : null}
                                className="avatar"
                            />
                            <div className="name">
                                {userInfo ? userInfo.name : null}
                                <Icon className="ml10 cursor-pointer" type="file-text"  onClick={ () => this.openCopyrightFile({ copyrightFileVisible: true }) }/>
                            </div>
                            {userInfo ? (
                                <div className="position">{`${get(
                                    userInfo,
                                    "dept[0]"
                                )}/${get(userInfo, "dept[1]")}`}</div>
                            ) : null}
                        </div>
                        <div className="count-container">
                            <div className="title-text">下载</div>
                            <div className="count-text">{download_count}</div>
                        </div>
                        <div className="count-container">
                            <div className="title-text">点赞</div>
                            <div className="count-text">{praise_count}</div>
                        </div>
                    </div>
                    <Tabs className="type-tab" onChange={this.switchTab}>
                        {Object.keys(big_type).map(key => {
                            return <TabPane tab={big_type[key]} key={key} />;
                        })}
                    </Tabs>
                </div>
                {// 矢量图,PSD,PPT模版展示数据为空的状态
                tabKey != 1 ? (
                    <QBlank type="loading" />
                ) : (
                    <div className="q-content">
                        <div className="search-container">
                            <Search
                                enterButton
                                value={keyword}
                                className="search"
                                placeholder="请输入关键字"
                                onSearch={this.handleSearch}
                                onChange={this.handleChange}
                            />
                            <QSort
                                value={this.state.sort}
                                onChange={this.sortChange}
                            />
                        </div>
                        {imgData.length ? (
                            <QGallery
                                loading={galleryLoading}
                                completed={loadCompleted}
                                elements={imgData}
                                load={this.loadData}
                                isUpload={isUpload}
                                reload={this.reloadGallery}
                            >
                                {/*{isMyself && (*/}
                                    {/*<Link to="/upload">*/}
                                        {/*<div className="q-img-card edit-card">*/}
                                            {/*<i className="icon-font-ourimg">*/}
                                                {/*&#xf298;*/}
                                            {/*</i>*/}
                                            {/*<div className="edit-text">*/}
                                                {/*上传新作品*/}
                                            {/*</div>*/}
                                        {/*</div>*/}
                                    {/*</Link>*/}
                                {/*)}*/}
                            </QGallery>
                        ) : (
                            <div>
                                <QBlank type="empty" />
                                {isMyself && <Link to="/upload" className='upload-image'>
                                    <Button type="primary">上传图片</Button>
                                    </Link>}
                            </div>
                        )}
                    </div>
                )}
                <Modal
                    visible={ copyrightFileVisible }
                    footer={ null }
                    title={ null }
                    onCancel={ this.onCopyrightFileCancel }
                    width={ 860 }>
                    <CopyrightFileModal
                        copyrightAuthDate={copyright_auth_date}
                        copyrightRealName={copyright_name}
                        copyrightUserName={copyright_username}/>
                </Modal>
            </div>
        );
    }
}

export default User;
