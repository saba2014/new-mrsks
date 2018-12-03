/* global L, N, minimapenabled, mask, adminenabled, legendenabled, jsbaseurl, icons, terr, 
 univers_mask, mapbox, adminenabled */
window.addEventListener('showItem', loadCatcher);

function loadCatcher(e) {
    document.getElementById(e.detail).classList.remove('hidden');
}

// функции предназначены для управления видимостью кнопок "Миникарта" и "Легенда"
function showBootstrapButton(button) {
    $(button).addClass('d-md-block d-xl-block');
}
function hideBootstrapButton(button) {
    $(button).removeClass('d-md-block d-xl-block');
}


// добавляем объект для создания окна логина и управления процессом логина
let JWT = new JWTAccessManager();
JWT.getRole(function () {
    if (JWT.usersRole === 0) {
        JWT.createLoginWindow();
    }
    else {
        // магический метод
        JWT.checkRole();
    }
});

// этого куска кода в идеале вообще не должно существовать,
// но пока он остаётся здесь, а я пишу для него костыль :)
var allRegions;
if (true)
    // после рефреша не заходит в это условие
    if(JWT.usersRole !== undefined && JWT.usersRole > 0) {
    $.ajax({
        async: false,
        method: 'GET',
        beforeSend: function(request){
            let token = new TokenStorage();
            token.checkRelevance();
        },
        url: window.location.origin + '/api/getobjs?type=Filiations',
        success: success,
        error: error_handle
    })
}

function success(response) {
        console.log("regions");
    allRegions = response.features.map(function (item) {
        return {id: item.properties.composit_id, name: item.properties.name};
    });
}

function error_handle() {
    console.error("Error occured trying to load regions");
}


var usecache = true,
    zoomPS = 10,
    zoomZTP = 10,
    zoomLN = 13;

// usecache && (~jsbaseurl.search(/\w+\.mrsks\.ru/i)===0)
if (usecache && isLocal(jsbaseurl)) {
    //var cache_url = "http://cache.mrgis02.mrsks.local/pkk5";
    var cache_url = '//pkk5.rosreestr.ru';
} else {
    var cache_url = '//pkk5.rosreestr.ru';
}
var map = L.map('p_Map', {
    attributionControl: false,
    maxZoom: 20
}).setView([coord_1, coord_2], zoom || zoomLN);

// Controls
let cont = new Container(map, L);
var shift = new L.Control.Shift();
var scale = new L.control.scale({imperial: false});
var menu = new L.Control.Menu_tree();
var drow = new L.Control.Drow();
var hg = new L.Control.Height_graphic();
var d = new L.Control.Distance();
let drawPath = new L.Control.drawShortPath();
let hash;
let trainingControl = new L.Control.kingdomControl();
var geocoding = new L.Geocoding({});
window.search = false;

// minimap section ===============================================================================================================================================================
var minimap;
N.initMiniMap();

$("div#minimap-window").draggable({
    containment: "div#p_Map",
    scroll: false,
    grid: [20, 20],
    handle: "div#dhead"
});

minimapenabled = parseInt(minimapenabled);
if (minimapenabled === 1) {
    hideBootstrapButton('.minimapbutton');
    $('div#minimap-window').show();
} else {
    showBootstrapButton('.minimapbutton');
    $('div#minimap-window').hide();
}
// end minimap section ===========================================================================================================================================================

// legend section =================================================================================================================================================================
$("div#legend-window").draggable({
    containment: "div#p_Map",
    scroll: false,
    grid: [20, 20],
    handle: "div#dlegendhead"
});

if (legendenabled === 1) {
    hideBootstrapButton('.legendbutton');
    $('div#legend-window').show();
} else {
    showBootstrapButton('.legendbutton');
    $('div#legend-window').hide();
}
// End legen section =============================================================================================================================================================

//Base Maps
var osm;
var new_kadastr;
var mrsks;
var ggl;
var doublegis;
var mapbox;
let mapboxSatellite;
N.initBaseMap(usecache);

map.on('dragend', N.onMove);
map.on('zoom', N.onMove);
map.on('resize', N.onMove);

