class PS extends Electric {
    constructor(map, Leaflet, voltage, color, geometry = null) {
        super(map, Leaflet, voltage, color);
        this._type = 'Ps';
        this._ps = 'Подстанции';
        this._geom = geometry;
        this.unick = parseInt(this.voltage, 10);
        this.arguments = '&voltage=' + this.voltage + '&ps=' + this._ps;
        if (this._geom!==null) this.arguments+='&geometry_type='+this._geom;
        this.popup = new Popup_ps();
        this.type_image = "circle";
    }

    pointToLayer(feature, latlng, data) {
        if (data._geom === "Point") {
            return PS.createMarker(feature, latlng, data);
        }
    }

    static createMarker(feature, latlng, data){
        let numofside = 9;
        let coltyp = feature.properties.kVoltage;
        let rotatobj = 50;
        let marker = new L.RegularPolygonMarker(latlng, {
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

    style(feature) {
        var ss = {
            color: feature.properties.kVoltage
        };
        return ss;
    }

}


