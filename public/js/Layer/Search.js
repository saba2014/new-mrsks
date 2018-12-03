class Search extends Layer {
    constructor(map, Leaflet, color) {
        super(map, Leaflet, color);
        this.color = color;
        this._type = "Lines";
        this.popup = new Popup_line();
    }

    actualiseLayerGJSON(tplnr) {
        if (this.map.hasLayer(this.LayerGJSON)) {
            this.LayerGJSON.refresh('api/getobjs?type=' + this._type + '&tplnr=' + tplnr + this.arguments);
        }
    }

    pointToLayer(feature, latlng) {
        var coltyp = '#BB0000';
        var marker = new window.L.circleMarker(latlng, {
            radius: 6,
            weight: 2,
            fillColor: coltyp,
            opacity: 0.9,
            fillOpacity: 0.6
        });
        return marker;
    }

    style(feature) {
        var coltyp = '#BB0000';
        var ss = {
            color: coltyp,
            weight: 4
        };
        return ss;
    }

    onLayerAdd(tplnr, location) {
        this.map.removeLayer(this.LayerGJSON);
        this.onMap = 1;
        if ((this.map.getZoom() > this._zoom)) {
            this._add(tplnr, location);
        }
    }

    _add(tplnr, location) {
        if (!this.map.hasLayer(this.LayerGJSON)) {
            this.map.addLayer(this.LayerGJSON);
            this.map.setView(location, this.map.getZoom(), {
                animate: false
            });
        }
        this.actualiseLayerGJSON(tplnr);
    }
}