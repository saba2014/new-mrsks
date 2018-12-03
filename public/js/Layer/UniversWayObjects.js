class UniversWayObjects extends Electric{
    constructor(map, Leaflet, id){
        super(map,Leaflet);
        this.popup=new Popup_univers_way();
        this.id = id;
        //this.arguments = '&regex='+JSON.stringify(this.tplnrs);
       // this._type = 'UniversWayObjects';
        this._type = "UniverseWays";
        this.arguments = "&id="+this.id+"&objects=1";
    }


   /* _get_api_url() {
        var url = 'api/getTplnrObjs?'+this.arguments+"&";
        if (this.api_url !== undefined) {
            url = this.api_url;
        }
        if (this.box) {
            url += 'lon1=' + this.map.getBounds().getWest() + '&lon2=' + this.map.getBounds().getEast() + '&lat1=' +
                this.map.getBounds().getSouth() + '&lat2=' + this.map.getBounds().getNorth();
        }
        return url;
    }*/

    onEachFeatureLayerGJSON(feature, layer, data) {
        if (feature.properties.kVoltage) {
            layer.setStyle({color: feature.properties.kVoltage});
        }
        data.popup.popup_text = feature;
        data.popup.bind_popup(layer, data.cheack_near);
    }

    /*check_popup(popup, near) {

    }*/

    pointToLayer(feature, latlng, data){
        let objType = feature.properties.TypeByTplnr;
        if (objType==="Подстанции") {
            return PS.createMarker(feature, latlng, data);
        }
        if (objType==="ТП"){
            return TP.createMarker(feature, latlng, data);
        }
        if (objType==="РП"){
            return RP.createMarker(feature, latlng, data);
        }
        if (feature.properties.TypeByTplnr==="Опора"||feature.properties.TypeByTplnr==="Отпайка"){
            let coltyp = feature.properties.kVoltage;
            let marker = new data.L.circleMarker(latlng, {
                radius: 4,
                weight: 1,
                fillColor: coltyp,
                opacity: 0.9,
                fillOpacity: 0.6,
            });
            /*let popup = new Popup_opory();
            popup.popup_text = feature;
            marker.bindPopup(popup.popup_text,{});*/
            return marker;
        }
    }
}