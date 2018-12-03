class ZTP extends Layer {
    constructor(map, Leaflet, year_0, year_1, kapzatr) {
        super(map, Leaflet, document, year_0, year_1);
        this._type = 'Ztp';
        this.year_0 = year_0;
        this.year_1 = year_1;
        this.kapzatr = kapzatr;
        this.unick = year_0 + "_" + year_1;
        this.color = '#7030A0';
        this.arguments = '&year_0=' + this.year_0 + '&year_1=' + this.year_1 + '&kapzatr='+ kapzatr;
        if (year_0) {
            this.color = '#E46C0A';
        }
        if (year_0 >= 2) {
            this.color = '#BB0000';
        }
        this.image_height = 20;
        this.image_width = 20;
        this.popup = new Popup_ztp();
        this.type_image = "rhombus";
        if (kapzatr=== 1) this.type_image = "rhombus_black";
    }

    pointToLayer(feature, latlng) {
        var date_ztp = new Date(feature.properties.date);
        var now = new Date();
        var numofside = 4;
        var coltyp = '#BB0000';
        var rotatobj = 0;
        if (date_ztp.getFullYear() === now.getFullYear()) {
            coltyp = '#7030A0';
        }
        if (date_ztp.getFullYear() === (now.getFullYear() - 1)) {
            coltyp = '#E46C0A';
        }
        var fillCol = coltyp ;
        if (feature.properties.kapzatr){
            if (feature.properties.kapzatr!=="")
                fillCol = '#000000';
        }
        var marker = new L.RegularPolygonMarker(latlng, {
            numberOfSides: numofside,
            color: fillCol,
            weight: 2,
            fillColor: coltyp,
            rotation: rotatobj,
            opacity: 1,
            fillOpacity: 0.7,
            radius: 8
        });
        return marker;
    }
}