import React, { Component } from "react";
import { observer, inject } from "mobx-react";
import {
    HashRouter as Router,
    Route,
    withRouter,
    Link
} from "react-router-dom";
import { Tabs, Pagination, Button,Checkbox, Modal, message, Spin, Icon, Row, Col, Input } from "antd";
import { auditStatusMap, auditLabelMap } from 'CONST/map';
import QBatchEditModal from 'COMPONENT/qBatchEditModal.js';
import BulkEditCard from './bulkEditCard';
import QBlank from 'COMPONENT/qBlank';
import QImgModal from 'COMPONENT/qImgModal';
const Search = Input.Search;
const TabPane = Tabs.TabPane;
const antIcon = <Icon type="loading" style={{ fontSize: 24 }} spin />;

@inject(state => ({
    history: state.history,
    router: state.router,
    pathname: state.history.location.pathname,
    uploadsCount: state.store.myUpload.uploadsCount,
    getUploadCount: state.store.myUpload.getUploadCount,
    getFilterOption: state.store.google.getFilterOption,
    totalCount: state.store.myUpload.totalCount,
    imgDataList: state.store.myUpload.imgDataList,
    imgDataCheckList: state.store.myUpload.imgDataCheckList,
    checkAllStatus: state.store.myUpload.checkAllStatus,
    getCheckedIndexList: state.store.myUpload.getCheckedIndexList,
    editImgs: state.store.myUpload.editImgs,
    uploadDel: state.store.myUpload.uploadDel,
    checkAll: state.store.myUpload.checkAll,
    batchEditData: state.store.myUpload.batchEditData,
    resetBatchEditParams: state.store.myUpload.resetBatchEditParams,
    getUploadImgs: state.store.myUpload.getUploadImgs,
    imgDataLoading: state.store.myUpload.imgDataLoading,
    saveImg: state.store.myUpload.saveImg,
    onEditStatus: state.store.myUpload.onEditStatus,
}))

@withRouter
@observer
class MyUpload extends Component {

    state = {
        confirm: true,
        tabKey: '0',
        batchVisible: false,
        page: {
            current: 1,
            pageSize: 10
        },
        submitType: '',
        submitTargetList: [],
        modalContent: '',
        modalHintContent: '',
        visible: false,
        bigImgVisible: false,
        imgUrl: '',
        keyword: ''
    };

    componentDidMount() {
        let nextparams = new URLSearchParams(this.props.history.location.search);
        let type = nextparams.get("type");
        if (type) {
            this.setState({
                tabKey: type
            });
        }
        let { current, pageSize } = this.state.page;
        this.props.getUploadCount();
        this.props.getFilterOption();
        this.props.getUploadImgs({
            offset: (current - 1) * pageSize,
            limit: pageSize,
            audit_state: type || this.state.tabKey
        });
    }

    onPageChange = (current, pageSize) => {
        this.props.getUploadImgs({
            offset: (current - 1) * pageSize,
            limit: pageSize,
            audit_state: this.state.tabKey,
            keyword: this.state.keyword
        });
        this.setState({
            page: {
                current,
                pageSize
            }
        });
    }

    onComfrimChange = (e) => {
        this.setState({
            confirm: e.target.checked
        })
    }

    switchTab = (key) => {
        this.setState({
            tabKey: key,
            keyword: '',
            page: {
                current: 1,
                pageSize: 10
            }
        }, () => {
            this.props.getUploadImgs({
                offset: 0,
                limit: 10,
                audit_state: key,
                keyword: this.state.keyword
            });
            this.props.getUploadCount();
        });
    }

    confirm = (type) => {
        let indexArr = this.props.getCheckedIndexList();
        if (indexArr.length === 0) {
            message.warning('请至少选则一项操作');
            return ;
        }
        if (type === 'del') {
            this.delImg(this.props.getCheckedIndexList());
            return ;
        }
        let arr = [];
        indexArr.map(item => {
            arr.push(item + 1);
        })
        let str = `确认提交所选的${arr.length}项素材么？`
        this.setState({
            visible: true,
            submitType: type,
            submitTargetList: indexArr,
            modalContent: str,
            modalHintContent: ''
        })
    }

    openBatchModal = () => {
        let indexArr = this.props.getCheckedIndexList();
        if (indexArr.length === 0) {
            message.warning('请至少选则一项操作');
            return ;
        }
        this.setState({
            batchVisible: true
        });
        this.props.resetBatchEditParams();
    }
    handleBatchSubmit = (validate, params, callback) => {
        let indexArr = this.props.getCheckedIndexList();
        this.props.batchEditData(indexArr, params);
        this.props.onEditStatus(indexArr);
        this.setState({
            batchVisible: false,
        });

        callback()
    }
    handleBatchClose = () => {
        this.setState({
            batchVisible: false
        });
    }

