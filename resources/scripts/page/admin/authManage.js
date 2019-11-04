import React, { Component } from "react";
import { observer, inject } from "mobx-react";
import { Route, withRouter, Link } from "react-router-dom";
import { authTypeMap } from "CONST/map";
import { Tabs, Modal, Table, Input, Button, Checkbox, Row, Col, Divider, message, Pagination } from "antd";
import autobind from "autobind-decorator";
import AddUserModal from './addUserModal';
import { arrIntersection } from 'UTIL/util';
import QBlank from 'COMPONENT/qBlank';
const TabPane = Tabs.TabPane;
const Search = Input.Search;

@inject(state => ({
    history: state.history,
    router: state.router,
    pathname: state.history.location.pathname,
    getList: state.store.authManage.getList,
    authManage: state.store.authManage,
    changeValue: state.store.authManage.changeValue,
    deleteAdmin: state.store.authManage.deleteAdmin,
    addAdminUser: state.store.authManage.addAdminUser,
    onCheck: state.store.authManage.onCheck,
    checkAll: state.store.authManage.checkAll,
    resetCheckStatus: state.store.authManage.resetCheckStatus,
    userInfo: state.store.global.userInfo
}))
@withRouter
@observer
class AuthManage extends Component {
    state = {
        role: "admin",
        pagination: {
            current: 1,
            pageSize: 10
        },
        query: "",
        addVisible: false,
        delVisible: false
    };

    showDeleteModal = () => {
        let delUserNum = this.props.authManage.dataSource.filter((item) => {
            return item.checked;
        }).length
        if (delUserNum == 0) {
            message.warning('请选择要删除的用户');
        }else{
            this.setState({
                delVisible: true,
            });
        }
    }

    closeDeleteModal = (user) => {
        this.setState({
            delVisible: false,
        }, () => {
            this.props.resetCheckStatus()
        });
    }

    submitDel = () => {
        let delUser = this.props.authManage.dataSource.filter((item) => {
            return item.checked;
        }).map((item) => {
            return item.username;
        })
        console.log(delUser, delUser.join(","))
        this.props.authManage.handleDelete({username: delUser.join(",")}, () => {
            this.closeDeleteModal();
        });
    }

    componentDidMount() {
        this.props.getList();
    }

    showAddModal = (visible) => {
        this.setState({
            addVisible: visible
        });
        if (visible) {
            this.props.changeValue('userQuery', '');
            this.props.changeValue('selectedUser', {});
            this.props.changeValue('searchDataList', []);
        }
    }

    handleAddSubmit = () => {
        let { role, selectedUser } = this.props.authManage;
        this.props.addAdminUser({
            role: role,
            username: Object.keys(selectedUser).join(',')
        }, () => {
            this.showAddModal(false);
            this.props.getList();
        })
    }

    render() {
        let { delVisible, delUser } = this.state;
        const {
            dataSource,
            handleSwitchTab,
            handleSearch,
            handlePage,
            handleSearchValueChange,
            pagination,
            role,
            query
        } = this.props.authManage;

        let delUserNum = dataSource.filter((item) => {
            return item.checked
        }).length;
        console.log("this.props.userInfo.role", this.props.userInfo.role);
        return (
            <div className="auth-manage-page content">
                <div className="header-manage__container">
                    <div className="header-manage__text--b">MANAGE</div>
                    <div className="header-manage__text--f">权限管理</div>
                    <Tabs className="material-tab" onChange={handleSwitchTab}>
                        {Object.keys(authTypeMap).map(key => {
                            return <TabPane tab={authTypeMap[key]} key={key} />;
                        })}
                    </Tabs>
                </div>
                <div className="search-container">
                    <Button
                        type="primary"
                        className={`add-admin ${(arrIntersection(this.props.userInfo.role, ['super_admin']) || role !== "admin") ? '' : 'hidden'}`}
                        onClick={()=>this.showAddModal(true)}>
                        {role === "admin" ? "+ 添加管理员" : "+ 添加设计运营"}
                    </Button>
                    <Button
                        type="primary"
                        className={`add-admin check-all ${(arrIntersection(this.props.userInfo.role, ['super_admin']) || role !== "admin") ? '' : 'hidden'}`}
                        onClick={ this.props.checkAll }>
                        { "全选" }
                    </Button>
                    <Button
                        type="primary"
                        className={`add-admin del ${(arrIntersection(this.props.userInfo.role, ['super_admin']) || role !== "admin") ? '' : 'hidden'}`}
                        onClick={ () => this.showDeleteModal() }>
                        { "删除" }
                    </Button>
                    <Search
                        value={query}
                        className="search"
                        enterButton
                        onSearch={handleSearch}
                        onChange={handleSearchValueChange}
                    />
                </div>
                <div className="user-list-box">
                    {
                        dataSource.length === 0 ? <QBlank type="empty"/>: dataSource.map((item, index) => {
                            return (
                                <div className={`user-box row-start ${ (index + 1) % 4 == 1 ? "row-start" : ((index + 1) % 4 == 0 ? "row-end" : "") }`}
                                    key={index}>
                                    <i className={`check-box icon-font-checkbox big ${ item.checked ? 'checked' : 'none-checked' }`}
                                        onClick={ () => this.props.onCheck(item, index, ! item.checked) }>
                                        &#xe337;
                                    </i>
                                    <Row className="avatar-box">
                                        <Col span={8} offset={8}>
                                            <img src={ item.avatar } width="80" height="80"/>
                                        </Col>
                                    </Row>
                                    <div className="info">
                                        <div className="row realname">
                                            <span> { item.realname } </span>
                                        </div>
                                        <div className="row username">
                                            <span> { item.username } </span>
                                        </div>
                                        <Row>
                                            <Col span={12} offset={6} className="divider">
                                            </Col>
                                        </Row>
                                        <div className="row dept">
                                            <span> { item.node_str } </span>
                                        </div>
                                    </div>
                                </div>
                            )
                        })
                    }
                    <Row>
                        <Col span={16} offset={8}>
                            <Pagination
                                className="material-pagination"
                                total={pagination.total}
                                current={pagination.current}
                                pageSize={pagination.pageSize}
                                onChange={pagination => handlePage(pagination)}
                                onShowSizeChange={(current, size) => handlePage({
                                        ...pagination,
                                        current: 1,
                                        pageSize: size
                                    })}
                                showSizeChanger
                                showQuickJumper
                            />
                        </Col>
                    </Row>
                </div>
                <AddUserModal
                    title={`添加${authTypeMap[role]}`}
                    visible={this.state.addVisible}
                    onClose={()=>this.showAddModal(false)}
                    onSubmit={this.handleAddSubmit}
                />
                <Modal
                    visible={delVisible}
                    footer={null}
                    title={null}
                    onCancel={this.closeDeleteModal}
                    width={380}
                    >
                    <p className="edit-confirm-content">确认要从{authTypeMap[role]}中删除{delUserNum}位用户吗？</p>
                    <div className="edit-confirm-btngroup">
                        <Button type="primary" onClick={this.submitDel} className='left-btn'>确认</Button>
                        <Button onClick={this.closeDeleteModal}>取消</Button>
                    </div>
                </Modal>
            </div>
        );
    }
}

export default AuthManage;
