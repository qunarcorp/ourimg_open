import React, { Component, Fragment } from "react";
import { observer, inject } from "mobx-react";
import { Route, withRouter, Link } from "react-router-dom";
import moment from "moment";
import {
    Form,
    Button,
    Radio,
    Input,
    DatePicker,
    message,
    Checkbox,
    Upload,
    Row,
    Col,
    Icon,
    Tag,
    Select
} from "antd";
import { creditsManageApi, adminApi } from "CONST/api";
import QNumberInput from "COMPONENT/qNumberInput";
import FmtRangePicker from "./fmtRangePicker";

const { createActivity, editActivity } = adminApi;

const { RangePicker } = DatePicker;
const controls = [
    "bold",
    "italic",
    "underline",
    "text-color",
    "separator",
    "link",
    "separator",
    "media"
];
const { TextArea } = Input;
let cityLock;

@inject(state => {
    const activeManage = state.store.activeManage;
    const component = state.store.component;
    return {
        history: state.history,
        router: state.router,
        pathname: state.history.location.pathname,
        onChangeTab: activeManage.onChangeTab,
        activityList: activeManage.activityList,
        editIndex: activeManage.editIndex,
        test: activeManage.test,
        getCitySuggest: component.getCitySuggest,
        cityList: component.cityList,
        editData: activeManage.editData,
        changeFormData: activeManage.changeFormData,
        changeFormDataArr: activeManage.changeFormDataArr,
        deleteFormDataArr: activeManage.deleteFormDataArr,
        changeFormDataCitySight: activeManage.changeFormDataCitySight,
        deleteCitySights: activeManage.deleteCitySights
    };
})
@withRouter
@observer
class EditActive extends Component {
    state = {
        // 0 是长期 提交之前要处理
        activeTimeType: 0,
        uploadLoading: false
    };

    componentDidMount() {
        // const { time = '' } = this.props;
        const { begin_time, end_time } = this.props;
        if (begin_time || end_time) {
            this.setState({
                activeTimeType: 1
            });
        }
    }

    componentWillUnmount() {
        this.props.onChangeTab("MANAGE");
    }

    checkDataValidate = err => {
        const { activeTimeType } = this.state;
        const { begin_time, end_time, background_img, time } = this.props;
        if (err) {
            return false;
        }
        if (!background_img) {
            message.warning("请添加背景图片");
            return false;
        }
        if (activeTimeType === 1 && !begin_time && !end_time && !time) {
            message.warning("请选择有效时间范围");
            return false;
        }
        return true;
    };

    handleSubmit = state => {
        this.props.form.validateFields((err, values) => {
            if (!this.checkDataValidate(err)) {
                return;
            }
            const { activeTimeType } = this.state;
            const { editData } = this.props;
            const { time } = values;
            const begin_time = activeTimeType === 0 ? "" : time[0];
            const end_time = activeTimeType === 0 ? "" : time[1];
            const params = {
                ...editData,
                begin_time,
                end_time,
                state
            };
            this.submitDataToServer(params);
        });
    };

    async submitDataToServer(params) {
        const url = this.props.editIndex === -1 ? createActivity : editActivity;
        const res = await $.post(url, params);
        if (res.status === 0) {
            this.props.onChangeTab("MANAGE");
        }
    }

    onChangeActiveTimeType(e) {
        this.setState({
            activeTimeType: e.target.value
        });
    }

    handlePicChange = info => {
        if (info.file.status === "uploading") {
            this.setState({ uploadLoading: true });
            return;
        }
        if (info.file.status === "done") {
            const {
                file: { response = {} }
            } = info;
            const { status, data } = response;
            if (status !== 0) {
                message.warning(file.response.message);
                this.setState({
                    uploadLoading: false
                });
                return;
            } else {
                const { img_url } = data;
                this.setState({
                    uploadLoading: false
                });
                this.props.changeFormData("background_img", img_url);
            }
            // getBase64(info.file.originFileObj, imageUrl => {
            //     console.log('imageUrl', imageUrl);
            //     this.setState({
            //         uploadLoading: false
            //     });
            //     this.props.changeFormData("background_img", imageUrl);
            // });
        }
    };

