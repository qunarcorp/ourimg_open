import React, { Component } from "react";
import { observer, inject } from "mobx-react";
import {
    Route,
    withRouter,
    Link
} from "react-router-dom";
import { messageTypeMap } from 'CONST/map';
import { Tabs, Pagination, message } from "antd";
import QBlank from "COMPONENT/qBlank";
const TabPane = Tabs.TabPane;

@inject(state => ({
    history: state.history,
    router: state.router,
    pathname: state.history.location.pathname,
    userInfo: state.store.global.userInfo,
    dataList: state.store.myMessage.dataList,
    totalCount: state.store.myMessage.totalCount,
    getDataList: state.store.myMessage.getDataList,
    setMsgRead: state.store.myMessage.setMsgRead
}))

@withRouter
@observer
class MyMessage extends Component {

    state = {
        tabKey: 'f',
        page: {
            current: 1,
            pageSize: 10
        },
        readId: ''
    };

    componentDidMount() {
        this.props.getDataList({
            offset: 0,
            limit: 10,
            read: 'f'
        });
    }

    switchTab = (key) => {
        this.setState({
            tabKey: key,
            page: {
                current: 1,
                pageSize: 10
            }
        });
        this.props.getDataList({
            offset: 0,
            limit: 10,
            read: key
        });
    }

    onPageChange = (current, pageSize) => {
        this.props.getDataList({
            offset: (current - 1) * pageSize,
            limit: pageSize,
            read: this.state.tabKey
        });
        this.setState({
            page: {
                current,
                pageSize
            }
        });
    }

    onClickMsg = (id) => {
        if (this.state.tabKey === 't') {
            return;
        }
        this.props.setMsgRead({id}, () => {
            message.success("消息已读");
            this.setState({
                readId: id
            });
            setTimeout(() => {
                let {current, pageSize} = this.state.page;
                this.props.getDataList({
                    offset: (current - 1) * pageSize,
                    limit: pageSize,
                    read: this.state.tabKey
                });
            }, 1000);
        });
    }
    render() {
        let { page, tabKey, readId } = this.state;
        let { totalCount, dataList, userInfo } = this.props;
        return (
            <div className="my-message-page content">
                <div className='bread'>消息中心</div>
                <Tabs className="material-tab" onChange={this.switchTab}>
                    {Object.keys(messageTypeMap).map(key => {
                        return <TabPane tab={messageTypeMap[key]} key={key}/>;
                    })}
                </Tabs>
                { dataList.length === 0 ? <QBlank type='message' /> : <div className="msg-list-content">
                    {
                        dataList.map(item =>
                            <div className="my-msg-item" key={item.id} onClick={()=>this.onClickMsg(item.id)}>
                                <div className="msg-header" >
                                    <div className="msg-title">
                                        {item.title}
                                        {
                                            tabKey ==='f' && readId !== item.id &&
                                             <span className="msg-hint"></span>
                                        }
                                    </div>
                                    <div className="msg-time" >{item.create_time}</div>
                                </div>
                                <div className="msg-content" >
                                    {item.content}
                                    {
                                        item.type === 'praise' &&
                                        <Link to={{ pathname: "/user", search: `?uid=${userInfo.userName}` }}>
                                            <span className="msg-link">查看详情></span>
                                        </Link>
                                    }
                                    {
                                        item.type === 'point' &&
                                        <Link to="/credits/myCredits">
                                            <span className="msg-link">查看详情></span>
                                        </Link>
                                    }
                                </div>
                            </div>)
                    }
                </div>}
                <Pagination
                    className="material-pagination"
                    hideOnSinglePage
                    total={ +totalCount}
                    current={page.current}
                    pageSize={page.pageSize}
                    onChange={this.onPageChange}
                    onShowSizeChange={(current, size)=>this.onPageChange(1, size)}
                    showSizeChanger showQuickJumper />
            </div>
        );
    }
}

export default MyMessage;
