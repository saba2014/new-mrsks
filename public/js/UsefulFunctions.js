var GetFirstSymbols = function (count, text) {
    if (text.length) {
        var len = text.length;
        if (len > count) {
            var res = text.substring(0, count);
            return res + "...";
        } else
            return text;
    } else
        return "";
};



/*
function for loading ways of universe, where tplnrs  is global array
 */
let results = {};

let preloadDataForMenu = function (callback) {
    $.when(
        $.ajax({
            url: window.location.origin + '/api/getobjs?type=UniverseWays',
            beforeSend: function (request) {

            },
            type: 'GET',
            success: function (response) {
                let allWays = response.features.map(function (item) {
                    return {id: item._id, obj: item.properties};
                });
                results.ways = allWays;
            },
            error: function (e) {
                if (e.status === 403) {
                    JWT.createLoginWindow();
                }
                else {
                    console.error("Error occured trying to load UniverseWays");
                }
            }
        }),
        $.ajax({
            url: window.location.origin + '/api/getobjs?type=MobileDivisions',
            beforeSend: function (request) {
                let token = new TokenStorage();
                token.checkRelevance();
            },
            type: 'GET',
            success: function (response) {
                results.mobileDivisions = response.features;
            },
            error: function (e) {
                if (e.status === 403) {
                    JWT.createLoginWindow();
                }
                else {
                    console.error("Error occured trying to load MobileDivisions");
                }
            }
        })
    ).then(function () {
       callback();
    });
};

var ResizePicture = function (width, height, MaxW, MaxH) {
    var res = [];
    if ((width > MaxW) || (height > MaxH)) {
        var diff = width / MaxW;
        if ((height / MaxH) > diff)
            diff = height / MaxH;
        res.push(width / diff);
        res.push(height / diff);
        return res;
    } else {
        res.push(width);
        res.push(height);
        return res;
    }
};

var GetPictureCenter = function (width, height, MaxW, MaxH) {
    var res = [];
    res.push((MaxW - width) / 2);
    res.push((MaxH - height) / 2);
    return res;
};

/*
Function that compares two sizes in px, return 'true' if one>two, 'false' otherwise
 */

var ComparePxSize = function (one, two) {
    one = parseInt(one, 10);
    two = parseInt(two, 10);
    if (one > two) return true;
    return false;
};

/*
function that parse url and returns value of 'name' field
 */

var getParameterByName = function (name, url) {
    if (!url) url = window.location.href;
    name = name.replace(/[\[\]]/g, "\\$&");
    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, " "));
};

/*
function that allows to find param's names in url's string
 */

var getNameOfParam = function (start, url) {
    if (!url) url = window.location.href;
    var st = url.indexOf('?' || '&', start);
    var fin = url.indexOf('=', start);
    var name = url.substring(st + 1, fin);
    return name;
};

/*
function that returns type of search
 */

var getSearchType = function (name) {
    if (name === 'tplnr')
        return 'techSearch';
    return name;
};

/*
function which check is properties exsist, if yes, return text with necessary format
 */

let isPropertieExsist = function (obj, name, textName) {
    if (obj[name]) {
        if (obj[name] !== "") {
            let result = '<br>' + textName + obj[name];
            return result;
        }
    }
    else return "";
};

/*
function that converts unix time to Normal hour:min:sec time format
 */

let fromUnixToNormalTime = function (unixtime) {
    let date = new Date(unixtime * 1000);
    let hours = date.getHours();
    let minutes = "0" + date.getMinutes();
    let seconds = "0" + date.getSeconds();
    let formattedTime = hours + ':' + minutes.substr(-2) + ':' + seconds.substr(-2);
    return formattedTime;
};

let timeNow = function (unix = true) {
    if (unix === true) {
        return Math.round(new Date().getTime() / 1000);
    }
    else return new Date();
};

let reverseCoordinates = function (arr) {
    for (let i = 0; i < arr.length; i++) {
        let a = arr[i][0];
        let b = arr[i][1];
        arr[i][0] = b;
        arr[i][1] = a;
    }
};

let isLocal = function(url){
    let res = /.local$/.test(url);
    return res;
}
