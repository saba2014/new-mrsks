class Electric extends Layer {
    constructor(map, Leaflet, voltage, color) {
        super(map, Leaflet, voltage, color);
        this.voltage = voltage;
        this.color = color;
        this.image_height = 20;
        this.image_width = 20;
        this.arguments += '&voltage=' + this.voltage;
        this.popup = new Popup_extended();
        this.markers = [];
        this.animation = new L.Animation(function (time) {
            return (time % 60000) / 10;
        }, function (progress) {
            let angle = progress % 360;

            this._el.setStyle({
                rotation: angle
            });

            this._el.redraw();
        });
        this.type_image = "opory";
    }

    add_animation(feature, marker, data) {
        if (feature.properties.highlight) {
            marker.animation = data.get_animation();
            data.markers.push(marker);
        }

    }

    load() {
        spinOFF();
        let self = this.options.data;
        for (let i = 0; i < self.markers.length; i++) {
            if (self.markers[i].animation !== undefined) {
                self.markers[i].animation.run(self.markers[i]);
            }
        }
    }

    loading() {
        spinON();
        let self = this.options.data;
        for (let i = 0; i < self.markers.length; i++) {
            if (self.markers[i].animation !== undefined) {
                self.markers[i].animation.stop(self.markers[i]);
            }
        }
        self.markers = [];
    }

    get_animation() {
        return new L.Animation(function (time) {
            return (time % 60000) / 300;
        }, function (progress) {
            function getcolor(begin, end, progress) {
                let begin_n = parseInt(begin, 16);
                let end_n = parseInt(end, 16);
                let new_color = parseInt(Math.abs(begin_n - (begin_n - end_n) *
                    Math.abs(Math.sin(progress))), 10);
                let string = new_color.toString(16);
                if (string.length < 2) {
                    string = "0" + string;
                }
                return string;
            }

            let col_end = "#00FF00";
            let col = this._el.defaultOptions.color;
            let new_color = "#" + getcolor(col.slice(1, 3), col_end.slice(1, 3), progress);
            new_color += getcolor(col.slice(3, 5), col_end.slice(3, 5), progress);
            new_color += getcolor(col.slice(5, 7), col_end.slice(5, 7), progress);
            let radius = Math.abs(Math.sin(progress)) * 4 + 8;
            this._el.setStyle({
                radius: radius,
                color: new_color
            });

            this._el.redraw();
        });
    }
}