class Line extends Electric {
    constructor(map, Leaflet, voltage, color, type_line) {
        super(map, Leaflet, voltage, color, type_line);
        this._type = 'Lines';
        if (type_line === undefined) {
            this.type_line = '';
        } else {
            this.type_line = type_line;
        }

        this.unick = parseInt(this.voltage, 10);
        this.arguments += '&type_line=' + this.type_line + '&no_opory=1';
        this.popup = new Popup_line();
        this.type_image = "line";
    }

    pointToLayer(feature, latlng) {
        var coltyp = feature.properties.kVoltage;
        var marker = new this.L.circleMarker(latlng, {
            radius: 4,
            weight: 1,
            fillColor: coltyp,
            opacity: 0.9,
            fillOpacity: 0.6,
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