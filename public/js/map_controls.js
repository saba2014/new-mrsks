/*
This code initiate controls and add some features, this code should be executed only for authorized users
 */

// добавляем объект для создания окна логина и управления процессом логина
//let JWT = new JWTAccessManager();

JWT.getRole(function () {
    if (JWT.usersRole === 0) {
        JWT.createLoginWindow();
    }
    else {
        // магический метод
        JWT.checkRole();
        // этот трюк позволил разрешить ребус, теперь всё работает!
        getRegions();
    }
});


function getRegions() {
    $.ajax({
        url: window.location.origin + '/api/getobjs?type=Filiations',
        beforeSend: function(request){
                    let token = new TokenStorage();
                    token.checkRelevance();
        },
        type: 'GET',
        success: function (response) {
            allRegions = response.features.map(function (item) {
                return {id: item.properties.composit_id, name: item.properties.name};
            });
            preloadDataForMenu(getScale);
            //getWays(getScale);
        },
        error: function(e) {
            if (e.status === 403) {
                JWT.createLoginWindow();
            }
            else {
                console.error("Error occured trying to load regions");
            }
        }
    });
}

function getScale() {
        $.get(location.origin + '/api/getFiliationZoom').done(function (data) {
            window.filiationScale = data[0].properties.filiationScale;
            window.poScale = data[0].properties.poScale;
            window.resScale = data[0].properties.resScale;
            addControlsToMap();
        }).fail(function (e) {
            if (e.status === 403) {
                logOut();
            }
            console.error('Error occured trying to load scale levels');
        });
}

function addControlsToMap() {
    if(cont === undefined) {
        return false;
    }
    cont.admin = adminenabled;
    cont.Set_Masks(mask);
    cont.Univers_Set_Masks(univers_mask);
    cont.Set_Layers(cache_url, shift);
    cont._set_overlayMap();
    cont.Set_Search_layer("#ff0000");
    //cont.Set_Menu(window.menu);
    map.removeControl(window.menu);
    delete window.menu;
    window.menu = new L.Control.Menu_tree();
    window.menu.Add_tree(map_tree);
    window.menu.Add_tree(cont.overlay_layer_tree);
    window.menu.Add_tree(cont.layer_tree);

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
            method: 'POST',
            beforeSend: function(request){
                let token = new TokenStorage();
                token.checkRelevance();
            },
            url: 'api/updatemenu',
            data: {tree: JSON.stringify(data)}
        }).done(callback);
    }

//post_menu(menu);
    map.addControl(menu);
    L.DomEvent.addListener(menu._container, 'wheel', L.DomEvent.stopPropagation);

    maxwidth = 0;
    $(".leaflet-control-layers-list").css("max-height", window.innerHeight - 100); //max-height of menu
    hg.set_reference("api/srtm");
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

    cont.Set_user_event(N.check_distance);
    cont.Set_layer_events();
    scale.addTo(map);

// поиск
    map.addControl(geocoding);
    var inp = $("#s_Input").val();
    if (inp!=="") getObjs();

    function geocode(page = 0, count = 30, flag = 0) { //rucadastrNum,
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

    /*
    show hidden options
     */
    if(!adminenabled){
        let options = document.querySelector('option[class=hidden]');
        for (let opt in options){
            if (opt.classList)
                opt.classList.remove('hidden');
        }
    }
    //document.getElementById('adminbutton').classList.remove('hidden');
    hash = new L.Hash(map,menu);
}