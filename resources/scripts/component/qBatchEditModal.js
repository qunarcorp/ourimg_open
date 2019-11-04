import React, { Component } from 'react'
import { Button, Modal, Radio, Select, Row, Col, Input, message, Tag, AutoComplete, Checkbox } from 'antd';
import { observer, inject } from 'mobx-react';
import { withRouter } from 'react-router-dom';
import QCRSelect from 'COMPONENT/qCRSelect';
import QCopy from 'COMPONENT/qCopy';
const RadioGroup = Radio.Group;
const Option = Select.Option;
let cityLock;
@inject(state => ({
    history: state.history,
    router: state.router,
    hash: state.router.location.hash,
    userInfo: state.store.global.userInfo,
    filterObj: state.store.google.filterObj,
    getCitySuggest: state.store.component.getCitySuggest,
    cityList: state.store.component.cityList,
    getBaseDept: state.store.myUpload.getBaseDept,
    baseDept: state.store.myUpload.baseDept,
}))
@withRouter
@observer
class QBatchEditModal extends Component {

    state = {
        colorParams: {
            title: false,
            city: false,
            titleLength: false
        },
        params: {
            title: '',
            purpose: '2',
            small_type: [],
            city: '2323',
            imgLocation: '',
            city_id: '',
            keyword: [],
            upload_source_type: 'personal',
            upload_source: '',
            purchase_source: '',
            original_author: '',
            is_signature: false,
        }
    }

    handleOk = () => {
        let result = this.checkDataValidate();
        if (! result) {
            return false;
        }
        let { validate, params } = result;
        this.props.onOk(validate, params, () => this.clearData());
    }
    handleCancel = () => {
        this.props.onCancel();
        this.clearData();
    }
    clearData = () => {
        this.setState({
            colorParams: {
                title: false,
                city: false,
                titleLength: false
            },
            params: {
                title: '',
                purpose: '2',
                small_type: [],
                city: '',
                imgLocation: '',
                city_id: '',
                keyword: [],
                upload_source_type: 'personal',
                upload_source: '',
                purchase_source: '',
                original_author: '',
                is_signature: false,
            }
        });
    }

    checkDataValidate = () => {
        let tmpParams = this.state.params;
        let params = {};
        let errorMessage = [];
        if (tmpParams.title.length == 0) {
            errorMessage.push('标题必填');
        }
        if (tmpParams.title.length > 15) {
            message.warning('标题不可超过15个字符');
            return false;
        }
        if (tmpParams.small_type.length == 0) {
            errorMessage.push('图片分类必填');
        }
        if (tmpParams.city.length == 0) {
            errorMessage.push('拍摄地点必填');
        }
        if (tmpParams.keyword.length == 0) {
            errorMessage.push('关键词语必填');
        }
        if (tmpParams.upload_source_type != 'personal') {
            if (tmpParams.bind_upload_source.length == 0) {
                errorMessage.push('上传来源必填');
            }else{
                tmpParams.upload_source = tmpParams.bind_upload_source;
            }
        }else{
            tmpParams.upload_source = '';
        }
        for (var key in tmpParams){
            if (key !== 'keyword' && key !== 'small_type' && tmpParams[key] !== ''
                && tmpParams[key] !== undefined && tmpParams[key] !== null) {
                params[key] = tmpParams[key];
            } else if ((key === 'keyword' || key === 'small_type') && tmpParams[key].length > 0) {
                params[key] = tmpParams[key];
            }
        }
        params['city_id'] = tmpParams['city_id'];
        return {
            validate: ! errorMessage.length,
            params: params,
        };
    }
    onParamsChange = (key, e) => {
        let obj = {};
        let tmpParams = this.state.params;
        if (key === 'title') {
            if (e.target.value.length > 15) {
                obj.colorParams = {
                    ...this.state.colorParams,
                    titleLength: true
                }
            } else {
                obj.colorParams = {
                    ...this.state.colorParams,
                    titleLength: false
                }
            }
        } else if (key === 'keyword') {
            let arr = [];
            e.map(item => {
                if (item.length <= 20) {
                    arr.push(item)
                }
            })
            if (arr.length < e.length) {
                message.warning('关键词超过20字符');
                e = arr;
            }
        } else if (key === 'is_signature') {
            e = e.target.checked
        } else if (key === 'upload_source_type') {
            e = e.target.value
        }
        obj.params = {
            ...tmpParams,
            [key]: e.target ? e.target.value : e
        }
        this.setState({
            ...obj
        })
    }

    colorChange = (key) => {
        this.setState({
            colorParams: {
                ... this.state.colorParams,
                [key]: !this.state.colorParams[key]
            }
        })
    }

