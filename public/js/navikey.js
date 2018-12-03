/* global L, map, cont, tmpLayer, jsbaseurl, popup_text, minimapenabled, drow, stopMap, cache_url*/

var N = {};
var kadastr_url = "";
N.onMapClick = function (e, text) {
    let popup = new Popup_line();
    var lTxt = popup.Seacrh(e.latlng, cache_url);
    if (text !== undefined) {
        lTxt = text + "<hr>" + lTxt;
    }
    var cObj = {
        oLocation: {
            latitude: e.latlng.lat,
            longitude: e.latlng.lng
        },
        oType: "noMove",
        oTxt: lTxt,
        oOType: "user"
    };
    N.chooseAddr(cObj);
    //N.check_distance();
};

N.addToReport = function (val) {
    if (val.oOType === "user") {
        $("#p_Report").children("#text").children("#uObject").html('<img class="leftimg" src="js/images/marker-icon.png" border=0>' + val.oTxt + "<hr>");
    }
    if (val.oOType === "ln") {
        $("#p_Report").children("#text").children("#uLine").html('<img class="leftimg" src="js/images/powerlinegreen.png" border=0>' + val.oTxt + "<hr>");
    }
    if (val.oOType === "ps") {
        $("#p_Report").children("#text").children("#uPStation").html('<img class="leftimg" src="js/images/powerstationgreen.png" border=0>' + val.oTxt + "<hr>");
    }
    $("#p_Report").show();

    var btns = document.querySelectorAll('button');
    //var clipboard = new Clipboard(btns);

    return false;
};

N.cadastrSearch = function (val) {
    $("#s_Select option[value='rucadastrNum']").prop("selected", true);
    $("#s_Input").val(val);
    // $("#s_RegList").hide();
};
var sMarker;
N.chooseAddr = function (myObj) {
    var lat = myObj.oLocation.latitude,
        lng = myObj.oLocation.longitude,
        type = myObj.oType,
        oType = myObj.oOType,
        oTxt = myObj.oTxt,
        dbId = myObj.dbID;

    if (lat !== 0) {
        var location = new L.LatLng(lat, lng);

        // удалить все маркеры и установить новый
        if (sMarker) {
            map.removeLayer(sMarker);
        }

        if (type === 'city' || type === 'administrative') {
            map.setView(location, 12, {
                animate: false
            });
        } else if ((type === 'point') || (type === 'Point')) {
            map.setView(location, map.getZoom(), {
                animate: false
            });
        } else if (type === 'noMove') {
        } else {
            map.setView(location, 15, {
                animate: false
            });
        }

        if (oTxt) {
            sMarker = new L.Marker(location);
            var sss = JSON.stringify(myObj);
            if (oType !== "worker") {
                oTxt = oTxt + "<br/> <input type=\'button\' value=\'Выбрать\' id=\'addBtn\' onclick=\'N.addToReport(" + sss + ")\'/>";
            }
            sMarker.bindPopup(oTxt, {
                maxHeight: window.innerHeight * 2 / 3,
                minWidth: 300,
                maxWidth: 300,
                keepInView: true,
                reserve: oTxt,
                idsInUse: [dbId]
            });
            map.addLayer(sMarker);
            if (drow._active === true) {
                drow._calc_disable();
                sMarker.openPopup();
                cont.check_popup();
                drow._calc_enable();
            }
            else {
                sMarker.openPopup();
                cont.check_popup();
            }
        }
    } else {
        alert("Объект без географических координат!\nПопробуйте поиск по адресу найденного объекта!");
    }
    cont.Check();
    return;
};

N.chooseLn = function (myObj) {

    var id = myObj.id,
        lat = myObj.oLocation.latitude,
        lng = myObj.oLocation.longitude,
        type = myObj.oType,
        oType = myObj.oOType,
        oTxt = myObj.oTxt,
        dbId = myObj.dbID;
    if (lat) {
        var location = new L.LatLng(lat, lng);


        // удалить все маркеры и установить новый
        if (sMarker) {
            map.removeLayer(sMarker);
        }
        map.setView(location, 12, {
            animate: false
        });
        if (oTxt) {
            sMarker = new L.Marker(location);
            var sss = JSON.stringify(myObj);
            if (oType !== "worker") {
                oTxt = oTxt + "<br/> <input type=\'button\' value=\'Выбрать\' id=\'addBtn\' onclick=\'N.addToReport(" + sss + ")\'/>";
            }
            sMarker.bindPopup(oTxt, {
                maxHeight: window.innerHeight * 2 / 3,
                minWidth: 300,
                maxWidth: 300,
                keepInView: true,
                reserve: oTxt,
                idsInUse: [dbId]
            });
            map.addLayer(sMarker);
            if (drow._active === true) {
                drow._calc_disable();
                sMarker.openPopup();
                cont.check_popup();
                drow._calc_enable();
            }
            else {
                sMarker.openPopup();
                cont.check_popup();
            }
        }
        cont.Check();
        cont.search.onLayerAdd(id, location);
    }
};

N.setInput = function (def) {
    $("#s_Input").val(def);
};

