class WorkersCounters extends Layer {
    constructor(map, Leaflet) {
        super(map, Leaflet);
        this._type = 'ElectricMeters';
        this._zoom = 11;
        this.people = [];
        this.region=[];
        let currDate = new Date();
        currDate.setMonth(currDate.getMonth() - 1);
        this.time_a = new Date(currDate).toISOString().split('T')[0];
        this.time_b = new Date().toISOString().split('T')[0];
        this.popup = new Popup_WorkersTracks();
        this.onMap=0;
    }

    onEachFeatureLayerGJSON(feature, layer, data) {
        if (feature.properties.color) {
            layer.setStyle({fillColor: feature.properties.color});
        }
        data.popup.set_popup_text(feature);
        data.popup.bind_popup(layer, data.cheack_near);
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
                            popup_content +=  tmp + '<hr><br>';
                        }
                    });
                }
                popup.setContent(popup.getContent() + popup_content);
            }
        });
        return popup_content;
    }

    pointToLayer(feature, latlng, data) {
        var marker = new data.L.circleMarker(latlng, {
            radius: 8,
            weight: 1,
            fillColor: '#ff0000',
            opacity: 0.9,
            fillOpacity: 0.6
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
        let url = 'api/getobjs?type=' + this._type;
        let people = JSON.stringify(this.people);
        if (people !== "[]") {
            url += '&names=' + people;
        }
        if(this.time_a!=='0000-00-00'){
            url +='&timeA=' + this.time_a;
        }
        if(this.time_b!=='0000-00-00'){
            url +='&timeB=' + this.time_b;
        }
        url += '&bukrs=["' + this.region.join('","') + '"]';
        url += '&lon1=' + this.map.getBounds().getWest() + '&lon2=' + this.map.getBounds().getEast() + '&lat1=' +
            this.map.getBounds().getSouth() + '&lat2=' + this.map.getBounds().getNorth();
        return url;
    }
}