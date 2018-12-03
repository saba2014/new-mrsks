class univers_objs extends Layer {
    constructor(map, Leaflet, type) {
        super(map, Leaflet, type);
        this._type = 'UniversObjs';
        this._search = false;
        this.type_obj = type;
        this.arguments = '&type_obj=' + this.type_obj;
        this.cheack_near = 0;
        this.popup = new Popup_extended();
    }

    pointToLayer(feature, latlng, data) {
        //let classname = feature.properties.highlight? 'Highlighted' : '';
        var myIcon = L.icon({
            iconUrl: "img/icons/icon_" + feature.properties.type + ".png",
            iconSize: [30, 30],
            //className: classname universe objects highlight if redo comment check css and modal
        });
        var marker = new L.marker(latlng, {
            icon: myIcon
        });
        if (data._cluster !== undefined) {
            data._cluster.addLayer(marker);
        }
        return marker;
    }

    onLayerAdd() {
        this.map.removeLayer(this.LayerGJSON);
        this.onMap = 1;
        if ((this.map.getZoom() > this._zoom)) {
            if (!this.map.hasLayer(this.LayerGJSON)) {
                this.map.addLayer(this.LayerGJSON);
            }
            this.actualiseLayerGJSON();
        }
    }
}