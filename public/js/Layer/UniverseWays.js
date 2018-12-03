class UniverseWays extends Layer {
    constructor(map, Leaflet) {
        super(map, Leaflet);
        this._type = 'UniverseWays';
    }


    onEachFeatureLayerGJSON(feature, layer, data) {
        if (feature.properties.color) {
            layer.setStyle({color: feature.properties.color});
        }
        if (feature.properties.name) {
            let text = '<b>Наименование маршрута: </b>' + feature.properties.name;
            let popup = L.popup().setContent(text);
            layer.bindPopup(popup);
        }
    }

    check_popup(popup, near) {
    }

    pointToLayer(){
    }

}