    delImg = (indexArr) => {
        let hintStr = '',
        str = '确认删除所选的' + (indexArr.length > 1 ? `${indexArr.length}个`: '') + '素材吗？';
        if (indexArr.length === 1) {
            let download = this.props.imgDataList[indexArr[0]].download;
            hintStr = download > 0 ? `该素材被${download}用户下载过` : '';
        } else {
            let totalDownload = 0, imgCount = 0;
            indexArr.map(index => {
                let download = parseInt(this.props.imgDataList[index].download);
                if (download > 0) {
                    imgCount += 1;
                    totalDownload += download;
                }
            });
            hintStr = totalDownload > 0 ?
            `您选中的${indexArr.length}个素材中，有${imgCount}个素材被${totalDownload}用户下载过` : '';
        }

        this.setState({
            visible: true,
            modalContent: str,
            modalHintContent: hintStr,
            submitType: 'del',
            submitTargetList: indexArr
        })
    }
    onCancel = () => {
        this.setState({
            visible: false
        })
    }

    checkReady = () => {
        let submitTargetList = this.state.submitTargetList;
        let imgDataCheckList = this.props.imgDataCheckList.slice();
        for (let i = 0; i < submitTargetList.length; i++) {
            if (imgDataCheckList[submitTargetList[i]].edit) {
                message.warning('提交失败，第' + (submitTargetList[i] + 1) + '项未保存')
                this.onCancel()
                return false;
            }
            if (!imgDataCheckList[submitTargetList[i]].ready) {
                message.warning('提交失败，第' + (submitTargetList[i] + 1) + '项未被编辑')
                this.onCancel()
                return false;
            }
        }
        return true;
    }

    submit = () => {
        if (this.state.submitType === 'submit') {
            if (!this.checkReady()) {
                return ;
            }
            this.props.editImgs(this.state.submitTargetList, () => {
                this.reloadData();
                this.onCancel();
            });
        } else {
            this.props.uploadDel(this.state.submitTargetList, () => {
                this.reloadData();
                this.onCancel();
            });
        }
    }
    showBigImg = ({visible, imgUrl}) => {
        this.setState({
            bigImgVisible: visible,
            imgUrl
        });
    }

    reloadData = () => {
        //TODO: 判断是否最后一页
        this.props.getUploadCount((data) => {
            let total = data[auditLabelMap[this.state.tabKey]];
            let { current, pageSize } = this.state.page;
            if (total > 0 && (current - 1) * pageSize >= total) {
                current = 1;
            }
            this.setState({
                page: {
                    current: current,
                    pageSize: pageSize
                }
            }, () => {
                this.props.getUploadImgs({
                    offset: (current - 1) * pageSize,
                    limit: pageSize,
                    audit_state: this.state.tabKey,
                    keyword: this.state.keyword
                });
            })
        });
    }

    keywordChange = (key, event) => {
        this.setState({[key]: event.target.value})
    }

    handleSearch = keyword => {
        let { current, pageSize } = this.state.page;
        this.setState(
            {
                keyword: keyword.replace(/，/g, ","),
                page: { pageSize: 10, current: 1 }
            },
            () => {
                this.props.getUploadImgs({
                    offset: (current - 1) * pageSize,
                    limit: pageSize,
                    audit_state: this.state.tabKey,
                    keyword: this.state.keyword
                });
            }
        );
    };

