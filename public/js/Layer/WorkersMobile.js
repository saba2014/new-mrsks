class WorkersMobile extends Layer {
    constructor(map, Leaflet) {
        super(map, Leaflet);
        this._type = 'MobileControllers';
        this._zoom = 9;
        this.near = 10;
        this.region = [];
        this.people = [];
        this.popup = new Popup_WorkersTracks();
        this.onMap=0;
    }

    onEachFeatureLayerGJSON(feature, layer, data) {
        data.popup.set_popup_text(feature);
        data.popup.bind_popup(layer, data.cheack_near);
    }


    pointToLayer(feature, latlng, data) {
        var path = "img/icons/worker.svg";
        var myIcon = L.icon({
            iconUrl: path,
            iconSize: [30, 30]//,
        });
        var marker = new L.marker(latlng, {
            icon: myIcon
        });
        return marker;
    }

    onLayerAdd(region) {
        this.region.push(region);
        document.getElementById('p_Workers').style.display = 'block';
        onCounter++;
        this.map.removeLayer(this.LayerGJSON);
        this.onMap++;
        if ((this.map.getZoom() > this._zoom)) {
            this._add();
        }
    }


    onLayerRemove(region) {
        let index= this.region.indexOf(region);
        this.region.splice(index,1);
            this.map.removeLayer(this.LayerGJSON);
            this.onMap--;
            onCounter--;
        if (onCounter===0) {
            document.getElementById('p_Workers').style.display = 'none';
            if(document.getElementById("p_Workers").classList.contains('workers-deployed')){
                document.getElementById('turnFilter').click();
            }
        }
    }


    _get_api_url() {
        let people = JSON.stringify(this.people);
        let url = 'api/getobjs?type=' + this._type;
        url += '&lon1=' + this.map.getBounds().getWest() + '&lon2=' + this.map.getBounds().getEast() + '&lat1=' +
            this.map.getBounds().getSouth() + '&lat2=' + this.map.getBounds().getNorth();
        url += '&bukrs=["' + this.region.join('","') + '"]';
        if (people !== "[]") {
            url += '&names=' + people;
        }
        return url;
    }

    check_popup(popup, near) {
        var popup_content = "";
        var self = this.popup;
        var Pnt = popup.getLatLng();
        var lng = Pnt.lng, lat = Pnt.lat;
        popup.setContent("");
        $.ajax({
            dataType: "json",
            beforeSend: function(request){
                let token = new TokenStorage();
                token.checkRelevance();
            },
            url: "api/getobjs?" + "type=" + this._type + '&bukrs=["' + this.region + '"]' + "&near=" + near + "&lon=" + lng + "&lat=" + lat
            + this.arguments,
            success: function (data) {
                if (data.features.length > 0) {
                    data.features.forEach(function (item, i, arr) {
                        self.set_popup_text(item);
                        var tmp = self.get_popup_text();
                        if (tmp&&i!==data.features.length-1) {
                            popup_content += tmp + '<hr><br>';
                        }
                    });
                }
                popup.setContent(popup.getContent() + popup_content);
            }
        });
        return popup_content;
    }
}