var key = [];
key.exclusive = 1;
var map_tree = new n_ary_tree(key, null);
if (!isLocal(jsbaseurl)){
//if ((jsbaseurl === "gis.mrsks.ru") || (jsbaseurl === "dev2.mrsks.ru") || (jsbaseurl === "dev5.mrsks.ru") ||  (jsbaseurl === "test.mrsks.ru") || (jsbaseurl === "map.mrsks.ru")) {
    var baseMaps = {
        'Карта OpenStreetMap': osm,
        'Web 2Gis': doublegis,
        'Снимки Mapbox': mapboxSatellite,
        'Mapbox': mapbox,
        'Снимки Google': ggl
    };
    map_tree.Add(N.get_key_sheet("Карта OpenStreetMap", osm, 1));
    map_tree.Add(N.get_key_sheet("Web 2Gis", doublegis));
    map_tree.Add(N.get_key_sheet("Mapbox", mapbox));
    map_tree.Add(N.get_key_sheet("Снимки Google", ggl));
    map_tree.Add(N.get_key_sheet("Снимки Mapbox", mapboxSatellite));

} else {
    var baseMaps = {
        'Карта OpenStreetMap': osm,
       // 'Карта схема ГеоМодуля': mrsks,
        'Web 2Gis': doublegis,
        'Mapbox': mapbox,
        'Снимки Google': ggl,
        'Снимки Mapbox': mapboxSatellite
    };
    map_tree.Add(N.get_key_sheet("Карта OpenStreetMap", osm, 1));
    //map_tree.Add(N.get_key_sheet("Карта схема ГеоМодуля", mrsks));
    map_tree.Add(N.get_key_sheet("Web 2Gis", doublegis));
    map_tree.Add(N.get_key_sheet("Mapbox", mapbox));
    map_tree.Add(N.get_key_sheet("Снимки Google", ggl));
    map_tree.Add(N.get_key_sheet("Снимки Mapbox", mapboxSatellite));
}

var menu = new L.Control.Menu_tree();

menu.Add_tree(map_tree);

menu.Add_tree(cont.overlay_layer_tree);
menu.Add_tree(cont.layer_tree);

function delete_parent(obj) {
    if (obj.parent !== undefined) {
        delete obj.parent;
    }
    for (var i = 0; i < obj.child.length; i++) {
        delete_parent(obj.child[i]);
    }
    var new_key = {};
    if ((obj.key !== undefined) && (obj.key !== null)) {
        if (obj.key.arguments !== undefined) {
            new_key.arguments = obj.key.arguments;
        }
        if (obj.key.name !== undefined) {
            new_key.name = obj.key.name;
        }
        if (obj.key.image !== undefined) {
            new_key.image = obj.key.image.innerHTML;
        }
    }
    obj.key = new_key;
}

function post_menu(menu, callback) {
    var data = menu.trees[1];
    delete_parent(data);
    $.ajax({
        beforeSend: function(request){
            console.log(request);
            let token = new TokenStorage();
           // token.checkRelevance();
        },
        method: 'POST',
        url: 'api/updatemenu',
        data: {tree: JSON.stringify(data)}
    }).done(callback);
}

//post_menu(menu);
map.addControl(menu);
menu.layersHash = menu.getAllNodes();
L.DomEvent.addListener(menu._container, 'wheel', L.DomEvent.stopPropagation);
maxwidth = 0;
/*L.DomEvent.addListener(menu._container, 'click', function () {
    var width = $(".leaflet-control-layers-list").css('width');
    width = parseInt(width, 10);
    if (width > maxwidth) maxwidth = width;
    $(".leaflet-control-layers-list").css("min-width", maxwidth + 'px');
});*/
$(".leaflet-control-layers-list").css("max-height", window.innerHeight - 100); //max-height of menu
var drow = new L.Control.Drow();
var hg = new L.Control.Height_graphic();
hg.set_reference("api/srtm");
var d = new L.Control.Distance();
drow.add_icons(icons);
drow.set_event_update(function () {
    if (this._active) {
        $(".leaflet-interactive").css("cursor", "crosshair");
        $(".leaflet-marker-draggable").css("cursor", "pointer");
    } else {
        $(".leaflet-interactive").css("cursor", "pointer");
    }
    $("#p_Report").children("#text").children("#dist").html(
        '<img class="leftimg" src="js/images/measure.png" border=0>' + " Расстояние: " +
        this._d2txt(this._distance_calc()) + "<hr>");
    var points = drow.get_points();
    hg.set_points(points);
});
drawPath.set_event_update(function () {
    if (this._active) {
        $(".leaflet-interactive").css("cursor", "crosshair");
        $(".leaflet-marker-draggable").css("cursor", "pointer");
    } else {
        $(".leaflet-interactive").css("cursor", "pointer");
    }
    $("#p_Report").children("#text").children("#dist").html(
        '<img class="leftimg" src="js/images/measure.png" border=0>' + " Расстояние: " +
        this._d2txt(this._distance_calc()) + "<hr>");
    var points = drawPath.get_points();
});
map.addControl(drow);
map.addControl(drawPath);
map.addControl(hg);
//map.addControl(trainingControl);

