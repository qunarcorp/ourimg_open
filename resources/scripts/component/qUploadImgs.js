import React, { Component } from 'react'
import { observer, inject } from 'mobx-react';
import { withRouter } from 'react-router-dom';
import { Upload, Icon, Button, message } from 'antd';
import { creditsManageApi } from "CONST/api";
import Sortable from 'react-sortablejs';

@inject(state => ({
    history: state.history,
    router: state.router,
    hash: state.router.location.hash
}))
@withRouter
@observer
class QUploadImgs extends Component {

    state = {
        fileList: [],
        imgUrls: [],
        showImgUrls: [],
        init: false
    };

    componentDidMount() {
        this.setState({
            init: this.props.init
        });
    }

    componentWillReceiveProps(nextProps) {
        if (!this.state.init) {
            let imgUrls = nextProps.defaultValue || [];
            let showImgUrls = nextProps.defaultShowValue || nextProps.defaultValue || [];
            let fileList = imgUrls.map((url, index) => ({
                url,
                response: {status:0, data:{img_url: url, img_resize: showImgUrls[index]}}
            }));
            this.setState({
                fileList,
                imgUrls,
                showImgUrls,
                init: true
            });
        }
    }

    handleChange = ({ file, fileList }) => {
        if (file.response && file.response.status !== 0) {
            message.warning(file.response.message);
            let availabelArr = fileList.filter(item => !!item.response && item.response.status === 0);
            this.setState({fileList:availabelArr});
            return;
        }
        if (this.props.type === 'upload' && fileList.length > 4) {
            message.warning('图片数量最多为4张');
            fileList = fileList.slice(-4);
        }

        this.setState({ fileList }, () => {
            let availabelArr = fileList.filter(item => !!item.response && item.response.status === 0);
            let imgUrls = availabelArr.map(item => item.response.data.img_url);
            let showImgUrls = availabelArr.map(item => item.response.data.img_resize);
            this.props.onChange(imgUrls);
            this.setState({imgUrls,showImgUrls});
        });
    };

    setMainImg = (index) => {
        if (this.props.type === 'upload') {
            let fileList = this.state.fileList.slice();
            let newArr = fileList.splice(index, 1).concat(fileList);
            let imgUrls = newArr.map(item => item.response.data.img_url);
            let showImgUrls = newArr.map(item => item.response.data.img_resize);
            this.props.onChange(imgUrls);
            this.setState({
                fileList: newArr,
                imgUrls,
                showImgUrls
            });
        }
    }

    delImg = (e,index) => {
        e.stopPropagation();
        let fileList = this.state.fileList.slice();
        fileList.splice(index, 1);
        let imgUrls = fileList.map(item => item.response.data.img_url);
        let showImgUrls = fileList.map(item => item.response.data.img_resize);
        this.props.onChange(imgUrls);
        this.setState({
            fileList,
            imgUrls,
            showImgUrls
        });
    }

    render() {
        const { fileList, showImgUrls } = this.state;
        const { type, sortable } = this.props;
        return (
            <div className="q-upload-imgs">
                <Upload
                    className="add-img-upload"
                    action={creditsManageApi.uploadImgs}
                    listType="picture-card"
                    fileList={fileList}
                    multiple
                    onPreview={this.handlePreview}
                    onChange={this.handleChange}
                    showUploadList={false}
                    >
                    <Button
                        className="add-img-btn">
                        <Icon type="plus" />
                    </Button>
                </Upload>
                {
                    sortable ?
                    <Sortable
                        className="img-list"
                        onChange={(order, sortable, evt) => {
                            let fileList = this.state.fileList.slice();
                            let file = fileList.splice(evt.oldIndex, 1);
                            let finalList = fileList.slice(0,evt.newIndex).concat(file).concat(fileList.slice(evt.newIndex, fileList.length));
                            let imgUrls = finalList.map(item => item.response.data.img_url);
                            let showImgUrls = finalList.map(item => item.response.data.img_resize);
                            this.props.onChange(imgUrls);
                            this.setState({
                                fileList: finalList,
                                imgUrls,
                                showImgUrls
                            });
                        }}
                    >
                    {
                        showImgUrls.map((url, index) =>
                            <div className="img-file" onClick={() => this.setMainImg(index)}>
                                <img className="img-item" src={url}/>
                                <i className="icon-font-ourimg close-btn hover-show"
                                    onClick={(e)=>this.delImg(e,index)}
                                >&#xe4d6;</i>
                                {
                                    type === 'upload' &&
                                    <div className="img-hint hover-show">设为主图</div>
                                }
                                {
                                    type === 'detail' &&
                                    <div className="img-hint">排序{index + 1}</div>
                                }
                            </div>)
                    }
                    </Sortable> :
                    <div className="img-list">
                    {
                        showImgUrls.map((url, index) =>
                            <div key={index} className="img-file" onClick={() => this.setMainImg(index)}>
                                <img className="img-item" src={url}/>
                                <i className="icon-font-ourimg close-btn hover-show"
                                    onClick={(e)=>this.delImg(e,index)}
                                >&#xe4d6;</i>
                                {
                                    type === 'upload' &&
                                    <div className="img-hint hover-show">设为主图</div>
                                }
                                {
                                    type === 'detail' &&
                                    <div className="img-hint">排序{index + 1}</div>
                                }
                            </div>)
                    }
                    </div>
                }
            </div>
        )
    }
}

export default QUploadImgs;
