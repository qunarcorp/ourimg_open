import React, { Component } from 'react'
import UserItem from './userItem';
import { List, Spin, Icon } from 'antd';
import InfiniteScroll from 'react-infinite-scroller';

const antIcon = <Icon type="loading" style={{ fontSize: 24 }} spin />;
class SearchResult extends Component {

    render() {
        let { loading, isDone, data, onLoad } = this.props;
        return (
            <div className="search-user-result">
            <InfiniteScroll
                initialLoad={false}
                pageStart={0}
                loadMore={onLoad}
                hasMore={!loading && !isDone}
                useWindow={false}>
                <List
                    itemLayout="horizontal"
                    dataSource={data}
                    renderItem={item => (
                        <UserItem clickable showDept key={item.userid} data={item}/>
                    )}
                >
                    {loading && !isDone && (
                    <div className="demo-loading-container">
                        <Spin indicator={antIcon}/>
                    </div>
                    )}
                </List>
            </InfiniteScroll>
            </div>
        )
    }
}

export default SearchResult;