    getWholeLocation = id => {
        var obj = this.props.cityList.filter(item => item.id == id)[0];
        return obj
            ? (obj.country ? obj.country + "/" : "") +
                  (obj.province ? obj.province + "/" : "") +
                  (obj.city ? obj.city + "/" : "") +
                  obj.name
            : "";
    };

    handleSearch = value => {
        if (cityLock) {
            clearTimeout(cityLock);
        }
        cityLock = setTimeout(() => {
            this.props.getCitySuggest(value.trim());
        }, 500);
    };

    render() {
        const {
            background_img,
            cityList,
            onChangeTab,
            theme_keywords,
            activity_type,
            task_points,
            changeFormDataArr,
            deleteFormDataArr,
            changeFormDataCitySight,
            city_sights,
            deleteCitySights
        } = this.props;
        const { activeTimeType } = this.state;
        const { getFieldDecorator } = this.props.form;
        const formItemLayout = {
            labelCol: {
                xs: { span: 24 },
                sm: { span: 3 }
            },
            wrapperCol: {
                xs: { span: 24 },
                sm: { span: 21 }
            }
        };
        // const activity_type = getFieldValue("activity_type");
        // const task_points = getFieldValue("task_points");
        const uploadButton = (
            <div>
                <Icon type={this.state.uploadLoading ? "loading" : "plus"} />
            </div>
        );
        const options = cityList.map(d => (
            <Option key={d.id}>
                {d.name}
                {d.city ? `(${d.city})` : ""}
            </Option>
        ));
        return (
            <Fragment>
                <div className={"edit-active m-t-48"}>
                    <Form
                        layout={"horizontal"}
                        // onSubmit={this.handleSubmit}
                        hideRequiredMark
                    >
                        <Form.Item {...formItemLayout} label="活动标题">
                            {getFieldDecorator("activity_title", {
                                rules: [
                                    {
                                        required: true,
                                        message: "请输入活动标题"
                                    }
                                ]
                            })(
                                <Input
                                    placeholder="请输入活动标题..."
                                    className="input-title"
                                    maxLength={50}
                                    addonAfter="/限50字"
                                />
                            )}
                        </Form.Item>
                        <Form.Item {...formItemLayout} label="活动类型">
                            {getFieldDecorator("activity_type", {
                                rules: [
                                    {
                                        required: true,
                                        message: "请选择活动类型"
                                    }
                                ]
                            })(
                                <Checkbox.Group>
                                    <Checkbox
                                        value={"daily"}
                                        className="checkbox-text"
                                    >
                                        日常任务
                                    </Checkbox>
                                    <Checkbox
                                        value={"city_sight"}
                                        className="checkbox-text"
                                    >
                                        城市景点任务
                                    </Checkbox>
                                    <Checkbox
                                        value={"theme"}
                                        className="checkbox-text"
                                    >
                                        主题任务
                                    </Checkbox>
                                </Checkbox.Group>
                            )}
                        </Form.Item>
                        {activity_type && activity_type.includes("city_sight") && (
                            <Form.Item {...formItemLayout} label="城市景点">
                                <Row>
                                    <Col span={9}>
                                        <div className="city-btn-container">
                                            <Select
                                                ref="select_city"
                                                showSearch
                                                className="select-city"
                                                labelInValue
                                                placeholder="添加城市景点可选择suggest项"
                                                defaultActiveFirstOption={false}
                                                showArrow={false}
                                                filterOption={false}
                                                onSearch={this.handleSearch}
                                                onChange={
                                                    changeFormDataCitySight
                                                }
                                                notFoundContent={null}
                                            >
                                                {options}
                                            </Select>
                                            {/* <Button type="primary" onClick={() => {console.log(this.refs.select_city)}}>添加</Button> */}
                                        </div>

                                        {/* <span>添加</span> */}
                                        {/* <Input.Search
                                            placeholder="添加城市景点"
                                            enterButton="添加"
                                            onSearch={value =>
                                                console.log(value)
                                            }
                                            className="input-search"
                                        /> */}
                                    </Col>
                                </Row>
                                <div className="tag-container--flex">
                                    {Object.keys(city_sights).filter(
                                        item => item
                                    ).length > 0 &&
                                        Object.keys(city_sights)
                                            .filter(item => item)
                                            .map(key => {
                                                return (
                                                    <Tag
                                                        className="tag-item"
                                                        key={key}
                                                        closable
                                                        onClose={() =>
                                                            deleteCitySights(
                                                                key
                                                            )
                                                        }
                                                    >
                                                        {city_sights[key]}
                                                    </Tag>
                                                );
                                            })}
                                </div>
                            </Form.Item>
                        )}
                        {activity_type && activity_type.includes("theme") && (
                            <Form.Item {...formItemLayout} label="关键词语">
                                <Row>
                                    <Col span={9}>
                                        <Input.Search
                                            placeholder="添加主题关键词语"
                                            enterButton="添加"
                                            onSearch={value =>
                                                changeFormDataArr(
                                                    "theme_keywords",
                                                    value
                                                )
                                            }
                                            className="input-search"
                                        />
                                    </Col>
                                </Row>
                                <div className="tag-container--flex">
                                    {theme_keywords.filter(item => item)
                                        .length > 0 &&
                                        theme_keywords
                                            .filter(item => item)
                                            .map(theme => {
                                                return (
                                                    <Tag
                                                        className="tag-item"
                                                        key={theme}
                                                        closable
                                                        onClose={() =>
                                                            deleteFormDataArr(
                                                                "theme_keywords",
                                                                theme
                                                            )
                                                        }
                                                    >
                                                        {theme}
                                                    </Tag>
                                                );
                                            })}
                                </div>
                            </Form.Item>
                        )}
                        <Form.Item {...formItemLayout} label="背景图片">
                            <div className='upload-img-container'>
                                <Upload
                                    listType="picture-card"
                                    className="avatar-uploader"
                                    showUploadList={false}
                                    action={creditsManageApi.uploadImgs}
                                    onChange={this.handlePicChange}
                                >
                                    {background_img ? (
                                        <img
                                            src={background_img}
                                            alt="avatar"
                                            className="img-avator"
                                        />
                                    ) : (
                                        uploadButton
                                    )}
                                </Upload>
                                <span className={"text-gray m-l"}>(请上传活动任务背景图片)</span>
                            </div>
                        </Form.Item>
                        <Form.Item {...formItemLayout} label="上传奖励">
                            {getFieldDecorator("img_upload_points", {
                                rules: []
                            })(<QNumberInput desc={"(每张图的积分奖励)"} min={0}/>)}
                        </Form.Item>
                        <Form.Item {...formItemLayout} label="任务奖励">
                            {getFieldDecorator("task_points", {
                                rules: []
                            })(<QNumberInput desc={"(任务完成总积分奖励)"} min={0}/>)}
                        </Form.Item>
                        {task_points > 0 && (
                            <Form.Item {...formItemLayout} label="图片基数">
                                {getFieldDecorator("need_img_count", {
                                    rules: []
                                })(
                                    <QNumberInput
                                        desc={
                                            "(满足图片基数要求，即可获得积分奖励)"
                                        }
                                        min={0}
                                    />
                                )}
                            </Form.Item>
                        )}
                        {task_points > 0 && (
                            <Form.Item {...formItemLayout} label="奖励周期">
                                {getFieldDecorator("points_cycle", {
                                    rules: []
                                })(
                                    <Radio.Group>
                                        <Radio
                                            value={"once"}
                                            className="checkbox-text"
                                        >
                                            一次性奖励
                                        </Radio>
                                        <Radio
                                            value={"daily"}
                                            className="checkbox-text"
                                        >
                                            每日
                                        </Radio>
                                        <Radio
                                            value={"weekly"}
                                            className="checkbox-text"
                                        >
                                            每周
                                        </Radio>
                                    </Radio.Group>
                                )}
                            </Form.Item>
                        )}
                        <Form.Item {...formItemLayout} label="活动介绍">
                            {getFieldDecorator("activity_introduction", {
                                // rules: [
                                //     {
                                //         required: true,
                                //         message: "请编辑活动介绍"
                                //     }
                                // ]
                            })(
                                <TextArea
                                    placeholder="请编辑活动介绍,限500字..."
                                    rows={5}
                                    suffix="/限50字"
                                    maxLength={500}
                                />
                            )}
                        </Form.Item>
                        <Form.Item {...formItemLayout} label="活动时间">
                            <Row>
                                <Col>
                                    <Radio.Group
                                        onChange={this.onChangeActiveTimeType.bind(
                                            this
                                        )}
                                        value={activeTimeType}
                                    >
                                        <Radio
                                            value={0}
                                            className="checkbox-text"
                                        >
                                            长期
                                        </Radio>
                                        <Radio
                                            value={1}
                                            className="checkbox-text"
                                        >
                                            有效期
                                        </Radio>
                                    </Radio.Group>
                                </Col>
                            </Row>
                            {activeTimeType === 1 &&
                                getFieldDecorator("time", {
                                    rules: []
                                })(<FmtRangePicker className="date-picker" />)}
                        </Form.Item>
                        <Form.Item {...formItemLayout} label="活动奖励">
                            {getFieldDecorator("activity_reward", {
                                // rules: [
                                //     {
                                //         required: true,
                                //         message: "请编辑活动奖励"
                                //     }
                                // ]
                            })(
                                <TextArea
                                    placeholder="请编辑活动奖励,限500字..."
                                    rows={5}
                                    maxLength={500}
                                />
                            )}
                        </Form.Item>
                        <Form.Item {...formItemLayout} label="作品要求">
                            {getFieldDecorator("img_requirements", {
                                // validateTrigger: 'onBlur',
                                // rules: [
                                //     {
                                //         required: true,
                                //         message: "请编辑作品要求"
                                //         // validator(rule, value, callback) {
                                //         //     value.toHTML() === '<p></p>' ? callback('请编辑作品要求') : callback()
                                //         // }
                                //     }
                                // ]
                            })(
                                // <BraftEditor
                                //     controlBarStyle={ { backgroundColor: '#eeeeee' } }
                                //     contentStyle={ { height: 138, backgroundColor: '#eeeeee' } }
                                //     controls={ controls }
                                //     placeholder="请编辑作品要求..."
                                // />
                                <TextArea
                                    placeholder="请编辑作品要求,限500字..."
                                    rows={5}
                                    maxLength={500}
                                />
                            )}
                        </Form.Item>
                        <Form.Item {...formItemLayout} label="活动说明">
                            {getFieldDecorator("activity_description", {
                                // rules: [
                                //     {
                                //         required: true,
                                //         message: "请编辑活动说明"
                                //     }
                                // ]
                            })(
                                <TextArea
                                    placeholder="请编辑活动说明,限500字..."
                                    rows={5}
                                    maxLength={500}
                                />
                            )}
                        </Form.Item>
                    </Form>
                </div>
                <div className="btn-container">
                    <Button
                        type="primary"
                        onClick={() => this.handleSubmit("online")}
                        className="btn-com"
                    >
                        发布
                    </Button>
                    <Button
                        onClick={() => this.handleSubmit("pending")}
                        className="btn-com btn-grey"
                    >
                        保存
                    </Button>
                    <Button
                        onClick={() => onChangeTab("MANAGE")}
                        className="btn-com btn-grey"
                    >
                        取消
                    </Button>
                </div>
            </Fragment>
        );
    }
}

export default (EditActive = Form.create({
    onFieldsChange(props, changedFields) {
        props.onFormChange(changedFields);
    },
    mapPropsToFields(props) {
        const res = {};
        Object.keys(props).forEach(key => {
            // const value = ARRAY_KEY.includes(key)
            //     ? props[key].filter(item => item)
            //     : props[key];
            res[key] = Form.createFormField({
                value: props[key]
            });
        });
        const { begin_time = "", end_time = "" } = props;
        if (begin_time || end_time) {
            res.time = Form.createFormField({
                value: [moment(begin_time), moment(end_time)]
            });
        }
        return res;
    },
    onValuesChange(_, values) {}
})(EditActive));
