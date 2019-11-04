import React, { Component } from "react";
import { observer, inject } from "mobx-react";
import {
    Route,
    withRouter,
    Link
} from "react-router-dom";
import { Table } from 'antd';
const dataSource = [{
    key: '1',
    name: '上传图片',
    score: '+2',
    limit: '无',
    desc: ['图片被审核通过后积分将计入积分账户中，未通过则不计积分',
        '（如遇特殊活动时，图片上传将获得额外奖励积分）']
  }, {
    key: '2',
    name: '图片被点赞/收藏',
    score: '+1',
    limit: '5',
    desc: ['1、图片被其他用户点赞/收藏，均可奖励积分',
        '2、同一图片同一用户被点赞/收藏多次，积分仅计算第一次',
        '3、为自己的图片点赞/收藏不计算积分']
  }, {
    key: '3',
    name: '图片被下载',
    score: '+5',
    limit: '无',
    desc: ['1、图片被下载使用，均可奖励积分',
        '2、同一图片同一用户被下载多次，积分仅计算第一次',
        '3、下载自己上传的图片不计算积分']
//   },{
//     key: '4',
//     name: '任务城市首图',
//     score: '+50',
//     limit: '1',
//     desc: ['发布城市任务期间，该城市首图奖励积分（以图片提交时间为准）']
//   },{
//     key: '5',
//     name: '任务城市非首图',
//     score: '+5',
//     limit: '1',
//     desc: ['发布城市任务期间，该城市下上传的非首图奖励积分（以图片提交时间为准）']
  },{
    key: '6',
    name: '任务奖励积分',
    score: '+5',
    limit: '无',
    desc: ['商城首页积分任务完成奖励（仅限标明了有任务积分奖励的任务）']
  },{
    key: '7',
    name: '精选推荐',
    score: '+20',
    limit: '无',
    desc: ['图片被评为“精选“的额外奖励积分，可与图片上传积分叠加计算']
  }];

const columns = [{
title: '项目',
dataIndex: 'name',
key: 'name',
}, {
title: '积分',
dataIndex: 'score',
key: 'score',
}, {
title: '单日次数限制',
dataIndex: 'limit',
key: 'limit',
}, {
title: '详细说明',
dataIndex: 'desc',
key: 'desc',
render: arr => (
    <div>
        {arr.map(text => <p key={text} className="desc-rule">{text}</p>)}
    </div>
)
}];
@inject(state => ({
    history: state.history,
    router: state.router,
    pathname: state.history.location.pathname,
    ruleHint: state.store.credits.ruleHint,
    ruleQAList: state.store.credits.ruleQAList,
    ruleInstruction: state.store.credits.ruleInstruction,
    getCreditsRules: state.store.credits.getCreditsRules
}))

@withRouter
@observer
class CreditsRule extends Component {

    componentDidMount() {
        this.props.getCreditsRules();
    }

    render() {
        const { ruleHint, ruleQAList, ruleInstruction } = this.props;
        return (
            <div className="credits-content credits-rule-content">
                <div className="rule-title">积分细则</div>
                <div className="rule-desc">{ruleHint}</div>
                <Table className="rule-table" dataSource={dataSource} columns={columns} pagination={false}/>
                <div className="rule-title">其他问题</div>
                <div className="rule-desc">{ruleInstruction}</div>
                <div className="rule-QA">
                {
                    ruleQAList.map((item, index) => [
                        <div className="rule-question" key={`q-${index}`}>
                            <i className="icon-font-ourimg">&#xf000;</i>
                            <div className="rule-text">{item.question}</div>
                        </div>, <div className="rule-answer" key={`a-${index}`}>
                            <i className="icon-font-ourimg">&#xe163;</i>
                            <div className="rule-text">
                                {item.answer.split("\n").map(text => <p>{text}</p>)}
                            </div>
                        </div>
                    ])
                }
                </div>
            </div>
        );
    }
}

export default CreditsRule;
