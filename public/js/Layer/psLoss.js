class psLoss extends Layer {
    constructor(map, Leaflet, document) {
        super(map, Leaflet, document);
        this._type = 'Loss';
        this.cheack_near = 1;
        this.popup = new Popup_loss();
    }

    pointToLayer(feature, latlng, data) {
        var numofside = 4, rotatobj = 0, coltyp = "#000000";
        if (feature.properties.oTypePS === "1") {
            numofside = 4;
            rotatobj = 45;
        } else if (feature.properties.oTypePS === "2") {
            numofside = 3;
            rotatobj = 30;
        } else if (feature.properties.oTypePS === "3") {
            numofside = 9;
            rotatobj = 50;
        }

        coltyp = data.popup.createColorByValue(feature.properties.loss.color, 50);

        var marker = new L.RegularPolygonMarker(latlng, {
            numberOfSides: numofside,
            color: coltyp,
            fillcolor: coltyp,
            rotation: rotatobj,
            opacity: 0.9,
            fillOpacity: 0.6,
            weight: 2,
            radius: 8
        });
        marker.data = [];
        marker.category = data.get_category(feature.properties.loss.color);
        marker.data.loss = feature.properties.loss.loss_all;
        marker.data.type = marker.category;
        marker.data.color = coltyp;
        if (data._cluster !== undefined) {
            data._cluster.addLayer(marker);
            data.popup.bind_popup(marker, data.cheack_near);
        }
        return marker;
    }


    get_category(value) {
        var color = 3;
        switch (value) {
            case "RED":
                color = 0; // красный
                break;
            case "GREN":
                color = 1; // Зеленый
                break;
            case "YELW":
                color = 2; // Желтый
                break;
            case "BLAK":
                color = 3; // черный
                break;
            case "BLUE":
                color = 4; // синий
                break;
            default:
                color = 3; // черный
        }
        return color;
    }
}