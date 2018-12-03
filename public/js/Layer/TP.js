class TP extends PS {
    constructor(map, Leaflet, voltage, color, geometry = null) {
        super(map, Leaflet, voltage, color, geometry);
        this._ps = 'ТП';
        this.arguments = '&voltage=' + this.voltage + '&ps=' + this._ps;
        if (this._geom!==null) this.arguments+='&geometry_type='+this._geom;
        this.type_image = "triangle";
    }

    pointToLayer(feature, latlng, data) {
        return TP.createMarker(feature, latlng, data);
    }

    static createMarker(feature, latlng, data){
        var numofside = 3;
        var coltyp = feature.properties.kVoltage;
        var rotatobj = 30;
        var marker = new L.RegularPolygonMarker(latlng, {
            numberOfSides: numofside,
            color: coltyp,
            weight: 2,
            fillcolor: coltyp,
            rotation: rotatobj,
            opacity: 1,
            fillOpacity: 0.7,
            radius: 8
        });
        if (feature.properties.highlight) {
            data.__proto__.add_animation(feature, marker, data);
        }
        return marker;
    }
}