    titleChange = () => {
        if (this.state.params.title && this.state.params.title.length > 15) {
            message.warning('标题不可超过15个字符');
        }
        this.colorChange('title')
    }
    handleSearch = (value) => {
        if (cityLock) {
            clearTimeout(cityLock)
        }

        this.setState({
            params: {
                ...this.state.params,
                imgLocation: value
            }
        }, () => {
            cityLock = setTimeout(() => {
                this.setState({
                    params: {
                        ...this.state.params,
                        city: value.trim(),
                        city_id: '',
                        imgLocation: value
                    }
                })
                this.props.getCitySuggest(value.trim())
            }, 500)
        })
    }
    onPlaceChange = (value, e) => {
        value = {
            key: value,
            label: e.props.children
        }
        this.setState({
            params: {
                ...this.state.params,
                city: value.label[0],
                imgLocation: this.getWholeLocation(value.key),
                city_id: value.key

            }
        })
    }
    getWholeLocation = (id) => {
        var obj = this.props.cityList.filter(item => item.id == id)[0];
        return (obj.country ? obj.country + '/' : '') +
        (obj.province ? obj.province + '/' : '') +
        (obj.city ? obj.city + '/' : '') + obj.name;
    }
    getFilter = key => {
        let { filterObj } = this.props;
        let arr = [];
        if (!filterObj[key]) {
            return null
        } else if (key === 'purpose') {
            for (let target in filterObj[key]) {
                arr.push(<Radio key={target} value={target}>{filterObj[key][target]}</Radio>)
            }
            return arr;
        } else if (key === 'small_type') {
            for (let target in filterObj[key]) {
                arr.push(<Option key={target} value={target}>{filterObj[key][target]}</Option>)
            }
            return arr;
        }
        return null
    }

    purchaseSourceChange = () => {
        if (this.state.params.purchase_source && this.state.params.purchase_source.length > 15) {
            message.warning('采购来源不可超过15个字符');
        }
        this.colorChange('purchase_source')
    }

    isAdmin() {
       let userRole = new Set(this.props.userInfo.role.slice());
       let adminRole = new Set(["super_admin", "admin"]);
       // 交集
       let intersectionSet = new Set([...userRole].filter(x => adminRole.has(x)));
       return intersectionSet.size;
    }

