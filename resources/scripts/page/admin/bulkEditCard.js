import React, { Component } from 'react'
import { observer, inject } from 'mobx-react';
import { Link, withRouter } from 'react-router-dom';
import { purposeMap, rejectReasonMap } from 'CONST/map';
import QCRSelect from 'COMPONENT/qCRSelect';
import { Button, Checkbox, Radio, Select, Row, Col, Modal, Timeline, Tag, Popover, Table } from 'antd';

const { Column } = Table;
const Option = Select.Option;

@inject(state => ({
    history: state.history,
    router: state.router,
    hash: state.router.location.hash,
    filterObj: state.store.google.filterObj,
    userInfo: state.store.global.userInfo,
    getCitySuggest: state.store.imgAudit.getCitySuggest,
    imgDataCheckList: state.store.imgAudit.imgDataCheckList,
    changeCheckStatus: state.store.imgAudit.changeCheckStatus,
    cityList: state.store.imgAudit.cityList,
    timelineLog: state.store.imgAudit.timelineLog,
    getTimelineData: state.store.imgAudit.getTimelineData
}))

@withRouter
@observer
export default class BulkEditCard extends Component {

    state = {
        params: {
            title: '',
            purpose: '',
            small_type: [],
            imgLocation: '',
            keyword: [],
            upload_source: '',
            purchase_source: '',
            original_author: '',
            is_signature: false,
            upload_source_type: 'personal',
            logVisible: false
        }
    }

    componentDidMount() {
        let title = this.props.item.title || this.props.item.file_name;
        this.setState({
            params: {
                ...this.state.params,
                purpose: !this.props.item.purpose || this.props.item.purpose == 0 ? '' : this.props.item.purpose,
                small_type: !this.props.item.small_type || this.props.item.small_type == 0 ? [] : this.props.item.small_type,
                title,
                imgLocation: this.props.item.location ? this.props.item.location.join('/') : '',
                keyword: this.props.item.keyword ? this.props.item.keyword.slice() : [],
                upload_source: this.props.item.upload_source,
                purchase_source: this.props.item.purchase_source,
                original_author: this.props.item.original_author,
                is_signature: this.props.item.is_signature,
                upload_source_type: this.props.item.upload_source.length == 0 ? 'personal' : 'department',
            }
        })
    }

    getFilter = key => {
        let { filterObj } = this.props;
        let arr = [];
        if (!filterObj[key]) {
            return null
        } else if (key === 'purpose') {
            for (let target in filterObj[key]) {
                arr.push(<Radio key={ target } value={ target }>{ filterObj[key][target] }</Radio>)
            }
            return arr;
        } else if (key === 'small_type') {
            for (let target in filterObj[key]) {
                arr.push(<Option key={ target } value={ target }>{ filterObj[key][target] }</Option>)
            }
            return arr;
        }
        return null
    }

    getValue = (type, key) => {
        return this.props.filterObj[type] ? this.props.filterObj[type][key] : '';
    }

    onCheck = (e) => {
        this.props.changeCheckStatus(this.props.index)
    }

    showLog = () => {
        this.props.getTimelineData({ eid: this.props.item.eid });
        this.setState({
            logVisible: true
        })
    }
    onCancel = () => {
        this.setState({
            logVisible: false
        })
    }

    isUpdated = (arr) => {
        for (var item of arr) {
            if (this.props.item.new_update_key && this.props.item.new_update_key.indexOf(item) !== -1) {
                return 'new-update';
            }
        }
        return '';
    }

    passImgTab = () => {
        return this.props.type == 2;
    }

    dealOperateDesc = (desc) => {
        if (desc.indexOf('驳回素材 理由：') !== -1) {
            let strIndex = desc.indexOf('驳回素材 理由：') + '驳回素材 理由：'.length;
            return desc.substr(0, strIndex) + '<span class="reject-reason">' + desc.substr(strIndex) + '</span>';
        }
        return desc;
    }

