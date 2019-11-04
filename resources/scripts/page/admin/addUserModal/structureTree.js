import React, { Component } from 'react'
import { Tree } from 'antd';
import { observer, inject } from 'mobx-react';
import { withRouter } from 'react-router-dom';
import UserItem from './userItem';
const { TreeNode } = Tree;
@inject(state => ({
    history: state.history,
    router: state.router,
    hash: state.router.location.hash,
    treeData: state.store.authManage.treeData,
    loadTreeNodeData: state.store.authManage.loadTreeNodeData
}))
@withRouter
@observer
class StructureTree extends Component {
    
      renderTreeNodes = data => data.map((item) => {
        return item.isLeaf ? 
            <TreeNode 
                className="tree-leaf-node"
                title={<UserItem clickable data={item}/>} 
                isLeaf={item.isLeaf}
                key={item.userid} dataRef={item}/>
            : <TreeNode 
                title={<span className="tree-node-title">
                    <span className="node-title">{item.dept_name}</span>
                    <span className="node-num">({item.manager_num}/{item.employee_num})</span>
                </span>} 
                key={item.id} dataRef={item}>
                {item.children && this.renderTreeNodes(item.children)}
            </TreeNode>;
      })


    render() {
        return (
            <div className="struc-tree-container">
                <Tree loadData={this.props.loadTreeNodeData} defaultExpandedKeys={['0']}>
                    {this.renderTreeNodes(this.props.treeData)}
                </Tree>
            </div>
        )
    }
}

export default StructureTree;