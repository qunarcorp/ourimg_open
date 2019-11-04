import React, { Component } from 'react'
import { Checkbox } from 'antd';
import { observer, inject } from 'mobx-react';
import { withRouter } from 'react-router-dom';
import { authTypeMap } from 'CONST/map';

@inject(state => ({
    history: state.history,
    router: state.router,
    hash: state.router.location.hash,
    role: state.store.authManage.role,
    userQuery: state.store.authManage.userQuery,
    selectedUser: state.store.authManage.selectedUser,
    handleSelectUser: state.store.authManage.handleSelectUser
}))
@withRouter
@observer
class UserItem extends Component {

    onSelectItem = (e) => {
        let { data, role } = this.props;
        if (!this.props.clickable || data.role.indexOf(role) !== -1) {
            return;
        }
        this.props.handleSelectUser(this.props.data, this.props.selectedUser[this.props.data.userid] ? 'del' : 'add');
    }

    matchStr = (str) => {
        let arr = str.split(this.props.userQuery), res = [];
        arr.map((item, index) => {
            res.push(<span>{item}</span>);
            if (index !== arr.length - 1) {
                res.push(<span className="highlight">{this.props.userQuery}</span>);
            }
        })
        return res;
    }

    render() {
        let { showDept, clickable, closeable, size, data,
            selectedUser, handleSelectUser, role } = this.props;
        let { userid, adname, dept, avatar} = data;
        return (
            <div className={`admin-user-item ${clickable ? 'clickable' : ''}`} onClick={this.onSelectItem}>
                <img src={avatar} className={`avatar ${size}`}/>
                <div className="user-info">
                    <div className="username">
                        {this.matchStr(adname)}({this.matchStr(userid)})
                        {
                            data.role.indexOf(role) !== -1 &&
                            <span className="user-role">
                                <i className="icon-font-ourimg">&#xe5d7;</i>
                                {authTypeMap[role]}
                            </span>
                        }
                    </div>
                    {
                        showDept && <div className="user-dept">{dept}</div>
                    }
                </div>
                <div className="operator">
                    {
                        clickable &&
                        <Checkbox
                            disabled={data.role.indexOf(role) !== -1}
                            checked={!!selectedUser[userid]}
                            className="check-btn">
                        </Checkbox>,
                        <i className={`icon-font-checkbox small ${data.role.indexOf(role) !== -1 ? 'disabled' : (!!selectedUser[userid] ? 'checked' : 'none-checked')}`}>
                            &#xe337;
                        </i>
                    }
                    {
                        closeable &&
                        <i className="icon-font-ourimg close-btn"
                            onClick={() => handleSelectUser(data, 'del')}
                        >&#xf3f3;</i>
                    }
                </div>
            </div>
        )
    }
}

export default UserItem;