    render() {
        let { params, logVisible } = this.state;
        let { title, purpose, small_type, keyword, imgLocation, upload_source,
           purchase_source, original_author, is_signature, star } = params;
        let { timelineLog, item, imgDataCheckList, index, type, userInfo } = this.props;
        const { filesize = '' } = item;
        const { GPSLatitude_new: lat, GPSLongitude_new: lng, GPSLatitudeRef, GPSLongitudeRef } = item.extend.extif && item.extend.extif.GPS || {};
        const { img_ext = '', img_width = '', img_height = '', Make = '', Model = '', ColorSpace = '', FocalLength = '', FNumber = '', ExposureMode = '', ExposureProgram = '', ExposureTime = '' } = item.extend.detail || {};
        let { operate_info, keyword_add, uploader_realname } = item;
        let realname = operate_info.realname;
        let upload_time = item.upload_time;

        const getDirection = (x) => {
            if (!x) {
                return ''
            }
            let res = '';
            switch (x) {
                case 'N':
                    res = '北'
                    break;
                case 'E':
                    res = '东'
                    break;
                case 'W':
                    res = '西'
                    break;
                case 'S':
                    res = '南'
                    break;
                default:
                    res = ''
            }
            return res
        }
        const data = [
            {
                key: '体积',
                value: `${filesize}`
            }, {
                key: '规格',
                value: img_width && img_height ? `${img_width}X${img_height}` : ''
            }, {
                key: '格式',
                value: `${img_ext}`
            }, {
                key: '设备制造商',
                value: `${Make}`
            }, {
                key: '设备型号',
                value: `${Model}`
            }, {
                key: '颜色空间',
                value: `${ColorSpace}`
            },
            // {
            //     key: '颜色描述文件',
            //     value: `${img_width}X${img_height}`
            // },
            {
                key: '焦距',
                value: `${FocalLength}`
            },
            // {
            //     key: 'Alpha通道',
            //     value: `${img_width}X${img_height}`
            // },
            // {
            //     key: '红眼',
            //     value: `${img_width}X${img_height}`
            // },
            {
                key: '光圈',
                value: `${FNumber}`
            }, {
                key: '测光模式',
                value: `${ExposureMode}`
            }, {
                key: '曝光程序',
                value: `${ExposureProgram}`
            }, {
                key: '曝光时间',
                value: `${ExposureTime}`
            }, {
                key: '经度',
                value: `${lng} ${getDirection(GPSLongitudeRef)}`
            }, {
                key: '纬度',
                value: `${lat} ${getDirection(GPSLatitudeRef)}`
            }
        ].filter(item => item.value.length && item.value !== 'null' && item.value.trim() !== 'undefined');

        const content = (
            <div className={ 'img-audit-page-table' }>
                <Table dataSource={ data } showHeader={ false } pagination={ false } size={ 'small' }
                       rowClassName={ (record, index) => {
                           return index % 2 === 1 ? 'bg-gray img-audit-page-table--font' : 'bg-white img-audit-page-table--font'
                       } } className='img-audit-page-table'>
                    <Column
                        className={ 'text-center' }
                        width="100px"
                        title=""
                        dataIndex="key"
                        key="key"
                    />
                    <Column
                        title=""
                        width="440px"
                        dataIndex="value"
                        key="value"
                    />
                </Table>
            </div>
        );

        return (
            <div className="img-bulk-card-panel">
                <div className={ `img-bulk-card ${imgDataCheckList[index].check ? 'checked' : ''}` }>
                    <div className="img-bulk-checkbox">
                        {
                            type != 4 &&
                            <i className={`icon-font-checkbox big ${ imgDataCheckList[index].check ? 'checked' : 'none-checked' }`}
                                onClick={this.onCheck}>
                                &#xe337;
                            </i>
                        }
                    </div>
                    <div className="img-bulk-container">
                        {
                            item.star
                            &&
                            <div className="star-hint">精选推荐</div>
                        }
                        <img className="upload-bulk-img" src={ item.small_img }></img>
                        {
                            <Popover placement="right" content={ content } title="" trigger="hover"
                                     style={ { padding: 0 } }>
                                <div className={ 'exif-info' }>
                                    <i className="icon-font-ourimg big-icon">&#xe10c;</i>
                                    <span style={ { marginLeft: '3px' } }>EXIF信息</span>
                                </div>
                            </Popover>
                        }
                        {
                            item.download > 0 &&
                            <span className="stat-item">
                                <i className="icon-font-ourimg big-icon">&#xf0aa;</i>
                                <span className="type">下载</span>
                                <span className="num">{ item.download }</span>
                            </span>
                        }
                        <div className="hover-btn">
                            <Button className="img-btn search-btn" size="small"
                                    onClick={ () => this.props.onShow({ visible: true, imgUrl: item.big_img }) }>
                                <i className="icon-font-ourimg">&#xf407;</i>
                            </Button>
                        </div>
                    </div>
                    <div className="img-bulk-info-panel">
                        <div className="img-bulk-title">
                            { title }
                        </div>
                        <div className="img-bulk-items">
                            <div className="img-bulk-item">
                                <Row>
                                    <Col span={6} className="label">图片编号</Col><Col span={18} className="info">
                                        { item.eid }
                                    </Col>
                                </Row>
                                <Row>
                                    <Col span={6} className="label">图片分类</Col><Col span={18} className="info">
                                        {
                                          (!small_type || small_type.length === 0) ? '无' :
                                          small_type.map(key =>this.getValue('small_type', key)).join(',')
                                        }
                                    </Col>
                                </Row>
                                <Row>
                                    <Col span={6} className="label">拍摄地点</Col><Col span={18} className="info">
                                        { !imgLocation ? '未填写' : imgLocation }
                                    </Col>
                                </Row>
                                <Row className="img-bulk-keyword">
                                    <Col span={6} className="label">关键词语</Col><Col span={18} className="info">
                                        {
                                            <div className="tag-container">
                                                {
                                                    !keyword || keyword.length === 0 ? '空' : keyword.map(item => {
                                                        return <Tag key={item + this.props.index}>{item}</Tag>
                                                    })
                                                }
                                            </div>
                                        }
                                    </Col>
                                </Row>
                                <Row>
                                    <Col span={6} className="label">图片用途</Col><Col span={18} className="info">
                                        { purposeMap[purpose] }
                                    </Col>
                                </Row>
                            </div>
                            <div className="img-bulk-item">
                                <Row>
                                    <Col span={8} className="label">采购来源</Col>
                                    <Col span={16} className="info">
                                        {
                                            (purchase_source === '' || purchase_source === null || purchase_source === undefined) ? '无' : purchase_source
                                        }
                                    </Col>
                                </Row>
                                <Row>
                                    <Col span={8} className="label">原始作者</Col>
                                    <Col span={16} className="info">
                                        {
                                            (original_author === '' || original_author === null || original_author === undefined)
                                                ? '无'
                                                : original_author + ( is_signature ? "（需署名）" : "" )
                                        }
                                    </Col>
                                </Row>
                                <Row>
                                    <Col span={8} className="label">上传来源</Col>
                                    <Col span={16} className="info">
                                        {
                                            (upload_source === '' || upload_source === null || upload_source === undefined)
                                                ? '个人'
                                                : upload_source
                                        }
                                    </Col>
                                </Row>
                                <Row>
                                    <Col span={8} className="label">上传作者</Col>
                                    <Col span={16} className="info author-txt">
                                        { uploader_realname }
                                    </Col>
                                </Row>
                                <Row>
                                    <Col span={8} className="label">上传时间</Col>
                                    <Col span={16} className="info">
                                        { upload_time }
                                    </Col>
                                </Row>
                            </div>
                        </div>
                        <div className="reject-box">
                            {
                                (type === '3' || type === '5')
                                &&
                                <Row className="img-audit-nopass">
                                    <Col span={3} className="label">驳回原因</Col>
                                    <Col span={20} className="info"
                                        dangerouslySetInnerHTML={{__html: (item.reject_reason ? item.reject_reason.join('，') : '') + '。<span class="operator">-- @' + (type === '5' ? '系统驳回' : item.audit_username) + '</span>'}}>
                                    </Col>
                                </Row>
                            }
                        </div>
                        <div className="upload-bulk-itembtn-container">
                            {
                                this.passImgTab()
                                &&
                                (
                                    item.star
                                    ? <Button className="del-btn" onClick={ this.props.onUnStar }>撤销推荐</Button>
                                    : <Button className="star-btn" onClick={ this.props.onStar }>精选推荐</Button>
                                )
                            }
                            {
                                (type == 1 || ((type == 3 || type == 5) && item.system_check_img != 2)) &&
                                <Button type="primary" onClick={ this.props.onPass }>通过</Button>
                            }
                            {
                                (type == 1 || type == 2) &&
                                <Button className="del-btn" onClick={ this.props.onReject }>驳回</Button>
                            }
                            {
                                <Button className="check-log-btn" onClick={ this.showLog }>查看流程日志</Button>
                            }
                            {
                                userInfo.role.indexOf('super_admin') !== 0 && item.deletable &&
                                <Button className="del-btn" onClick={ this.props.onDel }>删除</Button>
                            }
                        </div>
                    </div>
                    <Modal
                        className="q-modal time-line-modal"
                        visible={ logVisible }
                        footer={ null }
                        title="流程日志"
                        onCancel={ this.onCancel }
                        width={ 510 }
                    >
                        <div className="log-content">
                            <Timeline>
                                {
                                    timelineLog.map((item, pIndex) =>
                                        <Timeline.Item key={ pIndex } className={ `${timelineLog.length == (pIndex + 1) ? "timeline-end" : ""}` }>
                                            <div className={ `log-info ${timelineLog.length == (pIndex + 1) ? "" : "no-end"}` }>{ item.title }</div>
                                            {
                                                item.operate_info.map((info, index) =>
                                                    <div className={ `log-info ${timelineLog.length == (pIndex + 1) ? "" : "no-end"}` } key={ index }>
                                                        <span className="log-time">{ info.time }</span>
                                                        <div className="log-detail" dangerouslySetInnerHTML={{__html: this.dealOperateDesc(info.desc)}}></div>
                                                        <div className="log-detail">{ info.detail }</div>
                                                    </div>)
                                            }
                                        </Timeline.Item>)
                                }
                                {
                                    type == 1 && <Timeline.Item key="waiting" className="unfinished">
                                        <div className="log-title">待审核</div>
                                    </Timeline.Item>
                                }
                            </Timeline>
                        </div>
                    </Modal>
                </div>
                <div className="img-bulk-card-interval"></div>
            </div>
        )
    }
}
