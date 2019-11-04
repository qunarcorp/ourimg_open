import React from 'react'
import { blankHintMap } from 'CONST/map';

const QBlank = ({type}) => (
    <div className="q-blank-box">
        <img src="/img/blank.png" className="blank-img"/>
        <p className="blank-hint">{blankHintMap[type]}</p>
    </div>
);

export default QBlank;