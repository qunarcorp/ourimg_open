import React, { Component } from 'react'
import { Button, Input, Checkbox, Modal } from 'antd';
import { observer, inject } from 'mobx-react';
import { withRouter } from 'react-router-dom';
import UserItem from './userItem';
import SearchResult from './searchResult';
import StructureTree from './structureTree';

@inject(state => ({
    history: state.history,
    router: state.router,
    hash: state.router.location.hash,
    userQuery: state.store.authManage.userQuery,
    changeValue: state.store.authManage.changeValue,
    selectedUser: state.store.authManage.selectedUser,
    searchPage: state.store.authManage.searchPage,
    searchPageSize: state.store.authManage.searchPageSize,
    searchLastPage: state.store.authManage.searchLastPage,
    searchDataLoading: state.store.authManage.searchDataLoading,
    searchDataDone: state.store.authManage.searchDataDone,
    searchDataList: state.store.authManage.searchDataList,
    getSearchList: state.store.authManage.getSearchList
}))
@withRouter
@observer
class AddUserModal extends Component {

    onQueryChange= (e) => {
        this.props.changeValue('searchPage', 1);
        this.props.changeValue('userQuery', e.target.value);
        e.target.value !== '' && this.props.getSearchList({
            query: e.target.value,
            page: 1,
            page_size: this.props.searchPageSize
        });
    }

    loadNextPage = () => {
        let { userQuery, searchPage, searchPageSize, searchDataDone } = this.props;
        if (searchDataDone) {
            return;
        }
        this.props.changeValue('searchPage', searchPage + 1);
        this.props.getSearchList({
            query: userQuery,
            page: searchPage + 1,
            page_size: searchPageSize
        });
    }

    render() {
        let { title, userQuery, visible, onClose, onSubmit, selectedUser,
            searchDataDone, searchDataLoading, searchDataList } = this.props;
        return (
            <Modal
                className="q-modal admin-add-user-modal"
                visible={visible}
                footer={null}
                title={title}
                onCancel={onClose}
                width={800}
                >
                <div className="edit-confirm-content">
                    <div className="search-panel">
                        <Input className="search-bar"
                            placeholder="输入名字检索"
                            onChange={this.onQueryChange}
                            value={userQuery} allowClear
                        />
                        <div className="search-content">
                        {
                            userQuery !== '' ?
                            <SearchResult
                                loading={searchDataLoading}
                                isDone={searchDataDone}
                                onLoad={this.loadNextPage}
                                data={searchDataList}/> :
                            <StructureTree/>
                        }
                        </div>
                    </div>
                    <div className="select-panel">
                        <div className="select-num">已选 {Object.keys(selectedUser).length}/100</div>
                        <div className="select-list">
                            {
                                Object.values(selectedUser).map(user =>
                                <UserItem closeable size="small" key={user.userid} data={user}/>)
                            }
                        </div>
                    </div>
                </div>
                <div className="edit-confirm-btngroup">
                    <Button type="primary" onClick={onSubmit} className='left-btn'>确认</Button>
                </div>
            </Modal>
        )
    }
}

export default AddUserModal;
