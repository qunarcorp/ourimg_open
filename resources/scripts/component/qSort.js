import React, { Component } from 'react'
import { Radio } from 'antd';

const RadioButton = Radio.Button;
const RadioGroup = Radio.Group;

class QSort extends Component {

    render() {
        let { value, onChange } = this.props;
        return (
            <div className="q-sort-tool">
                <span>排序：</span>
                <RadioGroup value={value} size="small" onChange={onChange}>
                    <RadioButton value="0">默认</RadioButton>
                    <RadioButton value="1">最新上传</RadioButton>
                    <RadioButton value="2">下载最多</RadioButton>
                    <RadioButton value="3">收藏最多</RadioButton>
                    <RadioButton value="5">点赞最多</RadioButton>
                </RadioGroup>
            </div>
        )
    }
}

export default QSort;
