import React, { Component } from "react";
import { observer, inject } from "mobx-react";
import {
    Route,
    withRouter,
    Link
} from "react-router-dom";

const AuditRule = () => (
    <div className="q-auditRule-page content">
        <h1 className="article-title">
            去哪儿图片审核规范
        </h1>
        <div className="article-content">
            <p className="paragraph">
                1、图片拍摄<span className="hightlight">题材主旨为旅游风景</span>，不限地域、不限器材（手机/相机均可），不限色调（彩色，黑白均可）；
            </p>
            <p className="paragraph">
                2、<span className="hightlight">作品需保证为上传者本人原创，不可出现任何侵犯他人权益的情形，如非本人拍摄的作品需要提供原作者的书面授权。</span>图片内容需积极健康向上，不可涉及暴力，色情，诋毁社会，宗教禁忌等法律不允许的范围；
            </p>
            <p className="paragraph">
                3、图片宽度需大于2000px，支持格式：PNG\JPG\TIFF，不可为拼接图片，原片通过率更高；
            </p>
            <p className="paragraph">
                4、<span className="hightlight">图片不可侵犯个人肖像权</span>，含可清晰识别面部特征的个人肖像但未获得肖像授权的、含政治人物、明星肖像的图片将会不被通过；
            </p>
            <p className="paragraph">
                5、图片高清、颜色鲜明，曝光正常，<span className="hightlight">画面需有明确的主体，尽可能的体现出风景的特点，人文事件环境特征</span>；
            </p>
            <p className="paragraph">
                6、图片内容不可重复度过高，过于相似；
            </p>
            <p className="paragraph">
                7、图片上传时，<span className="hightlight">拍摄地点、关键词等信息需与图片内容相符</span>，突出画面元素特点等，标识信息丰富的图片通过率会更高。
            </p>
        </div>
    </div>
);

export default AuditRule;