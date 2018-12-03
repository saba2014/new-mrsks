class Worker extends Layer {
    constructor(map, Leaflet, type_worker, filId, img = "img/icons/worker.svg") {
        super(map, Leaflet, type_worker);
        this._type = 'Workers';
        this.type_worker = type_worker;
        this.near = 0;
        this.cheack_near = 0;
        this.img = img;
        if(type_worker !== undefined) {
            this.arguments += '&type_worker=' + this.type_worker;
        }
        if (filId) {
            this.filId = filId;
            this.arguments +='&filId=' + this.filId;
        }
        this.popup = new Popup_worker();
        this.track = new Track(this.map, this.L);
        this.track_line = new Track(this.map, this.L);
        this.track_line.Popup = this.popup;
    }

    set_info(info, status) {
        var message = "";
        var info_class = "btn-sm ";
        switch (status) {
            case "send":
                message = "Отправлено";
                info_class += "alert-warning";
                break;
            case "delivered":
                message = "Доставлено";
                info_class += "alert-info";
                break;
            case "disconnected":
                message = "Пользователь недоступен";
                info_class += "alert-danger";
                break;
            case "error":
                message = "Ошибка сервера обратитесь к администратору";
                info_class += "alert-danger";
                break;
            case "read":
                message = "Прочитано";
                info_class += "alert-success";
                break;
            case "wrong_input":
                message = "Неверный ввод сообщения, повторите попытку";
                info_class += "alert-danger";
                break;
        }
        info.innerHTML = message;
        info.className = info_class;
    }

    check_popup(popup, near) {

    }

    del_some(id) {

    }

    open_popup(popup, near) {
        var traks = document.getElementsByClassName("track");
        var message = document.getElementsByClassName("message");
        var n = traks.length;
        var track_layer = this.track;
        var track_layer_line = this.track_line;
        var map = this.map;
        for (var i = 0; i < n; i++) {
            traks[i].onclick = function () {
                track_layer.arguments = '&points=1&deviceId=' + btoa(this.id);
                track_layer.onLayerAdd();
                track_layer_line.arguments = '&points=0&deviceId=' + btoa(this.id);
                track_layer_line.onLayerAdd();
                LayersInWork.push(track_layer);
                LayersInWork.push(track_layer_line);
                OpenLayersMenu();
                //track_layer.Check();
            };
        }
        if (message.length === 0) return;
        var children = message[message.length - 1].children;
        var self = this;
        var mess = document.getElementById('popup_message');
        $.ajax({
            method: "POST",
            url: "api/message",
            beforeSend: function(request){
                let token = new TokenStorage();
                token.checkRelevance();
            },
            data: {
                id: children.send.id
            }
        }).done(function (msg) {
            self.set_info(children.popup_lable.children.info, msg.status);
        });
        children.send.onclick = function () {
            if (mess.value.trim() == '') {
                self.set_info(children.popup_lable.children.info, "wrong_input");
                return;
            }
            $.ajax({
                method: "POST",
                beforeSend: function(request){
                    let token = new TokenStorage();
                    token.checkRelevance();
                },
                url: "api/message",
                data: {
                    id: this.id,
                    message: children.message.value
                }
            })
                .done(function (msg) {
                    self.set_info(children.popup_lable.children.info, msg.status);
                    $('#popup_message').hide();
                });
        };
        clearInterval(cont.loop);
    }

    close_popup(popup) {
        clearInterval(cont.loop);
        if(this.onMap){
            cont.loop = setInterval(function (a) {
                for (let i = 0; i < cont.loopLayers.length; i++) {
                    a[i].Check();
                }
            }, 5000, cont.loopLayers);
        }
    }

    pointToLayer(item, latlng, data) {
        if (item.properties.registration == true) {
            data.get_color = function (number_phone) {
                var number = number_phone.slice(4, 11);
                var n = 1, t = 9999999, hex = "";
                for (var i = 0; i < 6; i++) {
                    n *= 16;
                }
                number = Math.round(number * t / n);
                hex = number.toString(16);
                for (i = hex.length; i < 6; i++) {
                    hex = "0" + hex;
                }
                hex = "#" + hex;
                return hex;
            };

            var numofside = 5;
            var coltyp = '#BB0000';
            var rotatobj = 45;
            coltyp = data.get_color(item.properties.number.toString());
            /*var path = "img/icons/worker.svg";
            if (item.properties.type === "car") {
                path = "img/icons/car.svg";
            }*/
            let path = data.img;
            var myIcon = L.icon({
                iconUrl: path,
                iconSize: [30, 30]//,
            });
            var marker = new L.marker(latlng, {
                icon: myIcon
            });
//    var marker = new data.L.RegularPolygonMarker(latlng, {
//        numberOfSides: numofside,
//        color: coltyp,
//        weight: 2,
//        fillcolor: coltyp,
//        rotation: rotatobj,
//        opacity: 1,
//        fillOpacity: 0.7,
//        radius: 8
//    });
            if (data._cluster !== undefined) {
                data._cluster.addLayer(marker);
            }
            // if (item.properties.registration==true)
            return marker;
        }
    }

    onLayerAdd() {
        this.map.addLayer(this.LayerGJSON);
        this.onMap = 1;
        this.actualiseLayerGJSON();
        /*
        add layers to container loop array
         */
        if (cont.loopLayers.length === 0) {
            cont.loopLayers.push(this);
            cont.loop = setInterval(function (a) {
                for (let i = 0; i < cont.loopLayers.length; i++) {
                    a[i].Check();
                }
            }, 5000, cont.loopLayers);
        }
        else {
            cont.loopLayers.push(this);
        }
    }

    onLayerRemove() {
        //this.map.removeLayer(this.LayerGJSON);
        this.onMap = 0;
        this.track_line.onLayerRemove();
        this.track.onLayerRemove();
        if (typeof(window.SwitchOffTrackButton) === 'function') {
            SwitchOffTrackButton();
        }
        if (cont.loopLayers.length === 1) {
            cont.loopLayers = [];
            clearInterval(cont.loop);
        }
        else {
            let index = cont.loopLayers.indexOf(this);
            cont.loopLayers.splice(index, 1);
        }
        this._cluster.removeLayer(this.GJSON);
    }
}