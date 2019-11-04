import React, { Component } from "react";
import { observer, inject } from "mobx-react";
import { Route, withRouter, Link } from "react-router-dom";
import { Button, Radio, Input, DatePicker, message } from 'antd';
import QNumberInput from 'COMPONENT/qNumberInput';
import QUploadImgs from 'COMPONENT/qUploadImgs';
import moment from 'moment';
const { TextArea } = Input;
const RadioGroup = Radio.Group;
const { RangePicker } = DatePicker;
@inject(state => ({
    history: state.history,
    router: state.router,
    pathname: state.history.location.pathname,
    formData: state.store.storeManage.formData,
    editItemEid: state.store.storeManage.editItemEid,
    onChangeFormData: state.store.storeManage.onChangeFormData,
    addNewGoods: state.store.storeManage.addNewGoods,
    onChangeTab: state.store.storeManage.onChangeTab,
    getGoodsdetail: state.store.storeManage.getGoodsdetail
}))
@withRouter
@observer
class EditGoods extends Component {

    componentDidMount() {
        if (this.props.editItemEid) {
            this.props.getGoodsdetail({eid: this.props.editItemEid, action: 'edit'});
        }
    }

    numberFilterChange = (type, e) => {
        let { value } = e.target;
        const reg = type === 'points' ? /^(0|[1-9][0-9]*)?$/ : /^(0|[1-9][0-9]*)(\.[0-9]*)?$/;
        if ((!Number.isNaN(value) && reg.test(value)) || value === '') {
            this.props.onChangeFormData(type, value);
        }
    }

    handleLimitChange = (value) => {
        if (value) {
            this.props.onChangeFormData('exchange_begin_time', moment().format('YYYY-MM-DD'));
            this.props.onChangeFormData('exchange_end_time', moment().format('YYYY-MM-DD'));
        } else {
            this.props.onChangeFormData('exchange_begin_time', '');
            this.props.onChangeFormData('exchange_end_time', '');
        }
        this.props.onChangeFormData('hasLimitTime', value);
    }

    handleDateChange = (dates, dateStrings) => {
        this.props.onChangeFormData('exchange_begin_time', dateStrings[0]);
        this.props.onChangeFormData('exchange_end_time', dateStrings[1]);
    }

    saveData = (publish_status) => {
        if (!this.checkDataValidate()) {
            return;
        }
        const { title, description, exchange_begin_time, exchange_end_time,
            exchange_description, price, points, stock, detail, detail_title,
            img_url, detail_img } = this.props.formData;
        let params = {
            title, description, exchange_begin_time, exchange_end_time,
            exchange_description, price, points, stock, detail, detail_title,
            img_url, detail_img
        };
        if (!this.props.editItemEid) {
            params.publish_status = publish_status;
        }
        this.props.addNewGoods(params, () => {
            this.props.onChangeTab('MANAGE');
        });
    }

    checkDataValidate = () => {
        const { title, price, points } = this.props.formData;
        if (!title) {
            message.warning('请填写商品标题');
            return false;
        } else if (!price) {
            message.warning('请设置参考价格');
            return false;
        } else if (!points) {
            message.warning('请设置兑换积分');
            return false;
        }
        return true;
    }