cont.Set_user_event(N.check_distance);
cont.Set_layer_events();
scale.addTo(map);
//L.control.scale({
//    imperial: false
//}).addTo(map);

// поиск
var geocoding = new L.Geocoding({});
map.addControl(geocoding);

function geocode(page = 0, count = 30, flag = 0) { //rucadastrNum,
    if (cont.search)
        cont.search.onLayerRemove();
    var a = $("#s_Select").val();
    var r = $("#s_RegList").val();
    if ((a === "rucadastrText") && (r === 0)) {
        alert("Выберите регион!");
    } else {
        geocoding.setOptions({
            provider: a,
            reg: r,
            page: page,
            count: count,
            flag: flag

        });
        geocoding.geocode($("#s_Input").val());
    }
}

function kadastr_shift(x, y) {
    cont.kadastr.LayerGJSON.setParams({
        shiftX: x,
        shiftY: y
    }, false);
}

map.on('popupclose', function (oPopup) {
    $("#p_Top").show();
    cont.close_popup(oPopup.popup);
});

map.on('popupopen', function (oPopup) {
    $("#p_Top").hide();
    if ((oPopup.popup.options.cheack_near !== 0) && ((oPopup.popup === undefined) || (oPopup.popup.options === undefined) ||
            (oPopup.popup.options.className !== "cluster_popup"))) {
        cont.check_popup(oPopup.popup);
    }
    cont.open_popup(oPopup.popup);
    if (drow._active) {
        var coord = [];
        coord.latlng = oPopup.popup.getLatLng();
        drow._add_point(coord);
    }
    $.post("api/srtm",
        {
            lat: oPopup.popup._latlng.lat.toString(),
            lon: oPopup.popup._latlng.lng.toString()
        },
        function (data, statust) {
            $(".elevat").html("Высота: " + data + "м");
        }
    ).then(function () {
    });
});

var spincnt = 0;

function spinON() {
    spincnt = spincnt + 1;
    if (spincnt === 1) {
        $('#spin').spin(true);
    }
}

function spinOFF() {
    spincnt = spincnt - 1;
    if (spincnt <= 0) {
        $('#spin').spin(false);
        spincnt = 0;
    }
}

map.on('layeradd', function (e) {
    // If added layer is currently loading, spin !
    e.layer.on('data:loading', function () {
        spinON();
    }, this);
    e.layer.on('data:loaded', function () {
        spinOFF();
        d.BringToFront();
    }, this);
    e.layer.on('data:error', function (error) {
        if(error.error === 'Forbidden'){
            JWT.createLoginWindow();
        }
    }, this);
}, this);

map.on('layerremove', function (e) {
    // Clean-up
    //spinOFF();
    e.layer.off('data:loaded');
    e.layer.off('data:loading');
}, this);

var funct = function () {
    map._onResize();
};

/*
 *
 * массив хранящий текущие слои
 */

var LayersInWork = [];

/*
 * функция удаления слоя согласно его названию
 */

var DeleteLayersInWork = function () {
    for (var i = 0; i < LayersInWork.length; i++)
        LayersInWork[i].onLayerRemove();
    SwitchOffTrack();
    LayersInWork = [];
};

minimap.invalidateSize();


/*
     * функция для определения того, какой хендлер будет обрабатывтаь клик по карте
     * при вызове без эвента включает обработчик события, с эвентом перехватывает его
     */
var enableClickHandler = function (e) {
    if (e === undefined) {
        if (drow._active === true || drawPath._active === true) {
            map.getContainer().style.cursor = 'crosshair';
        }
        else {
            map.getContainer().style.cursor = 'default';
        }
        this.map.off('click', enableClickHandler);
        this.map.on('click', enableClickHandler);
    }
    else {
        if (drow._active === true) {
            if (e) {
                drow._add_point(e);
            }
        }
        else if (drawPath._active === true) {
            if (e) {
                drawPath._add_point(e);
            }
        }
        else if (document.getElementById('s_Select').value === 'latlonSearch') {
            if (e) {
                window.N.onMapClick(e);
            }
        }
    }
};