    render() {
        let { params, colorParams } = this.state;
        let { title, purpose, small_type, city_id, keyword, city, upload_source_type, upload_source, purchase_source, original_author, is_signature, imgLocation } = params;
        let { baseDept, visible, cityList } = this.props;
        // const options = cityList.map(d => <Option key={d.id}>{d.name}{d.city ? `(${d.city})` : ''}</Option>);
        const children = cityList.map(d => <AutoComplete.Option key={d.id}>{d.name}{d.city ? `(${d.city})` : ''}</AutoComplete.Option>);
        return (
            <Modal
                className="q-modal q-batch-modal"
                title="批量编辑"
                visible={visible}
                footer={null}
                onCancel={this.handleCancel}
                >
                <div className="edit-confirm-content">
                    <Row gutter={16}>
                        <Col span={5} className="label">图片标题</Col>
                        <Col span={19} className="info">
                            <Input
                                placeholder="请在这里输入标题"
                                value={title}
                                style={{
                                    background: colorParams.title ? '#ffffff' : '#f8f8f8',
                                    color: colorParams.titleLength ? '#f5222d' : 'rgba(0, 0, 0, 0.65)'
                                }}
                                onFocus={this.colorChange.bind(this, 'title')}
                                onBlur={this.titleChange}
                                onChange={this.onParamsChange.bind(this, 'title')}></Input>
                        </Col>
                    </Row>
                    <Row gutter={16}>
                        <Col span={5} className="label">图片分类</Col>
                        <Col span={19} className="info">
                            <Select
                                mode="multiple"
                                className="upload-edit-bar-select"
                                placeholder="请选择分类" value={small_type}
                                onChange={this.onParamsChange.bind(this, 'small_type')}>
                                {
                                    this.getFilter('small_type')
                                }
                            </Select>
                        </Col>
                    </Row>
                    <Row gutter={16}>
                        <Col span={5} className="label">图片用途</Col>
                        <Col span={19} className="info">
                            <QCRSelect
                                editFlag={true} purpose={purpose}
                                onClick={this.onParamsChange}
                            />
                        </Col>
                    </Row>
                    <Row gutter={16}>
                        <Col span={5} className="label">拍摄地点</Col>
                        <Col span={19} className="info">
                            {/* <Select
                                showSearch
                                className="upload-edit-bar-select"
                                labelInValue
                                value={city_id ? {key: city_id ? city_id : '', label: city ? city : ''} : undefined}
                                placeholder="输入地点关键词可选择suggest项"
                                style={this.props.style}
                                defaultActiveFirstOption={false}
                                showArrow={false}
                                filterOption={false}
                                onSearch={this.handleSearch}
                                // onChange={this.onParamsChange.bind(this, 'city_id')}
                                onChange={this.onPlaceChange}
                                notFoundContent={null}
                            >
                                {options}
                            </Select> */}
                            <AutoComplete
                                labelInValue={false}
                                placeholder="输入地点关键词可选择suggest项"
                                onSearch={this.handleSearch}
                                onSelect={this.onPlaceChange}
                                defaultActiveFirstOption={false}
                                filterOption={false}
                                className="img-location-input"
                                value={ imgLocation }
                            >{children}</AutoComplete>
                        </Col>
                    </Row>
                    <Row gutter={16} className="img-bulk-keyword">
                        <Col span={5} className="label">关键词语</Col>
                        <Col span={19} className="info">
                            <Select
                                mode="tags"
                                className="upload-edit-bar-textarea"
                                onChange={this.onParamsChange.bind(this, 'keyword')}
                                tokenSeparators={[',', '，']}
                                dropdownStyle={{display: 'none'}}
                                placeholder="可输入图片元素相关的文字，按逗号或回车键生成标签"
                                value={keyword}
                                >
                            </Select>
                            <QCopy keyword={keyword}/>
                        </Col>
                    </Row>
                    {
                        this.isAdmin()
                        &&
                        <Row gutter={16} className="img-bulk-upload-source">
                            <Col span={5} className="label">上传来源</Col>
                            <Col span={19} className="info">
                                <div className="check-box">
                                    <Checkbox value="personal" onChange={ this.onParamsChange.bind(this, 'upload_source_type') } checked={ upload_source_type == "personal" }>个人</Checkbox>
                                    <Checkbox value="department" onChange={ this.onParamsChange.bind(this, 'upload_source_type') } checked={ upload_source_type == "department" }>部门自采</Checkbox>
                                </div>
                                <div className="select-box">
                                    <Select
                                        style={{ width: 200 }}
                                        defaultValue={ upload_source.length > 0 ? upload_source : "请选择上传来源" }
                                        onChange={ this.onParamsChange.bind(this, 'bind_upload_source') }
                                        disabled={ upload_source_type == 'personal' }
                                        >
                                        {
                                            baseDept.map((item, index) => (<Option value={ item } key={index}>{ item }</Option>))
                                        }
                                    </Select>
                                </div>
                            </Col>
                        </Row>
                    }
                    {
                        this.isAdmin()
                        &&
                        <Row gutter={16}>
                            <Col span={5} className="label">采购来源</Col>
                            <Col span={19} className="info">
                                <Input
                                    placeholder="请输入采购来源"
                                    value={purchase_source}
                                    style={{
                                        background: colorParams.title ? '#ffffff' : '#f8f8f8',
                                        color: colorParams.titleLength ? '#f5222d' : 'rgba(0, 0, 0, 0.65)'
                                    }}
                                    onFocus={this.colorChange.bind(this, 'purchase_source')}
                                    onBlur={this.purchaseSourceChange}
                                    onChange={this.onParamsChange.bind(this, 'purchase_source')}>
                                </Input>
                            </Col>
                        </Row>
                    }
                    {
                        this.isAdmin()
                        &&
                        <Row gutter={16}>
                            <Col span={5} className="label">原始作者</Col>
                            <Col span={19} className="info">
                            <div className="original-author-content">
                                <Input
                                    style={{
                                        background: colorParams.title ? '#ffffff' : '#f8f8f8',
                                        color: colorParams.titleLength ? '#f5222d' : 'rgba(0, 0, 0, 0.65)'
                                    }}
                                    onChange={this.onParamsChange.bind(this, 'original_author')}
                                    placeholder="请输入原始作者"
                                    value={original_author}
                                    >
                                </Input>
                                <span className="signature-row">
                                    <Checkbox
                                        onChange={ this.onParamsChange.bind(this, 'is_signature') }
                                        checked={ is_signature }>
                                        需署名
                                    </Checkbox>
                                </span>
                             </div>
                            </Col>
                        </Row>
                    }
                </div>
                <div className="edit-confirm-btngroup">
                    <Button type="primary" className="submit-btn" onClick={this.handleOk}>保存</Button>
                </div>
            </Modal>
        )
    }
}

export default QBatchEditModal;
