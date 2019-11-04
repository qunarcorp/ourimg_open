import React, { Component } from "react";
import { observer, inject } from "mobx-react";
import { Route, withRouter, Link } from "react-router-dom";
import { Button, Input, Modal, message } from 'antd';
const { TextArea } = Input;
const confirm = Modal.confirm;
@inject(state => ({
    history: state.history,
    router: state.router,
    pathname: state.history.location.pathname,
    saveEditRules: state.store.storeManage.saveEditRules,
    rule: state.store.storeManage.rule,
    instruction: state.store.storeManage.instruction,
    qaList: state.store.storeManage.qaList,
    getCreditsRules: state.store.storeManage.getCreditsRules,
    addNewQuestion: state.store.storeManage.addNewQuestion,
    changeRule: state.store.storeManage.changeRule,
    changeQaList: state.store.storeManage.changeQaList,
    delQuestion: state.store.storeManage.delQuestion
}))
@withRouter
@observer
class EditRule extends Component {

    componentDidMount() {
        this.props.getCreditsRules();
    }

    submitRules = () => {
        if (!this.checkDataValidate()) {
            return;
        }
        const { rule, instruction, qaList } = this.props;
        let params = {
            point_obtain_rule: rule,
            point_related_instructions : instruction,
            point_questions: qaList.map((item, index) => ({...item, number: index + 1}))
        };
        this.props.saveEditRules(params);
    }

    checkDataValidate = () => {
        for (var item of this.props.qaList) {
            if (!item.question || !item.answer) {
                message.warning('请将问题与回答填写完整');
                return false;
            }
        }
        return true;
    }

    delQuestion = (index) => {
        var vm = this;
        confirm({
            title: '是否删除？',
            onOk() {
                vm.props.delQuestion(index);
            },
            onCancel() {},
        });
    }

    render() {
        const { qaList, rule, instruction, addNewQuestion, changeRule, changeQaList } = this.props;
        return (
            <div className="edit-rule-panel">
                <div className="form-row">
                    <div className="label">积分获取规则</div>
                    <div className="input">
                        <Input 
                            value={rule}
                            maxLength={150}
                            onChange={(e) => changeRule('rule', e.target.value)}/>
                    </div>
                </div>
                <div className="form-row">
                    <div className="label">规则相关说明</div>
                    <div className="input">
                        <Input
                            maxLength={150}
                            value={instruction}
                            onChange={(e) => changeRule('instruction', e.target.value)}/>
                    </div>
                </div>
                {
                    qaList.map((item, index) => 
                        [<div className="form-row">
                            <div className="label">常见问题{index + 1}</div>
                            <div className="input">
                                <Input 
                                    maxLength={20}
                                    value={qaList[index].question} 
                                    onChange={(e) => changeQaList(index, 'question', e.target.value)}/>
                            </div>
                            <div 
                                className="del-btn" 
                                onClick={()=>this.delQuestion(index)}
                            >删除</div>
                        </div>, <div className="form-row">
                            <div className="label">回答{index + 1}</div>
                            <div className="input">
                                <TextArea 
                                    rows={4}
                                    value={qaList[index].answer} 
                                    onChange={(e) => changeQaList(index, 'answer', e.target.value)}/>
                            </div>
                        </div>]
                    )
                }
                <Button 
                    className="add-btn"
                    onClick={addNewQuestion}>
                    添加问题
                </Button>
                <Button 
                    className="submit-btn" type="primary"
                    onClick={this.submitRules}>
                    保存并发布
                </Button>
            </div>
        );
    }
}

export default EditRule;
