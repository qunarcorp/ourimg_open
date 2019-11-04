import React, { Component } from 'react'
import { observer, inject } from 'mobx-react';
import { withRouter, Link } from 'react-router-dom';
import { Upload, Progress, Icon, Checkbox, message } from 'antd';
import CopyrightModal from './copyrightModal';
import RepeatModal from './repeatModal';
const Dragger = Upload.Dragger;
let uploadNum = 0;

@inject(state => ({
    history: state.history,
    router: state.router,
    hash: state.router.location.hash,
    uploadImg: state.store.component.uploadImg,
    resetImgData: state.store.component.resetImgData,
    showRepeatModal: state.store.repeatModal.showRepeatModal,
    openRepeatModal: state.store.repeatModal.openRepeatModal,
    closeRepeatModal: state.store.repeatModal.closeRepeatModal,
    initDuplicateImg: state.store.component.initDuplicateImg,
    imgDataList: state.store.component.imgDataList,
    imgData: state.store.component.imgData,
    userInfo: state.store.global.userInfo,
    duplicateImg: state.store.component.duplicateImg
}))

@withRouter
@observer
export default class UploadCard extends Component {

    state = {
        confirm: false,
        // loading: false,
        multiple: false,
        startUpload: false,
        totalFile: 0,
        progressNum: 0
        // copyrightVisible: true
    }

    componentDidMount() {
        this.props.resetImgData();
        // this.showCopyright(true);
        this.setState({
            confirm: this.props.userInfo.auth_state !== "0"
        })
    }

    onComfrimChange = (e) => {
        this.setState({
            confirm: e.target.checked
        })
    }

    checkReadFile = (e) => {
        if (!this.state.confirm) {
            message.error('请阅读《去哪儿图片库加入协议》');
            e.preventDefault();
        }
    }

    beforeUpload = (file) => {
        let type = file.name.split('.').pop().toLocaleLowerCase();
        const arr = ['jpg', 'jpeg', 'png', 'tiff', 'gif', 'bmp', 'heic'];
        const Size1G = 1024 * 1024 * 1024;
        if (!this.state.confirm) {
            message.error('请阅读《去哪儿图片库加入协议》');
            return false;
        } else if (arr.indexOf(type) === -1) {
            message.error('图片格式不支持');
            return false;
        } else if (file.size > Size1G) {
            message.error('图片大小超过限制');
            return false;
        } else {
            if (uploadNum > 19) { //最多上传20张图片
                return false;
            }
            uploadNum ++;
            if (uploadNum > 1) {
                this.setState({
                    multiple: true
                })
            }
        }
    }

    customRequest = (data) => {
        // return ;
        this.setState({
            startUpload: true,
            totalFile: uploadNum,
            progressNum: 0
        });
        let formdata = new FormData();
        formdata.append('image', data.file)
        formdata.append('big_type', 1)
        let multiple = this.state.multiple;
        const { openRepeatModal } = this.props;
        this.props.uploadImg(formdata, multiple, (statusCode) => {
            uploadNum -- ;
            this.setState({progressNum: this.state.progressNum + 1});
            if (!multiple) { //非批量上传
                this.setState({startUpload: false});
                if (statusCode === 0) {
                    // 点击确定跳转
                    this.props.history.push('/edit')
                }else if (statusCode === 108 || statusCode === 103) {
                    openRepeatModal();
                }
            } else {
                if (uploadNum === 0) {
                    this.setState({startUpload: false});
                    if (this.props.duplicateImg.length > 0) {
                        openRepeatModal();
                    } else {
                        this.props.history.push('/bulkedit');
                    }
                    // 点击确定跳转
                    // this.props.history.push('/bulkedit')
                }
            }
        });
    }

    clickJumpToRepeat = () => {
        const { multiple } = this.state,
        { closeRepeatModal, initDuplicateImg, imgDataList, imgData } = this.props;
        initDuplicateImg();
        closeRepeatModal();
        // 至少一张上传成功才可以跳转
        if(multiple && imgDataList.length) {
            this.props.history.push('/bulkedit')
        }else if(!multiple && Object.keys(imgData).length){
            this.props.history.push('/edit');
        }
    }

    showCopyright = (visible) => {
        this.setState({
            copyrightVisible: visible
        })
    }

    // render() {
    //     let { confirm, copyrightVisible } = this.state;
    //     const { showRepeatModal } = this.props;
    // showCopyright = (visible) => {
    //     this.setState({
    //         copyrightVisible: visible
    //     })
    // }

    render() {
        let { confirm, startUpload, totalFile, progressNum } = this.state;
        const { showRepeatModal } = this.props;
        return (<section className="upload-card-container">
            <div className="upload-card">
                <div className="upload-content">
                {
                    startUpload &&
                    <div className="upload-waiting-area">
                        <Progress
                            className="upload-progress"
                            // strokeColor="#F275A1"
                            strokeColor="#3D8BE9"
                            percent={totalFile === 0 ? 0 : Math.round(progressNum/totalFile) * 100}
                            status="active"/>
                        <div className="upload-hint">图片上传中…</div>
                    </div>
                }
                <div
                    className={`upload-area ${startUpload ? 'hidden' : ''}`}
                    onClick={this.checkReadFile}>
                    <Dragger
                        beforeUpload={this.beforeUpload}
                        customRequest={this.customRequest}
                        // disabled={!confirm}
                        multiple={true}
                        // onChange={this.onChange}
                        fileList={[]}
                        >
                        <div className="upload-dargger">
                            <Icon type="cloud-upload" className="upload-icon"/>
                            <span>点击或拖拽上传图片，批量上传单次最多上传20张图片</span>
                            <span className="upload-text">*仅支持图片宽度2000以上，体积1G以下，格式为PNG/JPG/GIF/BMP/TIFF/HEIC的图片</span>
                        </div>
                    </Dragger>
                </div>
                </div>
                <div className="upload-confirm">
                    <Checkbox className="upload-confirm-checkbox" checked={confirm} onChange={this.onComfrimChange}>
                        我已认真阅读<Link className="link" to="/help/copyright">《去哪儿图片库加入协议》</Link>
                    </Checkbox>
                    <div className="hint-text">承诺对上传的素材拥有全部的知识产权，不存在版权不清晰或侵犯第三方合法权益的行为</div>
                </div>
                <Link className="rule-link" to="/help/auditRule">
                    如何快速通过审核
                    <i className="icon-font-ourimg">&#xe1fd;</i>
                </Link>
            </div>
            <CopyrightModal visible={this.props.userInfo.auth_state === "0"}/>
            {showRepeatModal && <RepeatModal clickJumpToRepeat={this.clickJumpToRepeat}/>}
        </section>)
    }
}
