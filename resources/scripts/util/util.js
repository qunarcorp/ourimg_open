import Http from './http';
import { jsonToQuery } from './http';
/**
 * url压缩
 * parseUrl
 * @param {any} url
 * @param {any} obj
 * @returns
 */
function parseUrl(url, obj) {
    const args = Object.keys(obj);
    return args.reduce(
        (prev, curr) => prev.replace(`:${curr}`, obj[curr]),
        url
    );
}

/**
 * http二层函数
 * http
 * @param {any} option
 * @returns
 */
function http(option) {
    function parseUrl(url, obj) {
        if (!obj) {
            return { newUrl: url, newParams: obj };
        }
        const args = Object.keys(obj);
        const newUrl = args.reduce((prev, curr) => {
            const res = prev.replace(`:${curr}`, obj[curr]);
            if (res !== prev) delete obj[curr];
            return res;
        }, url);
        return { newUrl, newParams: obj };
    }
    const http = Http(option);
    const get = async function (url, params, fetchOption) {
        const { newUrl, newParams } = parseUrl(url, params);
        const res = await http.get(newUrl, newParams, fetchOption);
        return res;
    };

    const post = async function (url, params, fetchOption) {
        let newUrl, newParams;
        if (typeof params !== 'string') {
            const res = parseUrl(url, params);
            newUrl = res.newUrl;
            newParams = res.newParams;
            (!fetchOption || (fetchOption && !fetchOption['Content-Type'])) && (newParams = JSON.stringify(newParams));
            (fetchOption && fetchOption['Content-Type'] === "application/x-www-form-urlencoded")
                && (newParams = jsonToQuery(newParams));

        } else {
            newUrl = url;
            newParams = params;
        }
        const res = await http.post(newUrl, newParams, fetchOption);
        return res;
    };

    const postFormData = http.postFormData;
    const getCross = http.getCross;


    const resquest = http.request;

    const HTTP = {
        get,
        getCross,
        post,
        postFormData,
        resquest
    };
    return HTTP;
}

/**
 * 获取路由相关的参数 category 和 code
 * getProjectType
 * @param {any} path
 * @returns
 */
function getProjectType(path) {
    const reg = new RegExp('^/(qp|native|app)/([^\/]+)($|\/)');
    const arr = path.match(reg);
    return arr || [];
}

/**
 * idToName
 * @param {any} path
 * @returns
 */
function idToName(list, listId, searchId, name) {
    const temp = list.find(item => item[listId] === searchId);
    return (temp && temp[name]) || '';
}

/**
 * 获取query对象
 * getQueryObject
 * @param {any} string query
 */
function getQueryObject(str) {
    if (!str) {
        return {};
    }
    str = str.replace('?', '');
    const strArr = str.split('&');
    const result = {};
    strArr.forEach((item) => {
        const itemArr = item.split('=');
        result[itemArr[0]] = itemArr[1];
    });
    return result;
}


/**把一个query对象转换为query
 * transObjectToQuery
 */
function transObjectToQuery(obj) {
    const result = [];
    for (const i in obj) {
        if (typeof obj[i] === 'string' || typeof obj[i] === 'number') {
            result.push(`${i}=${obj[i]}`);
        }
    }
    const query = result.join('&');
    return `?${query}`;
}
/**
 * 获取字符串字节长度
 * @param {*} str
 */
function getStrLength(str) {
    return str.replace(/[^\x00-\xff]/g,"aa").length;
}
/**
 * 控制是否格式化小数
 * @param {*} str
 */
function formatInputNumber(value, step) {
    let num = Math.pow(10,step);
    return value.toString().split('.')[1] && value.toString().split('.')[1].length > step ?
        parseInt((value * num).toString())/num : value
}
/**
 * 防抖函数
 * @param {*} fn
 * @param {*} delay
 */
let handle;
function debounce(fn, delay) {
    return function (e) {
        // 取消之前的延时调用
        clearTimeout(handle);
        handle = setTimeout(() => {
            fn(e);
        }, delay);
    }

}

/**
 * icon字符转换
 * @param {*} fn
 * @param {*} delay
 */
function transIconStr(text) {
    return unescape(text.replace(/&#x(.*);/, '%u$1'));
}
/**
 * 获取IE浏览器版本
 */
function getIEVersion() {
    var userAgent = navigator.userAgent; //取得浏览器的userAgent字符串
    var isIE = userAgent.indexOf("compatible") > -1 && userAgent.indexOf("MSIE") > -1; //判断是否IE<11浏览器
    var isEdge = userAgent.indexOf("Edge") > -1 && !isIE; //判断是否IE的Edge浏览器
    var isIE11 = userAgent.indexOf('Trident') > -1 && userAgent.indexOf("rv:11.0") > -1;
    if(isIE) {
        var reIE = new RegExp("MSIE (\\d+\\.\\d+);");
        reIE.test(userAgent);
        var fIEVersion = parseFloat(RegExp["$1"]);
        if(fIEVersion == 7) {
            return 7;
        } else if(fIEVersion == 8) {
            return 8;
        } else if(fIEVersion == 9) {
            return 9;
        } else if(fIEVersion == 10) {
            return 10;
        } else {
            return 6;//IE版本<=7
        }
    } else if(isEdge) {
        return 'edge';//edge
    } else if(isIE11) {
        return 11; //IE11
    }else{
        return -1;//不是ie浏览器
    }
}

function isChromeBrowser() {
    var userAgent = navigator.userAgent;
    return userAgent.indexOf("Chrome") > -1 && userAgent.indexOf("Safari") > -1;
}

function setVisitCount() {
   //
}

function arrIntersection(a, b) {
    if (a && b) {
        let intersection = a.filter(v => b.includes(v))
        return intersection.length > 0;
    } else {
        return true;
    }
}

//排序的函数
function objKeySort(arys) {
    //先用Object内置类的keys方法获取要排序对象的属性名，再利用Array原型上的sort方法对获取的属性名进行排序，newkey是一个数组
    var newkey = Object.keys(arys).sort();　　
    var newObj = {}; //创建一个新的对象，用于存放排好序的键值对
    for(var i = 0; i < newkey.length; i++) {
        //遍历newkey数组
        newObj[newkey[i]] = arys[newkey[i]];
        //向新创建的对象中按照排好的顺序依次增加键值对

    }
    return newObj; //返回排好序的新对象
}
//判断对象是否相等
function objEqual(a, b) {
    return JSON.stringify(objKeySort(a)) === JSON.stringify(objKeySort(b));
}

function getBase64(img, callback) {
    const reader = new FileReader();
    reader.addEventListener('load', () => callback(reader.result));
    reader.readAsDataURL(img);
}
export { objEqual, objKeySort, arrIntersection, setVisitCount, getIEVersion,
    transIconStr, debounce, parseUrl, http, getProjectType, idToName,
    isChromeBrowser, getQueryObject, formatInputNumber, transObjectToQuery, getStrLength, getBase64 };
