import jsonp from 'jsonp';

export function jsonToQuery(json) {
    const arr = [];
    for (const item in json) {
        if (json[item] !== '' && json[item] !== null && json[item] !== undefined) {
            arr.push(`${encodeURIComponent(item)}=${encodeURIComponent(typeof json[item] === 'string' ? json[item] : JSON.stringify(json[item]))}`);
        }
    }
    return arr.join('&');
}

function Http(
    $notify
) {
    // $notify = message.error;
    const NETWORKERROR = 1;
    const SERVERERROR = 2;
    const CLIENTERROR = 3;
    const DATAERROR = 4;
    const LOGICERROR = 5;
    const TIMEOUTERROR = 6;
    // const TIMEOUT = 8000;
    // const UNLOGINCODE = 401;

    const FETCHERRORMAG = new Map([
        [NETWORKERROR, '网络连接错误'],
        [SERVERERROR, '服务器错误'],
        [CLIENTERROR, '客户端错误'],
        [DATAERROR, '数据格式连接错误'],
        [LOGICERROR, '接口内容返回逻辑错误'],
        [TIMEOUTERROR, '请求超时']
    ]);

    const FetchOption = {
        handleError: true
    };

    class FetchError {

        constructor(
            errorno,
            err,
            res
        ) {
            this.errorno = errorno;
            this.message = err.errmsg || FETCHERRORMAG.get(errorno);
            this.data = err;
            this.res = res;
        }
    }
    // function timeout(t = TIMEOUT) {
    //     return new Promise((resolve, reject) => {
    //         window.setTimeout(() => {
    //             reject(TIMEOUTERROR);
    //         }, t);
    //     });
    // }
    function timeoutFetch(url, origin, fetchOption) {
    // return Promise.race([
    //   timeout(fetchOption.timeOut || TIMEOUT),
    //   fetch(url, origin)
    // ]);

        return fetch(url, origin);
    }

    async function request(
        url,
        originOption,
        fetchOption
    ) {
        const basicOption = {
            credentials: 'include'
        };

        const currentfetchOption = Object.assign({}, FetchOption, fetchOption);

        const { successMsg, failMsg, handleError, handleSuccess } = currentfetchOption;

        let res;
        try {
            res = await timeoutFetch(
                url,
                Object.assign({}, basicOption, originOption),
                currentfetchOption
            );
        } catch (e) {
            if (handleError) {
                $notify({
                    text: failMsg || FETCHERRORMAG.get(e === TIMEOUTERROR ? TIMEOUTERROR : NETWORKERROR),
                    type: 'error'
                });
            }
            if (e === TIMEOUTERROR) {
                throw new FetchError(TIMEOUTERROR, e);
            } else {
                throw new FetchError(NETWORKERROR, e);
            }
        }
        if (!res.ok) {
            const errorType = res.status === 404 ? CLIENTERROR : SERVERERROR;
            if (handleError) {
                $notify({
                    text: res.status+ ':' + FETCHERRORMAG.get(errorType),
                    type: 'error'
                });
            }
            throw new FetchError(errorType);
        }

        let data;
        try {
            data = await res.json();
        } catch (e) {
            if (handleError) {
                $notify({
                    text: FETCHERRORMAG.get(DATAERROR),
                    type: 'error'
                });
            }
            throw new FetchError(DATAERROR, e);
        }
        const { message, msg, status, ret } = data;
        // document.getElementById("qsso-login").click();
        if(status === 100) {
            document.getElementById("qsso-login").click();
            return;
        }
        if (status === 0 || ret ||  status === 108 || status === 103) {
            //返回格式为{"status": 'success', "status_id": 0, "msg": "msg", "data": {}}
            if (successMsg) {
                $notify({
                    text: successMsg,
                    type: 'success'
                });
            }
            if (handleSuccess === false) {
                return data;
            }
        } else {
            if (handleError) {
                $notify({
                    text: failMsg || message || msg || FETCHERRORMAG.get(LOGICERROR),
                    type: 'error'
                });
            }
            // throw new FetchError(LOGICERROR, data, data);
            return data;
        }
        return data;
    }

    async function get(
        url,
        params,
        fetchOption
    ) {
        // const newParams = Object.assign({
        //     timestamp: +new Date()
        // }, params || {});
        const newParams = params || {};
        const newUrl = params ? `${url}?${jsonToQuery(newParams)}` : url;
        let data;
        try {
            data = await request(
                newUrl,
                {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                },
                fetchOption
            );
        } catch (err) {
            throw err;
        }
        return data;
    }

    async function getCross(
        url,
        params,
        callback
    ) {
        // const newParams = Object.assign({
        //     timestamp: +new Date()
        // }, params || {});
        const newParams = params || {};
        const newUrl = params ? `${url}?${jsonToQuery(newParams)}` : url;
        try {
            jsonp(newUrl,{},callback);
        } catch (e) {
            throw (e)
        }
    }

    async function post(
        url,
        params,
        fetchOption
    ) {
        const opt = {
            method: 'POST',
            headers: {
                'Content-Type': fetchOption && fetchOption['Content-Type'] ? fetchOption['Content-Type'] : 'application/json'
            },
            body: params
        };
        let data;
        try {
            data = await request(url, opt, fetchOption);
        } catch (err) {
            throw err;
        }
        return data;
    }

    async function postFormData(
        url,
        params,
        fetchOption
    ) {
        const opt = {
            method: 'POST',
            body: params
        };
        let data;
        try {
            data = await request(url, opt, fetchOption);
        } catch (err) {
            throw err;
        }
        return data;
    }

    return {
        get,
        getCross,
        post,
        postFormData,
        request
    };
}
export default Http;
