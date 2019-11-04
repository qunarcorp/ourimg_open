import React, { Component } from 'react'
import { Radio, Tooltip } from 'antd';
import { purposeMap, copyrightMap } from 'CONST/map';
import { observer, inject } from 'mobx-react';
import { withRouter } from 'react-router-dom';
import { arrIntersection } from 'UTIL/util';
const RadioGroup = Radio.Group;

@inject(state => ({
    history: state.history,
    router: state.router,
    hash: state.router.location.hash,
    filterObj: state.store.google.filterObj,
    userInfo: state.store.global.userInfo
}))
@withRouter
@observer
class QCRSelect extends Component {

    render() {
        let { filterObj, editFlag, purpose, onClick, userInfo, iconShow } = this.props;
        return (
            <div className="q-copyright-select">
            {
                editFlag
                ? <div>
                {
                    Object.keys(filterObj.purpose).map((target, index) => {
                        if (target !== '3' || arrIntersection(userInfo.role, ['admin', 'super_admin'])) {
                            return <div className="radio-row" onClick={ () => onClick("purpose", target) } key={index}>
                                <i className={`icon-font-checkbox small ${purpose == target ? 'checked' : 'none-checked'}`}>
                                    &#xe337;
                                </i>
                                <Tooltip placement="bottomLeft" overlayClassName="q-tooltip" title={copyrightMap[target]}>
                                    {filterObj.purpose[target]}
                                </Tooltip>
                            </div>;
                        } else {
                            return '';
                        }
                    })
                }
                </div> : purpose === '' ? '无' :
                <div>
                    <Tooltip placement="bottomLeft" overlayClassName="q-tooltip" title={copyrightMap[purpose]}>
                    {
                        iconShow && <i className="icon-font-ourimg">&#xe159;</i>
                    }
                        {purposeMap[purpose]}
                    </Tooltip>
                </div>
            }
            </div>
        )
    }
}

export default QCRSelect;
