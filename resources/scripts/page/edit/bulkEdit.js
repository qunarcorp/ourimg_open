import React, { Component } from 'react'
import { observer, inject } from 'mobx-react';
import { withRouter, Link } from 'react-router-dom';
import BulkEditCard from './bulkEditCard';
import QBatchEditModal from 'COMPONENT/qBatchEditModal.js';
import { Button,Checkbox,Modal, message } from 'antd';
import QImgModal from 'COMPONENT/qImgModal';
@inject(state => ({
    history: state.history,
    router: state.router,
    hash: state.router.location.hash,
    userInfo: state.store.global.userInfo,
    getFilterOption: state.store.google.getFilterOption,
    imgDataList: state.store.component.imgDataList,
    imgDataCheckList: state.store.component.imgDataCheckList,
    checkAllStatus: state.store.component.checkAllStatus,
    getCheckedIndexList: state.store.component.getCheckedIndexList,
    editImgs: state.store.component.editImgs,
    uploadDel: state.store.component.uploadDel,
    checkAll: state.store.component.checkAll,
    batchEditData: state.store.component.batchEditData,
    batchEditParams: state.store.component.batchEditParams,
    resetBatchEditParams: state.store.component.resetBatchEditParams,
    onEditStatus: state.store.component.onEditStatus,
    saveImg: state.store.component.saveImg,
    setImgDataList: state.store.component.setImgDataList,
    resetBatchPrepareNum: state.store.component.resetBatchPrepareNum,
    component: state.store.component,
}))

@withRouter
@observer
export default class UploadBulkEdit extends Component {

    state = {
        visible: false,
        confirm: true,
        submitType: '',
        submitTargetList: [],
        modalContent: '',
        batchVisible: false,
        bigImgVisible: false,
        imgUrl: ''
    }

    componentDidMount() {
        if (this.props.imgDataList.length === 0) {
            this.props.history.push('/upload');
        } else {
            this.props.getFilterOption();
            // if (this.props.imgDataList.slice().length === 0) {
                // this.props.history.push('/upload')
            // }
        }
    }

    componentWillReceiveProps(nextprops) {
        if (nextprops.imgDataList.length === 0) {
            this.props.history.push('/upload');
        }
    }

    onComfrimChange = (e) => {
        this.setState({
            confirm: e.target.checked
        })
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

        let imgDataCheckList = this.props.imgDataCheckList.slice()

        this.setState({
            visible: true,
            submitType: type,
            submitTargetList: indexArr,
            modalContent: str
        })
    }

    delImg = (indexArr) => {
        let str = '确认删除所选的' + (indexArr.length > 1 ? `${indexArr.length}个`: '') + '素材吗？';
        this.setState({
            visible: true,
            modalContent: str,
            submitType: 'del',
            submitTargetList: indexArr
        })
    }

    onCancel = () => {
        this.setState({
            visible: false
        })
        if (this.props.imgDataList.length === 0) {
            this.props.history.replace('/upload');
        }
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

            this.props.editImgs(this.state.submitTargetList, (res) => {
                if (res.status === 0) {
                    if (res.data.fail.length === 0) {
                        this.props.checkAllStatus == 1 && this.props.checkAll();
                        this.props.history.push('/material/myUpload?type=1');
                    }
                } else {
                    this.onCancel();
                }
            });
        } else {
            this.props.uploadDel(this.state.submitTargetList, () => {
                this.props.checkAllStatus == 1 && this.props.checkAll();
                this.onCancel();
            });
        }
    }

    openBatchModal = () => {
        let indexArr = this.props.getCheckedIndexList();
        if (indexArr.length === 0) {
            message.warning('请至少选则一项操作');
            return ;
        }
        this.props.resetBatchEditParams();
        this.setState({
            batchVisible: true
        });
        this.props.resetBatchPrepareNum();
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
    showBigImg = ({visible, imgUrl}) => {
        this.setState({
            bigImgVisible: visible,
            imgUrl
        });
    }
    render() {
        let { visible, batchVisible, modalContent, bigImgVisible, imgUrl } = this.state;
        let { imgDataList } = this.props;

        let imgDataCheckList = this.props.imgDataCheckList.slice()
        let imgDataCheckListChecked = imgDataCheckList.filter(function(item){
            return item['check'];
        })

        // let checked = this.props.checkAllStatus == 1 ? true : false;
        let checked = imgDataCheckList.length == imgDataCheckListChecked.length;

        return (<section className="upload-bulk-container">
            <div className="upload-bulk-item">
                <div className="upload-bulk-globalbtn-container">
                    <div class="check-icon-box" onClick={this.props.checkAll}>
                        <i className={`icon-font-checkbox big ${checked ? 'checked' : 'none-checked'}`}>
                            &#xe337;
                        </i>
                    </div>
                    <span className="mr15 cursor-pointer" onClick={this.props.checkAll}>
                        <span class="fw600">全选</span>
                    </span>
                    <span className="text-btn-primary" onClick={this.confirm.bind(this, 'del')}>批量删除</span>
                    <span className="text-btn-primary" onClick={this.openBatchModal}>批量编辑</span>
                </div>
                {
                    imgDataList.map((item, index) => {
                        return <BulkEditCard
                            key={item.eid}
                            index={index}
                            item={item}
                            onShow={this.showBigImg}
                            delImg={this.delImg}>
                            </BulkEditCard>
                    })
                }
                <div className="upload-bulk-globalbtn-container">
                    <div class="check-icon-box" onClick={this.props.checkAll}>
                        <i className={`icon-font-checkbox big ${checked ? 'checked' : 'none-checked'}`}>
                            &#xe337;
                        </i>
                    </div>
                    <span className="mr15 cursor-pointer" onClick={this.props.checkAll}>
                        <span class="fw600">全选</span>
                    </span>
                    <span className="text-btn-primary" onClick={this.confirm.bind(this, 'del')}>批量删除</span>
                    <span className="text-btn-primary" onClick={this.openBatchModal}>批量编辑</span>
                </div>
            </div>
            <div className="upload-confirm">
                <Button type="primary" className="upload-confirm-button" disabled={!confirm} onClick={this.confirm.bind(this, 'submit')}>提交审核</Button>
                <Checkbox className="upload-confirm-checkbox" checked={this.state.confirm} onChange={this.onComfrimChange}>我已认真阅读<Link className="link" to="/help/copyright">《去哪儿图片库加入协议》</Link></Checkbox>
                <div className="hint-text">承诺对上传的素材拥有全部的知识产权，不存在版权不清晰或侵犯第三方合法权益的行为</div>
            </div>
            <Modal
                visible={visible}
                footer={null}
                title={null}
                onCancel={this.onCancel}
                width={380}
                >
                <p className="edit-confirm-content">{modalContent}</p>
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
        </section>)
    }
}
