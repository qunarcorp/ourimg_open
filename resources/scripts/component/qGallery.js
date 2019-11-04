import React, { Component } from 'react'
import InfiniteScroll from 'react-infinite-scroller';
import QImgCard from './qImgCard';
import QImgModal from './qImgModal';
import { inject, observer } from 'mobx-react';
import { withRouter } from 'react-router-dom';


@inject(state => ({
    isLogin: state.store.global.isLogin,
    userInfo: state.store.global.userInfo
}))

@withRouter
@observer
class QGallery extends Component {

    state = {
        visible: false,
        imgUrl: ''
    }

    componentDidMount() {
    }

    componentWillUnmount() {
    }

    loadMore = () => {
        // debugger
        console.log('loadMore');
        if (this.props.completed) {
            return;
        }
        this.props.load();
    }

    showBigImg = ({ visible, imgUrl }) => {
        this.setState({
            visible,
            imgUrl
        });
    }

    render() {
        let { visible, imgUrl } = this.state;
        let is_myUser;
        if (this.props.isLogin && this.props.location.pathname === '/user') {
            if (this.props.location.pathname + this.props.location.search === '/user?uid=' + this.props.userInfo.userName && this.props.isUpload) {
                is_myUser = true;
            } else {
                is_myUser = false;
            }
        }
        return (
            <div>
                { this.props.children }

                {this.props.elements.length > 0 &&
                <InfiniteScroll
                    loadMore={ this.loadMore }
                    hasMore={true || false}
                >
                    <div className={ 'q-pic-list' }>
                        {
                            this.props.elements.map((element, i) =>
                                <QImgCard
                                    onClickImg={ this.props.onClickImg }
                                    data={ element } index={ i } key={ i }
                                    onShow={ this.showBigImg }
                                    onOperate={ this.props.reload }
                                    isLogin={ this.props.isLogin }
                                    isMyUser={ is_myUser }/>)
                        }
                    </div>
                </InfiniteScroll>}
                {
                    this.props.loading &&
                    <div className="q-gallery-hint">加载中...</div>
                }
                {
                    this.props.completed && !this.props.hideHint &&
                    <div className="q-gallery-hint">到底啦~</div>
                }
                <QImgModal visible={ visible } url={ imgUrl } onClose={ this.showBigImg }/>
            </div>
        );
    }
}

export default QGallery;
