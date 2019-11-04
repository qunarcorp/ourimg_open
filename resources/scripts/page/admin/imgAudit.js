import React, { Component } from "react";
import { observer, inject } from "mobx-react";
import { Route, withRouter, Link } from "react-router-dom";
import {
    adminAuditTypeMap,
    adminAuditLabelMap,
    rejectReasonMap
} from "CONST/map";
import {
    Tabs,
    Pagination,
    Button,
    Checkbox,
    Radio,
    Modal,
    message,
    Spin,
    Icon,
    Input,
    Row,
    Col
} from "antd";
import QImgInfo from "COMPONENT/qImgInfo";
import QBlank from "COMPONENT/qBlank";
import QImgModal from "COMPONENT/qImgModal";
import BulkEditCard from "./bulkEditCard";
import { adminApi } from "CONST/api";

const RadioGroup = Radio.Group;
const TabPane = Tabs.TabPane;
const Search = Input.Search;
const { TextArea } = Input;
const { rejectReason } = adminApi;
const antIcon = <Icon type="loading" style={{ fontSize: 24 }} spin />;

@inject(state => ({
    history: state.history,
    router: state.router,
    pathname: state.history.location.pathname,
    imgDataList: state.store.imgAudit.imgDataList,
    totalCount: state.store.imgAudit.totalCount,
    getDataList: state.store.imgAudit.getDataList,
    getAuditCount: state.store.imgAudit.getAuditCount,
    auditCount: state.store.imgAudit.auditCount,
    checkAll: state.store.imgAudit.checkAll,
    userInfo: state.store.global.userInfo,
    checkAllStatus: state.store.imgAudit.checkAllStatus,
    getCheckedIndexList: state.store.imgAudit.getCheckedIndexList,
    imgDataLoading: state.store.imgAudit.imgDataLoading,
    getFilterOption: state.store.google.getFilterOption,
    setAuditPass: state.store.imgAudit.setAuditPass,
    setAuditReject: state.store.imgAudit.setAuditReject,
    setAuditBatchReject: state.store.imgAudit.setAuditBatchReject,
    setAuditDel: state.store.imgAudit.setAuditDel,
    setAuditSuperDel: state.store.imgAudit.setAuditSuperDel,
    imgDataCheckList: state.store.imgAudit.imgDataCheckList,
    imgStar: state.store.imgAudit.imgStar,
    imgUnStar: state.store.imgAudit.imgUnStar,
}))
@withRouter
@observer
class ImgAudit extends Component {
    state = {
        tabKey: 1,
        page: {
            current: 1,
            pageSize: 10
        },
        submitType: "",
        submitTargetList: [],
        modalContent: "",
        starModalContent: "",
        visible: false,
        starVisible: false,
        rejectVisible: false,
        // rejectReason: "",
        // 自定义拒绝原因
        rejectDesc: "",
        keyword: "",
        rejectItem: [],
        bigImgVisible: false,
        imgUrl: "",
        delItem: "",
        delVisible: false,
        delType: "visit",
        rejectReasonList: [],
        // 选择的拒绝原因
        rejectReason: [],
        showRejectDesc: false
    };

    componentDidMount() {
        let { keyword, tabKey } = this.state;
        this.props.getDataList({
            offset: 0,
            limit: 10,
            keyword,
            audit_state: tabKey
        });
        this.props.getFilterOption();
        this.props.getAuditCount();
        this.getRejectReason();
    }

    async getRejectReason() {
        const res = await $.get(rejectReason);
        const { status, data } = res;
        if (status === 0) {
            this.setState({
                rejectReasonList: data
            });
        }
    }

    //
    changeRejectReason(reasonTag) {
        const { rejectReason } = this.state;
        const newReason = [...rejectReason];
        const index = newReason.indexOf(reasonTag);
        if (index === -1) {
            newReason.push(reasonTag);
        } else {
            newReason.splice(index, 1);
        }
        this.setState({
            rejectReason: newReason
        });
    }