    render() {
        const { formData, onChangeFormData, editItemEid } = this.props;
        const { title, description, exchange_begin_time, exchange_end_time,
            exchange_description, price, points, stock, remain_stock, detail, detail_title, 
            hasLimitTime, img_url, small_img_url, detail_img } = formData;
        return (
            <div className="edit-goods-panel">
                <div className="form-row">
                    <div className="label">标题</div>
                    <div className="input">
                        <Input 
                            className="input-m"
                            value={title} 
                            maxLength={30}
                            placeholder="在这里输入标题"
                            onChange={(e)=>onChangeFormData('title', e.target.value)}/>
                    </div>
                </div>
                <div className="form-row">
                    <div className="label">副标题描述</div>
                    <div className="input">
                        <Input 
                            className="input-m"
                            maxLength={20}
                            value={description} 
                            placeholder="在这里输入商品特色描述"
                            onChange={(e)=>onChangeFormData('description', e.target.value)}/>
                    </div>
                </div>
                <div className="form-row">
                    <div className="label">上传图片</div>
                    <div className="tool-btn">
                        <QUploadImgs 
                            type="upload"
                            init={!editItemEid}
                            defaultValue={img_url}
                            defaultShowValue={small_img_url}
                            onChange={(value)=>onChangeFormData('img_url', value)}/>
                    </div>
                </div>
                <div className="form-row">
                    <div className="label">商品库存</div>
                    <div className="input">
                        <QNumberInput 
                            min={1}
                            max={100000}
                            value={stock}
                            onChange={(value)=>onChangeFormData('stock', value)}/>
                        {
                            editItemEid && 
                            <div className="remain-stock">剩余库存：{remain_stock}</div>
                        }
                    </div>
                </div>
                <div className="form-row">
                    <div className="label">兑换积分</div>
                    <div className="input">
                        <Input 
                            className="input-m"
                            value={points} 
                            onChange={(e)=>this.numberFilterChange('points', e)}/>
                    </div>
                </div>
                <div className="form-row">
                    <div className="label">参考价格</div>
                    <div className="input">
                        <Input 
                            className="input-m"
                            value={price} 
                            onChange={(e)=>this.numberFilterChange('price', e)}/>
                    </div>
                </div>
                <div className="form-row">
                    <div className="label">有效期</div>
                    <div className="input">
                        <RadioGroup 
                            className="time-limit" 
                            value={hasLimitTime}
                            onChange={(e)=>this.handleLimitChange(e.target.value)}>
                            <Radio value={false}>无限制</Radio>
                            <Radio value={true}>有限制</Radio>
                        </RadioGroup>
                        {
                            hasLimitTime && 
                            <RangePicker 
                                allowClear={false}
                                value={[moment(exchange_begin_time), moment(exchange_end_time)]}
                                onChange={this.handleDateChange}/>
                        }
                    </div>
                </div>
                <div className="form-row">
                    <div className="label">
                        <div>商品介绍</div>
                        <div>（标题）</div>
                    </div>
                    <div className="input">
                        <Input 
                            maxLength={30}
                            className="input-m"
                            value={detail_title}
                            onChange={(e)=>onChangeFormData('detail_title', e.target.value)}/>
                    </div>
                </div>
                <div className="form-row">
                    <div className="label">
                        <div>商品介绍</div>
                        <div>（内容）</div>
                    </div>
                    <div className="input">
                        <TextArea 
                            rows={4} 
                            maxLength={500}
                            value={detail}
                            onChange={(e)=>onChangeFormData('detail', e.target.value)}/>
                    </div>
                </div>
                <div className="form-row">
                    <div className="label">详情图片</div>
                    <div className="input">
                        <QUploadImgs 
                            type="detail"
                            sortable
                            init={!editItemEid}
                            defaultValue={detail_img}
                            onChange={(value)=>onChangeFormData('detail_img', value)}/>
                        <div className="hint-text">
                            <i className="icon-font-ourimg">&#xe159;</i>
                            左右拖动调整图片排序
                        </div>
                    </div>
                </div>
                <div className="form-row">
                    <div className="label">兑换说明</div>
                    <div className="input">
                        <TextArea 
                            rows={4}
                            maxLength={500}
                            value={exchange_description}
                            onChange={(e)=>onChangeFormData('exchange_description', e.target.value)}/>
                    </div>
                </div>
                <div className="form-row">
                    <div className="label"></div>
                    <div className="tool-btn">
                    {   
                        editItemEid ? 
                        <Button 
                            className="save-btn" type="primary"
                            onClick={this.saveData}>
                            保存
                        </Button> : 
                        [<Button 
                            key="publish"
                            className="save-btn" type="primary"
                            onClick={()=>this.saveData(1)}>
                            保存并发布
                        </Button>,
                        <Button 
                            key="draft"
                            className="save-btn" type="primary"
                            onClick={()=>this.saveData(0)}>
                            保存至草稿箱
                        </Button>]
                    }
                        
                    </div>
                </div>
            </div>
        );
    }
}

export default EditGoods;
