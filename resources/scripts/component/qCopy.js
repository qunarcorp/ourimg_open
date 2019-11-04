import React from 'react'
import { Route, Redirect } from 'react-router-dom';
import { Button, message } from 'antd';
import copy from 'copy-to-clipboard';

const QCopy = ({ keyword }) => (
        <Button 
        className={`q-copy-tool ${keyword ? '' : 'hide'}`} 
        size='small' 
        onClick={()=>{
            copy(keyword);
            message.success('已复制到剪贴板');
        }}>复制关键词</Button>
);

export default QCopy;