    switchTab = key => {
        this.setState({
            tabKey: key,
            keyword: "",
            page: {
                current: 1,
                pageSize: 10
            },
            delType: key == 4 ? 'forbid' : 'visit'
        },()=>{
            this.props.getDataList({
                offset: 0,
                limit: 10,
                keyword: '',
                audit_state: key
            });
        });
    };

    onPageChange = (current, pageSize) => {
        this.props.getDataList({
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
    };

    handleSearch = keyword => {
        this.setState(
            {
                keyword: keyword.replace(/，/g, ","),
                page: { pageSize: 10, current: 1 }
            },
            () =>
                this.props.getDataList({
                    offset: 0,
                    limit: 10,
                    keyword,
                    audit_state: this.state.tabKey
                })
        );
    };

    onCancel = () => {
        this.setState({
            visible: false,
            rejectVisible: false,
            rejectReason: [],
            rejectDesc: "",
            delVisible: false,
            showRejectDesc: false
        });
    };
    batchConfirm = (type, arr) => {
        let indexArr = arr || this.props.getCheckedIndexList();
        let eids = indexArr.map(i => this.props.imgDataList[i].eid);
        let str = "";
        if (indexArr.length === 0) {
            message.warning("请至少选则一项操作");
            return;
        }
        if (type === "del") {
            this.setState({
                delItem: eids.join(","),
                delVisible: true
            });
        } else if (type === "pass") {
            str = arr
                ? "确认审核通过所选素材？"
                : "确认审核通过" + indexArr.length + "个素材？";
            this.setState({
                visible: true,
                submitType: type,
                submitTargetList: indexArr,
                modalContent: str
            });
        } else if (type === "reject") {
            this.setState({
                rejectItem: eids,
                rejectVisible: true
            });
        } else if (type == "star") {
            this.onStar(eids)
        }
    };

    handlePassImg = arr => {
        this.props.setAuditPass({ eid: arr.join(",") }, res => {
            if (res.ret || res.status === 0) {
                message.success(res.message || "操作成功");
                this.props.getAuditCount();
                this.onCancel();
                let { keyword, tabKey, page } = this.state;
                let { pageSize, current} = page;
                current = (this.props.imgDataList.length == arr.length) ? 1 : current
                let str = arr.length == 1
                    ? "确认要推荐所选素材？"
                    : "确认要推荐" + arr.length + "个素材？";
                let needStarArr = this.props.imgDataList.filter((item, index) => {
                    return item.star == false && arr.indexOf(item.eid) !== -1;
                })
                this.setState({
                        page: { pageSize: pageSize, current: current },
                        starVisible: !! needStarArr.length,
                        starModalContent: !! needStarArr.length ? str : '',
                        starImg: !! needStarArr.length ? arr : []
                    }, () => {
                        if (! needStarArr.length) {
                            this.props.getDataList({
                                offset: (current - 1) * pageSize,
                                limit: pageSize,
                                keyword,
                                audit_state: tabKey
                            })
                        }
                    });
            }
        });
    };
    handleShowRejectModal = id => {
        this.setState({
            rejectItem: [id],
            rejectVisible: true
        });
    };
    handleShowDelModal = id => {
        this.setState({
            delItem: id,
            delVisible: true
        });
    };
    handleBatchDel = () => {
        let { submitTargetList } = this.state;
        let eids = submitTargetList.map(i => this.props.imgDataList[i].eid);
        this.setState({
            delItem: eids.join(","),
            delVisible: true
        });
    };
    onDelChange = e => {
        this.setState({
            delType: e.target.value
        });
    };
    submitSuperDel = () => {
        this.props.setAuditSuperDel({
            eids: this.state.delItem,
            type: this.state.delType
        }, (res) => {
            if (res.ret || res.status === 0) {
                message.success(res.message || '操作成功');
                let { keyword, tabKey, page } = this.state;
                this.onCancel();
                this.props.getAuditCount();
                let { pageSize, current} = page;
                current = (this.props.imgDataList.length == 1) ? 1 : current
                this.setState(
                    {
                        page: { pageSize: pageSize, current: current }
                    },
                    () => this.props.getDataList({
                        offset: (current - 1) * pageSize,
                        limit: pageSize,
                        keyword,
                        audit_state: tabKey
                    })
                );
            }
        });
    };
    submitReject = () => {
        let { rejectItem, rejectReason, rejectDesc } = this.state;
        if(!rejectDesc && rejectReason.length === 0) {
            message.info('驳回原因不可为空');
            return;
        }
        let reject_reason = [...rejectReason];
        if(rejectDesc) {
            reject_reason.push(rejectDesc);
        }
        // 批量驳回
        if (rejectItem.length > 1) {
            let params = {
                eid: rejectItem,
                reject_reason
            };
            this.props.setAuditBatchReject(
                params,
                this.rejectCallback
            );
        // 驳回
        } else {
            let params = {
                eid: rejectItem[0],
                reject_reason
            };
            this.props.setAuditReject(params, this.rejectCallback);
        }
    };

    rejectCallback = res => {
        if (res.ret || res.status === 0) {
            message.success(res.message || "操作成功");
            this.props.getAuditCount();
            this.onCancel();
            let { rejectItem, keyword, tabKey, page } = this.state;
            let { pageSize, current} = page;
            current = (this.props.imgDataList.length == rejectItem.length) ? 1 : current

            this.setState(
                {
                    page: { pageSize: pageSize, current: current }
                },
                () => this.props.getDataList({
                    offset: (current - 1) * pageSize,
                    limit: pageSize,
                    keyword,
                    audit_state: tabKey
                })
            );
        }
    };

    onParamsChange = (key, e) => {
        this.setState({
            [key]: e.target ? e.target.value : e
        });
    };
    batchSubmit = () => {
        let { submitType, submitTargetList } = this.state;
        let eids = submitTargetList.map(i => this.props.imgDataList[i].eid);
        if (submitType === "pass") {
            this.handlePassImg(eids);
        } else if (submitType === "del") {
            this.props.setAuditDel({ eid: eids.join(",") }, res => {
                if (res.ret || res.status === 0) {
                    message.success(res.message || "操作成功");
                    this.props.getAuditCount();
                    this.onCancel();
                    let { keyword, tabKey, page } = this.state;

                    let { pageSize, current} = page;
                    current = (this.props.imgDataList.length == eids.length) ? 1 : current

                    this.setState(
                        {
                            page: { pageSize: pageSize, current: current }
                        },
                        () => this.props.getDataList({
                            offset: (current - 1) * pageSize,
                            limit: pageSize,
                            keyword,
                            audit_state: tabKey
                        })
                    );
                }
            });
        }
    };
    showBigImg = ({ visible, imgUrl }) => {
        this.setState({
            bigImgVisible: visible,
            imgUrl
        });
    };

    openRejectDesc = () => {
        this.setState({
            showRejectDesc: true
        });
    };

    keywordChange = (key, event) => {
        this.setState({[key]: event.target.value})
    }

    handleShowDelModal = id => {
        this.setState({
            delItem: id,
            delVisible: true
        });
    };
    onStar = eid => {
        if (eid.constructor !== Array) {
            eid = [eid];
        }

        let { keyword, tabKey, page } = this.state;
        let { pageSize, current } = page;

        this.props.imgStar(eid, (res) => {
            message.success(res.message || "操作成功");
            this.props.getDataList({
                offset: (current - 1) * pageSize,
                limit: pageSize,
                keyword,
                audit_state: tabKey
            })
        })
    };
    onUnStar = eid => {
        if (eid.constructor !== Array) {
            eid = [eid];
        }

        let { keyword, tabKey, page } = this.state;
        let { pageSize, current} = page;

        this.props.imgUnStar(eid, (res) => {
            message.success(res.message || "操作成功");
            this.props.getDataList({
                offset: (current - 1) * pageSize,
                limit: pageSize,
                keyword,
                audit_state: tabKey
            })
        })
    };

    batchStar = () => {
        let starImg = this.state.starImg;
        this.setState({
            starVisible: false,
            // starModalContent: "",
            // starImg: []
        }, () => {
            this.onStar(starImg)
        });
    }

    onCancelStar = () => {
        let { keyword, tabKey, page } = this.state;
        let { pageSize, current} = page;
        this.setState({
            starVisible: false,
            // starModalContent: "",
            // starImg: []
        }, () => {
            this.props.getDataList({
                offset: (current - 1) * pageSize,
                limit: pageSize,
                keyword,
                audit_state: tabKey
            })
        });
    }

    render() {
        let {
            page,
            tabKey,
            visible,
            starVisible,
            modalContent,
            starModalContent,
            rejectVisible,
            delType,
            delVisible,
            rejectReason,
            rejectDesc,
            imgUrl,
            bigImgVisible,
            rejectReasonList,
            showRejectDesc
        } = this.state;
        let {
            totalCount,
            imgDataList,
            auditCount,
            imgDataLoading,
            userInfo
        } = this.props;
        let checked = this.props.checkAllStatus == 1 ? true : false;
        return (
            <div className="img-audit-page content">
                <div className="header-manage__container">
                    <div className="header-manage__text--b">CHECK</div>
                    <div className="header-manage__text--f">素材审核</div>
                    <Tabs className="material-tab" onChange={this.switchTab}>
                        {Object.keys(adminAuditTypeMap).map(key => {
                            return (
                                <TabPane
                                    tab={`${key}(${
                                        auditCount[
                                            adminAuditLabelMap[
                                                adminAuditTypeMap[key]
                                            ]
                                        ]
                                    })`}
                                    key={adminAuditTypeMap[key]}
                                />
                            );
                        })}
                    </Tabs>
                </div>
                <div className="search-param">
                    <div className="upload-bulk-globalbtn-container">
                        <Row>
                            <Col span={10}>
                                {tabKey !== "4" && (
                                    <div>
                                        <Button className="batch-text-btn" onClick={this.props.checkAll}>
                                            全选
                                        </Button>
                                        {tabKey == 2 && (
                                            <Button className="batch-text-btn batch-star-text-btn" onClick={() =>
                                                this.batchConfirm("star")
                                            }>
                                                批量推荐
                                            </Button>
                                        )}
                                        {(tabKey == 1 || tabKey == 3) && (
                                            <Button className="batch-text-btn pass" onClick={() =>
                                                this.batchConfirm("pass")
                                            }>
                                                批量通过
                                            </Button>
                                        )}
                                        {(tabKey == 1 || tabKey == 2) && (
                                            <Button className="batch-text-btn" onClick={() =>
                                                this.batchConfirm("reject")
                                            }>
                                                批量驳回
                                            </Button>
                                        )}
                                        {tabKey == 2 &&
                                            userInfo.role.indexOf("super_admin") !==
                                                0 && (
                                                <Button className="batch-text-btn" onClick={() =>
                                                    this.batchConfirm("del")
                                                }>
                                                    批量删除
                                                </Button>
                                        )}
                                    </div>
                                )}
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
                </div>
                {imgDataList.length === 0 ? (
                    <QBlank type={`adminAudit${tabKey}`} />
                ) : (
                    <div className="result-content">
                        <Spin indicator={antIcon} spinning={imgDataLoading}>
                            {imgDataList.length === 0 ? (
                                <div className="placeholder-card" />
                            ) : (
                                imgDataList.map((item, index) => {
                                    // return <QImgInfo key={item.eid} index={index}/>
                                    return (
                                        <BulkEditCard
                                            onShow={this.showBigImg}
                                            key={item.eid}
                                            index={index}
                                            item={item}
                                            delImg={this.delImg}
                                            type={tabKey}
                                            onPass={() =>
                                                this.batchConfirm("pass", [
                                                    index
                                                ])
                                            }
                                            onReject={() =>
                                                this.handleShowRejectModal(
                                                    item.eid
                                                )
                                            }
                                            onDel={() =>
                                                this.handleShowDelModal(
                                                    item.eid
                                                )
                                            }
                                            onStar={() =>
                                                this.onStar(
                                                    item.eid
                                                )
                                            }
                                            onUnStar={() =>
                                                this.onUnStar(
                                                    item.eid
                                                )
                                            }
                                        />
                                    );
                                })
                            )}
                        </Spin>
                        <div className="upload-bulk-globalbtn-container">
                            <Row>
                                <Col span={10}>
                                    {tabKey !== "4" && (
                                        <div>
                                            <Button className="batch-text-btn" onClick={this.props.checkAll}>
                                                全选
                                            </Button>
                                            {tabKey == 2 && (
                                                <Button className="batch-text-btn batch-star-text-btn" onClick={() =>
                                                    this.batchConfirm("star")
                                                }>
                                                    批量推荐
                                                </Button>
                                            )}
                                            {(tabKey == 1 || tabKey == 3) && (
                                                <Button className="batch-text-btn pass" onClick={() =>
                                                    this.batchConfirm("pass")
                                                }>
                                                    批量通过
                                                </Button>
                                            )}
                                            {(tabKey == 1 || tabKey == 2) && (
                                                <Button className="batch-text-btn" onClick={() =>
                                                    this.batchConfirm("reject")
                                                }>
                                                    批量驳回
                                                </Button>
                                            )}
                                            {tabKey == 2 &&
                                                userInfo.role.indexOf("super_admin") !==
                                                    0 && (
                                                    <Button className="batch-text-btn" onClick={() =>
                                                        this.batchConfirm("del")
                                                    }>
                                                        批量删除
                                                    </Button>
                                            )}
                                        </div>
                                    )}
                                </Col>
                                <Col span={14}>
                                    <Pagination
                                        className="material-pagination"
                                        total={totalCount}
                                        current={page.current}
                                        pageSize={page.pageSize}
                                        onChange={this.onPageChange}
                                        size="small"
                                        onShowSizeChange={(current, size) =>
                                            this.onPageChange(1, size)
                                        }
                                        showSizeChanger
                                        showQuickJumper
                                    />
                                </Col>
                            </Row>
                        </div>
                    </div>
                )}
                <Modal
                    visible={visible}
                    footer={null}
                    title={null}
                    onCancel={this.onCancel}
                    width={380}
                >
                    <p className="edit-confirm-content">{modalContent}</p>
                    <div className="edit-confirm-btngroup">
                        <Button
                            type="primary"
                            onClick={this.batchSubmit}
                            className="left-btn"
                        >
                            确认
                        </Button>
                        <Button onClick={this.onCancel}>取消</Button>
                    </div>
                </Modal>
                <Modal
                    visible={ starVisible }
                    footer={null}
                    title={null}
                    onCancel={this.onCancelStar}
                    width={380}
                >
                    <p className="edit-confirm-content">{ starModalContent }</p>
                    <div className="edit-confirm-btngroup">
                        <Button
                            type="primary"
                            onClick={this.batchStar}
                            className="left-btn"
                        >
                            确认
                        </Button>
                        <Button onClick={this.onCancelStar}>取消</Button>
                    </div>
                </Modal>
                <Modal
                    className="q-modal reject-img-modal"
                    visible={rejectVisible}
                    footer={null}
                    title="驳回原因"
                    onCancel={this.onCancel}
                    width={880}
                    footer={
                        showRejectDesc
                            ? [
                                  <Button
                                      key="submit"
                                      type="primary"
                                      className="footer-button"
                                      onClick={this.submitReject}
                                      disabled={!rejectDesc && (rejectReason.length === 0)}
                                  >
                                      确认驳回
                                  </Button>
                              ]
                            : [
                                  <Button
                                      key="back"
                                      className="footer-button add-button--desc"
                                      onClick={this.openRejectDesc}
                                  >
                                      <span className="add-button--icon">
                                          +{" "}
                                      </span>
                                      添加自定义原因
                                  </Button>,
                                  <Button
                                      key="submit"
                                      type="primary"
                                      className="footer-button"
                                      onClick={this.submitReject}
                                      disabled={!rejectDesc && (rejectReason.length === 0)}
                                  >
                                      确认驳回
                                  </Button>
                              ]
                    }
                >
                    {rejectReasonList.map(reason => {
                        const { category_name, tags } = reason;
                        return (
                            <div className="container" key={category_name}>
                                <div className="title">{category_name}</div>
                                <div className="reason-container">
                                    {tags.map(tag => {
                                        const activeName = rejectReason.includes(
                                            tag
                                        )
                                            ? "reason-item--selected"
                                            : "reason-item";
                                        return (
                                            <div
                                                className={`reason-item--box ${activeName}`}
                                                key={`${reason}${tag}`}
                                                onClick={() =>
                                                    this.changeRejectReason(tag)
                                                }
                                            >
                                                {tag}
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>
                        );
                    })}
                    {showRejectDesc && (
                        <div className="reason-desc--box">
                            <TextArea
                                refs='rejectDescRef'
                                autoFocus
                                maxLength={50}
                                rows={5}
                                value={rejectDesc}
                                placeholder="请输入自定义原因..."
                                onChange={e =>
                                    this.onParamsChange("rejectDesc", e)
                                }
                            />
                            <span className='reason-desc--limit'>/限50字</span>
                        </div>
                    )}
                    {/* <Row gutter={16}>
                            <Col span={5} className="label">常见原因</Col>
                            <Col span={19} className="info">
                                <Select
                                    className="upload-edit-bar-select"
                                    onChange={(e)=>this.onParamsChange('rejectReason', e)}
                                    placeholder="请选择分类" value={rejectReason}>
                                    {
                                        Object.keys(rejectReasonMap).map(key =>
                                            <Option value={key} key={key}>{rejectReasonMap[key]}</Option>
                                        )
                                    }
                                </Select>
                            </Col>
                        </Row>
                        <Row gutter={16}>
                            <Col span={5} className="label">补充原因</Col>
                            <Col span={19} className="info">
                                <TextArea rows={4} value={rejectDesc}
                                    onChange={(e)=>this.onParamsChange('rejectDesc', e)}/>
                            </Col>
                        </Row> */}
                </Modal>
                <Modal
                    visible={delVisible}
                    footer={null}
                    title={null}
                    onCancel={this.onCancel}
                    width={480}
                    className="super-del-modal"
                >
                    <div className="edit-confirm-content">
                        <RadioGroup onChange={this.onDelChange} value={delType}>
                            <Radio
                                value="visit"
                                className="del-type"
                                disabled={tabKey == 4}
                            >
                                <span className="type">删除但仍可访问</span>
                                <div className="explain-text">
                                    图片仅做软删除，已做推广使用的素材，访问不受限
                                </div>
                            </Radio>
                            <Radio value="forbid" className="del-type">
                                <span className="type">删除且禁止访问</span>
                                <div className="explain-text">
                                    图片在删除同时，所有访问素材地址均被拦截
                                </div>
                            </Radio>
                        </RadioGroup>
                    </div>
                    <div className="edit-confirm-btngroup">
                        <Button
                            type="primary"
                            onClick={this.submitSuperDel}
                            className="left-btn"
                        >
                            确认
                        </Button>
                        <Button onClick={this.onCancel}>取消</Button>
                    </div>
                </Modal>
                <QImgModal
                    visible={bigImgVisible}
                    url={imgUrl}
                    onClose={this.showBigImg}
                />
            </div>
        );
    }
}

export default ImgAudit;