    render() {
        let { confirm, batchVisible, page,  visible, modalContent, tabKey,
            modalHintContent, imgUrl,  bigImgVisible } = this.state;
        let { uploadsCount, imgDataList, totalCount, imgDataLoading } = this.props;
        let checked = this.props.checkAllStatus == 1 ? true : false;

        // let dataList = imgDataList.slice();
        // console.log(dataList[0] ? dataList[0].title : dataList[0])

        return (
            <div className="my-upload-page">
                <div className="content">
                    <div className="header-manage__container">
                        <div className="header-manage__text--b">UPLOAD</div>
                        <div className="header-manage__text--f">我的上传</div>
                        <Tabs className="material-tab" activeKey={tabKey} onChange={this.switchTab}>
                            {Object.keys(auditStatusMap).map(key => {
                                return <TabPane tab={`${key}(${uploadsCount[auditLabelMap[auditStatusMap[key]]]})`} key={auditStatusMap[key]} />;
                            })}
                        </Tabs>
                    </div>
                    <div className="operate-box">
                        <Row>
                            <Col span={10}>
                                <div className="upload-bulk-globalbtn-container" key="container">
                                    <Button className="batch-text-btn" onClick={ this.props.checkAll }>
                                        全选
                                    </Button>
                                    {
                                        tabKey == 0
                                        &&
                                        <Button className="batch-text-btn batch-edit-text-btn" onClick={ this.openBatchModal }>
                                            批量编辑
                                        </Button>
                                    }
                                    <Button className="batch-text-btn" onClick={ this.confirm.bind(this, 'del') }>
                                        批量删除
                                    </Button>
                                </div>
                            </Col>
                            <Col span={14}>
                                <Search
                                    enterButton
                                    className="search"
                                    value={ this.state.keyword }
                                    onChange={ this.keywordChange.bind(this, 'keyword') }
                                    placeholder="请输入关键字"
                                    onSearch={this.handleSearch}
                                />
                            </Col>
                        </Row>
                    </div>
                    <div className="upload-bulk-item">
                        <Spin indicator={antIcon} spinning={imgDataLoading}>
                        {
                            imgDataList.length === 0 ? <QBlank type="empty"/>: imgDataList.map((item, index) => {
                                return (
                                    <BulkEditCard
                                            reload={this.reloadData}
                                            onShow={this.showBigImg}
                                            key={item.eid}
                                            keyN={item.eid} index={index}
                                            item={item} delImg={this.delImg}
                                            type={tabKey}/>
                                )
                            })
                        }
                        </Spin>
                        <div className="operate-box padding-none">
                            {
                              imgDataList.length === 0
                              ?
                              ''
                              :
                              [
                                  <Row>
                                      <Col span={10}>
                                          <div className="upload-bulk-globalbtn-container" key="container">
                                              <Button className="batch-text-btn" onClick={ this.props.checkAll }>
                                                  全选
                                              </Button>
                                              {
                                                  tabKey == 0
                                                  &&
                                                  <Button className="batch-text-btn batch-edit-text-btn" onClick={ this.openBatchModal }>
                                                      批量编辑
                                                  </Button>
                                              }
                                              <Button className="batch-text-btn" onClick={ this.confirm.bind(this, 'del') }>
                                                  批量删除
                                              </Button>
                                          </div>
                                      </Col>
                                      <Col span={14}>
                                          <Pagination
                                              key="pagination"
                                              className="material-pagination"
                                              total={totalCount}
                                              current={page.current}
                                              pageSize={page.pageSize}
                                              onChange={this.onPageChange}
                                              onShowSizeChange={(current, size)=>this.onPageChange(1, size)}
                                              showSizeChanger showQuickJumper />
                                      </Col>
                                  </Row>
                              ]
                            }
                        </div>
                    </div>
                </div>
                {
                    tabKey !== 2 &&
                    <Link className="rule-link" to="/help/auditRule">
                        如何快速通过审核
                        <i className="icon-font-ourimg">&#xe1fd;</i>
                    </Link>
                }
                {
                    imgDataList.length !== 0 && tabKey == 0 &&
                    <div className="upload-confirm">
                        <Button
                            type="primary" className="upload-confirm-button"
                            disabled={!confirm} onClick={this.confirm.bind(this, 'submit')}>
                        提交审核</Button>
                        <Checkbox
                            className="upload-confirm-checkbox"
                            checked={confirm}
                            onChange={this.onComfrimChange}>
                        我已认真阅读<Link className="link" to="/help/copyright">《去哪儿图片库加入协议》</Link></Checkbox>
                        <div className="hint-text">承诺对上传的素材拥有全部的知识产权，不存在版权不清晰或侵犯第三方合法权益的行为</div>
                    </div>
                }
                <Modal
                    visible={visible}
                    footer={null}
                    title={null}
                    onCancel={this.onCancel}
                    width={380}
                    className="upload-operate-modal"
                    >
                    <div className="edit-confirm-content">
                        <div>{modalContent}</div>
                        {
                            modalHintContent && <div className="hint-text">{modalHintContent}</div>
                        }
                        {
                            modalHintContent && <div className="hint-text">已获得的永久使用权不受删除操作的限制</div>
                        }
                    </div>
                    <div className="edit-confirm-btngroup">
                        <Button type="primary" onClick={this.submit} className='left-btn'>确认</Button>
                        <Button onClick={this.onCancel}>取消</Button>
                    </div>
                </Modal>
                <QBatchEditModal
                    visible={batchVisible}
                    onOk={this.handleBatchSubmit}
                    onCancel={this.handleBatchClose}
                ></QBatchEditModal>
                <QImgModal visible={bigImgVisible} url={imgUrl} onClose={this.showBigImg}/>
            </div>
        )
    }
}

export default MyUpload;
