import React, { Component } from 'react'
import { observer, inject } from 'mobx-react';
import { withRouter, Link } from 'react-router-dom';
import { Button, Checkbox, message, Tabs } from 'antd';
const TabPane = Tabs.TabPane;

@inject(state => ({
    history: state.history,
    router: state.router,
    hash: state.router.location.hash,
    selectedItems: state.store.cart.selectedItems,
    setSelectedItems: state.store.cart.setSelectedItems
}))

@withRouter
@observer
export default class CartCard extends Component {
    
    componentDidMount() {
    }

    onChange = (e) => {
        this.props.setSelectedItems(this.props.data.sc_id, e.target.checked);
    }

    render() {
        let { title, small_img, width, height, sc_id, eid, ext } = this.props.data;
        return (<div className="cart-card-container">
            <div className="cart-card-img-container">
                <img className="cart-card-img" 
                    src={small_img} 
                    onClick={() => this.props.clickImg(eid)}></img>
                <div className="cart-card-checkbox">
                    <Checkbox
                        checked={this.props.selectedItems.indexOf(sc_id) !== -1}
                        onChange={this.onChange}
                    >
                    </Checkbox>
                </div>
            </div>
            <div className="cart-card-toolbar">
                <div className="cart-card-info">
                    <span className="cart-card-title">{`${title}.${ext}`}</span>
                    <span className="cart-card-size">规格：{width} x {height} px </span>
                </div>
                <div className="cart-card-del span-btn" onClick={()=>this.props.onDel(sc_id)}>
                    <span className="icon-span">&#xf05b;</span>
                </div>
            </div>
        </div>)
    }
}