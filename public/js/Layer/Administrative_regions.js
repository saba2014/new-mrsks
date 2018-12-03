class Administrative_regions extends Layer {
    constructor(map, Leaflet) {
        super(map, Leaflet);
        this._type = 'UniversRegions';
        this._search = false;
        this.near = 1;
    }

    style(feature) {
        var ss = {
            color: feature.properties.color,
            dashArray: "5, 10",
            fillOpacity: 0.00
        };
        return ss;
    }
}