N.initMiniMap = function () {
    minimap = L.map('minimap', {
        attribution: "",
        attributionControl: false
    });
    var mtLayer = L.tileLayer('//{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        id: 'mapbox.streets'
    });
    minimap.addLayer(mtLayer);
    try {
        if (google !== undefined) {
            var miniggl = new L.Google('SATELLITE');
        }
        else return false;
    }
    catch (e) {
        return false;
    }
    let miniMapboxSatelite = L.tileLayer('http://api.tiles.mapbox.com/v4/mapbox.satellite/{z}/{x}/{y}.png?access_token=pk.eyJ1Ijoia3I3NSIsImEiOiJjajJhOGk1NzMwMDBmMzJwYWMwOW1pOHhsIn0.ZExHsA_A-t5d_mUcLAP2eg', {
        maxZoom: 18
    });
    var minilayersControl = new L.Control.Layers({
        'Карта OpenStreetMap': mtLayer,
        'Снимки Google': miniggl,
        'Снимки Mapbox': miniMapboxSatelite
    });
    minimap.addControl(minilayersControl);
    minimap.setView(map.getCenter(), 8);
    minimap.on('movestart', N.onMiniMove);
    minimap.invalidateSize();
    return false;
};

N.onMiniMove = function (e) {
    if (minimapenabled === 1) {
        var miniMapCenter = minimap.getCenter();
        if (stopMap === 0) {
            stopMap = 1;
            map.setView(miniMapCenter);
            stopMap = 0;
        } else
            stopMap = 0;
    }
};

N.initBaseMap = function (usecache) {
    if (usecache && isLocal(jsbaseurl)) {
        /*osm = L.tileLayer('http://cache.mrgis02.mrsks.local/osm/{z}/{x}/{y}.png', {
            maxZoom: 18
        });*/
        osm = L.tileLayer('//{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18
        });
    } else {
        osm = L.tileLayer('//{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18
        });
    }
    mapbox = L.tileLayer('http://api.tiles.mapbox.com/v4/mapbox.streets/{z}/{x}/{y}.png?access_token=pk.eyJ1Ijoia3I3NSIsImEiOiJjajJhOGk1NzMwMDBmMzJwYWMwOW1pOHhsIn0.ZExHsA_A-t5d_mUcLAP2eg', {
        maxZoom: 18,
    });
    mrsks = L.tileLayer('http://cache.mrgis02.mrsks.local/mrsks/{z}/{x}/{y}.png', {
        maxZoom: 18
    });

    ggl = new L.Google('SATELLITE');
    mapboxSatellite = L.tileLayer('http://api.tiles.mapbox.com/v4/mapbox.satellite/{z}/{x}/{y}.png?access_token=pk.eyJ1Ijoia3I3NSIsImEiOiJjajJhOGk1NzMwMDBmMzJwYWMwOW1pOHhsIn0.ZExHsA_A-t5d_mUcLAP2eg', {
        maxZoom: 18,
    });
    doublegis = L.tileLayer('http://tile{s}.maps.2gis.com/tiles?v=1112&x={x}&y={y}&z={z}', {
        subdomains: ['1', '2', '3', '4'],
        //format: 'image/png',
        maxZoom: 18
    });
    map.addLayer(osm);
};

N.dec2hex = function (n) {
    n = parseInt(n);
    var c = 'ABCDEF';
    var b = n / 16;
    var r = n % 16;
    b = b - (r / 16);
    b = ((b >= 0) && (b <= 9)) ? b : c.charAt(b - 10);
    return ((r >= 0) && (r <= 9)) ? b + '' + r : b + '' + c.charAt(r - 10);
};

N.check_distance = function () {
    if (drow._active) {
        drow._calc_enable();
        $(".leaflet-interactive").css("cursor", "crosshair");
        $(".leaflet-marker-draggable").css("cursor", "pointer");
    } else {
        $(".leaflet-interactive").css("cursor", "pointer");
    }
    //drow._update();
};

N.onMove = function (e) {
    map.closePopup();
    var mapCenter = map.getCenter();
    $.cookie("lat", mapCenter.lat, {
        expires: 90
    });
    $.cookie("lon", mapCenter.lng % 180, {
        expires: 90
    });
    $.cookie("zoom", map.getZoom(), {
        expires: 90
    });
    if (e.distance !== undefined) {
        if (e.distance < 30) {
            return;
        }
    }

    cont.Check();
    cont.Set_near(map.getZoom());
    N.check_distance();
    if (parseInt(minimapenabled) === 1) {
        if (stopMap === 0) {
            stopMap = 1;
            minimap.setView(mapCenter);
            minimap.invalidateSize();
            //    stopMap = 0;
        } else
            stopMap = 0;
    }
};

N.get_key_sheet = function (name, layer, checked) {
    var key = [];
    key.name = name;
    if (checked !== undefined) {
        key.checked = checked;
    }
    key.function = function () {
        if (this.checked) {
            if (!map.hasLayer(layer)) {
                map.addLayer(layer);
            }
        } else {
            if (map.hasLayer(layer)) {
                map.removeLayer(layer);
            }
        }
    };
    return key;
};

N.enableLayer = function (inputQuery, lat, lng, geometry) {
    let input = $("[data-res-id=" + inputQuery + "]")[0];
    let latlng = L.latLng(lat, lng);
    for (let i = 0; i < geometry.length; i++) {
        [geometry[i][0], geometry[i][1]] = [geometry[i][1], geometry[i][0]];
    }
    map.fitBounds(geometry);
    if (!input.checked) {
        input.click();
    }
};

N.getZoom = function () {

    if (!this.resZoom || !this.poZoom || !this.filiationZoom) {

        $.get(location.origin + '/api/getFiliationZoom').done(function (data) {

            N.filiationScale = data[0].properties.filiationScale;

            N.poScale = data[0].properties.poScale;

            N.resScale = data[0].properties.resScale;

        }).fail(function (data) {

            console.error('Error occured trying to load scale levels');

        })

    }

};